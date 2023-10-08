<?php

namespace Admin\Controller;

class PoolController extends AdminController
{
     public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '',$coinname='')
    {
		$parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
		
		$map = array();
		        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);
        if ($status) {
            $map['status'] = $status;
        }
        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
		 if ($field && $name) {
                $map[$field] = $name;
        }
		if ($coinname) {
                $map['coinname'] = $coinname;
        }
		if ($status) {
                $map['status'] = $status;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];

		$data = M('Pool')->where($map)->order($order_set)->select();
        $count = M('Pool')->where($map)->order($order_set)->count();
        $builder = new BuilderList();
        $builder->title('Mining Machines:');
        $builder->titleList('Mining Machines', U('Pool/index'));
        $builder->button('add', 'Add', U('Pool/edit'));
		$builder->button('userMachines', 'User Machines', U('Pool/userMachines'));
        $builder->keyId();
		$builder->keyText('name', 'Name');
        $builder->keyText('price', 'Price');
        $builder->keyText('coinname', 'Coin');		
        $builder->keyText('days', 'Active for days');
		$builder->keyText('stocks', 'Available machines');
		$builder->keyText('quantity', 'Total machines');
		$builder->keyText('user_limit', 'User Buy Limit');
		$builder->keyText('daily_profit', 'Reward per day');
		$builder->keyText('getcoin', 'Reward Coin');
        
		//$builder->keyImage('ico', 'Image','Image',array('width' => 240, 'height' => 40, 'savePath' => 'Upload/pool', 'url' => U('pool/images')));
		
		
        $builder->keyText('power', 'Power Ghz');
		
		$builder->keyText('sort', 'Sort');
		$builder->keyBool('is_popular', 'Popular');
        $builder->keyStatus('status', 'Status', array('Inactive', 'Active'));
		$coinname_arr = array('' => 'Coin');
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
        $builder->search('status', 'select',  array('Inactive', 'Active'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyDoAction('Pool/edit?id=###', 'Edit', 'Option');
		$builder->keyDoAction('Pool/deletePool?id=###', 'Delete', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
	public function deletePool($id=NULL){
		$where['id']=$id;
		if (M('Pool')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('Could not delete!');
                }
		
	}
	public function userMachines($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '',$coinname='')
    {
		$parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
		
		$map = array();
		        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
		 if ($field && $name) {
                $map[$field] = $name;
        }
		if ($coinname) {
                $map['coinname'] = $coinname;
        }
		if ($status) {
                $map['status'] = $status;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];

		$data = M('PoolLog')->where($map)->order($order_set)->select();
		$count = M('PoolLog')->where($map)->count();
         $builder = new BuilderList();
        $builder->titleList('User Machines', U('Pool/userMachines'));
		$builder->button('Mining Machines', 'Mining Machines', U('Pool/index'));
        $builder->keyId();
		$builder->keyText('name', 'name');
		$builder->keyText('poolid', 'poolid');
		$builder->keyText('userid', 'UserId');
        $builder->keyText('num', 'Bought');
		$builder->keyText('price', 'Price');
        $builder->keyText('coinname', 'Coin');		
        
		$builder->keyText('days', 'Active for days');
		$builder->keyText('collected', 'Days used');
		
		
		$builder->keyText('daily_profit', 'Reward per day');
		$builder->keyText('getcoin', 'Reward Coin');
        
		//$builder->keyImage('ico', 'Image','Image',array('width' => 240, 'height' => 40, 'savePath' => 'Upload/pool', 'url' => U('pool/images')));
		
		
        $builder->keyText('power', 'Power Ghz');
		
		
        $builder->keyStatus('status', 'Status', array(0=>'Ready',1=> 'Mining',2=>'Released'));
		$coinname_arr = array('' => 'Buy Coin');
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
		
		$r_coinname_arr = array('' => 'RewardCoin');
        $r_coinname_arr = array_merge($r_coinname_arr, D('Coin')->get_all_name_list());
        $builder->search('getcoin', 'select', $r_coinname_arr);
        $builder->search('status', 'select',  array('Inactive', 'Active'));
        $builder->search('field', 'select', array('poolid' => 'poolid', 'userid' => 'userid'));
		$builder->search('name', 'text', 'Enter search content');
		
        $builder->setSearchPostUrl(U('Pool/userMachines'));
		
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function userRewards($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
		$map = array();
	if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }
		
		if (empty($order)) {
            $order = 'id_desc';
        }
	
        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
		if ($field && $name) {
                $map[$field] = $name;
        }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];
		$parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
		
		$data = M('PoolRewards')->where($map)->order($order_set)->select();
        $count = M('PoolRewards')->where($map)->count();
		$builder = new BuilderList();
        $builder->title('Mining User Rewards');
        $builder->titleList('Machines', U('Pool/Index'));
        $builder->keyId();
        $builder->keyText('poolid', 'poolid');
        $builder->keyText('userid', 'Userid');
        
        $builder->keyText('coinname', 'Coin');
		
        $builder->keyText('amount', 'amount');
		$builder->keyText('hash', 'Hash');
        $builder->keyText('addtime', 'addtime');
		
		$builder->setSearchPostUrl(U('Pool/userRewards'));
		$builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
		$builder->search('field', 'select', array('id' => 'ID', 'userid' => 'UserID','hash' => 'hash','poolid' => 'poolid'));
        $builder->search('name', 'text', 'Enter text');
		$builder->button('Machines', 'Machines', U('Pool/Index'));
		$builder->button('User Machines', 'User Machines',U('Pool/userMachines'));
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit($id = NULL)
    {

        if (!empty($_POST)) {
            
            if(!$_POST['id']){
			if (!check($_POST['days'], 'd') || !check($_POST['stocks'], 'd')  || !check($_POST['quantity'], 'd') || !check($_POST['user_limit'], 'd')) {
				$this->error("Days , Inventory, Quantity and User limit should be non decimal numbers");
			}
                $array = array(
					'name' => $_POST['name'],
                    'coinname' => $_POST['coinname'],
					'getcoin' => $_POST['getcoin'],
					'ico' => $_POST['ico'],
                    'price' => $_POST['price'],
                    'days' => $_POST['days'],
                    'stocks' => $_POST['stocks'],
					'quantity' => $_POST['quantity'],
                    'user_limit' => $_POST['user_limit'],
					'power' => $_POST['power'],
                    'daily_profit' => $_POST['daily_profit'],
                    'sort' => $_POST['sort'],
					'status' => $_POST['status'],
					'is_popular' => $_POST['is_popular'],
                );
                $rs = M('Pool')->add($array);

            }else {
			if (!check($_POST['days'], 'd') || !check($_POST['stocks'], 'd') || !check($_POST['quantity'], 'd') || !check($_POST['user_limit'], 'd')) {
				$this->error("Days , Inventory and User limit should be non decimal numbers");
			}
                $array = array(
					'name' => $_POST['name'],
                    'coinname' => $_POST['coinname'],
					'getcoin' => $_POST['getcoin'],
					'ico' => $_POST['ico'],
                    'price' => $_POST['price'],
                    'days' => $_POST['days'],
                    'stocks' => $_POST['stocks'],
					'quantity' => $_POST['quantity'],
                    'user_limit' => $_POST['user_limit'],
					'power' => $_POST['power'],
                    'daily_profit' => $_POST['daily_profit'],
                    'sort' => $_POST['sort'],
					'status' => $_POST['status'],
					'is_popular' => $_POST['is_popular'],
                );				

                $rs = M('Pool')->where(array('id'=>$_POST['id']))->save($array);
            }

            if ($rs) {
				S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
				$this->error('No changes were made !!');
            }
        } else {
			if ($id) {
                $this->data = M('Pool')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }
        
			
            if ($id) {
                $data = M('Pool')->where(array('id' => $id))->find();
				
				$this->assign($data);
            }
            $coin_list = D('Coin')->get_all_name_list();
			$status_array=array('0'=>'Inactive','1'=>'Active');

			$this->assign($coin_list);
			$this->assign($status_array);
            $this->display();
        }
    }
	
	
	public function deleteInvesmentLog($id=array()){
		
		$where['id']=end($id);
		if (M('InvestboxLog')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('Could not delete!');
                }
		
	}	
	
	public function editInvesmentLog($id = NULL)
    {

        if (!empty($_POST)) {
			
			$userid=$_POST['userid'];
		
		
		$check_if_user=M('User')->where(array('id' => $userid))->getField('id');
		if(!isset($check_if_user)){
			$this->error('No such user found');
		}
		
		$boxid=$_POST['boxid'];
		$check_if_boxid=M('Investbox')->where(array('id' => $boxid))->getField('id');
		if(!isset($check_if_boxid)){
			$this->error('No such Invest box found');
		}
			
            if(!isset($_POST['id'])){
				$userid= $_POST['userid'];
				$docid=$_POST['boxid'].'IB'.$userid.tradeno();
                $array = array(
					'boxid' => $_POST['boxid'],
                    'docid' => $docid,
                    'period' =>$_POST['period'],//
                    'amount' => $_POST['amount'],
                    'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
					'maturity' => $_POST['maturity'],
					'userid' => $_POST['userid'],
					'status' => $_POST['status'],
                );
				
                $rs = M('InvestboxLog')->add($array);

            }else {

				$array = array(
					'id' => $_POST['id'],
					'boxid' => $_POST['boxid'],
                    'period' =>$_POST['period'],//
                    'amount' => $_POST['amount'],
                    'maturity' => $_POST['maturity'],
					'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
					'userid' => $_POST['userid'],
					'status' => $_POST['status'],
                );
				
                $rs = M('InvestboxLog')->save($array);
            }

            if ($rs) {
				S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
				$this->error('No changes were made !!');
            }
        } else {
           if ($id) {
                $this->data = M('InvestboxLog')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }
            $boxid=$this->data['boxid'];
			$period=$this->data['period'];
			$begintime=$this->data['begintime'];
			$endtime=$this->data['endtime'];
			$amount=$this->data['amount'];
			$maturity=$this->data['maturity'];
			$userid=$this->data['userid'];
			$status=$this->data['status'];
			if ($boxid) {
				$this->assign('id',$id);
				$this->assign('boxid',$boxid);
				$this->assign('period',$period);
				$this->assign('begintime',$begintime);
				$this->assign('endtime',$endtime);
				$this->assign('amount',$amount);
				$this->assign('maturity',$maturity);
				$this->assign('userid',$userid);
				$this->assign('status',$status);
            }
            
			$status_array=array('0'=>'Premature','1'=>'Active','2'=>'Reject','3'=>'Completed','4'=>'Upcoming');

			$this->assign($status_array);
            $this->display();
        }
    }
	
	public function mineImage()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/pool/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }


}

?>