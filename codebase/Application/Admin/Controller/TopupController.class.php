<?php

namespace Admin\Controller;

class TopupController extends AdminController
{
    public function index($name = NULL)
    {
        //$this->checkUpdata();
        $where = array();

        if ($name && ($userid = D('User')->get_userid($name))) {
            $where['userid'] = $userid;
        }

        $where['status'] = array('neq', -1);
        $count = M('Topup')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Topup')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = D('User')->get_username($v['userid']);
            $list[$k]['mum'] = Num($v['mum']);
            $list[$k]['addtime'] = addtime($v['addtime']);
            $list[$k]['endtime'] = addtime($v['endtime']);
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function delete($id = NULL)
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
        if (D('Topup')->setStatus($id, 'delete')) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function repeal($id = NULL)
    {
        $topup = M('Topup')->where(array('id' => $id))->find();

        if (!$topup) {
            $this->error('does not exist!');
        }

        if ($topup['status'] != 0) {
            $this->error('We have been treated!');
        }

        $mo = M();
        $mo->startTrans();
        $rs = array();
        $user_coin = $mo->table('codono_user_coin')->where(array('userid' => $topup['userid']))->find();

        if (!$user_coin) {
            session(null);
            $this->error('User error property!');
        }

        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $topup['userid']))->setInc($topup['type'], $topup['mum']);
        $rs[] = $mo->table('codono_topup')->where(array('id' => $id))->save(array('endtime' => time(), 'status' => 2));

        if (check_arr($rs)) {
            $mo->commit();
            // removed unlock/lock
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error('operation failed!');
        }
    }

    public function resume($id = NULL)
    {
        if (empty($id)) {
            $this->error(L('INCORRECT_REQ'));
        }

        $topup = M('Topup')->where(array('id' => $id))->find();

        if (!$topup) {
            $this->error('data error!');
        }

        if (topup($topup['cellphone'], $topup['num'], md5($topup['id']))) {
            if (D('Topup')->setStatus($id, 'resume')) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {
                $this->error(L('OPERATION_FAILED'));
            }
        } else {
            $this->error('Third-party payment failure!');
        }
    }

    public function config()
    {

        $Config_DbFields = M('Config')->getDbFields();

        if (!in_array('topup_appkey', $Config_DbFields)) {
            M()->execute('ALTER TABLE `codono_config` ADD COLUMN `topup_appkey` VARCHAR(200)  NOT NULL   COMMENT \'name\' AFTER `id`;');
        }

        if (!in_array('topup_openid', $Config_DbFields)) {
            M()->execute('ALTER TABLE `codono_config` ADD COLUMN `topup_openid` VARCHAR(200)  NOT NULL   COMMENT \'name\' AFTER `id`;');
        }

        if (!in_array('topup_zidong', $Config_DbFields)) {
            M()->execute('ALTER TABLE `codono_config` ADD COLUMN `topup_zidong` VARCHAR(200)  NOT NULL   COMMENT \'name\' AFTER `id`;');
        }

        if (empty($_POST)) {
            $this->display();
        } else if (M('Config')->where(array('id' => 1))->save($_POST)) {
            $this->success('Changes Saved!');
        } else {
            $this->error('No changes were made!');
        }
    }

    public function type()
    {
        $where = array();
        $where['status'] = array('neq', -1);
        $count = M('TopupType')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('TopupType')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function forbidType($id = NULL)
    {
        if (D('Topup')->setStatus($id, 'forbid', 'TopupType')) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function resumeType($id = NULL)
    {
        if (D('Topup')->setStatus($id, 'resume', 'TopupType')) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function coin()
    {
        $where = array();
        $where['status'] = array('neq', -1);
        $count = M('TopupCoin')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('TopupCoin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function forbidCoin($id = NULL)
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
        if (D('Topup')->setStatus($id, 'forbid', 'TopupCoin')) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function resumeCoin($id = NULL)
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
        if (D('Topup')->setStatus($id, 'resume', 'TopupCoin')) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function deleteCoin($id = NULL)
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
        if (D('Topup')->setStatus($id, 'del', 'TopupCoin')) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function editCoin($id = NULL)
    {
        if (empty($_POST)) {
            if ($id) {
                $this->data = M('TopupCoin')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }
            if (!C('coin')[$_POST['coinname']]) {
                $this->error('Currency wrong!');
            }

            if ($_POST['id']) {
                $rs = M('TopupCoin')->save($_POST);
            } else {
                if ($id = M('TopupCoin')->where(array('coinname' => $_POST['coinname']))->find()) {
                    $this->error('Currency exist!');
                }

                $rs = M('TopupCoin')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {
                $this->error(L('OPERATION_FAILED'));
            }
        }
    }

    public function checkUpdata()
    {
    }
}