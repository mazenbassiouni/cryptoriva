<?php

namespace Home\Controller;

use Think\Exception;

/**
 * Processing Tron and Trc20 deposits
 * Class TronController
 * OUT_OF_ENERGY, SUCCESS , REVERT
 * contractRet {
 * DEFAULT = 0;
 * SUCCESS = 1;
 * REVERT = 2;
 * BAD_JUMP_DESTINATION = 3;
 * OUT_OF_MEMORY = 4;
 * PRECOMPILED_CONTRACT = 5;
 * STACK_TOO_SMALL = 6;
 * STACK_TOO_LARGE = 7;
 * ILLEGAL_OPERATION = 8;
 * STACK_OVERFLOW = 9;
 * OUT_OF_ENERGY = 10;
 * OUT_OF_TIME = 11;
 * JVM_STACK_OVER_FLOW = 12;
 * UNKNOWN = 13;
 * }
 */
class TronController extends HomeController
{
	public function makeOneAccount(){
		checkcronkey();
		$account =$this->newAccount();
		echo "<strong>This information on refresh will vanish , it does not get saved anywhere.If you plan to use this account as Main account ,Save this information somewhere safe, print it and keep safe</strong><pre>";
		print_r($account);
		echo "</pre><script>window.onbeforeunload = function() {return 'Data will be lost if you leave the page, are you sure?';}</script><i> This is just a tool to generate unique Tron address along with its private keys, You can use this account or generate somewhere else and save it in tron config in admin area, This information on refresh will vanish , it does not get saved anywhere.</i>";
	}
    /*Generate Address for users*/
    function newAccount()
    {

        $tron = TronClient();
        return $tron->generateAddress();

    }



    function getUserHexAddresses()
    {
        return M('Tron')->where(array('uid' => array('egt', 0)))->order('id desc')->field('address_hex')->select();
    }

    public function cronDeposits($fromblock = null, $range = 25)
    {
        checkcronkey();
        if ($fromblock == null) {
            $info = M()->query("SELECT block as block FROM `codono_coin` WHERE `name`='trx'");
            if ($info[0]['block'] == null) {
                echo "make sure to add block number to tron row in coin table";
                exit();
            }

            $fromblock = $info[0]['block'];
        }
        if (!check($range, 'd') || $range > 50) {
            $range = 25;
        }
        $this->findDeposits($fromblock, $range);
    }

    function findDeposits($block_from, $range)
    {
        $block_to = $block_from + $range;
        if ($block_from > $block_to) {
            return false;
        }

        G('begin');
        $user_addresses = $this->getUserHexAddresses();
        $tron = TronClient();
        $blocks = $tron->getBlockRange($block_from, $block_to);
		$parseResult=$this->parseBlocks($blocks);
        $transactions = $parseResult['transactions'];
		$lastblock = $parseResult['lastblock'];
		if($lastblock>0 && $lastblock<=$block_to){
			$db_update_block=$lastblock;
		}else{
			//we reread this block
			$db_update_block=$block_from;
		}
		
        echo "Reading blocks from $block_from to $block_to, in total number of  <strong>" . ($range) . "</strong> blocks and Total transactions : <strong>" . count($transactions) . "</strong><br/>";
        if (!empty($transactions)) {
            foreach ($transactions as $tx) {


                $key = array_search($tx['info']['to_address_hex'], array_column($user_addresses, 'address_hex'));
                if (is_int($key)) {
                    //transaction belongs to user address so ,check/add entry in db
                    $this->processTxDB($tx);

                }
                //else {echo "<br/>Key not found:".$tx['hash']};

            }
            $this->processHash();
            $update_block = M('Coin')->where(array('name' => 'trx', 'type' => 'tron'))->save(array('block' => $db_update_block));
        }

        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
        echo "<br/>Total Memory taken " . G('begin', 'end', 'm') . 's';
    }

