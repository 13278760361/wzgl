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
 * 出库列表 新增 出库申请 模型
 */
namespace Admin\Model;
use Think\Model;

class OutstockModel extends Model{
	//protected $tableName = "";//核心表
	//出库列表
	public function lists($condition=array(),$per=10) {
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["o.org_id"]=$orgId;//加组
		$count = M('outstock_goods')->alias("og")
				->join("k_outstock o on o.id = og.outstock_id")
				->join("k_base_goods g on g.id = og.goods_id",'left')
				->join("k_category c on c.id = og.goods_id",'left')
				->join("k_type t on g.type_id=t.id")
				//->join("k_category cg on g.cate_id=cg.id")
				->where($condition)
				->count();
	
		$Page = new \Think\PageA($count,$per); 
		$list = M('outstock_goods')->alias("og")
				->field("o.*,g.assets_name as goodsname,g.unit as unit,og.spec as spec,og.num as num ,og.price as price,og.total as total,c.cate_name as catname")
				->join("k_outstock o on o.id = og.outstock_id")
				->join("k_base_goods g on g.id = og.goods_id",'left')
				->join("k_category c on c.id = og.goods_id",'left')
				->join("k_type t on g.type_id=t.id")
				->where($condition)
				->limit($Page->firstRow.','.$Page->listRows)
				->order("o.id DESC")
				->select();
		if($list) {
			$newList = array();
			foreach($list as $k=>$v) {
				//部门
				$department = "";
				if($v['depart']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['depart']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['depart'] = $department;//部门
				$getStock = $this->getStock($v['goods_id'],$v['supplier_id'],$v['spec']);
				$v['stocknum'] = $getStock;
				$newList[$k] = $v;
			}
			$list = $newList;
		}

		//分页参数
		if($query) {
			foreach($query as $key=>$val) {
				$Page->parameter[$key] = urlencode($val);
			}
		}
		//print_r($this->linkStock(1));exit;
		$page = $Page->show();
		$rst = array('page'=>$page,'list'=>$list);
		return $rst;
		
	}
	
	//出库审核列表
	public function outstockCheckList($condition=array(),$per=10)
	{
		
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1)  $condition["org_id"]=$orgId;//加组
		$count = $this->where($condition)->count();
		$Page = new \Think\PageA($count,$per); 
		// status：1未审核  2审核中 3未通过  4通过
		$list = $this->field("id,depart,order_sn,status,apply_time,last_op_time,create_id")
				->where($condition)
				->limit($Page->firstRow.','.$Page->listRows)
				->order("id DESC")
				->select();
		if($list) {
			$newList = array();
			foreach($list as $k=>$v) {
				if($v['create_id']) {
					$getAdmin = $this->getAdmin("id=".$v['create_id']);
					$name = $getAdmin['name'];
				}
				else {
					$name = "";
				}
				$v['create_name'] = $name;
				//部门
				$department = "";
				if($v['depart']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['depart']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['depart'] = $department;//部门
				$newList[$k] = $v;
			}
			$list = $newList;
		}

		//分页参数
		if($query) {
			foreach($query as $key=>$val) {
				$Page->parameter[$key] = urlencode($val);
			}
		}
		//print_r($this->linkStock(1));exit;
		$page = $Page->show();
		$rst = array('page'=>$page,'list'=>$list);
		return $rst;
		
	}
	
	//出库报废审核列表
	public function outstockCancelCheckList($condition=array(),$per=10)
	{
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["o.org_id"]=$orgId;//加组
		$count = M("cancel")->alias("c")
				->where($condition)
				->join("k_outstock o on c.order_sn = o.order_sn",'left')
				->count();
		$Page = new \Think\PageA($count,$per); 
		// status：1未审核  2审核中 3未通过  4通过
		$list = M("cancel")->alias("c")
				->where($condition)
				->field("c.*,o.depart")
				->join("k_outstock o on c.order_sn = o.order_sn",'left')
				->limit($Page->firstRow.','.$Page->listRows)
				->order("id DESC")
				->select();
		if($list) {
			$newList = array();
			foreach($list as $k=>$v) {
				if($v['applyer_id']) {
					$getAdmin = $this->getAdmin("id=".$v['applyer_id']);
					$name = $getAdmin['name'];
				}
				else {
					$name = "";
				}
				$v['applyer'] = $name;
				//部门
				$department = "";
				if($v['depart']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['depart']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['depart'] = $department;//部门
				$newList[$k] = $v;
			}
			$list = $newList;
		}

		//分页参数
		if($query) {
			foreach($query as $key=>$val) {
				$Page->parameter[$key] = urlencode($val);
			}
		}
		//print_r($this->linkStock(1));exit;
		$page = $Page->show();
		$rst = array('page'=>$page,'list'=>$list);
		return $rst;
		
	}
	
	/*获取指定出库单数据：出库单号*/
	public function getAssignOutStockByOrderSn($orderSn) {
		$condition = array();
		$condition['order_sn'] = $orderSn;
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition['org_id'] = $orgId;
		$outstock = $this->where($condition)->find();
		return $outstock;
	}
	//获取指定出库相关审核数据
	public function getAssignOutStock($oid,$departs=array()) {
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		$olistCondition = array('id'=>$oid);
		$glistCondition = array('outstock_id'=>$oid);
		if($orgId&&$isSupper!=1){
			$olistCondition["org_id"]=$orgId;//加组
			$glistCondition["o.org_id"]=$orgId;//加组
		}
		
		$olist = 
			$this
			->field("id,create_id,status,depart,contacter,supplier_phone,order_sn,total_price,remark,managers,managers_phone,apply_time,is_scrap")
			->where($olistCondition)
			->find();
		if($olist) {
			$newList = array();
			foreach($olist as $k=>$v) {
				//部门
				if($k=='depart') {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v));
					$newList['depart_name'] = $getAssignDepartment['depart_name'];
					$newList['depart'] = $v;
				}else
				$newList[$k] = $v;
			}
			$olist = $newList;
		}
		$glist = 
			M('outstock_goods')->alias("og")
			->field("g.id as goods_id,og.remark as singleremark,og.supplier_id as supplier_id,g.assets_name as goodsname,g.unit as unit,og.spec as spec,og.num as num ,og.price as price,og.total as total,c.cate_name as catname,og.remark as remark")
			->join("k_outstock o on o.id = og.outstock_id",'left')
			->join("k_base_goods g on g.id = og.goods_id",'left')
			->join("k_category c on c.id = og.goods_id",'left')
			->where($glistCondition)
			->group('goods_id,spec')
			->select();
		if($glist) {
			$newGlist = array();
			foreach($glist as $gkey=>$gvalue) {
				$getStock = $this->getStock($gvalue['goods_id'],$gvalue['supplier_id'],$gvalue['spec']);
				$gvalue['stocknum'] = $getStock;
				$newGlist[$gkey] = $gvalue;
			}
			$glist = $newGlist;
		}
		//考虑审核
		$control = $this->auditer($oid,$departs);
		$lists = array($olist,$glist,$control);
		return $lists;
	}
	
