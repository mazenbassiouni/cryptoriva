<?php

namespace Common\Model;

use Think\Model;

class TradeModel extends Model
{
    protected $keyS = 'Trade';

    public function moni($market = NULL)
    {
        if (empty($market)) {
            return null;
        }

        $userid = rand(86345, 86355);
        $type = 1;
        $min_price = round(C('market')[$market]['buy_min'] * 100000);
        $max_price = round(C('market')[$market]['buy_max'] * 100000);
        $new_price = round(C('market')[$market]['new_price'] * 100000);
        $aa = array(1, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1);
        $bb = date('H', time()) * 1;

        if ($aa[$bb]) {
            // TODO: SEPARATE
            $price = round(rand($new_price, $max_price) / 100000, C('market')[$market]['round']);
            echo '1 ' . "\n";
        } else {
            // TODO: SEPARATE
            $price = round(rand($min_price, $new_price) / 100000, C('market')[$market]['round']);
            echo '0 ' . "\n";
        }

        echo $market . '|' . $price . "\n";
        $max_num = round((C('market')[$market]['trade_max'] / C('market')[$market]['buy_max']) * 10000, 8);
        $min_num = round((1 / C('market')[$market]['buy_max']) * 10000, 8);
        $num = round(abs(rand($min_num, $max_num)) / 10000, 8);

        if (!$price) {
            return 'The transaction price is malformed';
        }

        if (!check($num, 'double')) {
            return 'The number of transactions is malformed' . $num;
        }

        if (($type != 1) && ($type != 2)) {
            return 'Transaction type format error';
        }

        if (!C('market')[$market]) {
            return 'Error market';
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        if (!C('market')[$market]['trade']) {
            return 'The current market is prohibited transaction';
        }
        // TODO: SEPARATE

        $price = format_num($price, C('market')[$market]['round']);

        if (!$price) {
            return 'The transaction price error';
        }

        $num = round(trim($num), 8);

        if (!check($num, 'double')) {
            return 'Trading the number of errors';
        }

        if ($type == 1) {
            $min_price = (C('market')[$market]['buy_min'] ? C('market')[$market]['buy_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['buy_max'] ? C('market')[$market]['buy_max'] : 10000000);
        } else if ($type == 2) {
            $min_price = (C('market')[$market]['sell_min'] ? C('market')[$market]['sell_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['sell_max'] ? C('market')[$market]['sell_max'] : 10000000);
        } else {
            return 'Transaction type error';
        }

        if ($max_price < $price) {
            return 'Trading price exceeding the maximum limit!';
        }

        if ($price < $min_price) {
            return 'Trading price exceeds the minimum limit!' . $price . '-' . $min_price;
        }

        $hou_price = C('market')[$market]['hou_price'];
        /*
        if ($hou_price) {
        }
        */
        $user_coin = M('UserCoin')->where(array('userid' => $userid))->find();

        if ($type == 1) {
            $trade_fee = C('market')[$market]['fee_buy'];

            if ($trade_fee) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);
                $mum = round((($num * $price) / 100) * (100 + $trade_fee), 8);
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }

            if ($user_coin[$rmb] < $mum) {
                return C('coin')[$rmb]['title'] . 'Insufficient balance!';
            }
        } else if ($type == 2) {
            $trade_fee = C('market')[$market]['fee_sell'];

            if ($trade_fee) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);
                $mum = round((($num * $price) / 100) * (100 - $trade_fee), 8);
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }

            if ($user_coin[$xnb] < $num) {
                return C('coin')[$xnb]['title'] . 'Insufficient balance2!';
            }
        } else {
            return 'Transaction type error';
        }

        if (C('coin')[$xnb]['fee_bili']) {
            if ($type == 2) {
                $bili_user = round($user_coin[$xnb] + $user_coin[$xnb . 'd'], 8);

                if ($bili_user) {
                    $bili_keyi = round(($bili_user / 100) * C('coin')[$xnb]['fee_bili'], 8);

                    if ($bili_keyi) {
                        $bili_zheng = M()->query('select id,price,sum(num-deal)as nums from codono_trade where userid=' . userid() . ' and status=0 and type=2 and market like \'%' . $xnb . '%\' ;');

                        if (!$bili_zheng[0]['nums']) {
                            $bili_zheng[0]['nums'] = 0;
                        }

                        $bili_kegua = $bili_keyi - $bili_zheng[0]['nums'];

                        if ($bili_kegua < 0) {
                            $bili_kegua = 0;
                        }

                        if ($bili_kegua < $num) {
                            return 'Your total number of pending orders exceeds the system limit, you currently hold' . C('coin')[$xnb]['title'] . $bili_user . 'Months, has been pending' . $bili_zheng[0]['nums'] . 'One can also pending' . $bili_kegua . 'More';
                        }
                    } else {
                        return 'Trading volume can be wrong';
                    }
                }
            }
        }

        if (C('market')[$market]['trade_min']) {
            if ($mum < C('market')[$market]['trade_min']) {
                return 'Total transaction can not be less than' . C('market')[$market]['trade_min'];
            }
        }

        if (C('market')[$market]['trade_max']) {
            if (C('market')[$market]['trade_max'] < $mum) {
                return 'Total transaction can not be greater than' . C('market')[$market]['trade_max'];
            }
        }

        if (!$rmb) {
            return 'data error1';
        }

        if (!$xnb) {
            return 'data error2';
        }

        if (!$market) {
            return 'data error3';
        }

        if (!$price) {
            return 'data error4';
        }

        if (!$num) {
            return 'data error5';
        }

        if (!$mum) {
            return 'data error6';
        }

        if (!$type) {
            return 'data error7';
        }

        $mo = M();

        $mo->startTrans();
        $rs = array();

        if ($type == 1) {
            $finance = $mo->table('codono_finance')->where(array('userid' => $userid))->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $userid))->find();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setDec($rmb, $mum);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($rmb . 'd', $mum);
            $rs[] = $finance_nameid = $mo->table('codono_trade')->add(array('userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));
            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $userid))->find();
            $finance_hash = md5($userid . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $userid, 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $mum, 'type' => 2, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Buying commission-market' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
        } else if ($type == 2) {
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setDec($xnb, $num);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($xnb . 'd', $num);
            $rs[] = $mo->table('codono_trade')->add(array('userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
        } else {
            $mo->rollback();

            return 'Transaction type error';
        }

        if (check_arr($rs)) {
            $mo->commit();

            $this->dapan($market);
            return 'Trading success!';
        } else {
            $mo->rollback();

            mlog('bb ' . implode('|', $rs));
            return null;
        }
    }

    public function dapan($market = NULL)
    {
        if (!$market) {
            return false;
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        $fee_buy = C('market')[$market]['fee_buy'];
        $fee_sell = C('market')[$market]['fee_sell'];
        $invit_buy = C('market')[$market]['invit_buy'];
        $invit_sell = C('market')[$market]['invit_sell'];
        $invit_1 = C('market')[$market]['invit_1'];
        $invit_2 = C('market')[$market]['invit_2'];
        $invit_3 = C('market')[$market]['invit_3'];
        $mo = M();
        $new_trade_codono = 0;
        $i = 1;

        for (; $i < 30; $i++) {
            $buy = $mo->table('codono_trade')->where(array('market' => $market, 'type' => 1, 'status' => 0))->order('price desc,id asc')->find();
            $sell = $mo->table('codono_trade')->where(array('market' => $market, 'type' => 2, 'status' => 0))->order('price asc,id asc')->find();

            if ($sell['id'] < $buy['id']) {
                $type = 1;
            } else {
                $type = 2;
            }

            if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
                $rs = array();

                if ($buy['num'] <= $buy['deal']) {
                }

                if ($sell['num'] <= $sell['deal']) {
                }

                $amount = min(round($buy['num'] - $buy['deal'], 8), round($sell['num'] - $sell['deal'], 8));
                $amount = round($amount, 8);

                if ($amount <= 0) {
                    $log = 'error1market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . "\n";
                    $log .= 'ERR: DealQuantityError,QuantityYes' . $amount;
                    mlog($log);
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    break;
                }

                if ($type == 1) {
                    $price = $sell['price'];
                } else if ($type == 2) {
                    $price = $buy['price'];
                } else {
                    break;
                }

                if (!$price) {
                    $log = 'error2market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . "\n";
                    $log .= 'ERR: DealpriceError,priceYes' . $price;
                    mlog($log);
                    break;
                } else {
                    // TODO: SEPARATE
                    $price = round($price, C('market')[$market]['round']);
                }

                $mum = round($price * $amount, 8);

                if (!$mum) {
                    $log = 'error3market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . "\n";
                    $log .= 'ERR: The total turnover of the error, the total is' . $mum;
                    mlog($log);
                    break;
                } else {
                    $mum = round($mum, 8);
                }

                if ($fee_buy) {
                    $buy_fee = round(($mum / 100) * $fee_buy, 8);
                    $buy_save = round(($mum / 100) * (100 + $fee_buy), 8);
                } else {
                    $buy_fee = 0;
                    $buy_save = $mum;
                }

                if (!$buy_save) {
                    $log = 'error4market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: BuyersUpdateQuantityError,UpdateQuantityYes' . $buy_save;
                    mlog($log);
                    break;
                }

                if ($fee_sell) {
                    $sell_fee = round(($mum / 100) * $fee_sell, 8);
                    $sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
                } else {
                    $sell_fee = 0;
                    $sell_save = $mum;
                }

                if (!$sell_save) {
                    $log = 'error5market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: SellersUpdateQuantityError,UpdateQuantityYes' . $sell_save;
                    mlog($log);
                    break;
                }

                $user_buy = M('UserCoin')->where(array('userid' => $buy['userid']))->find();

                if (!$user_buy[$rmb . 'd']) {
                    $log = 'error6market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: Property buyerserror,freezeProperty is' . $user_buy[$rmb . 'd'];
                    mlog($log);
                    break;
                }

                $user_sell = M('UserCoin')->where(array('userid' => $sell['userid']))->find();

                if (!$user_sell[$xnb . 'd']) {
                    $log = 'error7market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: Sellers of propertyerror,freezeProperty is' . $user_sell[$xnb . 'd'];
                    mlog($log);
                    break;
                }

                if ($user_buy[$rmb . 'd'] < 1.0E-8) {
                    $log = 'error88market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: Buyers Update Freeze USD appear error,Should be updated' . $buy_save . 'Account Balance' . $user_buy[$rmb . 'd'] . 'Error handling';
                    mlog($log);
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    break;
                }

                if ($buy_save <= round($user_buy[$rmb . 'd'], 8)) {
                    $save_buy_rmb = $buy_save;
                } else if ($buy_save <= round($user_buy[$rmb . 'd'], 8) + 1) {
                    $save_buy_rmb = $user_buy[$rmb . 'd'];
                    $log = 'error8market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: Buyers Update Freeze USD Error occurs,Should be updated' . $buy_save . 'Account Balance' . $user_buy[$rmb . 'd'] . 'The actual update' . $save_buy_rmb;
                    mlog($log);
                } else {
                    $log = 'error9market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: Buyers Update Freeze USD appear error,Should be updated' . $buy_save . 'Account Balance' . $user_buy[$rmb . 'd'] . 'Error handling';
                    mlog($log);
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    break;
                }
                // TODO: SEPARATE

                if ($amount <= round($user_sell[$xnb . 'd'], C('market')[$market]['round'])) {
                    $save_sell_xnb = $amount;
                } else {
                    // TODO: SEPARATE

                    if ($amount <= round($user_sell[$xnb . 'd'], C('market')[$market]['round']) + 1) {
                        $save_sell_xnb = $user_sell[$xnb . 'd'];
                        $log = 'error10market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                        $log .= 'ERR: SellersUpdatefreezeVirtual currencyError occurs,Should be updated' . $amount . 'Account Balance' . $user_sell[$xnb . 'd'] . 'The actual update' . $save_sell_xnb;
                        mlog($log);
                    } else {
                        $log = 'error11market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                        $log .= 'ERR: SellersUpdatefreezeVirtual currencyappearerror,Should be updated' . $amount . 'Account Balance' . $user_sell[$xnb . 'd'] . 'Error handling';
                        mlog($log);
                        M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                        break;
                    }
                }

                if (!$save_buy_rmb) {
                    $log = 'error12market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: BuyersUpdateQuantityErrorerror,UpdateQuantityYes' . $save_buy_rmb;
                    mlog($log);
                    M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    break;
                }

                if (!$save_sell_xnb) {
                    $log = 'error13market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n";
                    $log .= 'ERR: SellersUpdateQuantityErrorerror,UpdateQuantityYes' . $save_sell_xnb;
                    mlog($log);
                    M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    break;
                }


                $mo->startTrans();
                $rs[] = $mo->table('codono_trade')->where(array('id' => $buy['id']))->setInc('deal', $amount);
                $rs[] = $mo->table('codono_trade')->where(array('id' => $sell['id']))->setInc('deal', $amount);
                $rs[] = $finance_nameid = $mo->table('codono_trade_log')->add(array('userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->setInc($xnb, $amount);
                $finance = $mo->table('codono_finance')->where(array('userid' => $buy['userid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->setDec($rmb . 'd', $save_buy_rmb);
                $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();
                $finance_hash = md5($buy['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                $rs[] = $mo->table('codono_finance')->add(array('userid' => $buy['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Buy success-market' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                $finance = $mo->table('codono_finance')->where(array('userid' => $sell['userid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $sell['userid']))->find();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $sell['userid']))->setInc($rmb, $sell_save);
                $save_buy_rmb = $sell_save;
                $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $sell['userid']))->find();
                $finance_hash = md5($sell['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                $rs[] = $mo->table('codono_finance')->add(array('userid' => $sell['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Success sell-market' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $sell['userid']))->setDec($xnb . 'd', $save_sell_xnb);
                $buy_list = $mo->table('codono_trade')->where(array('id' => $buy['id'], 'status' => 0))->find();

                if ($buy_list) {
                    if ($buy_list['num'] <= $buy_list['deal']) {
                        $rs[] = $mo->table('codono_trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    }
                }

                $sell_list = $mo->table('codono_trade')->where(array('id' => $sell['id'], 'status' => 0))->find();

                if ($sell_list) {
                    if ($sell_list['num'] <= $sell_list['deal']) {
                        $rs[] = $mo->table('codono_trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    }
                }

                if ($price < $buy['price']) {
                    $chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 8);
                    $chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 8);
                    $chajia = round($chajia_dong - $chajia_shiji, 8);

                    if ($chajia) {
                        $chajia_user_buy = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();

                        if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8)) {
                            $chajia_save_buy_rmb = $chajia;
                        } else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8) + 1) {
                            $chajia_save_buy_rmb = $chajia_user_buy[$rmb . 'd'];
                            mlog('error91market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n");
                            mlog('market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'The number of transactions' . $amount . 'means of transaction:' . $type . 'SellersUpdatefreezeVirtual currencyError occurs,Should be updated' . $chajia . 'Account Balance' . $chajia_user_buy[$rmb . 'd'] . 'The actual update' . $chajia_save_buy_rmb);
                        } else {
                            mlog('error92market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'means of transaction:' . $type . 'The number of transactions' . $amount . 'deal price' . $price . 'Total turnover' . $mum . "\n");
                            mlog('market place' . $market . 'Error: Buy Orders:' . $buy['id'] . 'Sell order:' . $sell['id'] . 'The number of transactions' . $amount . 'means of transaction:' . $type . 'SellersUpdatefreezeVirtual currencyappearerror,Should be updated' . $chajia . 'Account Balance' . $chajia_user_buy[$rmb . 'd'] . 'Error handling');
                            $mo->rollback();

                            M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                            M('Trade')->execute('commit');
                            break;
                        }

                        if ($chajia_save_buy_rmb) {
                            $finance = $mo->table('codono_finance')->where(array('userid' => $buy['userid']))->order('id desc')->find();
                            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();
                            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->setDec($rmb . 'd', $chajia_save_buy_rmb);
                            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->setInc($rmb, $chajia_save_buy_rmb);
                            $save_buy_rmb = $chajia_save_buy_rmb;
                            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();
                            $finance_hash = md5($buy['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                            if ($finance['mum'] < $finance_num) {
                                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                            } else {
                                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                            }

                            $rs[] = $mo->table('codono_finance')->add(array('userid' => $buy['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Buyers commission-return' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        }
                    }
                }

                $you_buy = $mo->table('codono_trade')->where(array(
                    'market' => array('like', '%' . $rmb . '%'),
                    'status' => 0,
                    'userid' => $buy['userid']
                ))->find();
                $you_sell = $mo->table('codono_trade')->where(array(
                    'market' => array('like', '%' . $xnb . '%'),
                    'status' => 0,
                    'userid' => $sell['userid']
                ))->find();

                if (!$you_buy) {
                    $you_user_buy = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();

                    if (0 < $you_user_buy[$rmb . 'd']) {
                        $finance = $mo->table('codono_finance')->where(array('userid' => $buy['userid']))->order('id desc')->find();
                        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->setField($rmb . 'd', 0);
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->setInc($rmb, $you_user_buy[$rmb . 'd']);
                        $save_buy_rmb = $you_user_buy[$rmb . 'd'];
                        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $buy['userid']))->find();
                        $finance_hash = md5($buy['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                        $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }

                        $rs[] = $mo->table('codono_finance')->add(array('userid' => $buy['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Buyers commission-thaw' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    }
                }

                if (!$you_sell) {
                    $you_user_sell = $mo->table('codono_user_coin')->where(array('userid' => $sell['userid']))->find();

                    if (0 < $you_user_sell[$xnb . 'd']) {
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $sell['userid']))->setField($xnb . 'd', 0);
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $sell['userid']))->setInc($rmb, $you_user_sell[$xnb . 'd']);
                    }
                }

                $invit_buy_user = $mo->table('codono_user')->where(array('id' => $buy['userid']))->find();
                $invit_sell_user = $mo->table('codono_user')->where(array('id' => $sell['userid']))->find();

                if ($invit_buy) {
                    if ($invit_1) {
                        if ($buy_fee) {
                            if ($invit_buy_user['invit_1']) {
                                $invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 6);

                                if (0 < $invit_buy_save_1) {
                                    $finance = $mo->table('codono_finance')->where(array('userid' => $invit_buy_user['invit_1']))->order('id desc')->find();
                                    $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_1']))->find();
                                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_1']))->setInc($rmb, $invit_buy_save_1);
                                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$rmb,'userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => '1st Tier Buy Bonus', 'type' => $market . 'Buy trade gift', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 1));
                                    $save_buy_rmb = $invit_buy_save_1;
                                    $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_1']))->find();
                                    $finance_hash = md5($invit_buy_user['invit_1'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                                    $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                                    if ($finance['mum'] < $finance_num) {
                                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                                    } else {
                                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                                    }

                                    $rs[] = $mo->table('codono_finance')->add(array('userid' => $invit_buy_user['invit_1'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '1st Tier Trading Bonus ' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                                }
                            }

                            if ($invit_buy_user['invit_2']) {
                                $invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 6);

                                if (0 < $invit_buy_save_2) {
                                    $finance = $mo->table('codono_finance')->where(array('userid' => $invit_buy_user['invit_2']))->order('id desc')->find();
                                    $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_2']))->find();
                                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_2']))->setInc($rmb, $invit_buy_save_2);
                                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$rmb,'userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => '2nd Tier buying gift', 'type' => $market . 'Buy trade gift', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 1));
                                    $save_buy_rmb = $invit_buy_save_2;
                                    $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_2']))->find();
                                    $finance_hash = md5($invit_buy_user['invit_2'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                                    $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                                    if ($finance['mum'] < $finance_num) {
                                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                                    } else {
                                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                                    }

                                    $rs[] = $mo->table('codono_finance')->add(array('userid' => $invit_buy_user['invit_2'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Trading II gift' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                                }
                            }

                            if ($invit_buy_user['invit_3']) {
                                $invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 6);

                                if (0 < $invit_buy_save_3) {
                                    $finance = $mo->table('codono_finance')->where(array('userid' => $invit_buy_user['invit_3']))->order('id desc')->find();
                                    $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_3']))->find();
                                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_3']))->setInc($rmb, $invit_buy_save_3);
                                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$rmb,'userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => 'Three generations of buying gift', 'type' => $market . 'Buy trade gift', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 1));
                                    $save_buy_rmb = $invit_buy_save_3;
                                    $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_buy_user['invit_3']))->find();
                                    $finance_hash = md5($invit_buy_user['invit_3'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                                    $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                                    if ($finance['mum'] < $finance_num) {
                                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                                    } else {
                                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                                    }

                                    $rs[] = $mo->table('codono_finance')->add(array('userid' => $invit_buy_user['invit_3'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Trading presented three generations' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                                }
                            }
                        }
                    }

                    if ($invit_sell) {
                        if ($sell_fee) {
                            if ($invit_sell_user['invit_1']) {
                                $invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 6);

                                if (0 < $invit_sell_save_1) {
                                    $finance = $mo->table('codono_finance')->where(array('userid' => $invit_sell_user['invit_1']))->order('id desc')->find();
                                    $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_1']))->find();
                                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_1']))->setInc($rmb, $invit_sell_save_1);
                                    $rs[] = $mo->table('codono_user_coin')->getLastSql();
                                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$rmb,'userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => 'Generation sell gift', 'type' => $market . 'Sell trade gift', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 1));
                                    $save_buy_rmb = $invit_sell_save_1;
                                    $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_1']))->find();
                                    $finance_hash = md5($invit_sell_user['invit_1'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                                    $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                                    if ($finance['mum'] < $finance_num) {
                                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                                    } else {
                                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                                    }

                                    $rs[] = $mo->table('codono_finance')->add(array('userid' => $invit_sell_user['invit_1'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Trading generations gift' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                                }
                            }

                            if ($invit_sell_user['invit_2']) {
                                $invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 6);

                                if (0 < $invit_sell_save_2) {
                                    $finance = $mo->table('codono_finance')->where(array('userid' => $invit_sell_user['invit_2']))->order('id desc')->find();
                                    $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_2']))->find();
                                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_2']))->setInc($rmb, $invit_sell_save_2);
                                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$rmb,'userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => 'II sell gift', 'type' => $market . 'Sell trade gift', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 1));
                                    $save_buy_rmb = $invit_sell_save_2;
                                    $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_2']))->find();
                                    $finance_hash = md5($invit_sell_user['invit_2'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                                    $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                                    if ($finance['mum'] < $finance_num) {
                                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                                    } else {
                                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                                    }

                                    $rs[] = $mo->table('codono_finance')->add(array('userid' => $invit_sell_user['invit_2'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Get a deal on behalf of the second generation' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                                }
                            }

                            if ($invit_sell_user['invit_3']) {
                                $invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 6);

                                if (0 < $invit_sell_save_3) {
                                    $finance = $mo->table('codono_finance')->where(array('userid' => $invit_sell_user['invit_3']))->order('id desc')->find();
                                    $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_3']))->find();
                                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_3']))->setInc($rmb, $invit_sell_save_3);
                                    $rs[] = $mo->table('codono_invit')->add(array('coin'=>$rmb,'userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => '3rd Tier Sell Bonus', 'type' => $market . 'Sell trade gift', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 1));
                                    $save_buy_rmb = $invit_sell_save_3;
                                    $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $invit_sell_user['invit_3']))->find();
                                    $finance_hash = md5($invit_sell_user['invit_3'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                                    $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                                    if ($finance['mum'] < $finance_num) {
                                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                                    } else {
                                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                                    }

                                    $rs[] = $mo->table('codono_finance')->add(array('userid' => $invit_sell_user['invit_3'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'Trading Center-Get a deal on behalf of 3rd Tier ' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                                }
                            }
                        }
                    }
                }

                if (check_arr($rs)) {
                    $mo->commit();

                    $new_trade_codono = 1;
                    S('allsum', null);
                    S('getJsonTop' . $market, null);
                    S('getTradelog' . $market, null);
                    S('getDepth' . $market . '1', null);
                    S('getDepth' . $market . '3', null);
                    S('getDepth' . $market . '4', null);
                    S('ChartgetJsonData' . $market, null);
                    S('allcoin', null);
                    S('trends', null);
                } else {
                    $mo->rollback();

                    mlog('bb ' . implode('|', $rs));
                }
            } else {
                break;
            }

            unset($rs);
        }

        if ($new_trade_codono) {
            $new_price = round(M('TradeLog')->where(array('market' => $market, 'status' => 1))->order('id desc')->getField('price'), 6);
            $buy_price = round(M('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0))->max('price'), 6);
            $sell_price = round(M('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0))->min('price'), 6);
            $min_price = round(M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->min('price'), 6);
            $max_price = round(M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->max('price'), 6);
            $volume = round(M('TradeLog')->where(array(
                'market' => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->sum('num'), 6);
            $sta_price = round(M('TradeLog')->where(array(
                'market' => $market,
                'status' => 1,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->order('id asc')->getField('price'), 6);
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

            $change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
            $upCoinData['change'] = $change;

            if ($upCoinData) {
                M('Market')->where(array('name' => $market))->save($upCoinData);
                M('Market')->execute('commit');
                S('home_market', null);
            }
        }
    }

    public function hangqing($market = NULL)
    {
        if (empty($market)) {
            return null;
        }

        $timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

        foreach ($timearr as $k => $v) {
            $tradeJson = M('TradeJson')->where(array('market' => $market, 'type' => $v))->order('id desc')->find();

            if ($tradeJson) {
                $addtime = $tradeJson['addtime'];
            } else {
                $addtime = M('TradeLog')->where(array('market' => $market))->order('id asc')->getField('addtime');
            }

            if ($addtime) {
                $youtradelog = M('TradeLog')->where('addtime >=' . $addtime . '  and market =\'' . $market . '\'')->sum('num');
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

                    $sum = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->sum('num');

                    if ($sum) {
                        $sta = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->order('id asc')->getField('price');
                        $max = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->max('price');
                        $min = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->min('price');
                        $end = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->order('id desc')->getField('price');
                        $d = array($na, $sum, $sta, $max, $min, $end);

                        if (M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->find()) {
                            M('TradeJson')->where(array('market' => $market, 'addtime' => $na, 'type' => $v))->save(array('data' => json_encode($d)));
                            M('TradeJson')->execute('commit');
                        } else {
                            M('TradeJson')->add(array('market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v));
                            M('TradeJson')->execute('commit');
                            M('TradeJson')->where(array('market' => $market, 'data' => '', 'type' => $v))->delete();
                            M('TradeJson')->execute('commit');
                        }
                    } else {
                        M('TradeJson')->add(array('market' => $market, 'data' => '', 'addtime' => $na, 'type' => $v));
                        M('TradeJson')->execute('commit');
                    }
                }
            }
        }
    }

    function forcematch($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', 'Incorrect ID``');
        }

        $trade = M('Trade')->where(array('id' => $id))->find();

        if (!$trade) {
            return array('0', 'Order does not exist');
        }

        if ($trade['status'] != 0) {
            return array('0', 'Orders can not be undone');
        }
        $market = $trade['market'];
        $xnb = explode('_', $market)[0];
        $rmb = explode('_', $market)[1];

        $price = $trade['price'];
        $type = $trade['type'];

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if (!$xnb) {
            return array('0', 'Sell market error');
        }

        if (!$rmb) {
            return array('0', 'Buy market error');
        }
        if ($trade['userid'] == 0) {
            return array('0', 'You can undo this order as its Liquidity Order , not required to match');
        }

        $mo = M();
        $user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
        $mo->startTrans();
        $rs = array();
        if ($type == 1) {
            $amount = bcsub($trade['num'], $trade['deal'], 8);
            $remaining_locked = bcmul($amount, $trade['price'], 8);
            $fee_for_remaining = bcmul(bcdiv($trade['fee'], $trade['num'], 8), $amount, 8);

            $remove_from_freeze = bcadd($remaining_locked, $fee_for_remaining, 8);

            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $amount);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $remove_from_freeze);
            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setInc('deal', $amount);

            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 1);
            $rs[] = $mo->table('codono_trade_log')->add(array('userid' => $trade['userid'], 'peerid' => 0, 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $remaining_locked, 'type' => $type, 'fee_buy' => $fee_for_remaining, 'fee_sell' => 0, 'addtime' => time(), 'status' => 1));

        } else if ($type == 2) {
            $amount = bcsub($trade['num'], $trade['deal'], 8);
            $remaining_locked = bcsub($trade['num'], $trade['deal'], 8);

            $effective_price = bcdiv($trade['mum'], $trade['num'], 8);
            $credit_rmb = bcmul($amount, $effective_price, 8);
            $sell_fee = bcdiv(bcmul($credit_rmb, $fee_sell, 8), 100, 8);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $credit_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $remaining_locked);

            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setInc('deal', $amount);

            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 1);
            $rs[] = $mo->table('codono_trade_log')->add(array('userid' => 0, 'peerid' => $trade['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $credit_rmb, 'type' => $type, 'fee_buy' => 0, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1));

        }
        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);
            $mo->commit();
            return array('1', 'Order has been Force Executed');
        } else {
            $mo->rollback();
            return array('0', 'Execution failed|' . implode('|', $rs));
        }
    }

    public function adminreject($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', 'Parameter error');
        }

        $trade = M('Trade')->where(array('id' => $id))->find();

        if (!$trade) {
            return array('0', 'Order does not exist');
        }

        if ($trade['status'] != 0) {
            return array('0', 'Orders can not be undone');
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return array('0', 'Sell market error');
        }

        if (!$rmb) {
            return array('0', 'Buy market error');
        }
        if ($trade['userid'] == 0) {
            $mo = M();
            $info = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 2);
            $market = $trade['market'];
            S('getActiveDepth' . $market, null);
            return array('1', 'Liquidity Order Undone');
        }
        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return array('0', 'BUY fee error');
        }

        if ($fee_sell < 0) {
            return array('0', 'Error handling sell');
        }
        $market = $trade['market'];
        $user_coin = M('UserCoin')->where(array('userid' => $trade['userid']))->find();
        $mo = M();
        $mo->startTrans();

        $rs = array();
        $user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

        if ($trade['type'] == 1) {
            $mun = format_num(bcmul(bcdiv(bcmul(bcsub($trade['num'], $trade['deal'], 8), $trade['price'], 8), 100, 8), bcadd(100, $fee_buy, 8), 8), 8);
            $user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();
                //$mo->rollback();
                //
                M('Trade')->where(array('id' => $id))->setField('status', 2);
                //$mo->commit();
                $mo->commit();
                return array('0', 'Undo failed1');
            }

            $finance = $mo->table('codono_finance')->where(array('userid' => $trade['userid']))->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_buy = $mo->table('codono_trade')->where(array(
                'market' => array('like', '%' . $rmb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            ))->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            if ($mun < 0) {
                $mo->rollback();
                M('Trade')->where(array('id' => $id))->setField('status', 2);
                $mo->commit();
                return array('1', 'Undone!');
            }
            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                //  $mo->rollback();
                M('Trade')->where(array('id' => $trade['id']))->setField('status', 2);
                $mo->commit();
                //$mo->commit();
                return array('0', 'Undo failed2');
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_sell_where = array(
                'market' => array('like', $xnb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            );
            $you_sell = $mo->table('codono_trade')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();
            //$mo->rollback();
            return array('0', 'Undo failed3');
        }

        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);
            $mo->commit();
            //$mo->commit();
            //
            return array('1', 'Order has been canceled');
        } else {
            $mo->rollback();
            //$mo->rollback();
            //
            return array('0', 'Undo failed4|' . implode('|', $rs));
        }
    }

    public function reject($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', 'Parameter error');
        }

        $trade = M('Trade')->where(array('id' => $id, 'userid' => userid()))->find();

        if (!$trade) {
            return array('0', 'Order does not exist');
        }

        if ($trade['status'] != 0) {
            return array('0', 'Orders can not be undone');
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return array('0', 'Sell market error');
        }

        if (!$rmb) {
            return array('0', 'Buy market error');
        }

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return array('0', 'BUY fee error');
        }

        if ($fee_sell < 0) {
            return array('0', 'Error handling sell');
        }
        $market = $trade['market'];
        $user_coin = M('UserCoin')->where(array('userid' => $trade['userid']))->find();
        $mo = M();
        $mo->startTrans();

        $rs = array();
        $user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

        if ($trade['type'] == 1) {
            $mun = format_num(bcmul(bcdiv(bcmul(bcsub($trade['num'], $trade['deal'], 8), $trade['price'], 8), 100, 8), bcadd(100, $fee_buy, 8), 8), 8);
            $user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();
                //$mo->rollback();
                //
                M('Trade')->where(array('id' => $id))->setField('status', 2);
                //$mo->commit();
                $mo->commit();
                return array('0', 'Undo failed1');
            }

            $finance = $mo->table('codono_finance')->where(array('userid' => $trade['userid']))->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_buy = $mo->table('codono_trade')->where(array(
                'market' => array('like', '%' . $rmb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            ))->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            if ($mun < 0) {
                $mo->rollback();
                //$mo->rollback();
                //
                M('Trade')->where(array('id' => $id))->setField('status', 2);
                $mo->commit();
                //$mo->commit();
                return array('1', 'Undone!');
            }
            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                //  $mo->rollback();
                M('Trade')->where(array('id' => $trade['id']))->setField('status', 2);
                $mo->commit();
                //$mo->commit();
                return array('0', 'Undo failed2');
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_sell_where = array(
                'market' => array('like', $xnb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            );
            $you_sell = $mo->table('codono_trade')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();
            //$mo->rollback();
            return array('0', 'Undo failed3');
        }

        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);
            $mo->commit();
            //$mo->commit();
            //
            return array('1', 'Order has been canceled');
        } else {
            $mo->rollback();
            //$mo->rollback();
            //
            return array('0', 'Undo failed4|' . implode('|', $rs));
        }
    }


    public function stopreject($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', 'Parameter error');
        }

        $trade = M('Stop')->where(array('id' => $id, 'userid' => userid()))->find();

        if (!$trade) {
            return array('0', 'Order does not exist');
        }

        if ($trade['status'] != 0) {
            return array('0', 'Orders can not be undone');
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return array('0', 'Sell market error');
        }

        if (!$rmb) {
            return array('0', 'Buy market error');
        }

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return array('0', 'BUY fee error');
        }

        if ($fee_sell < 0) {
            return array('0', 'Error handling sell');
        }
        $market = $trade['market'];
        $user_coin = M('UserCoin')->where(array('userid' => $trade['userid']))->find();
        $mo = M();

        $mo->startTrans();
        $rs = array();
        $user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

        if ($trade['type'] == 1) {
            $mun = round(((($trade['num'] - $trade['deal']) * $trade['price']) / 100) * (100 + $fee_buy), 8);
            $user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();

                M('Stop')->where(array('id' => $id))->setField('status', 2);
                $mo->commit();
                return array('0', 'Undo failed1');
            }

            $finance = $mo->table('codono_finance')->where(array('userid' => $trade['userid']))->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_buy = $mo->table('codono_stop')->where(array(
                'market' => array('like', '%' . $rmb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            ))->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                M('Stop')->where(array('id' => $trade['id']))->setField('status', 2);
                $mo->commit();
                return array('0', 'Undo failed2');
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_sell_where = array(
                'market' => array('like', $xnb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            );
            $you_sell = $mo->table('codono_stop')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();
            return array('0', 'Undo failed3');
        }

        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);

            $mo->commit();

            return array('1', 'Order has been canceled');
        } else {
            $mo->rollback();

            return array('0', 'Undo failed4|' . implode('|', $rs));
        }
    }
	
	
	public function adminstopreject($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', 'Parameter error');
        }

        $trade = M('Stop')->where(array('id' => $id))->find();

        if (!$trade) {
            return array('0', 'Order does not exist');
        }

        if ($trade['status'] != 0) {
            return array('0', 'Orders can not be undone');
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return array('0', 'Sell market error');
        }

        if (!$rmb) {
            return array('0', 'Buy market error');
        }

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return array('0', 'BUY fee error');
        }

        if ($fee_sell < 0) {
            return array('0', 'Error handling sell');
        }
        $market = $trade['market'];
        $user_coin = M('UserCoin')->where(array('userid' => $trade['userid']))->find();
        $mo = M();

        $mo->startTrans();
        $rs = array();
        $user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

        if ($trade['type'] == 1) {
            $mun = round(((($trade['num'] - $trade['deal']) * $trade['price']) / 100) * (100 + $fee_buy), 8);
            $user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();

                M('Stop')->where(array('id' => $id))->setField('status', 2);
                $mo->commit();
                return array('0', 'Undo failed1');
            }

            $finance = $mo->table('codono_finance')->where(array('userid' => $trade['userid']))->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_buy = $mo->table('codono_stop')->where(array(
                'market' => array('like', '%' . $rmb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            ))->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                M('Stop')->where(array('id' => $trade['id']))->setField('status', 2);
                $mo->commit();
                return array('0', 'Undo failed2');
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_sell_where = array(
                'market' => array('like', $xnb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            );
            $you_sell = $mo->table('codono_stop')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();
            return array('0', 'Undo failed3');
        }

        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);

            $mo->commit();

            return array('1', 'Order has been canceled');
        } else {
            $mo->rollback();

            return array('0', 'Undo failed4|' . implode('|', $rs));
        }
    }

    public function adminstopmanual($id = NULL,$percentage=0)
    {

        if (!check($id, 'd')) {
            return array('0', 'Incorrect ID``');
        }

        $trade = M('Stop')->where(array('id' => $id))->find();
        if (!$trade) {
            return array('0', 'Order does not exist');
        }
        if ($trade['status'] != 0) {
            return array('0', 'Orders can not be undone');
        }
        
        $market = $trade['market'];
        $xnb = explode('_', $market)[0];
        $rmb = explode('_', $market)[1];
        
        $type = $trade['type'];
        if($type == 1){
            $num = bcmul($trade['num'], (100 + $percentage)/100, 8);
            $mum = $trade['mum'];
            $price = bcmul($trade['price'], 100/(100 + $percentage), 8);
            $fee = $trade['fee'];
        }else{
            $num = $trade['num'];
            $mum = bcmul($trade['mum'], (100 + $percentage)/100, 8);
            $price = bcmul($trade['price'], (100 + $percentage)/100, 8);
            $fee = bcmul($trade['fee'], (100 + $percentage)/100, 8);
        }

        if (!$xnb) {
            return array('0', 'Sell market error');
        }
        if (!$rmb) {
            return array('0', 'Buy market error');
        }
        if ($trade['userid'] == 0) {
            return array('0', 'You can undo this order as its Liquidity Order , not required to match');
        }

        $mo = M();
        $mo->startTrans();
        $rs = array();

        if ($type == 1) {
            $user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mum <= round($user_buy[$rmb . 'd'], 8)) {
                $remove_from_freeze = $mum;
            } else if ($mum <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $remove_from_freeze = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();

                M('Stop')->where(array('id' => $id))->setField('status', 2);
                $mo->commit();
                return array('0', 'Manual match failed1');
            }

            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $num);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $remove_from_freeze);
            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->setInc('deal', $num);

            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->save(array('status' => 1, 'percent' => $percentage));
            $rs[] = $mo->table('codono_trade')->add(array('userid' => $trade['userid'], 'market' => $market, 'price' => $price, 'num' => $num, 'deal' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 1, 'percent' => $percentage));
            $rs[] = $mo->table('codono_trade_log')->add(array('userid' => $trade['userid'], 'peerid' => 0, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => bcsub( $mum, $fee, 8), 'type' => $type, 'fee_buy' => $fee, 'fee_sell' => 0, 'addtime' => time(), 'status' => 1, 'percent' => $percentage));

        } else if ($type == 2) {
            $user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($num <= round($user_buy[$xnb . 'd'], 8)) {
                $remove_from_freeze = $num;
            } else {
                $mo->rollback();

                M('Stop')->where(array('id' => $id))->setField('status', 2);
                $mo->commit();
                return array('0', 'Manual match failed1');
            }

            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $mum);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $remove_from_freeze);
            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->setInc('deal', $mum);


            $rs[] = $mo->table('codono_stop')->where(array('id' => $trade['id']))->save(array('status' => 1, 'percent' => $percentage));
            $rs[] = $mo->table('codono_trade')->add(array('userid' => $trade['userid'], 'market' => $market, 'price' => $price, 'num' => $num, 'deal' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 1, 'percent' => $percentage));
            $rs[] = $mo->table('codono_trade_log')->add(array('userid' => 0, 'peerid' => $trade['userid'], 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => bcadd($mum, $fee, 8), 'type' => $type, 'fee_buy' => 0, 'fee_sell' => $fee, 'addtime' => time(), 'status' => 1, 'percent' => $percentage));

        }
        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);
            $mo->commit();
            return array('1', 'Order has been Manually Matched');
        } else {
            $mo->rollback();
            return array('0', 'Execution failed|' . implode('|', $rs));
        }
    }
}

