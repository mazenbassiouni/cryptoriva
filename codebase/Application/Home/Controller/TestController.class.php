<?php

namespace Home\Controller;
use Common\Ext\Substrate;
use Think\Exception;
use Common\Design\Common\ModelHandle;
use Org\Util\Stringer;
/**
 * Js compression class
 * Class MinController
 * @package Report\Controller
 */
class TestController extends HomeController
{
    public function __construct(){
        parent::__construct();
        if(!M_DEBUG){
            //exit('This is only for development stage');
        }
    }
    public function blockgum(){
        $chain='bnb';
        $blockgum=blockgum($chain);
        $info=$blockgum->createAddress(2);
        var_dump($info);
    }
	public function testTime(){
        

// set the session cookie lifetime to 1 hour (3600 seconds)
session_set_cookie_params(3600);

// start the session
session_start();

// set a session value
session('yoyo', 'gogo');

// output the session value and expiration time
var_dump(session('yoyo'));
echo "Session expiration time: " . session_cache_expire() . " minutes";
		
	}
	public function seT(){
        $new= Stringer::randString(40);
        $hash = md5($new . '_' . time() . mt_rand(1, 1000000));
        $var=session('token_user', $hash);

		var_dump(session('token_user'));
	}
    public function check(){
        //Login being Made
        $code=I('get.code');
        if($code && 1==0){
            $user_exist = M('User')->where(array('id' =>38))->find();
            if(is_array($user_exist) && !empty($user_exist)){
                A("Login")->processLogin($user_exist,'Web-LOGIN');
            }else{
                $this->error('Could not be authenticated');
            } 

        }else{
            $this->error('Could not be authenticated');
        }
    }
	public function testFinance(){
		$userid=1;
		$coin='btc';
		$amount=0.5;
		$type=1;
        $mo=M();
        $mo->startTrans();
		                    echo ModelHandle::AddFinance($userid,$coin, $amount,$type, 'OTC',22,'This is test Finance');
							echo ModelHandle::UpdateBalance($userid,$coin,$amount,$type);
        $mo->commit();
	}
    public function substrate_createAccount($uid){
           $uid=$uid?$uid:-1;
        $cc=C('coin')['dot'];

        $config=[
            'host'=>$cc['dj_zj'],
            'port'=>$cc['dj_dk'],
            'api_key'=>cryptString($cc['dj_mm'],'d'),
            'decimals'=>$cc['cs_qk'],
            ];

        $substrate=Substrate($config);
        $newAdd=json_decode($substrate->createAddress($uid));
            var_dump($newAdd->uid);

    }
    public function substrate_getDeposits(){

        $cc=C('coin')['dot'];

        $config=[
            'host'=>$cc['dj_zj'],
            'port'=>$cc['dj_dk'],
            'api_key'=>cryptString($cc['dj_mm'],'d'),
            'decimals'=>$cc['cs_qk'],
        ];

        $substrate=Substrate($config);
        $deposits=json_decode($substrate->getDeposits(),true);

        foreach($deposits as $deposit){

            //$mark=$substrate->markDepositAsRecorded($deposit['tx_hash']);
            //var_dump($mark);
        }
    }
    public function substrate_withdrawal(){

        $cc=C('coin')['dot'];

        $config=[
            'host'=>$cc['dj_zj'],
            'port'=>$cc['dj_dk'],
            'api_key'=>cryptString($cc['dj_mm'],'d'),
            'decimals'=>$cc['cs_qk'],
        ];

        $substrate=Substrate($config);
        $substrate_amount=$substrate->amount_encode(0.001);
        $address='5HCBgQHimMKXt8vowoRtWNFVZMntGtnt83dLV2h8r6RZeT3b';
        $order_id=99;
        $request_sent=json_decode($substrate->withdraw($address,$substrate_amount,$order_id),true);
        var_dump($request_sent);

    }

