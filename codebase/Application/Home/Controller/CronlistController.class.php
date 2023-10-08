<?php

namespace Home\Controller;


class CronlistController extends HomeController
{
    //All queue in one controller

    public function __construct(){
        parent::__construct();
        checkcronkey();
    }
    public function index(){
        //Queue
        //Queue2
        $array['Coin']=[
            ['name'=>'deposit_btctype','params'=>[],'frequency'=>'every min','about'=>'Bitcoin Deposit'],
            ['name'=>'deposit_cryptoapis','params'=>[],'frequency'=>'every min','about'=>'CryptoApis Deposit'],
            ['name'=>'substrate_deposit','params'=>[],'frequency'=>'every min','about'=>'Substrate Deposit'],
            ['name'=>'getWithdrawalIdSubstrate','params'=>[],'frequency'=>'every 5 mins','about'=>'Substrate Withdrawal hash retrieve'],
            ['name'=>'blockgum_deposit','params'=>[],'frequency'=>'every min','about'=>'Blockgum Deposit'],
            ['name'=>'getWithdrawalIdBlockgum','params'=>[],'frequency'=>'every 5 mins','about'=>'Blockgum Withdrawal hash retrieve'],
            ['name'=>'blockgum_token_to_main','params'=>['coinname'=>'coinNameHere'],'frequency'=>'When required','about'=>'Blockgum Move tokens to main'],
            ['name'=>'blockgum_coin_to_main','params'=>['coinname'=>'tokenNameHere'],'frequency'=>'When required','about'=>'Blockgum Move Coins to main'],
            
            ['name'=>'wallet_cryptonote_deposit','params'=>[],'frequency'=>'every min','about'=>'Cryptonote deposit'],
            ['name'=>'wallet_cryptonote2_deposit','params'=>[],'frequency'=>'every min','about'=>'Cryptonote deposit'],
            ['name'=>'wallet_blockio_deposit','params'=>[],'frequency'=>'every 5 mins','about'=>'Block.IO deposit'],
            ['name'=>'wallet_blockio_withdraw','params'=>[],'frequency'=>'every 5 mins','about'=>'Block.IO Withdrawal'],
            ['name'=>'esmart_deposit','params'=>['chain'=>'chainNameHere'],'frequency'=>'every 1 min','about'=>'EVM Deposit Detection'],
            ['name'=>'esmart_to_main','params'=>['coin'=>'coinNameHere'],'frequency'=>'When Required','about'=>'Tool to move EVM Deposit to Main'],
            ['name'=>'esmart_token_to_main','params'=>['coinname'=>'coinNameHere'],'frequency'=>'When required','about'=>'Tool to move EVM Token Deposit to Main'],
            ['name'=>'esmart_move_all_tokens_to_main','params'=>[],'frequency'=>'When required','about'=>'Tool to move All EVM Token Deposit to Main'],
            ['name'=>'xrpdeposit','params'=>array(),'frequency'=>'every 1 min','about'=>'EVM Deposit Detection'],
            ['name'=>'wallet_waves_deposit','params'=>[],'frequency'=>'every 1 min','about'=>'Waves Deposit Detection'],
            ['name'=>'MoveFundsToWaveMainAccount','params'=>['name'=>'tokeNameHere'],'frequency'=>'When Required','about'=>'Tool to move Wames Assets Deposit to Main'],
            ['name'=>'MoveWaves2MainAccount','params'=>[],'frequency'=>'When required','about'=>'Tool to move Waves Deposit to Main'],
            ['name'=>'wallet_coinpay_deposit','params'=>[],'frequency'=>'Every 5 mins','about'=>'Cron to detect Deposits'],
            ['name'=>'wallet_coinpay_withdraw','params'=>[],'frequency'=>'Every 5 mins','about'=>'Cron to get withdrawal hash'],
            ['name'=>'move2cold','params'=>[],'frequency'=>'When required','about'=>'**EXPERIMENTAL** Use with caution make sure coldwallets are properly defined.'],
        ];
        $array['Tron']=[

            ['name'=>'cronDeposits','params'=>[],'frequency'=>'every 1 min','about'=>'Tron Deposit Detection'],
            ['name'=>'moveTronToMain','params'=>[],'frequency'=>'When Required','about'=>'Tool to move Tron Deposit to Main'],
            ['name'=>'moveTokenToMain','params'=>['token'=>'tokenNameHere'],'frequency'=>'When required','about'=>'Tool to move TRC20/TRC1- Token Deposit to Main']
        ];

        $array['Xtrade']=[
            ['name'=>'cronMe','params'=>[],'frequency'=>'every min','about'=>'When Binance keys are set in other_config run this to cross trade with Spot orders'],
            ['name'=>'otcTrade','params'=>[],'frequency'=>'every min','about'=>'When Binance keys are set in other_config run this to cross trade with Otc orders'],
        ];
        $array['Selfengine']=[
            ['name'=>'CreateOrderbook','params'=>[],'frequency'=>'every min','about'=>'When Using a market with selfengine'],
            ['name'=>'cleanUp','params'=>[],'frequency'=>'every day','about'=>'Delete Trade_log where userid,peerid is 0 and older than 2 days']
        ];

        $array['Queue']=[
            ['name'=>'checkInvest','params'=>[],'frequency'=>'every day','about'=>'Staking Release'],
            ['name'=>'BinanceUpdate','params'=>[],'frequency'=>'every min','about'=>'Binance Pricing Update'],
            ['name'=>'ExchangeBinanceUpdate','params'=>[],'frequency'=>'every min','about'=>'Liquidity Update '],
            ['name'=>'cmcUpdate','params'=>[],'frequency'=>'every min','about'=>'cmcPricing Update Run1'],
            ['name'=>'cmcUpdateRate','params'=>[],'frequency'=>'every min','about'=>'cmcPricing Update Run2'],
            ['name'=>'send_notifications','params'=>[],'frequency'=>'every min','about'=>'Sending Emails'],
            ['name'=>'fix_user_coin','params'=>[],'frequency'=>'When required','about'=>'Fixes Missing column for user balance , only run when you add new coins'],
            ['name'=>'genInternalCharts','params'=>[],'frequency'=>'every 10 min','about'=>'Run if internal trades are being used'],
            ['name'=>'tendency','params'=>[],'frequency'=>'When required','about'=>'Optional: To generate Tendency Chart'],
            ['name'=>'setHourlyPrice','params'=>[],'frequency'=>'When Required','about'=>'Optional: Tool to Generate Pricing '],
            ['name'=>'setMarketCoinStats','params'=>[],'frequency'=>'When required','about'=>'Not in use any more'],
            ['name'=>'matchOrdersManually','params'=>[],'frequency'=>'When required','about'=>'Not in use any more'],
            ['name'=>'clearRedisForLiquidity','params'=>[],'frequency'=>'When required','about'=>'Use when need to clear cache redis cache of orderbooks'],
            ['name'=>'fixTrades','params'=>[],'frequency'=>'When required','about'=>'**EXPERIMENTAL** Not required'],
        ];

        foreach($array as $key=>$cron){
            echo "Controller:".$key."<br/>";
            foreach($cron as $Q2) {
                echo "***************<pre>";
                echo implode('<br/>',$this->infoMarker($key,$Q2));
                echo "</pre>***************";
            }
        }
    }
    function infoMarker($key,$Q2): array
    {
        $params='';
        if($Q2['params']) {
        $params='?'.http_build_query($Q2['params']);
        }
        $ret['command']=PHP_PATH .' '.getcwd().'/index.php '.$key.'/'. $Q2['name'].'/securecode/'.CRON_KEY.'/'.$params;
        $ret['url']=SITE_URL . $key . "/" . $Q2['name'].'/securecode/'.CRON_KEY.'/'.$params;
        $ret['frequency']=$Q2['frequency'];
        $ret['about']=$Q2['about'];
        $ret['run']="<a href='".$ret['url']."' target='_blank'>".$ret['url']."</a>";
        return $ret;
    }
    public function test(){
        $Q2=['params'=>['coinname'=>'eth']];
        if($Q2['params']) {
            var_dump($Q2['params']);
            $params=http_build_query($Q2['params']);
            var_dump(10,$params);
        }

    }
}
