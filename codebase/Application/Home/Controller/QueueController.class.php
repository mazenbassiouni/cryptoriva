<?php

namespace Home\Controller;


class QueueController extends HomeController
{

	public function __construct(){
		parent::__construct();
        checkcronkey();
	}
  
    private function investinfo($id)
    {
        $Model = M('Investbox');
        $where['id'] = $id;
        return $Model->where($where)->find();
    }

    public function checkInvest()
    {
        
        $mo = M();
        $now = time();
        $map['endtime'] = array('lt', $now);
        $map['status'] = 1;
        $logs = M('InvestboxLog')->where($map)->order('id asc')->limit(1000)->select();

        if (!is_array($logs)) {
            echo 'Ok No records to be proceeded';
            exit;
        } else {
            echo count($logs) . " Record/s found to be processed<br/>";

        }
        foreach ($logs as $ibl) {

            if ($ibl['status'] != 1) {
                print_r($ibl);
                echo "Status=" . $ibl['status'];
                continue;
            }

            $userid = $ibl['userid'];

            $rs = array();

            if ($ibl['status'] == 1) {

                $credit = format_num($ibl['maturity'], 8);

                $invest_info = $this->investinfo($ibl['boxid']);
                if ($invest_info == NULL) {
                    $mo->table('codono_investbox_log')->where(array('id' => $ibl['id']))->save(array('status' => 0, 'withdrawn' => time(), 'credited' => $credit));

                    continue;
                }
                if (!$invest_info['coinname']) {
                    echo "Coin Name not found<br/>";
                    continue;
                }
                $coinname = strtolower($invest_info['coinname']);
                $coinnamed = strtolower($invest_info['coinname'] . 'd');


                $query = "SELECT `$coinname`,`$coinnamed` FROM `codono_user_coin` WHERE `userid` = $userid";
                $res_bal = $mo->query($query);
                $user_coin_bal = $res_bal[0];

                $mum_a = bcadd($user_coin_bal[$coinname], $credit, 8);
                $mum_b = $user_coin_bal[$coinnamed];
                $num = bcadd($user_coin_bal[$coinname], $user_coin_bal[$coinnamed], 8);
                $mum = bcadd($num, $credit, 8);

				$mo->startTrans();
				
                if (0 < $credit) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $ibl['userid']))->setInc($coinname, $credit);
                }
                $move_stamp = '3_' . $ibl['docid'];
                $rs[] = $mo->table('codono_investbox_log')->where(array('id' => $ibl['id']))->save(array('status' => 3, 'withdrawn' => time(), 'credited' => $credit));
                $finance_array = array('userid' => $userid, 'coinname' => $coinname, 'num_a' => $user_coin_bal[$coinname], 'num_b' => $credit, 'num' => $num, 'fee' => $credit, 'type' => 1, 'name' => 'investbox', 'nameid' => $ibl['id'], 'remark' => 'InvestBox_Maturity', 'move' => $move_stamp, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b);