    public function deposit_test(){
		$Info=D('Coin')->depositCoin(['ok'=>'hi']);
		var_dump($Info);
	}
    /*
         * transactionRequestStatus =created then save transactionRequestId in myzc->memo
         * and on callback check referenceId as myzc->memo , check if response has
         * data->product->WALLET_AS_A_SERVICE
         * and
         * data->event->TRANSACTION_REQUEST_BROADCASTED
         * and
         * data->item->transactionId
         * if transactionId is there then update in myzc -> txid , hash where memo is referenceId
     */
    public function cryptoapi_withdrawal(){
        $cryptoapi=CryptoApis([]);
        $blockchain='eth';
        $walletId="62833c373402c90007468fd4";
        $context=2;
        $main_address="0xdcc72fab5b34d0edd4be486966ba76ad78b6441e";
        $to_address="0x9ed9A10D4D62Fac35647f24591C18496d83a12E6";
        $amount=0.0124;
        $tx_note="A634635";
        $info=$cryptoapi->withdraw($blockchain,$walletId,$main_address,$to_address,$amount,$tx_note,$context);
        var_dump($info);

    }
    public function cryptoapi_withdrawal_btc(){
        $cryptoapi=CryptoApis([]);
        $blockchain='btc';
        $walletId="62833c373402c90007468fd4";
        $context=2;
        $main_address="";
        $to_address="tb1q43c80eq2fjqhvd374h0ahchncw9fe7j5z5lfw2mqhd7tntt5vfpqmjuyhv";
        $amount=0.00001;
        $tx_note="A634636";
        $info=$cryptoapi->withdraw($blockchain,$walletId,$main_address,$to_address,$amount,$tx_note,$context);
        var_dump($info);

    }
	public function testSafety(){
		safeLog('ok@123.com',10,'password reset');
		echo "Find";
	}

	public function testUpdateBalance(){
		$Bal=D('UserAssets');
		$userid=38;	//int userid
		$coin='eth'; // coin symbol
		$action='dec'; //inc or dec
		$type='freeze'; //freeze or balance
		$account=2; //2= spot balance
		$amount=2; // decimal
		//change can not be below 0 [no negative balance or freeze allowed , Gives false
		$info=$Bal->updateBalance($userid,$coin,$action,$amount,$type,$account);
		//check if info is true or false 
		var_dump($info);
		
	}
	public function testRedis(){
		echo "<pre>";
		print_r(S('ext_socket'));
		echo "</pre>";
	}
	public function mongo()
    {
		 G('begin');
		$mongo=MongoClient();
		//$filter  = ['name'=>'currencies'];
		$filter = [
               //'market'    => 'btc_usdt',
               //'type' =>'120',
          ];
		  $options =[
              // 'sort' => ['_id' => -1],
			   //'limit'=>100,
			   'projection'=>['id'=>1,'addtime'=>1,'market'=>1],
              'sort' => [
					'addtime' => -1 //+1 asc , -1 desc
				],
          ];
//$options = ['sort'=>array('_id'=>-1),'limit'=>3]; # limit -1 from newest to oldest
		$resp=$mongo->rawFind('codono_trade_json',$filter,$options);
        echo "<pre>";
		foreach($resp as $res){
		    print_r($res);
        }
        echo "</pre>";
			G('end');
	echo "<br/>Total Time taken " . G('begin', 'end') . 's';
    }
	
	//Coincap.io pricing updates to codono_coinmarketcap table for 100 top coins
    public function cmcUpdate()
    {
        
		S('cmcrates',null);
        S('cmcupdate', null);
        S('home_coin',null);		
        $timestamp = time();
        //Create table if it doesn't exist
        echo "Starting to retrive at:" . $timestamp . "<br/>";
        $request = $this->gcurl('https://api.coincap.io/v2/assets');
        $mo = M();
		$mongo=MongoClient();
		//$filter  = ['name'=>'currencies'];
        if (!isset($request) || $request != null) {

            $response = json_decode($request, true);

            if ($response['data'] && $response['timestamp']) {

                $coins = $response['data'];
                foreach ($coins as $find_coin) {
                    if ($find_coin['symbol'] == 'BTC') {
                        $btc = $find_coin;
                    }
                }

                foreach ($coins as $coin) {
		
                    $info = $mo->table('codono_coinmarketcap')->where(array('symbol' => $coin['symbol']))->find();
					
				$filter = [
					'symbol'    => $coin['symbol'],
						];
				$options =[
					'projection'=>['_id'=>0],
				];

					$m_info=$mongo->rawFind('codono_coinmarketcap',$filter,$options);
					$info=(array)$m_info[0];
				
					
                    if (!$info) {
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        //$if_saved = $mo->table('codono_coinmarketcap')->add($map);
						$if_saved=$mongo->insert('codono_coinmarketcap',$map);
                        echo "Saved status:$if_saved for coin <strong>" . $coin['name'] . "</strong> to DB<br/>";
                        //Add coin required
                        continue;
                    }
                    if ($coin['symbol'] == $info['symbol'] && $info['last_updated'] < $response['timestamp']) {
                        //Update required
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
						$filter=array('symbol' => $info['symbol']);
                        $if_updated = $mo->table('codono_coinmarketcap')->where($filter)->save($map);
						
						$if_saved=$mongo->update('codono_coinmarketcap',$filter,$map);
                        echo "Update status:$if_updated for coin <strong>" . $coin['symbol'] . "</strong> to DB<br/>";
                    }

                }

            } else {
                die('Data,timestamp');
            }
        } else {
            exit('Could not get response');
        }
    }

