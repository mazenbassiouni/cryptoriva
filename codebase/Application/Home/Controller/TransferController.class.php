<?php

namespace Home\Controller;

use Think\Page;

class TransferController extends HomeController
{
	const allowed_types = array('spot', 'p2p', 'nft','margin');  // type of account balances
    public function _initialize()
    {

        parent::_initialize();
        if (!userid()) {
            redirect('/#login');
        }

    }
	private function userSpotbalance($userid){
		if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
		$user_balance=M('UserCoin')->where(array('userid' => $userid))->find();
		$coins=C("coin_safe");
		foreach($coins as $coin){
			$list[$coin['name']]=$coin;
			$list[$coin['name']]['balance']=$user_balance[$coin['name']];
			$list[$coin['name']]['freeze']=$user_balance[$coin['name'].'d'];
			if($list[$coin['name']]['balance']<=0){
				unset($list[$coin['name']]);
			}
		}
		return $list;
	}
	/*
	Type =>1 P2P 2 Other2 3 Other3	
	*/
	private function userOtherbalances($userid,$type=1,$coin='all'){
		if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
		if (!check($type, 'd')) {
			$this->error(L('INCORRECT_REQ'));
		}
		$where=array();
		if($coin!='all'){
			if (!check($coin, 'n')) {
				$this->error("Incorrect coin");
			}
			$where['coin']=strtolower($coin);
		}
		$where['uid']=$userid;
		$where['type']=$type;
		$user_balance=M('user_assets')->where($where)->select();
		$coins=C("coin_safe");
		
		foreach($user_balance as $ub){
			$list[$ub['coin']]=$coins[$ub['coin']];
			$list[$ub['coin']]['balance']=$ub['balance'];
			$list[$ub['coin']]['freeze']=$ub['freeze'];
			if($list[$ub['coin']]['balance']<=0 || !array_key_exists($ub['coin'],$coins )){
				unset($list[$ub['coin']]);
			}
		}
		
		
		return $list;
	}
	
