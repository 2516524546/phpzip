<?php
if (!(defined('IN_IA')))
{
	exit('Access Denied');
}
class Member_EweiShopV2Model
{
	public function getInfo($openid = '')
	{
		global $_W;
		$uid = intval($openid);
		// echo "string";exit;
		if ($uid == 0)
		{
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		}
		else
		{
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where id=:id  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		}
		if (!(empty($info['uid'])))
		{
			load()->model('mc');
			$uid = mc_openid2uid($info['openid']);
			$fans = mc_fetch($uid, array('credit1', 'credit2', 'birthyear', 'birthmonth', 'birthday', 'gender', 'avatar', 'resideprovince', 'residecity', 'nickname'));
			$info['credit1'] = $fans['credit1'];
			$info['credit2'] = $fans['credit2'];
			$info['birthyear'] = ((empty($info['birthyear']) ? $fans['birthyear'] : $info['birthyear']));
			$info['birthmonth'] = ((empty($info['birthmonth']) ? $fans['birthmonth'] : $info['birthmonth']));
			$info['birthday'] = ((empty($info['birthday']) ? $fans['birthday'] : $info['birthday']));
			$info['nickname'] = ((empty($info['nickname']) ? $fans['nickname'] : $info['nickname']));
			$info['gender'] = ((empty($info['gender']) ? $fans['gender'] : $info['gender']));
			$info['sex'] = $info['gender'];
			$info['avatar'] = ((empty($info['avatar']) ? $fans['avatar'] : $info['avatar']));
			$info['headimgurl'] = $info['avatar'];
			$info['province'] = ((empty($info['province']) ? $fans['resideprovince'] : $info['province']));
			$info['city'] = ((empty($info['city']) ? $fans['residecity'] : $info['city']));
		}
		if (!(empty($info['birthyear'])) && !(empty($info['birthmonth'])) && !(empty($info['birthday'])))
		{
			$info['birthday'] = $info['birthyear'] . '-' . ((strlen($info['birthmonth']) <= 1 ? '0' . $info['birthmonth'] : $info['birthmonth'])) . '-' . ((strlen($info['birthday']) <= 1 ? '0' . $info['birthday'] : $info['birthday']));
		}
		if (empty($info['birthday']))
		{
			$info['birthday'] = '';
		}
		return $info;
	}
	public function getMember($openid = '',$count=false)
	{
		global $_W;
		$uid = (int) $openid;
		// dump($openid);exit;
		if ($uid == 0)
		{
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			if (empty($info))
			{
				if (strexists($openid, 'sns_qq_'))
				{
					$openid = str_replace('sns_qq_', '', $openid);
					$condition = ' openid_qq=:openid ';
					$bindsns = 'qq';
				}
				else if (strexists($openid, 'sns_wx_'))
				{
					$openid = str_replace('sns_wx_', '', $openid);
					$condition = ' openid_wx=:openid ';
					$bindsns = 'wx';
				}
				if (!(empty($condition)))
				{
					$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where ' . $condition . '  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
					if (!(empty($info)))
					{
						$info['bindsns'] = $bindsns;
					}
				}
			}
		}
		else
		{

            $info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $openid));


		}
		if(!$info){
		    return false;
        }
        if($count){
            //获取相同上级id的
            $infos = pdo_fetchall('select * from ' . tablename('ewei_shop_member') . ' where agentid=:agentid and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':agentid' => $info['agentid']));

            return count($infos);

        }
		if (!(empty($info)))
		{
			$info = $this->updateCredits($info);
		}
		// echo "string";
		return $info;
	}
	public function updateCredits($info)
	{
		global $_W;
		$openid = $info['openid'];
		if (empty($info['uid']))
		{
			$followed = m('user')->followed($openid);
			if ($followed)
			{
				load()->model('mc');
				$uid = mc_openid2uid($openid);
				if (!(empty($uid)))
				{
					$info['uid'] = $uid;
					$upgrade = array('uid' => $uid);
					if (0 < $info['credit1'])
					{
						mc_credit_update($uid, 'credit1', $info['credit1']);
						$upgrade['credit1'] = 0;
					}
					if (0 < $info['credit2'])
					{
						mc_credit_update($uid, 'credit2', $info['credit2']);
						$upgrade['credit2'] = 0;
					}
					if (!(empty($upgrade)))
					{
						pdo_update('ewei_shop_member', $upgrade, array('id' => $info['id']));
					}
				}
			}
		}
		$credits = $this->getCredits($openid);
		$info['credit1'] = $credits['credit1'];
		$info['credit2'] = $credits['credit2'];
		return $info;
	}
	public function getMobileMember($mobile)
	{
		global $_W;
		$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where mobile=:mobile and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':mobile' => $mobile));
		if (!(empty($info)))
		{
			$info = $this->updateCredits($info);
		}
		return $info;
	}
	public function getMid()
	{
		global $_W;
		$openid = $_W['openid'];
		$member = $this->getMember($openid);
		return $member['id'];
	}



	public function setCredit($openid = '', $credittype = 'credit1', $credits = 0, $log = array(),$orderid,$type='1',$order_id='')
	{


		// dump($credittype);
		// dump($credits);
		// dump($log);
		// dump($orderid);
		// exit;
		global $_W;
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		$aa = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE ordersn = :ordersn", array(':ordersn' => $orderid));
		$order_goods = pdo_fetchall('select g.id,g.title,g.thumb,g.goodssn,og.goodssn as option_goodssn, g.productsn,og.productsn as option_productsn, og.total,' . "\n" . '                    og.price,og.optionname as optiontitle, og.realprice,og.changeprice,og.oldprice,og.commission1,og.commission2,og.commission3,og.commissions,og.diyformdata,' . "\n" . '                    og.diyformfields,op.specs,g.merchid,og.seckill,og.seckill_taskid,og.seckill_roomid,g.ispresell from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid ' . ' left join ' . tablename('ewei_shop_goods_option') . ' op on og.optionid = op.id ' . ' where og.uniacid=:uniacid and og.orderid=:orderid ', array(':uniacid' => 2, ':orderid' => $aa['id']));

		$totalnumber = $order_goods[0]['total'];
		$productname = $order_goods[0]['title'];

		// dump($totalnumber);
		// dump($productname);exit;

		if (empty($log))
		{
			$log = array($uid, '未记录');
		}
		else if (!(is_array($log)))
		{
			$log = array(0, $log);
		}
		if (($credittype == 'credit1') && empty($log[0]) && (0 < $credits))
		{
			$shopset = m('common')->getSysset('trade');
			$member = $this->getMember($openid);
			if (empty($member['diymaxcredit']))
			{
				if (0 < $shopset['maxcredit'])
				{
					if ($shopset['maxcredit'] <= $member['credit1'])
					{
						return error(-1, '用户积分已达上限');
					}
					if ($shopset['maxcredit'] < ($member['credit1'] + $credits))
					{
						$credits = $shopset['maxcredit'] - $member['credit1'];
					}
				}
			}
			else if (0 < $member['maxcredit'])
			{
				if ($member['maxcredit'] <= $member['credit1'])
				{
					return error(-1, '用户积分已达上限');
				}
				if ($member['maxcredit'] < ($member['credit1'] + $credits))
				{
					$credits = $member['maxcredit'] - $member['credit1'];
				}
			}
		}
		// dump($uid);
		if (!(empty($uid)))
		{
			// echo "string";exit;
			$value = pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('mc_members') . ' WHERE `uid` = :uid', array(':uid' => $uid));
			$newcredit = $credits + $value;
			if ($newcredit <= 0)
			{
				$newcredit = 0;
			}
			pdo_update('mc_members', array($credittype => $newcredit), array('uid' => $uid));
			if (empty($log))
			{
				$log = array($uid, '未记录');
			}
			else if (!(is_array($log)))
			{
				$log = array(0, $log);
			}
			$data = array('uid' => $uid, 'credittype' => $credittype, 'uniacid' => $_W['uniacid'], 'num' => $credits, 'createtime' => TIMESTAMP, 'module' => 'ewei_shopv2', 'operator' => intval($log[0]), 'remark' => $log[1]);
			pdo_insert('mc_credits_record', $data);

			return;
		}

		// dump($openid);exit;
		// dump($credittype);
		// dump($credits);
		// dump($log);
		// dump($orderid);
		// exit;

		if(!(empty($openid))){


			$data=array(
				'openid'=>$openid,
				'remark'=>$log[1],
				'num'=>$credits,
				'createtime'=>TIMESTAMP,
				'orderid'=>$orderid,
			);
			$anc=pdo_insert('mc_credits_rechar', $data);

		}




		$value = pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('ewei_shop_member') . ' WHERE  uniacid=:uniacid and openid=:openid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

		$newcredit = $credits + $value;
		if ($newcredit <= 0)
		{
			$newcredit = 0;
		}

		$self_info = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE openid = :openid", array(':openid' => $openid));
		$id = $self_info['id'];
		$result = self::getrecom($id);
		// dump($result);exit;




		//余额支付的返利可以写在这里
		//1 取出前三代
		//2 判断->是否为黑名单or已审核or代理商or经销商or失效？
		//3 取订单数据，查询设置的返利比例
		//4 判断是入会还是重消
		//5 生成money_log
		//6 把获得的钱加到用户表
		//7 发信息给获得奖金的人
		//退款时候的情况

		$totmoney = abs($credits);
    // $totmoney = $_SESSION['price'];
		// dump($result);exit;
		// dump($orderid);
		$set = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_setting')." WHERE id = :id", array(':id' => '1'));

		if ($self_info['status'] == 1) {
			if($type=='1'){ // 支付的情况


				if ($result[0]['level'] == 0) {


     // if ($totmoney > 2000) {
		 //
		 //
			//  						$lv['level'] = 1;
			//  						$lv['ruhui_time'] = date('Y-m-d H:i:s',time());
			// 						$lv['outdata'] = 1;
		 //
			//  						$lv['etcstatus']= 0; // 入会标识
		 //
			//  						$bb=pdo_fetch("SELECT mem_num FROM ".tablename('ewei_shop_member')." WHERE openid = :openid", array(':openid' =>$openid));
		 //
			// 			 			if(empty($bb['mem_num'])){
		 //
			// 			 				$mem_num=pdo_fetch("SELECT numc FROM".tablename('ewei_shop_member').'order by numc desc');
			// 							$nuc=$mem_num['numc']+'1';
			// 							$lv['numc']=$nuc;
			// 							$lv['mem_num']= "CN".sprintf("%010d",$nuc);
			// 			 			}
		 //
		 //
		 //
			//  						pdo_update('ewei_shop_member', $lv, array('openid' => $openid));
		 //
			//  						m('message')->sendCustomNotice($openid, "您购买了{$totmoney}元的{$productname}，您的等级自动升为经销商");
		 //
			//  						if (!empty($self_info['agentid']) && $result[1]['level'] == 1) {
			//  							$totzhitui = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_member').'where level > 0 and agentid='.$self_info['agentid']);
			//  							if ($totzhitui >= $set['cxneedpeople']) {
			//  								$lc['level'] = 2;
		 //
			//  								// $mem_num=pdo_fetch("SELECT numc FROM".tablename('ewei_shop_member').'order by numc desc');
		 //
			//  								// $nuc=$mem_num['numc']+'1';
			//  								// $lv['numc']=$nuc;
			//  								// $lv['mem_num']= "CN".sprintf("%09d",$nuc);
		 //
			//  								pdo_update('ewei_shop_member', $lc, array('openid' => $result[1]['openid']));
			//  								m('message')->sendCustomNotice($result[1]['openid'], "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，并且直推人数达到了{$set['cxneedpeople']}人，您的等级自动升为代理商");
			//  							}
			//  						}           $totmoneyall = $totmoney;
     //                          $totmoney = 1000;
		 //
			// 												$tuijian = $totmoney * $set['tuijian'] * 0.01;
			// 												$huikui1 = $totmoney * $set['huikui1'] * 0.01;
			// 												$huikui2 = $totmoney * $set['huikui2'] * 0.01;
			// 												$huikui3 = $totmoney * $set['huikui3'] * 0.01;
		 //
		 //
			// 												$zuzhi1 = $totmoney * $set['zuzhi1'] * 0.01;
			// 												$zuzhi2 = $totmoney * $set['zuzhi2'] * 0.01;
			// 												$zuzhi3 = $totmoney * $set['zuzhi3'] * 0.01;
		 //
			// 												$datetime2 = date('Y-m',time());
			// 												if ($self_info['anzhi'] != $self_info['agentid'] && $self_info['anzhi'] != 0 && $self_info['anzhi'] !='') {
		 //
			// 													$tuijianuser = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE id = :id", array(':id' => $self_info['anzhi']));
			// 													if ($tuijianuser['level'] == 0) {
			// 														/*
			// 														pdo_insert('ewei_shop_money_log', array('openid' => $tuijianuser['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
			// 														$text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$huikui1}的推荐奖（无效奖金）";
			// 														m('message')->sendCustomNotice($tuijianuser['openid'], $text);
			// 														*/
			// 														pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													}else{
			// 														pdo_insert('ewei_shop_money_log', array('openid' => $tuijianuser['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 														$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$tuijian}元的推荐奖（{$data_time}月-待生效）";
			// 														m('message')->sendCustomNotice($tuijianuser['openid'], $text);
		 //
		 //
			// 														// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $tuijianuser['openid']), 'reward_money',1);
			// 														// $newmoney['reward_money'] = $reward_money + $tuijian;
			// 														// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $tuijianuser['openid']));
			// 													}
			// 												}else{
		 //
		 //
			// 													if (!empty($result[1]['openid']) && $result[1]['isblack'] == 0) {
			// 														if ($result[1]['level'] == 0) {
			// 															/*
			// 															pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
			// 															$text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$huikui1}的推荐奖（无效奖金）";
			// 															m('message')->sendCustomNotice($result[1]['openid'], $text);
		 //
		 //
			// 															pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui1, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
			// 															$text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$huikui1}的回馈奖（无效奖金）";
			// 															m('message')->sendCustomNotice($result[1]['openid'], $text);
			// 															*/
			// 															pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
		 //
			// 														}elseif ($result[1]['level'] == 1) {
			// 															pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 															// $text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$tuijian}的推荐奖（待生效）";
			// 															// m('message')->sendCustomNotice($result[1]['openid'], $text);
		 //
		 //
			// 															pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui1, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 															pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
		 //
			// 															$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$tuijian}元的推荐奖（{$data_time}月-待生效） ";
			// 															m('message')->sendCustomNotice($result[1]['openid'], $text);
		 //
			// 															// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
			// 															// $newmoney['reward_money'] = $reward_money + $tuijian;
			// 															// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
			// 														}else{
			// 															pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 															// $text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$tuijian}的推荐奖（待生效）";
			// 															// m('message')->sendCustomNotice($result[1]['openid'], $text);
			// 															pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui1, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 															pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 															$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$tuijian}元的推荐奖（待生效）和{$huikui1}元的回馈奖（{$data_time}月-待生效）和 {$zuzhi1}元的组织奖（{$data_time}月-待生效）";
			// 															m('message')->sendCustomNotice($result[1]['openid'], $text);
		 //
		 //
			// 															// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
			// 															// $newmoney['reward_money'] = $reward_money + $tuijian + $huikui1;
			// 															// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
			// 														}
			// 													}
			// 												}
		 //
		 //
		 //
			// 												if ($result[2]['level'] == 1) {
			// 													pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													// $text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui2}元的回馈奖（无效奖金）";
			// 													// m('message')->sendCustomNotice($result[2]['openid'], $text);
			// 												}elseif ($result[2]['level'] == 2){
			// 													pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui2}元的回馈奖（{$data_time}月-待生效）和 {$zuzhi2}元的组织奖（{$data_time}月-待生效）";
			// 													m('message')->sendCustomNotice($result[2]['openid'], $text);
		 //
			// 													// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[2]['openid']), 'reward_money',1);
			// 													// $newmoney['reward_money'] = $reward_money + $huikui2;
			// 													// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[2]['openid']));
			// 												}else{
			// 													pdo_insert('ewei_shop_money_log', array('openid' =>'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													pdo_insert('ewei_shop_money_log', array('openid' =>'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 												}
		 //
			// 												if ($result[3]['level'] == 1) {
			// 													pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													// $text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui3}元的回馈奖（无效奖金）";
			// 													// m('message')->sendCustomNotice($result[3]['openid'], $text);
			// 												}elseif ($result[3]['level'] == 2){
			// 													pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 													$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui3}元的回馈奖（{$data_time}月-待生效）和 {$zuzhi3}元的组织奖（{$data_time}月-待生效）";
			// 													m('message')->sendCustomNotice($result[3]['openid'], $text);
		 //
		 //
			// 													// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[3]['openid']), 'reward_money',1);
			// 													// $newmoney['reward_money'] = $reward_money + $huikui3;
			// 													// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[3]['openid']));
			// 												}else{
			// 													pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 														pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
			// 												}
		 //
		 //
			// 											 //
	   //                         //   $totmoney = 1000 ;
			// 												//  $totmoneyall = $totmoneyall - 2000;
			// 												//  $countmoney = $totmoneyall/1000;
     //                         //   $checkorder =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
			// 								 				//  $checkmember =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
			// 								 				//  $checklognew =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_money_log')." WHERE fromopenid = :fromopenid order by id desc limit 1", array(':fromopenid' => $checkmember['openid']));
			// 							         //   $checkordernew =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE agentid = :agentid order by id desc limit 1", array(':agentid' => $checkmember['id']));
			// 											 //   $fromuserold = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order_goods')." WHERE 1  order by id desc limit 1");
			// 												//  $childkey = time().rand(1,9999);
			// 						           //   pdo_update('ewei_shop_order', array('mychild'=>$childkey) , array('id' => $checkorder['id']));
			// 											 //
			// 												// for ($i=1; $i <= $countmoney ; $i++) {
     //                         //       $orderid = 'SH'.date("YmdHis",time()).rand(100000,999999);
     //                         //    // $checkorder =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
			// 												// 	if (!empty($checklognew)) {
			// 			 								 // 	if (!empty($checklognew['put_time'])) {
			// 											 //
			// 			 								 // 		$checklognewtime = $checklognew['put_time'].'-10 22:00:00';
			// 											 //
			// 			 								 // 		$put_datatime = date('Y-m', strtotime(" +{$i} month",strtotime($checklognewtime) ));
			// 			 								 // 	}else {
			// 			 								 // 		$put_datatime = date('Y-m', strtotime(" +{$i} month",time()));
			// 			 								 // 	}
			// 			 								 // }else {
			// 			 								 // 		$put_datatime = date('Y-m', strtotime("+{$i} month",time() ));
			// 			 								 // }
			// 			 								 //    $res2 = pdo_insert('ewei_shop_order_goods', array('uniacid' => 2, 'orderid' => $checkordernew['id']+$i, 'goodsid' => $fromuserold['goodsid'] , 'price'=> $fromuserold['price'] / $fromuserold['total'] , 'total' => 1  )  );
     //                         //    $res = pdo_insert('ewei_shop_order', array('openid' =>  $openid,'mychild'=>$childkey,'ishide'=>1, 'uniacid' => $checkorder['uniacid'], 'address' =>$checkorder['address'],  'agentid' => $checkorder['agentid'], 'ordersn' => $orderid, 'price' =>1000, 'goodsprice' =>1000, 'addressid'=>$checkorder['addressid'],'transid'=>$checkorder['transid'],status=>$checkorder['status'],'carrier'=>$checkorder['carrier'],'paytime'=>$checkorder['paytime'],'expresssn'=>$checkorder['expresssn'],'sendtime'=>$checkorder['sendtime'],'canceltime'=>$checkorder['canceltime'],'oldprice'=>$checkorder['oldprice'],'grprice'=>$checkorder['grprice'],'address'=>$checkorder['address'], 'createtime' => time(), 'finishtime' => $checkorder['finishtime']) );
     //                         //    if ($res==false) {
     //                         //    	return false;
     //                         //    }
			// 											 //
			// 												// 	pdo_update('ewei_shop_member', array($credittype => $newcredit,'expiry_date'=>date("Y-m-d",strtotime("+$i month",time()))), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
			// 												// 	$lv['etcstatus']= 0;// 重消
			// 												// 	$lv['outdata']= 1;// 溢出判断
			// 								 				// 	pdo_update('ewei_shop_member', $lv, array('openid' => $openid));
			// 											 //
			// 								 				// 	$zuzhi1 = 1000 * $set['zuzhi1'] * 0.01;
			// 								 				// 	$zuzhi2 = 1000 * $set['zuzhi2'] * 0.01;
			// 								 				// 	$zuzhi3 = 1000 * $set['zuzhi3'] * 0.01;
			// 											 //
			// 								 				// 	if ($result[1]['level'] == 1) {
			// 								 				// 		// pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 		// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（无效奖金）";
			// 								 				// 		// m('message')->sendCustomNotice($result[1]['openid'], $text);
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time' =>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));
			// 											 //
			// 								 				// 	}elseif ($result[1]['level'] == 2) {
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1,'put_time' =>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));
			// 											 //
			// 								 				// 		$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（待生效{$put_datatime}）";
			// 								 				// 		m('message')->sendCustomNotice($result[1]['openid'], $text);
			// 											 //
			// 								 				// 		// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
			// 								 				// 		// $newmoney['reward_money'] = $reward_money + $zuzhi1;
			// 								 				// 		// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
			// 								 				// 	}else{
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1,'put_time' =>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 	}
			// 											 //
			// 								 				// 	if ($result[2]['level'] == 1) {
			// 								 				// 			pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1,'put_time' =>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 		// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（无效奖金）";
			// 								 				// 		// m('message')->sendCustomNotice($result[2]['openid'], $text);
			// 								 				// 	}elseif ($result[2]['level'] == 2) {
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1,'put_time' =>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 		$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（待生效{$put_datatime}）";
			// 								 				// 		m('message')->sendCustomNotice($result[2]['openid'], $text);
			// 											 //
			// 								 				// 		// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[2]['openid']), 'reward_money',1);
			// 								 				// 		// $newmoney['reward_money'] = $reward_money + $zuzhi2;
			// 								 				// 		// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[2]['openid']));
			// 								 				// 	}else{
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1,'put_time' =>$put_datatime ,'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 	}
			// 											 //
			// 								 				// 	if ($result[3]['level'] == 1) {
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1,'put_time' =>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 		// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（无效奖金）";
			// 								 				// 		// m('message')->sendCustomNotice($result[3]['openid'], $text);
			// 								 				// 	}elseif ($result[3]['level'] == 2) {
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1,'put_time' =>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 		$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（{$put_datatime}月-待生效）";
			// 								 				// 		m('message')->sendCustomNotice($result[3]['openid'], $text);
			// 											 //
			// 											 //
			// 								 				// 		// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[3]['openid']), 'reward_money',1);
			// 								 				// 		// $newmoney['reward_money'] = $reward_money + $zuzhi3;
			// 								 				// 		// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[3]['openid']));
			// 								 				// 	}else{
			// 								 				// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'put_time' =>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
			// 								 				// 	}
			// 											 //
			// 												// }
		 //
		 //
		 //
		 //
		 //
     // }


