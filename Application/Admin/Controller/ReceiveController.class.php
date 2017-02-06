<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: Mr.czs (3032444149@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-29
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class ReceiveController extends BaseController
{

public $outlist=array('order_sn'=>'领用单号','assets_name'=>'物品名称','receive_spec'=>'规格型号','applyer'=>'申请人','depart'=>'部门','apply_time'=>'申请日期','number'=>'数量','price_total'=>'金额');
	//列表页面显示
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
		//参数筛选
		$w='1=1 ';
		$admin=session('admin');
		$order_sn=trim(I('order_sn'));
		$endtime=strtotime(I('endtime'));
		$starttime=strtotime(I('starttime'));
		$depart=trim(I('depart'));
		$assets_name=trim(I('assets_name'));
		$Smobile=trim(I('Smobile'));
		$type_id = I('type_id');
		$p_total = 0;$p_num=0;
		if($endtime<$starttime){
			$this->error('结束时间不能小于开始时间');
		}
		if($assets_name){
			$w.='and assets_name like "%'.$assets_name.'%"';
		}
		if($depart){
			$w.='and depart="'.$depart.'"';
		}
		if($order_sn){
			$w.='and order_sn like "%'.$order_sn.'%"';
		}
		if($endtime&&$starttime){
			$w.='and apply_time >='.$starttime.' and apply_time <='.$endtime;
		}else{
			$endtime=strtotime("+1 day");
			$starttime=strtotime("-1 month");
		}
		if($Smobile){
			$w.=" AND (assets_name LIKE '%{$Smobile}%' OR depart = '{$Smobile}' OR order_sn LIKE '%{$Smobile}%')";
		}
		$size=20;
		if($admin['info']['is_supper']!=1){
			$w.=' and k_receive.org_id='.$admin['info']['org_id'];
		}
		if($type_id){
          $w.=" and k_type.id='$type_id'";
        }
        
        if(I('cate_id')){
          $w.=" and k_category.id='".I('cate_id')."'";
        }
		$w.=' and (k_receive.status=4 or k_receive.status=3)';
		// echo $w;
  		$count = M('receive')
  		         ->join('k_receive_goods ON k_receive.r_id=k_receive_goods.receive_id')
  		         ->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')
  		         ->join('k_stock ON k_receive_goods.goods_id=k_stock.goods_id and k_receive_goods.s_id=k_stock.id')
  		         ->join('k_type ON k_base_goods.type_id=k_type.id')
  		         ->join('k_category ON k_base_goods.cate_id=k_category.id')
  		         ->where($w)->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show= $Page->show();// 分页显示输出
		$list=M('receive')
		      ->join('k_receive_goods ON k_receive.r_id=k_receive_goods.receive_id')
		      ->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')
		      ->join('k_stock ON k_receive_goods.goods_id=k_stock.goods_id and k_receive_goods.s_id=k_stock.id')
		      ->join('k_depart ON k_depart.id=k_receive.depart')
		      ->join('k_type ON k_base_goods.type_id=k_type.id')
  		      ->join('k_category ON k_base_goods.cate_id=k_category.id')
		      ->where($w)->order('apply_time desc')
		      ->limit($Page->firstRow.','.$Page->listRows)
		      ->select();
		foreach ($list as $key => $value) {
			# code...
			$p_total+=$value['price_total'];
			$p_num+=$value['number'];
		}
		$in_total = M('receive')->field('SUM(price_total) as total,SUM(number) as num')
		           ->join('k_receive_goods ON k_receive.r_id=k_receive_goods.receive_id')
		           ->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')
		           ->join('k_stock ON k_receive_goods.goods_id=k_stock.goods_id and k_receive_goods.s_id=k_stock.id')
		           ->join('k_depart ON k_depart.id=k_receive.depart')
		           ->join('k_type ON k_base_goods.type_id=k_type.id')
  		           ->join('k_category ON k_base_goods.cate_id=k_category.id')
		           ->where($w)
		           ->find();
		//dump($s_total);exit;
		if($type_id){
            $cat2=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
            $this->assign('cateS',$cat2->getList(0));
        }
		$this->assign('list',$list);

		// var_dump($list_dep);
		$this->assign('page',$show);
		$this->assign('starttime',$starttime);
		$this->assign('endtime',$endtime);
		$this->assign('order_sn',$order_sn);
		$this->assign('depart',$depart);

		$this->assign('assets_name',$assets_name);
		$this->assign('Smobile',$Smobile);
		$this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',I('cate_id'));
		$this->assign('s_total',$in_total['total']);
		$this->assign('p_total',$p_total);
		$this->assign('s_num',$in_total['num']);
		$this->assign('p_num',$p_num);
			if($admin['info']['is_supper']==1){
				//超级管理员
				$cat=CAT('Depart',array('id','pid','depart_name'));
	      $list_dep=$cat->getList(0);

			}else{
				// $where='pid='.;
				$cat=CAT('Depart',array('id','pid','depart_name'));
	      // $list_dep=$cat->getList($admin['info']['org_id']);
	      $lists=$cat->getList(0);
	      $list_dep=$cat->getChildren($admin['info']['org_id'],$lists);
			}
			$this->assign('list_dep',$list_dep);
		$this->display();
	}
	//编辑
	public function edit(){
		$id=I('id');
			$admin=session('admin');
		if(IS_POST){

			if($admin['info']['is_supper']==1){
				$org_id=I('org_id');
			}else{
				$org_id=$admin['info']['org_id'];
			}
			$ni=I();
			$other=$ni['o'];
			unset($ni['o']);
			empty($ni)&&$this->error('未添加审核物品！');
			!$other['department_id']&&$this->error('未选择审核部门！');
			// print_r($other['department_id']);die;
			$time=$other['date']?strtotime($other['date']):time();
			if(is_array($other['department_id'])){
				//判断是否有重复的值
				if (count($other['department_id']) != count(array_unique($other['department_id']))) {   
				  $this->error('请不要选择重复的审核人');
				} 
			}
			if(empty($other['applyer'])){
				$this->error('申请人不能为空');
			}
			if(empty($other['depart'])){
				$this->error('部门不能为空');
			}
			if(empty($other['total_price'])){
				$this->error('合计不能为空');
			}
			$data=array(
				'order_sn'  =>$other['ordersn'],
				'apply_time'=>$time,
				'last_op_time'=>time(),
				'create_id' =>$admin['info']['id'],
				'status'    =>1,
				'applyer'=>$other['applyer'],
				'depart'=>$other['depart'],
				're_remark'=>$other['re_remark'],
				'total_price'=>$other['total_price'],
				'org_id'=>$org_id
			);
			$M=M('receive');
			//创建申请单
			// $M->where(array('r_id'=>$id))->delete();
			$M->where(array('r_id'=>$id))->save($data);
			$orderID=$id;
			if (!$orderID) {
				$this->error('保存失败!');
			}
			$goods=array();
			$list=array();
			// print_r($ni);die;
			foreach ($ni as $key => $value) {
	
					// if(empty($value['number'])){
					// 		$this->error('数量不能为0');
					// }
					// if(empty($value['price'])){
					// 		$this->error('单价不能为空');
					// }
					if(isset($value['id'])){

						//判断领用的库存大小
					$dbNum=M('stock')->field('num,goods_id')->where(array('id'=>$value['id']))->find();
					if($dbNum['num']<$value['number']){
						
						$goodsName=M('base_goods')->field('assets_name')->where(array('id'=>$dbNum['goods_id']))->find();
						$this->error($goodsName['assets_name'].'领用数量最多为'.$dbNum['num']);
					}
								$list[]=$value['id'];
								$goods[]=array(
								'receive_id'=>$orderID,
								'goods_id'=>$dbNum['goods_id'],
								'receive_spec'  =>$value['spec'],
								'number' =>$value['number'],
								'price' =>$value['price'],
								'price_total' =>$value['price_total'],
								's_id'=>$value['id']
							);
							}
				
				
			}
			if(is_array($list)){
				//判断是否有重复的值
				if (count($list) != count(array_unique($list))) { 
				  $this->error('请不要重复添加商品');
				} 
			}
			foreach($goods as $k=>$v){
					if(empty($v['goods_id'])){
						unset($goods[$k]);
					}
			}
			if(count($goods)==1){
				$goods[0]=$goods[1];
				unset($goods[1]);
			}
			//保存申请单物品
			// print_r($goods);die;
			M('receive_goods')->where(array('receive_id'=>$id))->delete();
		
			$re=M('receive_goods')->addAll($goods);
			if (!$re) {
				$M->where(array('r_id'=>$orderID))->delete();
				$this->error('保存失败！');
			}	
			//保存审核人员
		 $users=array();
		 if(is_array($other['department_id'])){
		 		foreach ($other['department_id'] as $vd) {
				$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->where("depart_id=$vd and is_auditer=1")->getField('id');
				if (!$tuser) {
					$this->error($department.' 没有审核人员!');
				}
				$users[]=array(
					'receive_id'=>$orderID,
					'audit_id'=>$tuser,
					'audit_status'=>1
				);
			}
			M('receive_audit')->where(array('receive_id'=>$id))->delete();
			$re_audit=M('receive_audit')->addAll($users);
		 }else{
		 	$vd=$other['department_id'];
			$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->where("depart_id=$vd and is_auditer=1")->getField('id');
				if (!$tuser) {
					$this->error($department.' 没有审核人员!');
				}
		 	$userdata=array(
					'receive_id'=>$orderID,
					'audit_id'=>$tuser,
					'audit_status'=>1
		 		);
		 	M('receive_audit')->where(array('receive_id'=>$id))->delete();
		 	$re_audit=M('receive_audit')->add($userdata);
		 }
			if (!$re_audit) {
				$M->where(array('r_id'=>$orderID))->delete();
				M('receive_goods')->where(array('receive_id'=>$orderID))->delete();
				$this->error('保存失败！！');
			}
			$this->success('编辑成功',U('check'));
		}
		$detaillist=M('receive')->field('k_receive.org_id as orginfo_id,k_receive.*,k_receive_goods.*,k_base_goods.*,k_stock.*,k_supplier.*,k_stock.id as b_id')->join('k_receive_goods ON k_receive.r_id=k_receive_goods.receive_id')->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')->join('k_stock ON k_receive_goods.goods_id=k_stock.goods_id and k_receive_goods.s_id=k_stock.id')->join('k_supplier ON k_stock.supplier_id=k_supplier.id')->where(array('k_receive_goods.receive_id'=>$id))->select();
			// print_r($detaillist);
		// //获取审核的人列表信息
		$auditlist=M('receive_audit')->join('k_admin ON audit_id=k_admin.id')->join('k_depart ON k_admin.depart_id=k_depart.id')->where(array('receive_id'=>$detaillist[0]['receive_id']))->select();
		if($admin['info']['is_supper']==1){
				//超级管理员
				$cat=CAT('Depart',array('id','pid','depart_name'));
	     
			}else{
				// $where='pid='.;
				$cat=CAT('Depart',array('id','pid','depart_name'));
	     
			}
		$c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
        $cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
    	//$list_dep=$cat->getList($detaillist[0]['orginfo_id']);
		$isReadyCheck=2;
		$auditArr=M('receive_audit')->where(array('receive_id'=>$id))->find();
		if($auditArr['audit_status']==3||$auditArr['audit_status']==4){
			$isReadyCheck=1;
		}

		// print_r($auditlist);
	// echo $detaillist[0]['orginfo_id'];
		$this->assign('isReadyCheck',$isReadyCheck);
		$this->assign('list_dep',$this->getDeparts($detaillist[0]['orginfo_id']));
		$this->assign('auditlist',$auditlist);
		$this->assign('detaillist',$detaillist);
		$this->assign('org_id',$detaillist[0]['orginfo_id']);
		$this->assign('id',$id);
		$this->display();
	}
	 public function ajaxindex()
	    {
        $page=I('get.page');
        $len=I('get.size')?:2;
        $key=I('get.key_word');
        $first=$len*($page-1);
        $w="1=1";
        if($key)
        {
           $w.=" AND (assets_name LIKE '%{$key}%' OR depart_name LIKE '%{$key}%' OR order_sn LIKE '%{$key}%')";
        }
        $w.=' and (k_receive.status=4 or k_receive.status=3)';
					$list=M('receive')->join('k_receive_goods ON k_receive.r_id=k_receive_goods.receive_id')->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')->join('k_stock ON k_receive_goods.goods_id=k_stock.goods_id and k_receive_goods.s_id=k_stock.id')->join('k_depart ON k_depart.id=k_receive.depart')->where($w)->order('apply_time desc')->limit($first,$len)->select();
					foreach($list as $k=>$v){
						$list[$k]['url']='<a href="'.U('detail',array('id'=>$v['receive_id'],'goods_id'=>$v['goods_id'])).'">'.$v['order_sn'].'</a>';
						
					}
        $this->ajaxReturn($list);
    }
	//导出数据
    public function export(){
    	//参数筛选
		$w='1=1 ';
		$len=I('size')?I('size'):10;
		$page=I('page');
		$first=$len*($page-1);
		$order_sn=trim(I('order_sn'));
		$endtime=strtotime(I('endtime'));
		$starttime=strtotime(I('starttime'));
		$depart=trim(I('depart'));
		$assets_name=trim(I('assets_name'));
		if($endtime<$starttime){
			$this->error('结束时间不能小于开始时间');
		}
		if($assets_name){
			$w.='and assets_name like "%'.$assets_name.'%"';
		}
		if($depart){
			$w.='and depart like "%'.$depart.'%"';
		}
		if($order_sn){
			$w.='and order_sn="'.$order_sn.'"';
		}
		if($endtime&&$starttime){
			$w.='and apply_time >='.$starttime.' and apply_time <='.$endtime;
		}else{
			$endtime=strtotime("+1 day");
			$starttime=strtotime("-1 day");
		}
		$w.=' and (k_receive.status=4 or k_receive.status=3)';
		$lists=M('receive')->field('order_sn,assets_name,receive_spec,applyer,depart,apply_time,number,price_total')->join('k_receive_goods ON k_receive.r_id=k_receive_goods.receive_id')->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')->join('k_stock ON k_base_goods.id=k_stock.goods_id')->where($w)->limit($first,$len)->order('apply_time desc')->select();
			foreach ($lists as $k=>$v){
				$lists[$k]['apply_time'] = date('Y/m/d',$v['apply_time']);
			}
			return $lists;
    }
	//图表展示
	//图表展示
	public function chars(){
		$page=I('page')?I('page'):1;
		$id=I('id')?I('id'):'';
		$d_id=I('org_id');
		$where='1=1 ';
			$admin=session('admin');
			if($admin['info']['is_supper']!=1){
				$org_id=$admin['info']['org_id'];
				$where.='and org_id='.$org_id;
			}
		if(empty($id)){
			//默认第一个商品
			$ww='1=1 ';
			if($admin['info']['is_supper']!=1){
				$ww.='and org_id='.$admin['info']['org_id'];
			}
			$goodsRow=M('receive_goods')->join('k_receive ON k_receive.r_id=k_receive_goods.receive_id')->where($ww)->find();
			$id=$goodsRow['goods_id'];
		}
		$w='1=1 ';
		$starttime=strtotime(I('starttime'));
		$endtime=strtotime(I('endtime'));
		if($starttime&&$endtime){
				if($starttime>$endtime){
					$w.=' and apply_time>='.$endtime.' and apply_time<='.$starttime;
				}else{
					$w.=' and apply_time>='.$starttime.' and apply_time<='.$endtime;
				}
		}
		if($d_id){
			$w.=" and dp.id='$d_id'";
		}
		if(isset($id)){
			$w.=' and g.receive_id=r.r_id and r.depart=dp.id and r.status=4 and goods_id='.$id;
		// echo $w;
		$goodsname=M('base_goods')->where(array('id'=>$id))->find();
		$goodlists=M()->field('sum(number) as counnumber,depart_name')->table('k_receive r,k_receive_goods g,k_depart dp')->where($w)->group('depart')->select();
		// echo $goodlists;die;
			$goods='[';
			$str='[';
			foreach ($goodlists as $k => $v) {
				if($k<(count($goodlists)-1)){
					$str.='"'.$v["depart_name"].'",';
				}else{
					$str.='"'.$v["depart_name"].'"';
				}
				if($k<(count($goodlists)-1))
				{
					$goods.="{value:'".$v['counnumber']."', name:'".$v['depart_name']."'},";
				}else{
					 $goods.="{value:'".$v['counnumber']."', name:'".$v['depart_name']."'}";
				}
			}
			$goods.=']';
			$str.=']';
						$goodsSelect=M('receive_goods')->join('k_receive ON k_receive.r_id=k_receive_goods.receive_id')->field('goods_id')->where($where)->select();
			$arr=array();
			foreach ($goodsSelect as $k => $v) {
				$arr[]=$v['goods_id'];
			}
			$unique_arr=array_unique($arr);
			$strA=implode(',',$unique_arr);
			// echo $strA;
			//获取商品列表信息
			$base_goods=M('base_goods')->where('id in ('.$strA.')')->select();
		}
		
			// echo $goods;
			

			// print_r($base_goods);
			// print_r($str);
			//dump(session());exit;
		$org_id=I('get.org_id')?:0;
        $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
        $cate =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
        $top_departs = $cate->getList($c_id);
        $this->assign('top_departs',$top_departs);
        $this->assign('org',$org_id);
		$this->assign('strinfo',$str);
		$this->assign('base_goods',$base_goods);
		$this->assign('goods',$goods);
		$this->assign('id',$id);
		$this->assign('goodsname',$goodsname['assets_name']);
		$this->assign('starttime',strtotime("-1 month"));
		$this->assign('endtime',strtotime("+1 day"));
		$this->assign('is_supper',$_SESSION['admin']['info']['is_supper']);
		$this->assign('page',$page);
		if($page==1){
			$this->display();
		}else{
			$this->display('pin');
		}
		
	}
	//ajax分页
		public function ajaxcheck(){
			  $page=I('get.page');
			  $status=I('get.status');
        $len=I('get.size')?:2;
        $first=$len*($page-1);
        $w="1=1";
				if($status){
					
					switch ($status) {
						case '3':
						$w.=' and status=3 or status=4';
							break;
						case '4':
							break;
						
						default:
							$w.=' and status='.$status;
							break;
					}
				}else{
					$status=1;
				}
					$list=M('receive')->join('k_depart ON k_depart.id=k_receive.depart')->join('k_admin ON create_id=k_admin.id')->where($w)->limit($first,$len)->select();
        foreach($list as $k=>$v){
					$list[$k]['url']='<a href="'.U('checkdetail',array('id'=>$v['r_id'])).'" class="audit_already">'.$v['order_sn'].'</a>';
					if($v['status']==1){
        			$list[$k]['statusinfo']='<a class="audit_begin" href="'.U('checkdetail',array('id'=>$v['r_id'])).'">未审核</a>';
        	}
        	if($v['status']==2){
        			$list[$k]['statusinfo']='<a class="audit_begin" href="'.U('checkdetail',array('id'=>$v['r_id'])).'">审核中</a>';
        	}
        	if($v['status']==3){
        			$list[$k]['statusinfo']='<a class="audit_notpass" href="'.U('checkdetail',array('id'=>$v['r_id'])).'">未通过</a>';
        	}
        	if($v['status']==4){
        			$list[$k]['statusinfo']='<a class="audit_already" href="'.U('checkdetail',array('id'=>$v['r_id'])).'">已审核</a>';
        	}
        	if($status==1){
        		$list[$k]['statuseq1']='<td>';
        		if($adminid!=$v['creater_id']){
        			$list[$k]['statuseq1'].='无操作';
        		}else{
        			$list[$k]['statuseq1'].='<a href="'.U('edit',array('id'=>$v['r_id'])).'">编辑</a>';
        		}
						$list[$k]['statuseq1'].='</td>';
        	}
        }
        $this->ajaxReturn($list);
		}
	//审核列表
	public function check(){
		//数据筛选
		$w='1=1 ';
		$admin=session('admin');
		$order_sn=trim(I('order_sn'));
		$apply_time=strtotime(I('apply_time'));
		$depart=trim(I('depart'));
		$applyer=trim(I('applyer'));
		if($apply_time){
			$w.='and apply_time='.$apply_time;
		}else{
			$apply_time=time();
		}
		if($applyer){
			$w.=' and username like "%'.$applyer.'%"';
		}
		if($depart){
			$w.=' and depart="'.$depart.'"';
		}
		if($order_sn){
			$w.=' and order_sn like "%'.$order_sn.'%"';
		}
		$status=I('status');
		if($status){
			
			switch ($status) {
				case '3':
				$w.=' and status=3 or status=4';
					break;
				case '4':
				
					break;
				
				default:
					$w.=' and status='.$status;
					break;
			}
		}else{
			$status=4;
			// $w.=' and status=4';
		}

		if($admin['info']['is_supper']!=1){
			$w.=' and k_receive.org_id='.$admin['info']['org_id'];
		}
		
		// echo $w;
		$size=20;
  	$count = M('receive')->join('k_admin ON create_id=k_admin.id')->where($w)->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show= $Page->show();// 分页显示输出
		  $list=M('receive')->join('k_admin ON create_id=k_admin.id')->join('k_depart ON k_depart.id=k_receive.depart')->where($w)->limit($Page->firstRow.','.$Page->listRows)->order('apply_time desc')->select();
		  $this->assign('list',$list);
		  $this->assign('page',$show);
		  $this->assign('status',$status);
		  $this->assign('time',$apply_time);
		  $this->assign('order_sn',$order_sn);
		  $this->assign('depart',$depart);
		  $this->assign('applyer',$applyer);
		  
			if($admin['info']['is_supper']==1){
				//超级管理员
				$cat=CAT('Depart',array('id','pid','depart_name'));
	      $list_dep=$cat->getList(0);
			}else{
				// $where='pid='.;
				$cat=CAT('Depart',array('id','pid','depart_name'));
	      $lists=$cat->getList(0);
	      $list_dep=$cat->getChildren($admin['info']['org_id'],$lists);
			}
			$this->assign('list_dep',$list_dep);
			$this->assign('adminId',$admin['info']['id']);
			$this->display();
	}
	//审核动作
	public function checkaction(){
		//接受数据
		// $adminId=array('id'=>2);
		$adminId=session('admin');
		$id=I('id');
		$re_reason=I('resion');
		$audit_status=I('agreeRadio');
		$time=strtotime(I('time'));
		//判断参数是否为空
		if($audit_status==''){
			$this->error('请选择是否同意');
		}
		if(empty($time)){
			$this->error('请选择审核日期');
		}
		if(empty($re_reason)){
			$this->error('请填写审核意见');
		}
		$data=array(
			'audit_status'=>$audit_status,
			're_time'=>$time,
			're_reason'=>$re_reason
			);
		// print_r($data);
		$res=M('receive_audit')->where(array('receive_id'=>$id,'audit_id'=>$adminId['info']['id']))->save($data);
		if($res){
			//更新最后操作时间
			M('receive')->where(array('r_id'=>$id))->save(array('last_op_time'=>time()));
			$result=M('receive')->where(array('r_id'=>$id))->find();
			$url=U('checkdetail',array('id'=>$id));
			//判断操作是否通过，不通过的话 直接更新状态
			if($audit_status==3){
				//订单结束
				M('receive')->where(array('r_id'=>$id))->save(array('status'=>3));
				//通知申请人，订单申请失败
				$msg=array(
							'title'=>'您的领用申请审核不通过，请查看',
							'apply_id'=>$id,
							'send_id'=>$adminId['info']['id'],
							'receive_id'=>$result['create_id'],
							'url'=>$url,
							'send_time'=>time(),
							'type'=>30
					);
				//处理当前审核人的消息状态

				M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
								
				$final=M('message')->add($msg);
			}else{
				//说明同意，如果我是第一个或者是最后一个人的时候，不需要通知下一个申请人，只需要通知申请人
				//如果我不是第一个或者是最后一个审核人，则通知下一个申请人
				$userlist=M('receive_audit')->where(array('receive_id'=>$id))->select();

				foreach ($userlist as $k => $v) {
					if($v['audit_id']==$adminId['info']['id']){
						$lastkey=($k+1);
						if($k!=0&&$k!=(count($userlist)-1)){
							//说明不是第一人或者是最后一个人
							$keyk=($k+1);
						$msg=array(
							'title'=>'领用申请需要您审核，请查看',
							'apply_id'=>$id,
							'send_id'=>$adminId['info']['id'],
							'receive_id'=>$userlist[$keyk]['audit_id'],
							'url'=>$url,
							'send_time'=>time(),
							'type'=>30
							);
						M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
						$final=M('message')->add($msg);
						}else{
							//第一个或者是最后一个
							//同时，需要更新订单的完成状态--最后一个
							if($k==(count($userlist)-1)){
								$msg=array(
								'title'=>'您的领用申请审核已经通过，请查看',
								'apply_id'=>$id,
								'send_id'=>$adminId['info']['id'],
								'receive_id'=>$result['create_id'],
								'url'=>$url,
								'send_time'=>time(),
								'type'=>30
								);

								M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
							  $final=M('message')->add($msg);
								M('receive')->where(array('r_id'=>$id))->save(array('status'=>4));
							}else{
							$msg=array(
								'title'=>'领用申请需要您审核，请查看',
								'apply_id'=>$id,
								'send_id'=>$adminId['info']['id'],
								'receive_id'=>$userlist[$lastkey]['audit_id'],
								'url'=>$url,
								'send_time'=>time(),
								'type'=>30
								);
							M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
							$final=M('message')->add($msg);
							M('receive')->where(array('r_id'=>$id))->save(array('status'=>2));
							}
						}
					}
				}
				
			}
			$this->success('审核成功',U('check'));
		}else{
				$this->error('审核失败');
		}
		
	}
	//审核详细页面
	public function checkdetail(){
		// $adminId=array('id'=>2);
		$adminId=session('admin');
		$id=I('id');
		//检查该登录的管理员是否已经更改了消息状态，只能改下最后一个消息的申请人信息
		$msgList=M('message')->where(array('apply_id'=>$id))->select();
		$receiveinfo=M('receive')->where(array('r_id'=>$id))->find();
		if($msgList[count($msgList)-1]['receive_id']==$adminId['info']['id']&&($receiveinfo['status']==3||$receiveinfo['status']==4)){
			if($msgList[count($msgList)-1]['status']==1){
				//说明是最后一个信息 是申请人的消息
				M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
			}
		}
		$list=M('receive_goods')->join('k_receive ON k_receive.r_id=k_receive_goods.receive_id')->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')->join('k_stock ON k_receive_goods.goods_id=k_stock.goods_id and k_receive_goods.s_id=k_stock.id')->join('k_depart ON k_depart.id=k_receive.depart')->where(array('receive_id'=>$id))->select();
		// print_r($list);


		//获取审核的人列表信息
		$auditlist=M('receive_audit')->join('k_admin ON audit_id=k_admin.id')->join('k_depart ON k_admin.depart_id=k_depart.id')->where(array('receive_id'=>$id))->select();
		//判断用户是否具有操作的权限
		$isfinish=2;
		
		if($receiveinfo['status']>2){
			//说明订单申请处理完毕啦
			$isfinish=1;
		}
		//判断登录人是否有操作权限,1:如果上一个审核人没有审核，则没有审核权限，2：如果自己审核过了，则没有审核权限
		$receive_list=M('receive_audit')->where(array('receive_id'=>$id))->select();
		$auditid=array();
		foreach ($receive_list as $k => $v) {
			$auditid[]=$v['audit_id'];
			if($v['audit_id']==$adminId['info']['id']){
				if($k!=0){
					 $key=($k-1);
					//说明我不是第一个人操作，需要判断我上一个人是否已经审核
					 if($receive_list[$key]['audit_status']<=3){
					 	//说明上一个人没有审核或者是审核没有通过
					 	$isfinish=1;
					 }
				}
				//判断自己是否已经审核过了
				if($v['audit_status']>2){
					//说明自己审核过了
					$isfinish=1;
				}
			}
		}
	if(!in_array($adminId['info']['id'],$auditid)){
		$isfinish=1;
	}
		// echo $isfinish;
	  // print_r($auditlist);
		$this->assign('list_dep',$list_dep);
		$this->assign('auditlist',$auditlist);
		$this->assign('list',$list);
		$this->assign('isfinish',$isfinish);
		$this->assign('adminId',$adminId['info']['id']);
		$this->assign('id',$id);
		$this->display();
	
}
	//详细页面
	public function detail(){
		$id=I('id');
		$goods_id=I('goods_id');

		$detaillist=M('receive_goods')->join('k_receive ON k_receive.r_id=k_receive_goods.receive_id')->join('k_base_goods ON k_receive_goods.goods_id=k_base_goods.id')->join('k_stock ON k_receive_goods.goods_id=k_stock.goods_id and k_receive_goods.s_id=k_stock.id')->join('k_depart ON k_depart.id=k_receive.depart')->where(array('receive_id'=>$id))->select();
			// print_r($detaillist);
		// //获取审核的人列表信息
		$auditlist=M('receive_audit')->join('k_admin ON audit_id=k_admin.id')->join('k_depart ON k_admin.depart_id=k_depart.id')->where(array('receive_id'=>$detaillist[0]['receive_id']))->select();
		// print_r($auditlist);
		$depart=M('depart')->find($detaillist[0]['depart']);
		$this->assign('depart',$depart['depart_name']);
		$this->assign('auditlist',$auditlist);
		$this->assign('detaillist',$detaillist);
		$this->display();
	}
	//添加
		public function add(){

		if(IS_POST){
			// $admin=session('admin_info');
			$admin=session('admin');
			$ni=I();
			$other=$ni['o'];
			unset($ni['o']);
			// print_r($ni);die;
			empty($ni[1]['id'])&&$this->error('未添加审核物品！');
			!$other['department_id']&&$this->error('未选择审核部门！');
			$time=$other['date']?strtotime($other['date']):time();
			if(is_array($other['department_id'])){
				//判断是否有重复的值
				if (count($other['department_id']) != count(array_unique($other['department_id']))) {   
				  $this->error('请不要选择重复的审核人');
				} 
			}
			if(empty($other['applyer'])){
				$this->error('申请人不能为空');
			}
			if(empty($other['depart'])){
				$this->error('领用部门不能为空');
			}
			if($admin['info']['is_supper']==1){
				$org_id=$other['org_id'];
			}else{
				$org_id=$admin['info']['org_id'];
			}
			$data=array(
				'order_sn'  =>$other['ordersn'],
				'apply_time'=>$time,
				'last_op_time'=>time(),
				'create_id' =>$admin['info']['id'],
				'status'    =>1,
				'applyer'=>$other['applyer'],
				'depart'=>$other['depart'],
				're_remark'=>$other['re_remark'],
				'total_price'=>$other['total_price'],
				'org_id'=>$org_id
			);
			// print_r($order);
			$M=M('receive');
			//创建申请单
			$orderID=$M->add($data);
			if (!$orderID) {
				$this->error('保存失败!');
			}
			$goods=array();
			$list=array();
			// print_r($ni);die;
			foreach ($ni as $key => $value) {
				if(isset($value['id'])){

					//判断领用的库存大小
					$dbNum=M('stock')->field('num,goods_id')->where(array('id'=>$value['id']))->find();
					if($dbNum['num']<$value['number']){
						$M->where(array('r_id'=>$orderID))->delete();
						$goodsName=M('base_goods')->field('assets_name')->where(array('id'=>$dbNum['goods_id']))->find();
						$this->error($goodsName['assets_name'].'领用数量最多为'.$dbNum['num']);
					}
				$list[]=$value['id'];
				$goods[]=array(
					'receive_id'=>$orderID,
					'goods_id'=>$dbNum['goods_id'],
					'receive_spec'  =>$value['spec'],
					'number' =>$value['number'],
					'price' =>$value['price'],
					'price_total' =>$value['price_total'],
					's_id'=>$value['id']
				);
			}
			}
			foreach($goods as $k=>$v){
					if(empty($v['goods_id'])){
						unset($goods[$k]);
					}
			}
			// print_r($goods);die;
			if(is_array($list)){
				//判断是否有重复的值
				if (count($list) != count(array_unique($list))) { 
				  $M->where(array('r_id'=>$orderID))->delete();
				  $this->error('请不要重复的物品');
				} 
			}
			//保存申请单物品
			$re=M('receive_goods')->addAll($goods);
			if (!$re) {
				$M->where(array('r_id'=>$orderID))->delete();
				$this->error('保存失败！');
			}	
			//保存审核人员
		 $users=array();
		 if(is_array($other['department_id'])){
		 		foreach ($other['department_id'] as $vd) {
				$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->where("depart_id=$vd and is_auditer=1")->getField('id');
				if (!$tuser) {
				  $M->where(array('r_id'=>$orderID))->delete();
					$this->error($department.' 没有审核人员!');
				}
				$users[]=array(
					'receive_id'=>$orderID,
					'audit_id'=>$tuser,
					'audit_status'=>1
				);
			}
			$re_audit=M('receive_audit')->addAll($users);
		 }else{
		 	$vd=$other['department_id'];
			$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->where("depart_id=$vd and is_auditer=1")->getField('id');

				if (!$tuser) {
				  $M->where(array('r_id'=>$orderID))->delete();
					$this->error($department.' 没有审核人员!');
				}
		 	$users[]=array(
					'receive_id'=>$orderID,
					'audit_id'=>$tuser,
					'audit_status'=>1
		 		);
		 	$re_audit=M('receive_audit')->addAll($users);
		 }
			if (!$re_audit) {
				$M->where(array('r_id'=>$orderID))->delete();
				M('receive_goods')->where(array('receive_id'=>$orderID))->delete();
				$this->error('保存失败！！');
			}

			if(count($users)>1){
					$users=array_shift($users);
					$uid=$users['audit_id'];
			}else{
				  $uid=$users[0]['audit_id'];
			}
		
			// print_r($users);die;
			$msg=array(
				'title'=>'您有领用申请审核信息，请前往查看。',
				'apply_id'=>$orderID, //申请单ID
				'send_id'=>$admin['info']['id'],
				'receive_id'=>$uid,
				'url'=>U('Receive/checkdetail',array('id'=>$orderID)),
				'send_time'=>time(),
				'type'=>30
			);
			//添加消息
			$re_msg=M('Message')->add($msg);
			if ($re_msg) {
				$this->success('保存成功！',U('index'));
			}else{
				$M->rollback();$this->error('保存失败！');
			}
		}else
		{
					$did=I('did','');

					$admin=session('admin');
					$c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
					if($admin['info']['is_supper']==1){

						$cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc');
			      // $list_dep=$cat->getList($did);
			      $listsde=$cat->getList($c_id);
			      //$list_dep=$cat->getChildren($did,$listsde);
					}else{
						// $where='pid='.;
						$cat=CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc');
			      $listsde=$cat->getList($c_id);
			    //  $list_dep=$cat->getChildren($admin['info']['org_id'],$listsde);
					}
					$this->assign('list_dep',$this->getDeparts($did));
					$this->assign('order_sn',createOrderSN(C('LYSN')));

					$timedate=time();
					$this->assign('timedate',$timedate);
					$is_supper=0;
					if($admin['info']['is_supper']==1){
							//超级管理员
						$is_supper=1;
						$departlist=M('depart')->where(array('pid'=>$c_id))->select();
						// print_r($departlist);
						$this->assign('departlist',$departlist);
					}
					$this->assign('is_supper',$is_supper);
					$this->assign('did',$did);
				$this->display();
			}
	}
	//ajax获取商品列表
	public function goodsList()
	{
		$assets_name=I('field');
		$value=I('value');
		$admin=session('admin');
		$admin=session('admin');

			if($admin['info']['is_supper']==1){
				//超级管理员
				$org_id=I('org_id');
			}else{
				$org_id=$admin['info']['org_id'];
			}
	
		$w='and 1=1 ';
		$w.='and s.org_id='.$org_id;
		// echo $w;
	
		$lists=M()
					->field('b.assets_name,s.org_id,s.id,p.company_name,s.spec,b.unit')
					->table('k_stock s,k_supplier p,k_base_goods b')
					->where("s.supplier_id=p.id and s.goods_id=b.id and s.supplier_id=p.id ".$w." and b.".$assets_name." LIKE '".$value."%'")
					->select();

		$this->ajaxReturn($lists);
	}
	private function getDeparts($topid=0)
    {
    	$this->admin=session('admin');
        if ($topid) {
            if ($this->admin['info']['is_supper']) {
                $org=$topid;
            }else{
                $org=$this->admin['info']['org_id'];
            }
        }else{
            $org=$this->admin['info']['org_id'];
        }

        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $lists = $cat->getList(0);
        
        if ($topid) {
            $De = $cat->getPath($topid);
            $De[0]['fullname'] = $De[0]['depart_name'];
            $Depart = $cat->getChildren($org,$lists) ;
            $Depart = array_merge($De,$Depart);

        }else{
            $Depart = $this->admin['info']['is_supper'] ? $lists : $cat->getChildren($org,$lists);
        }
        return $Depart;
    }
}