	//核实部门审核人员是否存在
	public function checkAdmin($v) 
	{
		$admin = M('admin')->where(array('depart_id'=>$v,'is_auditer'=>1))->find();
		$depart_name = "";
		if(!$admin){
			$depart = M('depart')->where(array('id'=>$v))->find();
			$depart_name = $depart['depart_name']."没审核人员！请重新选择!";
		}
		return $depart_name;
	}
	
	//获取所有审核人员并给予审核权限
	public function auditer($outstockId,$departs) 
	{
		 $condition = array("oa.outstock_id"=>$outstockId);//加组条件
		 $orgId = $this->findAssignAdminInfo("org_id");
		 $isSupper = $this->findAssignAdminInfo("is_supper");
		 if($orgId&&$isSupper!=1) $condition["o.org_id"]=$orgId;//加组
		 $audit =
		 M("outstock_audit")->alias("oa")
		 ->field("o.depart as depart_name,oa.audit_id,oa.status,o.status as ostatus,a.depart_id,oa.remark,oa.sort,oa.time")
		 ->where($condition)
		 ->join("k_outstock o on o.id = oa.outstock_id")
		 ->join("k_admin a on a.id = oa.audit_id")
		 ->order("oa.sort asc")
		 ->select();
		 //outstock_audit：status ： 0审核中 1未通过  2通过
		 $newAudit = array();
		 foreach($audit as $k=>$v) {
		 	//操作权限
			//上一级审核员权限 control 1：不可操作 2：可操作
			if($v['ostatus']==4)$control = 1;//申请表通过审核将不再允许任何人操作
			else {
				if($v['sort']>0) {
					$preAudit = $audit[$v['sort']-1]['status'];
					$control = ($preAudit==2&&$v['status']==0)?2:1;//上一级审核通过且审核状态下才可审核
				}else {
					$control = ($v['status']==0)?2:1;
				}
			}
			$v['control'] = $control;
			$v['statusNote'] = ($v['status']==0)?"未审核":($v['status']==1?"未通过":($v['status']==2?"通过":"审核中"));
			$v['depart'] = $departs;
			$getDepartment = $this->getAssignDepartByAdmin(array("a.id"=>$v['audit_id']));
			$v['depart_name'] = $getDepartment['depart_name'];
			$v['depart_id'] = $getDepartment['depart_id']; 
			$getAdmin = $this->getAdmin(array('id'=>$v['audit_id'])) ;
			if(!$getAdmin) {
				$name = "";
				$signature = "";
			}
			else{
				 $name = $getAdmin['name'];
				 $signature = $getAdmin['signature'];
			}
			$v['name'] = $name;
			$v['signature'] = $signature;
			$newAudit[] = $v;
		 }
		 return $newAudit;
	}
	//获取下一位审核人
	public function nextAuditer($audit_id,$oid){
		//$auditNum = M("outstock_audit")->where("outstock_id=".$oid)->count();
		$auditInfo= M("outstock_audit")->where("audit_id=".$audit_id." && outstock_id=".$oid)->find();
		$sort = $auditInfo['sort'];
		$nextAudit = M("outstock_audit")->field("audit_id")->where(array('sort'=>$sort+1,'outstock_id'=>$oid))->find();
		if($nextAudit) return $nextAudit['audit_id'];
		else return 0;
	}