    public function cmcUpdateRate()
    {
        
        $timestamp = time();
        //Create table if it doesn't exist
        echo "Starting to retrive at:" . $timestamp . "<br/>";
        $request = $this->gcurl('https://api.coincap.io/v2/rates');
        $mo = M();

        if (!isset($request) || $request != null) {

            $response = json_decode($request, true);

            if ($response['data'] && $response['timestamp']) {

                $coins = $response['data'];

                foreach ($coins as $find_coin) {
                    if ($find_coin['symbol'] == 'BTC') {
                        $btc = $find_coin;
                    }
                }
                $all_coins = $mo->table('codono_coinmarketcap')->field('symbol,price_usd,price_btc')->select();
                F('cmcRates', $all_coins);
                foreach ($coins as $coin) {

                    $info = $mo->table('codono_coinmarketcap')->where(array('symbol' => $coin['symbol']))->find();

                    if (!$info) {
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        $if_saved = $mo->table('codono_coinmarketcap')->add($map);
                        echo "Saved status:$if_saved for coin <strong>" . $coin['name'] . "</strong> to DB<br/>";
                        //Add coin required
                        continue;
                    }

                    if ($coin['symbol'] == $info['symbol']) {
                        //Update required
                        $timestamp = time() * 1000;

                        $map = $this->rateCoinCap($coin, $timestamp, $btc);

                        $if_updated = $mo->table('codono_coinmarketcap')->where(array('symbol' => $info['symbol']))->save($map);
                        echo "Update status:$if_updated for coin <strong>" . $coin['symbol'] . "</strong> to DB<br/>";
                    }

                }

            } else {
                die('Data,timestamp');
            }
        } else {
            exit('Could not get response');
        }
    }

    private function mapCoinCap($info, $timestamp, $btc = array())
    {

        $increament_percetage = 0;
        $coin['id'] = $info['id'];
        $coin['name'] = $info['name'];
        $coin['symbol'] = $info['symbol'];
        $coin['rank'] = $info['rank'];
        if ($btc['priceUsd'] > 0) {
            $coin['price_btc'] = format_num($btc['priceUsd'] / $info['priceUsd'], 8);
        }
        $coin['price_usd'] = format_num(($info['priceUsd'] * (100 + $increament_percetage)) / 100, 8);
        $coin['total_supply'] = format_num($info['supply'], 0);
        $coin['max_supply'] = format_num($info['maxSupply'], 0);
        $coin['available_supply'] = format_num($info['maxSupply'], 0);
        $coin['percent_change_24h'] = format_num($info['changePercent24Hr'], 2);
        $coin['24h_volume_usd'] = format_num($info['volumeUsd24Hr']);
        $coin['market_cap_usd'] = format_num($info['marketCapUsd'], 0);
        $coin['last_updated'] = $timestamp;
        return $coin;
    }

    private function rateCoinCap($info, $timestamp, $btc = array())
    {

        $increament_percetage = 0;
        $coin['id'] = $info['id'];
        //$coin['name']=$info['name'];
        $coin['symbol'] = $info['symbol'];
        //$coin['rank']=$info['rank'];

        if ($btc['rateUsd'] > 0) {
            $coin['price_btc'] = format_num($btc['rateUsd'] / $info['rateUsd'], 8);
        }
        $coin['price_usd'] = format_num(($info['rateUsd'] * (100 + $increament_percetage)) / 100, 8);
        /*	$coin['total_supply']=format_num($info['supply'],0);
            $coin['max_supply']=format_num($info['maxSupply'],0);
            $coin['available_supply']=format_num($info['maxSupply'],0);
            $coin['percent_change_24h']=format_num($info['changePercent24Hr'],2);
            $coin['24h_volume_usd']=format_num($info['volumeUsd24Hr']);
            $coin['market_cap_usd']=format_num($info['marketCapUsd'],0);
            $coin['last_updated']=$timestamp;*/
        return $coin;
    }

