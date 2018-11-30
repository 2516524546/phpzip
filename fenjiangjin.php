<?php

/*
 * 人人店V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'commission/core/page_login_mobile.php';

class Order_EweiShopV2Page extends CommissionMobileLoginPage {

	function main() {
		global $_W, $_GPC;

    if (!empty($_POST['ordertime2'])) {
     $_SESSION['ordertime2']	 = $_POST['ordertime2'];
	}
 	if (!empty($_POST['ordertime3'])) {
		 $_SESSION['ordertime3']	 = $_POST['ordertime3'];
	}

		// $member = $this->model->getInfo($_W['openid'], array('total', 'ordercount0'));

		// $leijimoney = pdo_fetchcolumn("select sum(money) from ". tablename('ewei_shop_money_log').'where send=1 and status=1 and openid=:openid', array(':openid'=>$_W['openid']));

		include $this->template();
	}


	function get_list() {
		global $_W, $_GPC;
		$openid = $_W['openid'];
    $psize = 20;
		$yearme = $_SESSION['year'];
		$monthme = $_SESSION['month'];
		$status = trim($_GPC['status']);

		if (empty($_SESSION['statusnum'])) {
		 	  $_SESSION['statusnum'] = 1;
		 }
		if (!empty($status)) {
		  $_SESSION['statusnum']=$status;
		}else{
		 	$status = $_SESSION['statusnum'];
		}

    // halt($status);
		// if ($status == 1) {
		// 	$status = 1;
		// }elseif ($status == 2) {
		// 	 $status = 3;
		// }else {
		// 	 $status = -1;
		// }

		$condition = ' and dm.status>=0';
		if ($status != '') {
			$condition = ' and dm.status=' . intval($status);
		}

		$ordercount = $member['ordercount0']; //分销订单数
		// $ordercount = 222;
		// dump($ordercount);exit;



		// dump($_GPC);exit;

		$self = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE openid = :openid", array(':openid' => $openid));
		$mychild =  pdo_fetchall("SELECT * FROM ".tablename('ewei_shop_money_log')." WHERE openid = :openid", array(':openid' => $self['openid']));
    $allchild =  pdo_fetchall("SELECT * FROM ".tablename('ewei_shop_member')." WHERE 1 ");
		$self_son  ='';
		// foreach ($allchild  as $key => $value) {
		// 	foreach ($mychild as $key2 => $value2) {
		// 		 if ($value2['fromopenid']==$value['openid']) {
		// 		 	  $self_son .= $value['id'].',';
		// 		 }
		// 	}
		//
		// }
		foreach ($mychild  as $key => $value) {

     $self_son[]=  $value['fromopenid'];

		}
    $self_son = array_unique($self_son);


		// $self_son = explode(',',$self_son);
    // halt($self_son);


		// $level1 = pdo_fetchall("SELECT id FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid", array(':agentid' => $self['id']));
		//
		// $level1id = ((is_array($level1) ? implode(',', array_column($level1, 'id')) : 0));
		//
		// if(empty($level1id)){
		// 	$level1id=0;
		// }
		//
		// $level2 = pdo_fetchall("SELECT id FROM ".tablename('ewei_shop_member').' WHERE agentid in( ' . $level1id . ' )');
		//
		//
		// $level2id = ((is_array($level2) ? implode(',', array_column($level2, 'id')) : 0));
		//
		// if(empty($level2id)){
		// 	$level2id=0;
		// }
		// $level3 = pdo_fetchall("SELECT id FROM ".tablename('ewei_shop_member').' WHERE agentid in( ' . $level2id . ' )');
		// $level3id = ((is_array($level3) ? implode(',', array_column($level3, 'id')) : 0));
		//
		// if (!empty($level2id) || $level2id == '') {
		// 	$level2id = 0;
		// }
		// if (!empty($level3id) || $level3id == '') {
		// 	$level3id = 0;
		// }
		// // dump($level2id);
		// // dump($level3id);
		// $self_son = $level1id.','.$level2id.','.$level3id;
		// if(empty($self_son)){
		// 	$self_son=0;
		// }

   // halt($list);
		// $list = pdo_fetchall('select dm.id,p.title,p.thumb from ' . tablename('ewei_shop_order') . ' dm ' . ' left join ' . tablename('ewei_shop_order_goods') . ' o on o.orderid=dm.id ' . ' left join ' . tablename('ewei_shop_goods') . ' p on p.id=o.goodsid ' .  ' where dm.agentid in( ' . $self_son . ' ) ');

    foreach ($self_son as $key => $value) {

       $listall[] = pdo_fetchall('select dm.*,dm.openid,g.openid,g.avatar,g.nickname,g.weixin,g.outdata,g.expiry_date from ' . tablename('ewei_shop_order') . ' dm ' . ' left join ' . tablename('ewei_shop_member') . ' g on g.openid=dm.openid ' . ' where dm.openid=:openid ' . $condition , array(':openid' => $value));

    }
		$listchang = [];
		foreach ($listall as $key => $value) {
			 if (count($value) >= 1) {
			 	 $listchang = array_merge($value,$listchang);
			 }
		}


     $list = $listchang;
		 // $list = pdo_fetchall('select dm.*,p.openid,p.avatar,p.nickname,p.weixin from ' . tablename('ewei_shop_order') . ' dm ' . ' left join ' . tablename('ewei_shop_member') . ' p on p.id=dm.agentid ' . ' where dm.openid in( ' . $self_son . ' ) '.$condition.' ');

     // halt(count($list));

		// dump($list);exit;
		// $aaa = pdo_fetchall('select id from ' . tablename('ewei_shop_order') .  ' where id=350 ');


// exit;
		foreach ($list as $key => $value) {
			$proinfo = pdo_fetchall('select dm.id,dm.price,dm.total,p.title,p.thumb from ' . tablename('ewei_shop_order_goods') . ' dm ' . ' left join ' . tablename('ewei_shop_goods') . ' p on p.id=dm.goodsid ' .  ' where dm.orderid='.$value['id'].' ');
			$moneyinfo = pdo_fetchall("select * from ". tablename('ewei_shop_money_log').'where orderid=:orderid and openid=:openid', array(':orderid' => $value['ordersn'],':openid'=>$openid));



			if ($value['status'] == -1) {
				$list[$key]['status'] = '已退款';
				$list[$key]['time'] =date('Y-m-d H:i',$value['createtime']);
			} else if ($value['finishtime'] == 0 && $value['status'] == 1) {
				$list[$key]['status'] = '待生效';
				$list[$key]['time'] =date('Y-m-d H:i',$value['paytime']);
			} else if ($value['finishtime'] > 0 && $value['status'] == 3) {
				$list[$key]['status'] = '已生效';
				$list[$key]['time'] =date('Y-m-d H:i',$value['sendtime']);
			}
       // halt($list);
			$list[$key]['order_goods'] = $proinfo;
			$list[$key]['moneyinfo'] = $moneyinfo;
			$list[$key]['givemoney'] = number_format($moneyinfo[0]['money'] + $moneyinfo[1]['money'] + $moneyinfo[2]['money'] + $moneyinfo[3]['money'],2);


			$list[$key]['commission'] = "0.00";
			if (strpos($level1id, $value['agentid'])) {
				$list[$key]['level'] ="一";
			}elseif (strpos($level2id, $value['agentid'])) {
				$list[$key]['level'] ="二";
			}elseif (strpos($level3id, $value['agentid'])) {
				$list[$key]['level'] ="三";
			}

			// dump($proinfo);
		}
		// dump($list);exit;
   // halt(count($list));


		foreach ($list as $key => $value) {
			if (!empty($list[$key]['givemoney'])&&$list[$key]['givemoney'] == 0) {
        unset($list[$key]);
			}

 		 if ($list[$key]['isruhui']== 0 && $list[$key]['price'] >= 1000 ) {
 				 if($list[$key]['expriy_date'] ){
 					 // $tiemcut = ($list[$key]['price'])/1000;
 					 $tiemcut = intval(($list[$key]['price'])/1000);
 					 if($list[$key]['price'] % 1000) {
 							$hasyushou = $list[$key]['price']%1000;
 							$yushumoney = ($hasyushou*0.51)/3;
 					 }else {
 						 $yushumoney = 0;
 					 }
 					 // if($list[$key]['price'] % 1000) {
 					 // 	 $hasyushou = $list[$key]['price']%1000;
 					 // 	 $yushumoney = ($hasyushou*0.51)/3;
 					 // }else {
 					 // 	$yushumoney = 0;
 					 // }
 					 $lasttime = $list[$key]['expriy_date'].' 00:00:00';
 					 $lasttime = date('Y-m-02 H:i:s',strtotime($lasttime));
 					 $firsttime = date("Y-m-02",strtotime("-{$tiemcut} month",strtotime($lasttime)));
 					 // $firsttime = date('Y-m-d', strtotime('-$tiemcut month str$lasttime'));
 					 // dump($firsttime);exit;
 					 $arraykey = rand(0,9999).rand(0,999);
 					 for ($i=1; $i <= $tiemcut ; $i++) {
 						$listto[$i+$arraykey] = $list[$key];
 						$listto[$i+$arraykey]['price'] = 1000;
     	 			$listto[$i+$arraykey]['moneyinfo'][0]['money'] =  170;
 						$listto[$i+$arraykey]['expriy_date'] = date("Y-m",strtotime("+{$i} month",strtotime($firsttime)));
 						$listto[$i+$arraykey]['createtime'] = strtotime(date("Y-m-d H:i:s",strtotime("+{$i} seconds" ,$list[$key]['createtime'])));
						$listto[$i+$arraykey]['givemoney'] = 170 ;

 					 }

				    $listto[1+$arraykey]['price'] = 1000 + $hasyushou;
				    $listto[1+$arraykey]['moneyinfo'][0]['money'] = 170 + $yushumoney ;
            $listto[1+$arraykey]['givemoney'] = $listto[1+$arraykey]['moneyinfo'][0]['money'] ;

 				  	unset($list[$key]);
 						// dump($listto);exit;
 					 // $list = array_merge($listto,$list);
 				 }
 				 // break;
 				 // $key = 0;
 		 }elseif ($list[$key]['isruhui'] == 1 && $list[$key]['orderout'] == 1 && $list[$key]['price'] > 1000 ) {
 			 if (count($list[$key]['moneyinfo'])>2) {
				 if($list[$key]['expriy_date']){
   				$tiemcut = intval(($list[$key]['price']-2000)/1000);
   				if($list[$key]['price'] % 1000) {
   					 $hasyushou = $list[$key]['price']%1000;
   					 $yushumoney = ($hasyushou*0.51)/3;
   				}else {
   					$yushumoney = 0;
   				}
   			 $lasttime = $list[$key]['expriy_date'].' 00:00:00';
   			 $lasttime = date('Y-m-02 H:i:s',strtotime($lasttime));
   			 $firsttime = date("Y-m-02",strtotime("-{$tiemcut} month",strtotime($lasttime)));
   			 // $firsttime = date('Y-m-d', strtotime('-$tiemcut month str$lasttime'));
   			 // dump($firsttime);exit;
   			 // halt($list);
   			 $arraykey = rand(0,9999).rand(0,999);
   			 for ($i=1; $i <= $tiemcut ; $i++) {
   				$listto2[$i+$arraykey] = $list[$key];
   				$listto2[$i+$arraykey]['price'] =1000;
   				$listto2[$i+$arraykey]['moneyinfo'][2]['money'] = 170;
  				$listto2[$i+$arraykey]['givemoney'] = 170 ;
   				$listto2[$i+$arraykey]['expriy_date'] = date("Y-m",strtotime("+{$i} month",strtotime($firsttime)));
   				$listto2[$i+$arraykey]['createtime'] = strtotime(date("Y-m-d H:i:s",strtotime("+{$i} seconds",$list[$key]['createtime'])));
  				$zuzhi = $zuzhi + 170 ;
  				unset($listto2[$i+$arraykey]['moneyinfo'][0]);
  				unset($listto2[$i+$arraykey]['moneyinfo'][1]);
   			 }
   			 $listto2[$tiemcut+1+$arraykey] = $list[$key];
   			 $listto2[$tiemcut+1+$arraykey]['expriy_date'] = date('Y-m',strtotime($firsttime.' 00:00:00'));
   			 $listto2[$tiemcut+1+$arraykey]['price'] =2000 + $hasyushou;
  			 $listto2[$tiemcut+1+$arraykey]['moneyinfo'][2]['money'] = 170 + $yushumoney ;
  			 $listto2[$tiemcut+1+$arraykey]['givemoney'] = $listto2[$tiemcut+1+$arraykey]['moneyinfo'][2]['money'] +$listto2[$tiemcut+1+$arraykey]['moneyinfo'][1]['money'] +$listto2[$tiemcut+1+$arraykey]['moneyinfo'][0]['money'] ;

				 unset($list[$key]);
   			 // dump($list[$key]);exit;
   			 // $list = array_merge($listto,$list);
   		  }
			}elseif (count($list[$key]['moneyinfo'])==2) {
				if($list[$key]['expriy_date']){
				 $tiemcut = intval(($list[$key]['price']-2000)/1000);
				 if($list[$key]['price'] % 1000) {
						$hasyushou = $list[$key]['price']%1000;
						$yushumoney = ($hasyushou*0.51)/3;
				 }else {
					 $yushumoney = 0;
				 }
				$lasttime = $list[$key]['expriy_date'].' 00:00:00';
				$lasttime = date('Y-m-02 H:i:s',strtotime($lasttime));
				$firsttime = date("Y-m-02",strtotime("-{$tiemcut} month",strtotime($lasttime)));
				// $firsttime = date('Y-m-d', strtotime('-$tiemcut month str$lasttime'));
				// dump($firsttime);exit;
				// halt($list);
				$arraykey = rand(0,9999).rand(0,999);
				for ($i=1; $i <= $tiemcut ; $i++) {
				 $listto2[$i+$arraykey] = $list[$key];
				 $listto2[$i+$arraykey]['price'] =1000;
				 $listto2[$i+$arraykey]['moneyinfo'][2]['money'] = 170;
				 $listto2[$i+$arraykey]['givemoney'] = 170 ;
				 $listto2[$i+$arraykey]['expriy_date'] = date("Y-m",strtotime("+{$i} month",strtotime($firsttime)));
				 $listto2[$i+$arraykey]['createtime'] = strtotime(date("Y-m-d H:i:s",strtotime("+{$i} seconds",$list[$key]['createtime'])));
				 $zuzhi = $zuzhi + 170 ;
				 unset($listto2[$i+$arraykey]['moneyinfo'][0]);
				 unset($listto2[$i+$arraykey]['moneyinfo'][1]);
				}
				$listto2[$tiemcut+1+$arraykey] = $list[$key];
				$listto2[$tiemcut+1+$arraykey]['expriy_date'] = date('Y-m',strtotime($firsttime.' 00:00:00'));
				$listto2[$tiemcut+1+$arraykey]['price'] =2000 + $hasyushou;
				$listto2[$tiemcut+1+$arraykey]['moneyinfo'][1]['money'] = 17 0 + $yushumoney ;
				$listto2[$tiemcut+1+$arraykey]['givemoney'] = $listto2[$tiemcut+1+$arraykey]['moneyinfo'][2]['money'] +$listto2[$tiemcut+1+$arraykey]['moneyinfo'][1]['money'] +$listto2[$tiemcut+1+$arraykey]['moneyinfo'][0]['money'] ;

				unset($list[$key]);
				// dump($list[$key]);exit;
				// $list = array_merge($listto,$list);
			 }
		 }else {
				$list[$key]['expriy_date']	= date('Y-m',strtotime($list[$key]['expriy_date'].' 00:00:00'));

			}


 			 // dump($listto);exit;
 		 // continue;
 	 }elseif ($list[$key]['price']< 1000) {

 			 if($list[$key]['expriy_date']){
 				 $arraykey = rand(0,9999).rand(0,999);
 				 $listto2[$arraykey] = $list[$key];
 				 $listto2[$arraykey]['expriy_date']	= date('Y-m',strtotime($list[$key]['expriy_date'].' 00:00:00'));

 					 unset($list[$key]);
 				// $listto2[$arraykey]['createtime'] = strtotime(date("Y-m-d H:i:s",strtotime("+ seconds",$list[$key]['createtime'])));
 			 }

 	 }
 		else {
 		$list[$key]['expriy_date']	= date('Y-m',strtotime($list[$key]['expriy_date'].' 00:00:00'));
 	 }

 	 }


//------------
 	 if ($listto2 && empty($listto)) {
 		 // code...
 		 $list = array_merge($listto2,$list);
 	 }elseif ($listto && empty($listto2)) {
 		 $list = array_merge($listto,$list);
 	 }elseif ($listto && $listto2) {
 		 $list = array_merge($listto,$list,$listto2);
 	 }
 	 // $list = array_merge($listto2,$list);
  	$list = $this->sorttime($list);

    // halt(count($list));
		if (!(empty($_SESSION['ordertime2'])) && !(empty($_SESSION['ordertime3'] )))
		{

		  $yearme =	$_SESSION['ordertime2']   ;
			// halt($yearme);

			$monthme = $_SESSION['ordertime3'] ;
      //$monthme =$monthme-1;
		 foreach ($list as $key => $value) {

		 $years = date('Y',strtotime($list[$key]['expriy_date']));
		 $moths = date('m',strtotime($list[$key]['expriy_date']));
				 if (!empty($list[$key]['expriy_date'])) {
					 // dump($list[$key]['gg']['put_time'].'-01 00:00:00');exit;
					 if ( $yearme == $years ){
            if ($moths == $monthme){
            	      continue;
            }else {
            	  unset($list[$key]);
            }
			 }elseif ($yearme == 99 && $monthme !=999) {
				 if ($moths == $monthme){
								 continue;
				 }else {
						 unset($list[$key]);
				 }
			 }elseif ($yearme != 99 && $monthme ==999) {
				 if ($years == $yearme){
 								continue;
 				}else {
 						unset($list[$key]);
 				}
			}elseif ($yearme == 99 && $monthme ==999 ) {
				  continue;
			}
			 else {
			 	     	  unset($list[$key]);
			 }

			}
		}
			$list = $this->sorttime(array_merge($list));


	 }

	 // halt($list);
	 $zuzhi =0;
	 $huikui =0;
	 $zhitui =0;
	 foreach ($list as $key => $value) {
		 if ($value['moneyinfo']) {
			 foreach ($value['moneyinfo'] as $key2 => $value2) {
				 if ($value2['moneytype']=="推荐奖") {
    		 	   $zhitui = $zhitui  + $value2['money'];
    		 }if ($value2['moneytype']=="回馈奖") {
    		 	   $huikui = $huikui  + $value2['money'];
    		 }if ($value2['moneytype']=="组织奖") {
    		 	   $zuzhi = $zuzhi  + $value2['money'];
    		 }
			 }
		 }
	 }



		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$orders1 = array_slice($list, ($pindex - 1) * $psize, $psize);
    // halt($orders1);

		// $_SESSION['zuzhi'] = $zuzhi;
		// $_SESSION['huikui'] = $huikui;
		// $_SESSION['zhitui'] = $zhitui;
    // $_SESSION['totalmoney'] = $zuzhi + $huikui + $zhitui;
   // halt($zhitui);

		// halt(count($orders1));
		// dump($orders1);exit;
		show_json(1, array(
			'list' => $orders1,
			'pagesize' => $psize,
			'total' => sizeof($list),
			 'zuzhi' => $zuzhi,
			 'huikui' => $huikui,
			 'zhitui' => $zhitui,
		));
	}



	public function sorttime($sort_list){
		foreach($sort_list as $key=>$v){

			$sort_list[$key]['datetime'] = date("YmdHis",$v['createtime']);
		}
			$datetime = array();

			foreach ($sort_list as $user) {$datetime[] = $user['datetime'];
			}
			array_multisort($datetime,SORT_DESC,$sort_list);
			return 	$sort_list;

	}

	function get_lists() {
		global $_W, $_GPC;
		$openid = $_W['openid'];
		// dump($openid);exit;
		$member = $this->model->getInfo($openid, array('ordercount0'));
		$agentLevel = $this->model->getLevel($openid);
		$level = intval($this->set['level']);
		$status = trim($_GPC['status']);
		// halt($status);
		$condition = ' and o.status>=0';
		if ($status != '') {
			$condition = ' and o.status=' . intval($status);
		}
		$orders = array();
		$level1 = $member['level1'];
		$level2 = $member['level2'];
		$level3 = $member['level3'];
		$ordercount = $member['ordercount0']; //分销订单数
		// $ordercount = 222;
		// dump($ordercount);exit;
		if ($level >= 1) {
			//一级下线
			$level1_memberids = pdo_fetchall('select id from ' . tablename('ewei_shop_member') . ' where uniacid=:uniacid and agentid=:agentid', array(':uniacid' => $_W['uniacid'], ':agentid' => $member['id']), 'id');
			$level1_orders = pdo_fetchall('select commission1,o.id,o.createtime,o.price,og.commissions from ' . tablename('ewei_shop_order_goods') . ' og '
					. ' left join  ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
					. " where o.uniacid=:uniacid and o.agentid=:agentid {$condition} and og.status1>=0 and og.nocommission=0", array(':uniacid' => $_W['uniacid'], ':agentid' => $member['id']));
			// dump($member);
			// dump($level1_orders);exit;

			foreach ($level1_orders as $o) {
				if (empty($o['id'])) {
					continue;
				}
				$commissions = iunserializer($o['commissions']);
				$commission = iunserializer($o['commission1']);
				if (empty($commissions)) {
					$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
				} else {
					$commission_ok = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
				}
				$hasorder = false;
				foreach ($orders as &$or) {
					if ($or['id'] == $o['id'] && $or['level'] == 1) {
						$or['commission']+=$commission_ok;
						$hasorder = true;
						break;
					}
				}
				unset($or);
				if (!$hasorder) {
					$orders[] = array('id' => $o['id'], 'commission' => $commission_ok, 'createtime' => $o['createtime'], 'level' => 1);
				}
			}
		}

		if ($level >= 2) {
			//二级下线
			if ($level1 > 0) {
				$level2_orders = pdo_fetchall('select commission2 ,o.id,o.createtime,o.price,og.commissions   from ' . tablename('ewei_shop_order_goods') . ' og '
						. ' left join  ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
						. " where o.uniacid=:uniacid and o.agentid in( " . implode(',', array_keys($member['level1_agentids'])) . ")  {$condition}  and og.status2>=0 and og.nocommission=0 ", array(':uniacid' => $_W['uniacid']));
				foreach ($level2_orders as $o) {
					if (empty($o['id'])) {
						continue;
					}
					$commissions = iunserializer($o['commissions']);
					$commission = iunserializer($o['commission2']);
					if (empty($commissions)) {
						$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
					} else {
						$commission_ok = isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
					}
					$hasorder = false;
					foreach ($orders as &$or) {
						if ($or['id'] == $o['id'] && $or['level'] == 2) {
							$or['commission']+=$commission_ok;
							$hasorder = true;
							break;
						}
					}
					unset($or);
					if (!$hasorder) {
						$orders[] = array('id' => $o['id'], 'commission' => $commission_ok, 'createtime' => $o['createtime'], 'level' => 2);
					}
				}
			}
		}
		if ($level >= 3) {
			if ($level2 > 0) {
				$level3_orders = pdo_fetchall('select commission3 ,o.id,o.createtime,o.price,og.commissions  from ' . tablename('ewei_shop_order_goods') . ' og '
						. ' left join  ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
						. ' where o.uniacid=:uniacid and o.agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . ")  {$condition} and og.status3>=0 and og.nocommission=0", array(':uniacid' => $_W['uniacid']));
				foreach ($level3_orders as $o) {
					if (empty($o['id'])) {
						continue;
					}
					$commissions = iunserializer($o['commissions']);
					$commission = iunserializer($o['commission3']);
					if (empty($commissions)) {
						$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
					} else {
						$commission_ok = isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
					}

					$hasorder = false;
					foreach ($orders as &$or) {
						if ($or['id'] == $o['id'] && $or['level'] == 3) {
							$or['commission']+=$commission_ok;
							$hasorder = true;
							break;
						}
					}
					unset($or);
					if (!$hasorder) {
						$orders[] = array('id' => $o['id'], 'commission' => $commission_ok, 'createtime' => $o['createtime'], 'level' => 3);
					}
				}
			}
		}

		if ($orders)
			usort($orders, function($a, $b) {
				if ($a['createtime'] == $b['createtime']) {
					return 0;
				} else {
					return ($a['createtime'] < $b['createtime']) ? 1 : -1;
				}
			});

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$orders1 = array_slice($orders, ($pindex - 1) * $psize, $psize);
		$orderids = array();
		foreach ($orders1 as $o) {
			$orderids[$o['id']] = $o;
		}

		$list = array();
		// halt($list);
		if (!empty($orderids)) {

			$list = pdo_fetchall("select id,ordersn,openid,createtime,status from " . tablename('ewei_shop_order') . "  where uniacid ={$_W['uniacid']} and id in ( " . implode(',', array_keys($orderids)) . ") order by id desc");

			foreach ($list as &$row) {

				$row['commission'] = number_format((float)$orderids[$row['id']]['commission'], 2);
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				if ($row['status'] == 0) {
					$row['status'] = '待付款';
				} else if ($row['status'] == 1) {
					$row['status'] = '已付款';
				} else if ($row['status'] == 2) {
					$row['status'] = '待收货';
				} else if ($row['status'] == 3) {
					$row['status'] = '已完成';
				}
				if ($orderids[$row['id']]['level'] == 1) {
					$row['level'] = '一';
				} else if ($orderids[$row['id']]['level'] == 2) {
					$row['level'] = '二';
				} else if ($orderids[$row['id']]['level'] == 3) {
					$row['level'] = '三';
				}
				if (!empty($this->set['openorderdetail'])) {
					$goods = pdo_fetchall("SELECT og.id,og.goodsid,g.thumb,og.price,og.total,g.title,og.optionname,"
							. "og.commission1,og.commission2,og.commission3,og.commissions,"
							. "og.status1,og.status2,og.status3,"
							. "og.content1,og.content2,og.content3 from " . tablename('ewei_shop_order_goods') . " og"
							. " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid  "
							. " where og.orderid=:orderid and og.nocommission=0 and og.uniacid = :uniacid order by og.createtime  desc ", array(':uniacid' => $_W['uniacid'], ':orderid' => $row['id']));
					$goods = set_medias($goods, 'thumb');
					foreach ($goods as &$g) {
						$commissions = iunserializer($g['commissions']);
						if ($orderids[$row['id']]['level'] == 1) {
							$commission = iunserializer($g['commission1']);
							if (empty($commissions)) {
								$g['commission'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
							} else {
								$g['commission'] = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
							}
						} else if ($orderids[$row['id']]['level'] == 2) {
							$commission = iunserializer($g['commission2']);
							if (empty($commissions)) {
								$g['commission'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
							} else {
								$g['commission'] = isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
							}
						} else if ($orderids[$row['id']]['level'] == 3) {
							$commission = iunserializer($g['commission3']);
							if (empty($commissions)) {
								$g['commission'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
							} else {
								$g['commission'] = isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
							}
						}
						$g['commission'] = number_format($g['commission'], 2);
					}
					unset($g);
					$row['order_goods'] = set_medias($goods, 'thumb');
				} if (!empty($this->set['openorderbuyer'])) {
					$row['buyer'] = m('member')->getMember($row['openid']);
				}
			}

			unset($row);
		}
		// halt($list);
		// dump($psize);
		// dump($ordercount);exit;
		show_json(1, array(
			'list' => $list,
			'pagesize' => $psize,
			'total' => $ordercount
		));
	}

}
