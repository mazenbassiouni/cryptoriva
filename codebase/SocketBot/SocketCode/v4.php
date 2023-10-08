<?php

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Redis\Client;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

require_once __DIR__ . '/SocketRedis.class.php';

$context = array(
    'ssl' => array(
        //'local_cert'  => '/path_to/apache1.crt',
        //'local_pk'    => '/path_to/apache1.key',
        'verify_peer' => false,
        'verify_peer_name' => false,
        // 'allow_self_signed' => true
    )
);

$huobi_ws_feed = 'ws://api.huobi.pro:443/ws';
$worker = new Worker('websocket://0.0.0.0:7272', $context);
$worker->transport = 'tcp';

$worker->count = 4;
$worker->name = 'Huobi_wssV2';
$worker->onWorkerStart = function ($worker) {

    global $huobi_ws_feed;
    global $redis;
    $redis = new Client('redis://127.0.0.1:6379');

    $con = new AsyncTcpConnection($huobi_ws_feed);
    $con->transport = 'ssl';

    $con->onConnect = function ($con) {
        global $redis;

        $redis->get(CRON_KEY . '_ext_socket', function ($marks) use ($con) {
            $markets = json_decode($marks);

            if ($markets && !empty($markets)) {
                $klintime = ["1min","5min","15min","30min","60min","4hour","1day","1mon","1week","1year"];

                foreach ($markets as $mark) {
                    $value = str_replace('_', '', $mark);

                    foreach ($klintime as $v) {
                        $data = json_encode([
                            'sub' => "market.{$value}.kline.{$v}",
                            'id' => "id" . time(),
                            'freq-ms' => 5000
                        ]);
                        $con->send($data);
                    }

                    $depth = json_encode([
                        'sub' => "market.{$value}.depth.step1",
                        'id' => $value . "dep" . time()
                    ]);
                    $con->send($depth);

                    $trade = json_encode([
                        'sub' => "market.{$value}.trade.detail",
                        'id' => $value . "trade" . time()
                    ]);
                    $con->send($trade);

                    $detail = json_encode([
                        'sub' => "market.{$value}.detail",
                        'id' => $value . "detail" . time()
                    ]);
                    $con->send($detail);
                }
            }
        });
    };

    $con->onMessage = function ($con, $data) use ($worker) {
        $data = gzdecode($data);
        $data = json_decode($data, true);
        if (isset($data['ping'])) {
            $con->send(json_encode([
                "pong" => $data['ping']
            ]));
        } else {
            $msg = [];

            if (isset($data['ch'])) {
                $hbrds = new SocketRedis("127.0.0.1", 6379);
                $pieces = explode(".", $data['ch']);
                switch ($pieces[2]) {
                    case "kline":
                        $msg['type'] = "tradingview";
                        $msg['market'] = $pieces[1];
                        $msg['open'] = $data['tick']['open'];
