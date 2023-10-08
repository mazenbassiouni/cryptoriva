<?php

namespace Admin\Controller;

class AppController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function config()
    {
        if (empty($_POST)) {
            $appc = D('Appc')->find();
            $appc['pay'] = json_decode($appc['pay'], true);
            $show_coin = json_decode($appc['show_coin'], true);
            $Coin = D('coin')->where('type in ("rgb","qbb" ,"xrp","esmart","blockio","cryptonote","cryptoapis","coinpay","waves") and status = 1')->select();
            $appc['show_coin'] = array();

            foreach ($Coin as $val) {
                $appc['show_coin'][] = array('id' => $val['id'], 'name' => $val['title'] . '(' . $val['name'] . ')', 'flag' => $show_coin ? (in_array($val['id'], $show_coin) ? 1 : 0) : 1);
            }

            $show_market = json_decode($appc['show_market'], ctrue);
            $Market = D('Market')->where('status = 1')->select();
            $appc['show_market'] = array();

            foreach ($Market as $val) {
                $coin_name = explode('_', $val['name']);
                $xnb_name = D('Coin')->where(array('name' => $coin_name[0]))->find()['title'];
                $rmb_name = D('Coin')->where(array('name' => $coin_name[1]))->find()['title'];
                $appc['show_market'][] = array('id' => $val['id'], 'name' => $xnb_name . '/' . $rmb_name . '(' . $val['name'] . ')', 'flag' => $show_market ? (in_array($val['id'], $show_market) ? 1 : 0) : 1);
            }

            $this->assign('appCon', $appc);
            $this->display();
        } else {
            $_POST['pay'] = json_encode($_POST['pay']);
            $_POST['show_coin'] = json_encode($_POST['show_coin']);
            $_POST['show_market'] = json_encode($_POST['show_market']);

            if (D('Appc')->save($_POST)) {
                $this->success('Saved successfully!');
            } else {
                $this->error('Not modified');
            }
        }
    }

    public function vip_config_list()
    {
        $coin = D('coin')->select();
        $coinMap = array();

        foreach ($coin as $val) {
            $coinMap[$val['id']] = $val['title'];
        }

        $this->assign('coinMap', $coinMap);
        $this->Model = D('AppVip');
        $where = array();
        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->order('tag asc')->select();

        foreach ($list as $key => $val) {
            $val['rule'] = json_decode($val['rule'], true);
            $list[$key] = $val;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function vip_config_edit()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (empty($input_post)) {
            $coin = D('Coin')->where('status = 1')->select();
            $this->assign('coin', $coin);

            if (isset($input_get['id']) && $input_get['id']) {
                $vipArr = D('AppVip')->where(array('id' => trim($input_get['id'])))->find();
                $vipArr['rule'] = json_decode($vipArr['rule'], true);
                $this->assign('idi', count($vipArr['rule']));
                $rule_t = str_repeat('1,', count($vipArr['rule']));
                $rule_t = mb_substr($rule_t, 0, -1);
                $this->assign('rule_str', '[' . $rule_t . ']');
                $this->assign('data', $vipArr);
            } else {
                $this->assign('rule_str', '[]');
                $this->assign('idi', 0);
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if (!$_POST['tag']) {
                $this->error('Sort order can not be empty');
            }

            if (!check($_POST['tag'], 'integer')) {
                $this->error('Sort order must be an integer!');
            }

            if ($res = D('AppVip')->where(array('tag' => $_POST['tag']))->find()) {
                if ($res['id'] != $_POST['id']) {
                    $this->error('Sort order' . $_POST['tag'] . ' already exists!');
                }
            }

            $_POST['rule'] = json_decode($_POST['rule'], true);
            $key_map = array();
            $rule = array();

            foreach ($_POST['rule'] as $val) {
                if (!isset($key_map[$val['id']])) {
                    $key_map[$val['id']] = 1;
                    $rule[] = $val;
                } else {
                    $this->error('Currency is not the same upgrade');
                }
            }

            $_POST['rule'] = json_encode($rule);

            if ($_POST['id']) {
                $rs = D('AppVip')->save($_POST);
            } else {
                $_POST['addtime'] = time();
                $rs = D('AppVip')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {
                $this->error('Without any modifications!');
            }
        }
    }

    public function vip_config_edit_status()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $input_post['id']);
        } else {
            $id = $input_get['id'];
        }

        if (empty($id)) {
            $this->error('please choose Data to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $input_get['method'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                if (D('Appadsblock')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('Without any modifications!');
                }

                break;

            default:
                $this->error('Illegal parameters');
        }

        if (D('Appadsblock')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('Without any modifications!');
        }
    }

    public function adsblock_list()
    {
        $rankMap = array();
        $AppVip = D('AppVip')->where(array('status' => 1))->select();

        foreach ($AppVip as $val) {
            $rankMap[$val['id']] = $val['name'];
        }

        $this->assign('rankMap', $rankMap);
        $this->Model = D('Appadsblock');
        $where = array();
        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function adsblock_edit()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (empty($input_post)) {
            $AppVip = D('AppVip')->where(array('status' => 1))->select();
            $this->assign('AppVip', $AppVip);

            if (isset($input_get['id'])) {
                $this->data = D('Appadsblock')->where(array('id' => trim($input_get['id'])))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($_POST['id']) {
                $rs = D('Appadsblock')->save($_POST);
            } else {
                $_POST['adminid'] = session('admin_id');
                $rs = D('Appadsblock')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {
                $this->error('Without any modifications!');
            }
        }
    }

    public function adsblock_edit_status()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $input_post['id']);
        } else {
            $id = $input_get['id'];
        }

        if (empty($id)) {
            $this->error('please choose Data to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $input_get['method'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                if (D('Appadsblock')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('Without any modifications!');
                }

                break;

            default:
                $this->error('Illegal parameters');
        }

        if (D('Appadsblock')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('Without any modifications!');
        }
    }

    public function ads_list($block_id)
    {
        if (empty($block_id) || !isset($block_id)) {
            $block_id = 1;
        }

        $block_id = intval($block_id);
        $ads_block = M('Appadsblock')->where(array('id' => $block_id))->find();
        $this->assign('ads_block', $ads_block);
        $this->Model = D('Appads');

        if ($block_id) {
            $where['block_id'] = $block_id;
        }

        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function ads_edit()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (empty($input_post)) {
            if (isset($input_get['id'])) {
                $this->data = D('Appads')->where(array('id' => trim($input_get['id'])))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Upload/ad/';
            $upload->autoSub = false;
            $info = $upload->upload();

            if ($info) {
                foreach ($info as $k => $v) {
                    $input_post[$v['key']] = $v['savename'];
                }
            }

            if ($input_post['id']) {
                $rs = D('Appads')->save($input_post);
            } else {
                $input_post['adminid'] = session('admin_id');
                $rs = D('Appads')->add($input_post);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {
                $this->error('Without any modifications!');
            }
        }
    }

    public function ads_edit_status()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $input_post['id']);
        } else {
            $id = $input_get['id'];
        }

        if (empty($id)) {
            $this->error('please choose Data to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $input_get['method'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                if (D('Appads')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('Without any modifications!');
                }

                break;

            default:
                $this->error('Illegal parameters');
        }

        if (D('Appads')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('Without any modifications!');
        }
    }

    public function ads_user()
    {
        $this->Model = M('AppVipuser');
        $where = array();
        $count = $this->Model->join('codono_user ON codono_user.id = codono_app_vipuser.uid')->join('codono_app_vip ON codono_app_vip.id = codono_app_vipuser.vip_id')->field('codono_user.username,codono_app_vipuser.*,codono_app_vip.name as vip_name,codono_app_vip.tag')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->join('codono_user ON codono_user.id = codono_app_vipuser.uid')->join('codono_app_vip ON codono_app_vip.id = codono_app_vipuser.vip_id')->field('codono_user.username,codono_app_vipuser.*,codono_app_vip.name as vip_name,codono_app_vip.tag')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);

        foreach ($list as $key => $val) {

        }

        $this->assign('page', $show);
        $this->display();
    }

    public function ads_user_detail($uid = NULL)
    {
        $where = array();
        $this->Model = D('AppLog');

        if ($uid) {
            $where['uid'] = $uid;
        }

        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function upload()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/app/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = '/Upload/app/' . $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }
}

?>