    private function processTxDB($tx)
    {
        $hash = $tx['hash'];
        if (strlen($hash) != 64) {
            return false;
        }

        $mo = M();
        $result = $mo->table('codono_tron_hash')->where(array('hash' => $hash))->find();

        if (!is_array($result)) {
            echo "<br/><strong>" . $tx['type'] . "</strong> Found for user address hex " . $tx['info']['to_address_hex'] . " for " . $tx['hash'] . "<br/>";
            $saveData = array('hash' => $hash, 'contract_hex' => $tx['info']['contract_hex'], 'contract' => $tx['info']['contract'], 'to_address_hex' => $tx['info']['to_address_hex'], 'type' => $tx['type'], 'raw_amount' => $tx['info']['amount'], 'addtime' => time(), 'status' => $tx['status']);
            return $mo->table('codono_tron_hash')->add($saveData);
        } else {
            echo "<br/>Already added: " . $tx['hash'] . "<br/>";
        }
    }

    private function processHash()
    {
        echo "<br/>***************************Function " . __FUNCTION__ . " started!***************************<br/>";
        $mo = M();
        $result = $mo->table('codono_tron_hash')->where(array('deposited' => 0, 'issue' => 0))->select();
        echo count($result) . ' unprocessed records found<br/>';
        $main_address = C('coin')['trx']['codono_coinaddress'];
        $tron = TronClient();
        foreach ($result as $res) {
            $deposit_address = $tron->toAddress($res['to_address_hex'], 'd');

            if (strtolower($main_address) == strtolower($deposit_address)) {
                $mo->table('codono_tron_hash')->where(array('id' => $res['id']))->save(array('issue' => 1));
            }
            if ($res['status'] == 'SUCCESS') {

                $this->makeDeposit($res);
            } else {
                //recheck status

                $info = $tron->getTransaction($res['hash']);
                echo "<br/>**********Process it to myzr*********<br/><pre>";
                print_r($info);
                echo "</pre><br/>*******************<br/>";
            }
        }
        echo "<br/>***************************Function " . __FUNCTION__ . " Ends!***************************<br/>";
    }

    private function enterDeposit($entry, $coin, $amount)
    {
        $mo = M();
        $already_deposited = M('Myzr')->where(array('txid' => $entry['hash']))->find();
        if ($already_deposited) {
            $mo->table('codono_tron_hash')->where(array('id' => $entry['id']))->save(array('deposited' => 1));
        } else {
            $hash = $entry['hash'];
            $userinfo = M('Tron')->where(array('address_hex' => $entry['to_address_hex']))->field('uid,address_base58')->find();
            $userid = $userinfo['uid'];
            $address = $userinfo['address_base58'];
            //do deposit and mark as deposited
            if ($userid > 0) {
                $mo = M();
                $mo->startTrans();
                echo "<br/>Hash found $hash<br/>";
                try {
                    $mo->table('codono_user_coin')->where(['userid' => $userid])->setInc($coin, $amount);
                    $rs = M('myzr')->add([
                        'userid' => $userid,
                        'username' => $address,
                        'coinname' => $coin,
                        'type' => 'trx',
                        'txid' => $hash,
                        'num' => $amount,
                        'mum' => $amount,
                        'addtime' => time(),
                        'status' => 1
                    ]);
                    $mo->table('codono_tron_hash')->where(array('id' => $entry['id']))->save(array('deposited' => 1));
                    echo "added";
                    $mo->commit();
                } catch (Exception $e) {
                    $mo->rollback();
                }

            } else {
                //issue as 1
                $mo->table('codono_tron_hash')->where(array('id' => $entry['id']))->save(array('issue' => 1));
            }

        }
    }