//--------------------------------------------------------------------------------------



		 	if ($totmoney >= $set['level0_up']) {


						$lv['level'] = 1;
						$lv['ruhui_time'] = date('Y-m-d H:i:s',time());



			 			$bb=pdo_fetch("SELECT mem_num FROM ".tablename('ewei_shop_member')." WHERE openid = :openid", array(':openid' =>$openid));

			 			if($bb['mem_num']){

			 				$lv['mem_num']= $bb['mem_num'];

			 			}else{

				 			$mem_num=pdo_fetch("SELECT numc FROM".tablename('ewei_shop_member').'order by numc desc');
								$nuc=$mem_num['numc']+'1';
								$lv['numc']=$nuc;
				 			$lv['mem_num']= "CN".sprintf("%010d",$nuc);
	             			$datetime2 = date('Y-m',time());
							$lv['etcstatus']= 1;// 入会标识
			 			}




						pdo_update('ewei_shop_member', $lv, array('openid' => $openid));

						m('message')->sendCustomNotice($openid, "您购买了{$totmoney}元的{$productname}，您的等级自动升为经销商");

						if (!empty($self_info['tuijian']) && $result[1]['level'] == 1) {
							$totzhitui = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_member').'where level > 0 and agentid='.$self_info['tuijian']);
							if ($totzhitui >= $set['cxneedpeople']) {
								$lc['level'] = 2;

								// $mem_num=pdo_fetch("SELECT numc FROM".tablename('ewei_shop_member').'order by numc desc');

								// $nuc=$mem_num['numc']+'1';
								// $lv['numc']=$nuc;
								// $lv['mem_num']= "CN".sprintf("%09d",$nuc);


								pdo_update('ewei_shop_member', $lc, array('openid' => $result[1]['openid']));

								m('message')->sendCustomNotice($result[1]['openid'], "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，并且直推人数达到了{$set['cxneedpeople']}人，您的等级自动升为代理商");
							}
						}
						$allmoney = $totmoney;
						  $countmoney =  $totmoney/1000;
						$mynewmoney = $totmoney - 1000;
					}

					$allmoney = $totmoney;
					$countmoney =  $totmoney/1000;
					$mynewmoney= $totmoney - 1000;


					$tuijian = 1000 * $set['tuijian'] * 0.01;
					$huikui1 = 1000 * $set['huikui1'] * 0.01;
					$huikui2 = 1000 * $set['huikui2'] * 0.01;
					$huikui3 = 1000 * $set['huikui3'] * 0.01;


					$zuzhi1 = $mynewmoney * $set['zuzhi1'] * 0.01;
					$zuzhi2 = $mynewmoney * $set['zuzhi2'] * 0.01;
					$zuzhi3 = $mynewmoney * $set['zuzhi3'] * 0.01;

          $expired_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid),'expiry_date',1);

          $listtime = $expired_date.' 00:00:00';
					$listtime = date('Y-m-01 00:00:00',strtotime($listtime));
					$listtimeold = date('Y-m', strtotime("$listtime"));
					$listtime = date('Y-m', strtotime("$listtime -1 month "));
					// $listtime = date('Y-m',strtotime($listtime));
          $conuttime = intval($mynewmoney/1000);
					$data_time = '';
					for ($i=0; $i < $conuttime ; $i++) {
						$listtime = date('Y-m', strtotime("$listtime +1 month "));
					  $data_time .= $listtime.'月、';
					}
          $data_time = rtrim($data_time,'、');

					// dump($tuijian);
					// dump($huikui1);
					// dump($huikui2);
					// dump($huikui3);exit;
					// print_r($result);die;
					if ($self_info['anzhi'] != $self_info['agentid'] && $self_info['anzhi'] != 0 && $self_info['anzhi'] !='') {

						$tuijianuser = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE id = :id", array(':id' => $self_info['tuijian']));
						if ($tuijianuser['level'] == 0) {
							/*
							pdo_insert('ewei_shop_money_log', array('openid' => $tuijianuser['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
							$text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$huikui1}的推荐奖（无效奖金）";
							m('message')->sendCustomNotice($tuijianuser['openid'], $text);
							*/
							pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						}else{
							pdo_insert('ewei_shop_money_log', array('openid' => $tuijianuser['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
							$text = "您的下级{$self_info['realname']}入会成功，购买了{$allmoney}元的{$productname}，您获得了{$tuijian}元的推荐奖（{$data_time}-待生效）";
							m('message')->sendCustomNotice($tuijianuser['openid'], $text);


							// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $tuijianuser['openid']), 'reward_money',1);
							// $newmoney['reward_money'] = $reward_money + $tuijian;
							// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $tuijianuser['openid']));
						}
					}else{

						if (!empty($result[1]['openid']) && $result[1]['isblack'] == 0) {
							if ($result[1]['level'] == 0) {
								/*
								pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
								$text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$huikui1}的推荐奖（无效奖金）";
								m('message')->sendCustomNotice($result[1]['openid'], $text);


								pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui1, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
								$text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$huikui1}的回馈奖（无效奖金）";
								m('message')->sendCustomNotice($result[1]['openid'], $text);
								*/
								pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));

							}elseif ($result[1]['level'] == 1) {
								pdo_insert('ewei_shop_money_log', array('openid' => $tuijianuser['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
								// $text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$tuijian}的推荐奖（待生效）";
								// m('message')->sendCustomNotice($result[1]['openid'], $text);


								pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 70, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
								pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));

								$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$tuijian}元的推荐奖（{$data_time}-待生效） ";
								m('message')->sendCustomNotice($result[1]['openid'], $text);

								// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
								// $newmoney['reward_money'] = $reward_money + $tuijian;
								// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
							}elseif($result[1]['level'] == 2){
								pdo_insert('ewei_shop_money_log', array('openid' => $tuijianuser['openid'], 'moneytype' => "推荐奖", 'fromopenid' => $openid, 'rank' => 0, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 300, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
								// $text = "您的下级{$self_info['realname']}购买了{$totmoney}元的{$productname}，您获得了{$tuijian}的推荐奖（待生效）";
								// m('message')->sendCustomNotice($result[1]['openid'], $text);
								pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 70, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
								pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
								$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$tuijian}元的推荐奖（{$data_time}-待生效）和{$huikui1}元的回馈奖（{$listtimeold}-待生效）和 {$zuzhi1}元的组织奖（{$data_time}-待生效）";
								m('message')->sendCustomNotice($result[1]['openid'], $text);
								// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
								// $newmoney['reward_money'] = $reward_money + $tuijian + $huikui1;
								// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
							}
						}
					}



					if ($result[2]['level'] == 1) {
						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 70, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						// $text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui2}元的回馈奖（无效奖金）";
						// m('message')->sendCustomNotice($result[2]['openid'], $text);
					}elseif ($result[2]['level'] == 2){
						pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 70, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui2}元的回馈奖（{$listtimeold}-待生效）和 {$zuzhi2}元的组织奖（{$data_time}-待生效）";
						m('message')->sendCustomNotice($result[2]['openid'], $text);

						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[2]['openid']), 'reward_money',1);
						// $newmoney['reward_money'] = $reward_money + $huikui2;
						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[2]['openid']));
					}
					// else{
					// 	pdo_insert('ewei_shop_money_log', array('openid' =>'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
					// 	pdo_insert('ewei_shop_money_log', array('openid' =>'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
					// }

					if ($result[3]['level'] == 1) {
						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 70, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						// $text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui3}元的回馈奖（无效奖金）";
						// m('message')->sendCustomNotice($result[3]['openid'], $text);
					}elseif ($result[3]['level'] == 2){
						pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => 70, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
						$text = "您的下级{$self_info['realname']}入会成功，购买了{$totmoney}元的{$productname}，您获得了{$huikui3}元的回馈奖（{$listtimeold}-待生效）和 {$zuzhi3}元的组织奖（{$data_time}-待生效）";
						m('message')->sendCustomNotice($result[3]['openid'], $text);
						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[3]['openid']), 'reward_money',1);
						// $newmoney['reward_money'] = $reward_money + $huikui3;
						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[3]['openid']));
					}
					// else{
					// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "回馈奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $huikui3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
					// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'create_time' => date('Y-m-d H:i:s',time())));
					// }
					if ($allmoney>2000) {
             $orderout =1 ;
					   $outdata =1;
						 $etcstatus =0;
						 $countime = intval(($allmoney-2000)/1000);
						 $nowtime = date('Y-m-01', time());
						 $lastday = date('Y-m-d', strtotime("$nowtime +$countime month "));
						 $lastday = date('Y-m-d', strtotime("$lastday +1 month -1 day"));
					}else {
						 $orderout = 0;
						 $etcstatus = 1;
						 $outdata = 0 ;
						 $firstday = date('Y-m-01', time());
						 $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
					}
					// $expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);



					pdo_update('ewei_shop_member', array('etcstatus'=>$etcstatus,'outdata' => $outdata,$credittype => $newcredit,'expiry_date'=>$lastday), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
					$checkordernew =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE 1 order by id desc limit 1");
					$expired_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid),'expiry_date',1);
			 		pdo_update('ewei_shop_order', array('isruhui'=>1,'orderout'=>$orderout,'expriy_date' =>$expired_date), array('ordersn' => $checkordernew['ordersn']));

    }

				else{

          $countmoney =  intval($totmoney/1000) ;

					if ($totmoney < 1000) {



					 $checkorder =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
					 $checkmember =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
					 $checklognew =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_money_log')." WHERE fromopenid = :fromopenid order by id desc limit 1", array(':fromopenid' => $checkmember['openid']));
					 $checkordernew =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE 1 order by id desc limit 1");

					 $lv['etcstatus']= 0;// 重消
           $lv['credit2'] =  $checkmember['credit2'] -$totmoney;



					 pdo_update('ewei_shop_member', $lv, array('openid' => $openid));
           $expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


           $listtime = date('Y-m',strtotime($expiry_date));


					 pdo_update('ewei_shop_order', array('expriy_date' =>$expiry_date), array('ordersn' => $checkordernew['ordersn']));
					 $zuzhi1 = $totmoney * $set['zuzhi1'] * 0.01;
 					 $zuzhi2 = $totmoney * $set['zuzhi2'] * 0.01;
 					 $zuzhi3 = $totmoney * $set['zuzhi3'] * 0.01;
					 if ($result[1]['level'] == 1) {
 						// pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
 						// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（无效奖金）";
 						// m('message')->sendCustomNotice($result[1]['openid'], $text);
 						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));

 					}elseif ($result[1]['level'] == 2) {
 						pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
 						$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（{$listtime}月-待生效）";
 						m('message')->sendCustomNotice($result[1]['openid'], $text);
 						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
 						// $newmoney['reward_money'] = $reward_money + $zuzhi1;
 						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
 					}
 					// else{
 					// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
 					// }

 					if ($result[2]['level'] == 1) {
 							pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
 						// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（无效奖金）";
 						// m('message')->sendCustomNotice($result[2]['openid'], $text);
 					}elseif ($result[2]['level'] == 2) {
 						pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
 						$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（{$listtime}月-待生效）";
 						m('message')->sendCustomNotice($result[2]['openid'], $text);

 						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[2]['openid']), 'reward_money',1);
 						// $newmoney['reward_money'] = $reward_money + $zuzhi2;
 						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[2]['openid']));
 					}
 					// else{
 					// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
 					// }

 					if ($result[3]['level'] == 1) {
 						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
 						// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（无效奖金）";
 						// m('message')->sendCustomNotice($result[3]['openid'], $text);
 					}elseif ($result[3]['level'] == 2) {
 						pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
 						$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（{$listtime}月-待生效）";
 						m('message')->sendCustomNotice($result[3]['openid'], $text);
 						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[3]['openid']), 'reward_money',1);
 						// $newmoney['reward_money'] = $reward_money + $zuzhi3;
 						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[3]['openid']));
 					}





	 }else {


					// $totmoney = 1000;
				  $checkorder =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
					$checkmember =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
					$checklognew =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_money_log')." WHERE fromopenid = :fromopenid order by id desc limit 1", array(':fromopenid' => $checkmember['openid']));
          $checkordernew =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE agentid = :agentid order by id desc limit 1", array(':agentid' => $checkmember['id']));
					// pdo_delete('ewei_shop_order',  array('id' => $checkordernew['id']));
          // $fromuserold = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order_goods')." WHERE 1  order by id desc limit 1");
          // pdo_delete('ewei_shop_order_goods', array('id' => $fromuserold['id']));
					 // pdo_delete(tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
					// $checkorder =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
					// pdo_update('ewei_shop_order', array('price' => 2000) , array('id' => $checkorder['id']));
					$lv['etcstatus']= 0;// 重消

					pdo_update('ewei_shop_member', $lv, array('openid' => $openid));

					// $orderid = 'SH'.date("YmdHis",time()).rand(100000,999999);
					 // $checkorder =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
       for ($i=1; $i < $countmoney ; $i++) {
					 if (!empty($checklognew)) {
				 	if (!empty($checklognew['put_time'])) {

				 		$checklognewtime = $checklognew['put_time'].'-10 22:00:00';
				 		$put_datatime = date('Y-m', strtotime(" +{$i} month",strtotime($checklognewtime) ));

				 	}else {

				 		$put_datatime = date('Y-m', strtotime(" +{$i} month",time()));
				  	}
				 }else {
				 		$put_datatime = date('Y-m', strtotime("+{$i} month",time()));
				 }
			 }
			    $outdata = pdo_getcolumn('ewei_shop_member', array('openid' => $openid),'outdata',1);
					$outdata = $outdata+1;
			 	  $expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);

					$listtime = $expiry_date;
          $listtime = date('Y-m-01 00:00:00',strtotime($listtime));
					$conuttime = intval($totmoney/1000);
					$data_time = '';
					for ($i=1; $i <= $conuttime ; $i++) {
						$listtime = date('Y-m', strtotime("$listtime +1 month "));
						$data_time .= $listtime.'月、';
					}
          $data_time = rtrim($data_time,'、');
					$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));
					$newtime = $countmoney+1;
					$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));
					// dump($expiry_date);exit;
					// $expiry_date = date('Y-m-d', strtotime("$expiry_date +1 month -1 day"));
			    pdo_update('ewei_shop_member', array('outdata'=>$outdata,$credittype => $newcredit,'expiry_date'=>$expiry_date), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
					$expired_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid),'expiry_date',1);
          pdo_update('ewei_shop_order', array('expriy_date' => $expired_date), array('ordersn' => $checkordernew['ordersn']));
					$zuzhi1 = $totmoney * $set['zuzhi1'] * 0.01;
					$zuzhi2 = $totmoney * $set['zuzhi2'] * 0.01;
					$zuzhi3 = $totmoney * $set['zuzhi3'] * 0.01;

					if ($result[1]['level'] == 1) {
						// pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
						// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（无效奖金）";
						// m('message')->sendCustomNotice($result[1]['openid'], $text);
						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));

					}elseif ($result[1]['level'] == 2) {
						pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
						$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（{$data_time}-待生效）";
						m('message')->sendCustomNotice($result[1]['openid'], $text);
						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
						// $newmoney['reward_money'] = $reward_money + $zuzhi1;
						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
					}
					// else{
					// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
					// }

					if ($result[2]['level'] == 1) {
							pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
						// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（无效奖金）";
						// m('message')->sendCustomNotice($result[2]['openid'], $text);
					}elseif ($result[2]['level'] == 2) {
						pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
						$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（{$data_time}-待生效）";
						m('message')->sendCustomNotice($result[2]['openid'], $text);

						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[2]['openid']), 'reward_money',1);
						// $newmoney['reward_money'] = $reward_money + $zuzhi2;
						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[2]['openid']));
					}
					// else{
					// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
					// }

					if ($result[3]['level'] == 1) {
						pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
						// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（无效奖金）";
						// m('message')->sendCustomNotice($result[3]['openid'], $text);
					}elseif ($result[3]['level'] == 2) {
						pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $checkordernew['ordersn'], 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
						$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（{$data_time}-待生效）";
						m('message')->sendCustomNotice($result[3]['openid'], $text);
						// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[3]['openid']), 'reward_money',1);
						// $newmoney['reward_money'] = $reward_money + $zuzhi3;
						// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[3]['openid']));
					}

				//  for ($i=1; $i <= $countmoney ; $i++) {
				// 			$orderid = 'SH'.date("YmdHis",time()).rand(100000,999999);
				// 	 // $checkorder =  pdo_fetch("SELECT * FROM ".tablename('ewei_shop_order')." WHERE openid = :openid order by id desc limit 1", array(':openid' => $openid));
				// 	 if (!empty($checklognew)) {
				//  	if (!empty($checklognew['put_time'])) {
			 //
				//  		$checklognewtime = $checklognew['put_time'].'-10 22:00:00';
				//  		$put_datatime = date('Y-m', strtotime(" +{$i} month",strtotime($checklognewtime) ));
			 //
				//  	}else {
			 //
				//  		$put_datatime = date('Y-m', strtotime(" +{$i} month",time()));
				//  	}
				//  }else {
				//  		$put_datatime = date('Y-m', strtotime("+{$i} month",time()));
				//  }
			 //
			 //
				//   $res2 = pdo_insert('ewei_shop_order_goods', array('uniacid' => 2, 'orderid' => $checkordernew['id']+$i, 'goodsid' => $fromuserold['goodsid'] , 'price'=> $fromuserold['price'] / $fromuserold['total'] , 'total' => 1  )  );
			 //
				//   $res = pdo_insert('ewei_shop_order', array('openid' =>  $openid, 'uniacid' => $checkorder['uniacid'], 'address' =>$checkorder['address'],  'agentid' => $checkorder['agentid'], 'ordersn' => $orderid, 'price' =>1000, 'goodsprice' =>1000, 'addressid'=>$checkorder['addressid'],'transid'=>$checkorder['transid'],'paytype'=>1, status=>1,'carrier'=>$checkorder['carrier'],'paytime'=>$checkorder['paytime'],'expresssn'=>$checkorder['expresssn'],'sendtime'=>$checkorder['sendtime'],'canceltime'=>$checkorder['canceltime'],'oldprice'=>$checkorder['oldprice'],'grprice'=>$checkorder['grprice'],'address'=>$checkorder['address'], 'createtime' => time(), 'finishtime' => $checkorder['finishtime']) );
				// 	 // $res = pdo_insert('ewei_shop_order', array('openid' =>  $openid, 'uniacid' => $checkorder['uniacid'], 'address' =>$checkorder['address'],  'agentid' => $checkorder['agentid'], 'ordersn' => $orderid, 'price' =>1000, 'goodsprice' =>1000, 'createtime' => time(), 'finishtime' => $checkorder['finishtime']) );
				// 	 if ($res==false) {
				// 		 return false;
				// 	 }
			 //

			 //
				// 	$lv['etcstatus']= 0;// 重消
				// 	pdo_update('ewei_shop_member', $lv, array('openid' => $openid));
			 //
				// 	$zuzhi1 = 1000 * $set['zuzhi1'] * 0.01;
				// 	$zuzhi2 = 1000 * $set['zuzhi2'] * 0.01;
				// 	$zuzhi3 = 1000 * $set['zuzhi3'] * 0.01;
			 //
				// 	if ($result[1]['level'] == 1) {
				// 		// pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 0, 'create_time' => date('Y-m-d H:i:s',time())));
				// 		// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（无效奖金）";
				// 		// m('message')->sendCustomNotice($result[1]['openid'], $text);
				// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));
			 //
				// 	}elseif ($result[1]['level'] == 2) {
				// 		pdo_insert('ewei_shop_money_log', array('openid' => $result[1]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
				// 		$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi1}元的组织奖（{$put_datatime}月-待生效）";
				// 		m('message')->sendCustomNotice($result[1]['openid'], $text);
			 //
				// 		// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[1]['openid']), 'reward_money',1);
				// 		// $newmoney['reward_money'] = $reward_money + $zuzhi1;
				// 		// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[1]['openid']));
				// 	}
				// 	// else{
				// 	// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 1, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi1, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
				// 	// }
			 //
				// 	if ($result[2]['level'] == 1) {
				// 			pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
				// 		// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（无效奖金）";
				// 		// m('message')->sendCustomNotice($result[2]['openid'], $text);
				// 	}elseif ($result[2]['level'] == 2) {
				// 		pdo_insert('ewei_shop_money_log', array('openid' => $result[2]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
				// 		$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi2}元的组织奖（{$put_datatime}月-待生效）";
				// 		m('message')->sendCustomNotice($result[2]['openid'], $text);
			 //
				// 		// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[2]['openid']), 'reward_money',1);
				// 		// $newmoney['reward_money'] = $reward_money + $zuzhi2;
				// 		// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[2]['openid']));
				// 	}
				// 	// else{
				// 	// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 2, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi2, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
				// 	// }
			 //
				// 	if ($result[3]['level'] == 1) {
				// 		pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
				// 		// $text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（无效奖金）";
				// 		// m('message')->sendCustomNotice($result[3]['openid'], $text);
				// 	}elseif ($result[3]['level'] == 2) {
				// 		pdo_insert('ewei_shop_money_log', array('openid' => $result[3]['openid'], 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1, 'put_time'=>$put_datatime,'create_time' => date('Y-m-d H:i:s',time())));
				// 		$text = "您的下级{$self_info['realname']}重消成功，购买了{$totmoney}元的{$productname}，您获得了{$zuzhi3}元的组织奖（{$put_datatime}月-待生效）";
				// 		m('message')->sendCustomNotice($result[3]['openid'], $text);
			 //
			 //
				// 		// $reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $result[3]['openid']), 'reward_money',1);
				// 		// $newmoney['reward_money'] = $reward_money + $zuzhi3;
				// 		// pdo_update('ewei_shop_setting', $newmoney, array('openid' => $result[3]['openid']));
				// 	}
				// 	// else{
				// 	// 	pdo_insert('ewei_shop_money_log', array('openid' => 'CFKadmin', 'moneytype' => "组织奖", 'fromopenid' => $openid, 'rank' => 3, 'orderid' => $orderid, 'totmoney' => $totmoney, 'money' => $zuzhi3, 'status' => 1,'put_time'=>$put_datatime, 'create_time' => date('Y-m-d H:i:s',time())));
				// 	// }
			 //
       // }

          }
			//---------------------------
				}




				// dump($cxneedmoney);exit;
				// $expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);
				// if ($expiry_date > date('Y-m-d',time())) {
				// 	pdo_update('ewei_shop_member', array($credittype => $newcredit,'expiry_date'=>date("Y-m-d",strtotime("+$n month",strtotime($expiry_date)))), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
				// }else{
				// 	$firstday = date('Y-m-01', time());
 				//   $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
				// 	pdo_update('ewei_shop_member', array($credittype => $newcredit,'expiry_date'=>$lastday), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
				// }

			}else{ // 退款的情况






				if ($self_info['etcstatus'] == 1) {  // 入会消费




					$tuihuan = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=:status AND id=:id', array(':openid' => $openid, ':status' =>1,':id'=>$order_id));



					//$tuihuan['isruhui']==1,入会订单


					$tuitime=$tuihuan['createtime'];
					$time = date('Y',$tuitime);
					$apply_refund=$tuihuan['apply_refund'];


				//退款时期
					$time2=date('Y',$apply_refund);

					$nian=date("Y")-$time2;

					$mony=intval(date('m',$apply_refund));

					$update_time=$nian*12+$mony;

					//购买日期

					$nian1=date("Y")-$time;
					$money1=$time = date('m',$tuitime);
					$timne=$nian1*12+$money1;


					$time3=$update_time-$timne;




					// $n = floor($totmoney/$set['cxneedmoney']);//获取多少个月的
					// $lastdate=date("Y-m-d",strtotime("-$n month",strtotime($self_info['expiry_date'])));
					if ($totmoney >= $set['level0_up']) { // 退款的钱大于入会所需的钱，如何判断是重新消费和第一次入会消费
						// 过期


						$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);
						$credit2new = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2',1);
					 if (date("Y-m",strtotime($expiry_date)) < date("Y-m",time())) {



						 	 $lv['level'] = 0 ;
							 $lv['invalidtime'] = date('Y-m-d',time());
							 $lv['isexpiry'] = 1;


							 pdo_update('ewei_shop_member',$lv,array('openid' => $openid));


						 m('message')->sendCustomNotice($openid, "您购买了{$totmoney}元的{$productname}已退货，您的等级已经失效");
						 $totmoney = $totmoney - $set['level1needmoney'] + $set['cxneedmoney'];
					 }else {


					 		//退款时间-订购时间>0失效(跨月)
							//退款时间-订购时间=0普通会员（没跨月）
					 		//time3==0没跨月,time3>0

					 		//$time3=1;
					 		//



					if($tuihuan['isruhui']==1){



					   if($time3==0){

						 	$lv['level'] = 0;
							$lv['invalidtime'] = date('Y-m-d',time());
							$lv['credit2'] = $credit2new+ $totmoney;
							// $expiry_date=pdo_fetch('select expiry_date from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));

									$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


									$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));
									$newtime = $countmoney+1;

	                                $expiry_date= date("Y-m-d",strtotime("-1 month",strtotime($expiry_date)));
									$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));


							 		$lv['expiry_date']=$expiry_date;

							$agentid=pdo_fetch('select agentid from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));




							pdo_update('ewei_shop_member',$lv,array('openid' => $openid));


							$anzhi=pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid and level=:level ", array(':agentid' =>$agentid['agentid'],'level'=>1));

							$seting=pdo_fetch('select cxneedpeople from ' . tablename('ewei_shop_setting') . ' WHERE id=:id ', array(':id' =>1 ));



							if($anzhi<$seting['cxneedpeople']){

								$wh=array(
									'level'=>1
								);
								pdo_update('ewei_shop_member',$wh,array('id' => $agentid['agentid']));
							}


					    }elseif($time3>0){
					 	$lv['level']=0;

					 	$time4=date('Y');




					 		$expiry_date=pdo_fetch('select expiry_date from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));


					 		 $datc= date("Y-m-d",strtotime("-1 month",strtotime($expiry_date['expiry_date'])));




					 			$lv['invalidtime']=date('Y').'-'.(date('m')-1).'-'.'30';



					 			$daty=date('Y').'-'.(date('m')-1).'-'.'30';




							 	 // $lv['expiry_date']=;


								$lv['credit2'] = $credit2new+ $totmoney;

							 	$lv['isexpiry']=1;


								$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


								$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));
								$newtime = $countmoney+1;

                                $expiry_date= date("Y-m-d",strtotime("-1 month",strtotime($expiry_date)));
								$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));

								$lv['expiry_date']=$expiry_date;


					 		pdo_update('ewei_shop_member',$lv,array('openid' => $openid));
							$agentid=pdo_fetch('select agentid from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));

							$anzhi=pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid and level=:level ", array(':agentid' =>$agentid['agentid'],'level'=>1));

							$seting=pdo_fetch('select cxneedpeople from ' . tablename('ewei_shop_setting') . ' WHERE id=:id ', array(':id' =>1 ));



							if($anzhi<$seting['cxneedpeople']){

								$wh=array(
									'level'=>1
								);
								pdo_update('ewei_shop_member',$wh,array('id' => $agentid['agentid']));
							}


					    }

					}else{



					}





					 	  m('message')->sendCustomNotice($openid, "您购买了{$totmoney}元的{$productname}已退货");
					}

					}
					$tuijian = $totmoney * $set['tuijian'] * 0.01;
					$huikui1 = $totmoney * $set['huikui1'] * 0.01;
					$huikui2 = $totmoney * $set['huikui2'] * 0.01;
					$huikui3 = $totmoney * $set['huikui3'] * 0.01;
					// dump($tuijian);
					// dump($huikui1);
					// dump($huikui2);
					// dump($huikui3);exit;
					if ($self_info['anzhi'] != $self_info['agentid'] && $self_info['anzhi'] != 0 && $self_info['anzhi'] !='') {
						$tuijianuser = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE id = :id", array(':id' => $self_info['anzhi']));
						if ($tuijianuser['level'] == 0) {


						}else{

							$text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$tuijian}元的推荐奖";
							m('message')->sendCustomNotice($tuijianuser['openid'], $text);
						}
					}else{
						if (!empty($result[1]['openid']) && $result[1]['isblack'] == 0) {
							if ($result[1]['level'] == 0) {


							}elseif ($result[1]['level'] == 1) {

								// $text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$tuijian}元的推荐奖（待生效）和{$huikui1}元的回馈奖（无效奖金）";
								// m('message')->sendCustomNotice($result[1]['openid'], $text);

							}else{
								$text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$tuijian}元推荐奖和{$huikui1}元回馈奖";
								m('message')->sendCustomNotice($result[1]['openid'], $text);
							}
						}
					}
					if ($result[2]['level'] == 1) {
						// $text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$huikui2}元的回馈奖（无效奖金）";
						// m('message')->sendCustomNotice($result[2]['openid'], $text);
					}elseif ($result[2]['level'] == 2){
						$text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$huikui2}元的回馈奖";
						m('message')->sendCustomNotice($result[2]['openid'], $text);


					}

					if ($result[3]['level'] == 1) {

						// $text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$huikui3}元的回馈奖（无效奖金）";
						// m('message')->sendCustomNotice($result[3]['openid'], $text);
					}elseif ($result[3]['level'] == 2){

						$text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$huikui3}元的回馈奖";
						m('message')->sendCustomNotice($result[3]['openid'], $text);
					}

					// dump($cxneedmoney);exit;
					$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);
					// if (date("Y-m",strtotime($expiry_date)) < date("Y-m",time())) {

					// pdo_update('ewei_shop_member', array($credittype => $newcredit,'expiry_date'=>''), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
					// }

				}else{



					// 重消




					$zuzhi1 = $totmoney * $set['zuzhi1'] * 0.01;
					$zuzhi2 = $totmoney * $set['zuzhi2'] * 0.01;
					$zuzhi3 = $totmoney * $set['zuzhi3'] * 0.01;



					if ($result[1]['level'] == 1) {



						// $text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$zuzhi1}元的组织奖（无效奖金）";
						// m('message')->sendCustomNotice($result[1]['openid'], $text);
					}elseif ($result[1]['level'] == 2) {

						$text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$zuzhi1}元的组织奖";
						m('message')->sendCustomNotice($result[1]['openid'], $text);

					}

					if ($result[2]['level'] == 1) {

						// $text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$zuzhi2}元的组织奖（无效奖金）";
						// m('message')->sendCustomNotice($result[2]['openid'], $text);
					}elseif ($result[2]['level'] == 2) {

						$text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$zuzhi2}元的组织奖";
						m('message')->sendCustomNotice($result[2]['openid'], $text);


					}

					if ($result[3]['level'] == 1) {

						// $text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$zuzhi3}元的组织奖（无效奖金）";
						// m('message')->sendCustomNotice($result[3]['openid'], $text);
					}elseif ($result[3]['level'] == 2) {

						$text = "您的下级{$self_info['realname']}购买的{$totmoney}元的{$productname}已申请退款成功，需从您的账户扣除{$zuzhi3}元的组织奖";
						m('message')->sendCustomNotice($result[3]['openid'], $text);

					}

					// $check = pdo_fetchall("SELECT * FROM ".tablename('ewei_shop_order')." WHERE status != -1  and isruhui =0 and  openid = :openid", array(':openid' => $openid));
				 //
 				 // if (count($check)>=1) {
 					// 	return show_json(0,'6666');
 				 // }
					//$n = floor($totmoney/$set['cxneedmoney']);




					$outdata = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'outdata',1);


					$tuihuan = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=:status AND id=:id', array(':openid' => $openid, ':status' =>1,':id'=>$order_id));




					if ($outdata==1 || $tuihuan['isruhui']==1) {






					$tuihuan = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=:status AND id=:id', array(':openid' => $openid, ':status' =>1,':id'=>$order_id));


					//$tuihuan['isruhui']==1,入会订单


					$tuitime=$tuihuan['createtime'];
					$time = date('Y',$tuitime);
					$apply_refund=$tuihuan['apply_refund'];


				//退款时期
					$time2=date('Y',$apply_refund);

					$nian=date("Y")-$time2;

					$mony=intval(date('m',$apply_refund));

					$update_time=$nian*12+$mony;

					//购买日期

					$nian1=date("Y")-$time;
					$money1=$time = date('m',$tuitime);
					$timne=$nian1*12+$money1;


					$time3=$update_time-$timne;



					//退款时间-订购时间>0失效(跨月)
					//退款时间-订购时间=0普通会员（没跨月）





           				//$y = floor(intval(($totmoney-2000)/$set['cxneedmoney']));
						$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);
						$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));
						$newtime = $countmoney+1;
						$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));




						$y=floor(intval(($totmoney-2000)));

						$x =$y/1000+1;

						$x=intval($x);

						$tic=date("Y-m",strtotime("-$x month",strtotime($expiry_date)));


						$uc=date("Y-m",strtotime("-$x month",strtotime($expiry_date)));




				 		if (date("Y-m",strtotime("-$x month",strtotime($expiry_date))) < date('Y-m',time()) ) {




							 $expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);
							 $credit2date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2',1);
							 $credit2date = $credit2date - 1000;






							//入会大于2000

					if($time3==0){

									$y=floor(intval(($totmoney-2000)));

									 $x =$y/1000+1;

									$x=intval($x);

								 $expiry_date=pdo_fetch('select expiry_date from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));

									 $expiry_date=$expiry_date['expiry_date'];


									$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


									$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));
									$newtime = $countmoney+1;




			                       $expiry_date= date("Y-m-d",strtotime("-$x month",strtotime($expiry_date)));


			                       $main=date('Y-m-01');


			                        if($main>$expiry_date){


			                        	$expiry_date = date("Y-m-d",strtotime("+0 month -1day",strtotime($main)));


			                        }else{

			                        	$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));
			                        }







							$datc=$expiry_date;

							$agentid=pdo_fetch('select agentid from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));

							$anzhi=pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid and level=:level ", array(':agentid' =>$agentid['agentid'],'level'=>1));

							$seting=pdo_fetch('select cxneedpeople from ' . tablename('ewei_shop_setting') . ' WHERE id=:id ', array(':id' =>1 ));







							pdo_update('ewei_shop_member', array('level'=>0,'credit2' => $credit2date, $credittype => $newcredit,'expiry_date'=>$datc), array('uniacid' => $_W['uniacid'], 'openid' => $openid));

							if($anzhi<$seting['cxneedpeople']){

								$wh=array(
									'level'=>1
								);
								pdo_update('ewei_shop_member',$wh,array('id' => $agentid['agentid']));
							}


						}else if($time3>0){



							$y=floor(intval(($totmoney-2000)));

							 $x =$y/1000+1;

							 $x=intval($x);
							 $expiry_date=pdo_fetch('select expiry_date from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));

							 $expiry_date=$expiry_date['expiry_date'];


							$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


							$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));
							$newtime = $countmoney+1;

	                        $expiry_date= date("Y-m-d",strtotime("-$x month",strtotime($expiry_date)));
							$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));

							$datc=$expiry_date;



						 	pdo_update('ewei_shop_member', array('isexpiry'=>1,'level'=>0,'invalidtime'=>$datc,'credit2' => $credit2date, $credittype => $newcredit,'expiry_date'=>$datc), array('uniacid' => $_W['uniacid'], 'openid' => $openid));

						 	$agentid=pdo_fetch('select agentid from ' . tablename('ewei_shop_member') . ' where openid=:openid ', array(':openid' => $openid));

							$anzhi=pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid and level=:level ", array(':agentid' =>$agentid['agentid'],'level'=>1));

							$seting=pdo_fetch('select cxneedpeople from ' . tablename('ewei_shop_setting') . ' WHERE id=:id ', array(':id' =>1 ));



							if($anzhi<$seting['cxneedpeople']){

								$wh=array(
									'level'=>1
								);
								pdo_update('ewei_shop_member',$wh,array('id' => $agentid['agentid']));
							}



						 }

					 }else {



							$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);
							$credit2date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2',1);
							$credit2date = $credit2date - 1000;




							 $x =$totmoney/1000;
							 $x=intval($x);


							$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);

							$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));
							$newtime = $countmoney+1;


							$expiry_date= date("Y-m-d",strtotime("-$x month",strtotime($expiry_date)));

							$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));

							//$expiry_date = date("Y-m-d",strtotime("+1 month -1day",strtotime($expiry_date)));



							$level=pdo_fetch("SELECT level FROM ".tablename('ewei_shop_member')." WHERE openid = :openid ", array(':openid' =>$openid));


							$credit2 = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2');


						$tuihuan = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=:status AND id=:id', array(':openid' => $openid, ':status' =>1,':id'=>$order_id));

							$money = $credit2+ $tuihuan['grprice'];




							if($tuihuan['grprice']>1000){


							pdo_update('ewei_shop_member', array('level'=>$level['level'],'invalidtime'=> date('Y-m-d',time()) ,'credit2' => $money, $credittype => $newcredit,'expiry_date'=>$expiry_date ), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
							}else{


									$credit2 = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2');

										$money = $credit2+ $tuihuan['grprice'];

										$wh=array(
											'credit2'=>$money,

										);

										$che=pdo_update('ewei_shop_member',$wh,array('openid' => $openid));

							}


						}

					}else {



						$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);
						$credit2date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2',1);

						$credit2date = $credit2date - 1000;





					$tuihuan = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=:status AND id=:id', array(':openid' => $openid, ':status' =>1,':id'=>$order_id));





						//重消1000元的处理
						if($tuihuan['isruhui']==0){



								//退款日期
								$apply_refund=date('Y',$tuihuan['apply_refund']);



								//有效日期
								$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',0);


								$expiry_datc=strtotime($expiry_date);
								$expiry_date = date('Y',$expiry_datc);




								$update=$expiry_date-$apply_refund;


								$uo=date('m',$expiry_datc);
								$update_time=$update*12+$uo;


								$update=$update_time-date('m',$tuihuan['apply_refund']);




							if($update>=0){


								$y=$tuihuan['grprice'];


							 	$x =$y/1000;

							 	$x=intval($x);



							 	if($x>0){
							 		 //$x>0 1000元重消
									 	$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


										$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));

										$newtime = $countmoney+1;


				                        $expiry_date= date("Y-m-d",strtotime("-$x month",strtotime($expiry_date)));

										$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));


										$datc=$expiry_date;


										$credit2 = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2');



										$money = $credit2+ $tuihuan['grprice'];

										$wh=array(
											'credit2'=>$money,
											'expiry_date'=>$datc
										);



										$che=pdo_update('ewei_shop_member',$wh,array('openid' => $openid));

							 	}else{

							 		//$x<1000 1000以上重消


									$credit2 = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2');



										$money = $credit2+ $tuihuan['grprice'];

										$wh=array(
											'credit2'=>$money,
										);




										$che=pdo_update('ewei_shop_member',$wh,array('openid' => $openid));


							 	}


							}else if($update==-1){



								$y=$tuihuan['grprice'];


							 	$x =$y/1000;

							 	$x=intval($x);

								$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


								$expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));

								$newtime = $countmoney+1;


		                        $expiry_date= date("Y-m-d",strtotime("-$x month",strtotime($expiry_date)));

								$expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));


								$datc=$expiry_date;


								$credit2 = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2');




								$money = $credit2+ $tuihuan['grprice'];

								$wh=array(
									'credit2'=>$money,
									'expiry_date'=>$datc,

								);


								$che=pdo_update('ewei_shop_member',$wh,array('openid' => $openid));
							}else{



								$y=$tuihuan['grprice'];


							 	$x =$y/1000;

							 	$x=intval($x);

								$expiry_date = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'expiry_date',1);


								// $expiry_date = date('Y-m-01',strtotime($expiry_date." 00:00:00"));

								// $newtime = $countmoney+1;


		      //                   $expiry_date= date("Y-m-d",strtotime("-$x month",strtotime($expiry_date)));

								// $expiry_date = date("Y-m-d",strtotime("+{$newtime} month -1day",strtotime($expiry_date)));


								// $datc=$expiry_date;


								$credit2 = pdo_getcolumn('ewei_shop_member', array('openid' => $openid), 'credit2');



								$money = $credit2+ $tuihuan['grprice'];

								$wh=array(
									'credit2'=>$money,
									'expiry_date'=>$expiry_date,
									'invalidtime'=>$expiry_date,
									'isexpiry'=>1

								);


								$che=pdo_update('ewei_shop_member',$wh,array('openid' => $openid));
							}



						}


						// pdo_update('ewei_shop_member', array('outdata'=>$outdata-1, 'credit2' => $credit2date, $credittype => $newcredit,'expiry_date'=>date("Y-m-d",strtotime("-$n month",strtotime($expiry_date)))), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
					}

				}
			}
		}

	}


	public function getCredit($openid = '', $credittype = 'credit1')
	{
		global $_W;
		load()->model('mc');
		$uid = mc_openid2uid($openid);
		if (!(empty($uid)))
		{
			return pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('mc_members') . ' WHERE `uid` = :uid', array(':uid' => $uid));
		}
		return pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('ewei_shop_member') . ' WHERE  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
	}

	public function getCredits($openid = '', $credittypes = array('credit1', 'credit2'))
	{
		global $_W;
		load()->model('mc');
		$uid = mc_openid2uid($openid);
		$types = implode(',', $credittypes);
		if (!(empty($uid)))
		{
			return pdo_fetch('SELECT ' . $types . ' FROM ' . tablename('mc_members') . ' WHERE `uid` = :uid limit 1', array(':uid' => $uid));
		}
		return pdo_fetch('SELECT ' . $types . ' FROM ' . tablename('ewei_shop_member') . ' WHERE  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
	}


	public function checkMember()
	{
		global $_W;
		global $_GPC;
		set_time_limit(0);
		//超过有效期->清零
		//没有超过有效期，但是推荐人数没有10个，有就2级，没有就1级

		// $level1['level'] = 0;
		// $level1['invalidtime'] = date('Y-m-d',time());
		// $level1['isexpiry'] = 1;
		// pdo_update('ewei_shop_member', $level1, array('id' => 2245));

		// $time1 = date('Y-m',time())."-1";
		// $time2 = date("Y-m-d",strtotime("+1 month",strtotime($time1)));
		// $wages=pdo_fetchall("select * from ". tablename('ewei_shop_wages').'where openid!="admin" and sendtime>=:time1 and sendtime<:time2',array(':time1'=>$time1,':time2'=>$time2));
		// dump($wages);exit;


		$month = date('m',time());
		$day = date('d',time());

		// dump($day);
		// dump(date('Y-m-d',time()));exit;


		$set = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_setting')." WHERE id = :id", array(':id' => '1'));

		$sea = pdo_fetch("SELECT time6 FROM ".tablename('ewei_shop_bonus')." WHERE id = :id", array(':id' => '1'));

		$time=$sea['time6'];

		$str =(int)$time;

		$data=date('Y-m-d H:i');

		$date1=strtotime($data);

		if ($date1==$str) {



			$user=pdo_fetchall("select id,openid,level,expiry_date from ". tablename('ewei_shop_member'));
			//$time1 = date('Y-m',time())."-1";
			$sea = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_bonus')." WHERE id = :id", array(':id' => '1'));



			$ceq=$sea['time2']+1;



			$time1=$sea['time1'].'-'.$sea['time2'].'-'.'01';
			$time2=$sea['time1'].'-'.$sea['time2'].'-'.'31';
			// $time2 = date("Y-m-d",strtotime("+1 month -1day",strtotime($time1)));







			$wages=pdo_fetchall("select * from ". tablename('ewei_shop_wages').'where openid!="admin" and sendtime>=:time1 and sendtime<=:time2',array(':time1'=>$time1,':time2'=>$time2));






			$newtime = date('Y-m-d',time());


			// dump($user);
			foreach ($user as $key => $value) {
				// echo "111";
				if ($value['expiry_date'] < $newtime && $value['expiry_date'] != '') {
					// dump($value);
					// $level1['level'] = 0;
					$level1['invalidtime'] = date('Y-m-d',time());
					// $level1['isexpiry'] = 1;
					pdo_update('ewei_shop_member', $level1, array('id' => $value['id']));
				}else{
					$level2['level'] = 1;
					$totzhitui = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_member')." WHERE agentid = :agentid and level > 0", array(':agentid' => $value['id']));
					// if ($totzhitui<$set['cxneedpeople'] && $value['level'] == 2) {
					// 	pdo_update('ewei_shop_member', $level2, array('id' => $value['id']));
					// }
				}
			}



			foreach ($wages as $key => $m) {



				if($m['send']==0){


					$newmoney['reward_money']=0;
					pdo_update('ewei_shop_wages', array('send' => 1), array('id' => $m['id']));
					$reward_money = pdo_getcolumn('ewei_shop_member', array('openid' => $m['openid']), 'reward_money',1);

					$newmoney['reward_money'] = $reward_money + $m['tuijian'] + $m['huikui'] + $m['zuzhi'];

					// var_dump($m['openid']);exit;
					// var_dump($newmoney['reward_money']);exit;
					pdo_update('ewei_shop_member', $newmoney, array('openid' => $m['openid']));

					// $wh=array(
					// 	'fafan_status'=>1
					// );
					// pdo_update('ewei_shop_wages',$wh,array('openid' => $m['openid']));
				}





			}


			$s['plan'] = $month;
			pdo_update('ewei_shop_setting', $s, array('id' => '1'));
		}



		$member = array();
		$shopset = m('common')->getSysset(array('shop', 'wap'));
		$openid = $_W['openid'];
		if (($_W['routes'] == 'order.pay_alipay') || ($_W['routes'] == 'creditshop.log.dispatch_complete') || ($_W['routes'] == 'creditshop.detail.creditshop_complete') || ($_W['routes'] == 'order.pay_alipay.recharge_complete') || ($_W['routes'] == 'order.pay_alipay.complete') || ($_W['routes'] == 'newmr.alipay') || ($_W['routes'] == 'newmr.callback.gprs') || ($_W['routes'] == 'newmr.callback.bill') || ($_W['routes'] == 'account.sns'))
		{
			return;
		}

		if ($shopset['wap']['open'])
		{
			if (($shopset['wap']['inh5app'] && is_h5app()) || (empty($shopset['wap']['inh5app']) && empty($openid)))
			{
				return;
			}
		}

		if (empty($openid) && !(EWEI_SHOPV2_DEBUG))
		{
			$diemsg = ((is_h5app() ? 'APP正在维护, 请到公众号中访问' : '请在微信客户端打开链接'));
			exit('<!DOCTYPE html>' . "\r\n" . '                <html>' . "\r\n" . '                    <head>' . "\r\n" . '                        <meta name=\'viewport\' content=\'width=device-width, initial-scale=1, user-scalable=0\'>' . "\r\n" . '                        <title>抱歉，出错了</title><meta charset=\'utf-8\'><meta name=\'viewport\' content=\'width=device-width, initial-scale=1, user-scalable=0\'><link rel=\'stylesheet\' type=\'text/css\' href=\'https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css\'>' . "\r\n" . '                    </head>' . "\r\n" . '                    <body>' . "\r\n" . '                    <div class=\'page_msg\'><div class=\'inner\'><span class=\'msg_icon_wrp\'><i class=\'icon80_smile\'></i></span><div class=\'msg_content\'><h4>' . $diemsg . '</h4></div></div></div>' . "\r\n" . '                    </body>' . "\r\n" . '                </html>');
		}

		$member = m('member')->getMember($openid);
		$followed = m('user')->followed($openid);
		$uid = 0;
		$mc = array();
		load()->model('mc');

		if ($followed || empty($shopset['shop']['getinfo']) || ($shopset['shop']['getinfo'] == 1))
		{
			$uid = mc_openid2uid($openid);
			// if (!(EWEI_SHOPV2_DEBUG))
			if (!$member['avatar'])
			{
				$userinfo = mc_oauth_userinfo();
				// dump($userinfo);
			}
			else
			{
				$userinfo = array('openid' => $member['openid'], 'nickname' => $member['nickname'], 'headimgurl' => $member['avatar'], 'gender' => $member['gender'], 'province' => $member['province'], 'city' => $member['city']);

			}
			$mc = array();
			$mc['nickname'] = $userinfo['nickname'];
			$mc['avatar'] = $userinfo['headimgurl'];
			$mc['gender'] = $userinfo['sex'];
			$mc['resideprovince'] = $userinfo['province'];
			$mc['residecity'] = $userinfo['city'];
		}

		if (empty($member) && !(empty($openid)))
		{
			$member = array('uniacid' => $_W['uniacid'], 'uid' => $uid, 'openid' => $openid, 'realname' => (!(empty($mc['realname'])) ? $mc['realname'] : ''), 'mobile' => (!(empty($mc['mobile'])) ? $mc['mobile'] : ''), 'nickname' => (!(empty($mc['nickname'])) ? $mc['nickname'] : ''), 'avatar' => (!(empty($mc['avatar'])) ? $mc['avatar'] : ''), 'gender' => (!(empty($mc['gender'])) ? $mc['gender'] : '-1'), 'province' => (!(empty($mc['resideprovince'])) ? $mc['resideprovince'] : ''), 'city' => (!(empty($mc['residecity'])) ? $mc['residecity'] : ''), 'area' => (!(empty($mc['residedist'])) ? $mc['residedist'] : ''), 'createtime' => time(), 'status' => 0);
			$member['avatar'] = substr($member['avatar'], 0 , -3);
			// dump($member);exit;
			pdo_insert('ewei_shop_member', $member);
			$member['id'] = pdo_insertid();
			// echo "66";exit;
		}
		else
		{
			if ($member['isblack'] == 1)
			{
				show_message('暂时无法访问，请稍后再试!');
			}
			$upgrade = array('uid' => $uid);

			if (isset($mc['nickname']) && ($member['nickname'] != $mc['nickname']))
			{
				$upgrade['nickname'] = $mc['nickname'];
			}

			if (isset($mc['avatar']) && ($member['avatar'] != $mc['avatar']))
			{
				$upgrade['avatar'] = $mc['avatar'];
				// dump($mc);
			}
			if (isset($mc['gender']) && ($member['gender'] != $mc['gender']))
			{
				$upgrade['gender'] = $mc['gender'];
			}
			if (!(empty($upgrade)))
			{
				// dump($upgrade);exit;
				pdo_update('ewei_shop_member', $upgrade, array('id' => $member['id']));
			}

		}
		if (p('commission'))
		{
			p('commission')->checkAgent($openid);
		}
		if (p('poster'))
		{
			p('poster')->checkScan($openid);
		}
		if (empty($member))
		{
			return false;
		}
		return array('id' => $member['id'], 'openid' => $member['openid']);
	}
	public function getLevels($all = true)
	{
		global $_W;
		$condition = '';
		if (!($all))
		{
			$condition = ' and enabled=1';
		}
		return pdo_fetchall('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid' . $condition . ' order by level asc', array(':uniacid' => $_W['uniacid']));
	}
	public function getLevel($openid)
	{
		global $_W;
		global $_S;
		if (empty($openid))
		{
			return false;
		}
		$member = m('member')->getMember($openid);
		if (!(empty($member)) && !(empty($member['level'])))
		{
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $member['level'], ':uniacid' => $_W['uniacid']));
			if (!(empty($level)))
			{
				return $level;
			}
		}
		return array('levelname' => (empty($_S['shop']['levelname']) ? '普通会员' : $_S['shop']['levelname']), 'discount' => (empty($_S['shop']['leveldiscount']) ? 10 : $_S['shop']['leveldiscount']));
	}
	public function upgradeLevel($openid)
	{
		global $_W;
		if (empty($openid))
		{
			return;
		}
		$shopset = m('common')->getSysset('shop');
		$leveltype = intval($shopset['leveltype']);
		$member = m('member')->getMember($openid);
		if (empty($member))
		{
			return;
		}
		$level = false;
		if (empty($leveltype))
		{
			$ordermoney = pdo_fetchcolumn('select ifnull( sum(og.realprice),0) from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_order') . ' o on o.id=og.orderid ' . ' where o.openid=:openid and o.status=3 and o.uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid  and enabled=1 and ' . $ordermoney . ' >= ordermoney and ordermoney>0  order by level desc limit 1', array(':uniacid' => $_W['uniacid']));
		}
		else if ($leveltype == 1)
		{
			$ordercount = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=3 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid and enabled=1 and ' . $ordercount . ' >= ordercount and ordercount>0  order by level desc limit 1', array(':uniacid' => $_W['uniacid']));
		}
		if (empty($level))
		{
			return;
		}
		if ($level['id'] == $member['level'])
		{
			return;
		}
		$oldlevel = $this->getLevel($openid);
		$canupgrade = false;
		if (empty($oldlevel['id']))
		{
			$canupgrade = true;
		}
		else if ($oldlevel['level'] < $level['level'])
		{
			$canupgrade = true;
		}
		if ($canupgrade)
		{
			pdo_update('ewei_shop_member', array('level' => $level['id']), array('id' => $member['id']));
			m('notice')->sendMemberUpgradeMessage($openid, $oldlevel, $level);
		}
	}
	public function getGroups()
	{
		global $_W;
		return pdo_fetchall('select * from ' . tablename('ewei_shop_member_group') . ' where uniacid=:uniacid order by id asc', array(':uniacid' => $_W['uniacid']));
	}
	public function getGroup($openid)
	{
		if (empty($openid))
		{
			return false;
		}
		$member = m('member')->getMember($openid);
		return $member['groupid'];
	}
	public function setRechargeCredit($openid = '', $money = 0)
	{
		if (empty($openid))
		{
			return;
		}
		global $_W;
		$credit = 0;
		$set = m('common')->getSysset(array('trade', 'shop'));
		if ($set['trade'])
		{
			$tmoney = floatval($set['trade']['money']);
			$tcredit = intval($set['trade']['credit']);
			if ($tmoney <= $money)
			{
				if (($money % $tmoney) == 0)
				{
					$credit = intval($money / $tmoney) * $tcredit;
				}
				else
				{
					$credit = (intval($money / $tmoney) + 1) * $tcredit;
				}
			}
		}
		if (0 < $credit)
		{
			$this->setCredit($openid, 'credit1', $credit, array(0, $set['shop']['name'] . '会员充值积分:credit2:' . $credit));
		}
	}
	public function getCalculateMoney($money, $set_array)
	{
		$charge = $set_array['charge'];
		$begin = $set_array['begin'];
		$end = $set_array['end'];
		$array = array();
		$array['deductionmoney'] = round(($money * $charge) / 100, 2);
		if (($begin <= $array['deductionmoney']) && ($array['deductionmoney'] <= $end))
		{
			$array['deductionmoney'] = 0;
		}
		$array['realmoney'] = round($money - $array['deductionmoney'], 2);
		if ($money == $array['realmoney'])
		{
			$array['flag'] = 0;
		}
		else
		{
			$array['flag'] = 1;
		}
		return $array;
	}
	public function checkMemberFromPlatform($openid = '')
	{
		global $_W;
		$acc = WeiXinAccount::create($_W['acid']);
		$userinfo = $acc->fansQueryInfo($openid);
		$userinfo['avatar'] = $userinfo['headimgurl'];
		load()->model('mc');
		$uid = mc_openid2uid($openid);
		if (!(empty($uid)))
		{
			pdo_update('mc_members', array('nickname' => $userinfo['nickname'], 'gender' => $userinfo['sex'], 'nationality' => $userinfo['country'], 'resideprovince' => $userinfo['province'], 'residecity' => $userinfo['city'], 'avatar' => $userinfo['headimgurl']), array('uid' => $uid));
		}
		pdo_update('mc_mapping_fans', array('nickname' => $userinfo['nickname']), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
		$member = $this->getMember($openid);
		if (empty($member))
		{
			$mc = mc_fetch($uid, array('realname', 'nickname', 'mobile', 'avatar', 'resideprovince', 'residecity', 'residedist'));
			$member = array('uniacid' => $_W['uniacid'], 'uid' => $uid, 'openid' => $openid, 'realname' => $mc['realname'], 'mobile' => $mc['mobile'], 'nickname' => (!(empty($mc['nickname'])) ? $mc['nickname'] : $userinfo['nickname']), 'avatar' => (!(empty($mc['avatar'])) ? $mc['avatar'] : $userinfo['avatar']), 'gender' => (!(empty($mc['gender'])) ? $mc['gender'] : $userinfo['sex']), 'province' => (!(empty($mc['resideprovince'])) ? $mc['resideprovince'] : $userinfo['province']), 'city' => (!(empty($mc['residecity'])) ? $mc['residecity'] : $userinfo['city']), 'area' => $mc['residedist'], 'createtime' => time(), 'status' => 0);
			pdo_insert('ewei_shop_member', $member);
			$member['id'] = pdo_insertid();
			$member['isnew'] = true;
		}
		else
		{
			$member['nickname'] = $userinfo['nickname'];
			$member['avatar'] = $userinfo['headimgurl'];
			$member['province'] = $userinfo['province'];
			$member['city'] = $userinfo['city'];
			pdo_update('ewei_shop_member', $member, array('id' => $member['id']));
			$member['isnew'] = false;
		}
		return $member;
	}
	public function mc_update($mid, $data)
	{
		global $_W;
		if (empty($mid) || empty($data))
		{
			return;
		}
		$wapset = m('common')->getSysset('wap');
		$member = $this->getMember($mid);
		if (!(empty($wapset['open'])) && isset($data['mobile']) && ($data['mobile'] != $member['mobile']))
		{
			unset($data['mobile']);
		}
		load()->model('mc');
		mc_update($this->member['uid'], $data);
	}
	public function checkMemberSNS($sns)
	{
		global $_W;
		global $_GPC;
		if (empty($sns))
		{
			$sns = $_GPC['sns'];
		}
		if (empty($sns))
		{
			return;
		}
		if (($sns == 'wx') && !(empty($_GPC['token'])))
		{
			load()->func('communication');
			$snsurl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $_GPC['token'] . '&openid=' . $_GPC['openid'] . '&lang=zh_CN';
			$userinfo = ihttp_request($snsurl);
			$userinfo = json_decode($userinfo['content'], true);
			$userinfo['openid'] = 'sns_wx_' . $userinfo['openid'];
		}
		else if ($sns == 'qq')
		{
			$userinfo = htmlspecialchars_decode($_GPC['userinfo']);
			$userinfo = json_decode($userinfo, true);
			$userinfo['openid'] = 'sns_qq_' . $_GPC['openid'];
			$userinfo['headimgurl'] = $userinfo['figureurl_qq_2'];
			$userinfo['gender'] = (($userinfo['gender'] == '男' ? 1 : 2));
		}
		$data = array('nickname' => $userinfo['nickname'], 'avatar' => $userinfo['headimgurl'], 'province' => $userinfo['province'], 'city' => $userinfo['city'], 'gender' => $userinfo['sex'], 'comefrom' => 'h5app_sns_' . $sns);
		$openid = trim($_GPC['openid']);
		if ($sns == 'qq')
		{
			$data['openid_qq'] = trim($_GPC['openid']);
			$openid = 'sns_qq_' . trim($_GPC['openid']);
		}
		if ($sns == 'wx')
		{
			$data['openid_wx'] = trim($_GPC['openid']);
			$openid = 'sns_wx_' . trim($_GPC['openid']);
		}
		$member = $this->getMember($openid);
		if (empty($member))
		{
			$data['openid'] = $userinfo['openid'];
			$data['uniacid'] = $_W['uniacid'];
			$data['comefrom'] = 'sns_' . $sns;
			$data['createtime'] = time();
			$data['salt'] = m('account')->getSalt();
			$data['pwd'] = rand(10000, 99999) . $data['salt'];
			pdo_insert('ewei_shop_member', $data);
			return;
		}
		if (empty($member['bindsns']) || ($member['bindsns'] == $sns))
		{
			pdo_update('ewei_shop_member', $data, array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
		}
	}
	public function compareLevel(array $level, array $levels = array())
	{
		global $_W;
		$levels = ((!(empty($levels)) ? $levels : $this->getLevels()));
		$old_key = -1;
		$new_key = -1;
		foreach ($levels as $kk => $vv )
		{
			if ($vv['id'] == $level[0])
			{
				$old_key = $vv['level'];
			}
			if ($vv['id'] == $level[1])
			{
				$new_key = $vv['level'];
			}
		}
		return $old_key < $new_key;
	}
	public function wxuser($appid, $secret, $snsapi = 'snsapi_base', $expired = '600')
	{
		global $_W;
		if ($wxuser = $_COOKIE[$_W['config']['cookie']['pre'] . $appid] === NULL)
		{
			$code = ((isset($_GET['code']) ? $_GET['code'] : ''));
			if (!($code))
			{
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=' . $snsapi . '&state=wxbase#wechat_redirect';
				header('Location: ' . $oauth_url);
				exit();
			}
			load()->func('communication');
			$getOauthAccessToken = ihttp_get('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code');
			$json = json_decode($getOauthAccessToken['content'], true);
			if (!(empty($json['errcode'])) && ($json['errcode'] != '40029'))
			{
				return $json['errmsg'];
			}
			if (!(empty($json['errcode'])) && ($json['errcode'] == '40029'))
			{
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ((strpos($_SERVER['REQUEST_URI'], '?') ? '' : '?'));
				$parse = parse_url($url);
				if (isset($parse['query']))
				{
					parse_str($parse['query'], $params);
					unset($params['code']);
					unset($params['state']);
					$url = 'http://' . $_SERVER['HTTP_HOST'] . $parse['path'] . '?' . http_build_query($params);
				}
				$oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=' . $snsapi . '&state=wxbase#wechat_redirect';
				header('Location: ' . $oauth_url);
				exit();
			}
			if ($snsapi == 'snsapi_userinfo')
			{
				$userinfo = ihttp_get('https://api.weixin.qq.com/sns/userinfo?access_token=' . $json['access_token'] . '&openid=' . $json['openid'] . '&lang=zh_CN');
				$userinfo = $userinfo['content'];
			}
			else if ($snsapi == 'snsapi_base')
			{
				$userinfo = array();
				$userinfo['openid'] = $json['openid'];
			}
			$userinfostr = json_encode($userinfo);
			isetcookie($appid, $userinfostr, $expired);
			return $userinfo;
		}
		return json_decode($wxuser, true);
	}

	public function getrecom($recom,$level=0){
		// $user = User::find('id=?',$recom)->setColumns(array('user.id,user.realname,user.recom_id'))->asArray()->getOne();
		$user = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_member')." WHERE id = :id", array(':id' => $recom));
		if($user){
			$level++;
			$result = array();
			$u['rank']=$level;
			$u['id'] = $user['id'];
			$u['openid'] = $user['openid'];
			$u['realname'] = $user['realname'];
			$u['level'] = $user['level'];
			$u['agentid'] =$user['agentid'];
			$result[]=$u;
			if($level<4){
				$ret = self::getrecom($user['agentid'],$level);
				$result =array_merge($result,$ret);
			}
			return $result;
		}
		else
		{
			return array();
		}
	}
}
?>
