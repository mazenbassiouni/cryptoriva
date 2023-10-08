<?php

namespace Home\Controller;

use Think\Page;

class MoneyController extends HomeController
{
    public function __construct()
    {
        parent::__construct();
     //   exit("This feature is currently disabled");
    }

    public function index()
    {
        if (IS_POST) {
            $id = I('post.id',0,'intval'); 
            $num = I('num/f');
            $paypassword =   I('paypassword/s');

            if (!check($id, 'd')) {
                $this->error(L('Number format error!'));
            }

            if (!check($num, 'd')) {
                $this->error(L('The number of financial wrong format!'));
            }

            if (!check($paypassword, 'password')) {
                $this->error(L('Fund Pwd format error!'));
            }

            $money_min = (C('money_min') ? C('money_min') : 0.01);
            $money_max = (C('money_max') ? C('money_max') : 10000000);
            $money_bei = (C('money_bei') ? C('money_bei') : 10);

            if ($num < $money_min) {
                $this->error('Conduct financial transactions Quantity exceed system Least limit '.$money_min);
            }

            if ($money_max < $num) {
                $this->error(L('The number of financial system exceeds the maximum limit!'));
            }

            if ($num % $money_bei != 0) {
             //   $this->error(L('Each time the number of financial management must be') . $money_bei . L('Integral multiples!'));
            }

            if (!userid()) {
                $this->error(L('PLEASE_LOGIN'));
            }

            $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

            if (md5($paypassword) != $user['paypassword']) {
                $this->error(L('Trading password is wrong!'));
            }

            $money = M('Money')->where(array('id' => $id))->find();

            if (!$money) {
                $this->error(L('The current financial mistake!'));
            }

            if (!$money['status']) {
                $this->error(L('The current financial management has been disabled!'));
            }

            if (($money['num'] - $money['deal']) < $num) {
                $this->error(L('The remaining amount is insufficient system!'));
            }

            $userCoin = $this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();

            if (!$userCoin || !isset($userCoin[$money['coinname']])) {
                $this->error('Currency current error!');
            }

            if ($userCoin[$money['coinname']] < $num) {
                $this->error('Insufficient funds available,Current account balance:' . $userCoin[$money['coinname']]);
            }

            $money_log_num = M('MoneyLog')->where(array('userid' => userid(), 'money_id' => $money['id']))->sum('num');

            if ($money['max'] < ($money_log_num + $num)) {
              //  $this->error(L('The maximum available current financial') . $money['max'] . ',You have purchased:' . $money_log_num);
            }

            $mo = M();
            $mo->startTrans();
            $rs = array();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($money['coinname'], $num);
            $rs[] = $mo->table('codono_money_log')->add(array('userid' => userid(), 'money_id' => $money['id'], 'name' => $money['name'], 'num' => $num, 'tian' => $money['tian'], 'fee' => $money['fee'], 'feecoin' => $money['feecoin'], 'addtime' => time(), 'status' => 0));
            $rs[] = $mo->table('codono_money')->where(array('id' => $id))->setInc('deal', $num);

            if ($money['num'] <= $money['deal']) {
                $rs[] = $mo->table('codono_money')->where(array('id' => $id))->setField('status', 0);
            }

            if (check_arr($rs)) {
                $mo->commit();
                
                $this->success(L('Buy success!'));
            } else {
                $mo->rollback();
                $this->error(APP_DEBUG ? implode('|', $rs) : 'Failed purchase!');
            }
        } else {
			
            if (!userid()) {
                redirect(U('Login/login'));
            }

            $this->assign('prompt_text', D('Text')->get_content('game_money'));
            $where['status'] = 1;
            $count = M('Money')->where($where)->count();
            $Page = new Page($count, 5);
            $show = $Page->show();
            $list = M('Money')->where($where)->order('sort desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
			foreach ($list as $k => $v) {
                $list[$k]['fee'] = Num($v['fee']);
                $list[$k]['addtime'] = addtime($v['addtime']);
                $list[$k]['bili'] = round($v['deal'] / $v['num'], 2) * 100;
                $list[$k]['times'] = M('MoneyLog')->where(array('money_id' => $v['id']))->count();
                $list[$k]['shen'] = round($v['num'] - $v['deal'], 2);
            }

            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display();
        }
    }

    public function queue()
    {
        $br = (IS_CLI ? "\n" : '<br>');
        echo IS_CLI ? '' : '<pre>';
        echo 'start money queue:' . $br;
        $MoneyList = M('Money')->where(array('status' => 1))->select();
        debug($MoneyList, 'MoneyList');

        foreach ($MoneyList as $money) {
            debug($money, 'money');

            if ($money['endtime'] < $money['lasttime']) {
                echo 'end ok ' . $br;
                $MoneyLogList = D('MoneyLog')->where(array('money_id' => $money['id'], 'status' => 1))->select();

                if ($MoneyLogList) {
                    $mo = M();

                    foreach ($MoneyLogList as $user_money_list) {
                        if (!$user_money_list['status']) {
                            continue;
                        }

                        $mo->startTrans();
                        $rs = array();
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user_money_list['userid']))->setInc($money['coinname'], $user_money_list['num']);
                        $rs[] = $mo->table('codono_money_log')->save(array('id' => $user_money_list['id'], 'status' => 0));
                        $rs[] = $mo->table('codono_money_dlog')->add(array('userid' => $user_money_list['userid'], 'money_id' => $money['id'], 'type' => 1, 'num' => $user_money_list['num'], 'addtime' => time(), 'content' => 'Money End,Return principal:' . $user_money_list['num'] . 'More'));

                        if (check_arr($rs)) {
                            $mo->commit();
                            
                            echo 'commit ok ' . $br;
                        } else {
                            $mo->rollback();
                            echo 'rollback ' . $br;
                        }
                    }
                } else {
                    D('Money')->save(array('id' => $money['id'], 'status' => 0));
                    D('MoneyLog')->save(array('money_id' => $money['id'], 'status' => 0));
                    continue;
                }
            }

            echo (($money['lasttime'] + $money['step']) - time()) . ' s' . $br;
            debug(array('lasttime' => $money['lasttime'], 'step' => $money['step'], 'time()' => time()), 'check time');

            if (!$money['lasttime'] || (($money['lasttime'] + $money['step']) < time())) {
                echo 'start ' . $money['name'] . '#:' . $br;
                $mo = M();
                debug('A');
                $MoneyLogList = M('MoneyLog')->where(array('money_id' => $money['id'], 'status' => 1))->select();
                debug('B');
                debug($MoneyLogList, 'MoneyLogList');

                foreach ($MoneyLogList as $MoneyLog) {
                    debug('C');
                    debug(array($MoneyLog, $money), 'chktime');

                    if ($MoneyLog['chktime'] == $money['lasttime']) {
                        continue;
                    }

					$mo->startTrans();
                    $rs = array();
                    $fee = round(($money['fee'] * $MoneyLog['num']) / 100, 8);
                    echo 'update ' . $MoneyLog['userid'] . ' coin ' . $br;
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $MoneyLog['userid']))->setInc($money['feecoin'], $fee);
                    echo 'update ' . $MoneyLog['userid'] . ' log ' . $br;
                    $MoneyLog['allfee'] = round($MoneyLog['allfee'] + $fee, 8);
                    $MoneyLog['times'] = $MoneyLog['times'] + 1;
                    $MoneyLog['chktime'] = $money['lasttime'];
                    $rs[] = $mo->table('codono_money_log')->save($MoneyLog);
                    echo 'add ' . $MoneyLog['userid'] . ' dlog ' . $br;
                    $rs[] = $mo->table('codono_money_dlog')->add(array('userid' => $MoneyLog['userid'], 'money_id' => $money['id'], 'type' => 1, 'num' => $fee, 'addtime' => time(), 'content' => 'principal:' . $money['coinname'] . ' :' . $MoneyLog['num'] . 'More,Obtaining financial interest' . $money['feecoin'] . ' ' . $fee . 'More'));

                    if (check_arr($rs)) {
                        $mo->commit();
                        
                        echo 'commit ok ' . $br;
                    } else {
                        $mo->rollback();
                        echo 'rollback ' . $br;
                    }
                }