    private function makeDeposit($entry)
    {
        $deposited = false;
        if ($entry['type'] == 'trx') {
            $amount = bcdiv($entry['raw_amount'], bcpow(10, 6), 8);
            $coin = 'trx';
            $this->enterDeposit($entry, $coin, $amount);
        }
        if ($entry['type'] == 'trc20') {

            $contract = $entry['contract'];
            $coins = c('Coin');
            foreach ($coins as $co) {
                if ($co['dj_yh'] == $contract) {
                    $decimals = $co['cs_qk'];
                    $amount = bcdiv($entry['raw_amount'], bcpow(10, $decimals), 8);
                    //echo $amount. " amount found deposit for ".$co['name'];
                    $coin = $co['name'];
                    $this->enterDeposit($entry, $coin, $amount);
                    $deposited = true;
                }
            }
            if (!$deposited) {
                echo "<i>" . $entry['hash'] . ". <span style='color:blue'> is not a listed TRC20 $contract</span></i><br/>";
            }
            //echo "<i>" . $entry['hash'] . ". <span style='color:blue'> xThis is TRC20 , @Todo Write function for trc20 deposit detection</span></i><br/>";
        }
        if ($entry['type'] == 'trc10') {
            $contract = $entry['contract'];
            $coins = c('Coin');

            foreach ($coins as $co) {
                if ($co['dj_yh'] == $contract) {
                    $decimals = $co['cs_qk'];
                    $amount = bcdiv($entry['raw_amount'], bcpow(10, $decimals), 8);
                    //echo $amount. " amount found deposit for ".$co['name'];
                    $coin = $co['name'];
                    $this->enterDeposit($entry, $coin, $amount);
                    $deposited = true;
                }
            }
            if (!$deposited) {
                echo "<i>" . $entry['hash'] . ". <span style='color:blue'> is not a listed TRC10 $contract</span></i><br/>";
            }

            // echo "<i>" . $entry['hash'] . ". <span style='color:blue'> This is TRC10 , @Todo Write function for trc10 deposit detection</span></i><br/>";
        }

    }

    private function parseBlocks($blocks)
    {
        $transactions = array();

        foreach ($blocks as $block) {
            $txs = $block['transactions']; //multiple transactions

            if (is_array($txs)) {
                foreach ($txs as $tx) {
                    /*
                    echo "<pre>";
                    print_r($tx);
                    echo "</pre>";
                    */
                    $info = $this->txDetails($tx);
                    $transactions[] = $info;
                    //if(is_string($to_address) && $tx['ret'][0]['contractRet']=='SUCCESS'){
                    /*
                    if(is_string($info['to_address'])){
                        $transactions[$tx['txID']]=$info['to_address'];
                    }
                    */
                }

            }
        $lastblock=$block['block_header']['raw_data']['number'];
		}
		
		
        return array('transactions'=>$transactions,'lastblock'=>$lastblock);
    }

    function has_prefix($string, $prefix)
    {
        return substr($string, 0, strlen($prefix)) == $prefix;
    }

    private function trc20Tx($info)
    {
        $tron = TronClient();
        $value = $info['raw_data']['contract']['0']['parameter']['value'];
        if (strlen($value['data']) != 136) {
            return false;
        }
        if (!$this->has_prefix($value['data'], 'a9059cbb')) {
            return false;
        }

        $resp['contract_hex'] = $value['contract_address'];
        $resp['contract'] = $tron->toAddress($value['contract_address']);
        //$resp['owner_hex']=$value['owner_address'];
        //$resp['owner']=$tron->toAddress($value['owner_address']);
        //$resp['data']=$value['data'];
        $resp['to_address_hex'] = $this->addressParse($value['data']);
        $resp['to_address'] = $tron->toAddress($resp['to_address_hex'], 'd');
        $resp['amount'] = format_num(hexdec(ltrim(substr($value['data'], 72), 0)), 8);
        return $resp;
    }


    function addressParse($data)
    {
        if (!$this->has_prefix(substr($data, 30, 42), '41')) {
            $hex = '41' . substr($data, 32, 40);
        } else {
            $hex = substr($data, 30, 42);
        }
        return $hex;
    }

    private function sendGasMoney($to_address = null, $gas = 1)
    {
        if ($to_address == null) {
            return false;
        }
        $tron = TronClient();
        $CoinInfo = C('coin')['trx'];
        $priv_crypted = $CoinInfo['dj_mm'];
        $priv = cryptString($priv_crypted, 'd');

        $main_address = $CoinInfo['codono_coinaddress'];
        $amount = (double)$gas;
        return $tron->sendTransaction($to_address, $amount, $main_address, $priv);

    }

