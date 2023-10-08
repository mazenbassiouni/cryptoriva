<?php

namespace Home\Controller;

use Think\Page;

class P2pController extends HomeController
{
    const allow_special = 1;//  open C2C expedited
    const is_init_trade = 0; //enable asynchronous transactions
    const allowed_cryptos = array('USDT', 'BTC', 'ETH', 'BNB');  // all caps Coins to be buy and sold
    const require_coin = "USDT";  // caps
    const require_balance = 1000;
    const release_time = array('15', '30', '60', '120', '240', '360', '720');
	const article_category = "faq";  // category to show articles from

    //Set transaction currency price
	
    public function _initialize()
    {
        if (P2P_ALLOWED == 0) {
            $this->assign('type', 'Oops');
            $this->assign('error', 'Oops, Currently P2P is disabled!');
            //$this->display('Content/error');
            $this->error('P2P is currently disabled');
            exit();
        }
        //Judgment to log in, you cannot directly access the database without logging in
        parent::_initialize();

    }

    public function index()
    {
		$uid=userid();
        $type = I('request.type', 'buy', 'string'); //buy/sell
        $fiat = I('request.fiat', 'USD', 'string');
        $coin = I('request.coin', 'USDT', 'string');
		$pm = I('request.pm', 1, 'int'); //payment method
		
		$fiat_qty = I('request.fiat_qty', 0.00, 'float');
		$coin_qty = I('request.coin_qty', 0.00, 'float');
		
		if($coin_qty >0 || $fiat_qty>0){
			
			if($coin_qty>0 && $fiat_qty==0){
				$where['available']=array('egt',$coin_qty);
			}else{
				$where['min_limit']=array('elt',$fiat_qty);
				$where['max_limit']=array('egt',$fiat_qty);
			}
		}
		
        
        $order = 'id desc';
        if ($type != 'buy' && $type != 'sell') {
            redirect(U('P2p/index', array("type" => 'buy')));
        }
        if ($type == 'buy') {
            $where['ad_type'] = 2;
            $order = 'fixed_price asc';
        }
        if ($type == 'sell') {
            $where['ad_type'] = 1;
            $order = 'fixed_price desc';
        }
        $userBal = M('UserAssets')->where(array('uid' => $uid, 'coin' => strtolower($coin)))->find();
        $UserBanks = M('UserBank')->where(array('userid' => $uid, 'status' => 1))->order('id desc')->select();

        $userBal['balance'] = $userBal['balance'] ?: 0;
        $where['fiat'] = $fiat;
        $where['coin'] = $coin;
        $where['status'] = 1;

        $Model = M('P2pAds');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $ads = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->order($order)->select();
        $FiatMethods = $this->getP2pPaymentMethods();
        $ads_data = array();
        foreach ($ads as $ad) {
            if ($ad['available'] < $ad['min_limit']) {
                $Model->where(array('id' => $ad['id']))->save(array('online' => 0));
                unset($ad);
                continue;
            }
            $ads_data[$ad['id']] = $ad;
            $ads_data[$ad['id']]['orders'] = 546;
            $ads_data[$ad['id']]['completion'] = 85;
            $ads_data[$ad['id']]['method'] = array();
            foreach ($FiatMethods as $key => $method) {
                foreach (json_decode($ad['ad_methods'], true) as $meth) {
                    if ($meth == $key) {
                        $ads_data[$ad['id']]['method'][] = $method['name'];
                    }
                }
            }
        }
        $FiatList = $this->FiatList();
        $coin_imgs = array();
        foreach (C('coin') as $coin) {
            $coin_imgs[strtoupper($coin['name'])] = $coin['img'];
        }
        //Allowed crypto coins
        $allow_cryptos = self::allowed_cryptos;
        $this->assign('user_banks', $UserBanks);
        $this->assign('user_balance', $userBal['balance']);
        $this->assign('allow_cryptos', $allow_cryptos);
        $this->assign('FiatMethods', $FiatMethods);
        $this->assign('FiatList', $FiatList);
        $this->assign('coinimgs', $coin_imgs);
        $this->assign('ads', $ads_data);
        $this->assign('page', $show);
        $this->display();
    }

    private function FiatList()
    {
        $data = (APP_DEBUG ? null : S('FiatList'));

        if (!$data) {

            $data = M('Coin')->where(array('status' => 1, 'type' => 'rmb'))->field('name,title,img')->select();
            S('FiatList', $data);
        }
        return $data;
    }

    public function sell()
    {
        $this->display();
    }

    public function vieworder()
    {
        $uid = userid();
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $id = I('id/d');
        $order = M('P2pOrders')->where(array('id' => $id, 'userid' => $uid))->find();

        if (!$order) {
            redirect(U("P2p/orders"));
        }

        $ad_info = $this->orderinfo($order, 'orders');

        $payinfo = $ad_info['payinfo'];

        $remain_time = $this->remainingTime($order['endtime']);
        $times_up = $this->iftimesUp($order['endtime']);

        $order = $this->doRefundCancel($order, $times_up);

        $this->assign('times_up', $times_up);
        $this->assign('remain_time', $remain_time);
        $this->assign('ad_info', $ad_info);
        $this->assign('order', $order);
        $this->assign('payinfo', $payinfo);
        $this->assign('truename', $ad_info['seller']);
        $this->display();
    }

    public function receivedorder()
    {
        $uid = userid();
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $id = I('id/d');
        $order = M('P2pOrders')->where(array('id' => $id, 'peerid' => $uid))->find();

        if (!$order) {
            redirect(U("P2p/received"));
        }
        $ad_info = $this->orderinfo($order, 'orders');


        $payinfo = $ad_info['payinfo'];

        $remain_time = $this->remainingTime($order['endtime']);
        $times_up = $this->iftimesUp($order['endtime']);
        $order = $this->doRefundCancel($order, $times_up);

        $this->assign('times_up', $times_up);
        $this->assign('remain_time', $remain_time);
        $this->assign('ad_info', $ad_info);
        $this->assign('order', $order);
        $this->assign('payinfo', $payinfo);
        $this->assign('truename', $ad_info['seller']);
        $this->display('P2p/vieworder');
    }