                if (D('Money')->where(array('id' => $money['id']))->setField('lasttime', time())) {
                    echo 'update money last time ok' . $br;
                } else {
                    echo 'update money last time fail!!!!!!!!!!!!!!!!!!!!!! ' . $br;
                }
            } else {
                echo L('Time yet to come');
            }
        }
    }

    public function log()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_money_log'));
        $where['userid'] = userid();
        $count = M('MoneyLog')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('MoneyLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['fee'] = Num($v['fee']);
            $list[$k]['addtime'] = addtime($v['addtime']);
            $list[$k]['endtime'] = addtime($v['endtime']);
            $list[$k]['leiji'] = Num($v['leiji']);
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function fee()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $id = I('get.id',0,'intval');

        if (!check($id, 'd')) {
            $this->error('Parameter error!');
        }

        $where['moneylogid'] = $id;
        $where['userid'] = userid();
        $count = M('MoneyFee')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('MoneyFee')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function beforeGet($id)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $id = intval($id);
        $MoneyLog = M('MoneyLog')->where(array('userid' => userid(), 'id' => $id, 'status' => 1))->find();

        if (!$MoneyLog) {
            $this->error(L('Parameter error'));
        }

        $Money = M('Money')->where(array('id' => $MoneyLog['money_id']))->find();

        if (!$Money) {
            $this->error(L('Parameter error'));
        }

        $num = $MoneyLog['num'];
        $fee = ($Money['outfee'] ? round(($MoneyLog['num'] * $Money['outfee']) / 100, 8) : 0);
        $mo = M();
        $mo->startTrans();
        $rs = array();

        if ($Money['coinname'] != $Money['feecoin']) {
            $user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();

            if (!isset($user_coin[$Money['feecoin']])) {
                $this->error('Currency interest does not exist,Contact your administrator');
            }

            if ($user_coin[$Money['feecoin']] < $fee) {
                $this->error(L('Your') . $Money['feecoin'] . 'Not enough cash withdrawal fee(' . $fee . ')');
            }

            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($Money['feecoin'], $fee);
            debug($mo->table('codono_user_coin')->getLastSql(), 'codono_user_coin_sql0');
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setInc($Money['coinname'], $num);
            debug($mo->table('codono_user_coin')->getLastSql(), 'codono_user_coin_sql1');
        } else {
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setInc($Money['coinname'], round($num - $fee, 8));
            debug($mo->table('codono_user_coin')->getLastSql(), 'codono_user_coin_sql2');
        }

        $rs[] = $mo->table('codono_money_log')->where(array('id' => $MoneyLog['id']))->setField('status', 0);
        debug($mo->table('codono_money_log')->getLastSql(), 'codono_money_log_sql');
        $rs[] = $mo->table('codono_money_dlog')->add(array('userid' => userid(), 'money_id' => $Money['id'], 'type' => 2, 'num' => $fee, 'addtime' => time(), 'content' => L('Extraction advance') . $Money['title'] . ' Money principal' . $Money['coinname'] . ' ' . $MoneyLog['num'] . 'More,Earnings before interest' . $Money['feecoin'] . ': ' . $fee . 'More'));

        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(APP_DEBUG ? implode('|', $rs) : 'operation failed!');
        }
    }

    private function danweitostr($unit)
    {
        switch($unit){
            case 'y':
                return 'year';

            case 'm':
                return 'month';

            case 'd':
                return 'day';

            case 'h':
                return L('hour');

            default:

            case 'i':
                return L('minute');
        }
    }

    public function uninstall()
    {
    }
}