    private function txDetails($tx)
    {

        $transaction = array('hash' => $tx['txID'], 'status' => $tx['ret'][0]['contractRet']);
        $type = $tx['raw_data']['contract']['0']['type'];
        switch ($type) {
            case 'TriggerSmartContract':
                $transaction['type'] = 'trc20';
                $transaction['data'] = $tx['raw_data']['contract'][0]['parameter']['value']['data'];
                $transaction['info'] = $this->trc20Tx($tx);
                break;
            case 'TransferAssetContract':
                $transaction['type'] = 'trc10';
                $transaction['info']['to_address_hex'] = $tx['raw_data']['contract'][0]['parameter']['value']['to_address'];
                $transaction['info']['amount'] = $tx['raw_data']['contract'][0]['parameter']['value']['amount'];
                $transaction['info']['contract'] = hex2bin($tx['raw_data']['contract'][0]['parameter']['value']['asset_name']);

                break;
            case 'TransferContract':
                $transaction['type'] = 'trx';
                $transaction['info']['to_address_hex'] = $tx['raw_data']['contract'][0]['parameter']['value']['to_address'];
                $transaction['info']['amount'] = $tx['raw_data']['contract'][0]['parameter']['value']['amount'];
                break;

            default:
                $transaction['type'] = 'other';
                $transaction['info'] = $type;

        }
        return $transaction;
    }

    public function moveTronToMain()
    {
			checkcronkey();
            $coin = 'trx';
            G('begin');

            $coinname = strtolower($coin);

            $main_address = C('coin')[$coinname]['codono_coinaddress'];

            if (C('coin')[$coinname]['type'] != 'tron') {
                die("<span style='color:red'>Warning:This isn't tron type coinname :<b>$coinname</b> or there is configuration issues. Please check");
            }

            if (!$main_address) {
                die("<span style='color:red'>Warning:You have not defined Main Account in <b>$coinname</b> Please fix it first");
            }
            if (C('coin')[$coinname]['dj_yh']) {
                die("<span style='color:red'>Warning:This could be a Token, Use different cron for moving");
            }
            if ($_GET['agree'] != 'yes') {
                $agree_url = $_SERVER['REQUEST_URI'] . "/agree/yes";
                die("<span style='color:red'>Warning:This tool will move all your <b>$coinname</b> funds to main account $main_address ,  <a href='$agree_url'> Do you agree?</a></span>");
            }

            $condition['status'] = 1;
            $condition['shifted_to_main'] = 0;
            $condition['type'] = 'trx';
            $condition['coinname'] = $coinname;
            $transactions = M('Myzr')->where($condition)->order('id asc')->limit(50)->select();

            echo "There would be " . count($transactions) . " attempts</br>";

            $tron = TronClient();

            $unique_address = array();

            foreach ($transactions as $tx) {
                if (in_array($tx['username'], $unique_address)) {
                    $this->markAsMoved($tx['id']);
                    continue;
                }

                $from = $unique_address[] = $tx['username'];
                if (strtolower($main_address) == strtolower($from)) {
                    //because it was main address so don't move
                    continue;
                }
                echo "<br/>*********" . $from . "*********<br/>";
                $ubal = bcsub($tron->getBalance($from), 2, 8);

                if ($ubal <= 0) {
                    echo "balance of " . $tx['username'] . " is less :" . $ubal;
                   // $this->markAsMoved($tx['id']);
                    continue;
                } else {
                    echo "Moving balance of " . $tx['username'] . " Balance :" . $ubal;
                }


                $priv_crypted = M('Tron')->where(array('address_base58' => $tx['username']))->field('private_key')->find();

                $priv = cryptString($priv_crypted['private_key'], 'd');
                $sendrs = $tron->sendTransaction($main_address, $ubal, $tx['username'], $priv);

                if ($sendrs) {
                    echo "<br/>Moved:<br/>";
                    $this->markAsMoved($tx['id']);
                } else {
                    echo "<br/>No pass found for " . $from . '<br/>';
                    continue;
                }

            }
            echo "Cron Ended";
            G('end');
            echo "<br/>Total Time taken " . G('begin', 'end') . 's';

     
    }