	//获取下一位报废审核人
	public function nextCancelAuditer($audit_id,$cid){
		$auditInfo= M("cancel_audit")->where("audit_id=".$audit_id." && cancel_id=".$cid)->find();
		$sort = $auditInfo['sort'];
		$nextCancelAuditer = M("cancel_audit")->field("audit_id")->where(array('sort'=>$sort+1,'cancel_id'=>$cid))->find();
		if($nextCancelAuditer) return $nextCancelAuditer['audit_id'];
		else return 0;
	}
	
	//出库报废：获取所有审核人员并给予审核权限
	public function cancel_auditer($cid,$departs) 
	{
		 $condition = array("ca.cancel_id"=>$cid);
		 $orgId = $this->findAssignAdminInfo("org_id");
		 $isSupper = $this->findAssignAdminInfo("is_supper");
		 if($orgId&&$isSupper!=1) $condition["o.org_id"]=$orgId;//加组
		 $audit =
		 M("cancel_audit")->alias("ca")
		 ->field("o.depart as depart_name,ca.audit_id,ca.audit_name,ca.status,c.status as cstatus,a.depart_id,ca.remark,ca.sort,ca.time")
		 ->where($condition)
		 ->join("k_cancel c on c.id=ca.cancel_id")
		 ->join("k_outstock o on o.order_sn = c.order_sn")
		 ->join("k_admin a on a.id = ca.audit_id")
		 ->order("ca.sort asc")
		 ->select();
		if(!$audit) return array();
		 //cancel_audit：status ： 0审核中 1未通过  2通过
		 $newAudit = array();
		 foreach($audit as $k=>$v) {
		 	//操作权限
			//上一级审核员权限 control 1：不可操作 2：可操作
			if($v['ostatus']==4)$control = 1;//申请表通过审核将不再允许任何人操作
			else {
				if($v['sort']>0) {
					$preAudit = $audit[$v['sort']-1]['status'];
					$control = ($preAudit==2&&$v['status']==0)?2:1;//上一级审核通过且审核状态下才可审核
				}else {
					$control = ($v['status']==0)?2:1;
				}
			}
			$v['control'] = $control;
			$v['statusNote'] = ($v['status']==0)?"未审核":($v['status']==1?"未通过":($v['status']==2?"通过":"审核中"));
			$v['depart'] = $departs;
			$getDepartment = $this->getAssignDepartByAdmin(array("a.id"=>$v['audit_id']));
			$v['depart_name'] = $getDepartment['depart_name'];
			$v['depart_id'] = $getDepartment['depart_id']; 
			//$getAdmin = $this->getAdmin(array('id'=>$v['audit_id'])) ;
			//if(!$getAdmin) $name = "";else $name = $getAdmin['name'];
			$v['name'] = $v['audit_name'];
			$newAudit[] = $v;
		 }
		 return $newAudit;	
	}
	
