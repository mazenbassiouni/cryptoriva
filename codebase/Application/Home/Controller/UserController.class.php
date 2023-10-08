<?php

namespace Home\Controller;

use Common\Ext\GoogleAuthenticator;
use Think\Page;

class UserController extends HomeController
{
    public function mystatus()
    {
        if (!kyced()) {
            $this->error(L('Your KYC needs to be completed first!'));
        }
    }

    public function kyc($truename, $idcard, $idcardinfo, $address = "NoAddress")
    {
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN!'));
        }
        if (!check($truename, 'truename')) {
            $this->error(L('Real name format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }
        if (!check($idcardinfo, 'truename')) {
            $this->error(L('Please enter correct type of ID!'));
        }
        if (!check($address, 'address')) {
            $this->error(L('Please enter address!'));
        }
//idcardauth=2 =Pending for review
        if (M('User')->where(array('id' => userid()))->save(array('truename' => $truename, 'idcard' => $idcard, 'idcardinfo' => $idcardinfo, 'address' => addslashes($address), 'idcardauth' => 2))) {
            $this->success(L('success!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    /**
     * @param $pass
     */
    public function deletekyc($pass)
    {
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN!'));
        }
        if (M('User')->where(array('id' => userid()))->save(array('truename' => '', 'idcard' => '', 'idcardinfo' => '', 'idcardimg1' => '', 'idcardimg2' => '', 'idcardauth' => 0))) {
            $this->success(L('Deleted!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    public function index()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $user = $this->userinfo;
        $fiatcoins = array();
        if (!isset($user['apikey']) || $user['apikey'] == NULL) {
            $apikey = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
            M('User')->where(array('id' => userid()))->setField('apikey', $apikey);
        }
        foreach (C('coin') as $coinlist) {
            if ($coinlist['type'] == 'rmb' && $coinlist['zr_jz'] == 1) {
                $_fiat_coin['name'] = strtoupper($coinlist['name']);
                $_fiat_coin['img'] = $coinlist['img'];
                $fiatcoins[] = $_fiat_coin;
            }

        }

        if (!$user['fiat']) {
            $selected_coin = SYSTEMCURRENCY;
        } else {
            $selected_coin = $user['fiat'];
        }
        $is_ga = ($user['ga'] ? 1 : 0);
        $this->assign('is_ga', $is_ga);
        $this->assign('selected_coin', $selected_coin);
        $this->assign('fiatcoins', $fiatcoins);
        $this->assign('user', $user);
        $this->display();
    }

    public function nameauth()
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $user = M('User')->where(array('id' => $uid))->find();
        if (($user['idcardauth'] == '0' || $user['idcardauth'] == '3') && DEFAULT_KYC == 3) {
            redirect(U('Kyc/index'));
        }
        if (SUMSUB_KYC['status'] == '1' && DEFAULT_KYC == 2) {
            redirect(U('Sumsub/index'));
        }

        if ($user['idcard']) {
            $user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
        }

        $this->sub_nameauth($user);
        $this->display();
    }

    public function authentication()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $user = M('User')->where(array('id' => userid()))->find();
        /*
        if ($user['idcard']) {
            $user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
        }
        */
        $this->sub_nameauth($user);
        $this->display('User/nameauth');
    }

    public function doAuthentication($firstname, $lastname, $idcard, $idcardinfo, $country, $state, $city, $dob)
    {
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN!'));
        }
        if (!check($firstname, 'truename')) {
            $this->error(L('Firstname format error!'));
        }
        if (!check($lastname, 'truename')) {
            $this->error(L('Lastname format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }
        if (!check($idcardinfo, 'truename')) {
            $this->error(L('Please enter correct type of ID!'));
        }
        if (!check($country, 'address')) {
            $this->error(L('Please enter Country!'));
        }
        if (!check($state, 'address')) {
            $this->error(L('Please enter state!'));
        }
        if (!check($city, 'address')) {
            $this->error(L('Please enter city!'));
        }
        if (!check($dob, 'address')) {
            $this->error(L('Please enter dob!'));
        }

        if (M('User')->where(array('id' => userid()))->save(array('firstname' => $firstname, 'lastname' => $lastname, 'idcard' => $idcard, 'idcardinfo' => "NationalID", 'country' => addslashes($country), 'state' => addslashes($state), 'city' => addslashes($city), 'dob' => addslashes($dob), 'idcardauth' => 2))) {
            $this->success(L('success!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    public function password()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('user_password'));
        $this->display();
    }

    public function uppassword($oldpassword, $newpassword, $repassword, $cellphone_verify)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!session('real_cellphone')) {
            $this->error(L('Verification code has expired!'));
        }

        if ($cellphone_verify != session('real_cellphone')) {
            $this->error(L('Phone code error!'));
        } else {
            session('real_cellphone', null);
        }

        $this->sub_uppassword($oldpassword, $newpassword, $repassword);

        $rs = M('User')->where(array('id' => userid()))->save(array('password' => md5($newpassword)));

        if ($rs) {
            $this->success(L('Successfully modified'));
        } else {
            $this->error('No changes were made!');
        }
    }


    public function uppassword_qq($oldpassword = "", $newpassword = "", $repassword = "")
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if ($oldpassword == $newpassword) {
            $this->error(L('The new modification of password and password the same as the original!'));
        }
        $this->sub_uppassword($oldpassword, $newpassword, $repassword);
        $paypassword = M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($newpassword) == $paypassword) {
            $this->error(L('NEW_PWD_CANT_BE_SAME_AS_FUND_PASS'));
        }


        $rs = M('User')->where(array('id' => userid()))->save(array('password' => md5($newpassword)));

        if (!($rs === false)) {
            $this->success(L('Successfully modified'));
        } else {
            $this->error('No changes were made!');
        }
    }


    public function paypassword()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }


        $user = M('User')->where(array('id' => userid()))->find();
        if (!$user['paypassword']) {
            $this->assign('show_old', false);
        } else {
            $this->assign('show_old', true);
        }
        $this->assign('user', $user);

        $this->assign('prompt_text', D('Text')->get_content('user_paypassword'));
        $this->display();
    }


    public function uppaypassword_qq($oldpaypassword, $newpaypassword, $repaypassword)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }


        $rs = $this->sub_uppaypassword($oldpaypassword, $newpaypassword, $repaypassword);

        if (!($rs === false)) {
            $this->success(L('Successfully modified'));
        } else {
            $this->error('No changes were made!');
        }
    }


    public function uppaypassword($oldpaypassword, $newpaypassword, $repaypassword, $cellphone_verify)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!session('real_cellphone')) {
            $this->error(L('Verification code has expired!'));
        }

        if ($cellphone_verify != session('real_cellphone')) {
            $this->error(L('Phone code error!'));
        } else {
            session('real_cellphone', null);
        }

        $rs = $this->sub_uppaypassword($oldpaypassword, $newpaypassword, $repaypassword);

        if ($rs) {
            $this->success(L('Successfully modified'));
        } else {
            $this->error('No changes were made!');
        }
    }

    public function ga()
    {
        if (empty($_POST)) {
            if (!userid()) {
                redirect(U('Login/login'));
            }

            $this->assign('prompt_text', D('Text')->get_content('user_ga'));
            $user = $this->userinfo;
            $is_ga = ($user['ga'] ? 1 : 0);
            $this->assign('is_ga', $is_ga);

            if (!$is_ga) {
                $ga = new GoogleAuthenticator();
                $secret = $ga->createSecret();
                session('secret', $secret);
                $this->assign('Asecret', $secret);
                $qrCodeUrl = $ga->getQRCodeGoogleUrl($user['username'] . '%20-%20' . SHORT_NAME, $secret);
                $this->assign('qrCodeUrl', $qrCodeUrl);
            } else {
                $arr = explode('|', $user['ga']);
                $this->assign('ga_login', $arr[1]);
                $this->assign('ga_transfer', $arr[2]);
            }
            $this->display();
        } else {
            if (!userid()) {
                $this->error('Login has failed,please login again!');
            }

            $delete = $secret = '';
            $gacode = trim(I('ga'));
            $type = trim(I('type'));
            $ga_login = (I('ga_login') == false ? 0 : 1);
            $ga_transfer = (I('ga_transfer') == false ? 0 : 1);

            if (!$gacode) {
                $this->error('Enter 2FA Code!');
            }

            if ($type == 'add') {
                $secret = session('secret');

                if (!$secret) {
                    $this->error('2FA has expired,Please refresh the page!');
                }
            } else if (($type == 'update') || ($type == 'delete')) {
                $user = $this->userinfo;

                if (!$user['ga']) {
                    $this->error('2FA setup isnt done yet!');
                }

                $arr = explode('|', $user['ga']);
                $secret = $arr[0];
                $delete = ($type == 'delete' ? 1 : 0);
            } else {
                $this->error(L('Type is undefined'));
            }

            $ga = new GoogleAuthenticator();


            if ($ga->verifyCode($secret, $gacode, 1)) {
                $ga_val = ($delete == '' ? $secret . '|' . $ga_login . '|' . $ga_transfer : '');
                M('User')->save(array('id' => userid(), 'ga' => $ga_val));
                $this->success(L('Successful operation'));
            } else {
                $this->error(L('Verification failed'));
            }
        }
    }

    public function cellphone()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!ENABLE_MOBILE_VERIFY) {
            $this->error(L('No Access'));
        }

