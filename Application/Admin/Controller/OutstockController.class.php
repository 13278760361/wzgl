<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: mr.king (277429358@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-12-5 20:40:00
// +----------------------------------------------------------------------
/**
 * 出库列表 新增 出库申请
 */
namespace Admin\Controller;
use Think\Controller;
class OutstockController extends BaseController
{
	private $o;
	private $mPage = 10;//手机端分页请求每页大小
	private $page  = 1;//列表页分页大小；
	private $applyPage = 10;//申请单物品选择框物品条数
	
	public function _initialize()
	{
       parent::_initialize();
 	   $this->o = D("Outstock");
	   //departs
       //$Depart = new \Org\Util\Category('Depart',array('id','pid','depart_name'));
       //$this->departs = $Depart->getList('',0,'add_time asc');
	   $this->departs = $this->o->getDepartByOrg();
       $this->assign('departs',$this->departs);
	   //分页数量统一
	   $this->mPage = C('wapsize')?C('wapsize'):$this->mPage; 
	   $this->page  = C('pagesize')?C('pagesize'):$this->page; 
	   $this->applyPage = C('seachsize')?C('seachsize'):$this->applyPage; 
	   
	}
	private function getType()
    {
        return M('Type')->select();
    }

    private function getCate()
    {
        $cat=CAT('Category',array('id','pid','cate_name'));
        return $cat->getList(0);
    }
    public function ajaxgetcate()
    {
        $type_id=I('type_id');
        !$type_id&&$this->error('参数错误！');
        $cat=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
        $this->ajaxReturn($cat->getList(0));
    }
	public function index(){
		$this->redirect('lists');
	}
	
	/*
	*出库列表
	*/
	public function lists()
	{
		$condition = $this->listsCondition();
		$list = $this->o->lists($condition,$this->page);
		$p = I('get.p')?I('get.p'):1;
		$p_total = 0;$p_num=0;//分页统计
        $s_total = 0;$s_num=0;//总计

        foreach ($list['list'] as $key => $value) {
        	# code...
        	$p_total+=$value['total'];
        	$p_num+=$value['num'];
        }
        $s_list = $this->o->lists($condition,0);
        foreach ($s_list['list'] as $k => $v) {
        	# code...
        	$s_total+=$v['total'];
        	$s_num+=$v['num'];
        }
       // dump($this->page);exit;
		$this->assign('tdk',array('title'=>"出库查询列表",'description'=>"",'keyword'=>""));
		$this->assign('w',I());
		$this->assign("department","");
		$this->assign('list',$list['list']);
		$this->assign('page',$list['page']);
		$this->assign('p',$this->page*($p-1));//序号算法
		$this->assign("depart",$this->o->getDepartment());
		$this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',$condition['cate_id']);
		$this->assign('p_total',$p_total);
		$this->assign('s_total',$s_total);
		$this->assign('p_num',$p_num);
		$this->assign('s_num',$s_num);
		$this->assign('receipt',I('receipt'));
		$this->display();
	}
	
	/*出库列表条件*/
	private function listsCondition()
	{
		$condition = array();
		$condition['o.status'] = 4;
		$pData = I();
		if($pData){
			if($pData['order_sn'])
			$condition['o.order_sn'] = array('LIKE',"%".$pData['order_sn']."%");
			if($pData['assets_name'])
			$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");
			if($pData['depart'])
			$condition['o.depart'] = array('EQ',$pData['depart']);
			if($pData['str_time']&&$pData['end_time'])
				$condition['o.apply_time'] = array(array('EGT',strtotime($pData['str_time'].' 00:00:00')),
											   array('ELT',strtotime($pData['end_time'].' 23:59:59')));
			else {
				if($pData['str_time']) 
				$condition['o.apply_time'] = array('EGT',strtotime($pData['str_time'].' 00:00:00'));
				if($pData['end_time'])  
				$condition['o.apply_time'] = array('ELT',strtotime($pData['end_time'].' 23:59:59'));
			}
			if($pData['type_id'])
				$condition['t.id'] = array('EQ',$pData['type_id']);
			if($pData['cate_id'])
				$condition['c.id'] = array('EQ',$pData['cate_id']);
			if($pData['receipt'])
				$condition['o.managers'] = array('LIKE',"%".$pData['receipt']."%");
		}
		return $condition;
	}
	
	/*
	*新增出库
	*/
	public function add()
	{
		$admin = session('admin.info');
		if(IS_POST) {
    		$pData = I('post.');
    		
            if ( empty($pData['data']['depart']) ) {
                $this->error('请选择部门');
           }
            if ( empty($pData[1]['assets_name']) ) {  
                 $this->error('请添加物品');
            }
		    if ( empty($pData['data']['contacter']) ) {
                 $this->error('请填写联系人');
            }
		    if ( empty($pData['data']['supplier_phone']) ) {
                 $this->error('请填写联系方式');
            }
		    if ( empty($pData['data']['managers']) ) {
                 $this->error('请填写经办人');
            }
		    if ( empty($pData['data']['managers_phone']) ) {
                 $this->error('请填写经办人联系方式');
            }

            if ($admin['is_supper']) {
				if (!$pData['data']['org_id']) {$this->error('请选择申请单所属部门！');}
			}else{
				$pData['data']['org_id']=$admin['org_id'];
			}
			//$this->startTrans();
			$departs = array();
			if(is_array($pData['data']['depart_id']))
			$departs = $pData['data']['depart_id'];
			else $departs = array($pData['data']['depart_id']);
			foreach ($departs as $k => $v) {
				$info = $this->o->checkAdmin($v);
				if($info) {
					$this->error($info);
				}
	    	}
			//去重复
			$departs = array_unique($departs);
			//数据分离
			//k_outstock：出库单表 + 出库物品表：k_outstock_goods +  出库单审核人表：k_outstock_audit + 消息表k_Message
			//一:多：多：多
			$getPdata = $pData;
			$oData  = $getPdata['data'];//出库单
			unset($getPdata['data']);
			$ogData = $checkStock = $getPdata;//出库商品
			foreach($checkStock as $ckey=>$cvalue){
				if(!$cvalue['goods_id']) continue;
				$checkStockRst = $this->o->checkStock($cvalue['goods_id'],$cvalue['supplier_id'],$cvalue['spec'],$cvalue['num']);
				if($checkStockRst<=0){
					$this->error('库存不足，请核实出库数量或检查库存数量！');
				}
			}
			//1：出库单
			$applyTime = strtotime($oData['apply_time']);
			$oData['apply_time'] = ($applyTime<=0)?time():$applyTime;
			$oData['create_id'] = $admin['id'];
			$oData['create_name'] = $admin['username'];
			$oData['status'] = 1;
			$oData['last_op_time'] = time();
			//$v['creater'] = $admin['username'];	
			$oId =$this->o->addOutStockInfo("o",$oData);
			//入固定资产：卡片临时表
			//表：k_cards_temp
			$ctData['outstock_id'] = $oId;
			//$updOutStockInfo['status'] = 0;
			$ctData['outstock_at'] = $oData['apply_time'];
			$ctId = $this->o->updOutStockInfo('ct',$ctData);
			//多：出库物品表
			$newOgData = array();
			$sdData = array();
			$i=0;
			//$plusTotal = 0;
			foreach($ogData as $k=>$v) 
			{
				unset($v['stocknum']);
				if(!$v['goods_id']) continue;
				$v['outstock_id'] = $oId;
				$newOgData[] = $v;
				//出库：库存以及数量减：不审核
				$sData = array();
				$sData['goods_id'] = $v['goods_id'];
				$sData['supplier_id'] = $v['supplier_id'];
				$sData['spec'] = $v['spec'];
				$sData['num']  = $v['num'];
				//$sData['price'] = $v['price'];
				$sData['total'] = $v['total'];
				$stockId = $this->o->updOutStockInfo("s",$sData);	
				if($stockId){
					//记录库存明细
					$sdData[$i]['stock_id']= $stockId;
					$sdData[$i]['time']= $oData['apply_time'];
					$sdData[$i]['goods_num']= $v['num'];
					$sdData[$i]['func']	= "-";
					$sdData[$i]['price']= $v['price'];
					$sdData[$i]['total']= $v['total'];
					$sdData[$i]['status']= 0;
					$sdData[$i]['order_sn']= $oData['order_sn'];//出库单号
				}
				$i++;
			}  
			$ogId =  $this->o->addOutStockInfo("og",$newOgData);//出库物品
			if($sdData)
			$sdId = $this->o->updOutStockInfo("sd",$sdData) ;//库存明细
			//多：审核表
			$oA = array();$vv = array();
			$mData = array();
			$midI = 0;
			$i = 0;
			foreach($departs as $k=>$v) {
				 $adminInfo = $this->o->getAdmin("is_auditer=1 and depart_id=".$v) ;
				 $vv['outstock_id']= $oId;
				 $vv['audit_id'] = $adminInfo['id'];
				 $vv['audit_name'] = $adminInfo['username'];
				 $vv['sort'] = $k;
					 //多：消息推送
				 if($i==0){
					 if(!$this->sendMsg(
						 $type=40,//40：
						 $title="出库申请审核信息",
						 $apply_id = $oId,
						 $send_id = $admin['id'],
						 $receive_id = $vv['audit_id'],
						 $status = 1,//未读
						 $url = U('Outstock/audit',array('oid'=>$oId))
					 )){
						$this->error("审核信息发送失败！");
					 }
				 }
				 $oA[] = $vv;
			  $i++;
			}
			$aoId = $this->o->addOutStockInfo("oa",$oA);
			if($oId&&$ogId&&$aoId) {
				 //$this->commit();
				 $this->success('添加成功',U('Outstock/lists'));
			}else {
                 //$this->rollback();
                 $this->error('添加失败');
			}
			
		}else {
			$admin=session('admin');
			$order_sn = createOrderSn(C('CKSN')); //生成单号
			$this->assign('order_sn',$order_sn);
			//$this->assign("depart",$this->o->getDepartment());
			$c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
			if ($admin['info']['is_supper']) {
				$topDepart=M('Depart')->field('id,depart_name')->where(array('pid'=>$c_id))->select();
				$this->assign('topDepart',$topDepart);
				
			}
			if (I('org')) {
				$org_id=I('org');
			}else{
				if ($admin['info']['is_supper']) {
					$org_id=0;
				}else{
					$org_id=$admin['info']['org_id'];
				}
			}

			$this->assign('org',$org_id);
			$this->assign('isSupper',$admin['info']['is_supper']);

			$this->display();
		}
	}
	
	/*编辑*/
	public function edit($oid=0){

		$admin = session('admin.info');
		if(IS_POST) {
    		$pData = I('post.');
			//提交：分离物品老数据
			if($pData){
				$oldGoods = array();
				$setPData = array();
				foreach($pData as $pkey=>$dvalue) {
					if(preg_match('/m[0-9]*/is',$pkey)) {
						$oldGoods[] = $dvalue;
					}else {
						$setPData[$pkey] = $dvalue;
					}
				}
				$pData = $setPData;
			}else {
				$this->error('无效！');
			}
            if ( empty($pData['data']['depart']) ) {
                 $this->error('请填写部门');
            }
            if ( empty($pData[1]['assets_name'])&& !$oldGoods) {  
                 $this->error('请添加物品');
            }
		    if ( empty($pData['data']['contacter']) ) {
                 $this->error('请填写联系人');
            }
		    if ( empty($pData['data']['supplier_phone']) ) {
                 $this->error('请填写联系方式');
            }
		    if ( empty($pData['data']['managers']) ) {
                 $this->error('请填写经办人');
            }
		    if ( empty($pData['data']['managers_phone']) ) {
                 $this->error('请填写经办人联系方式');
            }
			//$this->startTrans();
			$departs = array();
			if(is_array($pData['data']['depart_id']))
			$departs = $pData['data']['depart_id'];
			else $departs = array($pData['data']['depart_id']);
			foreach ($departs as $k => $v) {
				$info = $this->o->checkAdmin($v);
				if($info) {
					$this->error($info);
				}
	    	}
			
			//去重复
			$departs = array_unique($departs);
			//新数据分离
			//k_outstock：出库单表 + 出库物品表：k_outstock_goods +  出库单审核人表：k_outstock_audit + 消息表k_Message
			//一:多：多：多
			$getPdata = $pData;
			$oData  = $getPdata['data'];//出库单
			unset($getPdata['data']);
			$ogData = $checkStock = $getPdata;//出库物品
			foreach($checkStock as $ckey=>$cvalue){
				if(!$cvalue['goods_id']) continue;
				$checkStockRst = $this->o->checkStock($cvalue['goods_id'],$cvalue['supplier_id'],$cvalue['spec'],$cvalue['num']);
				if(!$checkStockRst){
					$this->error('库存不足，请核实出库数量或检查库存数量！');
				}
			}
			
			//处理老数据
			//$getOutStockTotal = $this->o->getOStockTotalByOrderSn($pData['data']['order_sn']);//出库统计
			$getAssignOutStockByOrderSn = $this->o->getAssignOutStockByOrderSn($pData['data']['order_sn']);
			$oId= $getAssignOutStockByOrderSn['id'];
			$delOa = $this->o->del("oa",array('outstock_id'=>$oId));//清审核
			$oGoods = $this->o->getOutstockGoodsByOid($oId);
			$delM = $this->o->del("m",array('apply_id'=>$oId));//清消息
			foreach($oGoods as $oGoodskey=>$oGoodsvalue){// 回库
				$this->o->updOutStockInfo("s_plus",
					array(
					'goods_id'=>$oGoodsvalue['goods_id'],
					'supplier_id'=>$oGoodsvalue['supplier_id'],
					'spec'=>$oGoodsvalue['spec']
					)
				);
			}
			$delOg = $this->o->del("og",array('outstock_id'=>$oId));//清物品
			$delSd = $this->o->del("sd",array('order_sn'=>$pData['data']['order_sn'],'func'=>'-'));//清库存明细
			//end
			//1：出库单
			$oData['apply_time'] = strtotime($oData['apply_time'])?strtotime($oData['apply_time']):time();
			$oData['create_id'] = $admin['id'];
			$oData['create_name'] = $admin['username'];
			$oData['status'] = 1;
			$oData['last_op_time'] = time();
			//更新出库单
			$orderSn = $oData['order_sn'];
			unset($oData['order_sn']);
			$updO = $this->o->updOutStockInfo('o',$oData,array('id'=>$oId));
			//多：出库物品表
			$newOgData = array();
			$sdData = array();
			$i=0;
			$ogData = array_merge_recursive($oldGoods,$ogData);
			foreach($ogData as $k=>$v) 
			{
				unset($v['stocknum']);
				if(!$v['goods_id']) continue;
				$v['outstock_id'] = $oId;
				$newOgData[] = $v;
				//出库：库存以及数量减：不审核
				$sData = array();
				$sData['goods_id'] = $v['goods_id'];
				$sData['supplier_id'] = $v['supplier_id'];
				$sData['spec'] = $v['spec'];
				$sData['num']  = $v['num'];
				$sData['total'] = $v['total'];
				$stockId = $this->o->updOutStockInfo("s",$sData);	
				if($stockId){
					//记录库存明细
					$sdData[$i]['stock_id']= $stockId;
					$sdData[$i]['time']= $oData['apply_time'];
					$sdData[$i]['goods_num']= $v['num'];
					$sdData[$i]['func']	= "-";
					$sdData[$i]['price']= $v['price'];
					$sdData[$i]['total']= $v['total'];
					$sdData[$i]['status']= 0;
					$sdData[$i]['order_sn']= $orderSn;//出库单号
				}
				$i++;
			}  
			$ogId =  $this->o->addOutStockInfo("og",$newOgData);//出库物品
			if($sdData)
			$sdId = $this->o->updOutStockInfo("sd",$sdData) ;//库存明细
			//多：审核表
			$oA = array();$vv = array();
			$mData = array();
			$midI = 0;
			$i = 0;
			foreach($departs as $k=>$v) {
				 $adminInfo = $this->o->getAdmin("is_auditer=1 and depart_id=".$v) ;
				 $vv['outstock_id']= $oId;
				 $vv['audit_id'] = $adminInfo['id'];
				 $vv['audit_name'] = $adminInfo['username'];
				 $vv['sort'] = $k;
				 //多：消息推送
				 if($i==0){
					 if(!$this->sendMsg(
						 $type=40,//40：
						 $title="出库申请审核信息",
						 $apply_id = $oId,
						 $send_id = $admin['id'],
						 $receive_id = $vv['audit_id'],
						 $status = 1,//未读
						 $url = U('Outstock/audit',array('oid'=>$oId))
					 )){
						$this->error("审核信息发送失败！");
					 }
				 }
				 $i++;
				 $oA[] = $vv;
			}
			$aoId = $this->o->addOutStockInfo("oa",$oA);
			if($updO&&$ogId&&$aoId) {
				 //$this->commit();
				 $this->success('更新成功',U('Outstock/lists'));
			}else {
                 //$this->rollback();
                 $this->error('更新失败');
			}
			
		}else {
			$oRst = array();
			if($oid)
			$oRst = $this->o->getAssignOutStock($oid,$this->departs);	
			$isAuditer = $this->o->is_auditer($oid);	
			$this->assign('data',$oRst[0]);//出库申请单
			$this->assign('Cstatus',$oRst[0]['status']);//当前审核单状态
			$this->assign('gdata',$oRst[1]);//物品数据
			$this->assign('gnum',count($oRst[1]));//物品数据总和
			$this->assign('adata',$oRst[2]);//审核人
			$this->assign('cControl',$isAuditer['cControl']);//当前管理员权限
			$this->assign('admin_id',$isAuditer['cAdmin']);//当前管理员
			//$this->assign("depart",$this->o->getDepartment());
			$this->display();	
		}
	}
	
	/*审核列表*/
	public function auditlist()
	{
		$condition = array();
		$pData = I();
		$status = 0;
		$tabParam = array();
		$tabParamAll = array();
		if($pData){
			if($pData['order_sn'])
			$condition['order_sn'] = array('LIKE',"%".$pData['order_sn']."%");
			if($pData['depart'])
			$condition['depart'] = array('EQ',$pData['depart']);
			if($pData['apply_time'])
				$condition['apply_time'] = array('EGT',strtotime($pData['apply_time'].' 00:00:00'));
			if($pData['create_name']) {
				$getAdmin = $this->o->getAdmin("name='".$pData['create_name']."'");
				if(!$getAdmin)
				$condition['create_id'] = "-1";
				else
				$condition['create_id']  = $getAdmin['id'];
				
			}
			if($pData['status']) {
				$status = $pData['status'];
				if($status==3) $condition['status'] = array('in','3,4');
				else if($status==4) $condition['status'] = array('in','0');
				else $condition['status'] = $pData['status'];
			}
			$condition['query'] = array('status'=>$pData['status'],'order_sn'=>$pData['order_sn'],'depart'=>$pData['depart'],'apply_time'=>$pData['apply_time'],'create_name'=>$pData['create_name']);
			$tabParam = array(
				'order_sn'=>$pData['order_sn'],
				'depart'=>$pData['depart'],
				'apply_time'=>$pData['apply_time'],
				'create_name'=>$pData['create_name']
			);	
			
		}
		for($i=1;$i<5;$i++) {
			//$tabParamAll[$i] = array_merge($tabParam,array('status'=>($i==4?0:$i)));
			$tabParamAll[$i] = array('status'=>($i==4?0:$i));
		}
		$outstockCheckList = $this->o->outstockCheckList($condition,$this->page);
		$p = I('get.p')?I('get.p'):1;
		$this->assign('list',$outstockCheckList['list']);
		$this->assign('page',$outstockCheckList['page']);
		$this->assign('p',$this->page*($p-1));//序号算法
		$this->assign('status',$status);
		$this->assign('tabParamAll',$tabParamAll);
		$this->display();	
	}
	//审核 Admin/Outstock/audit/id/1
	public function audit($oid=0)
	{
		if(!$oid) $this->redirect('auditList');
		if(IS_POST) {
    		$pData = I('post.');
			$remark = $pData['remark'];
			$status = $pData['agreeyorn'];//0、审核中（默认） 1：不通过 2：通过
			$time = $pData['time'];
			//echo $status;exit();
			$time = $time?strtotime($time):time();
			$admin = session('admin.info');
			//更新审核人员表
			$oa = $this->o->updOutStockInfo('oa',array('status'=>$status,'remark'=>$remark,"time"=>$time),array('outstock_id'=>$oid,'audit_id'=>$admin['id']));
			//核实申请表状态
			$auditStatus = $this->o->auditStatus($oid);
			$o = $this->o->updOutStockInfo('o',array('status'=>$auditStatus,"last_op_time"=>time()),array('id'=>$oid));
			//$auditStatus:4：审核通过后 库存表做减少操作：取消
			if($oa&&$o){
				$getAssignOutStock = $this->o->getAssignOutStock($oid);
				$getCreateId = $getAssignOutStock[0]['create_id'];
				//下一位审核人
				switch($auditStatus) {
					case 2://审核中：下一位
						$nextAuditer = $this->o->nextAuditer($admin['id'],$oid);
						if($nextAuditer) {
							$title = "出库申请审核信息";
							$send_id = $admin['id'];
							$receive_id = $nextAuditer;		
						}else {
							// $title = "出库申请审核信息";
							// $send_id = $admin['id'];
							// $receive_id = $getCreateId;		
						}
					break;
					case 3://不通过：告知申请人
						$title = "出库申请审核未通过信息";
						$send_id = $admin['id'];
						$receive_id = $getCreateId;
						break;
					case 4://全通过：
						$title = "出库申请审核通过信息";
						$send_id = $admin['id'];
						$receive_id = $getCreateId;	
					break;
				}
				//更新消息：已读
				$this->o->updOutStockInfo(
					'new',
					array('status'=>2),
					array(
						'apply_id'=>$oid,
						'receive_id'=>$admin['id'],
						'type'=>'40'
					)
				);
				//发消息
				 if(!$this->sendMsg(
					 $type=40,//40：
					 $title=$title,
					 $apply_id = $oid,
					 $send_id = $send_id,
					 $receive_id = $receive_id,
					 $status = 1,//未读
					 $url = U('Outstock/audit',array('oid'=>$oid))
				 )){
					$this->error("审核信息发送失败！");
				 }
				
				$this->success("操作成功!",U('Outstock/auditlist'));
			}
			else $this->error("操作失败！");
			//核实是否通知下一位审核人员
		}else {
			$admin=session('admin.info');
			//查看时处理消息状态
			if ($oid) {
				$order=$this->o->field('status, create_id')->where(array('id'=>$oid))->find();
				if (($order['status']==3||$order['status']==4)&&$admin['id']=$order['create_id']) {
					M('Message')->where(array('type'=>40,'receive_id'=>$admin['id'],'apply_id'=>$oid))->save(array('status'=>2));
				}
			}
			$oRst = array();
			if($oid)
			$oRst = $this->o->getAssignOutStock($oid,$this->departs);
			if ($oRst[0]) {
				$is_c=M("Cancel")->where(array('order_sn'=>$oRst[0]['order_sn']))->find();
				$oRst[0]['is_scrap']=$is_c?true:false;
			}
			$isAuditer = $this->o->is_auditer($oid);	
			$this->assign('data',$oRst[0]);//出库申请单
			$this->assign('Cstatus',$oRst[0]['status']);//当前审核单状态
			$this->assign('gdata',$oRst[1]);//物品数据
			$this->assign('gnum',count($oRst[1]));//物品数据总和
			$this->assign('adata',$oRst[2]);//审核人
			$this->assign('cControl',$isAuditer['cControl']);//当前管理员权限
			$this->assign('admin_id',$isAuditer['cAdmin']);//当前管理员
			//$this->assign("depart",$this->o->getDepartment());
			$this->display();	
		}
	}
	/*出库报废：生成报废单*/
	public function cancel($order_sn){
		 $cancel_sn = createOrderSn(C('BFSN')); 
		 $getCancelByOrderSn = $this->o->getCancel(array('order_sn'=>$order_sn));
		 $getAssignOutStockByOrderSn = $this->o->getAssignOutStockByOrderSn($order_sn);
		 if($getCancelByOrderSn) $this->error("已报废处理中，不可重复操作！",U('Outstock/cancelinfo',array('cid'=>$getCancelByOrderSn['id'])));
		 $data = array();
		 if($order_sn){
			 $data['cancel_sn'] = $cancel_sn;
			 $data['order_sn']	= $getAssignOutStockByOrderSn['order_sn'];
			 $data['applyer_id']= $getAssignOutStockByOrderSn['create_id'];
			 $data['applyer']	= $getAssignOutStockByOrderSn['create_name'];
			 $data['apply_time']= time();
			 $data['cancel_type']= "-";
			 $data['status'] = 1;
			 $cid = $this->o->addOutStockInfo('c',$data);
			 if($cid) $this->success("添加报废单成功！",U('Outstock/cancellist'));
			 else $this->error("添加报废单失败！",U('Outstock/audit',array('oid'=>$getAssignOutStockByOrderSn['id'])));
		 }
	}
	/*出库报废：报废单详情*/
	public function cancelinfo($cid=0){
		$admin = session('admin.info');
		if(IS_POST){
			$pData = I();
			$cid = $pData['cid'];
			if(is_array($pData['data']['depart_id']))
			$departs = $pData['data']['depart_id'];
			else $departs = array($pData['data']['depart_id']);
			foreach ($departs as $k => $v) {
				$info = $this->o->checkAdmin($v);
				if($info) {
					$this->error($info);
				}
	    	}
			$departs = array_unique($departs);
			$i = 0;
			foreach($departs as $k=>$v) {
				 $adminInfo = $this->o->getAdmin("is_auditer=1 and depart_id=".$v) ;
				 $vv['id']= $cid;
				 $vv['audit_id'] = $adminInfo['id'];
				 $vv['audit_name'] = $adminInfo['username'];
				 $vv['sort'] = $k;
				 //多：消息推送				 
				 if($i==0){
					 if(!$this->sendMsg(
						 $type=100,//40：
						 $title="出库报废申请审核信息",
						 $apply_id = $cid,
						 $send_id = $admin['id'],
						 $receive_id = $vv['audit_id'],
						 $status = 1,//未读
						 $url = U('Outstock/cancelinfo',array('cid'=>$cid))
					 )){
						$this->error("审核信息发送失败！");
					 }
				 }
				 $i++;
				 $cA[] = $vv;
			}
			$ocId = $this->o->addOutStockInfo("ca",$cA);
			if($ocId) $this->success("新增成功！",U('Outstock/cancelinfo',array('cid'=>$cid)));
			else  $this->success("新增失败！",U('Outstock/cancelinfo',array('cid'=>$cid)));			
		}else {
			if(!$cid)$this->error("无效！");
			$getCancel = $this->o->getCancel(array('id'=>$cid));
			$getCancelAudit = $this->o->getCancelAudit(array('id'=>$getCancel['id']));
			$getAssignOutStockByOrderSn = $this->o->getAssignOutStockByOrderSn($getCancel['order_sn']);
			$oid = $getAssignOutStockByOrderSn['id'];
			$oRst = $this->o->getAssignOutStock($oid,$this->departs);
			$this->assign('data',$oRst[0]);//出库申请单
			$this->assign('Cstatus',$oRst[0]['status']);//当前审核单状态
			$this->assign('gdata',$oRst[1]);//物品数据
			$this->assign('gnum',count($oRst[1]));//物品数据总和
			$adata = $this->o->cancel_auditer($cid,$this->departs);
			$this->assign('adata',$adata);//报废审核人
			$isAuditer = $this->o->cancel_is_auditer($cid);
			$this->assign('cControl',$isAuditer['cControl']);//当前管理员权限
			$this->assign('admin_id',$isAuditer['cAdmin']);//当前管理员
			$this->assign('cid',$cid);//当前管理员	
			$this->display();
		}
	}
	
	/*出库报废：报废单详情*/
	public function cancellist(){
		$condition = array();
		$pData = I();
		$status = 0;
		$tabParam = array();
		$tabParamAll = array();
		if($pData){
			if($pData['order_sn'])
			$condition['c.cancel_sn'] = array('LIKE',"%".$pData['order_sn']."%");
			if($pData['depart'])
			$condition['o.depart'] = array('EQ',$pData['depart']);
			if($pData['apply_time'])
				$condition['c.apply_time'] = array('EGT',strtotime($pData['apply_time'].' 00:00:00'));
			if($pData['applyer']) {
				$getAdmin = $this->o->getAdmin("name='".$pData['applyer']."'");
				if(!$getAdmin)
					$condition['c.applyer_id'] = "-1";
				else
					$condition['c.applyer_id']  = $getAdmin['id'];
				
			}
			if($pData['status']) {
				$status = $pData['status'];
				if($status==3) $condition['c.status'] = array('in','3,4');
				else if($status==4) $condition['c.status'] = array('in','0');
				else $condition['c.status'] = $pData['status'];
			}
			$condition['query'] = array('status'=>$pData['status'],'order_sn'=>$pData['order_sn'],'depart'=>$pData['depart'],'apply_time'=>$pData['apply_time'],'applyer'=>$pData['applyer']);
			$tabParam = array(
				'order_sn'=>$pData['order_sn'],
				'depart'=>$pData['depart'],
				'apply_time'=>$pData['apply_time'],
				'applyer'=>$pData['applyer']
			);	
			
		}
		for($i=1;$i<5;$i++) {
			//$tabParamAll[$i] = array_merge($tabParam,array('status'=>($i==4?0:$i)));
			$tabParamAll[$i] = array('status'=>($i==4?0:$i));
		}
		$outstockCheckList = $this->o->outstockCancelCheckList($condition,$this->page);
		$p = I('get.p')?I('get.p'):1;
		$this->assign('w',I());
		$this->assign('list',$outstockCheckList['list']);
		$this->assign('page',$outstockCheckList['page']);
		$this->assign('p',$this->page*($p-1));//序号算法
		$this->assign('status',$status);
		$this->assign('tabParamAll',$tabParamAll);
		$this->display();	
	}
	
	/*出库报废审核*/
	public function cancelaudit($cid=0){
		if(IS_POST) {
			if(!$cid) $this->error("无效操作！");
    		$pData = I('post.');
			$remark = $pData['remark'];
			$status = $pData['agreeyorn'];//0、审核中（默认） 1：不通过 2：通过
			$time = time();
			$admin = session('admin.info');
			//更新审核人员表
			$oa = $this->o->updOutStockInfo('ca',array('status'=>$status,'remark'=>$remark,"time"=>$time),array('id'=>$cid,'audit_id'=>$admin['id']));
			//核实申请表状态
			$auditStatus = $this->o->cancelAuditStatus($cid);
			$o = $this->o->updOutStockInfo('c',array('status'=>$auditStatus),array('id'=>$cid));
			//$auditStatus:4：审核通过后 库存表做减少操作：取消
			if($oa&&$o){
				/*回库开始*/
				$getCancel = $this->o->getCancel(array('cid'=>$cid));
				$getCreateId = $getCancel['applyer_id'];
				//下一位审核人
				switch($getCancel['status']) {
					case 2://审核中：下一位
						$nextAuditer = $this->o->nextCancelAuditer($admin['id'],$cid);
						if($nextAuditer) {
							$title = "出库报废申请审核信息";
							$send_id = $admin['id'];
							$receive_id = $nextAuditer;		
						}else {
							$title = "出库报废申请审核信息";
							$send_id = $admin['id'];
							$receive_id = $getCreateId;		
						}
					break;
					case 3://不通过：告知申请人
					case 4://全通过：
					  if($getCancel['status']==4){//通过-报废-回库
							$orderSn = $getCancel['order_sn'];
							$getAssignOutStockByOrderSn = $this->o->getAssignOutStockByOrderSn($orderSn);
							$oId= $getAssignOutStockByOrderSn['id'];
							$oGoods = $this->o->getOutstockGoodsByOid($oId);
							foreach($oGoods as $oGoodskey=>$oGoodsvalue){// 回库
								$updOutStockInfo =
								$this->o->updOutStockInfo("s_plus",
									array(
									'goods_id'=>$oGoodsvalue['goods_id'],
									'supplier_id'=>$oGoodsvalue['supplier_id'],
									'spec'=>$oGoodsvalue['spec']
									)
								);
								if(!$updOutStockInfo) $this->error("物品回库失败！");
							}
							$delSd = $this->o->del("sd",array('order_sn'=>$orderSn,'func'=>'-'));//清库存明细
							if(!$delSd) $this->error("库存明细清除失败！",U('Outstock/cancelinfo',array('cid'=>$cid)));
					  
					  }
					 $title = "出库报废申请审核信息";
					 $send_id = $admin['id'];
					 $receive_id = $getCreateId;	
					break;
				}
				//更新消息：已读
				$this->o->updOutStockInfo(
					'new',
					array('status'=>2),
					array(
						'apply_id'=>$cid,
						'receive_id'=>$admin['id'],
						'type'=>100
					)
				);
				//发消息
				 if(!$this->sendMsg(
					 $type=100,
					 $title=$title,
					 $apply_id = $cid,
					 $send_id = $send_id,
					 $receive_id = $receive_id,
					 $status = 1,//未读
					 $url = U('Outstock/cancelinfo',array('cid'=>$cid))
				 )){
					$this->error("审核信息发送失败！");
				 }
				$this->success("操作成功!",U('Outstock/cancellist'));
			}
			else $this->error("操作失败！");
			//核实是否通知下一位审核人
		}else $this->error("");
	}
	
	/*导出出库报表*/
	public function export()
	{
		$condition = $this->listsCondition();
		$list = $this->o->lists($condition);
		$getData = $list['list'];
		$name = "出库报表";
		$head = array();
		$field = array(
			'order_sn'=>"出库单号",
			'depart'=>"部门",
			'goodsname'=>"物品名称",
			'spec'=>"规格型号",
			"unit"=>"单位",
			'price'=>"单价",
			'num'=>"数量",
			'total'=>'金额',
			'apply_time'=>'日期',
			'catname'=>'归类',
			'managers'=>'经办人'
		);
		foreach($field as $kk=>$vv){
			$head[] = $vv;
		}
		$data = array();
		foreach($getData as $k=>$v) {
			$one = array();
			foreach($field as $kk=>$vv){
				if($kk=='apply_time')
				$one[] = date("Y-m-d h:i:s",$v[$kk]);
				else $one[] = $v[$kk];
			}
			$data[]= $one;
		}
		
		Exporter($name,$data,$head);
	}
	
	/*柱状图*/
	public function bar() {
		$condition = array();
		$pData = I();
		$title = "";
		if($pData){
			if($pData['assets_name']){
				$title = $pData['assets_name'];
				$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");
			}
			if($pData['str_time']&&$pData['end_time'])
				$condition['o.apply_time'] = array(array('EGT',strtotime($pData['str_time'].' 00:00:00')),
											   array('ELT',strtotime($pData['end_time'].' 23:59:59')));
			else {
				if($pData['str_time']) 
				$condition['o.apply_time'] = array('EGT',strtotime($pData['str_time'].' 00:00:00'));
				if($pData['end_time'])  
				$condition['o.apply_time'] = array('ELT',strtotime($pData['end_time'].' 23:59:59'));
			}
			if($pData['org_id'])
				$condition['o.org_id'] = array('EQ',$pData['org_id']);
		}
		
		$list = $this->o->barPie($condition);
		$org_id=$pData['org_id']?:0;
        $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
        $cate =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
        $top_departs = $cate->getList($c_id);
        $this->assign('top_departs',$top_departs);
        $this->assign('org',$org_id);
		$this->assign('title',$title);
		$this->assign('list',$list['list']);
		$this->assign('goods',$list['goods']);
		$this->assign('is_supper',$_SESSION['admin']['info']['is_supper']);
		$this->display();
	}
	
	/*饼状图*/
	public function pie() {
		$condition = array();
		$pData = I();
		$title = "";
		if($pData){
			if($pData['assets_name']){
				$title = $pData['assets_name'];
				$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");
			}
			if($pData['str_time']&&$pData['end_time'])
				$condition['o.apply_time'] = array(array('EGT',strtotime($pData['str_time'].' 00:00:00')),
											   array('ELT',strtotime($pData['end_time'].' 23:59:59')));
			else {
				if($pData['str_time']) 
				$condition['o.apply_time'] = array('EGT',strtotime($pData['str_time'].' 00:00:00'));
				if($pData['end_time'])  
				$condition['o.apply_time'] = array('ELT',strtotime($pData['end_time'].' 23:59:59'));
			}
		}
		
		$list = $this->o->barPie($condition);
		$this->assign('title',$title);
		$this->assign('list',$list['list']);
		$this->assign('goods',$list['goods']);
		$this->display();
	}
	
	
	/*获取物品列表*/
	public function get_goods() {
		$name = I('post.value');
		$this->ajaxReturn($this->o->goods($name));
	}

	
	/*
	*
	*/
	public function check()
	{
		$this->display();	
	}
	/*发送短消息*/
	private function sendMsg($type,$title,$apply_id,$send_id,$receive_id,$status,$url){
		//出库：40 出库报废：100
		 //多：消息推送
		 $mData['type']  = $type;
		 $mData['title'] = $title;
		 $mData['apply_id'] = $apply_id;
		 $mData['send_id']  = $send_id;
		 $mData['receive_id'] = $receive_id;
		 $mData['status'] = $status;
		 $mData['url'] = $url;
		 $mData['send_time'] = time();
		 return $this->o->addOutStockInfo("m",$mData);
	}
}
 
 
 ?>