	/*获取某个出库申请的审核状态*/
	public function auditStatus($outstockId) {
		$auditStatus = M("outstock_audit")->where("outstock_id=".$outstockId)->select();
		$noPass = 0;$pass = 0;
		foreach($auditStatus as $k=>$v) {
			if($v['status']==1) $noPass++;
			if($v['status']==2) $pass++;
		}
		if($noPass) return 3;//出库申请单状态更新为：3 不通过
		if($pass==count($auditStatus)) return 4;//出库申请单状态更新为：4 通过
		else return 2;//出库申请单状态更新为：2 审核中
	}
	
	/*获取某个出库报废的审核状态*/
	public function cancelAuditStatus($cid) {
		$auditStatus = M("cancel_audit")->where("cancel_id=".$cid)->select();
		$noPass = 0;$pass = 0;
		foreach($auditStatus as $k=>$v) {
			if($v['status']==1) $noPass++;
			if($v['status']==2) $pass++;
		}
		if($noPass) return 3;//出库申请单状态更新为：3 不通过
		if($pass==count($auditStatus)) return 4;//出库申请单状态更新为：4 通过
		else return 2;//出库申请单状态更新为：2 审核中
	}
	
	
	/*获取当前管理员审核权限*/
	public function is_auditer($outstockId) {
		$auditer = $this->auditer($outstockId,array());
		$admin = session('admin.info');
		$control = 1;//不可操作 2：可操作
		foreach($auditer as $k=>$v) {
			if($v['audit_id']==$admin['id']) {
				$control = $v['control'];
				break;
			}
		}
		return array("cAdmin"=>$admin['id'],"cControl"=>$control);
	}
	
	/*出库单报废审核：获取当前管理员审核权限*/
	public function cancel_is_auditer($cid) {
		$auditer = $this->cancel_auditer($cid,array());
		$admin = session('admin.info');
		$control = 1;//不可操作 2：可操作
		foreach($auditer as $k=>$v) {
			if($v['audit_id']==$admin['id']) {
				$control = $v['control'];
				break;
			}
		}
		return array("cAdmin"=>$admin['id'],"cControl"=>$control);
	}
	
	//获取管理员信息
	public function getAdmin($condition) 
	{
		$admin = M('Admin')->where($condition)->find();
		return $admin;
	}	
	
	//新增出库单表以及相关表所有数据
	public function addOutStockInfo($type,$data) 
	{
		$orgId = $this->findAssignAdminInfo("org_id");
		switch($type) {
			case "o";//出库表
				// $orgId = $this->findAssignAdminInfo("org_id");
		 	// 	$isSupper = $this->findAssignAdminInfo("is_supper");
		 	// 	if($orgId&&$isSupper!=1) $data['org_id']=$orgId;//加组
				return $this->add($data);
			break;
			case "og";//出库物品
				return M("outstock_goods")->addAll($data);
			break;
			case "oa";//出库审核
				return M("outstock_audit")->addAll($data);
			break;
			case "c";//出库报废表
				$orgId = $this->findAssignAdminInfo("org_id");
		 		$isSupper = $this->findAssignAdminInfo("is_supper");
				if($orgId&&$isSupper!=1) $data["org_id"] = $orgId;//加组
				return M("cancel")->add($data);
			break;

			case "ca"://出库报废审核
				return M("cancel_audit")->addAll($data);
			break;			
			case "m";//出库消息推送
			 	return M("message")->add($data);
			break;
		}
	}

