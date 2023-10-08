<?php

namespace Home\Controller;

use Think\Page;

class IssueController extends HomeController
{
    public function __construct()
    {
        if (ICO_ALLOWED == 0) {
            die('Unauthorized!');
        }
        parent::__construct();
    }

    public function index()
    {
        if (C('issue_login')) {
            if (!userid()) {
                redirect(U('Login/login'));
            }
        }


        $where['status'] = array('neq', 0);
        $Model = M('Issue');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        //$list = $Model->fetchSql()->where($where)->order('addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $list = $Model->where($where)->order('tuijian asc,paixu desc,addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $the_recommended = $Model->where(array("tuijian" => 1))->order("addtime desc")->limit(1)->find();


        if ($the_recommended) {

            $the_recommended['coinname'] = C('coin')[$the_recommended['coinname']]['title'];
            $the_recommended['buycoin'] = C('coin')[$the_recommended['buycoin']]['title'];
            $the_recommended['bili'] = bcmul(bcdiv($the_recommended['deal'], $the_recommended['num'], 8), 100, 8);
            $the_recommended['content'] = mb_substr(clear_html($the_recommended['content']), 0, 350, 'utf-8');


            $end_ms = strtotime($the_recommended['time']) + $the_recommended['tian'] * 3600 * 24;
            $begin_ms = strtotime($the_recommended['time']);

            $the_recommended['beginTime'] = date("Y-m-d H:i:s", $begin_ms);
            $the_recommended['endTime'] = date("Y-m-d H:i:s", $end_ms);

            $the_recommended['zhuangtai'] = "Running";

            if ($begin_ms > time()) {
                $the_recommended['zhuangtai'] = "Upcoming";//Not started
            }


            if ($the_recommended['num'] <= $the_recommended['deal']) {
                $the_recommended['zhuangtai'] = "Ended";//Ended
            }


            if ($end_ms < time()) {
                $the_recommended['zhuangtai'] = "Ended";//Ended
            }

            $the_recommended['rengou'] = "";
            if ($the_recommended['zhuangtai'] == "Running") {
                $the_recommended['rengou'] = "<a href='/Issue/buy/id/" . $the_recommended['id'] . "'>Get Now</a>";
            }
        }


        if ($list) {
            $this->assign('prompt_text', D('Text')->get_content('game_issue'));
        } else {
            $this->assign('prompt_text', '');
        }


        $list_jinxing = array();//Running
        $list_yure = array();//Upcoming
        $list_jieshu = array(); //Ended


        foreach ($list as $k => $v) {
            if(!isset(C('coin')[$v['coinname']])){
				continue;
			}


            $list[$k]['bili'] = bcmul(bcdiv($v['deal'], $v['num'], 8), 100, 8);
            $list[$k]['endtime'] = date("Y-m-d H:i:s", strtotime($v['time']) + $v['tian'] * 3600 * 24);

            $list[$k]['coinname'] = C('coin')[$v['coinname']]['title'];
            $list[$k]['buycoin'] = C('coin')[$v['buycoin']]['title'];
            $list[$k]['bili'] = bcmul(bcdiv($v['deal'], $v['num'], 8), 100, 8);
            $list[$k]['content'] = mb_substr(clear_html($v['content']), 0, 350, 'utf-8');


            $end_ms = strtotime($v['time']) + $v['tian'] * 3600 * 24;
            $begin_ms = strtotime($v['time']);


            $list[$k]['beginTime'] = date("Y-m-d H:i:s", $begin_ms);
            $list[$k]['endTime'] = date("Y-m-d H:i:s", $end_ms);

            $list[$k]['zhuangtai'] = L('RUNNING');

            if ($begin_ms > time()) {
                $list[$k]['zhuangtai'] = L('UPCOMING');//upcoming
            }


            if ($list[$k]['num'] <= $list[$k]['deal']) {
                $list[$k]['zhuangtai'] = L('ENDED');//ended
            }

            if ($end_ms < time()) {
                $list[$k]['zhuangtai'] = L('ENDED');//ended
            }

            switch ($list[$k]['zhuangtai']) {
                case L('UPCOMING'):
                    $list_yure[] = $list[$k];
                    break;
                case L('RUNNING'):
                    $list_jinxing[] = $list[$k];
                    break;
                case L('ENDED'):
                    $list_jieshu[] = $list[$k];
                    break;
            }


        }

        $this->assign('tuijian', $the_recommended);
        $this->assign('list_yure', $list_yure);
        $this->assign('list_jinxing', $list_jinxing);
        $this->assign('list_jieshu', $list_jieshu);
        $this->assign('page', $show);
        $this->display();
    }