	public function index(){
		if(!P2P_TRADING){
            redirect(U('Finance/index'));
        }
		
		$from = I('request.from', 'spot', 'text');
		$to = I('request.from', 'p2p', 'text');
		 if (!in_array($from, self::allowed_types)) {
			 $from="spot";
			 $to="p2p";
		 }
		 if (!in_array($to, self::allowed_types)) {
			 $from="spot";
			 $to="p2p";
		 }
		$user_balance=M('UserCoin')->where(array('userid' => userid()))->find();
		if($from=='p2p' && $to='spot'){
		$list=$this->userOtherbalances(userid(),2);
		}
		if($from=='spot' && $to='p2p'){
		$list=$this->userSpotbalance(userid());
		}
		$this->assign("to",$to);
		$this->assign("from",$from);
		$this->assign("coins",$list);
		$this->assign("user_balance",$user_balance);
		$this->display();
	}
	public function doTransfer(){
		$userid=userid();
		if (!$userid) {
            $this->error(L('PLEASE_LOGIN'));
        }

	    
		$from = strtolower(I('request.from', 'spot', 'text'));
		$to = strtolower(I('request.to', 'p2p', 'text'));
		$coin = strtolower(I('request.coin', 'USDT', 'string'));
        $amount = I('amount/f');
		if (!check($amount, 'decimal')) {
                $this->error('Incorrect Amount:'.$amount);
        }

		if (!in_array($from, self::allowed_types) || !in_array($to, self::allowed_types) || $from==$to) {
			 $this->error("Select Correct  accounts");
		}

		$isValidCoin=$this->isValidCoin($coin);
        if($coin==null || !$isValidCoin){
            $this->error('Invalid coin');
        }
		$coind=$coin.'d';
        $mo=M();
        $before_spot_balance= $mo->table('codono_user_coin')->where(array('userid' => $userid))->field(array($coin,$coin.'d'))->find();
		if($from=='p2p' && $to='spot'){
			$list=$this->userOtherbalances(userid(),2);
		}
		if($from=='spot' && $to='p2p'){
			$list=$this->userSpotbalance(userid());
		}
		$before_user_balance=$list[$coin]['balance'];
		
		if($before_user_balance<$amount){
			$this->error('Insufficient balance');
		}

        $user_balance=$list[$coin];

        $rs=array();
		
        $mo->startTrans();

		if($from=='spot' && $to=='p2p'){

            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setDec($coin, $amount);
		    //Check if user_assets table has following row or not userid=$userid and coin=$coin if not then first insert then update

            list($condition, $result, $condition_add) = $this->sub_transfer($userid, $coin, $mo);
            if($result['coin']==$coin && $result['uid']=$userid){
                //add balance
                $rs[]=$mo->table('codono_user_assets')->where($condition)->setInc('balance', $amount);
            }

            $after_spot_balance= $mo->table('codono_user_coin')->where(array('userid' => $userid))->field(array($coin,$coin.'d'))->find();
            $rs[]=$tid=$mo->table('codono_transfer')->add(array('userid'=>$userid,'coin'=>$coin,'amount'=>$amount,'from_account'=>$from,'to_account'=>$to,'created_at'=>time()));
            $finance_hash=md5(ADMIN_KEY.'transfer'.$userid.$tid.time());
            $rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $coin, 'num_a' => $before_spot_balance[$coin], 'num_b' => $before_spot_balance[$coind], 'num' => $before_spot_balance[$coin] + $before_spot_balance[$coind], 'fee' => $amount, 'type' => 2, 'name' => 'giftcard', 'nameid' => $tid, 'remark' => 'Transfer', 'mum_a' => $after_spot_balance[$coin], 'mum_b' => $after_spot_balance[$coind], 'mum' => $after_spot_balance[$coin] + $after_spot_balance[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));
        }
        if($from=='p2p' && $to=='spot'){

            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($coin, $amount);
            //Check if user_assets table has following row or not userid=$userid and coin=$coin if not then first insert then update

            list($condition, $result, $condition_add) = $this->sub_transfer($userid, $coin, $mo);

            $rs[]=$mo->table('codono_user_assets')->where($condition)->setDec('balance', $amount);
            
            $after_spot_balance= $mo->table('codono_user_coin')->where(array('userid' => $userid))->field(array($coin,$coind))->find();
			
            $rs[]=$tid=$mo->table('codono_transfer')->add(array('userid'=>$userid,'amount'=>$amount,'coin'=>$coin,'from_account'=>$from,'to_account'=>$to,'created_at'=>time()));
			
            $finance_hash=md5(ADMIN_KEY.'transfer'.$userid.$tid.time());
            $rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $coin, 'num_a' => $before_spot_balance[$coin], 'num_b' => $before_spot_balance[$coind], 'num' => $before_spot_balance[$coin] + $before_spot_balance[$coind], 'fee' => $amount, 'type' => 1, 'name' => 'giftcard', 'nameid' => $tid, 'remark' => 'Transfer', 'mum_a' => $after_spot_balance[$coin], 'mum_b' => $after_spot_balance[$coind], 'mum' => $after_spot_balance[$coin] + $after_spot_balance[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));
        }
        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success(L('Transfer completed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues transferring!'));
        }
	}
	public function history(){
        $userid=userid();
	    if (!$userid) {
             redirect(U('Login/login'));
        }
	
		$where=array('userid'=>$userid);

		$Model = M('Transfer');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
		
        $show = $Page->show();

		
		$transfers=$Model->table('codono_transfer')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('transfers',$transfers);
		$this->assign('page', $show);
		$this->display();
	}

	private function findCardInfo($id){
		$giftcard_images=$this->giftcard_images();
		foreach($giftcard_images as $giftcard_image){
			if($id==$giftcard_image['id']){
				return $giftcard_image;
			}
		}
		return false;
	}
    public function giftcard(){
	    $userid=userid();
		if (!userid()) {
                        redirect(U('Login/login'));
        }
		
		$mo=M();
		$cards=$mo->table('codono_giftcard')->where(array('owner_id' => $userid))->select();
		$giftcard_images=$this->giftcard_images();
		
		foreach($cards as $card){
			
			$card_info=$this->findCardInfo($card['card_img']);
			$card['card_img']=$card_info['image'];
			$card['gc_title']=$card_info['title'];
			$card['secret_code']=cryptString($card['secret_code'],'d');
			if($card['status']==1){
					$mycards[]=$card;
			}
			if($card['status']==2){
					$spentcards[]=$card;
			}
		}
		$this->assign("spentcards",$spentcards);
		$this->assign("mycards",$mycards);
        $this->display();
    }

    /**
     *Interface to create cards
     */
    public function giftcardcreate(){
		if (!userid()) {
                        redirect(U('Login/login'));
        }
        $userid=userid();
		$user_balance=M('UserCoin')->where(array('userid' => $userid))->find();
		$coins=C("coin_safe");
		foreach($coins as $coin){
			$list[$coin['name']]=$coin;
			$list[$coin['name']]['balance']=$user_balance[$coin['name']];
		}
		$nonce=time();
		$giftcard_images=$this->giftcard_images();
		$this->assign("nonce",$nonce);
		$this->assign("giftcard_images",$giftcard_images);
		$this->assign("coins",$list);
		$this->assign("user_balance",$user_balance);
		$this->display();
	}

    /**
     *query to check value of card value
     */
    public function check(){
        $userid=userid();
        if (!$userid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $secret = I('request.secret', null, 'string');
        if($secret==null){
            $this->error('Invalid Giftcard');
        }
		$mo=M();
		$secret_code=cryptString($secret);
		$card_details= $mo->table('codono_giftcard')->where(array('secret_code' => $secret_code))->find();
		$cardid=$card_details['id'];
		if(!$card_details || $card_details['value'] <=0 || !$card_details['id']){
			$this->error("Invalid Gift card");
		}
		$coin=strtoupper($card_details['coin']);
		$coind=$coin.'d';
		$amount=NumToStr($card_details['value']);
		$isValidCoin=$this->isValidCoin($coin);
		
        if(!$coin || !$isValidCoin){
            $this->error('Invalid coin');
        }
		if($card_details['status']==2){
            $this->error('Card valued '.$coin.' '.$amount.' has been used on '.addtime($card_details['usetime']));
        }
		
        $this->success('Card value is '.$coin.' '.$amount);
	}
	/**
     * redeem a card code via its cardcode parameter.
     * if its
     */
    
	 public function redeem(){
        if (!userid()) {
                        $this->error(L('PLEASE_LOGIN'));
        }
		$secret = I('request.secret', null, 'string');
        if($secret==null){
            $this->error('Invalid Giftcard');
        }
		$this->doredeemCard($secret);
	}
    
	//This is authorized action 
	private function doredeemCard($secret){
		if (!userid()) {
                        $this->error(L('PLEASE_LOGIN'));
        }
		$userid=userid();
		$mo=M();
		$secret_code=cryptString($secret);
		$card_details= $mo->table('codono_giftcard')->where(array('secret_code' => $secret_code,'status'=>1))->find();
		$cardid=$card_details['id'];
		if($card_details['value'] <=0 || !$card_details['id']){
			$this->error("Invalid Gift card");
		}
		$coin=strtolower($card_details['coin']);
		$coind=$coin.'d';
		$amount=$card_details['value'];
		$isValidCoin=$this->isValidCoin($coin);
		
        if($coin==null || !$isValidCoin){
            $this->error('Invalid coin');
        }
		
		$before_balance= $mo->table('codono_user_coin')->where(array('userid' => $userid))->field(array($coin,$coind))->find();
	
		$rs=array();
		

        
        $mo->startTrans();
		$rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setInc($coin, $amount);
		$rs[]= $gc_id=M('Giftcard')->where(array('id' => $cardid))->save(array('consumer_id'=>$userid,'status' => 2,'usetime'=>time()));
		if(!$gc_id){
            $mo->rollback();
            $this->error(L('There were issues redeeming giftcard!'));
        }
		$after_balance= $mo->table('codono_user_coin')->where(array('userid' => $userid))->field(array($coin,$coin.'d'))->find();
		
		$finance_hash=md5(ADMIN_KEY.userid().$cardid.time());
		$rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $coin, 'num_a' => $before_balance[$coin], 'num_b' => $before_balance[$coind], 'num' => $before_balance[$coin] + $before_balance[$coind], 'fee' => $amount, 'type' => 1, 'name' => 'giftcard', 'nameid' => $gc_id, 'remark' => 'GiftCard Redeem', 'mum_a' => $after_balance[$coin], 'mum_b' => $after_balance[$coind], 'mum' => $after_balance[$coin] + $after_balance[$coin], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));
		 if (check_arr($rs)) {
            $mo->commit();
            
            $this->success(L('Giftcard Redeemed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues redeeming giftcard!'));
        }
		
	}

	public function doBuy(){
        if (!userid()) {
                        $this->error(L('PLEASE_LOGIN'));
        }
		$userid=userid();
        $coin = strtolower(I('request.coin', 'usdt', 'string'));
		$amount = I('request.amount', 0.00, 'float');
		$nonce = I('request.nonce', 0.00, 'float');
		$bannerid = I('request.bannerid', 0.00, 'float');
		$giftcard_images=$this->giftcard_images();
		foreach($giftcard_images as $gcimg){
			
			if($gcimg['id'] == $bannerid){
				$banner_img=$gcimg['image'];
			}
		}
		
		if(!$banner_img){
			$banner_img=end($giftcard_images)['card_img'];
		}
		
		$isValidCoin=$this->isValidCoin($coin);
		
        if($coin==null || !$isValidCoin){
            $this->error('Invalid coin');
        }
		if($amount==null || $amount<=0){
            $this->error('Invalid amount'.$amount);
        }
		$coind=$coin.'d';
		
		$this->isUniqueBuy($nonce);
		
		$mo=M();
		
		$before_balance= $mo->table('codono_user_coin')->where(array('userid' => userid()))->field(array($coin,$coin.'d'))->find();
		if($before_balance[$coin]<$amount){
			$this->error('Insufficient balance');
		}
		$rs=array();
        $mo->startTrans();
		
		$rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $amount);
		$secret_code=cryptString(cardGenSecret());
		$public_code=cardGenPublic($userid);
		$gc_entry=array('owner_id'=> userid(), 'coin'=>$coin, 'card_img'=>$bannerid, 'public_code'=>$public_code, 'secret_code'=>$secret_code, 'value'=>$amount, 'nonce'=>$nonce,'addtime'=>time(), 'status'=>'1');
		
		$rs[]= $gc_id=$mo->table('codono_giftcard')->add($gc_entry);
		if(!$gc_id){
            $mo->rollback();
            $this->error(L('There were issues buying giftcard!'));
        }
		$after_balance= $mo->table('codono_user_coin')->where(array('userid' => userid()))->field(array($coin,$coin.'d'))->find();
		
		$finance_hash=md5(ADMIN_KEY.userid().$gc_id.time());
		$rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $coin, 'num_a' => $before_balance[$coin], 'num_b' => $before_balance[$coind], 'num' => $before_balance[$coin] + $before_balance[$coind], 'fee' => $amount, 'type' => 2, 'name' => 'giftcard', 'nameid' => $gc_id, 'remark' => 'GiftCard Buy', 'mum_a' => $after_balance[$coin], 'mum_b' => $after_balance[$coind], 'mum' => $after_balance[$coin] + $after_balance[$coin], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));
		 if (check_arr($rs)) {
            $mo->commit();
            
            $this->success(L('Giftcard purchased!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues buying giftcard!'));
        }
	}
	private function isValidCoin($coin){
        $coins=C('coin_safe');
		
		if (array_key_exists(strtolower($coin),$coins))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	private function isUniqueBuy($nonce){
        $userid=userid();
		if (!check($nonce, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
		$mo=M();
		$if_exists= $mo->table('codono_giftcard')->where(array('owner_id' => $userid,'nonce'=>$nonce))->find();
		if($if_exists){
			$this->error("Already made a purchase, Or refresh the page");
		}
	}
	private function giftcard_images(){
        $data = (APP_DEBUG ? null : S('giftcard_images'));
		if (!$data) {	
		$data=M('GiftcardImages')->where(array('status' => 1))->order("id desc")->limit(100)->select();
		}
		return $data;
	}

    /**
     * @param $userid
     * @param string $coin
     * @param $mo
     * @return array
     */
    private function sub_transfer($userid, string $coin, $mo): array
    {
        $condition = array('account' => 1, 'uid' => $userid, 'coin' => $coin);
        $result = $mo->table('codono_user_assets')->where($condition)->find();

        if ($result == 0 || $result == null) {
            $condition_add = $condition;
            $condition_add['created_at'] = time();
            $mo->table('codono_user_assets')->add($condition_add);
            $result = $mo->table('codono_user_assets')->where($condition)->find();
        }
        if ($result == 0 || $result == null) {
            $mo->rollback();
            $this->error(L('There were some issues moving funds to p2p!'));
        }
        return array($condition, $result, $condition_add);
    }
}