	//更新出库单表以及相关表所有数据
	public function updOutStockInfo($type,$data,$condition=array()) 
	{
		$orgId = $this->findAssignAdminInfo("org_id");
		switch($type) {
			case "o";//出库表
				$orgId = $this->findAssignAdminInfo("org_id");
				$isSupper = $this->findAssignAdminInfo("is_supper");
			    if($orgId&&$isSupper!=1) $condition['org_id'] = $orgId;//加组
				return $this->where($condition)->save($data);
			break;
			//case "og";//出库物品
			//break;
			case "oa";//出库审核
				return M("outstock_audit")->where($condition)->save($data);
			break;
			case "m";//出库消息推送
			 	return M("message")->add($data);
			break;
			case "ct"://出库转资产卡片临时表：
				return M("cards_temp")->add($data);
			break;
			case "s"://stock：库存表：条件：存在减少
				$newdata = array();
				$getStockCondition = 
				array(
					'goods_id' => $data['goods_id'],
					'supplier_id' => $data['supplier_id'],
					'spec' => $data['spec'],
				);
				$orgId = $this->findAssignAdminInfo("org_id");
		 		$isSupper = $this->findAssignAdminInfo("is_supper");
		 		if($orgId&&$isSupper!=1) $getStockCondition['org_id'] = $orgId;//加组
				$getStock = M("stock")->field("id,num,total")->where($getStockCondition)->find();//get stock id
				if($getStock){
					$newdata['num']   = $getStock['num']-$data['num'];
					$newdata['total'] = $getStock['total']-$data['total'];
					$saveCondition['id'] = $getStock['id'];
					$saveStock = M("stock")->where($saveCondition)->save($newdata);
					if($saveStock) return $getStock['id'];
					return 0;
					
				}else{
					return 0;
				}
			break;
			case "s_plus"://stock：库存表：条件：回库
				$newdata = array();
				$getStockCondition = 
				array(
					'goods_id' => $data['goods_id'],
					'supplier_id' => $data['supplier_id'],
					'spec' => $data['spec'],
				);
				$orgId = $this->findAssignAdminInfo("org_id");
				$isSupper = $this->findAssignAdminInfo("is_supper");
				if($orgId&&$isSupper!=1) $getStockCondition['org_id'] = $orgId;//加组
				$getStock = M("stock")->field("id,num,total")->where($getStockCondition)->find();//get stock id
				if($getStock){
					$newdata['num']   = $getStock['num']+$data['num'];
					$newdata['total'] = $getStock['total']+$data['total'];
					$saveCondition['id'] = $getStock['id'];
					$saveStock = M("stock")->where($saveCondition)->save($newdata);
					if($saveStock) return true;
					return false;
					
				}else{
					return false;
				}
			break;
			case "sd"://stock_detial：库存明细
				return M("stock_detial")->addAll($data);
			break;
			case "c"://出库报废表
				$orgId = $this->findAssignAdminInfo("org_id");
				$isSupper = $this->findAssignAdminInfo("is_supper");
				if($orgId&&$isSupper!=1) $condition["org_id"]=$orgId;//加组
				return M("cancel")->where($condition)->save($data);
			break;
			case "ca"://出库报废审核
				return M("cancel_audit")->where($condition)->save($data);
			break;
			case "new"://短信息
				return M("message")->where($condition)->save($data);
			break;

		}
	}
	
	//删除出库单表以及相关表所有数据
	public function del($type,$condition=array()) {
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition['org_id'] = $orgId;//加组
		switch($type) {
			case "o";//出库表
				$oRst = $this->where($condition)->delete();//注意下属关联性
			break;
			case "og";//出库物品
				return M("outstock_goods")->where($condition)->delete();
			break;
			case "oa";//出库审核
				return M("outstock_audit")->where($condition)->delete();
			break;
			case "s"://stock：库存表：条件：存在减少
				return M("stock")->where($condition)->delete();//注意关联性
			break;
			case "sd"://stock_detial：库存明细
				 return M("stock_detial")->where($condition)->delete();
			break;
			case "c"://出库报废表
				
			break;
			case "ca"://出库报废审核
			break;//消息表
			case "m":
				return M("message")->where($condition)->delete();
			break;
			
		}	
	}
	
