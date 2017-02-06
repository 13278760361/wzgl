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
class LowWeixiuController extends BaseController
{
 public $outlist=array('id'=>'序号','card_sn'=>'卡片单号','assets_name'=>'名称','spec'=>'规格型号','department'=>'部门','unit'=>'单位','keeper'=>'保管','store_address'=>'存放地点','reason'=>'维修原因','apply_time'=>'维修日期','original_value'=>'金额','situation'=>'维修完成情况');
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
		$card_sn=trim(I('cartname'));
		$endtime=strtotime(I('endtime'));
		$starttime=strtotime(I('starttime'));
		$category=I('category');
		$department=trim(I('department'));
		$assets_name=trim(I('assets_name'));
		$Smobile=trim(I('Smobile'));
		$admin=session('admin');
		$type_id = I('type_id');
		if($admin['info']['is_supper']==1){
			//超级管理员
			$cat=CAT('Depart',array('id','pid','depart_name'));
      $list_dep=$cat->getList(0);
		}else{
			// $where='pid='.;
			$cat=CAT('Depart',array('id','pid','depart_name'));
      $lists=$cat->getList(0);
      $list_dep=$cat->getChildren($admin['info']['org_id'],$lists);
      $w.=' and k_cards.org_id='.$admin['info']['org_id'];
		}
		if($endtime<$starttime){
			$this->error('结束时间不能小于开始时间');
		}
		if($category){
			$w.=' and cate_id="'.$category.'"';
		}
		if($card_sn){
			$w.=' and card_sn like"%'.$card_sn.'%"';
		}
		if($department){
			$w.=' and department='.$department;
		}
		if($assets_name){
			$w.=' and assets_name like "%'.$assets_name.'%"';
		}
		if($endtime&&$starttime){
			$w.=' and last_op_time >='.$starttime.' and last_op_time <='.$endtime;
		}else{
			$endtime=strtotime("+1 day");
			$starttime=strtotime("-1 month");
		}

		if($type_id){
            $w.=" AND k_base_goods.type_id='{$type_id}'";
        }
        if(I('cate_id')){
            $w.=" AND k_base_goods.cate_id='".I('cate_id')."'";
        }
		if($Smobile){
			$w.=" AND (assets_name LIKE '%{$Smobile}%' OR department = '{$Smobile}' OR card_sn LIKE '%{$Smobile}%')";
		}
			$w.=' and (k_repair.status=4 or k_repair.status=3)';
		// echo $w;
			$w.=' and k_base_goods.type_id=2';
		$size=22;
		
