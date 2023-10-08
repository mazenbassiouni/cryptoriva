<?php

namespace Home\Controller;

use Think\Page;

class PoolController extends HomeController
{
    const ALLLOW_DEPOSIT_ENTRY = 1; // allow to show deposit entry in deposit table[codono_myzr] of coin with hash

    public function __construct()
    {
		if(POOL_ALLOWED==0){
		die('Unauthorized!');
		}
        parent::__construct();

    }

    public function index()
    {
        $count = M('Pool')->where(array('status' => 1))->count();
        switch ($count) {
            case 1:
                $class = "col-md-12";
                break;
            case 2:
                $class = "col-md-6";
                break;
            case 3:
                $class = "col-md-4";
                break;
            case 4:
                $class = "col-md-3";
                break;

            default:
                $class = "col-md-3";
        }
        $list = M('Pool')->where(array('status' => 1))->select();
        $pools=$populars=array();
        foreach ($list as $pool) {
            $pool['ico'] = $pool['ico'] ?: 'default.png';
            if ($pool['is_popular'] == 1) {
                $populars[] = $pool;
            }
            $pools[] = $pool;
        }
        $user_balances = $this->usercoins;
        $this->assign('class', $class);
        $this->assign('user_balances', $user_balances);
        $this->assign('list', $pools);
        $this->assign('populars', $populars);
        $this->display();

    }