    public function moveTokenToMain($token)
    {

        checkcronkey();
		G('begin');
        $coinname = strtolower($token);
        $CoinInfo = C('coin')[$coinname];
        $main_address = $CoinInfo['codono_coinaddress'];
        $decimals = $CoinInfo['cs_qk'];
        $contract_address = $CoinInfo['dj_yh'];
        echo "$coinname has contract address of $contract_address<br/>";
        if ($CoinInfo['type'] != 'tron' || !$contract_address) {
            if (C('coin')[$coinname]['dj_yh'])
                echo "CHECK TYPE";
            die("<span style='color:#ff0000'>Warning:This isn't tron type token :<b>$coinname</b> or there is configuration issues. Please check");
        }


        if (!$main_address) {
            die("<span style='color:red'>Warning:You have not defined Main Account in <b>$coinname</b> Please fix it first");
        }
        if (!$contract_address) {
            die("<span style='color:red'>Warning:This is not a token Use different cron for moving");
        }
        if ($_GET['agree'] != 'yes') {
            $agree_url = $_SERVER['REQUEST_URI'] . "/agree/yes";
            die("<span style='color:red'>Warning:This tool will move all your <b>$coinname</b> funds to main account $main_address ,  <a href='$agree_url'> Do you agree?</a></span>");
        }

        $condition['status'] = 1;
        $condition['shifted_to_main'] = 0;
        $condition['type'] = 'trx';
        $condition['coinname'] = $coinname;
        $transactions = M('Myzr')->where($condition)->order('id asc')->limit(50)->select();

        echo "There would be " . count($transactions) . " attempts</br>";


        $tron = TronClient();

        $unique_address = array();

        foreach ($transactions as $tx) {
            $tronhash = M('TronHash')->where(array('hash' => $tx['txid']))->find();
            if (in_array($tx['username'], $unique_address)) {
                $this->markAsMoved($tx['id']);
                continue;
            }
            $from = $unique_address[] = $tx['username'];
            echo "<br/>*********Moving <strong>" . $coinname . "</strong></strong> from " . $from . " and type is " . $tronhash['type'] . "*********<br/>";
            //check if it has enough gas

            
			$priv_crypted = M('Tron')->where(array('address_base58' => $tx['username']))->field('private_key')->find();
            $priv = cryptString($priv_crypted['private_key'], 'd');
			
			
			if ($tronhash['type'] == 'trc20') {
                $abi = $tron->getAbi($coinname);
                
                $token_balance = $tron->getTrc20Balance($tx['username'], $contract_address, $decimals, $abi);
            } elseif ($tronhash['type'] == 'trc10') {
                $token_balance = $tron->getTrc10Balance($tx['username'], $contract_address, $decimals);
            } else {

                echo "<br/> Incorrect Type" . $tx['type'];
                continue;
            }
			if ($token_balance <= 0) {
                echo "$coinname balance of " . $tx['username'] . " is less :" . $token_balance;
                $this->markAsMoved($tx['id']);
                continue;
            } else {
                echo "Moving balance of " . $tx['username'] . " Balance :" . $token_balance;
            }
			
			$gas_balance = $tron->getBalance($tx['username']);
            if ($gas_balance < 1) {
                echo $gas_balance." is gas balance so sending 1 trx as gas from main wallet to " . $tx['username'];
                $freeze_amount=1;
				$gas_amount=2;
				$gas_sent = $this->sendGasMoney($tx['username'], $gas_amount);
                $freeze=$tron->freezeBalance($tx['username'],$freeze_amount,$priv);
				echo "<pre/>";
                print_r($gas_sent);
				print_r($freeze);
                echo "</pre>We will retry to extract this balance on next run as we just sent some gas to address, We will also $freeze_amount  trx:";
				continue;
            }
            
			

			//$check_account_resources=$tron->getAccountResources($tx['username']);
			//dump($check_account_resources);
			//$check_account=$tron->getAccount($tx['username']);
			
            


            $sendrs = false;

            if ($tronhash['type'] == 'trc20') {
                //todo for trc20
                $abi = $tron->getAbi($CoinInfo['name']);
                $sendrs = $tron->transferTrc20($abi, $contract_address, $main_address, $token_balance, $tx['username'], $priv, $decimals);
            } else if ($tronhash['type'] == 'trc10') {
                //todo for trc10 transfer
                $sendrs = $tron->transferTrc10($main_address, $token_balance, $contract_address, $tx['username'], $priv, $decimals);
            }

            if ($sendrs) {
                echo "<br/>Moved:<br/>";
                $this->markAsMoved($tx['id']);
            } else {
                print_r($sendrs);
                echo "<br/>Could not move token from " . $from . '<br/>';
            }

        }
        echo "Cron Ended";
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';

    }

    private function markAsMoved($id)
    {
        $status = M('Myzr')->where(array('id' => $id))->save(array('shifted_to_main' => 1));

        if ($status) {
            return true;
        } else {
            return false;
        }

    }


}