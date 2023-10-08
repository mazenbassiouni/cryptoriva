<?php
/**
 * Advertising Application
 */

namespace Api\Controller;

class AdsController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //Initialization rating
    public function init_ads()
    {
        //Determine the user level
        $uid = $this->userid();

        $coin = D('coin')->select();
        $coinMap = array();
        foreach ($coin as $val) {
            $coinMap[$val['id']] = $val['name'];
        }

        $VipUser = D('AppVipuser')->where(array('uid' => $uid))->find();
        $save_id = 0;
        if (!$VipUser) {
            //vip0
            $save_id = D('AppVipuser')->add(array('uid' => $uid, 'vip_id' => 0, 'addtime' => time()));
            D('AppLog')->add(
                array(
                    'uid' => $uid,
                    'type' => 'vip',
                    'content' => 'Initialization ratingvip0',
                    'addtime' => time()
                )
            );
            $User = D('User')->where(array('id' => $uid))->find();
            if ($User['cellphone'] && $User['truename'] && $User['idcard']) {
                //Improve the information
                if ($AppVip = D('AppVip')->order('tag asc')->find()) {
                    $rule = $AppVip['rule'];
                }
            }
        } else {
            $save_id = $VipUser['id'];
            $last_tag = 0;
            if ($VipUser['vip_id']) {
                $VipData = D('AppVip')->where(array('id' => $VipUser['vip_id']))->find();
                $last_tag = $VipData['tag'];
            }
            //Determine whethercanenter下Onegrade
            $AppVip = D('AppVip')->where('`tag` > ' . $last_tag)->order('tag asc')->find();
            if (!$AppVip) {
                //There is no upgrade
                return;
            }
            $rule = $AppVip['rule'];
        }

        //The lowest levelvip
        $rule = json_decode($rule, true);
        $up_do = 0;
        if ($rule) {
            $UserCoin = D('UserCoin')->where(array('userid' => $uid))->find();
            $flag = 1;
            foreach ($rule as $val) {
                $coin_name = $coinMap[$val['id']];
                if ($UserCoin[$coin_name] < $val['num']) {
                    $flag = 0;
                    break;
                }
            }
            if ($flag) {
                //upgradevip1
                $up_do = 1;
            }
        } else {
            $up_do = 1;
        }

        if ($up_do) {
            D('AppVipuser')->save(array('id' => $save_id, 'vip_id' => $AppVip['id']));
            D('AppLog')->add(
                array(
                    'uid' => $uid,
                    'type' => 'vip',
                    'content' => 'upgrade to' . $AppVip['name'],
                    'addtime' => time()
                )
            );
        }
    }


    //See advertising module
    public function showBlock()
    {
        $blocks = D('Appadsblock')->where(array('status' => 1))->order('sort desc')->select();
        $this->ajaxShow($blocks);
    }

    //View Ad
    public function show($block_id)
    {
        $uid = $this->userid();
        $this->init_ads();
        $blockData = D('Appadsblock')->where(array('id' => $block_id))->find();
        $Appads = D('Appads')->where(array('block_id' => $block_id, 'status' => 1))->select();
        foreach ($Appads as $key => $val) {
            //Form advertising hash
            $hash = md5($uid . '_' . time() . mt_rand(1, 1000000));
            session('app_ads_hash_' . $val['id'], $hash);
            $val['hash'] = $hash;
            $val['remain'] = $blockData['remain'];
            $Appads[$key] = $val;
        }
        $this->ajaxShow($Appads);
    }

    public function click($hash, $aid)
    {
        $uid = $this->userid();
        $ads_session_id = 'app_ads_hash_' . $aid;
        if (!session($ads_session_id)) {
            $this->error('Advertising information is lost');
        }
        if (session($ads_session_id) != $hash) {
            $this->error('Advertising signature error');
        }
        $Vipuser = D('AppVipuser')->where(array('uid' => $uid))->find();
        $Vip = D('AppVip')->where(array('id' => $Vipuser['vip_id']))->find();
        $Appads = D('Appads')->where(array('id' => $aid))->find();
        $Appadsblock = D('Appadsblock')->where(array('id' => $Appads['block_id']))->find();
        if ($Appadsblock['rank'] > $Vip['tag']) {
            $this->error('Your rating(' . $Appadsblock['rank'] . ')not enough(' . $Vip['tag'] . '),You can not see this ad');
        }
        $price_coin = D('Coin')->where(array('id' => $Vip['price_coin']))->find();
        //Click onsuccess
        $mo = M();
        $rs = array();
        
        $mo->startTrans();

        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($price_coin['name'], $Vip['price_num']);
        $rs[] = $mo->table('codono_app_log')->add(
            array(
                'uid' => $uid,
                'type' => 'click_ads',
                'content' => 'View Ad[id:' . $aid . ']profit:' . $price_coin['name'] . ' ' . $Vip['price_num'],
                'addtime' => time()
            )
        );
        if (check_arr($rs)) {
            $mo->commit();
            
            //deletehash
            session($ads_session_id, null);
            $this->success('success!');
        } else {
            $mo->rollback();
            
            $this->error('failure!');
        }
    }
}