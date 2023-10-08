<?php

namespace Home\Controller;

use Common\Ext\Exception;
use Common\Ext\GoogleAuthenticator;
use Think\Page;

class FinanceController extends HomeController
{
    const G2FA_REQUIRED_FOR_WITHDRAWAL = 1;  // IF Google2Fa required for withdrawal
    const SHOW_SITE_DIVIDEND = 0;  // IF Google2Fa required for withdrawal
    const DUST_MINIMUM = 0.5; //coin to be considered for dust conversion in SYSTEMCURRENCY
    private $multichain;
    public function _initialize()
    {
        $this->multichain = 1;
        $this->assign('show_dividend', self::SHOW_SITE_DIVIDEND);
        parent::_initialize();
    }

    function calcEstimatedAssetvalue()
    {
        $CoinList = C('Coin');
        $UserCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        $Market = C('market');
        $User_Selected_coin = $this->userinfo['fiat'];
        $print_coins=[];
        if (!isset($User_Selected_coin['fiat'])) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $User_Selected_coin['fiat'];
        }
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($CoinList as $k => $v) {
            {
			if($v['symbol']){
				continue;
			}
                $x_market = strtolower($v['name'] . '_' . $conversion_coin);
                if ($v['name'] == strtolower($conversion_coin)) {
                    $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                    $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
                }
                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    if(isset(c('market')[$x_market])){
                        $jia = c('market')[$x_market]['new_price'];    
                    }else{
                        $jia = 0;    
                    }
                    
                }
                 $jia = $after =NumToStr($jia);//$jia = $after = bcdiv((double)$jia, $multiplier, 8);

                $print_coins[$v['name']] = array('name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia , 'zhehe' => bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 4), 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']);