	/*柱状图-饼状图数据*/
	public function barPie($condition=array()) {
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		$noConditionDataCondition = array();
		if($orgId&&$isSupper!=1) {
			$noConditionDataCondition['o.org_id']=$orgId;//加组
			$condition['o.org_id']=$orgId;//加组
		}
		$noConditionData = 
		M("outstock_goods")->alias("og")
		->field("g.assets_name as goodsname")
		->join("k_outstock o on o.id = og.outstock_id")
		->join("k_base_goods g on g.id=og.goods_id")
		->where($noConditionDataCondition)
		->group("g.assets_name")
		->select();
		$list =
		M("outstock_goods")->alias("og")
		->field("o.depart,sum(num) as total")
		->join("k_outstock o on o.id = og.outstock_id")
		->join("k_base_goods g on g.id=og.goods_id")
		->where($condition)
		->group("o.depart")
		->select();
		//部门集 颜色 部门出库总和
		$data = array();
		foreach($list as $k=>$v) {
			$v['color'] =  "#".dechex(rand(3355443,13421772));
			$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['depart']));
			$v['depart_name'] = $getAssignDepartment['depart_name'];
			$data[$k] = $v;
			
		}
		return array('goods'=>$noConditionData,'list'=>$data);
	}
	
    /**
    * [getDepartment 部门数据集]
	*/
	public function getDepartment($condition=array())
	{
		$rst = M("depart")->where($condition)
		       ->select();
		return $rst;		
	}  
	 /**
	  * [getAssignDepartment 获取指定部门数据：一条]
	  */
	public function getAssignDepartment($condition=array())
	{
		$rst = M("depart")->where($condition)
		       ->find();
		return $rst;		
	}
	
    /**
    * [getAssignDepartByAdmin 通过管理员获取部门]
	*/
	private function getAssignDepartByAdmin($condition=array())
	{
		$rst = M("depart")->alias("d")
			   ->field("d.depart_name,d.id as depart_id")
			   ->where($condition)
			   ->join("k_admin a on a.depart_id = d.id")
		       ->find();
		return $rst;		
	}   
	
	//获取物品列表
    public function goods($aname){
    	// print_r(I('post.'));exit;
    	$org_id=I('get.org')?:0;
		$where = "  bg.assets_name LIKE '%{$aname}%' and k.org_id=$org_id ";
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $where.=" and k.org_id=".$orgId;
		$field = "k.num as stocknum,bg.id as goods_id,k.supplier_id as supplier_id ,bg.assets_name as assets_name,bg.unit as unit,k.spec as spec";
		$rst = M("base_goods")->alias("bg")->field($field)->where($where)
			   ->join("k_stock k on bg.id=k.goods_id")
			   ->group("bg.id,k.supplier_id,k.spec")
			   ->select();
		return $rst;
    }
	/*检测库存*/
	public function checkStock($goods_id,$supplier_id,$spec,$num){
		$where = array();
		$where['goods_id'] = $goods_id;
		$where['supplier_id'] = $supplier_id;
		$where['spec'] = $spec;
		$getStock = M("stock")->field("num")->where($where)->find();
		if(!$getStock||$getStock['num']<$num) return 0;
		else return 1;	
	}
	/*获取库存总数*/
	public function getStock($goods_id,$supplier_id,$spec){
		$where = array();
		$where['goods_id'] = $goods_id;
		$where['supplier_id'] = $supplier_id;
		$where['spec'] = $spec;
		$getStock = M("stock")->field("num")->where($where)->find();
		if($getStock) return $getStock['num'];
		else return 0;
	}
	/*获取出库商品统计：依出库单号*/
	public function getOStockTotalByOrderSn($order_sn){
		$num = M("stock_detial")->where("order_sn = '".$order_sn."'")->sum('goods_num');
		$total = M("stock_detial")->where("order_sn = '".$order_sn."'")->sum('total');
		return array('num'=>$num,'total'=>$total);
	}
	/*出库报废单数据：依出库单号*/
	public function getCancel($where){
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $where["org_id"]=$orgId;//加组
		$rst = M("cancel")->where($where)->find();
		return $rst;
	}
	/*获取报废审核表：依出库单号*/
	public function getCancelAudit($where){
		$rst = M("cancel_audit")->where($where)->find();
		return $rst;
	}
	
	
	/*获取出库商品集合：依出库单标识*/
	public function getOutstockGoodsByOid($outstock_id){
		$getOutstockGoods = M("outstock_goods")->field("goods_id,supplier_id,spec")->where("outstock_id=".$outstock_id)->select();
		return $getOutstockGoods;
	}
	
	
	/*获取指定管理员信息：会话*/
	public function findAssignAdminInfo($field=""){
		$admin = session('admin.info');
		if($field) {
			return $admin[$field];
		}else{
			return $admin;
		}
	}
	/*部门列表：条件：org_id*/
	public function getDepartByOrg(){
		$admin=session('admin');
		$topid=I('org');
		if ($topid) {
    		if ($admin['info']['is_supper']) {
    			$org=$topid;
    		}else{
    			$org=$admin['info']['org_id'];
    		}
    	}else{
    		$org=$admin['info']['org_id'];
    	}

        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $lists = $cat->getList(0);
        
        if ($topid) {
        	$De = $cat->getPath($topid);
            $De[0]['fullname'] = $De[0]['depart_name'];
            $Depart = $cat->getChildren($org,$lists) ;
            $Depart = array_merge($De,$Depart);
        }else{
        	$Depart = $admin['info']['is_supper'] ? $lists : $cat->getChildren($org,$lists);
        }
        return $Depart;
		// $admin=session('admin');
  //       $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
  //       $lists = $cat->getList(0);
  //       $Depart = $admin['info']['is_supper'] ? $lists : $cat->getChildren($admin['info']['org_id'],$lists) ;
  //       return $Depart;
	}
	
}
 
 
 
 ?>