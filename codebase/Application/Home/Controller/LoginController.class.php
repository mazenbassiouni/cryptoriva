<?php

namespace Home\Controller;

use Common\Ext\Exception;
use Common\Ext\GoogleAuthenticator;
use Org\Util\Stringer;
use Think\Upload;

class LoginController extends HomeController
{

    public function googleLogin(){
        if (userid()) {
            redirect(U('Index/index'));
        }
        $client=googleclient();
        $auth_url = $client->loginURL();
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    }
    public function googleRedirect(){
        if (userid()) {
            redirect(U('Index/index'));
        }
        $code = I('get.code');
        try {
            $client=googleclient();

            // Verify the access token
            $access_info = $client->verify($code);
            $access_token=$access_info['access_token'];

            if(!$access_token){
                $this->error(L('Invalid Access Token'));
                redirect(U('Login/login'));
            }
            $payload=$client->profile($access_token);

            if ($payload) {

                $google_email=$payload['email'];

                $user_exist = M('User')->where(array('email' => $google_email))->find();
                $mo = M();
                $mo->startTrans();
                $rs = array();
                if(is_array($user_exist) && !empty($user_exist)){
                    $this->processLogin($user_exist,'GOOGLE-LOGIN');
                }else{
                    $invit = session('invit');

                    $invituser = M('User')->where(array('invit' => $invit))->find();

                    if (!$invituser) {
                        $invituser = M('User')->where(array('id' => $invit))->find();
                    }

                    if (!$invituser) {
                        $invituser = M('User')->where(array('username' => $invit))->find();
                    }

                    if (!$invituser) {
                        $invituser = M('User')->where(array('cellphone' => $invit))->find();
                    }

                    if ($invituser) {
                        $invit_1 = $invituser['id'];
                        $invit_2 = $invituser['invit_1'];
                        $invit_3 = $invituser['invit_2'];
                    } else {
                        $invit_1 = 0;
                        $invit_2 = 0;
                        $invit_3 = 0;
                    }

                    for (; true;) {
                        $tradeno = tradenoa();

                        if (!M('User')->where(array('invit' => $tradeno))->find()) {
                            break;
                        }
                    }
                    $add_user['invit']=$tradeno;
                    $add_user['invit_1']= $invit_1;
                    $add_user['invit_2']= $invit_2;
                    $add_user['invit_3']= $invit_3;

                    $add_user['username']=$payload['id'];
                    $add_user['email']=$payload['email'];
                    $add_user['firstname']=$payload['given_name'];
                    $add_user['lastname']=$payload['family_name'];
                    $add_user['password']=md5($payload['id'].rand(111100,9999999).time());
                    $add_user['status']=1;
                    echo "create user and login";



                    $rs[] = $mo->table('codono_user')->add($add_user);
                    $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $rs[0]));

                    if (check_arr($rs)) {
                        $mo->commit();

                        $user_exist = M('User')->where(array('email' => $google_email))->find();
                        $this->processLogin($user_exist,'GOOGLE-LOGIN');
                    } else {
                        $mo->rollback();
                        $this->error(L('Registration failed!'));
                    }


                }
                header("Location: /");
                exit;
            } else {
                // The access token is invalid
                // Show an error message or redirect the user to an error page
                // ...
                header("Location: /Content/E403");
            }
        }

        catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
        }

    }

    public function emailcode($email = 0)
    {
        $code = tradeno();
        session('real_verify', $code);

        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }

        if (M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email already exists, Please login instead!'));
        }
        safeLog($email, 15, 'Login/emailcode');
        $subject = "Signup code for " . SHORT_NAME;
        $content = 'Hello There,<br/>
			You have just tried signing up using our app of ' . SHORT_NAME . '<br/>
			Your verification code is :' . $code . ' <br/>
			Please disregard if you didnt attempt to register.
			';
        $mail_Sent = json_decode(tmail($email, $subject, $content));
        if ($mail_Sent->status == 1) {
            $this->success(L('Verification code has been sent'));
        } else {
            $this->error(L('Unable to send email Code, Retry in sometime'));
        }
    }
    function qrhash(){
        $new= Stringer::randString(40);
        $qrCode= md5($new . '_' . time() . mt_rand(1, 1000000));
        session('qrCode', $qrCode);
        $json=json_encode(['desktop_ip'=>get_client_ip(),'qr_secure'=>$qrCode]);
        return $json;
    }
    public function checkQr(){
        $code=session('qrCode');
        
        if($code && strlen($code)==32){

            $qr_details = M('Qrlogin')->where(array('qr_secret' =>$code,'status'=>0))->find();
            if (!$qr_details || $qr_details['qr_secret'] != $code || !($qr_details['userid']>0)){
                echo json_encode(['status'=>0,'message'=>'Not a login yet','data'=>$code]);exit;
            }   
            
               //@todo check and verify code 
                //find if code was before 40 secs ago
                //find if user ip and desktop_ip are same
                //find userid associated with code enabled 
                // if above all then give uid
                $uid=$qr_details['userid'];
                $user=false;
                if ($uid) {
                    $user = M('User')->where(array('id' => $uid))->find();
                    $remark = L('Login Via QRScan');
                }
                if (!$user) {
                    $this->error(L('INVALID_PASSWORD'));
                }
        
                if ($user['status'] != 1) {
                    $this->error(L('Your account has been frozen, please contact the administrator!'));
                }
                M('Qrlogin')->where(array('id' => $qr_details['id']))->save(array('status' => 1));
                session('qrCode',null);
                $this->processLogin($user,$remark);
        }

    }
    public function login()
    {
        if (userid()) {
            redirect(U('Index/index'));
        }
        
        $this->assign('qrhash',$this->qrhash());
        $this->display();
    }

    public function register()
    {
        if (userid()) {
            redirect(U('Index/index'));
        }

        if (userid()||session('reguserId')) {
            redirect('/');
        }
        $this->assign('qrhash',$this->qrhash());
        if (SIGNUP_MODEL == 1) {
            $this->display('Login/register');
        } else {
            $this->display('Login/signup');
        }
    }

    public function getuid($t = "0.0")
    {
        $arr["userid"] = userid();
        $arr["moble"] = username(userid());
        $arr["nickName"] = session("nickname");
        /*userInfo: {
        usd: '0',
        usdd: '0',
        mobile: '',
        nickName: '',
        token: '',
        userid: '',
        vip: {
            vipdengji: '0',
            fee_discounts: '0%'
        }
    },*/
        echo json_encode($arr);
    }


    public function webreg()
    {
        $this->display();
    }

    public function upregister($username = '', $password = '', $repassword = '', $verify = '', $invit = '', $cellphone = '', $cellphones = '', $cellphone_verify = '')
    {

        if (M_ONLY == 0) {
            if (!check_verify($verify)) {
                $this->error(L('Incorrect Captcha!'));
            }

            if (!check($username, 'username')) {
                $this->error(L('INVALID_USERNAME'));
            }

            if (!check($password, 'password')) {
                $this->error(L('INVALID_PASSWORD'));
            }

            if ($password != $repassword) {
                $this->error(L('Confirm password wrong!'));
            }
        } else {

            if (!check($password, 'password')) {
                $this->error(L('INVALID_PASSWORD'));
            }
            $username = $cellphone;
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }
        if (!$cellphones) {
            $this->error(L('INVALID COUNTRY FORMAT'));
        }

        if (!check($cellphone_verify, 'd')) {
            $this->error(L('INVALID_SMS_CODE'));
        }

        if ($cellphone_verify != session('real_verify')) {
            $this->error(L('INCORRECT_SMS_CODE'));
        }

        if (M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error(L('Phone number already exists!'));
        }

        if (M('User')->where(array('username' => $username))->find()) {
            $this->error(L('Username already exists'));
        }

        if (!$invit) {
            $invit = session('invit');
        }

        $invituser = M('User')->where(array('invit' => $invit))->find();

        if (!$invituser) {
            $invituser = M('User')->where(array('id' => $invit))->find();
        }

        if (!$invituser) {
            $invituser = M('User')->where(array('username' => $invit))->find();
        }

        if (!$invituser) {
            $invituser = M('User')->where(array('cellphone' => $invit))->find();
        }

        if ($invituser) {
            $invit_1 = $invituser['id'];
            $invit_2 = $invituser['invit_1'];
            $invit_3 = $invituser['invit_2'];
        } else {
            $invit_1 = 0;
            $invit_2 = 0;
            $invit_3 = 0;
        }

        for (; true;) {
            $tradeno = tradenoa();

            if (!M('User')->where(array('invit' => $tradeno))->find()) {
                break;
            }
        }

        $mo = M();
        $mo->startTrans();

        $rs = array();
        $rs[] = $mo->table('codono_user')->add(array('username' => $username, 'cellphone' => $cellphone, 'cellphones' => $cellphones, 'cellphonetime' => time(), 'password' => md5($password), 'invit' => $tradeno, 'tpwdsetting' => 1, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'addtime' => time(), 'status' => 1));
        $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $rs[0]));

        if (check_arr($rs)) {
            $mo->commit();
            session('reguserId', $rs[0]);
            $this->success(L('registration success!'));
        } else {
            $mo->rollback();
            $this->error(L('Registration failed!'));
        }
    }

    public function emailregister()
    {
        $username = I('post.username', '', 'text');
        $email = I('post.email', '', 'text');
        $verify = I('post.verify', '', 'text');
        $password = I('post.password', '', 'text');
        $invit = I('post.invit', '', 'text');
        if ($verify != session('real_verify')) {
            $this->error(L('Incorrect Email Code'));
        }

        if (!check($password, 'password')) {
            $this->error(L('INVALID_PASSWORD'));
        }


        if (!check($username, 'username')) {
            $this->error(L('Incorrect Username : 4-15 chars'));
        }
        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT') . $email);
        }

        if (M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email already exists!'));
        }
        if (M('User')->where(array('username' => $username))->find()) {
            $this->error(L('Username already exists!'));
        }

        if (!$invit) {
            $invit = session('invit');
        }

        $invituser = M('User')->where(array('invit' => $invit))->find();

        if (!$invituser) {
            $invituser = M('User')->where(array('id' => $invit))->find();
        }

        if (!$invituser) {
            $invituser = M('User')->where(array('email' => $email))->find();
        }

        if ($invituser) {
            $invit_1 = $invituser['id'];
            $invit_2 = $invituser['invit_1'];
            $invit_3 = $invituser['invit_2'];
        } else {
            $invit_1 = 0;
            $invit_2 = 0;
            $invit_3 = 0;
        }

        for (; true;) {
            $tradeno = tradenoa();

            if (!M('User')->where(array('invit' => $tradeno))->find()) {
                break;
            }
        }
        $add_user_info = array('username' => $username, 'email' => $email, 'password' => md5($password), 'invit' => $tradeno, 'tpwdsetting' => 1, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'status' => 1);

        $mo = M();
        $mo->startTrans();

        $rs = array();

        $rs[] = $mo->table('codono_user')->add($add_user_info);
        $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $rs[0]));
        $invit_insert = array('coin' => C('ref_award_coin'),'userid' => $invit_1, 'invit' => $rs[0], 'name' => C('ref_award_coin'), 'type' => 'Signup:Reward-' . C('ref_award_coin'), 'num' => C('ref_award_num'), 'mum' => C('ref_award_num'), 'fee' => '0', 'addtime' => time(), 'status' => 1);

        if (C('ref_award') && $invit_1 > 0 && $rs[0] > 0) {
            $rs[] = $mo->table('codono_invit')->add($invit_insert);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_1))->setInc(C('ref_award_coin'), C('ref_award_num'));
        }

        if (check_arr($rs)) {
            $mo->commit();
            session('reguserId', $rs[0]);
            $this->success(L('SUCCESSFULLY_REGISTERED'), U('User/index'));
        } else {
            $mo->rollback();
            $this->error(L('Registration Failed!'));
        }
    }


    public function emailsignup()
    {
        //@todo Protect against several request + captcha

        $username = I('post.username', '', 'text');
        $email = I('post.email', '', 'text');

        $password = I('post.password', '', 'text');
        $invit = I('post.invit', '', 'text');

        $verify = I('post.verify', '', 'text');

        $accounttype = I('post.accounttype', '', 'text');

        if ($accounttype != 1 && $accounttype != 2) {
            $this->error(L('Choose Account Type!'));
        }


        if (!check_verify($verify, 1, 1)) {
            $this->error(L('Incorrect Captcha!'));
        }


        if (!check($password, 'password')) {
            $this->error("Valid characters: a-z A-Z 0-9 !@#$%^*()+-");
        }

        if (!check($username, 'username')) {
            $this->error('Incorrect Username : 4-15 chars');
        }
        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT') . $email);
        }

        if (M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email already exists!'));
        }
        if (M('User')->where(array('username' => $username))->find()) {
            $this->error(L('Username already exists!'));
        }

        $client_ip = get_client_ip();
        $count = M('Signup')->where(array('email' => $email, 'status' => 0))->count();
        $IPCOUNT = M('Signup')->where(array('addip' => $client_ip, 'status' => 0))->count();
        if ($client_ip != '0.0.0.0' && $client_ip != '127.0.0.1' && $IPCOUNT > 3) {
            $this->error(L('Too many attempts to register from your IP, contact support!') . $client_ip);
        }
        if ($count > 3) {
            $this->error(L('Too many attempts to register, Please check your email or contact support!'));
        }
        $verify = tradeno();

        $add_user_info = array('username' => $username, 'accounttype' => $accounttype, 'email' => $email, 'password' => md5($password), 'invit' => $invit, 'addip' => $client_ip, 'addr' => get_city_ip(), 'verify' => $verify, 'addtime' => time());

        $mo = M();
        $mo->startTrans();

        $rs = array();

        $rs[] = $aid = $mo->table('codono_signup')->add($add_user_info);
        $link_code = cryptString($aid . '-' . $verify);
        $link = SITE_URL . 'Login/confirm/code/' . $link_code;

        $subject = "Welcome to " . SHORT_NAME . "!, Your signup confirmation";
        $content = "Hello " . $username . ",<br/>Thank you for choosing  " . SHORT_NAME . "<br/><strong>DO NOT CLICK ON THIS LINK IF YOU DIDNT SIGNUP WITH US!!</strong><br/>To complete the sign-up process,<br/><br/>Please visit following link :<br/> <strong>" . $link . "<strong><br/><br/><small><i>
			You are receiving this email because you registered on " . SHORT_NAME . " with this email address";
        $rs[] = M('Notification')->add(array('to_email' => $email, 'subject' => $subject, 'content' => $content));
        if (check_arr($rs)) {
            $mo->commit();
            $this->success(L('Please check your email for confirmation'));
        } else {
            $mo->rollback();
            $this->error(L('Registration Failed!'));
        }
    }
    public function test(){
        return time();
    }
    public function thankyou()
    {
        $this->display();
    }

    public function confirm($code)
    {

        $code = cryptString($code, 'd');
        $where['id'] = explode('-', $code)[0];
        $where['verify'] = explode('-', $code)[1];
        $where['status'] = 0;
        $result = M('Signup')->where($where)->find();
        if ($where['verify'] == $result['verify']) {
            //@todo create account
            //mark signup code as 1
            $invit = $result['invit'];
            $email = $result['email'];
            $username = $result['username'];
            $password = $result['password'];
            $accounttype = $result['accounttype'];


            //Check if email/username exists
            if (M('User')->where(array('email' => $email))->find() || M('User')->where(array('username' => $username))->find()) {
                $this->error(L('Email or Username already in Use!'));
            }
            $invituser = M('User')->where(array('invit' => $invit))->find();

            if (!$invituser) {
                $invituser = M('User')->where(array('id' => $invit))->find();
            }

            if (!$invituser) {
                $invituser = M('User')->where(array('email' => $email))->find();
            }

            if ($invituser) {
                $invit_1 = $invituser['id'];
                $invit_2 = $invituser['invit_1'];
                $invit_3 = $invituser['invit_2'];
            } else {
                $invit_1 = 0;
                $invit_2 = 0;
                $invit_3 = 0;
            }

            for (; true;) {
                $tradeno = tradenoa();

                if (!M('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }
            $add_user_info = array('username' => $username, 'accounttype' => $accounttype, 'addtime' => time(), 'email' => $email, 'password' => $password, 'invit' => $tradeno, 'tpwdsetting' => 1, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'status' => 1);

            $mo = M();
            $mo->startTrans();

            $rs = array();

            $rs[] = $uid = $mo->table('codono_user')->add($add_user_info);
            $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $rs[0]));
            $invit_insert = array('coin' => C('ref_award_coin'),'userid' => $invit_1, 'invit' => $rs[0], 'name' => C('ref_award_coin'), 'type' => 'Signup:Reward-' . C('ref_award_coin'), 'num' => C('ref_award_num'), 'mum' => C('ref_award_num'), 'fee' => '0', 'addtime' => time(), 'status' => 1);

            if (C('ref_award') && $invit_1 > 0 && $rs[0] > 0 && C('Coin')[C('ref_award_coin')]['name'] == C('ref_award_coin')) {
                $rs[] = $mo->table('codono_invit')->add($invit_insert);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_1))->setInc(C('ref_award_coin'), C('ref_award_num'));
            }

            if (check_arr($rs)) {
                M('Signup')->where(array('id' => $result['id']))->save(array('status' => 1));
                $mo->commit();
                session('userId', $uid);
                session('userName', $username);
                session('reguserId', $uid);
                redirect(U("Finance/index"));
            } else {
                $mo->rollback();
                $this->error(L('Registration Failed!'));
            }
        } else {
            safeLog($result['email'], 10, 'Email Register using Login/confirm ');
            $this->error(L('Invalid confirmation Link!'));
        }
    }

    public function welcome()
    {
        $this->display();
    }

    public function check_cellphone($cellphone = 0)
    {

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error(L('Phone number already exists!'));
        }

        $this->success('');

    }

    public function check_email($email = 0)
    {

        if (!check($email, 'email')) {
            $this->error(L('INVALID_FORMAT'));
        }

        if (M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email already exists!'));
        }
        safeLog($email, 10, 'Email Check on check_email ');
        $this->success('We have sent you a code via email!');
    }

    public function check_username($username = 0)
    {
	
        if (!check($username, 'username')) {
            $this->error(L('Invalid Username'));
        }

        if (M('User')->where(array('username' => $username))->find()) {
            $this->error(L('Username already exists!'));
        }
        
        $this->success('Success');
    }

    public function check_pwdcellphone($cellphone = 0)
    {

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (!M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error(L('The phone number does not exist!'));
        }

        $this->success('');

    }

    public function check_pwdemail($email = 0)
    {

        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }

        if (!M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email does not exist!'));
        }

        $this->success('');

    }


    public function real($email = 0, $type = 'sms', $cellphone = 0, $verify = 0, $cellphones = '')
    {
        $code = tradeno();
        session('real_verify', $code);
        $content = L('Your verification code is') . ' ' . $code;

        if (!check_verify($verify)) {
            $this->error(L('Incorrect Captcha!'));
        }
        if ($type == 'sms') {
            if (!check($cellphone, 'cellphone')) {
                $this->error(L('INVALID_PHONE_FORMAT'));
            }

            if (M('User')->where(array('cellphone' => $cellphone))->find()) {
                $this->error(L('Phone number already exists!'));
            }

            $full_number = $cellphones . $cellphone;
            if (send_cellphone($full_number, $content)) {
                if (MOBILE_CODE == 1) {
                    $this->success(L('Use Demo SMS Code:') . $code);
                } else {
                    $this->success(L('Verification code has been sent'));
                }
            } else {
                $this->error(L('Unable to send SMS Code, Retry in sometime'));
            }
        } else if ($type == 'email') {
            if (!check($email, 'email')) {
                $this->error(L('INVALID_EMAIL_FORMAT'));
            }

            if (M('User')->where(array('email' => $email))->find()) {
                $this->error(L('Email already exists!'));
            }
            //$mail_Sent= json_decode(SuperEmail::sendemail($email, "Verification Code", $content));
            $subject = "Thank you for signing up for " . SHORT_NAME;
            $content = "<br/><strong>DO NOT SHARE THIS CODE WITH ANYONE!!</strong><br/>To complete the sign-up process,<br/><br/>You may be asked to enter this confirmation code:<strong>" . $code . "<strong><br/><br/><small><i>
			You are receiving this email because you registered on " . SHORT_NAME . " with this email address";
            addnotification($email, $subject, $content);
            $this->success(L('Please check email for code'));

        }
    }


    public function register2()
    {
        if (!session('reguserId')) {
            redirect(U('Login/login'));
        }
        $this->display();
    }


    public function paypassword()
    {
        if (!session('reguserId')) {
            $this->error(L('Please login first!'));
        }
        if (M('User')->where(array('id' => session('reguserId'), 'paypassword' => array('exp', 'is not null')))->find()) {
            $this->error(L('This step has been already done!'));
        }
        $this->display();
    }


    public function upregister2($paypassword, $repaypassword)
    {
        if (!check($paypassword, 'password')) {
//            $this->error(L('Fund Pwd format error!'));
            $this->error("Valid characters: a-z A-Z 0-9 !@#$%^*()+-");
        }

        if ($paypassword != $repaypassword) {
            $this->error(L('INCORRECT_NEW_PWD'));
        }

        if (!session('reguserId')) {
            $this->error(L('Unauthorized access!'));
        }


        if (M('User')->where(array('id' => session('reguserId'), 'password' => md5($paypassword)))->find()) {
            $this->error(L('Fund Password and Login password cannot be the same!'));
        }

        if (M('User')->where(array('id' => session('reguserId')))->save(array('paypassword' => md5($paypassword)))) {
            $this->success(L('success!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    public function register3()
    {
        if (!session('reguserId')) {
            redirect(U('Login/login'));
        }
        $this->display();
    }

    public function truename()
    {
        if (!session('reguserId')) {
            redirect(U('Login/login'));
        }

        if (M('User')->where(array('id' => session('reguserId'), 'truename' => array('exp', 'is not null')))->find()) {
            $this->error(L('Please contact support to for RE-KYC'));
        }
        $this->display();
    }

    public function idimage()
    {
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'pdf');
        $upload->rootPath = './Upload/idcard/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }

    public function upregister3($truename, $idcard, $image)
    {
        if (!check($truename, 'truename')) {
            $this->error(L('Real name format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }

        if (!session('reguserId')) {
            $this->error(L('Unauthorized access!'));
        }
        if (!$image) {
            $this->error(L('Please upload ID card file!'));
        }

        if (M('User')->where(array('id' => session('reguserId')))->save(array('truename' => $truename, 'idcard' => $idcard, 'idcardimg1' => $image))) {
            $this->success(L('success!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    public function register4()
    {

        if (!session('reguserId')) {
            redirect(U('Login/login'));
        }

        $user = M('User')->where(array('id' => session('reguserId')))->find();


        if (!$user) {
            $this->error(L('Please register'));
        }
        if ($user['regaward'] == 0) {
            if (C('reg_award') == 1 && C('reg_award_num') > 0) {
                M('UserCoin')->where(array('userid' => session('reguserId')))->setInc(C('reg_award_coin'), C('reg_award_num'));
                M('User')->where(array('id' => session('reguserId')))->save(array('regaward' => 1));
            }
        }

        session('userId', $user['id']);
        session('userName', $user['username']);
        $this->assign('user', $user);
        $this->display();
    }


    public function info()
    {

        if (!session('reguserId')) {
            redirect(U('Login/login'));
        }

        $user = M('User')->where(array('id' => session('reguserId')))->find();


        if (!$user) {
            $this->error(L('Please register'));
        }
        if ($user['regaward'] == 0) {
            if (C('reg_award') == 1 && C('reg_award_num') > 0) {
                M('UserCoin')->where(array('userid' => session('reguserId')))->setInc(C('reg_award_coin'), C('reg_award_num'));
                M('User')->where(array('id' => session('reguserId')))->save(array('regaward' => 1));
            }
        }

        session('userId', $user['id']);
        session('userName', $user['username']);
        $this->assign('user', $user);
        $this->display();
    }


    public function chkUser($username)
    {
        if (!check($username, 'username')) {
            $this->error(L('INVALID_USERNAME'));
        }

        if (M('User')->where(array('username' => $username))->find()) {
            $this->error(L('Username already exists'));
        }

        $this->success('');
    }

    public function submit($username = "", $password = "", $cellphone = "", $cellphones = "", $verify = NULL)
    {
        if (C('login_verify')) {
            if (!check_verify($verify, $id = 1, $recap = 1)) {
                $this->error(L('Incorrect Captcha!'));
            }
        }
        $user = array();
        if (M_ONLY == 0) {
            //cellphone is holding value of username
            if (check($username, 'email')) {
                $user = M('User')->where(array('email' => $username))->find();
                $remark = L('Login Via Email');
            }
            if (!$user) {
                $user = M('User')->where(array('username' => $username))->find();
                $remark = L('Login with username');
            }

            if (!$user && check($username, 'cellphone')) {
                $user = M('User')->where(array('cellphone' => $username))->find();
                $remark = L('Login Via Mobile');
            }


        } else {

            if (!$user) {
                $user = M('User')->where(array('username' => $username))->find();
                $remark = L('Login Via Username');
            }
            if (check($cellphone, 'cellphone')) {
                $user = M('User')->where(array('cellphone' => $cellphone, 'cellphones' => $cellphones))->find();
                $remark = L('Login Via Mobile');
            }
            if (!$user && check($username, 'email')) {

                $user = M('User')->where(array('email' => $username))->find();

                $remark = L('Login Via Email');
            }
        }


        if (!$user) {
            $this->error(L('INVALID_PASSWORD'));
        }

        if (!check($password, 'password')) {
            $this->error(L('INVALID_PASSWORD'));
        }

        if (md5($password) != $user['password']) {
            $this->error(L('Password wrong!'));
        }

        if ($user['status'] != 1) {
            $this->error(L('Your account has been frozen, please contact the administrator!'));
        }

        $this->processLogin($user,$remark);


    }
    /*
     * Send User array from codono_user table
     */
     function processLogin($user,$remark='WebLogin'){
        $is_ga = $user['ga'] ? 1 : 0;
        //Google2FA IS ENABLED
        if ($is_ga == 1) {
            session('uid', $user['id']);
            session('uname', $user['username']);
            session('invitecode', $user['invit']);
            session('remarks', $remark);
            if($remark=='GOOGLE-LOGIN'){
				redirect('enter2fa');
			}else{
				$this->success('Enter 2FA!', U('enter2fa'));
			}
        }


        $ip = get_client_ip();

        $logintime = time();
        $token_user = md5($user['id'] . $logintime . rand(10000, 99999));
        session('token_user', $token_user);

        $mo = M();
        $mo->startTrans();

        $rs = array();

        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->setInc('logins', 1);

        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('token' => $token_user));

        $rs[] = $mo->table('codono_user_log')->add(array('userid' => $user['id'], 'type' => L('log in'), 'remark' => $remark, 'addtime' => $logintime, 'addip' => $ip, 'addr' => get_city_ip(), 'status' => 1));

        $login_date_time = date('Y-m-d H:i', $logintime) . '(' . date_default_timezone_get() . ')';
        $subject = "Successful Login From IP " . $ip . " - " . $login_date_time;
        $user_email = $user['email'];
        $user_name = $user['username'];

        $login_content = "Hi $user_name,<br/> We have detetcted a new login <br/><br/><br/><table style='border:2px solid black;width:100%'><tr style='border:1px solid black;width:100%'><td>Email</td><td>$user_email</td></tr><tr style='border:1px solid black;width:100%'><td>IP</td><td>$ip</td></tr><tr style='border:1px solid black;width:100%'><td>Time</td><td>$login_date_time</td></tr></table><br/><br/>";

        if (check_arr($rs)) {

            if (!$user['invit']) {
                for (; true;) {
                    $tradeno = tradenoa();

                    if (!M('User')->where(array('invit' => $tradeno))->find()) {
                        break;
                    }
                }

                M('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
            }


            session('userId', $user['id']);
            session('userName', $user['username']);

            $this->sub_check2fa($user, $rs[0]);

            //$this->addnotification($email, $subject, $content);
            addnotification($user_email, $subject, $login_content);

            //    exec($mail_Sent = json_decode(tmail($user_email, $subject, $login_content)));
            $mo->commit();

            $this->success(L('login successful!'),U('Index/index'));

        } else {
            session('codono_already', 0);
            $mo->rollback();
            $this->error(L('LOGIN_FAILED'));
        }
    }
    public function enter2fa()
    {
        if (session('userId') || session('uid') == 0) {
            redirect('/');
        }
        $this->display('enter2fa');
    }

    /**
     * @return void
     */
    public function check2fa()
    {
        $gacode = I('post.gacode', '', 'text');
        if (!$gacode) {
            $this->error(L('INVALID_CODE'));
        }
        $userx['id'] = $_SESSION['uid'];
        $userx['userName'] = $_SESSION['uname'];
        $userx['invit'] = $_SESSION['invitecode'];
        $userx['remarks'] = $_SESSION['remarks'];
        $user = M('User')->where(array('id' => $userx['id']))->find();

        $arr = explode('|', $user['ga']);
        $secret = $arr[0];
        $ga = new GoogleAuthenticator();
        $ga_verification = $ga->verifyCode($secret, $gacode, 1);
        if (!$ga_verification) {
            $this->error(L('Verification failed'));
        }

        $ip = get_client_ip();
        $logintime = time();
        $token_user = md5($user['id'] . $logintime);
        session('token_user', $token_user);

        $mo = M();
        $mo->startTrans();
        $rs = array();
        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->setInc('logins', 1);

        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('token' => $token_user));

        $rs[] = $mo->table('codono_user_log')->add(array('userid' => $user['id'], 'type' => L('log in'), 'remark' => $userx['remarks'], 'addtime' => $logintime, 'addip' => $ip, 'addr' => get_city_ip(), 'status' => 1));

        if (check_arr($rs)) {
            $mo->commit();

            if (!$user['invit']) {
                for (; true;) {
                    $tradeno = tradenoa();

                    if (!M('User')->where(array('invit' => $tradeno))->find()) {
                        break;
                    }
                }
                /* if user is eligible for trading bonus check */
                if (EligibleForTradeBonus($user['id'])) {
                    M('User')->where(array('id' => $user['id']))->setField('awardstatus', 1);
                }
                M('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
            }
            $remark = $user['id'] . '_' . $user['invit'];
            session('userId', $user['id']);
            session('userName', $user['username']);
            session('invit', $user['invit']);
            session('remark', $remark);
            $this->sub_check2fa($user, $rs[0]);
            $this->success(L('login successful!'), '/');
        } else {
            session('codono_already', 0);
            $mo->rollback();
            $this->error(L('LOGIN_FAILED'));
        }
    }

    public function loginout()
    {
        session(null);
        redirect('/');
    }

    public function findpwd()
    {
        $this->findpwdemail();
    }

    public function findpwdemail()
    {
        $this->assign('qrhash',$this->qrhash());
        if (IS_POST) {
            $input = I('post.');
            if ($input['email']) {
                safeLog($input['email'], 10, 'Login/findpwd');
                if (!check($input['email'], 'email')) {
                    $this->error(L('INVALID_EMAIL_FORMAT'));
                }

                if (!check($input['email_verify'], 'd')) {
                    $this->error(L('INVALID_CODE'));
                }

                if ($input['email_verify'] != session('findpwd_verify')) {
                    $this->error(L('INCORRECT_CODE'));
                }

                $user = M('User')->where(array('email' => $input['email']))->find();


                if (!$user) {
                    $this->error(L('Email does not exist!'));
                }

                if ($user['email'] != $input['email']) {
                    $this->error(L('Email is incorrect!'));
                }

                if (!check($input['password'], 'password')) {
                    $this->error(L('New password incorrect format!'));
                }


                if ($input['password'] != $input['repassword']) {
                    $this->error(L('INCORRECT_NEW_PWD'));
                }


                $mo = M();
                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('password' => md5($input['password'])));

                if (check_arr($rs)) {
                    $mo->commit();

                    $this->success(L('Successfully modified'));
                } else {
                    $mo->rollback();
                    $this->error('No changes were made!');
                }


            } else {


                if (!check($input['email'], 'email')) {
                    $this->error(L('INVALID_EMAIL_FORMAT'));
                }

                $user = M('User')->where(array('email' => $input['email']))->find();
                safeLog($input['email'], 10, 'Login/findpwd');
                if (!$user) {
                    $this->error(L('Email does not exist'));
                }

                if (!check($input['email_verify'], 'd')) {
                    $this->error(L('INVALID_CODE'));
                }

                if ($input['email_verify'] != session('findpwd_verify')) {
                    $this->error(L('INCORRECT_CODE'));
                }
                session("findpwdemail", $user['email']);
                $this->success(L('Verification success'));
            }

        } else {
            $this->display('findpwdemail');
        }
    }

    public function findpwdemailconfirm()
    {

        if (empty(session('findpwdcellphone'))) {
            session(null);
            redirect('/');
        }

        $this->display();
    }

    public function findpwdconfirm()
    {

        if (empty(session('findpwdcellphone'))) {
            session(null);
            redirect('/');
        }

        $this->display();
    }

    public function password_up($password = "")
    {


        if (empty(session('findpwdcellphone'))) {
            $this->error(L('Please return with the first step!'));
        }

        if (!check($password, 'password')) {
            $this->error(L('New Password incorrect format!'));
        }
        $user = M('User')->where(array('cellphone' => session('findpwdcellphone')))->find();

        if (!$user) {
            $this->error(L('Phone number does not exist'));
        }

        if ($user['paypassword'] == md5($password)) {
            $this->error("Funding Password and Login Password can not be same");
        }


        $mo = M();
        $mo->startTrans();

        $rs = array();
        $rs[] = $mo->table('codono_user')->where(array('cellphone' => $user['cellphone']))->save(array('password' => md5($password)));

        if (check_arr($rs)) {
            $mo->commit();
            $this->success(L('Successful operation'));
        } else {
            $mo->rollback();
            $this->error(L('Operation failed'));
        }

    }

    public function findpwdinfo()
    {

        if (empty(session('findpwdcellphone'))) {
            session(null);
            redirect('/');
        }
        session(null);
        $this->display();
    }


    public function findpaypwd()
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (IS_POST) {
            $input = I('post.');

            if (!check($input['username'], 'username')) {
                $this->error(L('INVALID_USERNAME'));
            }

            if (!check($input['cellphone'], 'cellphone')) {
                $this->error(L('INVALID_PHONE_FORMAT'));
            }

            if (!check($input['cellphone_verify'], 'd')) {
                $this->error(L('INVALID_SMS_CODE'));
            }

            if ($input['cellphone_verify'] != session('findpaypwd_verify')) {
                $this->error(L('INCORRECT_SMS_CODE'));
            }

            $user = M('User')->where(array('username' => $input['username']))->find();

            if (!$user) {
                $this->error(L('Username does not exist!'));
            }

            if ($user['cellphone'] != $input['cellphone']) {
                $this->error(L('Username or phone number wrong!'));
            }

            if (!check($input['password'], 'password')) {
                $this->error(L('New Fund Pwd format error!'));
            }

            if ($input['password'] != $input['repassword']) {
                $this->error(L('INCORRECT_NEW_PWD'));
            }

            $mo = M();
            $mo->startTrans();

            $rs = array();
            $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('paypassword' => md5($input['password'])));

            if (check_arr($rs)) {
                $mo->commit();

                $this->success(L('Successfully modified'));
            } else {
                $mo->rollback();
                $this->error('No changes were made!');
            }
        } else {
            $this->display();
        }
    }
	public function unlocking(){
		$this->display();
	}
    /**
     * @param $user
     * @param $value
     * @return void
     */
    private function sub_check2fa($user, $value): void
    {
        if (!$user['paypassword']) {
            session('regpaypassword', $value);
            session('reguserId', $user['id']);
        }

        if (!$user['truename']) {
            session('regtruename', $value);
            session('reguserId', $user['id']);
        }
        session('codono_already', 0);
    }
}
