<?php

namespace Home\Controller;

class PromoteController extends HomeController
{
    public function __construct()
    {
        parent::__construct();
//        if (PROMOTE_ALLOWED ) {
//            $this->assign('type', 'Oops');
//            $this->assign('error', 'Oops, Currently Promote is disabled!');
//            $this->display('Content/error');
//        }
    }
    public function index($id = NULL)
    {
        $where = "invit_1<>'' and invit_1>0";
        $list = M('User')->field(array('count(*)' => 'pnum', 'invit_1' => 'uid'))->where($where)->group('invit_1')->order('pnum desc')->limit(10)->select();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['uid']))->getField('username');
        }

        //select invit,sum(fee) as jiner from codono_invit  where  group by invit ORDER BY jiner desc
       // $where = "type like '%Top award%' and userid in (select id from codono_user)";
	    $where = "userid in (select id from codono_user)";
        $list_jiner = M('Invit')->field(array('sum(fee)' => 'jiner', 'userid'))->where($where)->group('userid')->order('jiner desc')->limit(10)->select();
		
        foreach ($list_jiner as $k => $v) {
            $username_before=M('User')->where(array('id' => $v['userid']))->getField('username');
			$list_jiner[$k]['username'] = substr_replace($username_before, '****', 3, 4);
			
        }

        $this->assign('list_jiner', $list_jiner);
        $this->assign('list', $list);
        $this->display();
    }


}