    public function buy()
    {
        $id=I('get.id',0,'int');
        $this->assign('prompt_text', D('Text')->get_content('game_issue_buy'));

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $Issue = M('Issue')->where(array('id' => $id))->find();
        if(!isset($Issue) || !is_array($Issue) ){
            redirect(U('Issue/index'));
        }
        $Issue['bili'] = bcmul(bcdiv($Issue['deal'], $Issue['num'], 8), 100, 8);

        $end_ms = strtotime($Issue['time']) + $Issue['tian'] * 3600 * 24;
        $begin_ms = strtotime($Issue['time']);

        $Issue['status'] = 1;

        if ($begin_ms > time()) {
            $Issue['status'] = 2;//notStart
        }


        if ($Issue['num'] == $Issue['deal']) {
            $Issue['status'] = 0;//AlreadyEnd
        }


        if ($end_ms < time()) {
            $Issue['status'] = 0;//AlreadyEnd
        }


        $Issue['endtime'] = date("Y-m-d H:i:s", strtotime($Issue['time']) + $Issue['tian'] * 3600 * 24);


        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        $this->assign('user_coin', $user_coin);

        if (!$Issue) {
            $this->error(L('ICO wrong!'));
        }
        $whereTimeLine = array('status' => '1', 'issue_id' => $id);
        $TimeLinelist = M('IssueTimeline')->where($whereTimeLine)->order('id desc')->select();
        $Issue['img'] = M('Coin')->where(array('name' => $Issue['coinname']))->getField('img');


        //$convert_coin_price_in_usd=$this->getCoinPrice($Issue['convertcurrency']);
        $pricecoin = $Issue['buycoin'];
        $price = $Issue['price'];
        //convertcurrency= in which user pays
        foreach (json_decode($Issue['show_coin'], true) as $showco) {
            if ($showco) {
                if (!empty(C('coin')[$showco])) {
                    $the_price = $this->getPricingTool($showco, $pricecoin, $price);
                    $show_coins[] = array('id' => C('coin')[$showco]['id'], 'name' => C('coin')[$showco]['name'], 'price' => $the_price, 'balance' => $user_coin[$showco],'img'=>C('coin')[$showco]['img']);
                }
            }
        }


        $payincoin = $Issue['convertcurrency'];
        $pricecoin = $Issue['buycoin'];
        $price = $Issue['price'];
        $convert_price = $this->getPricingTool($payincoin, $pricecoin, $price);
        if (!kyced()) {
         $kyced=0;
        }else{
            $kyced=1;
        }
        $this->assign('kyced', $kyced);
        $this->assign('convert_price', $convert_price);
        $this->assign('issue', $Issue);
        $this->assign('TimeLinelist', $TimeLinelist);
        $this->assign('show_coins', $show_coins);
        $this->display();
    }

    /*
    say payincoin is eth
    price is 100 usd,

    so calculate number of eth payable
    return , "payable" or false
    */
    private function getPricingTool($payincoin, $pricecoin, $price)
    {
        if ($payincoin == null) {
            return false;
        }
        $pricecoin_conversion_to_usd = 1;
        if ($payincoin == $pricecoin) {
            return bcmul($pricecoin_conversion_to_usd, $price, 8);
        }
        if ($pricecoin != 'usd') {
            //@todo find pricecoin in usd
            $pricecoin_conversion_to_usd = $this->getCoinPrice($pricecoin);
            if ($pricecoin_conversion_to_usd == null || $pricecoin_conversion_to_usd <= 0) {
                return false;
            }
        }
        $payincoin_to_usd = $this->getCoinPrice($payincoin);
        if ($payincoin_to_usd == null || $payincoin_to_usd <= 0) {
            return false;
        }
        $conversion = bcdiv($pricecoin_conversion_to_usd, $payincoin_to_usd, 8);
        return bcmul($conversion, $price, 8);
    }

