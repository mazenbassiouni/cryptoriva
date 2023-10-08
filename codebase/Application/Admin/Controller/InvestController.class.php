<?php

namespace Admin\Controller;

class InvestController extends AdminController
{
    public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coinname = '')
    {
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $map = array();
        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);
        if ($status) {
            $map['status'] = $status;
        }
        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
        if ($field && $name) {
            $map[$field] = $name;
        }
        if ($coinname) {
            $map['coinname'] = $coinname;
        }
        if ($status) {
            $map['status'] = $status;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        $data = M('Investbox')->where($map)->order($order_set)->select();
        $count = M('Investbox')->where($map)->order($order_set)->count();
        $builder = new BuilderList();
        $builder->title('Invest Box:');
        $builder->titleList('Investments', U('Invest/list'));
        $builder->button('add', 'Add', U('Invest/edit'));
        $builder->keyId();
        $builder->keyText('coinname', 'Coin');
        $builder->keyText('percentage', '%');
        $builder->keyText('period', 'Period in days');
        $builder->keyPrice('minvest', 'Minvest');
        $builder->keyPrice('maxvest', 'Maxvest');
        $builder->keyText('creatorid', 'creatorid');

        $builder->keyStatus('status', 'Status', array('Submitted', 'Approved', 'Reject', 'Completed', 'Upcomings'));
        $coinname_arr = array('' => 'Coin');
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
        $builder->search('field', 'select', array('creatorid' => 'creatorid'));
        $builder->search('status', 'select', array('Submitted', 'Approved', 'Reject', 'Completed', 'Upcomings'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyDoAction('Invest/edit?id=###', 'Edit', 'Option');
        $builder->keyDoAction('Invest/deleteInvest?id=###', 'Delete', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function deleteInvest($id = NULL)
    {
        $where['id'] = $id;
        if (M('Investbox')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('Without any modifications!');
        }

    }

    public function dicerolls($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coinname = '')
    {
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $map = array();
        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
        if ($field && $name) {
            $map[$field] = $name;
        }
        if ($coinname) {
            $map['coinname'] = $coinname;
        }
        if ($status) {
            $map['status'] = $status;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        $data = M('Dice')->where($map)->order($order_set)->select();
        $count = M('Dice')->where($map)->count();
        $builder = new BuilderList();
        $builder->title('Dice Rolls : Status >>> 1=win,2=lost');
        $builder->titleList('DiceRolls', U('Invest/dicerolls'));
        $builder->keyId();
        $builder->keyText('coinname', 'Coin');
        $builder->keyText('call', 'call');
        $builder->keyText('number', 'Number');
        $builder->keyText('userid', 'Userid');

        $builder->keyStatus('result', 'result', array('NA', 'Won', 'Lost'));
        $builder->keyPrice('amount', 'Amount');
        $builder->keyPrice('winamount', 'winamount');
        $builder->keyText('addtime', 'addtime');
        $builder->setSearchPostUrl(U('Invest/dicerolls'));
        $builder->search('field', 'select', array('id' => 'id', 'userid' => 'userid'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function investlist($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $map = array();
        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }

        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
        if ($field && $name) {
            $map[$field] = $name;
        }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $data = M('InvestboxLog')->where($map)->order($order_set)->select();
        $count = M('InvestboxLog')->where($map)->count();
        $builder = new BuilderList();
        $builder->title('Investments: Status');
        $builder->titleList('InvestBoxs', U('Invest/Index'));
        $builder->keyId();
        $builder->keyText('boxid', 'Boxid');
        $builder->keyText('docid', 'Docid');
        $builder->keyText('amount', 'amount');
        $builder->keyText('begintime', 'Begin');
        $builder->keyText('endtime', 'End');
        $builder->keyText('withdrawn', 'Withdrawn');
        $builder->keyText('maturity', 'Maturity');
        $builder->keyText('credited', 'Credited');
        $builder->keyText('userid', 'Userid');

        $builder->setSearchPostUrl(U('Invest/investlist'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('status', 'select', array('All Status', 'Premature Withdrawn', 'Active', 'Reject', 'Completed'));
        $builder->search('field', 'select', array('id' => 'ID', 'userid' => 'UserID', 'docid' => 'Docid', 'boxid' => 'BoxID'));
        $builder->search('name', 'text', 'Enter text');
        $builder->button('add', 'Add', U('Invest/editInvesmentLog'));
        $builder->button('delete', 'Delete', U('Invest/deleteInvesmentLog'));
        $builder->keyDoAction('Invest/editInvesmentLog?id=###', 'Edit', 'Option');

        $builder->keyStatus('status', 'Status', array('Premature', 'Active', 'Reject', 'Completed'));
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit($id = NULL)
    {
        $plans = array(1, 7, 30, 60, 90, 120, 180, 365);

        if (!empty($_POST)) {

            if (!$_POST['id']) {

                $action['coin'] = array('name' => $_POST['actionxcoinname'], 'value' => $_POST['actionxcoinvalue']);
                $action['market'] = array('name' => $_POST['actionxmarketname'], 'buy' => $_POST['actionxmarketbuy'], 'sell' => $_POST['actionxmarketsell']);
                $array = array(
                    'title' => $_POST['title'],
                    'coinname' => $_POST['coinname'],
                    'percentage' => $_POST['percentage'],
                    'period' => json_encode($_POST['period']),//
                    'minvest' => $_POST['minvest'],
                    'maxvest' => $_POST['maxvest'],
                    'creatorid' => $_POST['creatorid'],
                    'status' => $_POST['status'],
                    'allow_withdrawal' => $_POST['allow_withdrawal'],
                    'action' => (string)json_encode($action),
                );

                $rs = M('Investbox')->add($array);

            } else {

                $action['coin'] = array('name' => $_POST['actionxcoinname'], 'value' => $_POST['actionxcoinvalue']);
                $action['market'] = array('name' => $_POST['actionxmarketname'], 'buy' => $_POST['actionxmarketbuy'], 'sell' => $_POST['actionxmarketsell']);
                $array = array(
                    'id' => $_POST['id'],
                    'title' => $_POST['title'],
                    'coinname' => $_POST['coinname'],
                    'percentage' => $_POST['percentage'],
                    'period' => json_encode($_POST['period']),//
                    'minvest' => $_POST['minvest'],
                    'maxvest' => $_POST['maxvest'],
                    'creatorid' => $_POST['creatorid'],
                    'status' => $_POST['status'],
                    'allow_withdrawal' => $_POST['allow_withdrawal'],
                    'action' => (string)json_encode($action),
                );


                $rs = M('Investbox')->save($array);
            }

            if ($rs) {
                S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
                $this->error('No changes were made !!');
            }
        } else {
            if ($id) {
                $this->data = M('Investbox')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }
            $period = json_decode($this->data['period']);


            foreach ($plans as $val) {

                $appc['period'][] = array('value' => $val, 'flag' => in_array($val, $period) ? 1 : 0);
            }
            $this->assign('appCon', $appc);
            if ($id) {
                $data = M('Investbox')->where(array('id' => $id))->find();

                $this->assign($data);
                $action = json_decode($data['action']);
                $actionx['coin']['name'] = $action->coin->name;
                $actionx['coin']['value'] = $action->coin->value;
                $actionx['market']['name'] = $action->market->name;
                $actionx['market']['buy'] = $action->market->buy;
                $actionx['market']['sell'] = $action->market->sell;

                $this->assign('actionx', $actionx);
            }
            $coin_list = D('Coin')->get_all_name_list();
            $status_array = array('0' => 'Submitted', '1' => 'Approved', '2' => 'Reject', '3' => 'Completed', '4' => 'Upcoming');

            $this->assign($coin_list);
            $this->assign($status_array);
            $this->display();
        }
    }


    public function deleteInvesmentLog($id = array())
    {

        $where['id'] = end($id);
        if (M('InvestboxLog')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('Could not delete!');
        }

    }

    public function editInvesmentLog($id = NULL)
    {

        if (!empty($_POST)) {

            $userid = $_POST['userid'];


            $check_if_user = M('User')->where(array('id' => $userid))->getField('id');
            if (!isset($check_if_user)) {
                $this->error('No such user found');
            }

            $boxid = $_POST['boxid'];
            $check_if_boxid = M('Investbox')->where(array('id' => $boxid))->getField('id');
            if (!isset($check_if_boxid)) {
                $this->error('No such Invest box found');
            }

            if (!isset($_POST['id'])) {
                $userid = $_POST['userid'];
                $docid = $_POST['boxid'] . 'IB' . $userid . tradeno();
                $array = array(
                    'boxid' => $_POST['boxid'],
                    'docid' => $docid,
                    'period' => $_POST['period'],//
                    'amount' => $_POST['amount'],
                    'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
                    'maturity' => $_POST['maturity'],
                    'userid' => $_POST['userid'],
                    'status' => $_POST['status'],
                );

                $rs = M('InvestboxLog')->add($array);

            } else {

                $array = array(
                    'id' => $_POST['id'],
                    'boxid' => $_POST['boxid'],
                    'period' => $_POST['period'],//
                    'amount' => $_POST['amount'],
                    'maturity' => $_POST['maturity'],
                    'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
                    'userid' => $_POST['userid'],
                    'status' => $_POST['status'],
                );

                $rs = M('InvestboxLog')->save($array);
            }

            if ($rs) {
                S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
                $this->error('No changes were made !!');
            }
        } else {
            if ($id) {
                $this->data = M('InvestboxLog')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }
            $boxid = $this->data['boxid'];
            $period = $this->data['period'];
            $begintime = $this->data['begintime'];
            $endtime = $this->data['endtime'];
            $amount = $this->data['amount'];
            $maturity = $this->data['maturity'];
            $userid = $this->data['userid'];
            $status = $this->data['status'];
            if ($boxid) {
                $this->assign('id', $id);
                $this->assign('boxid', $boxid);
                $this->assign('period', $period);
                $this->assign('begintime', $begintime);
                $this->assign('endtime', $endtime);
                $this->assign('amount', $amount);
                $this->assign('maturity', $maturity);
                $this->assign('userid', $userid);
                $this->assign('status', $status);
            }

            $status_array = array('0' => 'Premature', '1' => 'Active', '2' => 'Reject', '3' => 'Completed', '4' => 'Upcoming');

            $this->assign($status_array);
            $this->display();
        }
    }


}