    private function gcurl($endpoint, $method = 'GET')
    {
        if (!$endpoint) {
            return "{'error':'No URL'}";
        }
        $call_url = $endpoint;
        $curl = curl_init();
        curl_setopt_array($curl, array(

            CURLOPT_URL => $call_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //return "cURL Error #:" . $err;
            return null;
        } else {
            return $response;
        }
    }


    public function testnet()
    {
        G('begin');
        $tron = TronClient();

        $address = "TJnUN8WBfBBcg6WmB5aJR22doBt4ih4C2W";
        $usdt = "TVoVJjrBpCZGDqru8k8DPjAU3rM6c9wAD8";

        $tokenname = "usdt";
        $btt = 1002000;
        $decimals = 6;

        $hex = "4160b161904e39b9d7e149c84fa1a9d865c402836f";
        $txid = "51de28d2ba62065dd9adfbfae56ef05d4bab43e6a933e18be7c20e11ac77b591";
        $txid_usdt = "6ceeed898a7be9c5faae6bfd4ead5b8e66e573e46d8a84e3a7e98c25b2942ace";
        $txinfo = $tron->getTransactionSimple($txid);
        G('getTransactionSimple');
        echo "<br/>getTransactionSimple Time taken " . G('begin', 'getTransactionSimple') . 's';
        echo "<br/>Printing TXInfo $txid<br/>";
        echo "<pre>";
        print_r($txinfo);
        echo "</pre>";
        //$new_Address=$tron->generateAddress();
        //var_dump($new_Address);

        $abi = json_decode($tron->getAbi($tokenname), true);

        //echo $tron->toAddress($address,'e');
        G('getBalance');
        echo "<br/>getBalance Time taken " . G('getTransactionSimple', 'getBalance') . 's';
        $balance = $tron->getBalance($address);
        var_dump($balance);
        //$trc10bal =$tron->getTrc10Balance($address,$btt,$decimals);

        $trc20bal = $tron->getTrc20Balance($address, $usdt, $decimals, $abi);

        var_dump($trc20bal);
        G('getTrc20Balance');
        echo "<br/>getTrc20Balance Time taken " . G('getBalance', 'getTrc20Balance') . 's';
        $asset = $tron->getAssetsbyname('usdt');
        //var_dump($asset);
        $txinfo = $tron->getTrc20TxByAddress($address, $usdt);
        var_dump($txinfo);
        $deposits = $tron->deposits($address);
        var_dump($deposits);
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
        //echo $tron->isConnected();
    }
	public static function getDepositAddress(){
		$tron = TronClient();
        return $tron->generateAddress();
	}
    public function index()
    {

        $tron = TronClient();
        $address = "TYDzsYUEpvnYmQk4zGP9sWWcTEd2MiAtW6";
        $testnet_address = "TJnUN8WBfBBcg6WmB5aJR22doBt4ih4C2W";
        $testnet_usdt = "TVoVJjrBpCZGDqru8k8DPjAU3rM6c9wAD8";
        $usdt = "TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t";
        $tokenname = "usdt";
        $btt = 1002000;
        $decimals = 6;

        $hex = "41BBC8C05F1B09839E72DB044A6AA57E2A5D414A10";
        $txid = "835b70f77afc6fe2889d7420faa46eb946d71846218e412c74461cb91b420593";
        $txid_usdt = "c642c0fcbc472a0e8a37c1b4797260f5cdc348af01f4b1247f4e859362c9ceb8";
        $address_info = $tron->getTransaction($txid);
        // print_r($address_info);
		echo "Newaddress->";
        $new_Address = $tron->generateAddress();
        var_dump($new_Address);
		echo "<--Newaddress";
        $abi = json_decode($tron->getAbi($tokenname), true);

        echo $tron->toAddress($address, 'e');
        //$balance =$tron->getBalance($address);
        //$trc10bal =$tron->getTrc10Balance($address,$btt,$decimals);
        echo "here";
        $trc20bal = $tron->getTrc20Balance($address, $usdt, $decimals, $abi);

        var_dump($trc20bal);
        $asset = $tron->getAssetsbyname('usdt');
        //var_dump($asset);
        $txinfo = $tron->getTrc20TxByAddress($address, $usdt);
        //var_dump($txinfo);
        $deposits = $tron->deposits($address);
        var_dump($deposits);
        //echo $tron->isConnected();
    }
    public function readByBlock()
    {
        $tron = TronClient();
        $blocks=$tron->getBlockRange(15753300,15753332);
        //$blocks=$tron->getBlock(15753332);
        
        foreach($blocks as $block){
            if(isset($block['transactions'])) {
                $transactions[] = $block['transactions'];
            }
        }
        echo "<pre>";
        var_dump($transactions);
        echo "</pre>";
    }
	public function convHash(){
		$tron= TronClient();
		echo $tron->toAddress('TFnm4oE8usgcvDdcCLwZhoe2Aua2MRRZBL','e');
		$inf=$tron->getTransaction('fd24b85e901c805553d189fd0c273d53452064a4c33957fd96c48415de66231e');
		        echo "<pre>";
        print_R($inf);
        echo "</pre>";

	}

    private function getUserHexAddresses(){
        $user_addresses=array("413fd6f1f7dd43d4d4479959d2961dcb13c5ffbb5b","41ecff55666d99db7065186a3d8e100d15c75b64d0","410c22811755ec2eca943e8b0618b4010011940202");
        return $user_addresses;
    }
    public function testDeposits(){
	    $this->findDeposits(20113310,20113311);
    }
	private function findDeposits($block_from,$block_to){
	    if($block_from>$block_to){
	        return false;
        }
	    echo "Reading blocks from $block_from to $block_to, in total number of  ".($block_to-$block_from)."blocks<br/>";
        G('begin');
        $user_addresses=$this->getUserHexAddresses();
				$tron = TronClient();
		        $blocks=$tron->getBlockRange($block_from,$block_to);
		        $transactions=$this->parseBlocks($blocks);
			
			if(!empty($transactions)){
				foreach($transactions as $tx){
					echo "<pre>";
					print_r($tx);
					echo "</pre>";
					if(in_array($tx['info']['to_address_hex'],$user_addresses)){
						echo "<br/> Found ".$tx['info']['contract_hex']." for user". $tx['info']['contract_hex']." <br/>" ;
						//todo add entry in db 
						$this->processTxDB($tx);
					}
				}
			}
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
        echo "<br/>Total Memory taken " . G('begin', 'end', 'm') . 's';
        return true;
	}
	private function processTxDB($tx){
		$hash=$tx['hash'];
		if(strlen($hash)!=64){
			return false;
		}
		$mo=M(); 
        $result =  $mo->table('codono_tron_hash')->where(array('hash' => $hash))->find();
		if(!is_array($result)){
			$saveData=array('hash'=>$hash,'contract_hex'=>$tx['info']['contract_hex'],'to_address_hex'=>$tx['info']['to_address_hex'],'type'=>$tx['type'],'amount'=>$tx['info']['amount'],'addtime'=>time(),'status'=>$tx['status']);
			 return $mo->table('codono_tron_hash')->add($saveData);
		}{
			return "Already found";
		}
    }
	public function processHash(){
		$mo=M(); 
        $result =  $mo->table('codono_tron_hash')->where(array('confirmed' => 0))->select();
		$tron = TronClient();
		foreach ($result as $res){
			$info=$tron->getTransaction($res['hash']);
			echo "<br/>*******************<br/><pre>";
			print_r($info);
			echo "</pre><br/>*******************<br/>";
		}
	
	}
	
	private function parseBlocks($blocks){
		$transactions=array();

		foreach($blocks as $block){
		    $txs=$block['transactions']; //multiple transactions
					
            if(is_array($txs)) {
				foreach($txs as $tx){
					
					$info=$this->txDetails($tx);
					$transactions[]=$info;
					//if(is_string($to_address) && $tx['ret'][0]['contractRet']=='SUCCESS'){
					/*
					if(is_string($info['to_address'])){
						$transactions[$tx['txID']]=$info['to_address'];
					}
					*/
				}
              
            }
        }
		return $transactions;
	}
	
	private function removeZero($string){
		 $length=strlen($string);
		 var_dump($length);
		for($i=0; $i<64-$length; $i++){
			$string = ltrim($string, '0');
		}
		return $string;
	}
	 function has_prefix($string, $prefix) {
		return substr($string, 0, strlen($prefix)) == $prefix;
	}
	private function trc20Tx($info){
		$tron =TronClient();
		$value=$info['raw_data']['contract']['0']['parameter']['value'];
		if(strlen($value['data'])!=136){
			return false;
		}
		if(!$this->has_prefix($value['data'],'a9059cbb')){
			return false;
		}

		$resp['contract_hex']=$value['contract_address'];
		$resp['contract']=$tron->toAddress($value['contract_address']);
		//$resp['owner_hex']=$value['owner_address'];
		//$resp['owner']=$tron->toAddress($value['owner_address']);
		//$resp['data']=$value['data'];
		$resp['to_address_hex']= $this->addressParse($value['data']);
		$resp['to_address']=$tron->toAddress($resp['to_address_hex'],'d');
		$resp['amount']=hexdec(ltrim(substr($value['data'],72),0));
		return $resp;
	}
	public function testme(){
		$tron =TronClient();
		echo $tron->toAddress('7e4d1a3549db571631a68951615c587277385aba','d');
	}
	public function testnow(){
		echo D('Chain')->generateNewAddress('tron');
	}
	function addressParse($data){
		if(!$this->has_prefix(substr($data,30,42), '41')){
			$hex='41'.substr($data,32,40);
		}else{
			$hex=substr($data,30,42);	
		}
		return $hex;
	}

	private function txDetails($tx){
					$transaction=array('hash'=>$tx['txID'],'status'=>$tx['ret'][0]['contractRet']);
					$type=$tx['raw_data']['contract']['0']['type'];
					switch($type){
						case 'TriggerSmartContract':
						$transaction['type']='trc20';
						$transaction['data']=$tx['raw_data']['contract'][0]['parameter']['value']['data'];
						$transaction['info']=$this->trc20Tx($tx);
						break;
						case 'TransferAssetContract':
						$transaction['type']='trc10';
						break;
						case 'TransferContract':
						$transaction['type']='trx';
						$transaction['info']['to_address_hex']=$tx['raw_data']['contract'][0]['parameter']['value']['to_address'];
						$transaction['info']['amount']=$tx['raw_data']['contract'][0]['parameter']['value']['amount'];
						break;
						
						default:
						$transaction['type']='other';
						$transaction['info']=$type;
				
					}
		return $transaction;
	}
    public function exception()
    {
        try {
            E('Test me this is exception', '6565');
        } catch (Exception $e) {
            echo "OK";
        }
    }

    public function redistest()
    {
		
		
        S('OK',"Redis is Working");

		echo "Hello";
        echo S('OK');
    }

    public function storedProcedure()
    {
        $mo = M();
        G('begin');
        $result = $mo->query("CALL getUsernames()");
        echo "ok";
        var_dump($result);
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
        echo "<br/>Total Memory taken " . G('begin', 'end', 'm') . 's';
    }



    public function BinanceTicker()
    {
        $api = binance();
        try {
            $ticker = $api->prices();
        } catch (\Exception $e) {
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
        } // Make sure you have an updated ticker object for this to work

        var_dump($ticker);
    }
	public function FeeTest(){
		$userid=38;
		$market='btc_usdt';
		echo $this->marketFees($market,$userid,1);
	}
	/*
$type=1 buy 
$type=2 sell
*/
private function marketFees($market,$userid,$type=""){
	$TradeFees = M('TradeFees')->where(array('userid' => $userid,'market'=>$market))->find();
	if($TradeFees['userid']==$userid){
		$buy_fees=$TradeFees['fee_buy'];
		$sell_fees=$TradeFees['fee_sell'];
	}
	else{
		
		$buy_fees=C('market')[$market]['fee_buy'];
		$sell_fees=C('market')[$market]['fee_buy'];
	}
	
	if($type==1){
		return $buy_fees;
	}else{
		return $sell_fees;
	}
}

}