    private function dbQuery($symbol, $column)
    {
        $src = strtoupper($symbol);
        //Lookup requested column in database
        $row = M('Coinmarketcap')->where(array('symbol' => $src))->field($column)->find();

        if ($row[$column]) {
            return $row[$column];
        }
        return false;
    }

    /**
     * Function getCoinPrice returns price of the coin or 'false' on failure
     * @var $symbol STRING (btc, eth, nano, etc...)
     */
    private function getCoinPrice(string $symbol)
    {
        return $this->dbQuery($symbol, 'price_usd');
    }

    /**
     * Function getCoinName returns full name of the coin or 'false' on failure
     * @var $symbol STRING (btc, eth, nano, etc...)
     */
    private function getCoinName(string $symbol)
    {
        return $this->dbQuery($symbol, 'name');
    }

    public function log($ls = 15)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_issue_log'));
        //$where['status'] = array('egt', 0);
        $where['userid'] = userid();
        $IssueLog = M('IssueLog');
        $count = $IssueLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $IssueLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['shen'] = round((($v['ci'] - $v['unlock']) * $v['num']) / $v['ci'], 6);
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function alllogs($ls = 15)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_issue_log'));
        $where['status'] = array('egt', 0);
        $IssueLog = M('IssueLog');
        $count = $IssueLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $IssueLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['shen'] = round((($v['ci'] - $v['unlock']) * $v['num']) / $v['ci'], 6);
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function upbuy($id, $num, $paypassword, $selected_coin)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (!check($num, 'd')) {
            $this->error(L('The number of ICO format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }
        if (!check($selected_coin, 'n')) {
            $this->error(L('Currency format error!'));
        }

        $User = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (!$User['paypassword']) {
            $this->error(L('Illegal Fund Pwd!'));
        }

        if (md5($paypassword) != $User['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $Issue = M('Issue')->where(array('id' => $id))->find();

        if (!$Issue) {
            $this->error(L('ICO wrong!'));
        }
        $show_coins = json_decode($Issue['show_coin'], true);

        if (!in_array($selected_coin, $show_coins)) {
            $this->error(L('Currency format error!'));
        }
        if (time() < strtotime($Issue['time'])) {
            $this->error(L('The current ICO has not yet started!'));
        }

        if (!$Issue['status']) {
            $this->error(L('The current ICO is over!'));
        }


        $end_ms = strtotime($Issue['time']) + $Issue['tian'] * 3600 * 24;
        /* 		$begin_ms = strtotime($Issue['time']);
                if($begin_ms<time()){
                    $Issue['status'] = 2;//notStart
                } */

        if ($end_ms < time()) {
            $this->error(L('The current ICO is over!'));
        }


        $issue_min = ($Issue['min'] ?: 9.9999999999999995E-7);
        $issue_max = ($Issue['max'] ?: 100000000);

        if ($num < $issue_min) {
            $this->error(L('MINIMUM_ICO_REQUIRED') . $issue_min);
        }

        if ($issue_max < $num) {
            $this->error(L('MAX_ICO_ALLOWED') . $issue_max);
        }

        if (($Issue['num'] - $Issue['deal']) < $num) {
            $this->error(L('ICO amount exceeds the current remaining amount!'));
        }

        $payincoin = $selected_coin;
        $pricecoin = $Issue['buycoin'];
        $price = $Issue['price'];
        $convert_price = $this->getPricingTool($payincoin, $pricecoin, $price);
        //$this->error(json_encode([$payincoin,$pricecoin,$price,$convert_price]));
        if ($convert_price <= 0 || $convert_price == null) {
            $this->error(L('Total payable is quite less, Please check amount!'));
        }
        $mum = bcmul($convert_price, $num, 8);
        if (!$mum) {
            $this->error(L('Total payable is quite less, Please check amount!'));
        }

        $buycoin = M('UserCoin')->where(array('userid' => userid()))->getField($payincoin);

        if ($buycoin < $mum) {
            $this->error(L('INSUFFICIENT') . C('coin')[$payincoin]['title']);
        }

        $issueLog = M('IssueLog')->where(array('userid' => userid(), 'coinname' => $Issue['coinname']))->sum('num');

        if ($Issue['limit'] < ($issueLog + $num)) {
            $this->error(L('The total ICO amount exceeds the maximum limit') . $Issue['limit']);
        }
        $jd_num = $num;

        /*
        if ($Issue['ci']) {
            $jd_num = round($num / $Issue['ci'], 6);
        }
        */
        if (!$jd_num) {
            $this->error(L('ICO thaw the number of errors'));
        }
        $conv_coin = $payincoin;
        $site_cut = bcdiv(bcmul($Issue['commission'], $mum, 8), 100, 8);
        $token_owners_keep = bcsub($mum, $site_cut, 8);

        $mo = M();

        $mo->startTrans();
        $rs = array();

        $finance = $mo->table('codono_finance')->where(array('userid' => userid()))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($payincoin, $mum);
        $rs[] = $finance_nameid = $mo->table('codono_issue_log')->add(array('userid' => userid(), 'coinname' => $Issue['coinname'], 'buycoin' => $Issue['buycoin'], 'convertcurrency' => $payincoin, 'name' => $Issue['name'], 'price' => $Issue['price'], 'convert_price' => $convert_price, 'num' => $num, 'mum' => $mum, 'ci' => $Issue['ci'], 'jian' => $Issue['jian'], 'unlock' => 1, 'addtime' => time(), 'endtime' => time(), 'status' => $Issue['ci'] == 1 ? 1 : 0));
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $finance_hash = md5(userid() . $finance_num_user_coin[$conv_coin] . $finance_num_user_coin[$conv_coin . 'd'] . $mum . $finance_mum_user_coin[$conv_coin] . $finance_mum_user_coin[$conv_coin . 'd'] . CODONOLIC . 'auth.codono.com');

        $xnum = $finance_num_user_coin[$conv_coin] + $finance_num_user_coin[$conv_coin . 'd'];
        $rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $conv_coin, 'num_a' => $finance_num_user_coin[$conv_coin], 'num_b' => $finance_num_user_coin[$conv_coin . 'd'], 'num' => $xnum, 'fee' => $mum, 'type' => 2, 'name' => 'issue', 'nameid' => $finance_nameid, 'remark' => 'Token Purchase' . $Issue['coinname'], 'mum_a' => $finance_mum_user_coin[$conv_coin], 'mum_b' => $finance_mum_user_coin[$conv_coin . 'd'], 'mum' => $finance_mum_user_coin[$conv_coin] + $finance_mum_user_coin[$conv_coin . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance['mum'] != $finance_num_user_coin[$conv_coin] + $finance_num_user_coin[$conv_coin . 'd'] ? 0 : 1));
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setInc($Issue['coinname'], $jd_num);
        $rs[] = $mo->table('codono_issue')->where(array('id' => $id))->setInc('deal', $num);
        //Increase Token Owners Balance token_owners_keep
        if ($Issue['ownerid'] > 0) {
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Issue['ownerid']))->setInc($payincoin, $token_owners_keep);
            $rs[] = $mo->table('codono_invit')->add(array('coin'=>$payincoin,'userid' => $Issue['ownerid'], 'invit' => userid(), 'name' => $conv_coin, 'type' => 'Issue of ICO Token:' . $num . $Issue['coinname'], 'num' => $token_owners_keep, 'mum' => $mum, 'fee' => $site_cut, 'addtime' => time(), 'status' => 1));
        }

        if ($Issue['num'] <= $Issue['deal']) {
            $rs[] = $mo->table('codono_issue')->where(array('id' => $id))->setField('status', 0);
        }

        if ($User['invit_1'] && $Issue['invit_1']) {
            $invit_num_1 = bcmul(bcdiv($mum, 100, 8), $Issue['invit_1'], 6);

            if ($invit_num_1) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $User['invit_1']))->setInc($Issue['invit_coin'], $invit_num_1);
                $rs[] = $mo->table('codono_invit')->add(array('coin'=>$Issue['invit_coin'],'userid' => $User['invit_1'], 'invit' => userid(), 'name' => $Issue['name'], 'type' => L('1st Tier ICO Bonus'), 'num' => $num, 'mum' => $mum, 'fee' => $invit_num_1, 'addtime' => time(), 'status' => 1));
            }
        }

        if ($User['invit_2'] && $Issue['invit_2']) {
            $invit_num_2 = bcmul(bcdiv($mum, 100, 8), $Issue['invit_2'], 6);

            if ($invit_num_2) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $User['invit_2']))->setInc($Issue['invit_coin'], $invit_num_2);
                $rs[] = $mo->table('codono_invit')->add(array('coin'=>$Issue['invit_coin'],'userid' => $User['invit_2'], 'invit' => userid(), 'name' => $Issue['name'], 'type' => L('2bd Tier ICO Bonus'), 'num' => $num, 'mum' => $mum, 'fee' => $invit_num_2, 'addtime' => time(), 'status' => 1));
            }
        }

        if ($User['invit_3'] && $Issue['invit_3']) {
            $invit_num_3 = bcmul(bcdiv($mum, 100, 8), $Issue['invit_3'], 6);

            if ($invit_num_3) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $User['invit_3']))->setInc($Issue['invit_coin'], $invit_num_3);
                $rs[] = $mo->table('codono_invit')->add(array('coin'=>$Issue['invit_coin'],'userid' => $User['invit_3'], 'invit' => userid(), 'name' => $Issue['name'], 'type' => L('3rd Tir ICO Bonus'), 'num' => $num, 'mum' => $mum, 'fee' => $invit_num_3, 'addtime' => time(), 'status' => 1));
            }
        }

        if ($mo->execute('commit') >= 0) {

            $this->success(L('Buy success!'));
        } else {
            $mo->rollback();
            $this->error('Failed purchase!');
        }
    }

    public function unlock($id)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($id, 'd')) {
            $this->error(L('Please select thaw item!'));
        }

        $IssueLog = M('IssueLog')->where(array('id' => $id))->find();

        if (!$IssueLog) {
            $this->error(L('INCORRECT_REQ'));
        }

        if ($IssueLog['status']) {
            $this->error(L('The current thaw is complete!'));
        }

        if ($IssueLog['ci'] <= $IssueLog['unlock']) {
            $this->error(L('Unauthorized access!'));
        }

        $tm = $IssueLog['endtime'] + (60 * 60 * $IssueLog['jian']);

        if (time() < $tm) {
            $this->error('Thawing time has not arrived,please at<br>[' . addtime($tm) . ']<br>After the operation again');
        }

        if ($IssueLog['userid'] != userid()) {
            $this->error(L('Unauthorized access'));
        }

        $jd_num = round($IssueLog['num'] / $IssueLog['ci'], 6);
        $mo = M();

        $mo->startTrans();
        $rs = array();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setInc($IssueLog['coinname'], $jd_num);
        $rs[] = $mo->table('codono_issue_log')->where(array('id' => $IssueLog['id']))->save(array('unlock' => $IssueLog['unlock'] + 1, 'endtime' => time()));

        if ($IssueLog['ci'] <= $IssueLog['unlock'] + 1) {
            $rs[] = $mo->table('codono_issue_log')->where(array('id' => $IssueLog['id']))->save(array('status' => 1));
        }

        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('Thaw success!'));
        } else {
            $mo->rollback();
            $this->error(L('Thaw failure!'));
        }
    }
}