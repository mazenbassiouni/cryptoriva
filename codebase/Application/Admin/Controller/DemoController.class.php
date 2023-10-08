<?php

namespace Admin\Controller;

class DemoController extends \Think\Controller
{
	public function __construct(){
		parent::__construct();
        die('To use this demo feature for auto login , comment this line number '.__LINE__);
	}

	public function autologin(){

		$admin = M('Admin')->where(array('id' => 1))->find();

        session('admin_id', $admin['id']);
        session('admin_username', $admin['username']);
        session('admin_password', $admin['password']);		
		session('2fa_attempt',null);
        $this->success(L('login successful!'),U('Admin/Index'));
        
	}

}

?>