        $user = $this->userinfo;
        $verified=0;
        if ($user['cellphone'] && $user['cellphones']) {
        $verified=1;
        }
        $this->assign('verified', $verified);
        $this->assign('user', $user);
        $this->assign('prompt_text', D('Text')->get_content('user_cellphone'));
        $this->display();
    }

    public function upcellphone($cellphone, $cellphone_verify)
    {
        if (!ENABLE_MOBILE_VERIFY) {
            $this->error(L('No Access'));
        }
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (!check($cellphone_verify, 'd')) {
            $this->error(L('INVALID_SMS_CODE'));
        }

        if ($cellphone_verify != session('real_verify')) {
            $this->error(L('INCORRECT_SMS_CODE'));
        }
        $user_cellphone = $this->userinfo['cellphone'];
        if ($user_cellphone == $cellphone) {
            $this->error(L('Phone number already exists!'));
        }

        $rs = M('User')->where(array('id' => userid()))->save(array('cellphone' => $cellphone, 'cellphonetime' => time()));

        if ($rs) {
            $this->success(L('Mobile phone authentication is successful!'));
        } else {
            $this->error(L('Mobile phone authentication failure!'));
        }
    }


    public function alipay()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        D('User')->check_update();
        $this->assign('prompt_text', D('Text')->get_content('user_alipay'));
        $user = $this->userinfo; //M('User')->where(array('id' => userid()))->find();
        $this->assign('user', $user);
        $this->display();
    }

    public function upalipay($alipay = NULL, $paypassword = NULL)
    {
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }

        if (!check($alipay, 'cellphone')) {
            if (!check($alipay, 'email')) {
                $this->error(L('Alipay account format error!'));
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $rs = M('User')->where(array('id' => userid()))->save(array('alipay' => $alipay));

        if ($rs) {
            $this->success(L('Alipay certification success!'));
        } else {
            $this->error(L('Failure Alipay certification!'));
        }
    }

    public function tpwdset()
    {
		exit();
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();
        $this->assign('prompt_text', D('Text')->get_content('user_tpwdset'));
        $this->assign('user', $user);
        $this->display();
    }

    public function tpwdsetting()
    {
        if (userid()) {
            $tpwdsetting = $this->userinfo['tpwdsetting'];//M('User')->where(array('id' => userid()))->getField('tpwdsetting');
            exit($tpwdsetting);
        }
    }

    public function uptpwdsetting($paypassword, $tpwdsetting)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }


        if (($tpwdsetting != 1) && ($tpwdsetting != 2) && ($tpwdsetting != 3)) {
            $this->error(L('Options Error!') . $tpwdsetting);
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');
        if ($user_paypassword) {
            if (!check($paypassword, 'password')) {
                $this->error(L('Fund Pwd format error!'));
            }
        }
        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        $rs = M('User')->where(array('id' => userid()))->save(array('tpwdsetting' => $tpwdsetting));

        if (!($rs === false)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function bank()
    {
        redirect(U('Finance/bank'));
    }

    public function upbank($name, $bank, $bankprov, $bankcity, $bankaddr, $bankcard, $paypassword)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note the name of the wrong format!'));
        }

        if (!check($bank, 'a')) {
            $this->error(L('Bank malformed!'));
        }

        if (!check($bankprov, 'c')) {
            $this->error(L('Opening provinces format error!'));
        }

        if (!check($bankcity, 'c')) {
            $this->error('Opening provinces malformed2!');
        }

        if (!check($bankaddr, 'a')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'd')) {
            $this->error(L('Bank account number format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserBankType')->where(array('title' => $bank))->find()) {
            $this->error(L('Bank error!'));
        }

        $userBank = M('UserBank')->where(array('userid' => userid()))->select();

        foreach ($userBank as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same name Notes!'));
            }

            if ($v['bankcard'] == $bankcard) {
                $this->error(L('Bank card number already exists!'));
            }
        }

        if (10 <= count($userBank)) {
            $this->error('Each user can add upto 10 accounts max!');
        }

        if (M('UserBank')->add(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('Banks added successfully!'));
        } else {
            $this->error(L('Bank Add Failed!'));
        }
    }

    public function delbank($id, $paypassword)
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserBank')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserBank')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }

    public function wallet($coin = NULL)
    {
        redirect(U('Finance/myzr'));
    }


    /**
     * @param $coin
     * @param $name
     * @param $addr
     * @param $paypassword
     * @param string $dest_tag
     */
    public function upwallet($coin, $name, $addr, $paypassword, string $dest_tag = '')
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note the name of the wrong format!'));
        }

        if (!check($addr, 'dw')) {
            $this->error(L('Wallet address format error!'));
        }
        /*
		if (!check($dest_tag, 'dw')) {
            $this->error(L('Destination tag incorrect format!'));
        }
        */
        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('Coin')->where(array('name' => $coin))->find()) {
            $this->error(L('Currency wrong!'));
        }

        $userWallet = M('UserWallet')->where(array('userid' => userid(), 'coinname' => $coin))->select();

        foreach ($userWallet as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same wallet logo!'));
            }

            if ($v['addr'] == $addr) {
                $this->error(L('Wallet address already exists!'));
            }
        }

        if (10 <= count($userWallet)) {
            $this->error('Maximum 10 addresses allowed per coin!');
        }

        if (M('UserWallet')->add(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }

    public function delwallet($id, $paypassword)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserWallet')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserWallet')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }

    public function goods()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $userGoodsList = M('UserGoods')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();

        foreach ($userGoodsList as $k => $v) {
            $userGoodsList[$k]['cellphone'] = substr_replace($v['cellphone'], '****', 3, 4);
            $userGoodsList[$k]['idcard'] = substr_replace($v['idcard'], '********', 6, 8);
        }

        $this->assign('userGoodsList', $userGoodsList);
        $this->assign('prompt_text', D('Text')->get_content('user_goods'));
        $this->display();
    }

    public function upgoods($name, $truename, $idcard, $cellphone, $addr, $paypassword)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note the name of the wrong format!'));
        }

        if (!check($truename, 'truename')) {
            $this->error(L('Contact Name Format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('Tel format error!'));
        }

        if (!check($addr, 'a')) {
            $this->error(L('Contact address malformed!'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        $userGoods = M('UserGoods')->where(array('userid' => userid()))->select();

        foreach ($userGoods as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same address identity!'));
            }
        }

        if (10 <= count($userGoods)) {
            $this->error('Max 10 addresses allowed per person!!');
        }

        if (M('UserGoods')->add(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'idcard' => $idcard, 'truename' => $truename, 'cellphone' => $cellphone, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }


    public function upgoods_codono($name = "", $truename = "", $idcard = "", $cellphone = "", $addr = "", $paypassword = "", $prov = "", $city = "")
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'idcard')) {
            $this->error(L('ID card format incorrect!'));
        }

        if (!check($truename, 'truename')) {
            $this->error(L('Contact Name format incorrect!'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('Mobile format incorrect!'));
        }

        if (!check($addr, 'mostregex')) {
            $this->error(L('Address format incorrect!'));
        }

        if (!check($prov, 'mostregex')) {
            $this->error(L('Provinces format incorrect!'));
        }
        if (!check($city, 'mostregex')) {
            $this->error(L('City format incorrect!'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Incorrect Trading password!'));
        }

        $userGoods = M('UserGoods')->where(array('userid' => userid()))->select();

        foreach ($userGoods as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same address identity!'));
            }
        }

        if (10 <= count($userGoods)) {
            $this->error('Each person can add upto 10 addresses!');
        }

        if (M('UserGoods')->add(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'prov' => $prov, 'city' => $city, 'truename' => $truename, 'cellphone' => $cellphone, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }


    public function delgoods($id, $paypassword)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserGoods')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserGoods')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }

    public function log()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $where['status'] = array('egt', 0);
        $where['userid'] = userid();
        $Model = M('UserLog');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('prompt_text', D('Text')->get_content('user_log'));
        $this->display();
    }

    public function install()
    {
        echo 'ok' . discount('0.05');
    }

    public function updateFiat($fiat)
    {
        $uid = userid();
        if (strtolower(C('coin')[strtolower($fiat)]['name']) != strtolower($fiat) || C('coin')[strtolower($fiat)]['type'] != 'rmb' || !$fiat) {
            $this->error('Please select correct currency');
        }

        $fiat_already = M('User')->where(array('id' => $uid))->field('fiat')->find();
        if (strtoupper($fiat_already['fiat']) == strtoupper($fiat)) {
            $this->success($fiat . ' is already your default currency!');
        }
        $mo = M();
        $rs = $mo->table('codono_user')->where(array('id' => $uid))->save(array('fiat' => strtoupper($fiat)));
        if ($rs) {
            $this->success('Currency has been updated!');
        } else {
            $this->error('Currency could not be updated!');
        }
    }

    public function security()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->display();

    }

    public function preference()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->display();
    }

    public function freeze()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->display();
    }

    public function doFreeze()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $code = trim(I('pass'));
        if ($code != 'freeze') {
            $this->error("Please type freeze");
        }
        $reason = trim(I('reason'));
        if (!$reason) {
            $this->error("Please select a reason");
        }
        M('User')->where(array('id' => userid()))->save(array('status' => 0, 'freeze_reason' => $reason));
        session(null);

        $this->success("Your account has been frozen, Please contact support");

    }

    public function antiphishing()
    {
        $userid = userid();
        if (!$userid) {
            redirect(U('Login/login'));
        }
        $data = M('User')->where(array('status' => 1, 'id' => $userid))->find();
        $this->assign('antiphishing', $data['antiphishing']);
        $this->display();
    }

    public function saveAntiPhishing()
    {
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        $antiphishing = I('post.antiphishing', '', 'text');
        if (!check($antiphishing, 'username')) {
            $this->error(L('Enter antiphising code from 4-15 letters'));
        }


        $rs = M('User')->where(array('id' => userid()))->save(array('antiphishing' => $antiphishing));
        if ($rs) {
            $this->success(L('Successfully updated'));
        } else {
            $this->error(L('There were issues updating your antiphishing code.'));
        }
    }

    /**
     * @param $user
     * @return void
     */
    public function sub_nameauth($user): void
    {
        $imgstr = "";
        $imgnum = 0;

        if ($user['idcardimg1']) {

            $img_arr = explode("_", $user['idcardimg1']);

            foreach ($img_arr as $k => $v) {
                $imgstr = $imgstr . '<li style="height:100px;"><embed style="width:300px;height:100px;" src="/Upload/idcard/' . $v . '" /></li>';
                $imgnum++;
            }

            unset($img_arr);
        }
        $allowImg = false;

        if ($user['accounttype'] == 1) {
            if (($user['idcardauth'] != 1 && $imgnum < 4) || ($user['idcardauth'] != 1 && $imgnum == 4 && !empty($user['idcardinfo']))) {
                $allowImg = true;
            }
        } else {
            if (($user['idcardauth'] != 1 && $imgnum < 7) || ($user['idcardauth'] != 1 && $imgnum == 7 && !empty($user['idcardinfo']))) {
                $allowImg = true;
            }
        }

        $this->assign('user', $user);
        $this->assign('userimg', $imgstr);
        $this->assign('imgnum', $imgnum);
        $this->assign('allowImg', $allowImg);

        $this->assign('prompt_text', D('Text')->get_content('user_nameauth'));
    }

    /**
     * @param $oldpassword
     * @param $newpassword
     * @param $repassword
     * @return void
     */
    private function sub_uppassword($oldpassword, $newpassword, $repassword): void
    {
        if (!check($oldpassword, 'password')) {
            $this->error(L('Old password format error!'));
        }

        if (!check($newpassword, 'password')) {
            $this->error(L('The new password is malformed!'));
        }

        if ($newpassword != $repassword) {
            $this->error(L('Confirm New Password wrong!'));
        }

        $password = $this->userinfo['password']; //M('User')->where(array('id' => userid()))->getField('password');

        if (md5($oldpassword) != $password) {
            $this->error(L('Old login password is incorrect!'));
        }
    }

    /**
     * @param $oldpaypassword
     * @param $newpaypassword
     * @param $repaypassword
     * @return bool|float|int|string
     */
    private function sub_uppaypassword($oldpaypassword, $newpaypassword, $repaypassword)
    {
        $uid = userid();
        if (!check($newpaypassword, 'password')) {
            $this->error(L('The new Fund Pwd format error!'));
        }

        if ($newpaypassword != $repaypassword) {
            $this->error(L('Confirm New Password wrong!'));
        }

        $user = M('User')->where(array('id' => $uid))->find();

        if ($user['paypassword']) {
            if (!check($oldpaypassword, 'password')) {
                $this->error(L('Old Fund Pwd format error!'));
            }

            if (md5($oldpaypassword) != $user['paypassword']) {
                $this->error(L('Old Fund Pwd is wrong!'));
            }

        }

        if (md5($newpaypassword) == $user['password']) {
            $this->error(L('Fund Pwd and login password can not be the same!'));
        }

        return M('User')->where(array('id' => userid()))->save(array('paypassword' => md5($newpaypassword)));
    }
}