<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: mr.king (277429358@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-28 20:44:13
// +----------------------------------------------------------------------
/**
 * 盘点、折旧、月结 模型
 */
namespace Admin\Model;
use Think\Model;

class CalculateModel extends Model{
	protected $tableName = "cards";//核心表
	/**
	*盘点
	**/
	public function ass($condition=array(),$per=10,$timecondition=array())
	{
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["c.org_id"]=$orgId;//加组
		$current_total = $this->allAss($condition,$timecondition);//合计
		$count = $this->alias('c')->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
//			   ->join('k_stock k on k.goods_id = c.goods_id')
			   //->join('k_stock_detial kd on kd.stock_id = k.id')
			   ->group('g.id,c.spec')
			   ->select();
//		$count = $this->alias('c')->field("c.id")
//				->where($condition)
//				->join('k_base_goods g  ON g.id = c.goods_id' )
//				->join('k_category y  ON g.cate_id = y.id' )
////			   ->join('k_stock k on k.goods_id = c.goods_id')
//				//->join('k_stock_detial kd on kd.stock_id = k.id')
//				->group('c.goods_id,c.spec')
//				->select();
//		dd($count);
//		dd(count($count));
		$Page = new \Think\PageA(count($count),$per);
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as taxval,g.assets_name as name,y.sn as sn,y.cate_name")
			   ->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
//			   ->join('k_stock k on k.goods_id = c.goods_id')
			   //->join('k_stock_detial kd on kd.stock_id = k.id')
			   ->group('g.id,c.spec')
			   ->limit($Page->firstRow.','.$Page->listRows)
			   ->select();
		if($list){
			$newList = array();
			foreach($list as $k=>$v){
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$v['inStock_amount']  = $inStock['amount'];
				$v['inStock_totalPrice']  = $inStock['totalPrice'];
				$v['outStock_amount'] = $outStock['amount'];
				$v['outStock_totalPrice'] = $outStock['totalPrice'];
				$noMonLoss = $this->monLoss($v['goods_id'],$v['spec'],false,array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$v['scrap_num'] = $noMonLoss['amount'];
				$v['scrap_total'] = $noMonLoss['totalPrice'];
				$v['account_num']   = $v['inStock_amount']-$v['outStock_amount']-$v['scrap_num'];
				$v['account_total'] = $v['inStock_total']-$v['outStock_total']-$v['scrap_total'];//账面结存-金额
				//开始使用时间
				$v['start_date'] = date("Y-m-d h:i:s" , $v['start_date']);
				//部门
				$department = "";
				if($v['department']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['department'] = $department;//部门
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
		$rst = array('page'=>$page,'list'=>$list,'current_total'=>$current_total);
		return $rst;
	}
	/**
	 *盘点
	 **/
	public function ass1($condition=array(),$first,$len,$timecondition=array())
	{
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["c.org_id"]=$orgId;//加组
		$current_total = $this->allAss($condition,$timecondition);//合计
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as taxval,g.assets_name as name,y.sn as sn")
				->where($condition)
				->join('k_base_goods g  ON g.id = c.goods_id' )
				->join('k_category y  ON g.cate_id = y.id' )
//			   ->join('k_stock k on k.goods_id = c.goods_id')
				//->join('k_stock_detial kd on kd.stock_id = k.id')
				->group('g.id,c.spec')
				->limit($first,$len)
				->select();
		if($list){
			$newList = array();
			foreach($list as $k=>$v){
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$v['inStock_amount']  = $inStock['amount'];
				$v['inStock_totalPrice']  = $inStock['totalPrice'];
				$v['unit']   = isset($v['unit'])?$v['unit']:' ';
				$v['spec']   = empty($v['spec'])?' ':$v['spec'];
				$v['outStock_amount'] = $outStock['amount'];
				$v['outStock_totalPrice'] = $outStock['totalPrice'];
				$noMonLoss = $this->monLoss($v['goods_id'],$v['spec'],false,array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$v['scrap_num'] = $noMonLoss['amount'];
				$v['scrap_total'] = $noMonLoss['totalPrice'];
				$v['account_num']   = $v['inStock_amount']-$v['outStock_amount']-$v['scrap_num'];
				$v['account_total'] = $v['inStock_total']-$v['outStock_total']-$v['scrap_total'];//账面结存-金额
				//开始使用时间
				$v['start_date'] = date("Y-m-d h:i:s" , $v['start_date']);
				$v['is_inventory'] = ($v['is_inventory'] ==1)?'已盘点':'未盘点';
				//部门
				$department = " ";
				if($v['department']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['department'] = $department;//部门
				$newList[$k] = $v;
			}
		}
		return $newList;
	}
	/**
	*盘点-账面单独处理：待完善：加载速度
	**/
	public function ass_status($condition=array(),$status=0,$per=10,$timecondition=array())
	{
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition = array_merge($condition,array("c.org_id"=>$orgId));
		$current_total = $this->allAss($condition,$timecondition);//合计
//		$count = $this->alias('c')->where($condition)
//		       ->join('k_base_goods g  ON g.id = c.goods_id' )
//			   ->join('k_category y  ON g.cate_id = y.id' )
//			   ->join('k_stock k on k.goods_id = c.goods_id')
//			   //->join('k_stock_detial kd on kd.stock_id = k.id')
//			   ->group('g.id,c.spec')
//			   ->count();
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as taxval,g.id as goods_id,g.assets_name as name,y.sn as sn")->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
//			   ->join('k_stock k on k.goods_id = c.goods_id')
			   //->join('k_stock_detial kd on kd.stock_id = k.id')
			   ->group('g.id,c.spec')
			   ->select();
		if($list){
			$newList = array();
			foreach($list as $k=>$v){
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']),$timecondition);	
				$v['inStock_amount']  = $inStock['amount'];
				$v['inStock_totalPrice']  = $inStock['totalPrice'];
				$v['outStock_amount'] = $outStock['amount'];
				$v['outStock_totalPrice'] = $outStock['totalPrice'];
				$noMonLoss = $this->monLoss($v['goods_id'],$v['spec'],false,array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$v['scrap_num'] = $noMonLoss['amount'];
				$v['scrap_total'] = $noMonLoss['totalPrice'];
				$v['account_num']   = $v['inStock_amount']-$v['outStock_amount']-$v['scrap_num'];
				$v['account_total'] = $v['inStock_total']-$v['outStock_total']-$v['scrap_total'];//账面结存-金额
				//开始使用时间
				$v['start_date'] = date("Y-m-d h:i:s" , $v['start_date']);
				$v['is_inventory'] = ($v['is_inventory'] ==1)?'已盘点':'未盘点';
				//部门
				$department = "";
				if($v['department']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['department'] = $department;//部门
				$newList[$k] = $v;
			}
			$list = $newList;
		}
		/*账面筛选*/
		if($status&&$list) {
			//重构数据
			$statusList_one = array();//>0
			$statusList_two = array();//<0
			foreach($list as $k=>$v){
				if($v['account_total']>0) {
					$statusList_one[$k] = $v;
				}
				else {
					$statusList_two[$k] = $v;
				}
			}
			if($status==1){
				$newList = $statusList_one;
				$count = count($statusList_one);
			}
			else{
				$newList = $statusList_two;
				$count = count($statusList_two);
			}
			$query['status'] = $status;
			//重构分页
			$Page = new \Think\PageA($count,$per); 
			$list = array_slice($newList,$Page->firstRow,$Page->listRows);
		}else {
			$Page = new \Think\PageA(0,$per); 
			$list = array();
		}
		
		//分页参数
		if($query) {
			foreach($query as $key=>$val) {
				$Page->parameter[$key] = urlencode($val);
			}
		}
		//print_r($this->linkStock(1));exit;
		$page = $Page->show();
		$rst = array('page'=>$page,'list'=>$list,'current_total'=>$current_total);
		return $rst;
	}
	/**
	 *盘点-账面单独处理：待完善：加载速度
	 **/
	public function ass_status1($condition=array(),$first,$len,$status=0,$timecondition=array())
	{
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition = array_merge($condition,array("c.org_id"=>$orgId));
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as taxval,g.id as goods_id,g.assets_name as name,y.sn as sn")->where($condition)
				->join('k_base_goods g  ON g.id = c.goods_id' )
				->join('k_category y  ON g.cate_id = y.id' )
//			   ->join('k_stock k on k.goods_id = c.goods_id')
				//->join('k_stock_detial kd on kd.stock_id = k.id')
				->group('g.id,c.spec')
				->select();
		if($list){
			$newList = array();
			foreach($list as $k=>$v){
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$v['inStock_amount']  = $inStock['amount'];
				$v['inStock_totalPrice']  = $inStock['totalPrice'];
				$v['outStock_amount'] = $outStock['amount'];
				$v['unit']   = isset($v['unit'])?$v['unit']:' ';
				$v['spec']   = empty($v['spec'])?' ':$v['spec'];
				$v['outStock_totalPrice'] = $outStock['totalPrice'];
				$noMonLoss = $this->monLoss($v['goods_id'],$v['spec'],false,array('g.type_id'=>$condition['g.type_id']),$timecondition);
				$v['scrap_num'] = $noMonLoss['amount'];
				$v['scrap_total'] = $noMonLoss['totalPrice'];
				$v['account_num']   = $v['inStock_amount']-$v['outStock_amount']-$v['scrap_num'];
				$v['account_total'] = $v['inStock_total']-$v['outStock_total']-$v['scrap_total'];//账面结存-金额
				//开始使用时间
				$v['start_date'] = date("Y-m-d h:i:s" , $v['start_date']);
				//部门
				$department = " ";
				if($v['department']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['department'] = $department;//部门
				$newList[$k] = $v;
			}
			$list = $newList;
		}
		/*账面筛选*/
		if($status&&$list) {
			//重构数据
			$statusList_one = array();//>0
			$statusList_two = array();//<0
			foreach($list as $k=>$v){
				if($v['account_total']>0) {
					$statusList_one[$k] = $v;
				}
				else {
					$statusList_two[$k] = $v;
				}
			}
			if($status==1){
				$newList = $statusList_one;
			}
			else{
				$newList = $statusList_two;
			}
			$query['status'] = $status;
			//重构分页
			$list = array_slice($newList,$first,$len);
		}else {
			$list = array();
		}
		return $list;
	}
	/**
	*allAss 盘点-统计
	**/
	private function allAss($condition=array(),$timecondition=array())  
	{	
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as taxval,g.assets_name as name,y.sn as sn")
			   ->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
//			   ->join('k_stock k on k.goods_id = c.goods_id')
			   //->join('k_stock_detial kd on kd.stock_id = k.id')
			   ->group('g.id,c.spec')
			   ->select();
		$assTotal = 0;
		if($list){
			$newList = array();
			foreach($list as $k=>$v){
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']));
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']));		
				$v['inStock_amount']  = $inStock['amount'];
				$v['inStock_totalPrice']  = $inStock['totalPrice'];
				$v['outStock_amount'] = $outStock['amount'];
				$v['outStock_totalPrice'] = $outStock['totalPrice'];
				$noMonLoss = $this->monLoss($v['goods_id'],$v['spec'],false,array('g.type_id'=>$condition['g.type_id']));
				$v['scrap_num'] = $noMonLoss['amount'];
				$v['scrap_total'] = $noMonLoss['totalPrice'];
				//$v['account_num']   = $v['inStock_amount']-$v['outStock_amount']-$v['scrap_num'];
				//$v['account_total'] = $v['inStock_total']-$v['outStock_total']-$v['scrap_total'];
				$assTotal += $v['inStock_total']-$v['outStock_total']-$v['scrap_total'];
				//开始使用时间
				//$v['start_date'] = date("Y-m-d h:i:s" , $v['']);
				//$newList[$k] = $v;
			}
		}
		//print_r($this->linkStock(1));exit;
		return $assTotal;
	}
	
	 /**
	  * [depr 折旧]
	  */
	public function depr($condition=array(),$per=10)
	{
		//print_r($this->linkStock($assets_name="方便面",$spec="5"));
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["c.org_id"]=$orgId;
		$count = $this->alias('c')->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
			   ->group('g.id,c.spec')
			   ->select();
		$Page = new \Think\PageA(count($count),$per);
		$list = $this->alias('c')->field("c.*,count(c.ID) as total,g.assets_name as name,y.sn as sn,y.cate_name as catname ,g.unit as unit")
			   ->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
			   ->group('g.id,c.spec')
			   ->limit($Page->firstRow.','.$Page->listRows)
			   ->select();
		if($list) {
			$newList = array();
			foreach($list as $k=>$v){
				$deprs = 
				deprs(//
				  array(
					'depr'=>$v['depr_method'],//折旧算法类型0 1 2 3
					'start_date'=>date("Y-m-d",$v['start_date']),//开始使用时间
					'service_life'=>$v['service_life'],//预计使用年限
					'original_value'=>$v['original_value'],//原值
					'net_salvage'=>$v['net_salvage'],//净残值率
					'month_works'=>$v['month_works']//工作量法的工作量
				 ));
				 $v['sueM'] = $deprs['sueM'];//计提时间
				 $v['mon_depr_sum'] = $deprs['mon_depr_sum'];//累计折旧
				 $v['net_worth'] = $deprs['net_worth'];//净值
				 $v['k_net_worth'] = $deprs['k_net_worth'];//剩余可折旧
				 //部门
				 $department = "";
				 if($v['department']) {
				 	$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				 }
				 $v['department'] = $department;//部门
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
		//print_r($this->assLinkStock());
		$page = $Page->show();
		$rst = array('page'=>$page,'list'=>$list);
		return $rst;
	}

	/**
	 * [depr 折旧]
	 */
	public function depr1($condition=array(),$first,$len)
	{
		//print_r($this->linkStock($assets_name="方便面",$spec="5"));
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["c.org_id"]=$orgId;
		$list = $this->alias('c')->field("c.*,count(c.ID) as total,g.assets_name as name,y.sn as sn,y.cate_name as catname ,g.unit as unit")
				->where($condition)
				->join('k_base_goods g  ON g.id = c.goods_id' )
				->join('k_category y  ON g.cate_id = y.id' )
				->group('g.id,c.spec')
				->limit($first,$len)
				->select();
		if($list) {
			$newList = array();
			foreach($list as $k=>$v){
				$deprs =
						deprs(//
								array(
										'depr'=>$v['depr_method'],//折旧算法类型0 1 2 3
										'start_date'=>date("Y-m-d",$v['start_date']),//开始使用时间
										'service_life'=>$v['service_life'],//预计使用年限
										'original_value'=>$v['original_value'],//原值
										'net_salvage'=>$v['net_salvage'],//净残值率
										'month_works'=>$v['month_works']//工作量法的工作量
								));
				$v['sueM'] = $deprs['sueM'];//计提时间
				$v['mon_depr_sum'] = $deprs['mon_depr_sum'];//累计折旧
				$v['start_date'] = date("Y-m-d",$v['start_date']);//开始使用时间
				$v['net_worth'] = $deprs['net_worth'];//净值
				$v['k_net_worth'] = $deprs['k_net_worth'];//剩余可折旧
				//部门
				$department = " ";
				if($v['department']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['department'] = $department;//部门
				$newList[$k] = $v;
			}
			$list = $newList;
		}
//		//分页参数
//		if($query) {
//			foreach($query as $key=>$val) {
//				$Page->parameter[$key] = urlencode($val);
//			}
//		}
//		//print_r($this->assLinkStock());
//		$page = $Page->show();
//		$rst = array('page'=>$page,'list'=>$list);
		return $list;
	}
	 /**
	  * [mon 月结-卡片列表]
	  */
	public function mon($condition=array(),$per=10)
	{
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		if($condition['c.org_id']){
			$condition["c.org_id"] = $condition['c.org_id'];
			$query['query']['orgid'] = $condition['c.org_id'];
		}else {
			$orgId = $this->findAssignAdminInfo("org_id");
			$isSupper = $this->findAssignAdminInfo("is_supper");
			if($orgId&&$isSupper!=1) $condition["c.org_id"]=$orgId;
		}
		
		$count = $this->alias('c')->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
			   ->group('g.id,c.spec')
			   ->select();
		$Page = new \Think\PageA(count($count),$per);
		//分页数据
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as totalTaxVal,count(c.ID) as total,g.assets_name as name,y.sn as sn,y.cate_name as catname ,g.unit as unit")->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
			   ->group('g.id,c.spec')
			   ->limit($Page->firstRow.','.$Page->listRows)
			   ->select();	
		$current_all_total = 0;//本期总结存
		$last_all_total = 0;//上期结存
		if($list){
			$newList = array();
			foreach($list as $k=>$v){
				//期初结存
				$monLastCalculate = $this->monLastCalculate($v['name'],$v['spec']);
				$v['last_num']   = $monLastCalculate['amount'];
				$v['last_total'] = $monLastCalculate['totalPrice'];
				$last_all_total+=$v['last_total'];
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']));
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']));		
				$v['ent_num']  = $inStock['amount'];//入库数量
				$v['ent_total']  = $inStock['totalPrice'];//入库金额
				$v['out_num'] = $outStock['amount'];//出库数量
				$v['out_total'] = $outStock['totalPrice'];//出库金额
				//报废报损
				$monLoss = $this->monLoss($v['goods_id'],$v['spec'],true,array('g.type_id'=>$condition['g.type_id']));
				$v['scrap_num']   = $monLoss['amount'];
				$v['acrap_total'] = $monLoss['totalPrice'];
				//本期维修
				$monRepair = $this->monRepair($v['goods_id'],$v['spec'],array('g.type_id'=>$condition['g.type_id']));
				$v['repair_num']   = $monRepair['amount'];
				$v['repair_total'] = $monRepair['totalPrice'];
				//本期结存
				//$v['current_num']   = $v['last_num']+$inStock-$v['out_num']-$v['total']-$v['repair_num'];//=surplus_num
				$v['current_num']   = $v['last_num']+$v['ent_num']-$v['out_num']-$v['repair_num'];//=surplus_num
				$v['current_total'] = $v['last_total']+$v['ent_total']-$v['totaltaxval']-$v['out_total']-$v['repair_total'];// = surplus_total
				$current_all_total += $v['current_total'];
				//部门
				$department = "";
				if($v['department']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['department'] = $department;//部门
				//$newList
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
		//上期结存合计-单
		//$monLastCalculateTotal = $this->monLastCalculate();
		//$last_all_total = $monLastCalculateTotal['totalPrice'];
		//本期总结存
		//$allMon = $this->allMon();//总月结
		//$current_all_total = $allMon['current_all_total'];
		//print_r($this->assLinkStock());
		//按钮：该月已月结情况
		$monRst = $this->monCondition();
		$monStatus = 0;
		if($monRst) {
			$monStatus = 1;
		}
		$page = $Page->show();
		$rst = array('page'=>$page,'list'=>$list,'last_all_total'=>$last_all_total,'current_all_total'=>$current_all_total,'monStatus'=>$monStatus);
		return $rst;
	}
	/**
	 * [mon 月结-卡片列表]
	 */
	public function mon1($condition=array(),$first,$len)
	{
		$query = array();
		if($condition){
			$query = $condition['query'];
			unset($condition['query']);
		}
		if($condition['c.org_id']){
			$condition["c.org_id"] = $condition['c.org_id'];
			$query['query']['orgid'] = $condition['c.org_id'];
		}else {
			$orgId = $this->findAssignAdminInfo("org_id");
			$isSupper = $this->findAssignAdminInfo("is_supper");
			if($orgId&&$isSupper!=1) $condition["c.org_id"]=$orgId;
		}
		//分页数据
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as totalTaxVal,count(c.ID) as total,g.assets_name as name,y.sn as sn,y.cate_name as catname ,g.unit as unit")->where($condition)
				->join('k_base_goods g  ON g.id = c.goods_id' )
				->join('k_category y  ON g.cate_id = y.id' )
				->group('g.id,c.spec')
				->limit($first,$len)
				->select();
		$current_all_total = 0;//本期总结存
		$last_all_total = 0;//上期结存
		if($list){
			$newList = array();
			foreach($list as $k=>$v){
				//期初结存
				$monLastCalculate = $this->monLastCalculate($v['name'],$v['spec']);
				$v['spec']   = empty($v['spec'])?' ':$v['spec'];
				$v['last_num']   = $monLastCalculate['amount'];
				$v['last_total'] = $monLastCalculate['totalPrice'];
				$last_all_total+=$v['last_total'];
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']));
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']));
				$v['ent_num']  = $inStock['amount'];//入库数量
				$v['ent_total']  = $inStock['totalPrice'];//入库金额
				$v['out_num'] = $outStock['amount'];//出库数量
				$v['out_total'] = $outStock['totalPrice'];//出库金额
				//报废报损
				$monLoss = $this->monLoss($v['goods_id'],$v['spec'],true,array('g.type_id'=>$condition['g.type_id']));
				$v['scrap_num']   = $monLoss['amount'];
				$v['acrap_total'] = $monLoss['totalPrice'];
				//本期维修
				$monRepair = $this->monRepair($v['goods_id'],$v['spec'],array('g.type_id'=>$condition['g.type_id']));
				$v['repair_num']   = $monRepair['amount'];
				$v['repair_total'] = $monRepair['totalPrice'];
				//本期结存
				//$v['current_num']   = $v['last_num']+$inStock-$v['out_num']-$v['total']-$v['repair_num'];//=surplus_num
				$v['current_num']   = $v['last_num']+$v['ent_num']-$v['out_num']-$v['repair_num'];//=surplus_num
				$v['current_total'] = $v['last_total']+$v['ent_total']-$v['totaltaxval']-$v['out_total']-$v['repair_total'];// = surplus_total
				$current_all_total += $v['current_total'];
				//部门
				$department = "";
				if($v['department']) {
					$getAssignDepartment = $this->getAssignDepartment(array('id'=>$v['department']));
					$department = $getAssignDepartment['depart_name'];
				}
				$v['department'] = $department;//部门
				//$newList
				$newList[$k] = $v;
			}
			$list = $newList;
		}
//		//分页参数
//		if($query) {
//			foreach($query as $key=>$val) {
//				$Page->parameter[$key] = urlencode($val);
//			}
//		}
		//上期结存合计-单
		//$monLastCalculateTotal = $this->monLastCalculate();
		//$last_all_total = $monLastCalculateTotal['totalPrice'];
		//本期总结存
		//$allMon = $this->allMon();//总月结
		//$current_all_total = $allMon['current_all_total'];
		//print_r($this->assLinkStock());
		//按钮：该月已月结情况
//		$monRst = $this->monCondition();
//		$monStatus = 0;
//		if($monRst) {
//			$monStatus = 1;
//		}
//		$page = $Page->show();
//		$rst = array('page'=>$page,'list'=>$list,'last_all_total'=>$last_all_total,'current_all_total'=>$current_all_total,'monStatus'=>$monStatus);
		return $list;
	}
	/*月结管理*/
	public function monManage($condition=array()){
		$getMon = M("monthly")->where($condition)->select();
		if($getMon) {
			$newMon = array();
			foreach($getMon as $key=>$value){
				$getAssignDepartment = $this->getAssignDepartment(array('id'=>$value['org_id'],'pid'=>0));
				$value['departname'] = $getAssignDepartment['depart_name'];
				//$value['status'] = $value['status']==1?"已锁":"正常";
				$newMon[$key] = $value;
			}
			$getMon = $newMon;
		}
		return $getMon;
	}
	/*更新总月结*/
	public function updMonManage($data,$condition){
		$updM = M("monthly")->where($condition)->save($data);
		return $updM;
	}
	
	 /**
	  * [allMon 月结数据-无分页]
	  */
	public function allMon($data,$condition=array())
	{
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["c.org_id"]=$orgId;
		//无分页数据
		$list = $this->alias('c')->field("c.*,(c.tax_val*count(c.ID)) as totalTaxVal,count(c.ID) as total,g.assets_name as name,y.sn as sn,y.cate_name as catname ,g.unit as unit")
			   ->where($condition)
		       ->join('k_base_goods g  ON g.id = c.goods_id' )
			   ->join('k_category y  ON g.cate_id = y.id' )
			   ->group('g.id,c.spec')
			   ->select();  	
		$current_all_total = 0;//本期总结存
		if($list){
			$newList = array();
			$vv = array();
			foreach($list as $k=>$v){
				//期初结存
				//$monLastCalculate = $this->monLastCalculate($v['name'],$v['spec']);
				//$v['last_num']   = $monLastCalculate['amount'];
				//$v['last_total'] = $monLastCalculate['totalPrice'];
				//物品标识、名称、规格
				$vv['goods_id'] = $v['goods_id'];//标识
				//$vv['name'] = $v['name'];//名称
				$vv['spec'] = $v['spec'];//规格
				//库存
				$inStock  = $this->linkStock($v['goods_id'],$v['spec'],"+","",array('g.type_id'=>$condition['g.type_id']));
				$outStock = $this->linkStock($v['goods_id'],$v['spec'],"-","",array('g.type_id'=>$condition['g.type_id']));	
				$vv['ent_num']  = $inStock['amount'];//入库数量
				$vv['ent_total']  = $inStock['totalPrice'];//入库金额
				$vv['out_num'] = $outStock['amount'];//出库数量
				$vv['out_total'] = $outStock['totalPrice'];//出库金额
				//增值税
				$vv['VAT_num']   = $v['total'];
				$vv['VAT_total'] = $v['totaltaxval'];
				$monLoss = $this->monLoss($v['goods_id'],$v['spec'],true,array('g.type_id'=>$condition['g.type_id']));
				$vv['scrap_num']   = $monLoss['amount'];
				$vv['acrap_total'] = $monLoss['totalPrice'];
				//本期维修
				$monRepair = $this->monRepair($v['goods_id'],$v['spec'],array('g.type_id'=>$condition['g.type_id']));
				$vv['repair_num']   = $monRepair['amount'];
				$vv['repair_total'] = $monRepair['totalPrice'];
				//本期结存
				//$v['current_num']   = $v['last_num']+$inStock-$v['out_num']-$v['total']-$v['repair_num'];//=surplus_num
				$vv['surplus_num']   = $vv['last_num']+$vv['ent_num']-$vv['out_num']-$vv['repair_num'];//=surplus_num
				$vv['surplus_total'] = $vv['last_total']+$vv['ent_total']-$vv['VAT_total']-$vv['out_total']-$vv['repair_total'];// = surplus_total 
				//本期结存总合计
				$current_all_total += $vv['surplus_total'];//本期统计并写入月结表-total 
				//月结表标识
				$vv['monthly_id'] = $data['monthly_id'];
				//$newList
				$newList[$k] = $vv;
			}
			$list = $newList;
		}
		//上期结存合计
		$monLastCalculateTotal = $this->monLastCalculate();
		$last_all_total = $monLastCalculateTotal['totalPrice'];
		//print_r($this->assLinkStock());
		$rst = array('list'=>$list,'last_all_total'=>$last_all_total,'current_all_total'=>$current_all_total);
		return $rst;
	}
	
	//月结事件
	public function monA($type){
		//k_monthly 月结表 + k_monthly_detail：月结详细表
		//月结
		$M = M('monthly');
		$MD= M('monthly_detail');
		$mData = array();
		$orgId = $this->findAssignAdminInfo("org_id");//加组
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($type==1) {
			//：是否符合月结条件
			$mRst = $this->monCondition();
			if($mRst) return array(0,"本期已月结，不可在操作！");
			//月结操作
			$mData['time'] = time();
			$allMon = $this->allMon();//该期总月结 
			$mData['total'] = $allMon['current_all_total'];
			//$admin = session('admin.info');
			$admin = $this->findAssignAdminInfo();
			$mData['operator'] = $admin['name'];//操作者信息
			if(!$allMon['list']) return array(0,"无卡片，操作失败！");//无卡片
			if($orgId&&$isSupper!=1) $mData['org_id'] = $orgId;//加组
			$monthlyId=$M->add($mData);
			if (!$monthlyId) {
				return array(0,"操作失败！");
			}
			$newAllMon = $this->allMon(array('monthly_id'=>$monthlyId));//重新获该期总月结
			$rst = $MD->addAll($newAllMon['list']);
			if(!$rst) return array(0,"操作失败！");
			else{
				//更新卡片盘点状态
				//is_inventory 1 
				$upData['is_inventory'] = 1;
				$cardCondition = "is_inventory=0";
				if($orgId&&$isSupper!=1) $cardCondition.= " and org_id = ".$orgId;//加组
				$cRst = M("cards")->where($cardCondition)->save($upData);
				//超管锁定所有月结
				if($cRst){
					if($isSupper==1){
						$updMonManage = $this->updMonManage(array('status'=>1));
						if($updMonManage) {
							return array(1,"月结锁定成功!");
						}else {
							return array(0,"月结锁定失败!");
						}
					}else {
						return array(1,"操作成功!");
					}
				}
				else  return array(0,"更新卡片盘点状态失败!");
			}
		//反月结
		}else{
			//删除本期数据
			$mRst = $this->monCondition();
			if($mRst['status']==1&&$orgId) return array(0,"反月结已被锁，请咨询管理员！");
			if($mRst){
				$monthlyId = $mRst['id'];
			 	$delMRst = $M->where(array('id'=>$monthlyId))->delete();
				if($delMRst){
					$delMdRst = $MD->where(array('monthly_id'=>$monthlyId))->delete();
					if($delMdRst){
						//更新卡片盘点状态
						//is_inventory 1 
						$upData['is_inventory'] = 0;
						$cardCondition = "is_inventory=1";
						if($orgId&&$isSupper!=1) $cardCondition.= " and org_id = ".$orgId;//加组
						$cRst = M("cards")->where($cardCondition)->save($upData);
						if($cRst) return array(1,"操作成功!");
						else  return array(0,"更新卡片盘点状态失败!");
					}
				}else {
					return array(0,"操作失败!");
				}
			}else {
				return array(0,"无数据可提供反月结操作!");
			}
			//更新卡片盘点状态			
		}
	}
	/*月结依据*/
	private function monCondition(){
		$bMonth = mktime(0,0,0,date("m"),1,date('Y'));//开始
		$eMonth = mktime(23,59,59,date("m"),date('t'),date('Y'));//结束
		$map['time'] = array(array('EGT',$bMonth),array('ELT',$eMonth));
		if($orgId&&$isSupper!=1) $map["org_id"]=$orgId;//月结加组
		$mRst = M('monthly')->where($map)->order("id desc")->find();
		return $mRst;
	}
	
	 /**
	  * [monLastCalculate 关联mon 月结-期初结存-单]
	  * amount-fee
	  */
	private function monLastCalculate($assets_name="",$spec=""){
		//资产月结详细表：k_monthly_detail + 月结表：k_monthly
		//时间范围
		//$currentDate = date("Y-m-d",time());
		$condition = array();
		if($assets_name) $condition['g.assets_name'] = $assets_name;
		if($spec) $condition['d.spec'] = $spec;
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["m.org_id"]=$orgId;
		$rst = M("monthly_detail")->alias("d")->field("d.surplus_num as amount,d.surplus_total as money")
		       ->where($condition)
			   ->join("k_monthly m on m.id = d.monthly_id")
			   ->join("k_base_goods g on d.goods_id = g.id ")
			   ->order("m.time DESC")
			   ->find();
		//存在上期结存
		if($rst){
			$rst['totalPrice'] = $rst['money']?$rst['money']:0;
			return $rst;
		}
		else return array('amount'=>0,'totalPrice'=>0);//总数量-总金额
	}
	 /**
	  * [monLastStock 月结-本期入库]
	  * amount-fee
	  */
	private function monLastStock($goodsId){
		return $this->linkStock($goodsId,"+");
	}
	
	 /**
	  * [monLastOutStock 月结-本期出库]
	  * amount-fee
	  */
	private function monLastOutStock($goodsId){
		return $this->linkStock($goodsId,"-");
	}
		
	 /**
	  * [monVat 月结-增值税]
	  * amount-fee
	  */
	private function monVat(){
	
	}
	
	 /**
	  * [monLoss 月结-本期报废报损]
	  * amount-fee
	  */
	  private function monLoss($goods_id=0,$spec="",$mon=true,$condition=array(),$timecondition=array()){
	//private function monLoss($assets_name="",$spec="",$mon=true,$condition=array()){
		$condition['g.id'] = $goods_id;
		$condition['c.spec'] = $spec;
		//月结使用
		if($mon){
			$lastCalculateTime = M("monthly")->order("time desc")->getField('time');
			if($lastCalculateTime) $condition['p.time'] = array(array('EGT',$lastCalculateTime),array('ELT',strtotime(date("Y-m-d H:i:s",time()))));
		}else {//其他
			if($timecondition) $condition['p.time'] = $timecondition;
		}
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["p.org_id"]=$orgId;
		$rst = M("scrap_goods")->alias('ps')->field("count(ps.scrap_id) as amount,ps.card_sn,sum(ps.money) as money")//?
				->where($condition)
				->join('k_scrap p on p.id = ps.scrap_id' )
				->join('k_cards c on c.order_sn = ps.card_sn' )
				->join('k_base_goods g on g.id = c.goods_id')
				//->group('g.assets_name,c.spec')
				->find();
		if($rst){
			$rst['totalPrice'] = ($rst['amount']*$rst['money'])?$rst['amount']*$rst['money']:0;
			return $rst;
		}
		else return array('amount'=>0,'totalPrice'=>0);//总数量-总金额
	}
	
	 /**
	  * [monRepair 月结-本期维修]
	  * amount-fee
	  */
	private function monRepair($goods_id=0,$spec="",$condition=array()){
		//资产维修单：k_repair + 维修单物品表：k_repair_goods on 一id = 多card_id?
		$condition['r.status'] = 2;//维修审核：1不通过 2通过
		$condition['g.id'] = $goods_id;
		$condition['c.spec'] = $spec;
		$lastCalculateTime = M("monthly")->where()->order("time desc")->getField('time');
		if($lastCalculateTime) $condition['rg.time'] = array(array('EGT',$lastCalculateTime),array('ELT',strtotime(date("Y-m-d H:i:s",time()))));
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["r.org_id"]=$orgId;
		$rst = M("repair_goods")->alias('rg')->field("count(rg.card_id) as amount,sum(rg.money) as money")	
			   ->where($condition)
			   ->join("k_repair r on r.r_id = rg.card_id")
			   ->join('k_cards c ON c.order_sn = rg.card_sn')//规格
			   ->join('k_base_goods g  ON  g.id = c.goods_id' )//物品名称
			   //->group('g.assets_name,s.spec')
			   ->find();	
		if($rst){
			$rst['totalPrice'] = $rst['money']?$rst['money']:0;
			return $rst;
		}
		return array('amount'=>0,'totalPrice'=>0);//总数量-总金额
		
	}
	
	 /**
	  * [monCurrentCalculate 关联mon 月结-本期结存]
	  * amount-fee
	  */
	private function monCurrentCalculate(){
	
	}
	 /**
	  * [vat 增值税]
	  * rate-fee
	  */
	private function vat(){
		
	}
	 /**
	  * [linkStock 关联库存]
	  */
	 private function linkStock($goods_id=0,$spec="",$func="+",$time="",$condition=array(),$timecondition=array()) {
	//private function linkStock($assets_name="",$spec="",$func="+",$time="") {//20161227前注释
		//库存明细表：k_stock_detial d + 库存表规格表：k_stock s + 物品表：k_base_goods g
		$condition['g.id'] = $goods_id;
		$condition['s.spec'] = $spec;
		//搜索条件存在情况：用于盘点
		if($timecondition){
			$condition['d.time'] = $timecondition;
		}else{
			//月结时使用，盘点需在控制器传时间条件
			if(!$time){
				//最近月结时间 
				$lastCalculateTime = M("monthly")->order("time desc")->getField('time');
				if($lastCalculateTime) $condition['d.time'] = array(array('EGT',$lastCalculateTime),array('ELT',strtotime(date("Y-m-d H:i:s",time()))));
			}
			else{
				$condition['d.time'] = array(array('ELT',strtotime(date("Y-m-d H:i:s",time()))));
			}
		}
		$condition['d.func'] = $func;
		//MONTH(order_date)=MONTH(CURDATE())
		$orgId = $this->findAssignAdminInfo("org_id");
		$isSupper = $this->findAssignAdminInfo("is_supper");
		if($orgId&&$isSupper!=1) $condition["s.org_id"]=$orgId;
//		 $mmp['spec'] = $spec;
////		 $mmp['spec'] = '正常规格';
//		 $mmp['goods_id'] = $goods_id;
////		 $mmp['goods_id'] = 1;
//		 $stock = M("stock")->where($mmp)->getField('id,goods_id');//所有id
//		 if(count($stock) <1){
//			 return array('amount'=>0,'price'=>0,'totalPrice'=>0);//总数量-总金额
//		 }
//		 $new_stock = [];
//		 foreach($stock as $k=>$v){
//			 $new_stock[]=$k;
//		 }
//		 $stock_ids = implode(',',$new_stock);
//		 $detail = M('stock_detial')->where(" (stock_id in($stock_ids)) and (func ='".$func."')")->select();
//		 if(count($detail) <1){
//			 return array('amount'=>0,'price'=>0,'totalPrice'=>0);//总数量-总金额
//		 }
//		 $tot_price=$num = 0;
//		 foreach($detail as $k=>$v){
//			 $num+= $v['goods_num'];
//			 $tot_price +=$v['total'];
//		 }
//		 $res['amount'] = $num;
////		 $total = M('stock_detial')->where(" (stock_id in($stock_ids)) and (func ='".$func."')")->field('SUM(total) as tot')->find();
//		 $res['totalPrice'] = $tot_price;
//		return $res;



		$rst = M("stock_detial")->alias('d')->field("d.goods_num as amount,d.total as price")
				->where($condition)
		        ->join('k_stock s  ON  s.id = d.stock_id' )//规格型号
				->join('k_base_goods g  ON  g.id = s.goods_id' )//名称
				->select();
		 $tot_price=$num = 0;
		 if($rst){
			 foreach($rst as $k=>$v){
				 $num+= $v['amount'];
				 $tot_price +=$v['price'];
			 }
		}
		 $res['amount'] = $num;
		 $res['totalPrice'] = $tot_price;
//		 dd($res);
		 return $res;
//		else return array('amount'=>0,'price'=>0,'totalPrice'=>0);//总数量-总金额
	}
	
	
	
	 /**
	  * [assDate 盘点时间集合]
	  */
	  public function assDate()
	  {
	  	$year = M("stock_detial")->field("FROM_UNIXTIME(time,'%Y') as time")->group("FROM_UNIXTIME(time,'%Y')")->select();
		if(!$year) $year = array(array("time"=>date("Y",time())));
		$season = array(1=>"上半年",2=>"下半年",3=>"一季度",4=>"二季度",5=>"三季度",6=>"四季度");
		return array("year"=>$year,"season"=>$season);
	  }
	  
	 /**
	  * [assDateYear 年：上半年|下半年]
	  */
	  public function assDateYear($year,$type)
	  {
		if($type==1) {
			$bMon = 1;
			$eMon = 6;
		}else if($type==2) {
			$bMon = 7;
			$eMon = 12;
		}else{//全年
			$bMon = 1;
			$eMon = 12;
		}
		$bYear = strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0,$bMon,1,$year)));
		$eYear = strtotime(date('Y-m-d H:i:s', mktime(23,59,59,$eMon,date('t',mktime(0, 0 , 0,$eMon,1,$year)),$year)));  
		
		$yearCondition = array(array('EGT',$bYear),array('ELT',$eYear));
		return $yearCondition;		
	  }
	  	  
	 /**
	  * [assDateSeason 季度]
	  */
	  public function assDateSeason($year,$cMon)
	  {
		//$cSeason = ceil((date('n'))/3);//当月季度	
		$cSeason = ceil(($cMon)/3);//当月季度	
		$bSeason = strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0,$cSeason*3-3+1,1,$year)));
		$eSeason = strtotime(date('Y-m-d H:i:s', mktime(23,59,59,$cSeason*3,date('t',mktime(0, 0 , 0,$cSeason*3,1,$year)),$year)));  
		$seasonCondition = array(array('EGT',$bSeason),array('ELT',$eSeason));
		return $seasonCondition;		
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
        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $lists = $cat->getList(0);
        $Depart = $admin['info']['is_supper'] ? $lists : $cat->getChildren($admin['info']['org_id'],$lists) ;
        return $Depart;
	}
		
	
}

?>