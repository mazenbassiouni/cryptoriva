<?php
namespace Home\Controller;
class DemoController extends HomeController
{
	public function __construct(){
		parent::__construct();
        die('To use this demo feature for auto login , comment this line number '.__LINE__);
	}
    public function autologin()
    {

		if(userid()){
			redirect(U('/'));
		}
		$users=$this->getUsers();
		$count=sizeof($users);
		$user=$users[rand(1,$count)];
		
		$logintime = time();
        $token_user = md5($user['id'] . $logintime);
        session('token_user', $token_user);
			if ($user['token'] != session('token_user')) {
				$this->setLogin($user['id'],$user['username'],$token_user);
				$this->giveBalance($user['id']);
				redirect(U('/'));

			}else{
				$this->login();
				
			}
		
    }
	private function giveBalance($uid){
			$mo=M();
		 $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc('btc', 1);
		  return $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc('usdt', 100);
	}
	private function setLogin($uid,$username,$token){
			session('userId', $uid);
            session('userName', $username);
			$this->setToken($uid,$token)	;
			
	}
	private function getUsers(){
		$mo=M();
		return $mo->table('codono_user')->where(array('status' => 1,'ga'=>''))->field('id,token,username,ga')->limit(30)->select();
	}
	private function setToken($uid,$token){
		$mo=M();
        return $mo->table('codono_user')->where(array('id' => $uid))->save(array('token' => $token));
	}
}