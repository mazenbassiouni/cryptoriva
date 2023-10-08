<?php
/*
Table:codono_investbox
Status >>> 0=submitted,1=approved,2=reject,3=completed,4=upcoming
Period >>> 1=daily,7=weekly,30=monthly
Action >>> $json='{"coin":{"name":"btc","value":"1.5"},"market":{"name":"btc_usd","buy":"5","sell":"6"}}'; it is defined function is 
*/
namespace Home\Controller;

use Think\Page;

class InvestController extends HomeController
{
	// Enable disable invest controller
	const ENABLE_INVEST_CONTROLLER = 1;  
	const CREATOR_MAX_ALLOWED = 3;  // Maxmium allowed invest box by a creator
	const ALLOWED_PLANS = array(array('value'=>'1','name'=>'Daily'),array('value'=>'7','name'=>'Weekly'),array('value'=>'30','name'=>'Monthly'));  // Payment method/ Frequency
	const DICE_ENABLE =1;
	const DICE_COIN = 'ltc';  // Define a coin to be rolled
	const ALLOWED_BIDS = array('0.01','0.2','0.1','0.2','0.5','1');  // Define a coin to be rolled
	
	const DICE_PROFIT = '100';  // PERCENTAGE TO BE AWARDED IF USER WINS IN DICE ROLL
	const PROBABILITY = '35';  // winning probability index Do not keep below 10
	public function _initialize()
    {
		if(INVEST_ALLOWED==0){
		die('Unauthorized!');
		}
	//Exit on Investments are disable								 
		if(self::ENABLE_INVEST_CONTROLLER==0){
		$this->assign('type', 'Oops');
        $this->assign('error', 'Oops, Currently Investments are disabled!');
        $this->display('Content/error_specific');
		exit;
		}
		parent::_initialize();
		$Market =C('market') ;
        $market_list=array();
        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }
		
        $this->assign('market_list', $market_list);

		$coin_list = D('Coin')->get_all_name_list();
		$this->assign('DICE_ENABLE',self::DICE_ENABLE);
		$this->assign('coin_list',$coin_list);		
		$this->assign('basecoin',self::DICE_COIN);
		$this->assign('allowed_plans',self::ALLOWED_PLANS);
		$this->assign('allowed_bids',self::ALLOWED_BIDS);
	}