    /*
    *Renting Process
    *num:amount
    *id:poolid
    */
    public function rentMachine()
    {

        if (!userid()) {
            $this->error(L('Please login'));
        }
        $input = I('post.');

        if (!check($input['num'], 'd')) {
            $this->error(L('Quantity wrong format!'));
        }

        if ($input['num'] < 1) {
            $this->error(L('Quantity wrong!'));
        }
        if (!check($input['num'], 'd')) {
            $this->error(L('Quantity wrong!'));
        }

        if (!check($input['id'], 'd')) {
            $this->error(L('Mining machine type format error!'));
        }
        $last_stock = S('pool_avail_' . $input['id']);

        $user = $this->userinfo;

        if (!$user['id']) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $uid = $user['id'];
        $pool = M('Pool')->where(array('id' => $input['id']))->find();

        $coinname = strtolower($pool['coinname']);
        $UserCoin = $this->usercoins;
        $user_coin_balance = $UserCoin[$coinname];
        if (!$pool) {
            $this->error(L('No Mining Machine Found!'));
        }

        if ($pool['status'] != 1) {
            $this->error(L('Mining Machine is not currently active!'));
        }

        $total_required = format_num($pool['price'] * $input['num']);

        if ($user_coin_balance < $total_required) {
            $this->error(L('Not Enough balance available'));
        }

        $user_boughts = M('PoolLog')->where(array('userid' => $uid, 'name' => $pool['name']))->sum('num');

        if ($pool['stocks'] < 1) {
            $this->error(L('We currently do not have Enough Mining Machines!'));
        }

        if ($pool['user_limit'] && ($pool['user_limit'] < ($user_boughts + $input['num']))) {
            $this->error("You have already bought $user_boughts machines of same !");
        }
        $user_coin_bal = $this->usercoins;
        $num_a = $user_coin_bal[$coinname];
        $num_b = $user_coin_bal[$coinname . 'd'];
        $num = bcadd($num_a, $num_b, 8);
        $mum_a = bcadd($num_a, $total_required, 8);
        $mum_b = $num_b;
        $mum = bcadd($mum_a, $mum_b, 8);
        $hash = hash('sha1', md5(SHORT_NAME . $uid . time() . $pool['id'] . $coinname . $total_required . rand(10000, 99999)));

        $mo = M();
        $mo->startTrans();
        $rs = array();
        $rs[] = $mo->table('codono_pool')->where(array('id' => $pool['id']))->setDec('stocks', $input['num']);

        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['id']))->setDec($coinname, $total_required);
        $rs[] = $poollogid = $mo->table('codono_pool_log')->add(array('poolid' => $pool['id'], 'userid' => $user['id'], 'coinname' => $pool['coinname'], 'getcoin' => $pool['getcoin'], 'name' => $pool['name'], 'ico' => $pool['ico'], 'price' => $pool['price'], 'num' => $input['num'], 'days' => $pool['days'], 'daily_profit' => $pool['daily_profit'], 'power' => $pool['power'], 'endtime' => time(), 'addtime' => time(), 'status' => 0, 'collected' => 0));


        // Finance Entry
        $finance_array = array('userid' => $uid, 'coinname' => $coinname, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num, 'fee' => 0, 'type' => 2, 'name' => 'Mining Rent', 'nameid' => $poollogid, 'remark' => 'pool_rent', 'move' => $hash, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b);
        $rs[] = $mo->table('codono_finance')->add($finance_array);

        if (check_arr($rs)) {
            $mo->commit();
            
            $last_stock = $last_stock - 1;
            S('pool_avail_' . $pool['id'], $last_stock);
            $this->success(L('Machine Rented!'));
        } else {
            $mo->rollback();
            $this->error(L('Could not rent the machine!'));
        }
    }

    /*
    *Machines that I have rented

    */
    public function myMachines()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $uid = userid();
        //     $where['status'] = array('egt', 0);
        $where['userid'] = $uid;
        import('ORG.Util.Page');
        $Model = M('PoolLog');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myRewards()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $uid = userid();

        $where['userid'] = $uid;
        import('ORG.Util.Page');
        $Model = M('PoolRewards');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    /**
     *
     */
    public function startMachine()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (IS_POST) {
            $input = I('post.');

            if (!check($input['id'], 'd')) {
                $this->error(L('Please select mining machine to work!'));
            }

            $poolLog = M('PoolLog')->where(array('id' => $input['id']))->find();

            if (!$poolLog) {
                $this->error(L('INCORRECT_REQ'));
            }

            if ($poolLog['status'] == 1) {
                $this->error(L('Access error!'));
            }

            $uid = userid();

            if (!$uid) {
                $this->error(L('PLEASE_LOGIN'));
            }

            if ($poolLog['userid'] != $uid) {
                $this->error(L('Unauthorized access'));
            }
            //@todo mum is not being used
            $mum = bcmul($poolLog['price'], $poolLog['num'], 8);
            $mo = M();
            $mo->startTrans();
            $rs = array();
            $rs[] = $mo->table('codono_pool_log')->where(array('id' => $poolLog['id']))->save(array('endtime' => time(), 'status' => 1));

            if (check_arr($rs)) {
                $mo->commit();
                
                $this->success(L('Mining machine has started to work!'));
            } else {
                $mo->rollback();
                if (M_DEBUG == 1) {
                    clog("Mining", implode('|', $rs));
                }
                $this->error(L('Mining machine failed to work!'));
            }
        } else {
            $this->error(L('Please select mining machine to work!'));
        }

    }

    public function claimReward()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $uid = userid();
        if (IS_POST) {
            $input = I('post.');

            if (!check($input['id'], 'd')) {
                $this->error(L('Please choose to your mining machine correctly!'));
            }

            $poolLog = M('PoolLog')->where(array('id' => $input['id']))->find();

            if (!$poolLog) {
                $this->error(L('INCORRECT_REQ'));
            }

            if ($poolLog['days'] <= $poolLog['collected']) {
                $this->releaseMachine($poolLog['id']);
                $this->success(L('You have successfully used this mining machine!'));
            }
            /*
            //Logic of Checking when was last claimed
            $last_Reward= M('PoolRewards')->where(array('userid'=>$uid,'poolid'=>$poolLog['poolid']))->max('addtime');
            //Reward was previously used by miner check if  got anything in past 24 hours?
            if(isset($last_Reward) && $last_Reward>1 ){
                $remaining = bcsub(time() , $last_Reward);
                if($remaining<86400){
                    $diff=bcsub(86400,$remaining);
                    $time_left=gmdate("H:i:s", $diff);
                    $this->error("Please wait ".$time_left." before claiming rewards");
                }
            }
            */
            //Logic of when was it bought ,how many days passed and how many times it got collected

            $start_time = $poolLog['addtime'];
            $now = time();
            $days = $poolLog['days'];
            $next_collection_day = $poolLog['collected'];
            $next_collection_stamp = bcmul($next_collection_day, 86400);
            $next_collection_day_stamp = bcadd($start_time, $next_collection_stamp);
            if ($now < $next_collection_day_stamp) {

                $diff = bcsub($next_collection_day_stamp, $now);
                $time_left = gmdate("m-d-Y H:i:s", $next_collection_day_stamp);
                $this->error("Please wait " . $time_left . " before claiming rewards");
            }
            //$this->error(__LINE__);
            //@todo calculate tm value
            /*
                        $tm = $poolLog['endtime'] + (60 * 60 * C('pool_jian'));

                        if (time() < $tm) {
                        }
            */


            if (!$uid) {
                $this->error(L('PLEASE_LOGIN'));
            }

            if ($poolLog['userid'] != $uid) {
                $this->error(L('Unauthorized access'));
            }
            $user_coin_bal = $this->usercoins;
            $mo = M();
            $mo->startTrans();

            $getcoin = $poolLog['getcoin'];
            $daily_profit_num = bcmul($poolLog['daily_profit'], $poolLog['num'], 8);


            $num_a = $user_coin_bal[$getcoin];
            $num_b = $user_coin_bal[$getcoin . 'd'];
            $num = bcadd($num_a, $num_b, 8);
            $mum_a = bcadd($num_a, $daily_profit_num, 8);
            $mum_b = $num_b;
            $mum = bcadd($mum_a, $mum_b, 8);


            $coin_info = C('coin')[$getcoin];
            $coin_type = $coin_info['type'];
            $from_name = "mining-" . $getcoin . '-' . $poolLog['poolid'];
            $rs = array();

            $hash = hash('sha1', md5(SHORT_NAME . $uid . time() . $poolLog['id'] . $getcoin . $daily_profit_num . rand(10000, 99999)));
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($getcoin, $daily_profit_num);
            $rs[] = $mo->table('codono_pool_log')->where(array('id' => $poolLog['id']))->save(array('collected' => $poolLog['collected'] + 1, 'endtime' => time()));

            //deposit entry
            if (self::ALLLOW_DEPOSIT_ENTRY == 1) {
                $rs[] = M('myzr')->add(array('userid' => $uid, 'type' => $coin_type, 'username' => $from_name, 'coinname' => $getcoin, 'fee' => 0, 'txid' => "mine_" . $hash, 'num' => $daily_profit_num, "mum" => $daily_profit_num, 'addtime' => time(), 'status' => 1));
            }
            // Finance Entry
            $finance_array = array('userid' => $uid, 'coinname' => $getcoin, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num, 'fee' => 0, 'type' => 1, 'name' => 'Mining Income', 'nameid' => $poolLog['id'], 'remark' => 'pool_reward', 'move' => $hash, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b);
            $rs[] = $mo->table('codono_finance')->add($finance_array);

            //PoolRewards entry
            $reward_array = array('poolid' => $poolLog['poolid'], 'userid' => $poolLog['userid'], 'logid' => $poolLog['id'], 'coinname' => $getcoin, 'amount' => $daily_profit_num, 'addtime' => time(), 'hash' => $hash);

            $rs[] = $mo->table('codono_pool_rewards')->add($reward_array);

            if ($poolLog['days'] <= $poolLog['collected'] + 1) {
                //Pool completes now change status =2
                $rs[] = $mo->table('codono_pool_log')->where(array('id' => $poolLog['id']))->save(array('status' => 2));

                // Release the resorce and Increase mine machine quantity by 1
                $rs[] = $mo->table('codono_pool')->where(array('id' => $poolLog['poolid']))->setInc('stocks', 1);

            }

            if (check_arr($rs)) {
                $mo->commit();
                
                $this->success(L('Mining rewards succesfully sent to your account! '));
            } else {
                $mo->rollback();
                $this->error(L('There are difficulties gathering mining rewards!'));
            }
        }
    }

    private function releaseMachine($poolLog_id): bool
    {
        if (!check($poolLog_id, 'd')) {
            return false;
        }
        $poolLog = M('PoolLog')->where(array('id' => $poolLog_id))->find();
        if (!$poolLog['id']) {
            return false;
        }
        $rs = array();
        $mo = M();
        $mo->startTrans();
        //Pool completes now change status =2
        $rs[] = $mo->table('codono_pool_log')->where(array('id' => $poolLog['id']))->save(array('status' => 2));

        // Release the resorce and Increase mine machine quantity by 1
        $rs[] = $mo->table('codono_pool')->where(array('id' => $poolLog['poolid']))->setInc('stocks', 1);

        if (check_arr($rs)) {
            $mo->commit();
            
            return true;
        } else {
            $mo->rollback();
            return false;
        }
    }


}