                $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
            }
        }
        return array('cc' => $conversion_coin, 'pc' => $print_coins, 'usd' => $usd);
    }

    public function index()
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }


        $calc = $this->calcEstimatedAssetvalue();
        $conv_coin_img=C('coin')[strtolower($calc['cc'])]['img']?:'default.png';
        $this->assign('conv_coin_img', $conv_coin_img);
        $this->assign('conversion_coin', $calc['cc']);
        $this->assign('usd', $calc['usd']);
        $this->assign('coinList', $calc['pc']);
        $this->assign('prompt_text', D('Text')->get_content('finance_index'));
        $this->display();
    }

    public function dust()
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }

        $CoinList = C('Coin');//M('Coin')->where(array('status' => 1))->order('name desc')->select();
        $UserCoin = $this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();
        $Market = C('market');//M('Market')->where(array('status' => 1))->select();
        $User_Selected_coin = $this->userinfo['fiat'];//M('User')->where(array('id' => userid()))->field('fiat')->find();
        /*
        if (!isset($User_Selected_coin['fiat'])) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $User_Selected_coin['fiat'];
        }*/
        $conversion_coin = DUST_COIN;
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($CoinList as $k => $v) {
            {

                $x_market = strtolower($v['name'] . '_' . $conversion_coin);
                if ($v['name'] == strtolower($conversion_coin)) {
                    $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                    $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
                }
                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    $jia = $before = 0;
                    //$jia=c('market')[$x_market]['new_price'];
                }
                $jia = $after = bcdiv((double)$jia, $multiplier, 8);
                $total = bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8);
                $require_ment = bcmul($UserCoin[$v['name']], $jia, 8);
                if ($total <= self::DUST_MINIMUM && $require_ment > 0) {
                    $print_coins[$v['name']] = array('name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia, 'zhehe' => $total, 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']);
                    $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
                }

            }
        }
        unset($print_coins[DUST_COIN]);
        $this->assign('conversion_coin', $conversion_coin);
        $this->assign('usd', $usd);
        $this->assign('coinList', $print_coins);
        $this->assign('prompt_text', D('Text')->get_content('finance_index'));
        $this->display();
    }

    public function doDustConvert()
    {
        $userid = userid();
        if (!$userid) {
            redirect(U('Login/login'));
        }


        $coin = strtolower(I('request.coin', 'usdt', 'string'));
        $coind = $coin . 'd';
        $isValidCoin = $this->isValidCoin($coin);
        if ($coin == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }
        $UserCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        $dust_coin = strtolower(DUST_COIN);
        $min_require_dust = self::DUST_MINIMUM;
        $user_coin_bal = $UserCoin[$coin];

        if ($user_coin_bal <= 0) {
            $this->error(L('Insufficient funds available'));
        }
        $value_of_dust = 0;
        $conversion_coin = $dust_coin;


        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }


        $v = C('coin')[$coin];
        $x_market = strtolower($v['name'] . '_' . $conversion_coin);
        if ($v['name'] == strtolower($conversion_coin)) {
            $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
            $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
        }
        if (isset($the_cms[$v['name']])) {
            $jia = $before = $the_cms[$v['name']];
        } else {
            //$jia = $before = 1;
            $jia = c('market')[$x_market]['new_price'];
        }
        $jia = $after = bcdiv((double)$jia, $multiplier, 8);

        $receiveable_amount = bcmul($UserCoin[$v['name']], $jia, 8); //give $dust_coin $receiveable_amount and take $user_coin_bal $coin

        $mo = M();
        $mo->startTrans();
        $rs = array();
        $before_spot_balance = $mo->table('codono_user_coin')->where(array('userid' => $userid))->field(array($coin, $coind))->find();
        $dust_entry = array('uid' => $userid, 'from_coin' => $coin, 'from_amount' => $user_coin_bal, 'to_coin' => $dust_coin, 'to_amount' => $receiveable_amount, 'created_at' => time());

        $rs[] = $did = $mo->table('codono_dust')->add($dust_entry);
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setDec($coin, $user_coin_bal);
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($dust_coin, $receiveable_amount);
        //Check if user_assets table has the following row or not userid=$userid and coin=$coin if not then first insert then update


        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('Dust Conversion completed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues converting!'));
        }


    }

    public function p2p()
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $CoinList = C('coin_safe');
        $UserAssets = M('UserAssets')->where(array('uid' => $uid))->select();
        foreach ($UserAssets as $UserAsset) {
            $UserCoin[$UserAsset['coin']] = $UserAsset['balance'];
            $UserCoin[$UserAsset['coin'] . 'd'] = $UserAsset['freeze'];
        }

        $Market = C('market');//M('Market')->where(array('status' => 1))->select();
        $User_Selected_coin = $this->userinfo['fiat'];//M('User')->where(array('id' => userid()))->field('fiat')->find();

        if (!isset($User_Selected_coin['fiat'])) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $User_Selected_coin['fiat'];
        }
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($CoinList as $k => $v) {
            {


                $x_market = strtolower($v['name'] . '_' . $conversion_coin);
                if ($v['name'] == strtolower($conversion_coin)) {
                    $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                    $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
                }
                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    $jia = $before = 1;
                    //$jia=c('market')[$x_market]['new_price'];
                }
                $jia = $after = bcdiv((double)$jia, $multiplier, 8);

                if ($v['type'] == 'rmb') {

                    $fiat[$v['name']] = array('name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia * 1, 'zhehe' => bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 4), 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']);
                } else {
                    $crypto[$v['name']] = array('name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia * 1, 'zhehe' => bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 4), 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']);
                }
                $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
            }
        }

        $this->assign('conversion_coin', $conversion_coin);
        $this->assign('usd', $usd);
        $this->assign('fiatList', $fiat);
        $this->assign('cryptoList', $crypto);
        $this->assign('prompt_text', D('Text')->get_content('finance_index'));
        $this->display();
    }

    public function fhindex()
    {
        $this->assign('show_dividend', self::SHOW_SITE_DIVIDEND);
        if (self::SHOW_SITE_DIVIDEND == 0) {
            die('Turned Off');
        }
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_dividend'));
        $coin_list = D('Coin')->get_all_xnb_list_allow();

        $list = array();
        foreach ($coin_list as $k => $v) {
            $list[$k]['img'] = C('coin')[$k]['img'];//D('Coin')->get_img($k);
            $list[$k]['title'] = $v;
            $list[$k]['quanbu'] = D('Coin')->get_sum_coin($k);
            $list[$k]['quanbu'] = $list[$k]['quanbu'] ?: 1;
            $list[$k]['wodi'] = D('Coin')->get_sum_coin($k, userid());
            $list[$k]['bili'] = bcmul(bcdiv($list[$k]['wodi'], $list[$k]['quanbu'], 8), 100, 2) . '%';
        }

        $this->assign('list', $list);
        $this->display();
    }


    public function myfhroebx()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_dividend_log'));
        $where['userid'] = userid();
        $Model = M('DividendLog');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function bank()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }


        $UserBankType = M('UserBankType')->where(array('status' => 1))->order('id desc')->select();
        $this->assign('UserBankType', $UserBankType);


        //$truename = M('User')->where(array('id' => userid()))->getField('truename');
        $user = $this->userinfo; //M('User')->where(array('id' => userid()))->find();

        if ($user['idcardauth'] == 0 && KYC_OPTIONAL == 0) {
            redirect('/user/nameauth');
        }

        $truename = $user['truename'];
        $this->assign('truename', $truename);
        //$UserBank = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->limit(1)->select();
        $UserBank = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $FiatList = M('Coin')->where(array('status' => 1, 'type' => 'rmb'))->field('name,title')->select();
        $this->assign('FiatList', $FiatList);
        $this->assign('UserBank', $UserBank);
        $this->assign('prompt_text', D('Text')->get_content('user_bank'));
        $this->display();
    }


    public function upbank($name, $bank, $bankprov, $bankcity, $bankaddr, $bankcard, $paypassword, $truename = "NA")
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note name of the wrong format!'));
        }
        if (!check($truename, 'english')) {
            $this->error(L('Account name incorrect format!'));
        }

        if (!check($bank, 'a')) {
            $this->error(L('Bank malformed!'));
        }

        if (!check($bankprov, 'c')) {
            $this->error(L('Opening provinces format error!'));
        }

        if (!check($bankcity, 'c')) {
            $this->error('Format of the city is wrong!');
        }

        if (!check($bankaddr, 'a')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'a')) {
            $this->error(L('Bank account number format error!'));
        }

        if (strlen($bankcard) < 4 || strlen($bankcard) > 50) {

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
            $this->error('Each user can add upto 10 accounts only!');
        }

        if (M('UserBank')->add(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'truename' => $truename, 'addtime' => time(), 'status' => 1))) {
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


    public function mycz($status = NULL, $coinname = '')
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }

        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mycz'));

        if (!check($coinname, 'n')) {
            $coinname = DEFAULT_FIAT;
            //$this->error(L('Currency format error!'));
        }


        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        if (!is_array(C('coin')[$coinname])) {
            $this->error(L('Invalid Coin!'));
        }

        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';

        $myczType = M('MyczType')->where(array('status' => 1))->select();
        $coin_id = C('coin')[$coinname]['id'];
        $myczTypeList = array();
        foreach ($myczType as $k => $v) {
            $show_coin = json_decode($v['show_coin']);
            if (in_array($coin_id, $show_coin)) {

                $myczTypeList[$v['name']]['name'] = $v['title'];
                $myczTypeList[$v['name']]['img'] = $v['img'];
            }
        }
        $this->assign('myczTypeList', $myczTypeList);
        $user_coin = $this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['fiat'] = format_num($user_coin[$coin_name], 2);
        $user_coin['fiat_trade'] = format_num($user_coin[$coin_named], 2);
        $this->assign('user_coin', $user_coin);
        $coin_img = C('coin')[$coinname]['img'];
        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }
        foreach (C('coin') as $coinlist) {
            if ($coinlist['type'] == 'rmb' && $coinlist['zr_jz'] == 1) {
                $_fiat_coin['name'] = $coinlist['name'];
                $_fiat_coin['img'] = $coinlist['img'];
                $fiatcoins[] = $_fiat_coin;
            }

        }
        $this->assign('fiatcoins', $fiatcoins);
        $this->assign('coinname', $coinname);
        $this->assign('coin_img', $coin_img);
        $this->assign('status', $status);
        $where['userid'] = userid();
        $where['state'] = array('neq', '5');
        $count = M('Mycz')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['type'] = M('MyczType')->where(array('name' => $v['type']))->getField('title');
            $list[$k]['typeEn'] = $v['type'];
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myczRemittance($id = NULL)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(array('id' => $id))->find();

        if (!$mycz) {
            $this->error(L('Top-order does not exist!'));
        }

        if ($mycz['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mycz['status'] != 0) {
            $this->error(L('You can not mark it as paid!'));
        }

        $rs = M('Mycz')->where(array('id' => $id))->save(array('status' => 3));

        if ($rs) {
            $this->success(L('Successful operation'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function myczChakan($id = NULL)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(array('id' => $id))->find();

        if (!$mycz) {
            $this->error(L('Top-order does not exist!'));
        }

        if ($mycz['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mycz['status'] != 0) {
            $this->error(L('Order has been processed!'));
        }

        $rs = M('Mycz')->where(array('id' => $id))->save(array('status' => 3));

        if ($rs) {
            $this->success('', array('id' => $id));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function myczUp($type, $num, $coinname = 'usd')
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error('Please Login!');
        }


        if (!check($type, 'n')) {
            $this->error(L('Recharge way malformed!'));
        }

        if (!check($num, 'usd')) {
            $this->error(L('Recharge amount malformed!'));
        }
        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        $Coin = C('coin')[$coinname]; //M('Coin')->where(array('name' => $coinname))->find();

        if (!$Coin) {
            $this->error(L('Currency wrong!'));
        }
        $coin_name = strtolower($coinname);

        $myczType = M('MyczType')->where(array('name' => $type))->find();

        if (!$myczType) {
            $this->error(L('There is no way to recharge!'));
        }

        if ($myczType['status'] != 1) {
            $this->error(L('There is no way to recharge open!'));
        }

        $coin_id = C('coin')[$coinname]['id'];
        $show_coin = json_decode($myczType['show_coin']);

        if (!in_array($coin_id, $show_coin)) {
            $this->error($coinname . ' can not be recharged using ' . $myczType['title']);
        }


        $mycz_min = ($myczType['min'] ?: 1);
        $mycz_max = ($myczType['max'] ?: 100000);

        if ($num < $mycz_min) {
            $this->error(L('Recharge amount can not be less than') . $mycz_min . ' ' . strtoupper($coinname));
        }

        if ($mycz_max < $num) {
            $this->error(L('Recharge amount can not exceed') . $mycz_max . ' ' . strtoupper($coinname));
        }


        for (; true;) {
            $tradeno = tradeno();

            if (!M('Mycz')->where(array('tradeno' => $tradeno))->find()) {
                break;
            }
        }

        $mycz = M('Mycz')->add(array('userid' => userid(), 'coin' => $coin_name, 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0));

        if ($mycz) {
            $this->success(L('Prepaid orders created successfully!'), array('id' => $mycz));
        } else {
            $this->error(L('Recharge order creation failed!'));
        }
    }


    public function outlog($status = NULL)
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));


        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }
        $where['userid'] = userid();
        $count = M('Mytx')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();

    }


    public function mytx($coin = DEFAULT_FIAT, $status = NULL)
    {


        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }

        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));
        $cellphone = $this->userinfo['cellphone'];//M('User')->where(array('id' => userid()))->getField('cellphone');
        $email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField('email');

        if ($cellphone || $email) {
            $cellphone = substr_replace($cellphone, '****', 3, 4);
            $email = substr_replace($email, '****', 3, 4);
        } else {
            if (M_ONLY == 1) {
                redirect(U('Home/User/cellphone'), $time = 5, $msg = L('Please Verify your Phone!'));
            }
        }

        foreach (C('coin') as $coinlist) {
            if ($coinlist['type'] == 'rmb') {
                $_fiat_coin['name'] = $coinlist['name'];
                $_fiat_coin['img'] = $coinlist['img'];
                $fiatcoins[] = $_fiat_coin;
            }

        }
        if (C('coin')[$coin]['name'] != $coin || C('coin')[$coin]['type'] != 'rmb' || C('coin')[$coin]['status'] != 1) {
            //$this->error(L('Wrong Coin!'),U('Finance/mytx'));
            redirect(U('Finance/mytx/coin/usd'), $time = 1, $msg = L('Wrong Coin!'));

        }
        $coin_img = C('coin')[$coin]['img'];
        $this->assign('coin_img', $coin_img);
        $this->assign('coin', $coin);
        $this->assign('fiatcoins', $fiatcoins);
        $this->assign('cellphone', $cellphone);
        $this->assign('email', $email);
        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['fiat'] = format_num($user_coin[$coin], 2);
        $user_coin['fiat_total'] = format_num($user_coin[$coin . 'd'], 2);
        $this->assign('user_coin', $user_coin);

        $userBankList = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->limit(10)->select();
        $this->assign('userBankList', $userBankList);

        $this->assign('withdrawal_fee_percent', C('coin')[$coin]['zc_fee']);
        $this->assign('withdrawal_fee_flat', C('coin')[$coin]['zc_flat_fee']);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
        $count = M('Mytx')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function mytxUp($num, $paypassword, $type, $coinname, $cellphone_verify = 0)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        $Coin = C('coin')[$coinname];// M('Coin')->where(array('name' => $coinname))->find();

        if (!$Coin) {
            $this->error(L('Currency wrong!'));
        }
        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';

        if (M_ONLY == 1) {
            if (!check($cellphone_verify, 'd')) {
                $this->error(L('INVALID_SMS_CODE'));
            }
        }

        if (!check($num, 'd')) {
            $this->error(L('The amount of withdrawals format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($type, 'd')) {
            $this->error(L('Withdraw way malformed!'));
        }
        if (M_ONLY == 1) {
            if ($cellphone_verify != session('mytx_verify')) {
                $this->error(L('INCORRECT_SMS_CODE'));
            }
        }
        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();

        if ($userCoin[$coin_name] < $num) {
            $this->error(L('Lack of available Balance!'));
        }

        $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $userBank = M('UserBank')->where(array('id' => $type))->find();

        if (!$userBank) {
            $this->error(L('Withdraw wrong address!'));
        }
        $mytx_bei = C('mytx_bei'); //multiple of
        $mytx_min = C('coin')[$coin_name]['zc_min'] ?: 1;
        $mytx_max = C('coin')[$coin_name]['zc_max'] ?: 1000000;

        $mytx_fee_percent = C('coin')[$coin_name]['zc_fee'] ?: 0;
        $mytx_fee_flat = C('coin')[$coin_name]['zc_flat_fee'] ?: 0;

        if ($num < $mytx_min) {
            $this->error(L('Every withdrawal amount can not be less than') . $mytx_min);
        }

        if ($mytx_max < $num) {
            $this->error(L('Every withdrawal amount can not exceed') . $mytx_max);
        }

        if ($mytx_bei) {
            if ($num % $mytx_bei != 0) {
                $this->error(L('Every mention the amount of cash must be') . $mytx_bei . L('Integral multiples!'));
            }
        }
        $truename = $userBank['truename'] ?: $user['truename'];
        $percent_fee = bcmul(bcdiv($num, 100, 8), $mytx_fee_percent, 2);
        $flat_fee = $mytx_fee_flat;
        $remaining = bcsub($num, $flat_fee, 2);
        //$fees_total=bcadd($percent_fee,$flat_fee,8);
        $fee = bcmul(bcdiv($remaining, 100, 8), $percent_fee, 2);
        $fees_total = bcadd($fee, $flat_fee, 8);
        $mum = bcsub($remaining, $fee, 2);
        $mo = M();

        $mo->startTrans();
        $rs = array();
        $finance = $mo->table('codono_finance')->where(array('userid' => userid()))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin_name, $num);
        $rs[] = $finance_nameid = $mo->table('codono_mytx')->add(array('userid' => userid(), 'num' => $num, 'fee' => $fees_total, 'mum' => $mum, 'name' => $userBank['name'], 'truename' => $truename, 'bank' => $userBank['bank'], 'bankprov' => $userBank['bankprov'], 'bankcity' => $userBank['bankcity'], 'bankaddr' => $userBank['bankaddr'], 'bankcard' => $userBank['bankcard'], 'addtime' => time(), 'status' => 0, 'coin' => $coin_name));
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $finance_hash = md5(userid() . $finance_num_user_coin[$coin_name] . $finance_num_user_coin[$coin_named] . $mum . $finance_mum_user_coin[$coin_name] . $finance_mum_user_coin[$coin_named] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$coin_name] + $finance_num_user_coin[$coin_named];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $coin_name, 'num_a' => $finance_num_user_coin[$coin_name], 'num_b' => $finance_num_user_coin[$coin_named], 'num' => $finance_num_user_coin[$coin_name] + $finance_num_user_coin[$coin_named], 'fee' => $num, 'type' => 2, 'name' => 'mytx', 'nameid' => $finance_nameid, 'remark' => 'Fiat withdrawal', 'mum_a' => $finance_mum_user_coin[$coin_name], 'mum_b' => $finance_mum_user_coin[$coin_named], 'mum' => $finance_mum_user_coin[$coin_name] + $finance_mum_user_coin[$coin_named], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        if (check_arr($rs)) {
            session('mytx_verify', null);
            $mo->commit();

            $this->success(L('Withdrawal order to create success!'));
        } else {
            $mo->rollback();
            $this->error(L('Withdraw order creation failed!'));
        }
    }

    public function mytxReject($id)
    {

        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mytx = M('Mytx')->where(array('id' => $id, 'userid' => userid()))->find();

        if (!$mytx) {
            $this->error(L('Withdraw order does not exist!'));
        }

        if ($mytx['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mytx['status'] != 0) {
            $this->error(L('Orders can not be undone!'));
        }

        $mo = M();

        $mo->startTrans();
        $rs = array();
        $fiat = strtolower($mytx['coin']);
        $fiatd = strtolower($mytx['coin']) . 'd';
        $finance = $mo->table('codono_finance')->where(array('userid' => $mytx['userid']))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mytx['userid']))->find();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $mytx['userid']))->setInc($fiat, $mytx['num']);
        $rs[] = $mo->table('codono_mytx')->where(array('id' => $mytx['id']))->setField('status', 2);
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mytx['userid']))->find();
        $finance_hash = md5($mytx['userid'] . $finance_num_user_coin[$fiat] . $finance_num_user_coin[$fiatd] . $mytx['num'] . $finance_mum_user_coin[$fiat] . $finance_mum_user_coin[$fiatd] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$fiat] + $finance_num_user_coin[$fiatd];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(array('userid' => $mytx['userid'], 'coinname' => $fiat, 'num_a' => $finance_num_user_coin[$fiat], 'num_b' => $finance_num_user_coin[$fiatd], 'num' => $finance_num_user_coin[$fiat] + $finance_num_user_coin[$fiatd], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => 'Fiat Withdrawal-Undo withdrawals', 'mum_a' => $finance_mum_user_coin[$fiat], 'mum_b' => $finance_mum_user_coin[$fiatd], 'mum' => $finance_mum_user_coin[$fiat] + $finance_mum_user_coin[$fiatd], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }


    public function myczReject($id)
    {

        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(array('id' => $id, 'userid' => userid()))->find();

        if (!$mycz) {
            $this->error(L('Withdraw order does not exist!'));
        }

        if ($mycz['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mycz['status'] != 0 && $mycz['status'] != 3) {
            $this->error(L('Order cant be cancelled!') . $mycz['status']);
        }

        $mo = M();

        $mo->startTrans();
        $rs = array();
        $rs[] = $mo->table('codono_mycz')->where(array('id' => $mycz['id']))->setField('status', 5);
        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }

    /**
     * @throws Exception
     */
    public function myzr($coin = NULL)
    {

        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        if ($this->multichain) {
            redirect(U('Wallet/cryptodeposit', array('coin' => $coin)));
        }
        $dest_tag = '';
        $show_qr = 1;
        $this->assign('prompt_text', D('Text')->get_content('finance_myzr'));
        $coin = $coin ?: C('xnb_mr');
        $coin = trim($coin);

        $this->assign('xnb', $coin);

        $Coins = C('coin');
        foreach ($Coins as $k => $v) {
            if ($v['type'] != 'rmb') {
                $coin_list[$v['name']] = $v;
            }
        }

        ksort($coin_list);
        $this->assign('coin_list', $coin_list);
        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();
        $user_coin[$coin] = format_num($user_coin[$coin], 8);
        $this->assign('user_coin', $user_coin);

        $Coin = $Coins[$coin];
        $this->assign('zr_jz', $Coin['zr_jz']);


        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        }

        $this->assign("codono_opencoin", $codono_getCoreConfig['codono_opencoin']);

        if ($codono_getCoreConfig['codono_opencoin'] == 1 && $Coin['type'] != 'offline') {
            if (!$Coin['zr_jz']) {
                $wallet = L('The current ban into the currency!');
            } else {
                $qbdz = $coin . 'b';
                $tokenof = $Coin['tokenof'];
                if (!$user_coin[$coin . 'b'] && !$user_coin[$tokenof . 'b']) {

                    if ($Coin['type'] == 'rgb') {
                        $wallet = md5(username() . $coin);

                        $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $wallet));
                        $user_exists = $this->userinfo['id'];//M('User')->where(array('id' => userid()))->getField('id');

                        if (!$rs && !$user_exists) {
                            $this->error(L('Generate wallet address wrong!'));
                        }
                        //die($qbdz);
                        if (!$rs && $user_exists) {
                            $ucoin[$qbdz] = $wallet;
                            $ucoin['userid'] = $user_exists;
                            $new_rs = M('UserCoin')->add($ucoin);
                        }

                    }


                    //XRP STARTS

                    if ($Coin['type'] == 'xrp') {
                        $address = $wallet = $Coin['codono_coinaddress'];//Contract Address
                        $dest_tag = $user_coin[$coin . '_tag'];

                        if (isset($address)) {
                            if (!$dest_tag) {

                                $xrp_len = 9 - strlen($uid);
                                $min = pow(10, ($xrp_len - 1));
                                $max = pow(10, $xrp_len) - 1;
                                $xrp_str = mt_rand($min, $max);

                                $saveme[$coin . '_tag'] = $dest_tag = $uid . $xrp_str;

                                //TO add xrp_tag field in user_coin table if not exits
                                $dest_tag_field = $coin . '_tag';
                                $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                                $if_tag_exists = M()->execute($tag_sql);

                                //Create a destination tag
                                if (!$if_tag_exists) {

                                    M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for $coin'");
                                }

                                $rs = M('user_coin')->where(array('userid' => $uid))->save(array($coin . '_tag' => $dest_tag));

                                if ($rs) {
                                    $wallet = $address;
                                    $show_qr = 1;
                                } else {
                                    $wallet = L('Wallet System is currently offline 2! ' . $coin);
                                    $show_qr = 0;
                                }
                            }
                        } else {
                            $wallet = L('Wallet System is currently offline 2! ' . $coin);
                            $show_qr = 0;

                        }
                    }

                    //XRP ENDS
                    //Tron starts
                    if ($Coin['type'] == 'tron') {

                        $contract = $Coin['dj_yh'];

                        if (!$contract) {
                            //Call the interface to generate a new wallet address

                            $tron = A('Tron')->newAccount();

                            if (is_array($tron)) {
                                $saveme[$qbdz] = $wallet = $tron['address_base58'];
                                $tron_info = $tron;
                                $tron_info['uid'] = $uid;
                                $tron_info['private_key'] = cryptString($tron['private_key']);
                                $rs[] = M('Tron')->add($tron_info);
                                $rs[] = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                            } else {
                                $wallet = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr = 0;
                            }
                        } else {
                            $rs1 = $user_coin;
                            $tokenof = $Coin['tokenof'];
                            $tokenofb = $tokenof . 'b';

                            if ($rs1[$tokenofb]) {
                                $wallet = $rs1[$tokenofb];
                                $show_qr = 1;

                            } else {

                                //Call the interface to generate a new wallet address
                                $tron = A('Tron')->newAccount();
                                if ($tron) {
                                    $saveme[$qbdz] = $wallet = $tron['address_base58']; //token address
                                    $saveme[$tokenofb] = $wallet; //token parent address

                                    $tron_info = $tron;
                                    $tron_info['uid'] = $uid;
                                    $tron_info['private_key'] = cryptString($tron['private_key']);
                                    $rs[] = M('Tron')->add($tron_info);
                                    $rs[] = M('UserCoin')->where(array('userid' => $uid))->save($saveme);


                                } else {
                                    $wallet = L('Wallet System is currently offline 1! ' . $coin);
                                    $show_qr = 0;
                                }

                            }
                        }

                    }
                    //Tron  Ends
                    //esmart starts
                    if ($Coin['type'] == 'esmart') {

                        $contract = $Coin['dj_yh'];//Contract Address
                        $dj_password = $Coin['dj_mm'];
                        $dj_address = $Coin['dj_zj'];
                        $dj_port = $Coin['dj_dk'];
                        $esmart_config = array(
                            'host' => $Coin['dj_zj'],
                            'port' => $Coin['dj_dk'],
                            'coinbase' => $Coin['codono_coinaddress'],
                            'password' => cryptString($Coin['dj_mm'], 'd'),
                            'contract' => $Coin['dj_yh'],
                            'rpc_type' => $Coin['rpc_type'],
                            'public_rpc' => $Coin['public_rpc'],
                        );
                        $Esmart = Esmart($esmart_config);

                        if (!$contract) {

                            //esmart
                            //Call the interface to generate a new wallet address
                            $wall_pass = ETH_USER_PASS;
                            $wallet = $Esmart->personal_newAccount($wall_pass);

                            if ($wallet) {
                                if ($tokenof) {
                                    $saveme[$tokenof . 'b'] = $wallet;
                                } else {
                                    $saveme[$qbdz] = $wallet;
                                }


                                $rs = M('UserCoin')->where(array('userid' => userid()))->save($saveme);

                            } else {
                                $wallet = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr = 0;
                            }
                        } else {

                            //esmart contract
                            $rs1 = $user_coin;
                            $tokenof = $Coin['tokenof'];
                            $tokenofb = $tokenof . 'b';
                            if ($rs1[$tokenofb]) {
                                $wallet = $rs1[$tokenofb];
                                $saveme[$qbdz] = $wallet;

                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                            } else {
                                //Call the interface to generate a new wallet address
                                $wall_pass = ETH_USER_PASS;
                                $wallet = $Esmart->personal_newAccount($wall_pass);
                                if ($wallet) {
                                    if ($tokenof) {
                                        $saveme[$tokenof . 'b'] = $wallet;
                                    } else {
                                        $saveme[$qbdz] = $wallet;
                                    }

                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                                } else {
                                    $wallet = L('Wallet System is currently offline 1! ' . $coin);
                                    $show_qr = 0;
                                }

                            }
                        }

                    }
                    //Esmart  Ends

                    //CoinPayments starts
                    if ($Coin['type'] == 'coinpay') {

                        $dj_username = $Coin['dj_yh'];
                        $dj_password = $Coin['dj_mm'];
                        $dj_address = $Coin['dj_zj'];
                        $dj_port = $Coin['dj_dk'];

                        $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                        $information = $cps_api->GetBasicInfo();
                        $coinpay_coin = strtoupper($coin);


                        if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                            clog('coinpay_connection', $coin . ' can not be connectted at time: Error is ' . $information['error']);
                            $wallet = L('Wallet System is currently offline 1!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;

                            $ipn_url = SITE_URL . 'IPN/confirm';


                            // Prevent coinpayments to send duplicate address
                            $need_new_address = false;

                            $transaction_response = $cps_api->GetCallbackAddressWithIpn($coinpay_coin, $ipn_url);
                            $dest_tag = $transaction_response['result']['dest_tag'] ?: 0;
                            $wallet_addr = $transaction_response['result']['address'];

                            $user_condition = array();
                            $user_condition[$coin . 'b'] = $wallet_addr;
                            if ($dest_tag != NULL || $dest_tag != 0) {
                                $user_condition[$coin . '_tag'] = $dest_tag;
                            }

                            if (($user = M('UserCoin')->where($user_condition)->find())) {
                                $need_new_address = true;
                            }


                            // Prevent coinpayments to send duplicate address ends


                            if (!is_array($wallet_addr)) {
                                $wallet_ad = $wallet_addr;
                                if (!$wallet_ad) {
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }


                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                            }
                            $dest_tag_field = $coin . '_tag';
                            $coinpay_update_array[$qbdz] = $wallet;

                            $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                            $if_tag_exists = M()->execute($tag_sql);

                            //Create a destination tag
                            if (!$if_tag_exists) {

                                M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for xrp,xmr'");
                            }

                            if ($dest_tag != 0 || $dest_tag != NULL) {
                                $coinpay_update_array[$dest_tag_field] = strval($dest_tag);
                                //$dtag_sql='UPDATE `codono_user_coin` SET `'.$dest_tag_field.'` = '.$dest_tag.' WHERE `codono_user_coin`.`userid` = '.userid();
                                $dtag_sql = 'UPDATE `codono_user_coin` SET `' . $dest_tag_field . '` = "' . $dest_tag . '" WHERE `codono_user_coin`.`userid` = ' . userid();
                                $rs = M('UserCoin')->execute($dtag_sql);
                            }


                            $mo = M();
                            $rs = $mo->table('codono_user_coin')->where(array('userid' => $uid))->save($coinpay_update_array);

                            if (!$rs) {
                                //    $this->error('Add error address wallet3!');
                                $wallet = L('Wallet System is currently offline 1!');
                                $show_qr = 0;
                            }
                        }


                    }
                    //CoinPayments  Ends
                    //WavesPlatform Starts

                    if ($Coin['type'] == 'waves') {

                        $qbdz = 'wavesb';
                        $dj_username = $Coin['dj_yh'];
                        $dj_password = $Coin['dj_mm'];
                        $dj_address = $Coin['dj_zj'];
                        $dj_port = $Coin['dj_dk'];
                        $dj_decimal = $Coin['cs_qk'];
                        $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
                        $waves_coin = strtoupper($coin);
                        $information = json_decode($waves->status(), true);

                        if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                            $wallet = L('Wallet System is currently offline 1!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;
                            $rs1 = M('UserCoin')->where(array('userid' => $uid))->find();
                            if ($rs1['wavesb']) {
                                $waves_good = 0;
                                $wallet_addr = $rs1['wavesb'];
                            } else {
                                $waves_good = 1;
                                $transaction_response = $address = json_decode($waves->CreateAddress(), true);
                                $wallet_addr = $transaction_response['address'];
                            }

                            if (!is_array($wallet_addr)) {
                                $wallet_ad = $wallet_addr;
                                if (!$wallet_ad) {
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $show_qr = 0;
                                $wallet = L('Wallet System is currently offline 2!');
                            }
                            if ($show_qr == 1) {
                                $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $wallet));
                                if (!$rs && $waves_good == 1) {
                                    $wallet = L('Wallet System is currently offline 3!');
                                }
                            }
                        }
                    }
                    //WavesPlatform Ends
                    //blockio starts
                    if ($Coin['type'] == 'blockio') {

                        $dj_username = $Coin['dj_yh'];
                        $dj_password = $Coin['dj_mm'];
                        $dj_address = $Coin['dj_zj'];
                        $dj_port = $Coin['dj_dk'];

                        $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                        $json = $block_io->get_balance();

                        if (!isset($json->status) || $json->status != 'success') {
                            //$this->error(L('Wallet link failure! 1'));
                            $wallet = L('Wallet System is currently offline 2!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;
                            $wallet_addr = $block_io->get_address_by_label(array('label' => username()))->data->address;

                            if (!is_array($wallet_addr)) {
                                $getNewAddressInfo = $block_io->get_new_address(array('label' => username()));
                                $wallet_ad = $getNewAddressInfo->data->address;


                                if (!$wallet_ad) {
                                    //$this->error('Generate Wallet address error1!');
                                    //$this->error($wallet_addr.'ok');
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                            }

                            $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $wallet));

                            if (!$rs) {
                                $this->error('Add error address wallet3!');
                            }
                        }


                    }
                    //blockio  Ends

                    //cryptonote starts
                    if ($Coin['type'] == 'cryptonote') {
                        $dj_username = $Coin['dj_yh'];
                        $dj_password = $Coin['dj_mm'];
                        $dj_address = $Coin['dj_zj'];
                        $dj_port = $Coin['dj_dk'];
                        $cryptonote = CryptoNote($dj_address, $dj_port);
                        $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

                        $json = json_decode($cryptonote->get_height());

                        if (!isset($json->height) || $json->error != 0) {
                            $wallet = L('Wallet System is currently offline 2!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;
                            $cryptofields = $coin . 'b';


                            $wallet_addr = $Coin['codono_coinaddress'];
                            if (!is_array($wallet_addr)) {
                                $getNewAddressInfo = json_decode($cryptonote->create_address(0, username()));
                                $wallet_ad = $getNewAddressInfo->address;


                                if (!$wallet_ad) {
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                                //$wallet=L('Can not generate '.$coin.' wallet at the moment');
                            }

                            $dest_tag = $cryptonote->genPaymentId();
                            $dest_tag_field = $coin . '_tag';
                            $cryptonote_update_array[$qbdz] = $wallet;

                            $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                            $if_tag_exists = M()->execute($tag_sql);

                            //Create a destination tag
                            if (!$if_tag_exists) {

                                M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for xrp,xmr'");
                            }

                            if ($dest_tag != 0 || $dest_tag != NULL) {
                                $cryptonote_update_array[$dest_tag_field] = $dest_tag;
                                $dtag_sql = 'UPDATE `codono_user_coin` SET `' . $dest_tag_field . '` = "' . $dest_tag . '" WHERE `codono_user_coin`.`userid` = ' . userid();
                                $rs = M('UserCoin')->execute($dtag_sql);
                            }


                            $mo = M();
                            $rs = $mo->table('codono_user_coin')->where(array('userid' => $uid))->save($cryptonote_update_array);


                            if (!$rs) {
                                $this->error('Add error address wallet3!');
                            }
                        }


                    }
                    //CryptoNote Ended
                    //Bitcoin starts
                    if ($Coin['type'] == 'qbb') {

                        $dj_username = $Coin['dj_yh'];
                        $dj_password = $Coin['dj_mm'];
                        $dj_address = $Coin['dj_zj'];
                        $dj_port = $Coin['dj_dk'];
                        $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                        $json = $CoinClient->getinfo();

                        if (!isset($json['version']) || !$json['version']) {
                            //$this->error(L('Wallet link failure! 1'));
                            $wallet = L('Wallet System is currently offline 3!');
                            $show_qr = 0;
                        } else {

                            $show_qr = 1;


                            $wallet_ad = $CoinClient->getnewaddress();

                            if (!$wallet_ad) {
                                $this->error('Generate Wallet address error1!');
                            } else {
                                $wallet = $wallet_ad;
                            }


                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                            }
                            //$rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $wallet));
                            $rs = M()->execute("UPDATE `codono_user_coin` SET  `$qbdz` =  '$wallet' WHERE userid = '$uid' ");
                            if (!$rs) {
                                $this->error('Add error address wallet3!');
                            }
                        }
                    }
                } else {

                    if ($user_coin[$tokenof . 'b']) {
                        $wallet = $user_coin[$tokenof . 'b'];
                    } else {
                        $wallet = $user_coin[$coin . 'b'];
                    }
                    $dest_tag = "";
                    if (isset($user_coin[$coin . '_tag'])) {
                        $dest_tag = $user_coin[$coin . '_tag'];
                    }
                }
            }
        } else {

            if (!$Coin['zr_jz']) {
                $wallet = L('The current ban into the currency!');
            } else {

                $wallet = $Coin['codono_coinaddress'];

                $cellphone = $this->userinfo['cellphone'];//M('User')->where(array('id' => userid()))->getField('cellphone');
                $email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField('email');

                if ($cellphone || $email) {
                    $cellphone = substr_replace($cellphone, '****', 3, 4);
                    $email = substr_replace($email, '****', 3, 4);
                } else {
                    if (M_ONLY == 1) {
                        redirect(U('Home/User/cellphone'), $time = 5, $msg = L('Please Verify your Phone!'));
                    }
                }

                $this->assign('cellphone', $cellphone);
                $this->assign('email', $email);


            }

        }


        $this->assign('wallet', $wallet);
        $this->assign('dest_tag', $dest_tag);
        $where['userid'] = $uid;
        $where['coinname'] = $coin;
        $Model = M('Myzr');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('show_dividend', self::SHOW_SITE_DIVIDEND);
        $this->assign('list', $list);
        $this->assign('show_qr', $show_qr);
        $this->assign('page', $show);
        $this->display();
    }


    public function addnew($coin = NULL)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $Coin = M('Coin')->where(array(
            'status' => 1,
            'type' => array('neq', 'rmb')
        ))->select();

        if (!$coin) {
            $coin = "";
        }

        $this->assign('xnb', $coin);

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        $where['status'] = 1;
        if (!empty($coin)) {
            $where['coinname'] = $coin;
        }


        $count = M('UserWallet')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();

        $userWalletList = M('UserWallet')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('page', $show);
        $this->assign('userWalletList', $userWalletList);
        $this->assign('prompt_text', D('Text')->get_content('user_wallet'));
        $this->display();
    }

    public function upwallet($coin, $name, $addr, $paypassword)
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
            $this->error('You are allowed to add till 10 addresses!');
        }

        if (M('UserWallet')->add(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'coinname' => $coin, 'addtime' => time(), 'status' => 1))) {
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


    public function coinoutLog($coin = NULL)
    {
        $this->page_title=l("Withdrawal History");

        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myzc'));
        $explorer = "";
        $coin = trim($coin);
        if (isset(C('coin')[$coin]) && isset($coin)) {
            $explorer = C('coin')[$coin]['js_wk'];

        }
        $this->assign('xnb', $coin);
        $CoinInfo = C('coin');

        foreach ($CoinInfo as $k => $v) {
            if ($v['type'] != 'rmb') {
                $coin_list[$v['name']] = $v;
            }

        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        if (isset($coin) && $coin != '') {
            $where['coinname'] = $coin;
        }

        $Model = M('Myzc');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('explorer', $explorer);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function requestOTP($coinname, $address, $amount)
    {
        if (!userid()) {
            $this->error(L('Please login first!'));
        }

        $user_coin = $this->usercoins;// M('UserCoin')->where(array('userid' => userid()))->find();
        $min_withdrawal_allow = C('coin')[$coinname]['zc_min'];
        $max_withdrawal_allow = C('coin')[$coinname]['zc_max'];

        if ($user_coin[$coinname] < $amount) {
            $this->error("Insufficient funds:  $user_coin[$coinname] $coinname");
        }

        if ($amount < $min_withdrawal_allow || $amount <= 0) {
            if ($min_withdrawal_allow <= 0) {
                $min_error_message = "Please try higher amount for withdrawal";
            } else {
                $min_error_message = "Minimum withdrawal amount $min_withdrawal_allow $coinname";
            }
            $this->error($min_error_message);
        }

        if ($amount > $max_withdrawal_allow) {
            if ($max_withdrawal_allow < 0) {
                $max_error_message = "Please try lower amount for withdrawal";
            } else {
                $max_error_message = "Max withdrawal amount $min_withdrawal_allow $coinname";
            }
            $this->error($max_error_message);
        }


        $user = $this->userinfo;
        $code = tradeno();

        session('requestOTP', $code);
        $email = $user['email'];
        $client_ip = get_client_ip();
        $requestTime = date('Y-m-d H:i', time()) . '(' . date_default_timezone_get() . ')';
        $subject = "Withdrawal Request on " . SHORT_NAME;
        $content = "<br/><strong>DO NOT SHARE THIS CODE WITH ANYONE!!</strong><br/>To complete the withdrawal process,<br/><br/>You may be asked to enter this confirmation code:<strong>$code <strong><br/><br/><small><i>
			<table>
			<tr style='border:2px solid black'><td>Email</td><td>$email</td></tr>
			<tr style='border:2px solid black'><td>IP</td><td>$client_ip</td></tr>
			<tr style='border:2px solid black'><td>Coin</td><td>$coinname</td></tr>
			<tr style='border:2px solid black'><td>Amount</td><td>$amount</td></tr>
			<tr style='border:2px solid black'><td>Address</td><td>$address</td></tr>
			<tr style='border:2px solid black'><td>Time</td><td>$requestTime</td></tr>	
			</table>
			<strong>If You didnt request this withdrawal, immediately change passwords,and contact us</strong>";
        addnotification($email,$subject,$content);
        $this->success(L('Please check email for code'));
    }


    public function myzc($coin = NULL)
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }
        if ($this->multichain) {
            redirect(U('Wallet/cryptowithdrawal', array('coin' => $coin)));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myzc'));
        $coin = $coin ? strtolower($coin) : C('xnb_mr');


        $this->assign('xnb', $coin);
        $Coins = C('Coin');

        foreach ($Coins as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        $user = $this->userinfo;

        $is_ga = $user['ga'] ? 1 : 0;

        $cmcs = (APP_DEBUG ? null : S('cmcRates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }

        if (!$user['fiat']) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $user['fiat'];
        }

        $multiplier = 1;
        $the_cms = array();
        $cms = array();
        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($the_cms as $key => $usd_value) {
            $cms[$key] = bcdiv($usd_value, $multiplier, 8);
        }

        $this->assign('is_ga', $is_ga);
        $this->assign('coin_list', $coin_list);
        $usercoins[$coin] = $this->usercoins[$coin];
        $user_coin = $usercoins;

        $user_coin[$coin] = format_num($user_coin[$coin], 8);
        $user_coin['converted'] = bcmul($user_coin[$coin], $cms[$coin], 8);
        $user_coin['conversion_coin'] = $conversion_coin;
        $this->assign('user_coin', $user_coin);
        $cellphone = $this->userinfo['cellphone'];
        $email = $this->userinfo['email'];

        if (!$coin_list[$coin]['zc_jz']) {
            $this->assign('zc_jz', L($coin . ': Withdrawals are temporarily disabled'));
        } else {
            $userWalletList = M('UserWallet')->where(array('userid' => userid(), 'status' => 1, 'coinname' => $coin))->order('id desc')->select();
            $this->assign('userWalletList', $userWalletList);


            if ($cellphone || $email) {
                $cellphone = substr_replace($cellphone, '****', 3, 4);
                $email = substr_replace($email, '****', 3, 4);
            }
        }
        $this->assign('cellphone', $cellphone);
        $this->assign('email', $email);
        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Model = M('Myzc');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function upmyzc($coin, $num, $addr, $paypassword, $cellphone_verify = 0, $dest_tag = 0, $gacode = 0, $otp = 0)
    {
        $uid = userid();

        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        if (!kyced()) {
            $this->error(L('Complete KYC First!'));
        }
        if (M_ONLY == 1) {
            if (!check($cellphone_verify, 'd')) {
                $this->error(L('INVALID_SMS_CODE'));
            }

            if ($cellphone_verify != session('myzc_verify')) {
                $this->error(L('INCORRECT_SMS_CODE'));
            }
        }
        $num = format_num($num);

        if (!check($num, 'double')) {
            $this->error(L('Number format error!'));
        }
        if ($otp != session('requestOTP')) {
            $this->error('Incorrect OTP!');
        }

        if (!check($addr, 'dw')) {
            $this->error(L('Wallet address format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($coin, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coin]) {
            $this->error(L('Currency wrong!'));
        }
        $CoinInfo = C('coin')[$coin];


        if (!$CoinInfo) {
            $this->error(L('Currency wrong!'));
        }

        $myzc_min = ($CoinInfo['zc_min'] ? abs($CoinInfo['zc_min']) : 0.0001);
        $myzc_max = ($CoinInfo['zc_max'] ? abs($CoinInfo['zc_max']) : 10000000);

        if ($num < $myzc_min) {
            $this->error(L('Amount is less than Minimum Withdrawal Amount!'));
        }

        if ($myzc_max < $num) {
            $this->error(L('Amount Exceeds Maximum Withdrawal Limit!'));
        }

        $user = $this->userinfo;

        if (self::G2FA_REQUIRED_FOR_WITHDRAWAL == 1) {
            $is_ga = $user['ga'] ? 1 : 0;
            if ($is_ga == 1) {
                if (!$gacode || $gacode == 0) {
                    $this->error(L('You must enter 2FA Code'));
                }

                $arr = explode('|', $user['ga']);
                $secret = $arr[0];
                $ga = new GoogleAuthenticator();
                $ga_verification = $ga->verifyCode($secret, $gacode, 1);
                if (!$ga_verification) {
                    $this->error(L('Incorrect Google 2FA Entered'));
                }
            }
        }

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();

        if ($user_coin[$coin] < $num) {
            $this->error(L('Insufficient funds available'));
        }
        $zc_user = $CoinInfo['zc_user'];
        $qbdz = $coin . 'b';
        $fee_user['userid'] = M('User')->where(array('id' => $zc_user))->getField("id");
        if ($fee_user['userid'] == 0 || $fee_user['userid'] == null || $fee_user['userid'] < 0) {
            $fee_user['userid'] = 0;
        }

        $flat_fee = $CoinInfo['zc_flat_fee'];
        $percent_fee = bcmul(bcdiv($num, 100, 8), $CoinInfo['zc_fee'], 8);
        $fee = bcadd($flat_fee, $percent_fee, 8);
        if ($fee_user) {

            $mum = format_num($num - $fee, 8);

        } else {
            $mum = format_num($num - $fee, 8);
        }
        if ($fee < 0) {
            $this->error(L('Incorrect withdrawal fee!'));
        }
        if ($mum < 0) {
            $this->error(L('Incorrect withdrawal amount!'));
        }

        //tron Starts
        if ($CoinInfo['type'] == 'tron') {
            $heyue = $CoinInfo['dj_yh']; //Contract Address
            $tokenof = $CoinInfo['tokenof']; //Contract Address
            $mo = M();
            $tron = TronClient();
            $peer = $mo->table('codono_user_coin')->where(array($qbdz => $addr))->find();

            if ($peer) {

                $mo = M();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));


                if ($fee && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }
                $this->success('You have successfully raised the coins and will automatically transfer them out after the admin review!');
            } else {
                //blockchain Wallet Withdrawal
                $heyue = $CoinInfo['dj_yh'];//Contract Address
                $tokenof = $CoinInfo['tokenof'];//tokenof
                $dj_password = cryptString($CoinInfo['dj_mm'], 'd');
                $dj_address = $CoinInfo['dj_zj'];
                $dj_port = $CoinInfo['dj_dk'];
                $dj_decimal = $CoinInfo['cs_qk'];
                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
                $mo = M();
                $rs = array();
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                    }
                }


                if ($auto_status) {


                    $ContractAddress = $heyue;
                    $main_address = $CoinInfo['codono_coinaddress'];
                    $amount = (double)$mum;
                    $priv = cryptString($CoinInfo['dj_mm'], 'd');
                    $decimals = $CoinInfo['cs_qk'];
                    if (str_contains($ContractAddress, 'T')) {
                        //todo for trc20
                        $abi = $tron->getAbi($CoinInfo['name']);
                        $sendrs = $tron->transferTrc20($abi, $ContractAddress, $addr, $amount, $main_address, $priv, $decimals);
                    } else if ($ContractAddress) {

                        //todo for trc10 transfer
                        $sendrs = $tron->transferTrc10($addr, $amount, $ContractAddress, $main_address, $priv, $decimals);

                    } else {
                        $sendrs = $tron->sendTransaction($addr, $amount, $main_address, $priv);
                    }

                    if ($sendrs['result'] && $sendrs['txid'] && $aid) {

                        $hash = $sendrs['txid'];
                        M('Myzc')->where(array('id' => $aid))->save(array('txid' => $hash));
                        if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$aid' ");
                    }
                    $this->success('You have the success of the coin, background audit will automatically go out!' . $mum);
                }
                $this->success('You have successfully raised the coins and will automatically transfer them out after the background review!');

            }
        }
        //tron Ends

        //esmart Starts
        if ($CoinInfo['type'] == 'esmart') {
            $heyue = $CoinInfo['dj_yh']; //Contract Address
            $tokenof = $CoinInfo['tokenof']; //Contract Address
            $mo = M();

            $peer = $mo->table('codono_user_coin')->where(array($qbdz => $addr))->find();

            if ($peer) {

                $mo = M();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));


                if ($fee && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }
                $this->success('You have successfully raised the coins and will automatically transfer them out after the admin review!');
            } else {
                //emsart Wallet Withdrawal
                $heyue = $CoinInfo['dj_yh'];//Contract Address
                $tokenof = $CoinInfo['tokenof'];//tokenof
                $dj_password = cryptString($CoinInfo['dj_mm'], 'd');
                $dj_address = $CoinInfo['dj_zj'];
                $dj_port = $CoinInfo['dj_dk'];
                $dj_decimal = $CoinInfo['cs_qk'];
                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
                $mo = M();
                $rs = array();
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                    }
                }


                if ($auto_status) {


                    $ContractAddress = $heyue;
                    $esmart_config = array(
                        'host' => $CoinInfo['dj_zj'],
                        'port' => $CoinInfo['dj_dk'],
                        'coinbase' => $CoinInfo['codono_coinaddress'],
                        'password' => cryptString($CoinInfo['dj_mm'], 'd'),
                        'contract' => $CoinInfo['dj_yh'],
                        'rpc_type' => $CoinInfo['rpc_type'],
                        'public_rpc' => $CoinInfo['public_rpc'],
                    );
                    $Esmart = Esmart($esmart_config);

                    if ($heyue) {
                        //Contract Address transfer out
                        $zhuan['fromaddress'] = $CoinInfo['codono_coinaddress'];
                        $zhuan['toaddress'] = $addr;
                        $zhuan['token'] = $heyue;
                        $zhuan['type'] = $coin;
                        $zhuan['amount'] = (double)$mum;
                        $zhuan['password'] = $CoinInfo['dj_mm'];
                        $sendrs = $Esmart->transferToken($zhuan['toaddress'], $zhuan['amount'], $zhuan['token'], $dj_decimal);
                    } else {

                        $sendrs = $Esmart->transferFromCoinbase($addr, floatval($mum));
                    }

                    if ($sendrs && $aid) {
                        $arr = json_decode($sendrs, true);
                        $hash = $arr['result'] ?: $arr['error']['message'];
                        M('Myzc')->where(array('id' => $aid))->save(array('txid' => $hash));
                        if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$aid' ");
                    }
                    $this->success('You have the success of the coin, background audit will automatically go out!' . $mum);
                }
                $this->success('You have successfully raised the coins and will automatically transfer them out after the background review!');

            }
        }
        //esmart Ends


        //xrp starts
        if ($CoinInfo['type'] == 'xrp') {
            if ($dest_tag == 0) {
                $this->error('Make sure correct dest_tag is defined');
            }
            $mo = M();

            $peer = $mo->table('codono_user_coin')->where(array($qbdz => $addr, 'dest_tag' => $dest_tag))->find();

            if ($peer) {

                $mo = M();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));


                if ($fee && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }
                $this->success('You have successfully raised the coins and will automatically transfer them out after the admin review!');
            } else {
                // Wallet Withdrawal
                $xrpData = C('coin')['xrp'];
                $xrpClient = XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['codono_coinaddress'], $xrpData['dj_mm']);

                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);

                $mo = M();
                $rs = array();
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0));

                if ($fee && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                    }
                }


                if ($auto_status) {
                    $sendrs = false;
                    $sign = $xrpClient->sign($mum, $addr, $dest_tag);

                    if (strtolower($sign['result']['status']) == 'success') {
                        $submit = $xrpClient->submit($sign['result']['tx_blob']);
                        if (strtolower($submit['result']['status']) == 'success') {
                            $hash = $submit['result']['tx_json']['hash'];
                            M('Myzc')->where(array('id' => $aid))->save(array('txid' => $hash));
                            if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$aid' ");
                            $this->success('Successfully transferred out');
                        } else {
                            M()->execute("UPDATE `codono_myzc` SET  `status` =  0 WHERE id = '$aid' ");
                        }
                    } else {
                        $this->success('Your request is being processed!');
                    }

                }
                $this->success('Your request is being processed!');

            }
        }
        //xrp ends


        //rgb starts Offline type
        if ($CoinInfo['type'] == 'rgb') {

            $peer = M('UserCoin')->where(array($qbdz => $addr))->find();
            if (!$peer) {
                $this->error(L('Withdrawal Address does not exist!'));
            }

            $mo = M();

            $mo->startTrans();
            $rs = array();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

            if ($fee && $fee_user['userid'] != 0) {

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }

            $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
            $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

            if ($fee_user) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $zc_user, 'coinname' => $coin, 'txid' => $zcid, 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
            }

            if (check_arr($rs)) {
                $mo->commit();

                session('myzc_verify', null);
                $this->success(L('Transfer success!'));
            } else {
                $mo->rollback();
                $this->error('Transfer Failed!');
            }
        }

        /* Offline Coins Manual withdrawal */
        if ($CoinInfo['type'] == 'offline') {

            $mo = M();

            $mo->startTrans();
            $rs = array();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);

            $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0));


            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $zcid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }

            if (check_arr($rs)) {
                $mo->commit();

                session('myzc_verify', null);
                $this->success(L('Transfer success!'));
            } else {
                $mo->rollback();
                $this->error('Transfer Failed!');
            }
        }
        //Offline manual withdrawal ends
        //Coinpayments starts
        if ($CoinInfo['type'] == 'coinpay') {
            $mo = M();
            $coinpay_condition[$qbdz] = $addr;
            if ($dest_tag != NULL && $dest_tag != 0) {
                $coinpay_condition[$coin . '_tag'] = $dest_tag;
            }
            if ($mo->table('codono_user_coin')->where($coinpay_condition)->find()) {
                $peer = M('UserCoin')->where($coinpay_condition)->find();

                if (!$peer) {
                    $this->error(L('Withdraw address does not exist!'));
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);


                $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $zcid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                    }
                }


                if (check_arr($rs)) {
                    $mo->commit();

                    session('myzc_verify', null);
                    $this->success(L('Transfer success!'));
                } else {
                    $mo->rollback();
                    $this->error('Transfer Failed!');
                }
            } else {

                $dj_username = $CoinInfo['dj_yh'];
                $dj_password = $CoinInfo['dj_mm'];
                $dj_address = $CoinInfo['dj_zj'];
                $dj_port = $CoinInfo['dj_dk'];

                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
                $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                $information = $cps_api->GetBasicInfo();
                $coinpay_coin = strtoupper($coin);

                $can_withdraw = 1;
                if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                    //         $this->error(L('Wallet link failure! Coinpayments'));
                    clog($coin, '$coin Wallet link failure! Coinpayments ,can not be connetcted at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                //TODO :Find a valid way to validate coin address
                if (strlen($addr) > 8) {
                    $valid_res = 1;
                } else {
                    $valid_res = 0;
                }

                if (!$valid_res) {
                    $this->error($addr . L(' It is not a valid address wallet!'));
                }

                $balances = $cps_api->GetAllCoinBalances();

                if ($balances['result'][$coinpay_coin]['balancef'] < $num) {
                    //$this->error(L('Can not be withdrawn due to system'));
                    clog($coin, $coin . ' Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                //Reduce Withdrawers balance
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //Add entry of withdraw [zc] in database 
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                        clog('foundalready', array('res' => $r, 'lastsql' => $mo->table('codono_user_coin')->getLastSql()));
                    }
                }

                if (check_arr($rs)) {
                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();

                        $buyer_email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField('email');
                        $withdrawals = ['amount' => $mum,
                            'add_tx_fee' => 0,
                            'auto_confirm' => 1, //Auto confirm 1 or 0
                            'currency' => $coinpay_coin,
                            'address' => $addr,
                            //'dest_tag'=>$dest_tag,
                            'ipn_url' => SITE_URL . '/IPN/confirm',
                            'note' => $buyer_email];
                        if ($dest_tag != 0 && $dest_tag != NULL) {
                            $withdrawals['dest_tag'] = $dest_tag;
                        }

                        $the_withdrawal = $cps_api->CreateWithdrawal($withdrawals);


                        if ($the_withdrawal["error"] != "ok") {
                            $pending_status = M('Myzc')->where(array('id' => $aid))->save(array('status' => 0));
                            $the_status = false;
                            $this->error('Your withdrawal request is sent to admin,' . $the_withdrawal["error"]);

                        } else {
                            $the_status = true;
                            $cp_withdrawal_id = $the_withdrawal["result"]["id"];
                            M('Myzc')->where(array('id' => $aid))->save(array('hash' => $cp_withdrawal_id));
                            //$this->success('Successful Withdrawal!');
                        }
                    }

                    if ($auto_status && $the_status && $can_withdraw == 1) {
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Successful Withdrawal!');
                    } else {
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Being Reviewed!');
                    }
                } else {
                    $mo->rollback();
                    $this->error('Withdrawal failure!');
                }
            }
        }
        //Coinpayments ends
        //WavesPlatform Starts
        if ($CoinInfo['type'] == 'waves') {

            $mo = M();

            if ($mo->table('codono_user_coin')->where(array($qbdz => $addr))->find()) {
                $peer = M('UserCoin')->where(array($qbdz => $addr))->find();

                if (!$peer) {
                    $this->error(L('Withdraw address does not exist!'));
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                if ($fee && $fee_user['userid'] != 0) {
                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    }
                }

                $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee_user) {
                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $zc_user, 'coinname' => $coin, 'txid' => $zcid, 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->commit();

                    session('myzc_verify', null);
                    $this->success(L('Transfer success!'));
                } else {
                    $mo->rollback();
                    $this->error('Transfer Failed!');
                }
            } else {

                $dj_username = $CoinInfo['dj_yh'];
                $dj_password = $CoinInfo['dj_mm'];
                $dj_address = $CoinInfo['dj_zj'];
                $dj_port = $CoinInfo['dj_dk'];
                $dj_decimal = $CoinInfo['cs_qk'];
                $main_address = $CoinInfo['codono_coinaddress'];
                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
                $can_withdraw = 1;
                $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
                $waves_coin = strtoupper($coin);
                $information = json_decode($waves->status(), true);
                if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                    clog('waves_error', $coin . ' can not be connected at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                //TODO :Find a valid way to validate coin address
                if (strlen($addr) > 30) {
                    $valid_res = 1;
                } else {
                    $valid_res = 0;
                }

                if (!$valid_res) {
                    $this->error($addr . L(' It is not a valid address wallet!'));
                }

                $balances = json_decode($waves->Balance($main_address, $dj_username), true);
                $dj_decimal = $dj_decimal ?: 8;
                $wave_main_balance = $waves->deAmount($balances['balance'], $dj_decimal);
                if ($wave_main_balance < $num) {

                    clog('waves_error', $coin . ' main_address ' . $main_address . ' Balance is ' . $wave_main_balance . ' is' . $dj_decimal . ' lower than  ' . $num . ' at time:' . time() . ' ' . $dj_username . '<br/>');
                    $can_withdraw = 0;
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                //Reduce Withdrawers balance
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //Add entry of withdraw [zc] in database 
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }

                if (check_arr($rs)) {
                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();

                        $buyer_email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField('email');

                        $wavesend_response = $waves->Send($main_address, $addr, $mum, $dj_username);
                        $the_withdrawal = json_decode($wavesend_response, true);
                        if ($the_withdrawal["error"]) {
                            $the_status = false;
                            clog('waves_error', json_encode($the_withdrawal));
                            $pending_status = M('Myzc')->where(array('id' => $aid))->save(array('status' => 0));
                            $this->error('Your withdrawal request is sent to admin,' . $the_withdrawal["message"]);

                        } else {
                            $the_status = true;
                            M('Myzc')->where(array('id' => $aid))->save(array('txid' => $the_withdrawal['id'], 'hash' => $the_withdrawal['signature']));
                            //$this->success('Successful Withdrawal!');
                        }
                    }

                    if ($auto_status && $the_status && $can_withdraw == 1) {
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Successful Withdrawal!');
                    } else {
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Being Reviewed!' . $the_withdrawal["error"]);
                    }
                } else {
                    $mo->rollback();
                    $this->error('Withdrawal failure!');
                }
            }
        }
        //WavesPlatform Ends

        //BLOCKIO starts
        if ($CoinInfo['type'] == 'blockio') {
            $mo = M();

            if ($mo->table('codono_user_coin')->where(array($qbdz => $addr))->find()) {
                $peer = M('UserCoin')->where(array($qbdz => $addr))->find();

                if (!$peer) {
                    $this->error(L('Withdraw address does not exist!'));
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                if ($fee && $fee_user['userid'] != 0) {
                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    }
                }

                $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee_user) {
                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $zc_user, 'coinname' => $coin, 'txid' => $zcid, 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->commit();

                    session('myzc_verify', null);
                    $this->success(L('Transfer success!'));
                } else {
                    $mo->rollback();
                    $this->error('Transfer Failed!');
                }
            } else {

                $dj_username = $CoinInfo['dj_yh'];
                $dj_password = $CoinInfo['dj_mm'];
                $dj_address = $CoinInfo['dj_zj'];
                $dj_port = $CoinInfo['dj_dk'];

                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
                $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                $json = $block_io->get_balance();
                $can_withdraw = 1;
                if (!isset($json->status) || $json->status != 'success') {
                    //$this->error(L('Wallet link failure! blockio'));
                    clog('blockio', 'Blockio Could not be connected at ' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                $valid_res = $block_io->validateaddress($addr);

                if (!$valid_res) {
                    $this->error($addr . ' :' . L('Not valid address!'));
                }


                if ($json->data->available_balance < $num) {
                    //$this->error(L('Wallet balance of less than'));
                    clog('blockio', 'Blockio Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                //Reduce Withdrawers balance
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //Add entry of withdraw [zc] in database 
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }

                if (check_arr($rs)) {
                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();


                        $sendrs = $block_io->withdraw(array('amounts' => $mum, 'to_addresses' => $addr));
                        $flag = 0;
                        if ($sendrs) {
                            if (isset($sendrs->status) && ($sendrs->status == 'success')) {
                                $flag = 1;
                            }
                        } else {
                            $flag = 0;
                        }
                        if (!$flag) {
                            $this->error('wallet server  Withdraw currency failure,Manually turn out');
                        } else {
                            $this->success('Successful Withdrawal!');
                        }
                    }

                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Successful Withdrawal!');
                    } else {
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Application is successful Withdrawal,Please wait for the review!');
                    }
                } else {
                    $mo->rollback();
                    $this->error('Withdrawal failure!');
                }
            }
        }
        //BlockIO ends
        //cryptonote starts
        if ($CoinInfo['type'] == 'cryptonote') {
            $mo = M();

            if ($mo->table('codono_user_coin')->where(array($qbdz => $addr))->find()) {
                $peer = M('UserCoin')->where(array($qbdz => $addr))->find();

                if (!$peer) {
                    $this->error(L('Withdraw address does not exist!'));
                }
                $mo = M();

                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                if ($fee && $fee_user['userid'] != 0) {
                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    }
                }

                $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee_user) {
                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $zcid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->commit();

                    session('myzc_verify', null);
                    $this->success(L('Transfer success!'));
                } else {
                    $mo->rollback();
                    $this->error('Transfer Failed!');
                }
            } else {

                $dj_username = $CoinInfo['dj_yh'];
                $dj_password = $CoinInfo['dj_mm'];
                $dj_address = $CoinInfo['dj_zj'];
                $dj_port = $CoinInfo['dj_dk'];

                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
                $cryptonote = CryptoNote($dj_address, $dj_port);

                //check if withdrawal  addresss and payid are valid
                $valid_addr = $cryptonote->checkAddress($addr);
                $valid_payid = $cryptonote->checkPaymentId($dest_tag);
                if (!$valid_addr) {
                    $this->error('Please check if your address ' . $addr . ' is valid');
                }

                if (!$valid_payid) {
                    $this->error('Please check if your paymentId ' . $dest_tag . ' is valid');
                }

                $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

                $json = json_decode($cryptonote->get_height());
                $can_withdraw = 1;
                if (!isset($json->height) || $json->error != 0) {
                    clog('CryptoNote', $coin . ' Could not be connected at ' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                $bal_info = json_decode($cryptonote->getBalance());
                $crypto_balance = $cryptonote->deAmount($bal_info->available_balance);

                if ($crypto_balance < $num) {
                    clog('CryptoNote', $coin . ' Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                    $auto_status = 0;
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                //Reduce Withdrawers balance
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //Add entry of withdraw [zc] in database 
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }

                if (check_arr($rs)) {
                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();


                        $send_amt = format_num($num, 8);
                        $transData = [
                            [
                                "amount" => $send_amt,
                                "address" => $addr
                            ]
                        ];
                        $sendrs = json_decode($cryptonote->transfer($transData, $dest_tag));


                        $flag = 0;


                        clog($coin . '_cryptno_error', json_encode($sendrs));
                        if ($sendrs->error == 0) {
                            if (isset($sendrs->tx_hash) && isset($sendrs->tx_key)) {
                                $flag = 1;
                            }

                        } else {
                            $flag = 0;
                        }
                        if (!$flag) {
//                            $this->error('We have sent your withdrawal request to admin');
                            $can_withdraw = 0;

                        }
                    }
                    $hash = $sendrs->tx_key;
                    $txid = $sendrs->tx_hash;
                    if ($hash && $txid) {
                        M('Myzc')->where(array('id' => $aid))->save(array('txid' => $sendrs->tx_hash, 'hash' => $sendrs->tx_key, 'status' => 1));
                    }
                    if ($auto_status && $can_withdraw == 1) {

                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Successful Withdrawal');
                    } else {
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Application is successful Withdrawal,Please wait for the review!');
                    }
                } else {
                    $mo->rollback();
                    $this->error('Withdrawal failure!');
                }
            }
        }
        //CryptoNote Ends
        //Bitcoin Type Starts
        if ($CoinInfo['type'] == 'qbb') {
            $mo = M();

            if ($mo->table('codono_user_coin')->where(array($qbdz => $addr))->find()) {
                $peer = M('UserCoin')->where(array($qbdz => $addr))->find();

                if (!$peer) {
                    $this->error(L('Withdraw address does not exist!'));
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                if ($fee && $fee_user['userid'] != 0) {
                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    }
                }

                $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee_user) {
                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $zcid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->commit();

                    session('myzc_verify', null);
                    $this->success(L('Transfer success!'));
                } else {
                    $mo->rollback();
                    $this->error('Transfer Failed!');
                }
            } else {

                $dj_username = $CoinInfo['dj_yh'];
                $dj_password = $CoinInfo['dj_mm'];
                $dj_address = $CoinInfo['dj_zj'];
                $dj_port = $CoinInfo['dj_dk'];
                $dj_decimal = 8;
                $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                $can_withdraw = 1;

                $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);

                $json = $CoinClient->getinfo();

                if (!isset($json['version']) || !$json['version']) {

                    mlog($coin . ' Could not be connected at ' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                $valid_res = $CoinClient->validateaddress($addr);

                if (!$valid_res['isvalid']) {
                    $this->error($addr . L('It is not a valid address wallet!'));
                }
                $daemon_balance = $CoinClient->getbalance();

                if ($daemon_balance < $num) {
                    //$this->error(L('Wallet balance of less than'));
                    mlog($coin . ' :Low wallet balance: ' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                $mo = M();

                $mo->startTrans();
                $rs = array();
                //Reduce Withdrawers balance
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //Add entry of withdraw [zc] in database 
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $fee_user['userid'], 'invit' => userid(), 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }

                if (check_arr($rs)) {

                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();

                        if (str_contains($dj_address, 'new')) {
                            $send_amt = bcadd($mum, 0, 5);

                        } else {
                            $send_amt = (double)bcadd($mum, 0, 5);
                        }


                        $contract=$CoinInfo['contract'];    
                        if($contract){
                            $sendrs = $CoinClient->token('send',$contract,$addr, (double)$send_amt);     
                        }else{  
                            $sendrs = $CoinClient->sendtoaddress($addr, $send_amt);
                        }

                        if ($sendrs) {
                            $flag = 1;
                            $arr = json_decode($sendrs, true);

                            if (isset($arr['status']) && ($arr['status'] == 0)) {
                                $flag = 0;
                            }
                        } else {
                            $flag = 0;
                        }

                        if (!$flag) {
                            //$this->error('Wallet Server  Withdraw failure:'.$sendrs);
                            $mo->rollback();
                            $this->error('Wallet Server  Withdraw failure:' . ($sendrs));
                        } else {
                            M('Myzc')->where(array('id' => $aid))->save(array('txid' => $sendrs));
                            $this->success('Successful Withdrawal!');
                        }
                    }

                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();

                        session('myzc_verify', null);

                        $this->success('Successful Withdrawal!');
                    } else {
                        $pending_status = M('Myzc')->where(array('id' => $aid))->save(array('status' => 0));
                        $mo->commit();

                        session('myzc_verify', null);
                        $this->success('Withdrawal application is successful,Please wait for the review!');
                    }
                } else {
                    $mo->rollback();
                    $this->error('Withdrawal failure!');
                }
            }
        }
        //Bitcoin Type Ends
    }


    public function upmyzr($coin, $codono_dzbz, $num, $paypassword, $cellphone_verify = 0)
    {
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        if (M_ONLY == 1) {
            if (!check($cellphone_verify, 'd')) {
                $this->error(L('INVALID_SMS_CODE'));
            }

            if ($cellphone_verify != session('myzr_verify')) {
                $this->error(L('INCORRECT_SMS_CODE'));
            }
        }

        $num = abs($num);

        if (!check($num, 'currency')) {
            $this->error(L('Number format error!'));
        }


        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($coin, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coin]) {
            $this->error(L('Currency wrong!'));
        }

        $CoinInfo = C('coin')[$coin];//M('Coin')->where(array('name' => $coin))->find();

        if (!$CoinInfo) {
            $this->error(L('Currency wrong!'));
        }


        $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $codono_zrcoinaddress = $CoinInfo['codono_coinaddress'];

        if ($CoinInfo['type'] == 'offline') {

            M('myzr')->add(array('userid' => userid(), 'username' => $codono_dzbz, 'txid' => $codono_zrcoinaddress, 'coinname' => $coin, 'num' => $num, 'mum' => 0, 'addtime' => time(), 'status' => 0));

            $this->success(L('We have received your deposit request , It will be processes shortly!'));

        } else {
            $this->error("Wallet coins are not allowed to operate!");
        }

    }


    public function mywt($market = NULL, $type = NULL, $status = NULL)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        if (TRADING_ALLOWED == 0) {
            die('Unauthorized!');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mywt'));
        check_server();
        $Coin = C('Coin');

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = M('Market')->where(array('status' => 1))->select();

        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!isset($market_list[$market])) {
            $market = $Market[0]['name'];
        }

        $where['market'] = $market;

        if (($type == 1) || ($type == 2)) {
            $where['type'] = $type;
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $where['status'] = $status - 1;
        }

        $where['userid'] = userid();
        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('status', $status);
        $Model = M('Trade');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        //$Page->parameter .= 'type=' . $type . '&status=' . $status . '&market=' . $market . '&';
        $show = $Page->show();

        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = $v['num'] * 1;
            $list[$k]['price'] = $v['price'] * 1;
            $list[$k]['deal'] = $v['deal'] * 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function mycj($market = NULL, $type = NULL)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mycj'));
        check_server();
        $Coins = C('Coin');//M('Coin')->where(array('status' => 1))->select();

        foreach ($Coins as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = M('Market')->where(array('status' => 1))->select();

        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!$market_list[$market]) {
            $market = $Market[0]['name'];
        }

        if ($type == 1) {
            $where = 'userid=' . userid() . ' && market=\'' . $market . '\'';
        } else if ($type == 2) {
            $where = 'peerid=' . userid() . ' && market=\'' . $market . '\'';
        } else {
            $where = '((userid=' . userid() . ') || (peerid=' . userid() . ')) && market=\'' . $market . '\'';
        }

        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('userid', userid());
        $Model = M('TradeLog');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $Page->parameter .= 'type=' . $type . '&market=' . $market . '&';
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = $v['num'] * 1;
            $list[$k]['price'] = $v['price'] * 1;
            $list[$k]['mum'] = $v['mum'] * 1;
            $list[$k]['fee_buy'] = $v['fee_buy'] * 1;
            $list[$k]['fee_sell'] = $v['fee_sell'] * 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function mytj()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->title=D('Text')->get_title('finance_mytj');
        
        $uid=userid();    
        
        $this->assign('prompt_text', D('Text')->get_content('finance_mytj'));
        
        $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (!$user['invit']) {
            for (; true;) {
                $tradeno = tradenoa();

                if (!M('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }

            M('User')->where(array('id' => userid()))->save(array('invit' => $tradeno));
            $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();
        }
        
            $stats['invit_1'] = M('User')->where(array('invit_1' => $uid))->count();
            $stats['invit_2'] = M('User')->where(array('invit_2' => $uid))->count();
            $stats['invit_3'] = M('User')->where(array('invit_3' => $uid))->count();
            $stats['indirect']=$stats['invit_2']+$stats['invit_3'];
            $income_records = M('Invit')->field(array('sum(fee)' => 'total', 'coin'))->where($where)->group('coin')->order('total desc')->limit(10)->select();
            foreach($income_records as $income_record){
                $converted_list[]=$this->getConversion($income_record['coin'],$income_record['total'],SYSTEMCURRENCY);
            }
            $stats['earning']['total']=array_sum($converted_list);
            $stats['earning']['coin']=SYSTEMCURRENCY;
            $where['invit']=userid();
            $where['fee']=array('gt',0);
            $Model = M('Invit');
            $count = $Model->where($where)->count();
            $Page = new Page($count, 15);
            $show = $Page->show();
            $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            
        $this->assign('stats', $stats);    
        $this->assign('records', $list);
        $this->assign('user', $user);
        $this->display();
    }
    private function getConversion($fromcoin='USD',$amount=1,$tocoin='USD')
    {
		$mux=1; //Multiplier for conversion
		$fromcoin=strtoupper($fromcoin);
		$tocoin=strtoupper($tocoin);
		if($tocoin!='USD'){
			//Grab price from tocoin to USD

			$conv=$this->getConversion($tocoin);
			$mux=bcdiv(1,$conv,8);
			
		}
		else{
			$mux=1;
		}
        $price = M('Coinmarketcap')->where(array("symbol" => $fromcoin))->field('price_usd')->find();
		if ($price) {
			$price=$price['price_usd'];
        } else {
            $price = null;
        }
		
		$new_amount=bcmul($price ,$amount,8);
		$final=bcmul($mux ,$new_amount,8);
        return $final;
    }

    public function mywd()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mywd'));
        check_server();
        $where['invit_1'] = userid();
        $Model = M('User');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id asc')->field('id,username,idcardauth,addtime,invit_1')->limit($Page->firstRow . ',' . $Page->listRows)->select();


        foreach ($list as $k => $v) {
            $list[$k]['invits'] = M('User')->where(array('invit_1' => $v['id']))->order('id asc')->field('id,username,idcardauth,addtime,invit_1')->select();
            $list[$k]['invitss'] = count($list[$k]['invits']);

            foreach ($list[$k]['invits'] as $kk => $vv) {
                $list[$k]['invits'][$kk]['invits'] = M('User')->where(array('invit_1' => $vv['id']))->order('id asc')->field('id,username,idcardauth,addtime,invit_1')->select();
                $list[$k]['invits'][$kk]['invitss'] = count($list[$k]['invits'][$kk]['invits']);
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myjp()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myjp'));
        check_server();
        $where['userid'] = userid();
        $Model = M('Invit');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['invit'] = M('User')->where(array('id' => $v['invit']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myaward()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myaward'));
        //check_server();
        $where['userid'] = userid();
        $Model = M('UserAward');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function withdrawMobile()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        $this->assign('balance', $userCoin['ugx']);
        $this->display();
    }

    public function trace()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $Coins = C('coin');
        foreach ($Coins as $k => $v) {
            if (($v['type'] == 'esmart' || $v['type'] == 'blockgum' ) && $v['dj_yh'] == '') {
                $coin_list[$v['name']] = ['type'=>$v['type'],'name'=>$v['name'],'title'=>$v['title'],'network'=>$v['js_yw']];
            }
        }
        
        
        ksort($coin_list);
        $this->assign('coin_list', $coin_list);
        $this->display();
    }
    function trackTx($txid, $chain)
    {

        if (!preg_match('/^(0x)?[\da-f]{64}$/i', $txid)) {
            $this->error("Invalid txid");
        }

        $isValidCoin = $this->isValidCoin($chain);
        if ($chain == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }

        $amount = 0;
        $token_address_found = 0;
        $all_coinsList = C('coin');
        $tokens_list = $coinList = array();
        if(C('coin')[$chain]['type']=='blockgum'){
            $blockgum=blockgum($chain);
            $history=$blockgum->traceDeposit($txid);
            if($history && $history['status']==1){
                $this->success("Your request for trace has been added, Server Resp:".$history['message']);
            }else{
                $this->success("Auto trace would not detect such deposit, Please contact support");
            }
            
        }
        foreach ($all_coinsList as $coinex) {
            if ($coinex['type'] == 'esmart' && $coinex['status'] == 1) {
                $coinList[] = $coinex;
                if ($coinex['tokenof'] == $chain && $coinex['dj_yh'] != '') {
                    $tokens_list[] = strtolower($coinex['dj_yh']);
                }

            }
        }


        $coin_esmart = C('coin')[$chain];

        $esmart_details = C('coin')[$coin_esmart['name']];

        $block = M()->query("SELECT * FROM `codono_coin` WHERE `name`='" . $coin_esmart['name'] . "'");

        $esmart_details = $block[0];

        $pcoin = $esmart_details['tokenof'] ?: $esmart_details['name'];

        $pcoinb = $pcoin . 'b';

        // Saving Userid's and their addresses  $pcoinb_array

        $coin_Query = "SELECT count(" . $pcoinb . ") as count FROM `codono_user_coin` WHERE `" . $pcoinb . "` is not null";
        $pcoinb_count = M()->query($coin_Query)[0]['count'];
        $coin_Query = "SELECT " . $pcoinb . " as addr , userid  FROM `codono_user_coin` WHERE `" . $pcoinb . "` is not null";
        $pcoinb_array = M()->query($coin_Query);


        $main_account = $esmart_details['codono_coinaddress'];

        $esmart_config = array(
            'host' => $esmart_details['dj_zj'],
            'port' => $esmart_details['dj_dk'],
            'coinbase' => $esmart_details['codono_coinaddress'],
            'password' => cryptString($esmart_details['dj_mm'], 'd'),
            'contract' => $esmart_details['dj_yh'],
            'rpc_type' => $esmart_details['rpc_type'],
            'public_rpc' => $esmart_details['public_rpc'],
        );
        $Esmart = Esmart($esmart_config);


        $decimals = $esmart_details['cs_qk'];


        $time_start = microtime(true);
        $infoTx = $Esmart->eth_getTransactionByHash($txid);

        $listtransactions = array($infoTx);

        foreach ($listtransactions as $ks => $trans) {

            if (isset($trans['from']) && strtolower($trans['from']) == strtolower($main_account)) {
                $this->error("This was a gas transfer from our exchange account only");
                continue;
            }

            if (!isset($trans['to'])) {
                $this->error("No payee");
                continue;//No payee.
            }

            if ($trans["value"] == '0x0' || $trans["value"] == '0x' || $trans["value"] == '0') {
                if (!$trans['input']) {
                    $this->error("Invalid tx");
                    continue;
                }

                //Find if its a token if input != 0x then its a token


                if ($trans['input'] != '0x') {
                    $func = '0x' . substr($trans['input'], 2, 8);
                    if (!in_array(strtolower($trans['to']), $tokens_list) && $func != '0x2228f3a4') {
                        continue;
                    }

                    $to_num = substr($trans['input'], 74);//Quantity
                    $tos = substr($trans['input'], 34, 40);//Reciever

                    if (!$tos) {
                        $this->error("No such sender");
                        continue;
                    }

                    $tos = "0x" . $tos;


                    $num = $Esmart->fromWei($to_num);

                } else {
                    //This is for ethereum it self
                    $tos = $trans['to'];
                    $num = $trans["value"];

                }
                if ($func == '0x2228f3a4') {
                    $batch_decodes = $Esmart->payoutERC20Batch_decode($trans['input']);

                    foreach ($batch_decodes as $batch_decode) {
                        //check if erc20 contract is listed on exchange or not
                        if (!in_array(strtolower($batch_decode['contract']), $tokens_list)) {
                            continue;
                        }
                        $batch_address = $batch_decode['address'];
                        $coin_Query = "SELECT userid FROM `codono_user_coin` WHERE `" . $pcoinb . "` LIKE '" . $batch_address . "'";
                        $users = M()->query($coin_Query);
                        if (!$users) continue;
                        $batch_uid = $users[0]['userid'];
                        $batch_contract_info = $this->findTokenByContract($batch_decode['contract']);
                        $batch_decimal = $batch_contract_info['cs_qk'];
                        $batch_coin = $batch_contract_info['name'];
                        $batch_amount = hexdec($batch_decode['bal_hex']) / bcpow(10, $batch_decimal);
                        //Already recorded
                        if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {
                            echo $batch_address . '=>' . $batch_amount . '=>tx for ' . $batch_coin . ' already credited Checking Next' . "<br>";
                            continue;
                        }
                        $data = ['userid' => $batch_uid, 'amount' => $batch_amount, 'coin' => $batch_coin, 'address' => $batch_address, 'hash' => $trans['hash']];
                        $b_info = D('Coin')->depositCoin($data);                     
                    }
                    continue;
                }
                if (count($listtransactions) < $pcoinb_count) {

                    $coin_Query = "SELECT userid as userid FROM `codono_user_coin` WHERE `" . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) {
                        $this->error("No such user");
                        continue;
                    }

                    $user = $users[0];
                } else {

                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        if (isset($pcoinb_user['addr']) && $pcoinb_user['addr'] == $tos) {
                            $coin_Query = "SELECT userid as userid FROM `codono_user_coin` WHERE `" . $pcoinb . "` LIKE '" . $tos . "'";
                            $users = M()->query($coin_Query);

                            if (!$users) {
                                $this->error("No such user [2]");
                                continue;
                            }

                            $user = $users[0];

                            //$user=$pcoinb_user['userid'];


                        } else {
                            $this->error("We could not find it ");
                            continue;
                        }
                    }
                    if (!isset($user['userid'])) {
                        $this->error("no such user found ");
                        continue;
                    }
                }

                $hash_result = $Esmart->eth_getTransactionReceipt($trans['hash']);

                if ($hash_result['status'] != '0x1' && strtolower($hash_result['transactionHash']) != strtolower($trans['hash'])) {

                    $this->error($trans['hash'] . " tx was failed or can not confirm it - Skipping it");
                    continue;
                }

                $func = '0x' . substr($trans['input'], 2, 8);

                $flag = false;
                if ($func == "0xa9059cbb") {
                    $token_address_found = $trans['to'];
                    $from = $trans['from'];
                    $to = '0x' . substr(substr($trans['input'], 10, 64), -40);

                    $coin_Query = "SELECT name,cs_qk FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $token_address_found . "%'";

                    $coin_info = M()->query($coin_Query);
                    $decimals = $coin_info[0]['cs_qk'];
                    $amount = hexdec(substr($trans['input'], 74, 64)) / bcpow(10, $decimals);
                    $flag = true;

                } else if ($func == "0x23b872dd") {
                    $token_address_found = $trans['to'];
                    $from = '0x' . substr(substr($trans['input'], 10, 64), -40);
                    $to = '0x' . substr(substr($trans['input'], 74, 64), -40);
                    $amount = hexdec(substr($trans['input'], 138, 64));

                    $flag = true;

                }

                if ($flag) {
                    $coin_Query = "SELECT name,cs_qk FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $token_address_found . "%'";
                    $coin_info = M()->query($coin_Query);
                    $coin = $coin_info[0]['name'];
                }
                if ($trans['input'] != '0x' && $coin == $pcoin) {
                    $this->error("Invalid tx [4] ");
                    continue;
                }

                if ($trans['input'] == '0x' && $coin != $pcoin) {
                    $this->error($pcoin . "Invalid tx [3]" . $coin);
                    continue;
                }

                if ($trans['input'] != '0x' && $coin != $pcoin) {
                    $contract_Address_to_look = $trans['to'];
                    if ($token_address_found != $contract_Address_to_look) {
                        continue;
                    }
                    $token_query = "SELECT name,cs_qk as decimals FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $contract_Address_to_look . "%'";

                    $resulto = M()->query($token_query);

                    if (!$resulto[0]['name']) {
                        $this->error('This token deposited is not registered on exchange');
                        continue;
                    }

                    $coin = $resulto[0]['name'];

                    $num = $amount;

                }
                if ($num <= 0 && $coin == $pcoin) {
                    $num = $Esmart->fromWei($trans['val']);
                }

                if ($num <= 0) continue;


                if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {
                    //Already recorded
                    $this->error("Transaction was already deposited!");
                    continue;
                }

                $mo = M();
                $mo->startTrans();

                $num = format_num($num, 8);
                $coin = $this->findSymbol($coin);

                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $user['userid'], 'type' => 'esmart', 'username' => $tos, 'coinname' => $coin, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $num, 'mum' => $num, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $num);

                if (check_arr($rs)) {
                    $mo->commit();
                    //$this->deposit_notify($user['userid'], $tos, $coin, $trans['hash'], $num, time());

                    $this->success("Transaction was found and deposited");
                } else {
                    $mo->rollback();
                    $this->error("Deposit could not be processed");
                }


            } else {

                $tos = $trans['to'];
                if (count($listtransactions) < $pcoinb_count) {


                    $coin_Query = "SELECT userid as userid FROM `codono_user_coin` WHERE `" . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) continue;

                    $user = $users[0];
                } else {
                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        if ($pcoinb_user['addr'] == $tos) {
                            //$user=$pcoinb_user;

                            $coin_Query = "SELECT userid as userid FROM `codono_user_coin` WHERE `" . $pcoinb . "` LIKE '" . $tos . "'";
                            $users = M()->query($coin_Query);

                            if (!$users) continue;

                            $user = $users[0];
                        } else {
                            continue;
                        }
                    }

                    if (!isset($user)) continue;
                }


                //esmart
                //$user = M('UserCoin')->where(array($pcoinb => $trans['to']))->find();
                if (!$user['userid']) continue;
                if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {

                    $this->error("Transaction was already deposited!");
                    continue;
                }
                $addbalance = $Esmart->fromWei($trans["value"]);

                M('myzr')->add(array('userid' => $user['userid'], 'username' => $trans['to'], 'coinname' => $pcoin, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $addbalance, 'mum' => $addbalance, 'addtime' => time(), 'status' => 1, 'type' => 'esmart'));
                $rs[] = M()->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($pcoin, $addbalance);
                deposit_notify($user['userid'], $tos, $pcoin, $trans['hash'], $addbalance, time());
                $this->success("Transaction was found and deposited");
            }

        }
    }
    public function doTrace($txid, $chain)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->trackTx($txid,$chain);
    }

    public function findSymbol($coin): ?string
    {
        $coin = strtolower($coin);
        $coininfo = C('coin')[$coin];
        if (!is_Array($coininfo)) {
            return null;
        }
        $symbol = strtolower($coininfo['symbol']);

        if ($symbol == null) {
            return $coin;
        } else {
            return $symbol;
        }
    }

    private function isValidCoin($coin): bool
    {
        $coins = C('coin_safe');

        if (array_key_exists(strtolower($coin), $coins)) {
            return true;
        } else {
            return false;
        }
    }
}