    private function doRefundCancel($order, $times_up)
    {
        $orderid = $order['id'];
        $status = $order['status'];
        if ($times_up && $status == 0) {
			
            $order_info = M('P2pOrders')->where(array('id' => $orderid, 'status' => 0))->find();
            $ad_info=$this->orderinfo($order_info);
            $buyer=$ad_info['buyer'];
			$seller=$ad_info['seller'];
			$merchant=$ad_info['seller'];
			$coin = strtolower($order_info['coin']);
            $total = $order_info['coin_qty'];
			$ad_id= $order_info['ad_id'];
            $peerid=$order_info['peerid'];
            //do refund
            if (!empty($order_info)) {
				$rs = array();
				$mo = M();
                $mo->startTrans();
				if($seller==$merchant){
					//refund to p2p_ads, move freeze to balance for coin
						$condition = array('id' => $ad_id, 'coin' => $coin);
						$found = $mo->table('codono_p2p_ads')->where($condition)->find();
						if($found) {
							$rs[] = $mo->table('codono_p2p_ads')->where($condition)->setInc('available', $total);
							$rs[] = $mo->table('codono_p2p_ads')->where($condition)->setDec('freeze', $total);
						}
					
					
				}else{
					//seller is not merchant then refund to user_assets , move freeze to available for coin
						$condition = array('uid' => $seller, 'coin' => $coin);
						$found = $mo->table('codono_user_assets')->where($condition)->find();
					if(!$found) {
						$rs[]=$mo->table('codono_user_assets')->add($condition);
						$rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $total);
					} else {
						$rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $total);
						$rs[] = $mo->table('codono_user_assets')->where($condition)->setDec('freeze', $total);
					}
					
				}
				
                

                $up_where['id'] = $orderid;
                $up_where['coin'] = $coin;
                $up_where['status'] = 0;

                $request = array('status' => 2);
                $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);
                if (check_arr($rs)) {
                    $mo->commit();
                    
                    $subject = "Timeout:P2p order has been cancelled";

                    $message = "Due to timeout order " . $order_info['orderid'] . " has been cancelled ,any funds frozen will be refunded back";
                    $this->notify($peerid, $subject, $message);
                    $chat_array = array('orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time());
                    $mo->table('codono_p2p_chat')->where(array('orderid' => $orderid))->add($chat_array);
                    $order = $mo->table('codono_p2p_orders')->where(array('id' => $orderid))->find();
                } else {
                    $mo->rollback();
                    

                }

            }
            //change status to 2
        }
        return $order;
    }

    private function remainingTime($endtime): string
    {
        $now = time();
        $remain_time = bcsub($endtime, $now, 0);
        if ($remain_time <= 0) {
            $remain_time = 0;
        }

        $t = round($remain_time);
        return sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
    }

    private function iftimesUp($endtime): int
    {
        $now = time();
        $remain_time = bcsub($endtime, $now, 0);
        if ($remain_time <= 0) {
            $times_up = 1;
        } else {
            $times_up = 0;
        }
        return $times_up;

    }

    public function doTrade()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $total=0;
        $request["paywith"] = $paywith = I('request.paywith', 0.00, 'float');
        $request["amount"] = $amount = I('request.amount', 0.00, 'float');
        $request["type"] = $order_type = I('type/d');
        $request["id"] = $id = I('id/d');
        $request["paymethod"] = $paymethod = I('paymethod/d');

        if ($request["type"] != 1 && $request["type"] != 2) {
            $this->error(L("No such listing found"));
        }

        //ones buying is selling for other
        if ($request['type'] == 1) {
            $ad_type = 2;
        } else {
            $ad_type = 1;
        }

        $mo = M();
        //find info about ad
        $listing = $mo->table('codono_p2p_ads')->where(array('id' => $request['id'], 'status' => 1, 'online' => 1, 'ad_type' => $ad_type))->find();
        
        if (!$listing) {
            $this->error(L("No such listing found325"));
        }
        if ($listing['uid'] == $uid) {
            $this->error(L("You can not trade with own ad"));
        }
        $coin = $listing['coin'];
        //$fiat = $listing['fiat'];
        //@todo find user balance in crypto
        $balance_info = $this->userOtherbalances($uid, 1);

        // if type ==2
        //this ad is sell type
        if ($order_type == 2) {
            if (!$paymethod) {
                $this->error(L("Please select correct Payment Method"));
            }
            $my_pay_method = M('UserBank')->where(['userid' => $uid, 'status' => 1, 'id' => $paymethod])->find();
            if (!$my_pay_method || $my_pay_method['id'] != $paymethod) {
                $this->error(L("Please select correct Payment Method"));
            }
            $fiat_amount = 0;
            //calculate total_required
            if ($paywith > 0 && $amount == 0) {
                $fiat_amount = $paywith;
                $total = bcdiv((float)$paywith, (float)$listing['fixed_price'], 8);
                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error("Keep Fiat amount between:" . NumToStr($listing['min_limit']) . ' and ' . NumToStr($listing['max_limit']));
                }
            }

            if ($paywith == 0 && $amount > 0) {
                $fiat_amount = bcmul((float)$listing['fixed_price'], (float)$amount, 8);
                $min_crypto = bcdiv((float)$listing['min_limit'], (float)$listing['fixed_price'], 8);
                $max_crypto = bcdiv((float)$listing['max_limit'], (float)$listing['fixed_price'], 8);

                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error("Crypto Limit Between:" . NumToStr($min_crypto) . ' and ' . NumToStr($max_crypto));
                }
                $total = $amount;
            }
            $avail_amount = strtoupper($listing['fiat']) . ' ' . NumToStr($listing['available']);
            if ($fiat_amount > $listing['available']) {
                $this->error("Only available " . $avail_amount);
            }

            if ($total <= 0) {
                $this->error(L("Enter amount properly"));
            }


            $my_p2p_bal = $balance_info[strtolower($coin)]['balance'];
            if ($my_p2p_bal < $total) {
                $this->error(L('Not Enough balance available') . ':' . $my_p2p_bal);
            }
            //@todo see if this user meets requirements
            if ($listing['cond_kyc'] == 1 && $this->userinfo['idcardauth'] == 0) {
                $this->error(L('Please complete KYC!'));
            }

            if ($listing['cond_reg'] == 1 && $listing['cond_reg_ago'] > 0) {
                $seconds = $listing['cond_reg_ago'] * 86400;
                $check_before = bcadd(time(), $seconds, 0);
                if ($this->userinfo['addtime'] > $check_before) {
                    $this->error("You need do not meet minimum days registration criteria");
                }
            }
            //@todo see if total = price * crypto amount and check if user has enough coin [Fiat]
            if ($listing['cond_balance'] == 1 && $listing['cond_min_bal'] > 0) {
                $req_p2p_bal = $balance_info[strtolower(self::require_coin)]['balance'];
                $req_user_balance = M('UserCoin')->where(['userid' => $uid])->getField(strtolower(self::require_coin));

                if ($listing['cond_min_bal'] > $req_p2p_bal && $listing['cond_min_bal'] > $req_user_balance) {
                    $this->error("Minimum balance criteria:" . NumToStr($listing['cond_min_bal']) . ' ' . self::require_coin);
                }
            }

			
            //@todo add merchant order count by 1 
            //Need to code  later if there is such payment method
            $lc_coin = strtolower($coin);
            $rs = array();
            $mo->startTrans();
            //$before_balance = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $lc_coin))->find();

            //move p2p_ads entry 'available' to 'freeze'
            $rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $id))->setDec('available', $total);
            $rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $id))->setInc('freeze', $total);

            //put my coin 'total' to 'freeze'
            $rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $lc_coin))->setDec('balance', $total);
            $rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $lc_coin))->setInc('freeze', $total);
            $seconds = $listing['time_limit'] * 60;
            $end_time = time() + $seconds;
            //do p2p_order entry
            $p2p_order = array(
                'name' => $listing['name'],
                'orderid' => strtoupper(dechex(cardGenPublic($uid . $listing['id']))),//$listing['orderid'],
                'coin' => $lc_coin,
                'fixed_price' => $listing['fixed_price'],
                'coin_qty' => $total,
                'fiat' => $listing['fiat'],
                'fiat_qty' => $fiat_amount,
                'ad_id' => $listing['id'],
                'ad_type' => $order_type,
                'userid' => $uid,
                'merchant_id' => $listing['uid'],
                'payment_info' => json_encode(array($my_pay_method)),
                'time_limit' => $listing['time_limit'],
                'addtime' => time(),
                'endtime' => $end_time,
                'status' => 0
            );


            //do finance entry


            //if success notify owner of ad


        } else {
            //user is buyer and ordering on sell order
            $listing_pay_methods = json_decode($listing['pay_methods'], true);

            $search = implode(',', $listing_pay_methods);
            $peerid = $listing['merchant_id'];

            $my_pay_method = M('UserBank')->where(array('userid' => $peerid))->select($search);


            if (empty($my_pay_method)) {
                $this->error(L('There were issues placing order!') . ':2');
            }
            $fiat_amount = 0;
            //calculate total_required
            if ($paywith > 0 && $amount == 0) {
                $fiat_amount = $paywith;
                $total = bcdiv((float)$paywith, (float)$listing['fixed_price'], 8);
                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error("Keep Fiat amount between:" . NumToStr($listing['min_limit']) . ' and ' . NumToStr($listing['max_limit']));
                }
            }

            if ($paywith == 0 && $amount > 0) {
                $fiat_amount = bcmul((float)$listing['fixed_price'], (float)$amount, 8);
                $min_crypto = bcdiv((float)$listing['min_limit'], (float)$listing['fixed_price'], 8);
                $max_crypto = bcdiv((float)$listing['max_limit'], (float)$listing['fixed_price'], 8);

                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error("Crypto Limit Between:" . NumToStr($min_crypto) . ' and ' . NumToStr($max_crypto));
                }
                $total = $amount;
            }
            $avail_amount = strtoupper($listing['fiat']) . ' ' . NumToStr($listing['available']);
            if ($fiat_amount > $listing['available']) {
                $this->error("Only available " . $avail_amount);
            }
            if ($total <= 0) {
                $this->error(L("Enter amount properly"));
            }

            //@todo see if this user meets requirements
            if ($listing['cond_kyc'] == 1 && $this->userinfo['idcardauth'] == 0) {
                $this->error(L('Please complete KYC!'));
            }

            if ($listing['cond_reg'] == 1 && $listing['cond_reg_ago'] > 0) {
                $seconds = $listing['cond_reg_ago'] * 86400;
                $check_before = bcadd(time(), $seconds, 0);
                if ($this->userinfo['addtime'] > $check_before) {
                    $this->error("You need do not meet minimum days registeration criteria");
                }
            }
            //@todo see if total = price * crypto amount and check if user has enough coin [Fiat]
            if ($listing['cond_balance'] == 1 && $listing['cond_min_bal'] > 0) {
                $req_p2p_bal = $balance_info[strtolower(self::require_coin)]['balance'];
                $req_user_balance = M('UserCoin')->where(['userid' => $uid])->getField(strtolower(self::require_coin));

                if ($listing['cond_min_bal'] > $req_p2p_bal && $listing['cond_min_bal'] > $req_user_balance) {
                    $this->error("Minimum balance criteria:" . NumToStr($listing['cond_min_bal']) . ' ' . self::require_coin);
                }
            }


            //@todo check payment method
            //Need to code  later if there is such payment method
            $lc_coin = strtolower($coin);
            $rs = array();
            $mo->startTrans();

            //move p2p_ads entry 'available' to 'freeze'
            $rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $id))->setDec('available', $total);
            $rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $id))->setInc('freeze', $total);

            $seconds = $listing['time_limit'] * 60;
            $end_time = time() + $seconds;
            //do p2p_order entry
            $p2p_order = array(
                'name' => $listing['name'],
                'orderid' => strtoupper(dechex(cardGenPublic($uid . $listing['id']))),//$listing['orderid'],
                'coin' => $lc_coin,
                'fixed_price' => $listing['fixed_price'],
                'coin_qty' => $total,
                'fiat' => $listing['fiat'],
                'fiat_qty' => $fiat_amount,
                'ad_id' => $listing['id'],
                'ad_type' => $order_type,
                'userid' => $uid,
                'merchant_id' => $listing['uid'],
                'payment_info' => json_encode($my_pay_method),
                'time_limit' => $listing['time_limit'],
                'addtime' => time(),
                'endtime' => $end_time,
                'status' => 0
            );


            //@todo if success notify owner of ad


        }
        $rs[] = $p2p_adid = $mo->table('codono_p2p_orders')->add($p2p_order);
        if (check_arr($rs)) {
            $mo->commit();
            
            //push auto reply to p2p_chat
            if ($listing['autoreply']) {
                $add_array = array('orderid' => $p2p_adid, 'content' => $listing['autoreply'], 'userid' => $listing['uid'], 'addtime' => time());
                $mo->table('codono_p2p_chat')->where(array('orderid' => $p2p_adid))->add($add_array);
            }
            $this->success(L('Order placed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues placing order!'));
        }
        //sell logic ends

    }

    /*
    order is array information of order from p2p_orders table
    $types=orders, received
    */

    private function orderinfo($order, $type = 'orders')
    {

        if (!$order) {
            return false;
        }
        $uid = userid();
        $user = $this->userinfo;
        $ad_info = $order;
        $ad_info['myid'] = $uid;


        if ($order['userid'] == $uid) {
            $ad_info['is_merchant'] = 0;
            $ad_info['peerid'] = $order['merchant_id'];
            $ad_info['peername'] = username($ad_info['peerid']);

        } elseif ($order['merchant_id'] == $uid) {
            $ad_info['is_merchant'] = 1;
            $ad_info['peerid'] = $order['userid'];
            $ad_info['peername'] = username($ad_info['peerid']);
        } else {
            $this->error(L("No such listing found"));
        }

        if ($order['ad_type'] == 2) {
            if ($ad_info['is_merchant']) {
                $ad_info['mytype'] = 'buyer';
            } else {
                $ad_info['mytype'] = 'seller';
            }
        }


        if ($order['ad_type'] == 1) {
            if ($ad_info['is_merchant']) {
                $ad_info['mytype'] = 'seller';
            } else {
                $ad_info['mytype'] = 'buyer';
            }
        }


        if ($ad_info['mytype'] == 'buyer') {
            $ad_info['seller'] = fullname($ad_info['peerid']);
        } else {
            $ad_info['seller'] = $user['firstname'] . ' ' . $user['lastname'];
        }

        $ad_info['payinfo'] = json_decode($order['payment_info'], true);
		if ($order['ad_type'] == 1) {
            $ad_info['buyer'] = $order['userid'];
            $ad_info['seller'] = $order['merchant_id'];
        } else {
            $ad_info['buyer'] = $order['merchant_id'];
            $ad_info['seller'] = $order['userid'];
        }

        return $ad_info;

    }

    public function test()
    {
     //   $paymethods = array(16, 18);
     //   $search = implode(',', $paymethods);
      //  $my_pay_method = M('UserBank')->where(array('userid' => '38'))->select($search);
        //var_dump(json_encode($my_pay_method));
    }

    private function userOtherbalances($userid, $type = 1, $coin = 'all')
    {
        $coin = strtolower($coin);
        if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        if (!check($type, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        $where = array();
        if ($coin != 'all') {
            if (!check($coin, 'n')) {
                $this->error("Incorrect coin " . $coin);
            }
            $where['coin'] = strtolower($coin);
        }
        $where['uid'] = $userid;
        $where['type'] = $type;
        $user_balance = M('user_assets')->where($where)->select();
        $coins = C("coin_safe");
        $list = array();
        foreach ($user_balance as $ub) {
            $list[$ub['coin']] = $coins[$ub['coin']];
            $list[$ub['coin']]['balance'] = $ub['balance'];
            $list[$ub['coin']]['freeze'] = $ub['freeze'];
            if ($list[$ub['coin']]['balance'] <= 0 || !array_key_exists($ub['coin'], $coins)) {
                unset($list[$ub['coin']]);
            }
        }


        return $list;
    }


    public function quick()
    {
		
		$FiatList = $this->FiatList();
		$allow_cryptos = $this->allowed_cryptos();
		$this->assign('FiatList', $FiatList);
        $this->assign('allow_cryptos', $allow_cryptos);
        $this->display();
    }

    public function apply()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $require_coin = strtolower(self::require_coin);
        $require_balance = self::require_balance;
        $FiatList = $this->FiatList();
        $allowed_fiat = array();
        foreach ($FiatList as $fiat) {
            $allowed_fiat[] = strtoupper($fiat['name']);
        }
        $status = $this->checkApply();
        $this->assign('allowed_fiat', $allowed_fiat);
        $this->assign('require_balance', $require_balance);
        $this->assign('require_coin', $require_coin);
        $this->assign('status', $status);
        $this->display();
    }

    public function markAsPaid()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }


        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(array('id' => $id, 'status' => 0))->find();


        if (!$order) {
            redirect(U("P2p/orders"));
        }
		
		
        $ad_info = $this->orderinfo($order, 'orders');
		
		$times_up = $this->iftimesUp($order['endtime']);
		if($ad_info['endtime']<time()){
			$this->doRefundCancel($order, $times_up);
			$this->error('Payment time has ended already!');
		}
		
		
        $ad_info = $this->orderinfo($order, 'orders');
		
		
		
		
        $peerid = $ad_info['peerid'];
        //This could be buyer instead of seller
        if ($ad_info['mytype'] != 'buyer') {
            $this->error(L("No such listing found"));
        }


        $subject = "P2p order has been paid";
        $message = "User has marked as paid your order " . $order['orderid'] . " please confirm";
        $updated = $mo->table('codono_p2p_orders')->where(array('id' => $order['id']))->save(array('has_paid'=>1,'paidtime'=>time(),'status' => 1));
		
        
        
        if ($updated) {
			   $chat_array = array('orderid' => $order['id'], 'content' => $message, 'userid' => 0, 'addtime' => time());
			 $mo->table('codono_p2p_chat')->where(array('orderid' => $order['id']))->add($chat_array);
            $this->notify($peerid, $subject, $message);
        }

        $this->success(L("This order has been markerd as paid"));

        //notify counter party that user has paid

    }

    public function confirmCancel()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }

        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(array('id' => $id, 'status' => 0))->find();

        if (!$order) {
            $this->error(L("No such listing found"));
        }

        $ad_info = $this->orderinfo($order, 'orders');

        $peerid = $ad_info['peerid'];

		//@todo if merchant and cancelled then change the rating
		
		
        //This could be seller instead buyer [ Only buyer can cancle the order]
        if ($ad_info['mytype'] != 'buyer') {
            $this->error(L("No such listing found"));
        }


        $coin = strtolower($order['coin']);
        $total = $order['coin_qty'];
        $orderid = $order['id'];
		$is_merchant=$ad_info['is_merchant'];
		$ad_id=$order['ad_id'];
        $mo = M();
        $mo->startTrans();

        //changes for seller
		$condition_1 = array('uid' => $uid, 'coin' => $coin);
		$condition_2 = array('uid' => $peerid, 'coin' => $coin);
        

        if($is_merchant){
		$rs[] = $mo->table('codono_user_assets')->where($condition_2)->setDec('freeze', $total);
        $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setInc('balance', $total);
		}
        
		
		
		$rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $ad_id))->setDec('freeze', $total);
		$rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $ad_id))->setInc('available', $total);

        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 0;

        //status 2 = cancelled
        $request = array('status' => 2);
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();
            

            $subject = "P2p order has been cancelled";
            $merchantname = username($uid);
            $message = $merchantname . " has cancelled this order " . $order['orderid'] . " any funds frozen will be refunded back";
            $this->notify($peerid, $subject, $message);

            $add_array = array('orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time());
            $mo->table('codono_p2p_chat')->where(array('orderid' => $orderid))->add($add_array);
            $this->success(L("This order has been cancelled"));
        } else {
            $mo->rollback();
            
            $this->error('There were issues updating the order!');
        }
    }

    public function releasePayment()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(array('id' => $id, 'status' => 1))->find();

        if (!$order) {
            $this->error(L("No such listing found"));
        }

        $ad_info = $this->orderinfo($order, 'orders');


        $peerid = $ad_info['peerid'];

        //This could be buyer instead of seller
        if ($ad_info['mytype'] != 'seller') {
            $this->error(L("No such listing found"));
        }
		$is_merchant=$ad_info['is_merchant'];
		
        $coin = strtolower($order['coin']);
        $total = $order['coin_qty'];
        $orderid = $order['id'];
		$ad_id=$order['ad_id'];
        $mo = M();
        $mo->startTrans();
		//@todo if merchant confirmed then change the rating 5.0
		
		
        //changes for seller
        $condition_1 = array('uid' => $uid, 'coin' => $coin);
        $found = $mo->table('codono_user_assets')->where($condition_1)->find();
        if(!$found) {
            $rs[]= $mo->table('codono_user_assets')->add($condition_1);
        }
        


        //changes for buyer
        $condition_2 = array('uid' => $peerid, 'coin' => $coin);
        $found = $mo->table('codono_user_assets')->where($condition_2)->find();
        if(!$found) {
            $rs[]=$mo->table('codono_user_assets')->add($condition_2);
        }
		//user is seller but not merchant thus reduce his freeze
		if(!$is_merchant){
				$rs[] = $mo->table('codono_user_assets')->where($condition_1)->setDec('freeze', $total);
		}
        $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setInc('balance', $total);
		
		
		
		$rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $ad_id))->setDec('freeze', $total);
		
		
		
		
		
        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 1;

        $request = array('status' => 4);
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();
            
            $subject = "P2p payment has been released";
            $message = "User has released the funds on your order " . $order['orderid'] . " Congrats!";
            $this->notify($peerid, $subject, $message);
            $this->success(L("This order has been markerd as received and Payment has been released"));
        } else {
            $mo->rollback();
            
            $this->error('There were issues updating the order!');
        }

    }
	
	 public function dispute()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(array('id' => $id, 'status' => 1))->find();

        if (!$order) {
            $this->error(L("No such listing found"));
        }

        $ad_info = $this->orderinfo($order, 'orders');
		
        $peerid = $ad_info['peerid'];

        //This could be buyer instead of seller
        if ($ad_info['mytype'] != 'seller' && $ad_info['mytype'] != 'buyer') {
            $this->error(L("No such listing found"));
        }
		
		$is_merchant=$ad_info['is_merchant'];
		
        $coin = strtolower($order['coin']);
        $total = $order['coin_qty'];
        $orderid = $order['id'];
		$ad_id=$order['ad_id'];
        $mo = M();
        $mo->startTrans();


		//@todo if merchant confirmed then change the rating 5.0
		
		
		
		
		
        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 1;

        $request = array('status' => 3);
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();
            
            $subject = "P2P order Disputed";
            $message = username($uid)." has created a dispute for order " . $order['orderid'] . "!";
           $this->notify($peerid, $subject, $message);
           $chat_array = array('orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time());
           $mo->table('codono_p2p_chat')->where(array('orderid' => $orderid))->add($chat_array);
					
					
            $this->success(L("This order has been opened for dispute resolution"));
        } else {
            $mo->rollback();
            
            $this->error('There were issues updating the order!');
        }

    }

    public function sendchat()
    {
        $uid = userid();
        $data = array('status' => 0, 'data' => array());
        if (!$uid) {
            exit(json_encode($data));
        }
        $orderid = I('request.orderid', 0, 'int');
        $content = I('request.content', null, 'text');

        if ($orderid != 0 && $content != null) {



            $mo = M();
            $order = $mo->table('codono_p2p_orders')->where(array('id' => $orderid))->find();

            if ($order['userid'] == $uid) {
              //  $is_merchant = 0;
               // $peerid = $order['merchant_id'];
            } elseif ($order['merchant_id'] == $uid) {
               // $is_merchant = 1;
               // $peerid = $order['userid'];
            } else {
                exit(json_encode($data));
            }

            $add_array = array('orderid' => $orderid, 'content' => $content, 'userid' => $uid, 'addtime' => time());
            $data['status'] = 1;
            $data['info'] = $mo->table('codono_p2p_chat')->where(array('orderid' => $orderid))->add($add_array);
        }
        exit(json_encode($data));
    }

    public function getChat()
    {
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }

        $uid = userid();

        $data = array('status' => 0, 'data' => array());
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(array('id' => $id))->find();

        if ($order['userid'] == $uid) {
          //  $is_merchant = 0;
          //  $peerid = $order['merchant_id'];
        } elseif ($order['merchant_id'] == $uid) {
           // $is_merchant = 1;
           // $peerid = $order['userid'];
        } else {
            exit(json_encode($data));
        }
        $d_chats = $mo->table('codono_p2p_chat')->where(array('orderid' => $id))->select();
        $chats=[];
        foreach ($d_chats as $_chat) {
            $_chat['timestamp'] = $this->time_elapsed_string($_chat['addtime']);
            $chats[] = $_chat;
        }
        $data = array('status' => 0, 'data' => $chats);
        exit(json_encode($data));
    }

    private function time_elapsed_string($ptime)
    {
        $etime = time() - $ptime;

        if ($etime < 1) {
            return '0 seconds';
        }

        $a = array(365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        );
        $a_plural = array('year' => 'years',
            'month' => 'months',
            'day' => 'days',
            'hour' => 'hours',
            'minute' => 'minutes',
            'second' => 'seconds'
        );

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
            }
        }
        return false;
    }

    private function notify($userid, $subject, $message)
    {
        $email = getEmail($userid);
        addnotification($email, $subject, $message);
    }

    private function checkApply()
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $userinfo = $this->userinfo;
        $status = array("sms" => 0, "email" => 0, "kyc" => 0, "merchant" => 0, "balance" => 0);
        $require_coin = strtolower(self::require_coin);
        $require_balance = self::require_balance;
        $balance = $this->findFunds($require_coin);
        if ($userinfo['cellphone'] && $userinfo['cellphones'] && $userinfo['cellphonetime']) {
            $status['sms'] = 1;
        }
        if ($userinfo['email']) {
            $status['email'] = 1;
        }
        if ($userinfo['idcardauth']) {
            $status['kyc'] = 1;
        }
        if ($userinfo['is_merchant']) {
            $status['merchant'] = 1;
        }
        if ($balance && $balance > $require_balance) {
            $status['balance'] = 1;
        }

        return $status;

    }

    private function findFunds($coin)
    {
        $coin = strtolower($coin);
        $isValidCoin = isValidCoin($coin);
        if ($coin == null || !$isValidCoin) {
            return 0;
        }
        $require_coin = strtolower($coin);
        $userBal = M('UserAssets')->where(array('uid' => userid(), 'coin' => strtolower($require_coin)))->find();
		
        return $userBal['balance'] ?: 0;
    }

    public function doApply()
    {
		$uid=userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $require_coin = strtolower(self::require_coin);
        $require_balance = self::require_balance;
        $status = $this->checkApply();

        if (!$status['sms']) {
            $this->error(L('Please confirm you mobile number!'));
        }
        if (!$status['email']) {
            $this->error(L('Please confirm you email!'));
        }
        if (!$status['kyc']) {
            $this->error(L('Please complete KYC!'));
        }
        if (!$status['balance']) {
            $this->error(L("You need balance of " . $require_coin . " " . $require_balance));
        }
		$if_merchant=M('p2p_merchants')->where(array('uid' => $uid, 'status' => 1))->find();
        if ($status['sms'] && $status['email'] && $status['kyc']) {
			
			if(!$if_merchant){
				M('p2p_merchants')->where(array('id' => $uid))->add(array('uid' => $uid,'name'=>username($uid),'status'=>1,'rating'=>0,'orders'=>0));
			}
             M('User')->where(array('id' => $uid))->save(array('is_merchant' => 1));
			 
            $this->success(L('Successfully upgraded for merchant!'));
        }
        $this->error(L("There were some issues for your application"));
    }


    public function orders()
    {
        $userid = userid();
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $allow_cryptos = $this->allowed_cryptos();
        $this->assign('allow_cryptos', $allow_cryptos);

        $where = array('userid' => $userid);

        $Model = M('P2pOrders');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $p2pOrders = M('P2pOrders')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $type = "order";
        $this->assign('p2pOrders', $p2pOrders);
        $this->assign('page', $show);
        $this->assign('type', $type);
        $this->display();
    }

    public function received()
    {
        $userid = userid();
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $allow_cryptos = $this->allowed_cryptos();
        $this->assign('allow_cryptos', $allow_cryptos);

        $where = array('merchant_id' => $userid);

        $Model = M('P2pOrders');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $p2pOrders = M('P2pOrders')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $type = "received";
        $this->assign('p2pOrders', $p2pOrders);
        $this->assign('page', $show);
        $this->assign('type', $type);
        $this->display('P2p/orders');
    }

    public function paymentsettings()
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

        $truename = $user['firstname'] . ' ' . $user['lastname'];

        $this->assign('truename', $truename);
        //$UserBank = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->limit(1)->select();
        $UserBank = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $FiatList = $this->FiatList();
        $this->assign('FiatList', $FiatList);
        $this->assign('UserBank', $UserBank);


        $this->display();
    }

    public function doAddBank($bank, $bankaddr, $bankcard)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($bank, 'a')) {
            $this->error(L('Bank malformed!'));
        }


        if (!check($bankaddr, 'a')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'd')) {
            $this->error(L('Bank account number format error!'));
        }

        if (!M('UserBankType')->where(array('title' => $bank))->find()) {
            //$this->error(L('Bank error!'));
        }

        $userBank = M('UserBank')->where(array('userid' => userid()))->select();

        foreach ($userBank as $k => $v) {
            if ($v['bankcard'] == $bankcard) {
                $this->error(L('Bank card number already exists!'));
            }
        }

        if (20 <= count($userBank)) {
            $this->error('Each user can add upto 20 accounts max!');
        }

        if (M('UserBank')->add(array('userid' => userid(), 'name' => userid() . '_' . time(), 'bank' => $bank, 'bankprov' => 'NA', 'bankcity' => 'NA', 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('Banks added successfully!'));
        } else {
            $this->error(L('Bank Add Failed!'));
        }
    }

    public function newad()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $allow_cryptos = self::allowed_cryptos;
        $FiatList = $this->FiatList();
        $release_times = self::release_time;
        $rt_go = array();
        foreach ($release_times as $rt) {

            $release_time['value'] = $rt;
            if ($rt < 60) {
                $release_time['type'] = 'm';
                $release_time['title'] = $rt;
            } else {
                $release_time['title'] = bcdiv($rt, 60, 1);
                $release_time['type'] = 'h';
            }
            $rt_go[] = $release_time;
        }
        $SellMethods = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $BuyMethods = M('P2pMethods')->where(array('status' => 1))->order('id desc')->select();
        $this->assign('allow_cryptos', $allow_cryptos);
        $this->assign('release_time', $rt_go);
        $this->assign('SellMethods', $SellMethods);
        $this->assign('BuyMethods', $BuyMethods);
        $this->assign('FiatList', $FiatList);

        $this->display();
    }

    public function doNewAd()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $request["fiat"] = I('request.fiat', null, 'string');
        $request["coin"] = I('request.crypto', null, 'string');
        $request["price_type"] = I('price_type/d');
        $request["fixed_price"] = I('user_price/f');
        $request["floating"] = I('request.user_float', null, 'int');
        $request["available"] = I('request.user_total', 0.00, 'float');
        $request["ad_type"] = I('type/d');
        $request["time_limit"] = I('payment_time/d');
        $request["min_limit"] = I('order_min', 0.00, 'float');
        $request["max_limit"] = I('order_max', 0.00, 'float');
        $request["terms"] = I('description', null, 'text');
        $request["autoreply"] = I('autoreply', null, 'text');
        $request["cond_kyc"] = I('kyc_required/d');
        $request["cond_reg"] = I('reg_time_required/d');
        $request["cond_reg_ago"] = I('reg_time/d'); //days
        $request["cond_balance"] = I('balance_required/d');
        $request["cond_min_bal"] = I('min_balance', 0.00, 'float'); //min balance required
        $request["online"] = I('online/d'); //is ad online or offline right now
        $request["uid"] = $uid;

        $bankList = I('bankList');


        $isValidCoin = isValidCoin($request['coin']);
        if (!$request['coin'] || !$isValidCoin) {
            $this->error("Please choose correct asset");
        }
        $isValidFiat = isValidCoin($request['fiat']);
        if (!$request['fiat'] || !$isValidFiat) {
            $this->error("Please choose correct Fiat");
        }
        if ($request["price_type"] != 1 && $request["price_type"] != 2) {
            $this->error("Please choose correct type buy or sell");
        }
        if ($request["ad_type"] != 1 && $request["ad_type"] != 2) {
            $this->error("Please choose correct type fixed or floating");
        }
        if (!$request["floating"] && $request["ad_type"] != 1) {
            $request["floating"] = 0;
        }

        if ($request["floating"] < 50 && $request["floating"] > 200) {
            $this->error("Floating percentage can be between 50 to 200");
        }
        if ($request["available"] <= 0) {
            $this->error('Please enter total quantity!');
        }
        if ($request["cond_reg"] != 0 && $request["cond_reg"] != 1) {
            $this->error("Please choose correct condition for user registration duration");
        }
        if ($request["cond_kyc"] != 0 && $request["cond_kyc"] != 1) {
            $this->error("Please choose correct condition for kyc requirement");
        }
        if ($request["cond_balance"] != 0 && $request["cond_balance"] != 1) {
            $this->error("Please choose correct condition for balance requirement");
        }
        if ($request["online"] != 0 && $request["online"] != 1) {
            $this->error("Please choose either online or offline");
        }
        if ($request["cond_balance"] == 1 && $request["cond_min_bal"] < 0) {
            $this->error("Please choose correct amount for balance requirement");
        }
        if ($request["cond_reg"] == 1 && ($request["cond_reg_ago"] <= 0 || $request["cond_reg_ago"] >= 730)) {
            $this->error("Please choose correct number of days for user registration condition");
        }
		
        $userinfo = M('User')->where(array('id' => $uid))->find();
		
        $merchant_info = M('p2p_merchants')->where(array('uid' => $uid, 'status' => 1))->find();
        if ($userinfo['is_merchant'] == 0 || $merchant_info['status'] != 1) {
            $this->error("Please apply for merchant account");
        }
        $request['name'] = $merchant_info['name'] ?: username();
        if ($request['ad_type'] == 2) {
            $funds = $this->findFunds($request['coin']);
            if ($funds < $request['available']) {
                $this->error("Your P2P account has only " . $request['coin'] . " " . $funds);
            }
        }

       // $Usermethods = M('UserBank')->where(['userid' => $uid, 'status' => 1])->select();
        $request['pay_methods'] = json_encode($this->validateUserMethods($uid, $bankList));
        $request['orderid'] = strtoupper(dechex(cardGenPublic($uid)));
        $request['status'] = 1;
        $request['created_at'] = $request['updated_at'] = time();

        if($request['ad_type'] == 2) {
            $coin = strtolower($request['coin']);
            $total = $request['available'];

            $mo = M();
            $mo->startTrans();
            $before_balance = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->find();

            $rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->setDec('balance', $total);
            
            $rs[] = $p2p_adid = $mo->table('codono_p2p_ads')->add($request);

            $after_balance = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->find();

            $finance_hash = md5($p2p_adid . 'p2p_' . $uid . time());

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => $coin, 'num_a' => $before_balance['balance'], 'num_b' => $before_balance['freeze'], 'num' => $before_balance['balance'] + $before_balance['freeze'], 'fee' => $total, 'type' => $request['ad_type'], 'name' => 'p2p', 'nameid' => $p2p_adid, 'remark' => 'P2P Ad post', 'mum_a' => $after_balance['balance'], 'mum_b' => $after_balance['freeze'], 'mum' => $after_balance['balance'] + $after_balance['freeze'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));
        } else {
            //Ad type is buy
            //$coin = strtolower($request['coin']);
            //$total = $request['available'];
            $mo = M();
            $mo->startTrans();
            $rs[] = $mo->table('codono_p2p_ads')->add($request);
        }
        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success('P2P ad has been placed!');
        } else {
            $mo->rollback();
            
            $this->error('There were issues placing the order!');
        }
    }

    public function edit()
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $id = I('request.id', 0, 'int');
        $where['id'] = $id;
        $where['uid'] = $uid;
        $Listing = M('P2pAds')->where($where)->find();
        if (!$Listing || empty($Listing)) {
            $this->error(L("No such listing found"));
        }

        $allow_cryptos = self::allowed_cryptos;
        $FiatList = $this->FiatList();
        $release_times = self::release_time;
        $rt_go = array();
        foreach ($release_times as $rt) {

            if ($rt < 60) {
                $release_time['value'] = $rt;
                $release_time['type'] = 'm';
            } else {
                $release_time['value'] = bcdiv($rt, 60, 1);
                $release_time['type'] = 'h';
            }
            $rt_go[] = $release_time;
        }
        $paymethods = array();
        if ($Listing['pay_methods'] != null && $this->is_json($Listing['pay_methods'])) {
            $paymethods = json_decode($Listing['pay_methods'], true);
        }

        $UserBank = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $this->assign('allow_cryptos', $allow_cryptos);
        $this->assign('release_time', $rt_go);
        $this->assign('UserBank', $UserBank);
        $this->assign('FiatList', $FiatList);
        $this->assign('ad', $Listing);
        $this->assign('paymethods', $paymethods);
        $this->display();
    }

    public function doEdit()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $id = I('request.id', 0, 'int');
        $where['id'] = $id;
        $where['uid'] = $uid;
        $Listing = $ad_Listing = M('P2pAds')->where($where)->find();
        if (!$Listing || empty($Listing)) {
            $this->error(L("No such listing found"));
        }
        $the_type=1;
        $request["fiat"] = I('request.fiat', null, 'string');
        $request["coin"] = I('request.crypto', null, 'string');
        $request["price_type"] = I('price_type/d');
        $request["fixed_price"] = I('user_price/f');
        $request["floating"] = I('request.user_float', null, 'int');
        $request["available"] = I('request.user_total', 0.00, 'float');
        $request["ad_type"] = I('type/d');
        $request["time_limit"] = I('payment_time/d');
        $request["min_limit"] = I('order_min', 0.00, 'float');
        $request["max_limit"] = I('order_max', 0.00, 'float');
        $request["terms"] = I('description', null, 'text');
        $request["autoreply"] = I('autoreply', null, 'text');
        $request["cond_kyc"] = I('kyc_required/d');
        $request["cond_reg"] = I('reg_time_required/d');
        $request["cond_reg_ago"] = I('reg_time/d'); //days
        $request["cond_balance"] = I('balance_required/d');
        $request["cond_min_bal"] = I('min_balance', 0.00, 'float'); //min balance required
        $request["online"] = I('online/d'); //is ad online or offline right now
        $request["uid"] = $uid;
        $request["cond_kyc"] = $request["cond_kyc"] ? 1 : 0;
        $request["cond_reg"] = $request["cond_reg"] ? 1 : 0;
        $request["cond_balance"] = $request["cond_balance"] ? 1 : 0;
        $input = $request;

        $difference = 0;
        $action = null;
        if ($ad_Listing['available'] > $input['available']) {
            $action = 'small';
            $difference = bcsub($ad_Listing['available'], $input['available'], 8);
        }
        if ($ad_Listing['available'] < $input['available']) {
            $action = 'large';
            $difference = bcsub($input['available'], $ad_Listing['available'], 8);
        }

        foreach ($Listing as $key => $val) {
            if ($val == $request[$key]) {
                unset($request[$key]);
                unset($Listing[$key]);
            }
        }
        if (empty($request)) {
            $this->success(L("No changes made"));
        }

        $bankList = I('bankList');

        if ($request['coin']) {
            $isValidCoin = isValidCoin($request['coin']);
            if (!$isValidCoin) {
                $this->error("Please choose correct asset");
            }
        }
        if ($request['fiat']) {
            $isValidFiat = isValidCoin($request['fiat']);
            if (!$isValidFiat) {
                $this->error("Please choose correct Fiat");
            }
        }

        if ($request["price_type"] && $request["price_type"] != 1 && $request["price_type"] != 2) {
            $this->error("Please choose correct type buy or sell");
        }

        if ($request["ad_type"] && $request["ad_type"] != 1 && $request["ad_type"] != 2) {
            $this->error("Please choose correct type fixed or floating");
        }
        if ($request["floating"] && !$request["floating"] && $request["ad_type"] != 1) {
            $request["floating"] = 0;
        }

        if ($request["floating"] && $request["floating"] < 50 && $request["floating"] > 200) {
            $this->error("Floating percentage can be between 50 to 200");
        }
        if ($request["available"] && $request["available"] <= 0) {
            $this->error('Please enter total quantity!');
        }
        if (isset($request["cond_reg"])) {
            if ($request["cond_reg"] != 0 && $request["cond_reg"] != 1) {
                $this->error("Please choose correct condition for user registration duration");
            }
        }
        if (isset($request["cond_kyc"])) {
            if ($request["cond_kyc"] != 0 && $request["cond_kyc"] != 1) {
                $this->error("Please choose correct condition for kyc requirement");
            }
        }
        if (isset($request["cond_balance"])) {
            if ($request["cond_balance"] != 0 && $request["cond_balance"] != 1) {
                $this->error("Please choose correct condition for balance requirement");
            }
        }
        if (isset($request["online"])) {
            if ($request["online"] != 0 && $request["online"] != 1) {
                $this->error("Please choose either online or offline");
            }
        }
        if (isset($request["cond_min_bal"])) {
            if ($request["cond_min_bal"] < 0) {
                $this->error("Please choose correct amount for balance requirement");
            }
        }
        if (isset($request["cond_reg"])) {
            if ($request["cond_reg"] == 1 && ($request["cond_reg_ago"] <= 0 || $request["cond_reg_ago"] >= 730)) {
                $this->error("Please choose correct number of days for user registration condition");
            }
        }
        $userinfo = $this->userinfo;
        $merchant_info = M('p2p_merchants')->where(array('uid' => $uid, 'status' => 1))->find();
        if ($userinfo['is_merchant'] == 0 || $merchant_info['status'] != 1) {
            $this->error("Please apply for merchant account");
        }
        $request['name'] = $merchant_info['name'] ?: username();
        if ($request['ad_type'] == 2) {
            $funds = $this->findFunds($request['coin']);
            if ($funds < $request['available']) {
                $this->error("Your P2P account has only " . $request['coin'] . " " . $funds);
            }
        }

      //  $Usermethods = M('UserBank')->where(['userid' => $uid, 'status' => 1])->select();
        $request['pay_methods'] = json_encode($this->validateUserMethods($uid, $bankList));

        $request['status'] = 1;
        $request['updated_at'] = time();

        $coin = strtolower($request['coin']);
        $total = $request['available'];

        $mo = M();
		$mo->startTrans();	
        if($request['ad_type'] == 2) {
			
            $before_balance = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->find();

            if ($difference > 0 && $action == 'small') {
                $the_type = 1;
                $rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->setDec('freeze', $difference);
                $rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->setInc('balance', $difference);
            }
            if ($difference > 0 && $action == 'large') {
                $the_type = 2;
                $rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->setDec('balance', $difference);
                $rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->setDec('freeze', $difference);
            }
            $up_where['id'] = $p2p_adid = $request['id'];
            $up_where['coin'] = $coin;
            $up_where['status'] = 1;

            $rs[] = $mo->table('codono_p2p_ads')->where($up_where)->save($request);

            $after_balance = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->find();

            $finance_hash = md5($p2p_adid . 'p2p_update_' . $uid . time());

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => $coin, 'num_a' => $before_balance['balance'], 'num_b' => $before_balance['freeze'], 'num' => $before_balance['balance'] + $before_balance['freeze'], 'fee' => $total, 'type' => $the_type, 'name' => 'p2p', 'nameid' => $p2p_adid, 'remark' => 'P2P Edit post', 'mum_a' => $after_balance['balance'], 'mum_b' => $after_balance['freeze'], 'mum' => $after_balance['balance'] + $after_balance['freeze'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));
        } else {
            //Ad type is buy

            $up_where['id'] = $request['id'];
            $up_where['coin'] = $coin;
            $up_where['status'] = 1;

            $rs[] = $mo->table('codono_p2p_ads')->where($up_where)->save($request);
        }
        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success('P2P ad has been updated!');
        } else {
            $mo->rollback();
            
            $this->error('There were issues updating the order!');
        }
    }

    private function is_json($str)
    {
        return json_decode($str) != null;
    }

    private function validateUserMethods($userid, $selected)
    {
        $Usermethods = M('UserBank')->where(['userid' => $userid, 'status' => 1])->select();
        $good_ids = array();
        foreach ($Usermethods as $key => $val) {
            foreach ($selected as $sel) {
                if ($val['id'] === strtoupper($sel)) {
                    $good_ids[] = $val['id'];
                }
            }
        }
        return $good_ids;
    }


    private function searchBySymbol($symbol, $array)
    {
        foreach ($array as $key => $val) {
            if ($val['symbol'] === strtoupper($symbol)) {
                return $val['price_usd'];
            }
        }
        return null;
    }

    public function grabPrice($crypto = "btc", $fiat = "usdt")
    {
        $cmcs = (APP_DEBUG ? null : S('cmcrates'));
        $uc_crypto = strtoupper($crypto);
        $lc_crypto = strtolower($crypto);
        $uc_fiat = strtoupper($fiat);
        $lc_fiat = strtolower($fiat);
        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }

        $price_in_usd = $this->searchBySymbol($uc_crypto, $cmcs);
        if ($uc_fiat != "USD") {
            $fiat_to_usd = $this->searchBySymbol($uc_fiat, $cmcs);
        } else {
            $fiat_to_usd = 1;
        }

        $crypto_to_fiat_price = bcdiv($price_in_usd, $fiat_to_usd, 2);
        $crypto_to_fiat_price = $crypto_to_fiat_price ?: 0;
        if (!$crypto_to_fiat_price) {
            $data['status'] = 0;
        } else {
            $data['status'] = 1;
        }
		$data['userid'] = userid();
        $data['value'] = $crypto_to_fiat_price;
        $data['crypto'] = $uc_crypto;
        $data['fiat'] = $uc_fiat;
        $data['symbol'] = $this->fiatSymbol($uc_fiat);
        $data['balance'] = $this->findFunds($uc_crypto);
        echo json_encode($data);
        exit;

    }

    private function fiatSymbol($fiat)
    {
        $fiat = strtolower($fiat);
        $symbolList = array("btc" => "&#8383;", "usd" => "&#36;", "cent" => "&#162;", "pound" => "&#163;", "eur" => "&#8364;", "yen" => "&#165;", "inr" => "&#8377;", "rub" => "&#8381;", "yuan" => "&#20803;", "ngn" => "&8358;", "pts" => "&#8359;", "rup" => "&#8360;", "won" => "&#8361;", "sheq" => "&#8362;", "dong" => "&#8363;", "kip" => "&#8365;", "mnt" => "&#8366;", "php" => "&#8369;", "try" => "&#8378;", "azn" => "&#8380;", "thb" => "&#3647;", "irr" => "&#65020;");
        $symbol = $symbolList[$fiat];
        if (!$symbol) {
            $symbol = strtoupper(substr($fiat, 0));
        }
        return $symbol;

    }

    public function myads()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $uid = userid();
        $type = I('request.type', null, 'string'); //buy/sell

        $coin = I('request.coin', null, 'string');
        $status = I('request.status', null, 'string'); //online or offline


        if ($status && ($status == 'online' || $status == 'offline')) {
            if ($status == 'online') {
                $where['online'] = 1;
            }
            if ($status == 'offline') {
                $where['online'] = 0;
            }
        }
        if ($type == 'buy') {
            $where['ad_type'] = 1;
        }
        if ($type == 'sell') {
            $where['ad_type'] = 2;
        }
        $where['uid'] = $uid;
        if ($coin) {
            $where['coin'] = $coin;
        }
        $where['status'] = 1;

        $Model = M('P2pAds');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();


        $ads = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();


        $this->assign('myads', $ads);

        $allow_cryptos = $this->allowed_cryptos();
        $this->assign('allow_cryptos', $allow_cryptos);
        $this->assign('page', $show);
        $this->display();
    }

    public function closelisting()
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $uid = userid();
        $orderid = I('request.orderid', 0, 'string');

        if (!$orderid || $orderid == 0) {
            $this->error(L("No such listing found"));
        }

        $where['uid'] = $uid;
        $where['orderid'] = $orderid;
        $where['status'] = 1;


        $ad = M('P2pAds')->where($where)->find();
		
        $coin=strtolower($ad['coin']);
        $total=$ad['available'];
        if (!$ad || empty($ad)) {
            $this->error(L("No such listing found"));
        }

        $rs = array();
        $mo=M();
        $mo->startTrans();
        $ad = M('P2pAds')->where($where)->find();

        $coin=strtolower($ad['coin']);
        $total=$ad['available'];
        $before_balance = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->find();

        //move p2p_ads entry 'available' to 'freeze'
		//refund if the funds were frozen for sell order
		if($ad['ad_type']==2){
				$rs[] = $mo->table('codono_p2p_ads')->where(array('id' => $ad))->setDec('available', $total);
				$rs[] = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $coin))->setInc('balance', $total);
		}
        $rs[] = M('P2pAds')->where($where)->save(array('online' => 0,'status' => 0));

        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success(L("Success"));
        } else {
            $mo->rollback();
            
            $this->error('There were issues placing the order!');
        }

    }

    private function allowed_cryptos()
    {
        $allowed_cryptos = array();
        foreach (self::allowed_cryptos as $base) {
            $base = strtolower($base);
            $allowed_cryptos[$base]['image'] = '/Upload/coin/' . c('coin')[$base]['img'];
            $allowed_cryptos[$base]['symbol'] = $base;
            $allowed_cryptos[$base]['title'] = c('coin')[$base]['title'];
            $allowed_cryptos[$base]['min'] = 1;
            if ($base == 'usdt' || $base == 'usd') {
                $max_allowed_base = 1000000;
            } else {
                $max_allowed_base = 5000000;
            }
            $allowed_cryptos[$base]['max'] = $max_allowed_base;
        }
        return $allowed_cryptos;
    }

    public function merchantapplication()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->display();
    }

    public function faq()
    {
		$name=self::article_category;
		$Articletype = M('ArticleType')->where(array('name' => $name))->find();
		$main_title=$Articletype['title'];
		$pname=$Articletype['name'];
		$SubArticletypes=M('ArticleType')->where(array('shang' => $pname))->select();
		$i=0;
        $list=[];
		foreach($SubArticletypes as  $sub){
			
			$list[$i]['category']=$sub['name'];
			$list[$i]['id']=$sub['id'];
			
			$list[$i]['data']=M('Article')->where(array('status' => 1, 'type' => $sub['name']))->select();
			$i++;
		}
		$this->assign('main_title',$main_title);
		$this->assign('pname',$pname);
		$this->assign('list',$list);
        $this->display();
    }


    public function delbank($id)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }


        if (!M('UserBank')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserBank')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }

    private function getP2pPaymentMethods()
    {
        $export = array();
        $data = (APP_DEBUG ? null : S('getP2pPaymentMethods'));
        if ($data == null) {
            $infos = M('P2pMethods')->where(array('status' => 1))->order('sort asc,name asc')->select();
            foreach ($infos as $info) {
                $export[$info['id']] = $info;
            }
            $data = $export;
        }
        return $data;
    }
	public function cryptolist(){
		return self::allowed_cryptos;
	}
}