/**************************INVEST CODE START **************************/		
	public function index()
    {
		$Model = M('Investbox');
		$where=array();
		$where['status'] = array('eq', 1); //enable to show only active investment boxes
		$list = (APP_DEBUG ? null : S('investbox_list'));
		if (!$list) {
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $lists = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		}
		foreach($lists as $list){
			$list['period']=json_decode($list['period'],true);
			$list['img']=C('Coin')[$list['coinname']]['img'];
			$list['balance']=$this->coinBalance($list['coinname']) ;
			$all_list[]=$list;
		}
		
		
		
		$this->assign('list', $all_list);
        $this->assign('page', $show);
		$this->display();
    }
	private function coinBalance($coinname){
		$coinname=strtolower($coinname);
		
		if($coinname!=C('coin')[$coinname]['name']){
			return 0;
		}
		$Model = M('UserCoin');
        return $Model->where(array('userid'=>userid()))->getField($coinname);
	}
	public function listinvest()
    {
		if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
		$Model = M('InvestboxLog');
		$where['userid'] = array('eq', userid());
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $lists = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
		foreach($lists as $list){
			$invest_info=$this->investinfo($list['boxid']);
			$list['coinname']=$invest_info['coinname'];
			$list['percentage']=$invest_info['percentage'];
			$list['minvest']=$invest_info['minvest'];
			$list['maxvest']=$invest_info['maxvest'];
			//$list['period']=$list['period'];
			$list['allow_withdrawal']=$invest_info['allow_withdrawal'];
		//	$list['action']=$invest_info['action'];
			$list['boxstatus']=$invest_info['status'];
			$all_info[]=$list;
		}
		
		$this->assign('list', $all_info);
        $this->assign('page', $show);
		$this->display();
    }
	private function investinfo($id){
		$Model = M('Investbox');
		$where['id'] = $id;
        return $Model->where($where)->find();
	}
	public function withdraw(){
		if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
		$Model = M('InvestboxLog');
		$where['docid'] =$docid= I('get.docid','','text');
		$where['userid'] = userid();
		$investboxlog = $Model->where($where)->find();
		if(!is_array($investboxlog) || $investboxlog['docid'] != $docid || $investboxlog['userid']!=userid() || $investboxlog['status']!=1 || format_num($investboxlog['credited'],8)>0 )
			
		{
			$this->error('No such record found');
			
		}

		$invest_info=$this->investinfo($investboxlog['boxid']);

		if( $invest_info['allow_withdrawal'] !=1 )
		{
		
			$this->error('This record will be processed upon maturity!');
		}
		if($investboxlog['id']>0){
			 $this->investshow(D('Investbox')->withdraw($investboxlog['id']));
		}else{
			$this->error('No such record');
		}
		
	}
	public function makeinvest($id,$amount,$period)
	{
		$amount=(double)$amount;
		if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
		if (!check($id, 'd')) {
		      $this->error('Incorrect Investbox');
		}	
		if (!check($amount, 'double')||$amount <=0) {
                $this->error('Incorrect amount:'.$amount);
        }
		
		//@todo check period
		$Model = M('Investbox');
		$where['id'] = $id;
		$investbox = $Model->where($where)->find();
		$in_period=json_decode($investbox['period'],true);
		
		if(!in_array($period,$in_period) ){
			    $this->error('Invalid Lock Period');
		}
		if($amount > $investbox['maxvest'] || $amount < $investbox['minvest'] ){
			    $this->error("Keep amount between ".($investbox['minvest']*1)." and ".($investbox['maxvest']*1));
		}
		
		$user_coin = $this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();
		if ($user_coin[$investbox['coinname']] < $amount) {
                $this->error(L('INSUFFICIENT') . C('coin')[$investbox['coinname']]['title']);
        }
		$result=$this->ifactionFulfill($investbox['action'],userid());
		if($result['status']==0){
	        $this->error($result['message']);
		}
		//Check if there is active investment by user
		
		$where['boxid'] = $id;
		$where['userid'] = userid();
		$where['status'] = 1;
	
		$investboxlog = M('InvestboxLog')->where($where)->find();
		if($investboxlog['userid']==userid()){
	      $this->error('You have already an active investment with docid:'.$investboxlog['docid']);
		}
		//Check active investment ends
		
		//Investment area
		$coinname=strval($investbox['coinname']);
		$coinnamed= $investbox['coinname'].'d';
		$userid=(integer)userid();
		
		$mo = M();
        
        $mo->startTrans();
		$rs = array();

		$query="SELECT `$coinname`,`$coinnamed` FROM `codono_user_coin` WHERE `userid` = $userid";
		$res_bal=$mo->query($query);
		$user_coin_bal = $res_bal[0];
		
		$docid=$id.'IB'.$userid.tradeno();
		
		$begintime=time();
		$conv_period='+'.$period.' days';
		
		$endtime=strtotime($conv_period, $begintime);
		/*Calculating Maturity */
		$daily_interest_per=bcdiv($investbox['percentage'],365,8);
		$total_percent_receivable=bcmul($daily_interest_per,$period,8);
		$divisible=bcdiv($total_percent_receivable,100,8);
		$total_percent=bcadd(1,$divisible,8);
		
		$maturity=bcmul($amount ,$total_percent,8);
		/*Calculating Maturity */
		
		
		$mum_a=bcsub($user_coin_bal[$coinname],$amount,8);
		$mum_b=$user_coin_bal[$coinnamed];
		
		$num=bcadd($user_coin_bal[$coinname],$user_coin_bal[$coinnamed],8);
		$mum=bcadd($mum_a,$user_coin_bal[$coinnamed],8);
		
		$insert_array=array('boxid'=>$id,'userid' => userid(),'docid'=> $docid,'period'=>$period,'amount' => $amount, 'begintime' =>$begintime, 'endtime' => $endtime,'status'=>1,'maturity'=>$maturity);
		$rs[] = $invest_insert=$mo->table('codono_investbox_log')->add($insert_array); 
		
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coinname, $amount);
		
		$rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $coinname, 'num_a' => $user_coin_bal[$coinname], 'num_b' => $user_coin_bal[$coinnamed], 'num' => $num , 'fee' => $amount, 'type' => 2, 'name' => 'investbox', 'nameid' => $invest_insert, 'remark' => 'InvestBox'.$invest_insert,  'move' => $invest_insert, 'addtime' => time(), 'status' => 1,'mum'=>$mum,'mum_a'=>$mum_a,'mum_b'=>$mum_b));
		
		if (check_arr($rs)) {
            $mo->commit();
            
			$this->success(('Investment successful Docid is !'.$docid));
            
        } else {
            $mo->rollback();
            $this->error(L('We could not add your investment!'));
        }

	}

    public function createinvest($coinname, $minvest=0, $period=0, $maxvest=0, $percentage=0, $coin=null, $balance=0, $market=0, $buytrades=0, $selltrades=0)
	{
		if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
		if (!check($coinname, 'n')) {
            $this->error('Select Proper Coinname');
        }
		if ($coin && !check($coin, 'n')) {
            $this->error('Select Proper Coin');
        }
		if (!check($minvest, 'double')) {
			$this->error(L('Enter Proper minvest!'));
		}
		if (!check($maxvest, 'double')) {
			$this->error(L('Enter Proper maxvest!'));
		}
		if (!check($percentage, 'double')) {
			$this->error(L('Enter Proper percentage!'));
		}
		 if ($market && !check($market, 'market')) {
			 $this->error(L('Select Proper Market!'));
		 }
		if ($buytrades && !check($buytrades, 'd')) {
			$this->error(L('Enter Proper Buy trades number!'));
		}
		if ($selltrades && !check($selltrades, 'd')) {
			$this->error(L('Enter Proper Sell trades number!'));
		}
		//$Model = M('Investbox');
		
		//$investbox = $Model->where($where)->find();
		if($maxvest < $minvest ){
			    $this->error('Check Proper Minvest and Maxvest Valye');
		}

		//Check if there is pending investbox by user
		$addtime=time();
		$coinname=strval($coinname);
		$coin=strval($coin);
		$percentage=(double)$percentage;
		$minvest=(double)$minvest;
		$maxvest=(double)$maxvest;
		$period=(integer)$period;
		

		$where['userid'] = userid();
		$where['status'] = 0;
		$number_of_user_investbox = M('Investbox')->where($where)->count();
		if($number_of_user_investbox>=self::CREATOR_MAX_ALLOWED){
	      $this->error('You have already :'.$number_of_user_investbox.' pending please wait for them to be reviewed');
		}
		//Check if there is pending investbox by user ends
		
		//Investment area

		if($coin && $balance>0){
			
		$action['coin'] = array('name' => $coin, 'value' => $balance);
		}
		if($market){
        $action['market']['name'] = $market;
			if($buytrades>0){
				$action['market']['buy'] = $buytrades;
			}
			if($selltrades>0){
				$action['market']['sell'] = $selltrades;
			}
		}
		$userid=(integer)userid();

		$mo = M();
        
        $mo->startTrans();
		$rs = array();

		$insert_array=array('coinname'=>$coinname,'percentage'=>$percentage,'period'=> $period,'minvest' => $minvest, 'maxvest' =>$maxvest,'creatorid' => $userid, 'addtime' => $addtime,'status'=>0);
		if(is_array($action)){
		$actionjson=json_encode($action);
		$insert_array['action']=$actionjson;
		}
		
		$rs[] = $mo->table('codono_investbox')->add($insert_array);
			
		if (check_arr($rs)) {
            $mo->commit();
            
			$this->success(L('Investment Plan has been created! Please wait for the review'));
            
        } else {
            $mo->rollback();
            $this->error(L('We could not add your investment plan!'));
        }

	}
	public function investshow($rs = array())
    {
        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

	private function ifactionFulfill($action_json,$userid){
		$actionchecker=json_decode($action_json);
		$res['status']=1;
		if((integer)$actionchecker->noaction==1){
			$res['status']=1;
			$res['message']="There are no action required";
		}
		
		//Balance Check
		if((integer)$actionchecker->coin->value>0){
			$user_coin = M('UserCoin')->where(array('userid' => $userid))->getField(strval($actionchecker->coin->name));
			if($user_coin>$actionchecker->coin->value){
			$res['status']=1;
			$res['message']="You have sufficient balance, Required is ".$actionchecker->coin->value.$actionchecker->coin->name;
			}
			else{
			$res['status']=0;
			$res['message']="You have dont sufficient balance, Required is ".$actionchecker->coin->value.$actionchecker->coin->name;
			return $res;
			}
		}
		$market=$actionchecker->market->name?:"NONEMARKET";
		$buy_trade_count=M('TradeLog')->where(array('userid' => $userid,'status'=>1,'type'=>1,'market'=>$market))->count();
		
		
		//Buy Trades Check
		if((integer)$actionchecker->market->buy>$buy_trade_count){
			$market=strval($actionchecker->market->name);
			
			if($buy_trade_count>$actionchecker->market->buy){
			$res['status']=1;
			$res['message']="You have done sufficient buy trades, Required is ".$actionchecker->market->buy.$actionchecker->market->name;
			}
			else{
			$res['status']=0;
			$res['message']="You dont not sufficient buy trades [$buy_trade_count], Required is ".$actionchecker->market->buy.' '.$actionchecker->market->name;
			return $res;
			}
		}
		$sell_trade_count = M('TradeLog')->where(array('userid' => $userid,'status'=>1,'type'=>2,'market'=>$market))->count();
		//Sell Trades Check
		if((integer)$actionchecker->market->sell>$sell_trade_count){
			$market=strval($actionchecker->market->name);
			
			if($sell_trade_count>$actionchecker->market->sell){
			$res['status']=1;
			$res['message']="You have done sufficient sell trades, Required is ".$actionchecker->market->sell.$actionchecker->market->name;
			}
			else{
			$res['status']=0;
			$res['message']="You dont not sufficient sell trades [$sell_trade_count], Required is ".$actionchecker->market->sell.' '.$actionchecker->market->name;
			return $res;
			}
		}
		return $res;
	}
/**************************INVEST CODE ENDS **************************/	
/**************************DICE CODE STARTS **************************/
	public function dicerolls()
    {
		if(self::DICE_ENABLE==0){die('ACCESS DENIED');}
		if (userid()) {
		$where=array('userid'=>userid());
		$Model = M('Dice');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		}else{
			$list=array();
		}
		$user_bal=$this->usercoins[strtolower(self::DICE_COIN)];
		$this->assign('user_bal', $user_bal);
		$this->assign('list', $list);
        $this->assign('page', $show);
		$this->display();
    }
	public function highlow($call,$amount,$time){
		$result=0;
	    if(self::DICE_ENABLE==0){die('ACCESS DENIED');}
		$coinname=self::DICE_COIN;
		$coinnamed=$coinname.'d';
		if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
		if (!check($call, 'a')) {
            $this->error('Incorrect call, Choose either High or Low');
        }
		if (!check($time, 'd')) {
		   $this->error('Please refresh the page or try again later');
		}
		
		if(!in_array($amount,self::ALLOWED_BIDS)){
			$this->error($amount .' is not an allowed amount');
		}
		if($call!='low'){
			$call='high';
		}
		$this->ifAllowedToDice($coinname,$amount,$time);
		
		$number=$this->probability($call);
		
		if($call=='low' && $number<48){
			$result=1;
		}
		if($call=='high' && $number>52){
			$result=1;
		} 
		if($call=='high' && $number<53){
			$result=2;
		}
		if($call=='low' && $number>47){
			$result=2;
		}
		$winamount=0;
		if($result==1){$winamount=$amount* (1+(self::DICE_PROFIT/100));}
	
		$mo = M();
        $mo->startTrans();
		$rs = array();
		$userid=userid();
		$query="SELECT `$coinname`,`$coinnamed` FROM `codono_user_coin` WHERE `userid` = $userid";
		$res_bal=$mo->query($query);
		$user_coin_bal = $res_bal[0];


		$insert_array=array('call'=>$call,'userid' => userid(),'number'=>$number,'result'=> $result,'amount' => $amount, 'addtime' =>$time,'coinname'=>$coinname,'winamount'=>$winamount);
	
		if($result==1){
			$mum_a=bcadd($user_coin_bal[$coinname],$amount,8);
		}
		else{
			$mum_a=bcsub($user_coin_bal[$coinname],$amount,8);
		}
		
		$mum_b=$user_coin_bal[$coinnamed];
		$num=bcadd($user_coin_bal[$coinname],$user_coin_bal[$coinnamed],8);
		$mum=bcadd($mum_a,$user_coin_bal[$coinnamed],8);
		$rs[] = $dice_roll=M('Dice')->add($insert_array); 
		
		if($result==1){
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setInc($coinname, $amount);
		}else{
		$rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coinname, $amount);	
		}
		$finance_update_array=array('userid' => userid(), 'coinname' => $coinname, 'num_a' => $user_coin_bal[$coinname], 'num_b' => $user_coin_bal[$coinnamed], 'num' => $num , 'fee' => $amount, 'type' => $result, 'name' => 'DiceRoll', 'nameid' => $dice_roll, 'remark' => 'Dice:'.$dice_roll,  'move' => $dice_roll, 'addtime' => time(), 'status' => 1,'mum'=>$mum,'mum_a'=>$mum_a,'mum_b'=>$mum_b);
		$rs[] =$mo->table('codono_finance')->add($finance_update_array);
		
		if (check_arr($rs)) {
            $mo->commit();
            
				if($result==1){
					$this->success("Call was $call , So You win:".$number);
				}else{
					$this->error("Call was $call , So You loose:".$number);
				}		
            
        } else {
            $mo->rollback();
            $this->error(L('Sorry we could not roll the dice!'));
        }
	}
	private function ifAllowedToDice($coinname,$amount,$time){
		if(self::DICE_ENABLE==0){die('ACCESS DENIED');}
		$balance_coin = $this->usercoins[$coinname];//M('UserCoin')->where(array('userid' => userid()))->getField($coinname);
		if($balance_coin<$amount){
			$this->error('You have low balance!');
		}
		$time = M('Dice')->where(array('userid' => userid(),'addtime'=>$time))->getField('addtime');
		if($time>0){
			$this->error('Too fast, Refresh the page!'.$time);
		}
		
	}
	private function probability($call): int
    {
		
	$probability=self::PROBABILITY;
        $num=$this->calc();
        if($call=="high"){
            $num=$num-(50-$probability);
		}else{
            $num=$num+(50-$probability);
		}
		return $this->trick($num);
	}
	private function trick($num)
	{

			if($num>50)
			$number=mt_rand(51, 100);
			else
			$number=mt_rand(1, 50);
			return $number;
	}
	
	private function calc()
	{
	 $number=mt_rand(1, 100);
		if($number>47 && $number <53){
			$this->calc();
		}
		return $number;
	}
	

	//Display functions
	public function bet()
    {
		if(self::DICE_ENABLE==0){die('ACCESS DENIED');}
       $this->display();
    }
	public function history()
    {
		if(self::DICE_ENABLE==0){die('ACCESS DENIED');}
       $this->display();
    }
}