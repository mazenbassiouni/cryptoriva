<?php

namespace Admin\Controller;

class TradeController extends AdminController
{
    public function index($field = NULL, $name = NULL, $market = NULL, $status = NULL, $type = 0)
    {
        //$this->checkUpdata();
        $where = array();
		$where['userid']=array('neq',0);
        $where = $this->sub_filter($field, $name, $where);

        $this->sub_index_filter($market, $where, $status, $type);
        $this->display();
    }
	public function stoporders ($field = NULL, $name = NULL, $market = NULL, $status = NULL, $type = 0)
	{
		        $this->title='Stop Orders';
        $where = array();
		$where['userid']=array('neq',0);
        $where = $this->sub_filter($field, $name, $where);

         if ($market) {
            $where['market'] = $market;
        }

        if ($status) {
            $where['status'] = $status;
        }

        if ($status == 0 && $status != null) {
            $where['status'] = 0;
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        $count = M('Stop')->where($where)->count();

        $codono_getSum = M('Stop')->where($where)->sum('mum');

        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Stop')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }
        $this->assign('list', $list);
        $this->assign('codono_count', $count);
        $this->assign('codono_getSum', $codono_getSum);
        $this->assign('page', $show);

        $this->display();
	}
	public function liqindex($field = NULL, $name = NULL, $market = NULL, $status = NULL, $type = 0)
    {
        //$this->checkUpdata();
        $where = array();

        $where = $this->sub_filter($field, $name, $where);
		$where['userid']=array('eq',0);
        $this->sub_index_filter($market, $where, $status, $type);
        $this->display('index');
    }
    public function fill($id = NULL)
    {
        if (!check($id, 'd')) {
            $this->error(L('Invalid trade id'));
        }
        $rs=M()->execute("UPDATE `codono_trade_log` SET  `fill` =  1 WHERE id = '$id' ");

        if ($rs) {
            $this->success('Marked as filled');
        } else {
            $this->error('Could not mark as filled');
        }
    }
	
	public function stopreject($id = NULL)
    {
        $rs = D('Trade')->adminstopreject($id);

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    public function forceStopToLimit($stopid=null,$match=0){
        $mo=M();
        $stopinfo = $mo->table('codono_stop')->where(array('id' => (int)$stopid,'status'=>0))->find();

        $market = $stopinfo['market'];
        $price = $stopinfo['price'];
        $num = $stopinfo['num'];
        $type = $stopinfo['type'];
        $userid = $stopinfo['userid'];
        if($stopid != $stopinfo['id']){
            $this->error('No such stop order found'.$stopinfo['id']);
        }
        if ($type == 1) {

            $mo->table('codono_trade')->add(array('userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));
            $stop_update = M('Stop')->where(array('id' => $stopid))->save(array('status' => 1));


        } else if ($type == 2) {

            $mo->table('codono_trade')->add(array('userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
            $stop_update = M('Stop')->where(array('id' => $stopid))->save(array('status' => 1));

        }else{
                return $this->error(L('Transaction type error'));
        }
        if($stop_update){
            if($match==1){
                $tc = new \Home\Controller\TradeController();

                exec($tc->matchingTrade($market));
            }
            return $this->success(L('Trading success!'));
        }
    }
    public function reject($id = NULL)
    {
        $rs = D('Trade')->adminreject($id);

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }
	public function forcematch($id = NULL)
    {
        $rs = D('Trade')->forcematch($id);

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    public function log($field = NULL, $name = NULL, $market = NULL, $type = NULL)
    {
        $where = array();
		$where['userid']=array('neq', 0);
		$where['peerid']=array('neq', 0);
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'peername') {
                $where['peerid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }
		

        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        if ($market) {
            $where['market'] = $market;
        }

        $count = M('TradeLog')->where($where)->count();
        $codono_getSum = M('TradeLog')->where($where)->sum('mum');
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('TradeLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['peername'] = M('User')->where(array('id' => $v['peerid']))->getField('username');
        }


        $this->assign('codono_count', $count);
        $this->assign('codono_getSum', $codono_getSum);

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function liqlog($field = NULL, $name = NULL, $market = NULL, $type = NULL,$fill=NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'peername') {
                $where['peerid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }
		if($fill!=null){
		if ($fill == 0 || $fill == 1 ) {
			$where['fill'] = $fill;
		} 
		}
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }
		


        if ($market) {
            $where['market'] = $market;
        }
		$stmt="";
		foreach ($where as $key=>$val){
			$stmt.= "$key = '$val' AND ";
		}
		$stmt.="1=1";
		
        //$count = M('TradeLog')->where($where)->count();
		$query="select * from codono_trade_log where $stmt";
		    $count = M('TradeLog')->where($where)->count();
        $codono_getSum = M('TradeLog')->where($where)->sum('mum');
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();

		
        $codono_getSum = M('TradeLog')->where($where)->sum('mum');
		
        $Page = new \Think\Page($count, 50);
        $show = $Page->show();
        $list = M('TradeLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
		
        foreach ($list as $k => $v) {
			if($v['userid']!=0 && $v['peerid']!=0) {
				unset($list[$k]);
				continue;
			}
			$list[$k]['trade_coin']=explode('_', $v['market'])[0];
			$list[$k]['base_coin']=explode('_', $v['market'])[1];
        }
		session('liqrecords',$list);

        $this->assign('codono_count', $count);
        $this->assign('codono_getSum', $codono_getSum);

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function download_liqlog(){
		
		$list=session('liqrecords');
		
		$xlsName = 'LiqRecord';
        $xls = array();

        $xls[0][0] = "id";
		$xls[1][0] = "userid";
        $xls[2][0] = "peerid";
        $xls[3][0] = "market";
        $xls[4][0] = "price";
        $xls[5][0] = "num";
        $xls[6][0] = "mum";
        $xls[7][0] = "fee_buy";
        $xls[8][0] = "fee_sell";
        $xls[9][0] = "type";
        $xls[10][0] = "addtime";
		$xls[11][0] = "fill";
        
		$xls[0][2] = "id";
		$xls[1][2] = "userid";
        $xls[2][2] = "peerid";
        $xls[3][2] = "market";
        $xls[4][2] = "price";
        $xls[5][2] = "num";
        $xls[6][2] = "mum";
        $xls[7][2] = "fee_buy";
        $xls[8][2] = "fee_sell";
        $xls[9][2] = "type";
        $xls[10][2] = "addtime";
		$xls[11][2] = "fill";
        $this->exportExcel($xlsName, $xls, $list);
	}

	public function export_liqlog($field = NULL, $name = NULL, $market = NULL, $type = NULL,$fill=0)
    {
        $where = array();
		$where['fill']=0;
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'peername') {
                $where['peerid'] = M('User')->where(array('username' => $name))->getField('id');
            }else if ($field == 'fill') {
                $where['fill'] = M('User')->where(array('fill' => $fill))->getField('id');
            }  else {
                $where[$field] = $name;
            }
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        if ($market) {
            $where['market'] = $market;
        }
		

        $count = M('TradeLog')->where($where)->count();
        $codono_getSum = M('TradeLog')->where($where)->sum('mum');
 
        $list = M('TradeLog')->where($where)->order('id desc')->select();
		
        foreach ($list as $k => $v) {
			if($v['userid']==0 || $v['peerid']==0)
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['peername'] = M('User')->where(array('id' => $v['peerid']))->getField('username');
        }

        
        $xlsName = 'LiqRecord';
        $xls = array();

        $xls[0][0] = "id";
		$xls[1][0] = "userid";
        $xls[2][0] = "peerid";
        $xls[3][0] = "market";
        $xls[4][0] = "price";
        $xls[4][2] = "num";
        $xls[5][0] = "mum";
        $xls[6][0] = "fee_buy";
        $xls[7][0] = "fee_sell";
        $xls[8][0] = "type";
        $xls[9][0] = "addtime";
		$xls[11][0] = "fill";
        
		$xls[0][2] = "id";
		$xls[1][2] = "userid";
        $xls[2][2] = "peerid";
        $xls[3][2] = "market";
        $xls[4][2] = "price";
        $xls[4][2] = "num";
        $xls[5][2] = "mum";
        $xls[6][2] = "fee_buy";
        $xls[7][2] = "fee_sell";
        $xls[8][2] = "type";
        $xls[9][2] = "addtime";
		$xls[11][2] = "fill";
        $this->exportExcel($xlsName, $xls, $list);
    }
		


    public function chat($field = NULL, $name = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('Chat')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Chat')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function chatStatus($id = NULL, $type = NULL, $model = 'Chat')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function comment($field = NULL, $name = NULL, $coinname = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $count = M('CoinComment')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('CoinComment')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function commentStatus($id = NULL, $type = NULL, $model = 'CoinComment')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function market($field = NULL, $name = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('Market')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Market')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            if ($v['begintrade']) {
                $begintrade_codono_var = substr($v['begintrade'], 0, 5);
            } else {
                $begintrade_codono_var = "00:00";
            }
            if ($v['endtrade']) {
                $endtrade_codono_var = substr($v['endtrade'], 0, 5);
            } else {
                $endtrade_codono_var = "23:59";
            }


            $list[$k]['tradetimecodono'] = $begintrade_codono_var . "-" . $endtrade_codono_var;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function marketEdit($id = NULL)
    {


        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error('Incorrect Core Config');
        }


        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = array();

                $beginshi = "00";
                $beginfen = "00";
                $endshi = "23";
                $endfen = "59";
							$data=array();
			$data['api_min']=0.00;
			$data['api_max']=0.00;
			$data['api_max_qty']=0.00;
			$data['new_price']=0.00;	
			$data['buy_price']=0.00;	
			$data['sell_price']=0.00;	
			$data['min_price']=0.00;	
			$data['max_price']=0.00;	
			$data['volume']=0.00;	
			$data['change']=0.00;

			$this->assign('data',$data);
            } else {
                $market_codono = M('Market')->where(array('id' => $id))->find();
                $this->data = $market_codono;

                if ($market_codono['begintrade']) {
                    $beginshi = explode(":", $market_codono['begintrade'])[0];
                    $beginfen = explode(":", $market_codono['begintrade'])[1];
                } else {
                    $beginshi = "00";
                    $beginfen = "00";
                }

                if ($market_codono['endtrade']) {
                    $endshi = explode(":", $market_codono['endtrade'])[0];
                    $endfen = explode(":", $market_codono['endtrade'])[1];
                } else {
                    $endshi = "23";
                    $endfen = "59";
                }

            }
            $coin_list=[];
            foreach(C('coin') as $coin_once){
                if($coin_once['symbol']=='' || $coin_once['symbol']==null){
                    $coin_list[]['name']=$coin_once['name'];
                }
            }
            usort($coin_list, function ($item1, $item2) {
                return $item1['name'] <=> $item2['name'];
            });


            $this->assign('coin_list', $coin_list);
            $this->assign('codono_getCoreConfig', $codono_getCoreConfig['codono_indexcat']);
            $this->assign('beginshi', $beginshi);
            $this->assign('beginfen', $beginfen);
            $this->assign('endshi', $endshi);
            $this->assign('endfen', $endfen);
            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $round = array(0, 1, 2, 3, 4, 5, 6,7,8,9,10);

            if (!in_array($_POST['round'], $round)) {
                $this->error('Decimal format error!');
            }
			$save_data=$_POST;
			$save_data['orderbook_markup']=$_POST['orderbook_markup']?$_POST['orderbook_markup']:0.00;
            if (isset($_POST['id'])) {
                $rs = M('Market')->save($save_data);
            } else {
                $save_data['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($save_data['buyname']);
                unset($save_data['sellname']);

                if (M('Market')->where(array('name' => $save_data['name']))->find()) {
                    $this->error('Market exists!');
		}
		$rs = M('Market')->add($save_data);

		$bmarket = strtoupper($_POST['sellname'] . $_POST['buyname']);
                M('Binance')->add(['symbol'=> $bmarket]);

            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {

                $this->error(L('OPERATION_FAILED'));
            }
        }
    }

    public function marketStatus($id = NULL, $type = NULL, $model = 'Market')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function invit($field = NULL, $name = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('Invit')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Invit')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['invit'] = M('User')->where(array('id' => $v['invit']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function checkUpdata()
    {
        if (!S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata')) {
            $list = M('Menu')->where(array(
                'url' => 'Trade/index',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Trade/index', 'title' => 'Trades', 'pid' => 5, 'sort' => 1, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Trade/index',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Trades', 'pid' => 5, 'sort' => 1, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Trade/log',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Trade/log', 'title' => 'Transaction Record', 'pid' => 5, 'sort' => 2, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Trade/log',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Transaction Record', 'pid' => 5, 'sort' => 2, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Trade/chat',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Trade/chat', 'title' => 'Trading Chat', 'pid' => 5, 'sort' => 3, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Trade/chat',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Trading Chat', 'pid' => 5, 'sort' => 3, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Trade/comment',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Trade/comment', 'title' => 'Currency Comments', 'pid' => 5, 'sort' => 4, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Trade/comment',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Currency Comments', 'pid' => 5, 'sort' => 4, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Trade/market',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Trade/market', 'title' => 'market place', 'pid' => 5, 'sort' => 5, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Trade/market',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'market place', 'pid' => 5, 'sort' => 5, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Trade/invit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Trade/invit', 'title' => 'TRADING RECOMMENDATIONS', 'pid' => 5, 'sort' => 6, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Trade/invit',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'TRADING RECOMMENDATIONS', 'pid' => 5, 'sort' => 6, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats'));
            }

            if (M('Menu')->where(array('url' => 'Chat/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Tradelog/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata', 1);
        }
    }

    public function getFillId($fill_id){
        if($fill_id==0){
            echo json_encode(array('status' => 0, 'data' => '<table class="table table-bordered"><tr><td>Order Matched internally again ,  So it did not have to call Binance</td></tr></table>'));exit;
        }
        $data= M('BinanceTrade')->where(array('id' => $fill_id))->find();
        if(is_array($data)) {
            echo json_encode(array('status' => 1, 'data' => viewAsTable($data),'raw'=>$data));exit;
        }else{
            echo json_encode(array('status' => 0, 'data' => array()));exit;
        }

    }

    /**
     * @param $field
     * @param $name
     * @param array $where
     * @return array
     */
    private function sub_filter($field, $name, array $where): array
    {
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } elseif ($field == 'liq') {
                $where['userid'] = 0;
            } else {
                $where[$field] = $name;
            }
        }
        return $where;
    }

    /**
     * @param $market
     * @param array $where
     * @param $status
     * @param $type
     * @return void
     */
    private function sub_index_filter($market, array $where, $status, $type): void
    {
        if ($market) {
            $where['market'] = $market;
        }

        if ($status) {
            $where['status'] = $status;
        }

        if ($status == 0 && $status != null) {
            $where['status'] = 0;
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        $count = M('Trade')->where($where)->count();

        $codono_getSum = M('Trade')->where($where)->sum('mum');

        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Trade')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }
        $this->assign('list', $list);
        $this->assign('codono_count', $count);
        $this->assign('codono_getSum', $codono_getSum);
        $this->assign('page', $show);
    }
}

?>