                $rs[] = $mo->table('codono_finance')->add($finance_array);
            } else {
                $mo->rollback();
                echo 'Invalid status of investment !<br/>';
                continue;
            }

            if (check_arr($rs)) {
                $mo->commit();
                
                echo $ibl['docid'] . ' investment has been credited!<br/>';

            } else {
                $mo->rollback();
                
                echo 'Investment could not be credited!<br/>';

            }

        }
        echo "End of checking";
    }

    function array_multi_unique($multiArray){

        $uniqueArray = array();
      
        foreach($multiArray as $subArray){
      
          if(!in_array($subArray, $uniqueArray)){
            $uniqueArray[] = $subArray;
          }
        }
        return $uniqueArray;
      }
    /* Function to Get Market Prices from Binance and save it to codono_binance table */
    public function BinanceUpdate()
    {
		if(!$this->timeToDoTask(__FUNCTION__,60)){
		//exit('This can be called once in a min , you have already called once');
		}
        $flag = 1;
        

        G('begin');
        try{
            $request = $this->gcurl('https://api.binance.com/api/v3/ticker/24hr');
        }catch(Exception $e){
            dump($e->getMessage());
        }
        
        $mo = M();

        if (!isset($request) || $request != null) {

            $response = json_decode($request, true);

            if ($response) {
				
				$pairs=$this->array_multi_unique($this->limitBinanceResults($response));
                $bin_data = $mo->table('codono_binance')->order('id asc')->limit(300)->select();
                
                foreach ($pairs as $pair) {

                    //$info=$bin_data[$pair['symbol']];
                    foreach ($bin_data as $bin) {
                        if (strtolower($pair['symbol']) == strtolower($bin['symbol'])) {
                            $info = $bin;
                            break;
                        } else {
                            //	continue;
                            $info = false;
                        }

                    }

                    if (!isset($info)) {
                        $saveData = $this->binanceConvert($pair);
                        $if_saved = $mo->table('codono_binance')->add($saveData);
                        echo "Saved status:$if_saved for market <strong>" . $pair['symbol'] . "</strong> to DB<br/>";
                        //Add pair required
                        continue;
                    }

                    if (strtolower($pair['symbol']) == strtolower($info['symbol'])) {
                        //Update required
                        $saveData = $this->binanceConvert($pair);
                        $if_updated = $mo->table('codono_binance')->where(array('symbol' => $info['symbol']))->save($saveData);
                        echo "Update1 status:$if_updated for pair <strong>" . $pair['symbol'] . "</strong> to DB<br/>";
                    }

                }

            } else {
                //   die('Data,timestamp');
                echo "<br/><span style='color:red' >Improper Binance response</span>";
                echo "<pre>";
                print_r($response);
                print_r($request);
                echo "</pre>";
                $flag = 0;
            }
        } else {
            echo "<br/><span style='color:red' >Could not get Binance response</span>";
            echo "<pre>";
            print_r($request);
            echo "</pre>";
            $flag = 0;
        }
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';

    }

    public function ExchangeBinanceUpdate()
    {
		if(!$this->timeToDoTask(__FUNCTION__,60)){
		//exit('This can be called once in a min , you have already called once');
		}
        echo "<br/> Updating Market Table now<br/>";
        G('1_begin');
        $this->Binance2Market();
        G('1_end');
        echo "<br/>Total Time taken " . G('1_begin', '1_end') . 's';

    }

    private function limitBinanceResults($binance_coins)
    {
        $markets = C('market');
		$processed=[];
        $coins_array = array();
        foreach ($markets as $market) {
            $first_coin = explode('_', $market['name'])[0];
            $last_coin = explode('_', $market['name'])[1];
            //Because binance does not have usd market but usdt market
            if (strtolower($last_coin) == 'usd') {
                $last_coin = 'usdt';
            }
            $bin_pair = strtoupper($first_coin . $last_coin);
            $found_entry = array_search($bin_pair, array_column($binance_coins, 'symbol'));
			$if_processed = in_array($bin_pair, $processed);
			if($if_processed){
				continue;
			}
            if ($found_entry>=0) {
                $coins_array[] = $binance_coins[$found_entry];
            }
			$processed[]=$bin_pair;
        }

        return $coins_array;
    }

    private function binanceConvert($data)
    {
        $response = array(
            'symbol' => $data['symbol'],
            'priceChange' => $data['priceChange'],
            'priceChangePercent' => $data['priceChangePercent'],
            'weightedAvgPrice' => $data['weightedAvgPrice'],
            'prevClosePrice' => $data['prevClosePrice'],
            'lastPrice' => $data['lastPrice'],
            'lastQty' => $data['lastQty'],
            'bidPrice' => $data['bidPrice'],
            'bidQty' => $data['bidQty'],
            'askPrice' => $data['askPrice'],
            'askQty' => $data['askQty'],
            'openPrice' => $data['openPrice'],
            'highPrice' => $data['highPrice'],
            'lowPrice' => $data['lowPrice'],
            'volume' => $data['volume'],
            'quoteVolume' => $data['quoteVolume'],
        );
        if ($data['firstId']) {
            $response['firstId'] = $data['firstId'];
        }
        if ($data['lastId']) {
            $response['lastId'] = $data['lastId'];
        }

        return $response;
    }

    private function Binance2Market()
    {
        $markets = C('market');
        $binancePairs = M('Binance')->select();
       /* foreach ($binancePairs as $binancePair) {
            $bininfo[$binancePair['symbol']] = $binancePair;
        }
       */
		
        foreach ($markets as $market) {
		
            if ($market['ext_price_update'] == 1 && $market['ext_orderbook'] == 1) {
                $first_coin = explode('_', $market['name'])[0];
                $last_coin = explode('_', $market['name'])[1];
                //Because binance does not have usd market but usdt market
                if (strtolower($last_coin) == 'usd') {
                    $last_coin = 'usdt';
                }
                $bin_pair = strtoupper($first_coin . $last_coin);

                $bmarket = $this->bmarket($bin_pair);
                echo "<br/>Market=".$bmarket;
				 array_unshift($binancePairs,array('symbol'=>'00000'));
                $if_found = array_search($bmarket, array_column($binancePairs, 'symbol'));
                if ($if_found) {
                    $binTableData = $binancePairs[$if_found];
					
                    $res = $this->MapBin2Market($market['name'], $binTableData);
					
                    echo "********************<br/>Updated " . $market['name'] . "<br/>";
                }
				S('getJsonTop' . $market['name'], 0);

           
            }
        }
    }

    private function bmarket($market)
    {
        return strtoupper(str_replace('_', '', $market));
    }

    private function MapBin2Market($market, $binTableData)
    {
        $return['market_update'] = 0;
        $return['market_orderbook'] = 0;
        $first_coin = explode('_', $market)[0];
        $last_coin = explode('_', $market)[1];
        if (strtolower($last_coin) == 'usd') {
            $last_coin = 'usdt';
        }
        $search_pair = strtoupper($first_coin . $last_coin);

        $found_pair = $binTableData;//M('Binance')->where(array('symbol' => $search_pair))->find();
		

        if (is_array($found_pair) && $found_pair != null) {
            if ($found_pair['lastprice']) {
                $upCoinData['new_price'] = $found_pair['lastprice'];
            }

            if ($found_pair['askprice']) {
                $upCoinData['buy_price'] = $found_pair['askprice'];
            }

            if ($found_pair['bidprice']) {
                $upCoinData['sell_price'] = $found_pair['bidprice'];
            }

            if ($found_pair['lowprice']) {
                $upCoinData['min_price'] = $found_pair['lowprice'];
            }

            if ($found_pair['highprice']) {
                $upCoinData['max_price'] = $found_pair['highprice'];
            }

            if ($found_pair['volume']) {
                $upCoinData['volume'] = $found_pair['volume'];
            }

            if ($found_pair['pricechangepercent']) {
                $upCoinData['change'] = $found_pair['pricechangepercent'];
            }
            $market_info_selected = M('Trade')->query('SELECT max(`flag`) as last_flag FROM `codono_trade` WHERE `market`="' . $market . '" and `userid`=0');
            $old_market_flag = $market_info_selected[0]['last_flag'];
			
            if ($old_market_flag <= $found_pair['lastid'] || $old_market_flag == NULL) {
                $update_required = true;
            } else {
                $update_required = false;
            }
			echo "<br/>Update required for market $market is $update_required";
				
            if (is_array($upCoinData)) {
                $return['market_update'] = M('Market')->where(array('name' => strtolower($market)))->save($upCoinData);
            }


            if (C('market')[strtolower($market)]['ext_orderbook'] == 1 && $update_required) {

                $commission = C('market')[strtolower($market)]['orderbook_markup'];

                $number_of_orders = 30;
                $this->OrderBookGen($market, $found_pair['bidprice'], $found_pair['bidqty'], $found_pair['askprice'], $found_pair['askqty'], $found_pair['lastid'], $commission, $number_of_orders);
                $return['market_orderbook'] = 1;

            }
            /*
            else {
                echo $old_market_flag." and ".$found_pair['lastid'];
            }
            */


        }

        return $return;
    }

    //Create Orderbook using a price
    private function OrderBookClean($market, $lastid)
    {
        if ($lastid <= 0) {
            $condition = array('userid' => 0, 'market' => $market);
        } else {
            $condition = array('userid' => 0, 'market' => $market, 'flag' => array('lt', $lastid));
        }

        return M('Trade')->where($condition)->delete();
    }

    private function OrderBookGen($market, $bidPrice, $bidQty, $askPrice, $askQty, $lastId, $commission, $number_of_orders)
    {

        if ($bidPrice <= 0 || $bidPrice == null || $askPrice < 0 || $askPrice == null) {
            return false;
        }
        $avgQty = bcdiv(bcadd($askQty, $bidQty, 8), 2, 8);
        $last['bidPrice'] = bcmul($bidPrice, bcsub(1, bcdiv($commission, 100, 8), 8), 8);
        $last['bidQty'] = $avgQty; //$bidQty;
        $last['askPrice'] = bcmul($askPrice, bcadd(1, bcdiv($commission, 100, 8), 8), 8);
        $last['askQty'] = $avgQty; //$askQty;
		
        $bid_stack = array();
        $ask_stack = array();
        for ($i = 0; $i < $number_of_orders; $i++) {
            $rand_sign1 = rand(1, 2);
            $rand_sign2 = rand(1, 2);
            $rand_bid_price = 1 - rand(10, 50) / 20000;
            $rand_ask_price = 1 + rand(10, 50) / 20000;

            if ($rand_sign1 == 1) {
                $rand_bid_qty = 1 + rand(10, 20) / 100;
            } else {
                $rand_bid_qty = 1 - rand(10, 20) / 100;
            }
            if ($rand_sign2 == 1) {
                $rand_ask_qty = 1 + rand(10, 20) / 100;
            } else {
                $rand_ask_qty = 1 - rand(10, 20) / 100;
            }


            $last['bidPrice'] = bcmul($last['bidPrice'], $rand_bid_price, 8);
            $last['bidQty'] = bcmul($last['bidQty'], $rand_bid_qty, 5);
            $last['askPrice'] = bcmul($last['askPrice'], $rand_ask_price, 8);
            $last['askQty'] = bcmul($last['askQty'], $rand_ask_qty, 5);
			
			if($last['bidPrice'] <=0 || $last['bidQty'] <=0 || $last['askPrice'] <=0 ||$last['askQty'] <=0){
				break;
			}
			
            $bid_stack[$i]['market'] = $market;
            $bid_stack[$i]['price'] = $last['bidPrice'];
            $bid_stack[$i]['num'] = $last['bidQty'];
            $bid_stack[$i]['type'] = 1;
            $bid_stack[$i]['addtime'] = time();
            $bid_stack[$i]['flag'] = $lastId;

            $ask_stack[$i]['market'] = $market;
            $ask_stack[$i]['price'] = $last['askPrice'];
            $ask_stack[$i]['num'] = $last['askQty'];
            $ask_stack[$i]['type'] = 2;
            $ask_stack[$i]['addtime'] = time();
            $ask_stack[$i]['flag'] = $lastId;
        }
		

        $this->OrderBookClean($market, $lastId);
        // echo "<br/>Found $cleaned old records from $market orderbook , So deleted them, Now adding new Orderbook<br/>";
        $stacks = array_merge_recursive($bid_stack, $ask_stack);
		M('Trade')->addAll($stacks);
	
		$allow_trade_log=C('market')[$market]['ext_fake_trades']?:0;
		
        if ($allow_trade_log == 1) {
            //  echo "<br/>Now adding Trade Logs<br/>";
            $this->SelfOrderLogGenerate($stacks);
			S('getTradelog' . $market, null);
        }
		//A('Trade')->callStopMatching($market,1);
		//A('Trade')->callStopMatching($market,2);
        A('Trade')->callMatchingTrade($market);

        //$ok=A('Ajax')->getActiveOrders($market,1,'json');
        S('getDepth', null);

        S('getActiveDepth' . $market, null);
        S('getActiveDepth', null);
        S('getDepthNew', null);
		S('getJsonTop' . $market, null);
        return true;
    }
	private function superQty($qty){
		    $rand_sign = rand(1, 2);
            
            if ($rand_sign == 1) {
                $rand_percent = 1 + rand(10, 20) / 100;
            } else {
                $rand_percent = 1 - rand(10, 20) / 100;
            }
			return bcmul($qty, $rand_percent, 5);
	}
	 private function SelfOrderLogGenerate($_stacks)
    {
            
        shuffle($_stacks);
        shuffle($_stacks);
			
        $size = rand(0, 4);
        $stacks = array();
        for ($i = 0; $i <= $size; $i++) {
            $stacks[] = $_stacks[$i];
        }
        $stamp = time() - 60;

        $count = count($stacks);
		
        if (count($stacks) > 0) {
            foreach ($stacks as $stack) {

                $salt = $this->salt_stamp($size);
                $stamp = $stamp + $salt;
                $stack['userid'] = 0;
                $stack['peerid'] = 0;
                $stack['fee_buy'] = 0;
                $stack['fee_sell'] = 0;
                $stack['status'] = 1;
                $stack['addtime'] = $stamp;
				$stack['num']=$this->superQty($stack['num']);//rand(bcmul($stack['num'],0.01,8),bcmul($stack['num'],0.25,8));
                $stack['mum'] = bcmul($stack['num'], $stack['price'], 8);

                unset($stack['flag']);
                unset($stack['deal']);
                unset($stack['fee']);
                M('TradeLog')->add($stack);
            }
        }
        echo "<br/>Added $count trade_log records";
    }
	/**
     * @param $size
     * @return int
     */
    private function salt_stamp($size): int
    {
        return (int)bcadd($size, rand(1, 10),0);

    }
    //Binance code ends
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
                    if (!$info) {
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        $if_saved = $mo->table('codono_coinmarketcap')->add($map);
                        echo "Saved status:$if_saved for coin <strong>" . $coin['name'] . "</strong> to DB<br/>";
                        //Add coin required
                        continue;
                    }
                    if ($coin['symbol'] == $info['symbol'] && $info['last_updated'] < $response['timestamp']) {
                        //Update required
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
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

    public function send_notifications()
    {
        
        $notifications = M('Notification')->where(array('status' => 0))->order('id desc')->select();

        foreach ($notifications as $note) {
            if (!check($note['to_email'], 'email')) {
                continue;
            }

            $status = tmail($note['to_email'], $note['subject'], $note['content']);

            if ($status) {
                $the_status = json_decode($status);
                if ($status == true || isset($the_status->status) )// means email has been sent , Now mark this email as sent
                {
                    echo M('Notification')->where(array('id' => $note['id']))->save(array('status' => 1));
                }
            }
        }
        echo "End";
    }

    public function index()
    {
        echo "ok";
    }

    /*Adjustment of abnormal Trades if deal > num */
    public function fixTrades()
    {
        
        $mo = M();
        
        $Trade = M('Trade')->where('deal > num')->order('id desc')->find();

        if ($Trade) {
			$mo->startTrans();
            if ($Trade['status'] == 0) {
            $rs[] =   $mo->table('codono_trade')->where(array('id' => $Trade['id']))->save(array('deal' => Num($Trade['num']), 'status' => 1));
            } else {
            $rs[] =    $mo->table('codono_trade')->where(array('id' => $Trade['id']))->save(array('deal' => Num($Trade['num'])));
            }
			if (check_arr($rs)) {
				$mo->commit();
			} else {
				$mo->rollback();
            
			}
		}
        echo "Cron Ended :)";
    }

    public function clearRedisForLiquidity()
    {
        
		
        foreach (C("market") as $market) {
			
		if ($market['ext_price_update'] == 1 && $market['ext_orderbook'] == 1) {
			
            S('allsum', null);
            S('getJsonTop' . $market['name'], null);
            S('getTradelog' . $market['name'], null);
            S('getDepth' . $market['name'] . '1', null);
            S('getDepth' . $market['name'] . '3', null);
            S('getDepth' . $market['name'] . '4', null);
            S('ChartgetJsonData' . $market['name'], null);
            S('allcoin', null);
            S('trends', null);
            S('getActiveDepth'.$market['name'], null);
			}
        }

    }

    public function matchOrdersManually()
    {
        
        foreach (C('market') as $k => $v) {
            A('Trade')->matchingTrade($v['name']);
            echo $v["name"] . "<br/>";
        }
    }


    public function setMarketCoinStats()
    {
        
        foreach (C('market') as $k => $v) {
            $this->setMarket($v['name']);
        }

        foreach (C('coin_list') as $k => $v) {
            $this->setcoin($v['name']);
        }

    }

    private function setMarket($market = NULL)
    {
        if (!$market) {
            return null;
        }

        $market_json = M('Market_json')->where(array('name' => $market))->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('TradeLog')->where(array('market' => $market))->order('addtime asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
        $trade_num = M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array(
                array('egt', $start),
                array('elt', $end)
            )
        ))->sum('num');

        if ($trade_num) {
            $trade_mum = M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('mum');
            $trade_fee_buy = M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee_buy');
            $trade_fee_sell = M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee_sell');
            $d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

            if (M('Market_json')->where(array('name' => $market, 'addtime' => $end))->find()) {
                M('Market_json')->where(array('name' => $market, 'addtime' => $end))->save(array('data' => json_encode($d)));
            } else {
                M('Market_json')->add(array('name' => $market, 'data' => json_encode($d), 'addtime' => $end));
            }
        } else {
          //  $d = null;

            if (M('Market_json')->where(array('name' => $market, 'data' => ''))->find()) {
                M('Market_json')->where(array('name' => $market, 'data' => ''))->save(array('addtime' => $end));
            } else {
                M('Market_json')->add(array('name' => $market, 'data' => '', 'addtime' => $end));
            }
        }
    }

    private function setcoin($coinname = NULL)
    {
        //   echo "<br/>Start coin " . $coinname;
        if (!$coinname) {
            return null;
        }

        if (C('coin')[$coinname]['type'] == 'qbb') {
            $dj_username = C('coin')[$coinname]['dj_yh'];
            $dj_password = C('coin')[$coinname]['dj_mm'];
            $dj_address = C('coin')[$coinname]['dj_zj'];
            $dj_port = C('coin')[$coinname]['dj_dk'];
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                return null;
            }

            $data['trance_mum'] = $json['balance'];
            $bb = $json['balance'];
        } else {
            $data['trance_mum'] = 0;
            $bb = 0;
        }

        $market_json = M('CoinJson')->where(array('name' => $coinname))->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('Myzr')->where(array('name' => $coinname))->order('id asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

        if ($addtime) {
            if ((time() + (60 * 60 * 24)) < $addtime) {
                return null;
            }

            $trade_num = M('UserCoin')->where(array(
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum($coinname);
            $trade_mum = M('UserCoin')->where(array(
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum($coinname . 'd');
            $aa = $trade_num + $trade_mum;


            $trade_fee_buy = M('Myzr')->where(array(
                'coinname' => $coinname,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee');
            $trade_fee_sell = M('Myzc')->where(array(
                'coinname' => $coinname,
                'addtime' => array(
                    array('egt', $start),
                    array('elt', $end)
                )
            ))->sum('fee');
            $d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

            if (M('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->find()) {
                M('CoinJson')->where(array('name' => $coinname, 'addtime' => $end))->save(array('data' => json_encode($d)));
            } else {
                M('CoinJson')->add(array('name' => $coinname, 'data' => json_encode($d), 'addtime' => $end));
            }
        }
    }


    public function setHourlyPrice()
    {
        
        foreach (C('market') as $k => $v) {
            echo "$k<br/>";
            
                $t = time();
                $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
                echo "Start is :$start<br/>";
                $hou_price = M('TradeLog')->where(array(
                    'market' => $v['name'],
                    'addtime' => array('lt', $start)
                ))->order('id desc')->getField('price');

                if (!$hou_price) {
                    $hou_price = M('TradeLog')->where(array('market' => $v['name']))->order('id asc')->getField('price');
                }

                M('Market')->where(array('name' => $v['name']))->setField('hou_price', $hou_price);
                S('home_market', null);
            
        }
    }

	/* Generates Tendency */
    public function tendency()
    {
        
        foreach (C('market') as $k => $v) {
            echo '----Computing trend----' . $v['name'] . '------------';
            $tendency_time = 4;
            $t = time();
            $tendency_str = $t - (24 * 60 * 60 * 3);
            $x = 0;

            for (; $x <= 18; $x++) {
                $na = $tendency_str + (60 * 60 * $tendency_time * $x);
                $nb = $tendency_str + (60 * 60 * $tendency_time * ($x + 1));
                $b = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $v['name'] . '\'')->max('price');

                if (!$b) {
                    $b = 0;
                }

                $rs[] = array($na, $b);
            }

            M('Market')->where(array('name' => $v['name']))->setField('tendency', json_encode($rs));
            unset($rs);
            echo 'Computing success!';
            echo "\n";
        }

        echo 'Trend Calculation 0k ' . "\n";
    }


    private function liq_market_stats($market)
    {
       
        $round = C('market')[$market]['round'];
        $new_price = format_num(M('TradeLog')->where(array('market' => $market, 'status' => 1, 'addtime' => array('gt', time() - (60 * 60 * 24))))->order('id desc')->limit(100)->getField('price'), $round);
        $buy_price = format_num(M('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0, 'addtime' => array('gt', time() - (60 * 60 * 24))))->order('id desc')->limit(100)->max('price'), $round);
        $sell_price = format_num(M('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0, 'addtime' => array('gt', time() - (60 * 60 * 24))))->order('id desc')->limit(100)->min('price'), $round);
        $min_price = format_num(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24))
        ))->order('id desc')->limit(100)->min('price'), $round);
        $max_price = format_num(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24))
        ))->order('id desc')->limit(100)->max('price'), $round);
        $volume = format_num(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24))
        ))->order('id desc')->limit(100)->sum('num'), $round);
        $sta_price = format_num(M('TradeLog')->where(array(
            'market' => $market,
            'status' => 1,
            'addtime' => array('gt', time() - (60 * 60 * 24))
        ))->order('id desc')->limit(100)->getField('price'), $round);
        $Cmarket = M('Market')->where(array('name' => $market))->find();

        if ($Cmarket['new_price'] != $new_price) {
            $upCoinData['new_price'] = $new_price;
        }

        if ($Cmarket['buy_price'] != $buy_price) {
            $upCoinData['buy_price'] = $buy_price;
        }

        if ($Cmarket['sell_price'] != $sell_price) {
            $upCoinData['sell_price'] = $sell_price;
        }

        if ($Cmarket['min_price'] != $min_price) {
            $upCoinData['min_price'] = $min_price;
        }

        if ($Cmarket['max_price'] != $max_price) {
            $upCoinData['max_price'] = $max_price;
        }

        if ($Cmarket['volume'] != $volume) {
            $upCoinData['volume'] = $volume;
        }

        $change = format_num((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 4);
        $upCoinData['change'] = $change;

        if ($upCoinData) {
            M('Market')->where(array('name' => $market))->save($upCoinData);
            M('Market')->execute('commit');
            S('home_market', null);
        }
    }

    public function genInternalCharts()
    {
        G('begin');
        foreach (C('market') as $k => $v) {

            //if (array_key_exists(strtoupper($v['name']), LIQUIDITY_ARRAY)) {
            if ($v["ext_charts"] == 1) {
                echo "<br/>" . $v['name'] . " is system is showing external charts " . $v["charts_symbol"] . "<br/>";
            } else {
                $this->setChartMaker($v['name']);
            }
        }

        echo "<br/><b>Chartmaker Ends</b>";
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
    }

    private function setChartMaker($market)
    {

        $timearr = [1, 3, 5, 15, 30, 60, 120, 240, 360, 720, 1440, 10080];

        $tl_addtime = M('TradeLog')->where(['market' => $market])->order('addtime asc')->getField('addtime');

        if ($tl_addtime) {
            $tl_addtimeArray = M('TradeLog')->where('addtime >=' . $tl_addtime . '  and market =\'' . $market . '\'')->order('addtime asc')->limit(200)->field('addtime')->select();
            $tl_so_addtime = sizeof($tl_addtimeArray);
            $tl_any_trade_after = M('TradeLog')->where('addtime >=' . $tl_addtime . '  and market =\'' . $market . '\'')->field('id')->find();
        }


        foreach ($timearr as $v) {
            echo "<br/><strong>==================Generating for $v mins chart for $market==================</strong><br/>";
            $tradeJson = M('TradeJson')->where(['market' => $market, 'type' => $v])->order('addtime desc')->field('addtime')->find();

            $any_trade_after = null;
            if ($tradeJson) {
                $addtime = $tradeJson['addtime'];
                if (!$addtime) {
                    //    echo "<br/>tradeJson Stop at " . __LINE__ . " :Line No addtime found";
                    continue;
                }
                $addtimeArray = M('TradeLog')->where('addtime >=' . $addtime . '  and market =\'' . $market . '\'')->order('addtime asc')->field('addtime')->limit(200)->select();
                $so_addtime = sizeof($addtimeArray);
                $any_trade_after = M('TradeLog')->where('addtime >=' . $addtime . '  and market =\'' . $market . '\'')->field('id')->find();
            } else {
                $addtime = $tl_addtime;
                if (!$addtime) {
                    //     echo "<br/>NotradeJson Stop at " . __LINE__ . " :Line No addtime found";
                    continue;
                }
                $addtimeArray = $tl_addtimeArray;
                $so_addtime = $tl_so_addtime;
                $any_trade_after = $tl_any_trade_after;
            }

            $addtimeArray = array_unique($addtimeArray, SORT_REGULAR);
            if (isset($addtimeArray[$so_addtime])) {
                if ($addtimeArray[$so_addtime]['addtime'] == $addtime) {
                    echo "We already found its record for addtime $addtime";
                    continue;
                }
            }


            echo "<br/> $so_addtime records found to be processed and addtime is $addtime<br/>";

            if (!$any_trade_after) {
                echo "<br/> There were no new records found to be processed cron will check next timeframe<br/>";
                continue;
            } else {
                if ($v == 1) {
                    $start_time = $addtime;
                } else {
                    $start_time = mktime(date('H', $addtime), floor(date('i', $addtime) / $v) * $v, 0, date('m', $addtime), date('d', $addtime), date('Y', $addtime));
                }

                $x = 0;
                for (; $x < $so_addtime; ++$x) {
                    //echo "x=$x,so_addtime=$so_addtime";

                    $y = $x + $v; //New index with is index1 + timeframe ie 1, 3,5 etc
                    $na = $addtimeArray[$x]['addtime'];
                    if ($na == null && !$addtimeArray[$x]['addtime']) {
                        continue;
                    }
                    //$nb=$addtimeArray[$x+1]['addtime'];
                    foreach ($addtimeArray as $att) {

                        $checkb = $att['addtime'];

                        if ($checkb - $na >= 60 * $v) {
                            break;
                        } else {
                            $x++;
                        }

                    }

                    $nb = $checkb;

                    echo "<br/>$x attempt now going to check between $na and $nb<br/>";

                    $x++;
                    //Exceed the current time, out of the loops


                    if (time() < $na) {
                        echo "greater";
                        break;
                    }
                    echo "<br>attemptNO #$x";

                    //Query the transaction volume in the time interval
                    //try {
                    if (!$na || !$nb) {
                        echo "start=$na and end=$nb exit point";
                        continue;
                    }
                    $sum = $this->sum_market($na, $nb, $market);

                    if ($sum > 0) {
                        echo "<br/>sum=$sum<br/>";
                        $sta = M('TradeLog')->where('addtime >=' . $na . ' and addtime <=' . $nb . ' and market =\'' . $market . '\'')->order('addtime asc')->getField('price');
                        $all_price = $this->getprice_market($na, $nb, $market);
                        $max = $all_price['max'];
                        $min = $all_price['min'];
                        $end = $all_price['end'];
                        $d = [$na, (float)$sum, format_num($sta), format_num($max), format_num($min), format_num($end)];
                    } else {
                        echo "<br/>Sum is $sum end Check next record<br/>";
                        continue;
                    }
                    $tradejsonid = M('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $v])->field('id,data')->find();
                    if (!empty($tradejsonid)) {
                        //echo "Found:<br/>";

                        if (json_decode($tradejsonid['data']) != $d) {
                            $saved = M('TradeJson')->where(array("id" => $tradejsonid['id']))->save(array('data' => json_encode($d)));
                            echo "<br/>Save status:" . $saved;
                        }
                        /*
                        else {
                                echo "<br/>No changes:";
                        }
                         */
                    } else {
                        M('TradeJson')->add(['market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v]);

                    }


                    //usleep(1000);

                }
                echo "<br/>chart for $market for $v done<br/>";
            }
            //usleep(1000);
        }
    }

    //min,max,end
    private function getprice_market($na, $nb, $market)
    {
        $s = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->getField('price', TRUE);
        $price['min'] = min($s);
        $price['max'] = max($s);
        $price['end'] = end($s);
        return $price;
    }

    private function sum_market($na, $nb, $market)
    {
        $s = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->getField('num', TRUE);
        $num = 0;

        if ($s) {
            foreach ($s as $v) {
                $num = bcadd($num, $v, 8);
            }
        }
        return $num;
    }

    private function setTradeJson($market)
    {
        $cron_time_start = microtime(true);
        $timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);
        echo "<br/>==========================";
        foreach ($timearr as $k => $v) {
            echo "<br/>$market for $v min charts";
            $tradeJson = M('TradeJson')->where(array(
                'market' => $market,
                'type' => $v
            ))->order('id desc')
                ->find();

            if ($tradeJson) {
                $addtime = $tradeJson['addtime'];
            } else {
                $addtime = M('TradeLog')->where(array(
                    'market' => $market
                ))->order('id asc')
                    ->getField('addtime');
            }
            $youtradelog = null;
            if ($addtime) {
                $youtradelog = M('TradeLog')->where('addtime >= %d and market =\'%s\'', $addtime, $market)->sum('num');
            }

            if ($youtradelog) {
                if ($v == 1) {
                    $start_time = $addtime;
                } else {
                    $start_time = mktime(date('H', $addtime), floor(date('i', $addtime) / $v) * $v, 0, date('m', $addtime), date('d', $addtime), date('Y', $addtime));
                }

                $x = 0;

                for (; $x <= 20; $x++) {
                    $na = $start_time + (60 * $v * $x);
                    $nb = $start_time + (60 * $v * ($x + 1));

                    if (time() < $na) {
                        break;
                    }

                    $sum = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->sum('num');

                    if ($sum) {
                        $sta = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id asc')->getField('price');
                        $max = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->max('price');
                        $min = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->min('price');
                        $end = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id desc')->getField('price');
                        $d = array($na, $sum, $sta, $max, $min, $end); //date,qty,open,high,low,close
                        if (M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->find()) {
                            M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->save(array('data' => json_encode($d)));
                        } else {
                            M('TradeJson')->add(array('market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v));
                            M('TradeJson')->execute('commit');
                        }
                    }
                }
            }
        }

        $time_end = microtime(true);

        //dividing with 60 will give the execution time in minutes otherwise seconds
        $execution_time = ($time_end - $cron_time_start) / 60;

        //execution time of the script
        echo "<br/><b>$market Execution Time:</b>  $execution_time  Mins";
        echo "<br/>==========================";
    }


  
    private function InformAdmin($info)
    {
        //Do something , You can send a specific notification to admin , It can be  either email sms , or anything
    }

    /**
     *Creates columns in codono_user_coin table for missing columns [example xrp, xrpd,xrpb]
     */
    public function fix_user_coin()
    {

        if ($_GET['agree'] != 'yes') {
            $agree_url = $_SERVER['REQUEST_URI'] . "/agree/yes";
            die("<span style='color:red'>Warning this tool will modify you user_coin table , this is very powerful,  <a href='$agree_url'> Do you agree?</a></span>");
        }

        $sql = "SELECT (column_name) as name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . DB_NAME . "' AND TABLE_NAME='codono_user_coin'";
        $mo = M();
        $ucoins = $mo->query($sql);
        $cnames = array();
        foreach ($ucoins as $ucoin) {
            $cnames[] = $ucoin['name'];
        }

        foreach (C('coin') as $coin) {
            $name = $coin['name'];
            $address = $coin['name'] . 'b';
            $balance = $coin['name'] . 'd';
            $present = in_array($name, $cnames);
            if ($present == 0) {
                echo "$name is not present so creating it<br/>";
                M()->execute('ALTER TABLE  `codono_user_coin` ADD  `' . $name . '` DECIMAL(20,8) UNSIGNED  NULL DEFAULT "0.00000000"');
                M()->execute('ALTER TABLE  `codono_user_coin` ADD  `' . $balance . '` DECIMAL(20,8) UNSIGNED NULL DEFAULT "0.00000000" ');
                M()->execute('ALTER TABLE  `codono_user_coin` ADD  `' . $address . '` VARCHAR(99) DEFAULT NULL ');
            } else {
                echo "$name is present Skipping to next<br/>";
            }
        }
    }
}