  	$count = M('repair')->join('k_repair_goods ON r_id=card_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->join('k_depart ON k_cards.department=k_depart.id')->where($w)->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show= $Page->show();// 分页显示输出

$list=M('repair')->join('k_repair_goods ON r_id=card_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->join('k_depart ON k_cards.department=k_depart.id')->where($w)->order('repeat_time asc')->limit($Page->firstRow.','.$Page->listRows)
->select();

		$s_total = M('repair')
		           ->join('k_repair_goods ON r_id=card_id')
		           ->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')
		           ->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')
		           ->join('k_category ON k_base_goods.cate_id=k_category.id')
		           ->join('k_depart ON k_cards.department=k_depart.id')
		           ->where($w)->order('repeat_time asc')->getField('SUM(original_value) as num');
		// print_r($list);
		$p_total = 0;
		foreach ($list as $key => $value) {
			# code...
			$p_total+=$value['original_value'];
		}
		if($type_id){
            $cat2=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
            $this->assign('cateS',$cat2->getList(0));
        }
		$this->assign('p_total',$p_total);
        $this->assign('s_total',$s_total?$s_total:0);
		$this->assign('list',$list);
		//获取分类信息
		$catelist=M('category')->select();
		$this->assign('catelist',$catelist);
		$this->assign('page',$show);
		$this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',I('cate_id'));
        
		$this->assign('cate_id',$category);
		$this->assign('card_sn',$card_sn);
		$this->assign('starttime',$starttime);
		$this->assign('endtime',$endtime);
		$this->assign('sorter',$sorter);
		$this->assign('department',$department);
		$this->assign('assets_name',$assets_name);
		$this->assign('Smobile',$Smobile);
		$this->assign('list_dep',$list_dep);
		$this->assign('adminid',$admin['info']['id']);
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
            $w.=" AND (assets_name LIKE '%{$key}%' OR department LIKE '%{$key}%' OR card_sn LIKE '%{$key}%')";
        }
        	$w.=' and (k_repair.status=4 or k_repair.status=3) and k_base_goods.type_id=2';
        $list=M('repair')->join('k_repair_goods ON r_id=card_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->join('k_depart ON k_depart.id=k_cards.department')->where($w)->order('repeat_time asc')->limit($first,$len)
					->select();
        foreach($list as $k=>$v){
        	if($v['situation']==''){
        		$list[$k]['situation']='';
        	}
        	if($v['depart_name']==''){
        		$list[$k]['depart_name']='';
        	}
        	if($v['store_address']==''){
        		$list[$k]['store_address']='';
        	}
        	$list[$k]['url']='<a href="'.U('detail',array('id'=>$v['r_id'])).'">'.$v['card_sn'].'</a>';
        }
        $this->ajaxReturn($list);
    }
  //导出数据
    public function export()
    {
  				//参数筛选
		$w='1=1 ';
		$len=I('size')?I('size'):10;
		$page=I('page');
		$first=$len*($page-1);
		$card_sn=trim(I('cartname'));
		$endtime=strtotime(I('endtime'));
		$starttime=strtotime(I('starttime'));
		$category=I('category');
		$department=trim(I('department'));
		$assets_name=trim(I('assets_name'));
		if($endtime<$starttime){
			$this->error('结束时间不能小于开始时间');
		}
		if($category){
			$w.='and cate_id="'.$category.'"';
		}
		if($card_sn){
			$w.='and card_sn="'.$card_sn.'"';
		}
		if($department){
			$w.='and department like "%'.$department.'%"';
		}
		if($assets_name){
			$w.='and assets_name like "%'.$assets_name.'%"';
		}
		if($endtime&&$starttime){
			$w.='and last_op_time >='.$starttime.' and last_op_time <='.$endtime;
		}else{
			$endtime=strtotime("+1 day");
			$starttime=strtotime("-1 day");
		}
		$w.=' and (k_repair.status=4 or k_repair.status=3)';
		$w.=' and type_id=2';
			$lists=M('repair')->field('card_sn,spec,time,assets_name,department,unit,keeper,store_address,reason,original_value,situation')->join('k_repair_goods ON r_id=card_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->where($w)->limit($first,$len)->order('repeat_time asc')->select();
				foreach($lists as $k=>$v)
        {
        	$lists[$k]['id'] = $k+1;
            $lists[$k]['apply_time'] = date('Y/m/d',$v['time']);
        }
        return $lists;
    }
	//详细信息页面
	public function detail(){
		$id=I('id');
		$list=M('repair_goods')->join('k_repair ON k_repair_goods.card_id=k_repair.r_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->join('k_depart ON k_cards.department=k_depart.id')->where(array('card_id'=>$id))->select();
		//获取审核的人列表信息
		$auditlist=M('repair_audit')->join('k_admin ON audit_id=k_admin.id')->join('k_depart ON k_admin.depart_id=k_depart.id')->where(array('repair_id'=>$id,'is_people'=>1))->select();
		// print_r($list);
		$this->assign('auditlist',$auditlist);
		$this->assign('list',$list);
		$this->display();
	}
	public function edit(){
		$id=I('id');
		if(IS_POST){
			//先删除该订单信息
			$admin=session('admin');
			// $admin=1;
			$ni=I();
			
			$other=$ni['o'];
			unset($ni['o']);
			!$ni&&$this->error('未添加审核物品！');
			!$other['department_id']&&$this->error('未选择审核部门！');
			if(is_array($other['department_id'])){
				//判断是否有重复的值
				if (count($other['department_id']) != count(array_unique($other['department_id']))) {   
				  $this->error('请不要选择重复的审核人');
				} 
			}
			//申请人不能作为审核人
			$admindepart=M('admin')->field('depart_id')->where(array('id'=>$admin['info']['id']))->find();
			if(is_array($other['department_id'])){
				if(in_array($admindepart['depart_id'],array_unique($other['department_id']))){
							$this->error('您是申请人，请不要选择自己作为审核人');
						}
			}else{
				if($admindepart['depart_id']==$other['department_id']){
							$this->error('您是申请人，请不要选择自己作为审核人');
						}
			}
			$time=$other['date']?strtotime($other['date']):time();
			$goods=array();
			$list=array();
				foreach ($ni as $key => $value) {
				if(!empty($value['time'])){
				$is_date=strtotime($value['time'])?strtotime($value['time']):false;
				if($is_date==false){
					$this->error('时间日期错误');
				}
				}
				$list[]=$value['order_sn'];
					$goods[]=array(
					'card_id'=>$id,
					'card_sn'=>$value['order_sn'],
					'money'  =>$value['original_value'],
					'reason' =>$value['reason'],
					'time'   =>strtotime($value['time'])
				);
		
				
			}
			if(is_array($list)){
				//判断是否有重复的值
				if (count($list) != count(array_unique($list))) { 
				  
				  $this->error('请不要重复添加卡片');
				} 
			}
			foreach($goods as $k=>$v){
					if(empty($v['money'])){
						unset($goods[$k]);
					}
			}
			// print_r($goods);die;
			//保存申请单物品
			M('repair_goods')->where(array('card_id'=>$id))->delete();
			$re=M('repair_goods')->addAll($goods);
			if (!$re) {
				$M->where(array('r_id'=>$id))->delete();
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
					'repair_id'=>$id,
					'audit_id'=>$tuser,
					'audit_status'=>1,
					'is_people'=>1
				);
			}
		}else{
			$vd=$other['department_id'];
			$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->where("depart_id=$vd and is_auditer=1")->getField('id');
				if (!$tuser) {
					$this->error($department.' 没有审核人员!');
				}
			$users[]=array(
					'repair_id'=>$id,
					'audit_id'=>$tuser,
					'audit_status'=>1,
					'is_people'=>1
				);
		}
			//追加申请人放到数组最后
			$users[]=array(
					'repair_id'=>$id,
					'audit_id'=>$admin['info']['id'],
					'audit_status'=>1,
					'is_people'=>2
				);
			// array_push($users,$userShengren);
			// print_r($users);
			M('repair_audit')->where(array('repair_id'=>$id))->delete();
			$re_audit=M('repair_audit')->addAll($users);
			if (!$re_audit) {
				$M->where(array('r_id'=>$id))->delete();
				M('repair_goods')->where(array('card_id'=>$id))->delete();
				$this->error('保存失败！');
			}else{
				$this->success('编辑成功',U('check'));
			}

		}
		$admin=session('admin');
		//先检查该维修单是否是编辑人添加的
		$isCheck=M('repair_audit')->where(array('repair_id'=>$id,'is_people'=>2))->find();
		if($isCheck['audit_id']!=$admin['info']['id']){
			//说明没有编辑权限
			$this->error('该维修单不是您添加的，没有编辑权限');
		}
		$list=M('repair_goods')->join('k_repair ON k_repair_goods.card_id=k_repair.r_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->join('k_depart ON k_cards.department=k_depart.id')->where(array('card_id'=>$id))->select();
		//获取审核的人列表信息
		$auditlist=M('repair_audit')->join('k_admin ON audit_id=k_admin.id')->join('k_depart ON k_admin.depart_id=k_depart.id')->where(array('repair_id'=>$id,'is_people'=>1))->select();
		$this->assign('auditlist',$auditlist);
		// print_r($auditlist);
		$this->assign('list',$list);
		$cat=CAT('Depart',array('id','pid','depart_name'));
    $list_dep=$cat->getList($list[0]['org_id']);
    // print_r($list_dep);
		$this->assign('list_dep',$list_dep);
		//判断是否可以编辑
		$audit=M('repair_audit')->where(array('repair_id'=>$id,'is_people'=>1))->find();
		$isReadyChecked=2;
		if($audit['audit_status']==3||$audit['audit_status']==4){
			//说明第一个人审核了
			$isReadyChecked=1;
		}
		$did=$list[0]['org_id'];
		// print_r($did);
		$this->assign('isReadyChecked',$isReadyChecked);
		$this->assign('did',$did);
		$this->assign('id',$id);
		$this->display();
	}
	//添加方法
	public function add(){

		if(IS_POST){
			$admin=session('admin');

			// $admin=1;
			$ni=I();
			$other=$ni['o'];
			unset($ni['o']);
			!$ni&&$this->error('未添加审核物品！');
			!$other['department_id']&&$this->error('未选择审核部门！');
			if(is_array($other['department_id'])){
				//判断是否有重复的值
				if (count($other['department_id']) != count(array_unique($other['department_id']))) {   
				  $this->error('请不要选择重复的审核人');
				} 
			}
			//申请人不能作为审核人
			$admindepart=M('admin')->field('depart_id')->where(array('id'=>$admin['info']['id']))->find();
			if(is_array($other['department_id'])){
				if(in_array($admindepart['depart_id'],array_unique($other['department_id']))){
							$this->error('您是申请人，请不要选择自己作为审核人');
						}
			}else{
				if($admindepart['depart_id']==$other['department_id']){
							$this->error('您是申请人，请不要选择自己作为审核人');
						}
			}
			$time=$other['date']?strtotime($other['date']):time();
			if($admin['info']['is_supper']==1){
				//超级管理员操作
				$org_id=$other['org_id'];
				if(empty($org_id)){
					$this->error('请选择添加的部门');
				}
			}else{
				$org_id=$admin['info']['org_id'];
			}
			$data=array(
				'repeat_sn'  =>$other['ordersn'],
				'repeat_time'=>$time,
				'last_op_time'=>time(),
				'creater_id' =>$admin['info']['id'],
				'creater' =>$admin['info']['username'],
				'org_id' =>$org_id,
				'status'    =>1
			);
			// print_r($order);
			$M=M('repair');
			//创建申请单
			$id=$M->add($data);
			if (!$id) {
				$this->error('保存失败！');
			}
			$goods=array();
			$list=array();
			// print_r($ni);die;
			foreach ($ni as $key => $value) {
				if($value['time']){
					$is_date=strtotime($value['time'])?strtotime($value['time']):false;
					if($is_date==false){
					$M->where(array('r_id'=>$id))->delete();
					$this->error('维修日期格式错误');
				}
				}
				$list[]=$value['order_sn'];
				$goods[]=array(
					'card_id'=>$id,
					'card_sn'=>$value['order_sn'],
					'money'  =>$value['original_value'],
					'reason' =>$value['reason'],
					'time'   =>strtotime($value['time'])
				);
			}
			// print_r($goods);die;
			foreach($goods as $k=>$v){
					if(empty($v['money'])){
						unset($goods[$k]);
					}
			}
			if(is_array($list)){
				//判断是否有重复的值
				if (count($list) != count(array_unique($list))) { 
				  $M->where(array('r_id'=>$id))->delete();  
				  $this->error('请不要重复添加卡片');
				} 
			}
			// print_r($goods);die;
			//保存申请单物品
			//保存申请单物品
			if(isset($goods[0])){
				$re=M('repair_goods')->addAll($goods);
			}else{
				$re=M('repair_goods')->add($goods[1]);
			}
			if (!$re) {
				$M->where(array('r_id'=>$id))->delete();
				$this->error('保存失败！');
			}	
			//保存审核人员
		 $users=array();
		 if(is_array($other['department_id'])){
			foreach ($other['department_id'] as $vd) {
				$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->where("depart_id=$vd and is_auditer=1")->getField('id');
				if (!$tuser) {
					$M->where(array('r_id'=>$id))->delete();
					M('repair_goods')->where(array('card_id'=>$id))->delete();
					$this->error($department.' 没有审核人员!');
				}
				$users[]=array(
					'repair_id'=>$id,
					'audit_id'=>$tuser,
					'audit_status'=>1,
					'is_people'=>1
				);
			}
		}else{
			$vd=$other['department_id'];
			$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->where("depart_id=$vd and is_auditer=1")->getField('id');
				if (!$tuser) {
					$M->where(array('r_id'=>$id))->delete();
					M('repair_goods')->where(array('card_id'=>$id))->delete();
					$this->error($department.' 没有审核人员!');
				}
			$users[]=array(
					'repair_id'=>$id,
					'audit_id'=>$tuser,
					'audit_status'=>1,
					'is_people'=>1
				);
		}
			//追加申请人放到数组最后
			$users[]=array(
					'repair_id'=>$id,
					'audit_id'=>$admin['info']['id'],
					'audit_status'=>1,
					'is_people'=>2
				);
			// array_push($users,$userShengren);
			// print_r($users);
			$re_audit=M('repair_audit')->addAll($users);
			if (!$re_audit) {
				$M->where(array('r_id'=>$id))->delete();
				M('repair_goods')->where(array('card_id'=>$id))->delete();
				$this->error('保存失败！');
			}
			$receive=array_shift($users);
			$msg=array(
				'title'=>'你有一条资产维修申请审核信息，请前往查看。',
				'apply_id'=>$id, //申请单ID
				'send_id'=>$admin,
				'receive_id'=>$receive['audit_id'],
				'url'=>U('Weixiu/checkdetail',array('id'=>$id)),
				'send_time'=>time(),
				'type'=>85
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
			$did=I('get.did','');
			$card_id=I('card_id');
			//dump($did);exit;
					if($card_id>0){
			$lists=M()
					->field('c.id,c.order_sn,c.store_address,c.spec,c.keeper,d.depart_name,c.original_value,bg.assets_name,bg.unit,cat.cate_name')
					->table('k_cards c,k_base_goods bg,k_category cat,k_depart d')
					->where("c.goods_id=bg.id and bg.cate_id=cat.id and c.department=d.id and c.id={$card_id}")
					->select();
					}
					$admin=session('admin');
					$c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
            // $cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
            // $top_departs = $cat->getList($c_id);
					if($admin['info']['is_supper']==1){
						$cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc');
			      // $list_dep=$cat->getList($did);
			            $listsde=$cat->getList($c_id);
			    //  $list_dep=$cat->getChildren($did,$listsde);
					}else{
						// $where='pid='.;
						$cat=CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc');
			      $listsde=$cat->getList($c_id);
			     // $list_dep=$cat->getChildren($admin['info']['org_id'],$listsde);
					}
					// print_r($list_dep);
					
					$this->assign('list_dep',$this->getDeparts($did));
					$this->assign('order_sn',createOrderSN(C('WXSN')));

					$this->assign('lists',$lists);
					$this->assign('card_id',$card_id);
					$this->assign('from',I('from'));
					$timedate=time();
					$this->assign('timedate',$timedate);
					$admin=session('admin');
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
	/**
	 * ajax获取物品
	 * @return [type] [description]
	 */
	public function goodsList()
	{
		$order_sn=I('field');
		$value=I('value');
		$admin=session('admin');

			if($admin['info']['is_supper']==1){
				//超级管理员
				$org_id=I('org_id');
			}else{
				$org_id=$admin['info']['org_id'];
			}
		$w=' and 1=1 ';
		$w.='and c.org_id='.$org_id;
		// $w.='and bg.type_id=3';
		// echo $w;
		$lists=M()
					->field('c.id,c.order_sn,c.store_address,c.spec,c.keeper,de.depart_name,c.original_value,bg.assets_name,bg.unit,cat.cate_name,c.spec')
					->table('k_cards c,k_base_goods bg,k_category cat,k_depart de')
					->where("c.goods_id=bg.id and bg.cate_id=cat.id and c.department=de.id ".$w." and c.".$order_sn." LIKE '".$value."%' and bg.type_id=2")->limit(20)
					->select();
		$this->ajaxReturn($lists);
	}
	//返回签名
	public function ajaxSignature(){
		$id=I('uid');
		$list=M('admin')->where(array('depart_id'=>$id))->find();
		echo $list['signature'];die;
	}
	//审核详细页面
	public function checkdetail(){
		//分配管理员账号 判断是否有权限操作审核记录信息
		// $adminId=1;
		$adminId=session('admin');
		// echo $adminId['info']['id'];
		$id=I('id','intval');
		//检查该登录的管理员是否已经更改了消息状态，只能改下最后一个消息的申请人信息
		$msgList=M('message')->where(array('apply_id'=>$id))->select();
	  $repairinfo=M('repair')->where(array('r_id'=>$id))->find();
		if($msgList[count($msgList)-1]['receive_id']==$adminId['info']['id']&&($repairinfo['status']==3||$repairinfo['status']==4)){
			// echo '11'; 
			if($msgList[count($msgList)-1]['status']==1){
				//说明是最后一个信息 是申请人的消息
				M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
			}
		}
		
		$detaillist=M('repair_goods')->join('k_repair ON k_repair_goods.card_id=k_repair.r_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->join('k_depart ON k_cards.department=k_depart.id')->where(array('k_repair_goods.card_id'=>$id))->select();
		//获取审核的人列表信息
		$auditlist=M('repair_audit')->join('k_admin ON audit_id=k_admin.id')->join('k_depart ON k_admin.depart_id=k_depart.id')->where(array('repair_id'=>$id,'is_people'=>1))->select();
		//判断用户是否具有操作的权限
		$isfinish=2;
		
		if($repairinfo['status']>2){
			//说明订单申请处理完毕啦，判断维修人员是否已经填写了维修情况
			if(!empty($repairinfo['situation'])&&!empty($repairinfo['r_money'])){
				$isfinish=1;
			}  
		}

//判断登录人是否有操作权限,1:如果上一个审核人没有审核，则没有审核权限，2：如果自己审核过了，则没有审核权限
		$repair_list=M('repair_audit')->where(array('repair_id'=>$id))->select();
		// print_r($repair_list);
		$auditid=array();
		foreach ($repair_list as $k => $v) {
			$auditid[]=$v['audit_id'];
			if($v['audit_id']==$adminId['info']['id']){

				if($k!=0){
					 $key=($k-1);
					//说明我不是第一个人操作，需要判断我上一个人是否已经审核
					 if($repair_list[$key]['audit_status']<=3){
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
		// print_r($auditid);
		if(!in_array($adminId['info']['id'],$auditid)){
			$isfinish=1;
		}

		$lastaudit=M('repair_audit')->where(array('repair_id'=>$id,'is_people'=>2))->find();
		// echo $isfinish;
		// echo $lastaudit['audit_id'];
		// echo $adminId['info']['id'];
		

		$this->assign('auditlist',$auditlist);
		$this->assign('lastaudit',$lastaudit['audit_id']);
		$this->assign('detaillist',$detaillist);
		$this->assign('adminId',$adminId['info']['id']);
		$this->assign('card_sn',$id);
		$this->assign('isfinish',$isfinish);

		$this->display();
	}
	//审核人员提交审核的结果 
	//审核人员提交审核的结果 
public function checkaction(){
	// $adminId=1;
	$adminId=session('admin');
	$yijian=I('yijian');
	$id=I('card_sn');
	$card_sn=I('card_sn');
	$agreeRadio=I('agreeRadio');
	$datetime=strtotime(I('datetime'));
	// $agreeRadio=$agreeRadio=='不同意'?3:4;
	$data=array(
			'audit_status'=>$agreeRadio,
			'yijian'=>$yijian,
			'audit_time'=>$datetime
		);
	$arr=array(
		'situation'=>I('situation'),
		'r_money'=>I('money')
		);
	//判断是审核员操作还是申请人操作
	$result=M('repair_audit')->where(array('repair_id'=>$id,'audit_id'=>$adminId['info']['id']))->find();
	// print_r($result);
	if($result['is_people']==1){
		//审核人员操作
		//判断填写的数据是否为空
		if(!$data['audit_status']){
			$this->error('请选择是否同意');
		}
		if(!$data['yijian']){
			$this->error('请填写意见');
		}
		if(!$data['audit_time']){
			$this->error('请填写日期');
		}

			$res=M('repair_audit')->where(array('repair_id'=>$id,'audit_id'=>$adminId['info']['id']))->save($data);

	}else{
		//申请人员操作，主要填写维修完成情况和维修费用
		//判断填写的数据是否为空
		if(!$arr['situation']){
			$this->error('维修情况不能为空');
		}
		if(!$arr['r_money']){
			$this->error('维修金额不能为空');
		}
		$res=M('repair')->where(array('r_id'=>$result['repair_id']))->save($arr);
		M('repair_audit')->where(array('repair_id'=>$id,'audit_id'=>$adminId['info']['id']))->save(array('audit_status'=>4,'audit_time'=>time()));
	}

	if($res){
		//审核成功了，更新最后的操作时间
		M('repair')->where(array('r_id'=>$id))->save(array('last_op_time'=>time()));
		$repearinfo=M('repair')->where(array('r_id'=>$id))->find();
		$shengqingren=M('repair_audit')->where(array('is_people'=>2,'repair_id'=>$id))->find();
		$resuser=M('repair_audit')->where(array('is_people'=>2,'repair_id'=>$id))->find();
		$url=U('Weixiu/checkdetail',array('id'=>$id));
		//判断提交的审核是否同意，如果是不同意，则返回申请人去并把该审核订单作废
		if($agreeRadio==3){
			//说明该审核人不同意，则直接通知申请人员，其他人员不做审核处理
			M('repair')->where(array('r_id'=>$id))->save(array('status'=>3));
			//通知申请人信息
					M('repair')->where(array('r_id'=>$result['repair_id']))->save(array('status'=>3));
						//最后一个审核人审核了 然后再到申请人
						$datamsg=array(
							'title'=>'审核失败，如有需要，请重新申请',
							'apply_id'=>$repearinfo['r_id'],
							'send_id'=>$adminId['info']['id'],
							'receive_id'=>$resuser['audit_id'],
							'status'=>1,
							'url'=>$url,
							'send_time'=>time(),
							'type'=>85
							
						);
						//更改该审核人的消息状态
						M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
					M('message')->add($datamsg);
		}
		if($agreeRadio==4){
			//说明该审核人通过了审核，则继续下一位审核人员处理，并且需要判断当前审核人是不是申请人
			//更改维修单状态，如果是第一个审核审核  状态就是在审核中 如果是最后一个审核人(非申请人)则是审核通过
			$aulist=M('repair_audit')->where(array('is_people'=>1,'repair_id'=>$id))->select();
			
			foreach($aulist as $k=>$v){
				if($v['audit_id']==$adminId['info']['id']){
					
					if($k==0){
						//第一个审核人审核,状态为审核中
						M('repair')->where(array('r_id'=>$result['repair_id']))->save(array('status'=>2));
					}
					if($k==(count($aulist)-1)){
						//最后一个审核人
						M('repair')->where(array('r_id'=>$result['repair_id']))->save(array('status'=>4));

						//最后一个审核人审核了 然后再到申请人
						$datamsg=array(
							'title'=>'审核通过，请及时填写维修完成情况和维修费',
							'apply_id'=>$repearinfo['r_id'],
							'send_id'=>$adminId['info']['id'],
							'receive_id'=>$resuser['audit_id'],
							'status'=>1,
							'url'=>$url,
							'send_time'=>time(),
							'type'=>85
						);
							M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
					M('message')->add($datamsg);
						
					}
					if($k!=(count($aulist)-1)){
						//下一个人审核

					// M('repair_goods')->where()->find
					
					//获取下一位审核人
					$nkey=($k+1);
					$nexaud=$aulist[$nkey]['audit_id'];
					$datamsg=array(
							'title'=>'有维修单需要您审核，请及时处理',
							'apply_id'=>$repearinfo['r_id'],
							'send_id'=>$adminId['info']['id'],
							'receive_id'=>$nexaud,
							'status'=>1,
							'url'=>$url,
							'send_time'=>time(),
							'type'=>85
						);
						M('message')->where(array('apply_id'=>$id,'receive_id'=>$adminId['info']['id']))->save(array('status'=>2));
					M('message')->add($datamsg);
					}
					
				}
			}

		}
		$this->success('审核成功',-1);
	}else{
			$this->error('审核失败');
	}

}
		//ajax分页
		public function ajaxcheck(){
			$adminid=session('admin')['info']['id'];
			  $page=I('get.page');
			  $status=I('get.status');
        $len=I('get.size')?:2;
        $first=$len*($page-1);
        $w="1=1";
				if(empty($status)){
						$w.='and k_repair.status=1';
						$status=1;
				}else{
						switch ($status) {
							case '3':
							$w.=' and k_repair.status=3 or k_repair.status=4';
							break;
							case '4':
							break;
							default:
							$w.=' and k_repair.status='.$status;
							break;
						}
				}
				$w.=' and k_base_goods.type_id=2';
					$list=M('repair')->join('k_repair_goods ON r_id=card_id')->join('k_cards ON k_repair_goods.card_sn=k_cards.order_sn')->join('k_base_goods ON k_cards.goods_id=k_base_goods.id')->join('k_category ON k_base_goods.cate_id=k_category.id')->where($w)->order('repeat_time asc')->limit($first,$len)->select();
        foreach($list as $k=>$v){
        	$list[$k]['url']='<a href="'.U('checkdetail',array('id'=>$v['card_id'])).'" class="audit_already">'.$v['repeat_sn'].'</a>';
        	if($v['status']==1){
        			$list[$k]['statusinfo']='<a class="audit_begin" href="'.U('checkdetail',array('id'=>$v['card_id'])).'">未审核</a>';
        	}
        	if($v['status']==2){
        			$list[$k]['statusinfo']='<a class="audit_begin" href="'.U('checkdetail',array('id'=>$v['card_id'])).'">审核中</a>';
        	}
        	if($v['status']==3){
        			$list[$k]['statusinfo']='<a class="audit_notpass" href="'.U('checkdetail',array('id'=>$v['card_id'])).'">未通过</a>';
        	}
        	if($v['status']==4){
        			$list[$k]['statusinfo']='<a class="audit_already" href="'.U('checkdetail',array('id'=>$v['card_id'])).'">已审核</a>';
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
	//审核状态列表
	public function check(){
		//数据筛选
		$w='1=1 ';
		$creater=trim(I('creater'));
		$time=strtotime(I('time'));
		$department=I('department');
		$repeat_sn=I('repeat_sn');
		$status=I('status');
		$admin=session('admin');
		if($admin['info']['is_supper']==1){
			//超级管理员
			$cat=CAT('Depart',array('id','pid','depart_name'));
      $list_dep=$cat->getList(0);
		}else{
			// $where='pid='.;
			$cat=CAT('Depart',array('id','pid','depart_name'));
      $lists=$cat->getList(0);
      $list_dep=$cat->getChildren($admin['info']['org_id'],$lists);
      $w.=' and c.org_id='.$admin['info']['org_id'];
		}
		if($creater){
			$w.=' and creater like "%'.$creater.'%"';
		}
		if($time){
			$w.=' and repeat_time="'.$time.'"';
		}else{
			$time=time();
		}
		if($department){
			$w.=' and department='.$department;
		}
		if($repeat_sn){
			$w.=' and repeat_sn like "%'.$repeat_sn.'%"';
		}

		if(empty($status)){
			// $w.=' and status=1';
			$status=4;
		}else{
			switch ($status) {
				case '3':
				$w.=' and (status=3 or status=4)';
					break;
				case '4':
				
					break;
				default:
					$w.=' and status='.$status;
					break;
			}
			
		}

		$w.=' and b.type_id=2';
		$w.=' and r.r_id=g.card_id and g.card_sn=c.order_sn and c.goods_id=b.id';
		// echo $w;
		$size=22;
  	$count = M()->table('k_repair r,k_repair_goods g,k_cards c,k_base_goods b')->where($w)->group('r.repeat_sn')->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show= $Page->show();// 分页显示输出
// 1未审核  2审核中 3未通过  4通过

		$list=M()->table('k_repair r,k_repair_goods g,k_cards c,k_base_goods b')->where($w)->group('r.repeat_sn')->limit($Page->firstRow.','.$Page->listRows)->select();
		// print_r($list);
		$this->assign('list',$list);
		$this->assign('status',$status);
		$this->assign('page',$show);
		$this->assign('creater',$creater);
		$this->assign('time',$time);
		$this->assign('department',$department);
		$this->assign('repeat_sn',$repeat_sn);
		$this->assign('list_dep',$list_dep);

		$this->assign('adminid',$admin['info']['id']);
		$this->display();
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
        //dump($topid);exit;
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