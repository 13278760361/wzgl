<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: Mr.czs (3032444149@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-28
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class BaoxiaoController extends BaseController
{
	public $outlist=array('id'=>'序号','charge'=>'单位负责人','total'=>'金额','department'=>'使用单位','prove'=>'保管验收或证明','user'=>'经报人','time'=>'报销日期');
	//列表页面
	public function index(){
		$admin=session('admin');

		$w='1=1 ';
		$department=trim(I('department'));
		if($department){
			$w.='and department='.$department;
		}
		$user=trim(I('user'));
		if($user){
			$w.=' and user like "%'.$user.'%"';
		}
		$time=strtotime(I('time'));
		if($time){
			$w.=' and time ="'.$time.'"';
		}else{
			$time=time();
		}
		if($admin['info']['is_supper']==1){
			//超级管理员
			$cat=CAT('Depart',array('id','pid','depart_name'));
      $list_dep=$cat->getList(0);
		}else{
			// $where='pid='.;
			$cat=CAT('Depart',array('id','pid','depart_name'));
      $lists=$cat->getList(0);
      $list_dep=$cat->getChildren($admin['info']['org_id'],$lists);
      $w.=' and org_id='.$admin['info']['org_id'];
		}
		
		$w.=' and type_id=3';
		$size=22;
  	$count = M('expaccount')->where($w)->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show= $Page->show();// 分页显示输出
		$list=M('expaccount')->field('k_expaccount.id as e_id,k_expaccount.*,k_depart.*')->join('k_depart ON k_depart.id=k_expaccount.department')->order('time')->where($w)->limit($Page->firstRow.','.$Page->listRows)->select();
		// print_r($list);
		$this->assign('list',$list);
		$this->assign('department',$department);

		$this->assign('show',$show);
		$this->assign('user',$user);

		$this->assign('timedate',$time);
		
		$this->assign('list_dep',$list_dep);
		$this->assign('adminId',$admin['info']['id']);
		$this->display();
	}
	//编辑
	public function edit(){
		$id=I('id');
		
		if(IS_POST){
			//先把该订单全部删除 然后再添加
			$data=array(
				'time'=>strtotime(I('time')),
				'department'=>I('department'),
				'total'=>I('total'),
				'total_capital'=>I('total_capital'),
				'topic'=>I('topic'),
				'cash'=>I('cash'),
				'bank'=>I('bank'),
				'transfer'=>I('transfer'),
				'signature'=>I('signature'),
				'charge'=>I('charge'),
				'prove'=>I('prove'),
				'user'=>I('user'),
				'opinion'=>I('opinion')
			);
		
		  if(empty(I('department'))){
				$this->error('使用单位不能为空');
			}
			if(empty(I('total'))){
				$this->error('金额合计不能为空');
			}
			if(empty(I('charge'))){
				$this->error('单位负责人不能为空');
			}
			if(empty(I('user'))){
				$this->error('经报人不能为空');
			}
			if(empty(I('prove'))){
				$this->error('保管验收或证明不能为空');
			}
		  $useto=I('useto');
		  if(empty($useto[0])){
		  	$this->error('用途不能为空');
		  }

			$amount=I('amount');
			if(empty($amount[0])){
		  	$this->error('单据张数不能为空');
		  }
			$money=I('money');
			if(empty($money[0])){
		  	$this->error('金额不能为空');
		  }
			if(count($useto)!=count($amount)||count($useto)!=count($money)||count($amount)!=count($money)){
				$this->error('单据张数/用途/金额数据缺损');
			}
			//更新
		$res=M('expaccount')->where(array('id'=>$id))->save($data);

			//先删除该列表信息 然后再全部添加
			M('expaccountDetial')->where(array('account_id'=>$id))->delete();
			$arrlist=array();
			foreach ($useto as $k => $v) {
				if(isset($v)){
						$arrlist[$k]=array(
							'amount'=>$amount[$k],
							'money'=>$money[$k],
							'useto'=>$v,
							'account_id'=>$id
							);
					}
					
			}

			foreach ($arrlist as $kk => $vv) {
				if(!empty($vv['useto'])){
					$flag=M('expaccountDetial')->add($vv);
				}
				
			}
			if($flag){
				$this->success('操作成功',U('index'));
			}else{
				$this->error('操作失败');
			}
			
		
		}
		$detail=M('expaccount')->join('k_depart ON k_depart.id=k_expaccount.department')->where(array('k_expaccount.id'=>$id))->find();
		$list=M('expaccountDetial')->where(array('account_id'=>$id))->select();
		// print_r($detail);
		$this->assign('detail',$detail);
		$this->assign('list',$list);
		$this->assign('id',$id);
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
	      
			}
			if($detail['user_id']!=$admin['info']['id']){
	      		$this->error('对不起！您没有编辑权限');
	      }
		$this->assign('depart',$list_dep);
		$this->display();
	}
	//导出数据
    public function export(){
  	$w='1=1 ';
  	$len=I('size')?I('size'):10;
		$page=I('page');
		$first=$len*($page-1);
		$department=trim(I('department'));
		if($department){
			$w.='and department like "%'.$department.'%"';
		}
		$user=trim(I('user'));
		if($user){
			$w.=' and user like "%'.$user.'%"';
		}
		$time=strtotime(I('time'));
		if($time){
			$w.=' and time ="'.$time.'"';
		}else{
			$time=time();
		}
		$w.=' and type_id=3';
		$lists=M('expaccount')->order('time')->where($w)->limit($first,$len)->select();
		foreach($lists as $k=>$v)
        {
        	$lists[$k]['id'] = $k+1;
          $lists[$k]['time'] = date('Y/m/d',$v['time']);
        }
        return $lists;
    }
	//维修单详细信息页面
	public function detail(){
		$id=I('id','intval');
		// echo $id;
		$detail=M('expaccount')->join('k_depart ON k_depart.id=k_expaccount.department')->where(array('k_expaccount.id'=>$id))->find();
		$list=M('expaccountDetial')->where(array('account_id'=>$id))->select();
		// print_r($detail);
		$this->assign('detail',$detail);
		$this->assign('list',$list);
		$this->display();
	}
	//添加维修单
	public function add(){
		$admin=session('admin');
		if(IS_POST){
		$user_id=$admin['info']['id'];
		$data=array(
				'time'=>strtotime(I('time')),
				'department'=>I('department'),
				'total'=>I('total'),
				'total_capital'=>I('total_capital'),
				'topic'=>I('topic'),
				'cash'=>I('cash'),
				'bank'=>I('bank'),
				'transfer'=>I('transfer'),
				'signature'=>I('signature'),
				'charge'=>I('charge'),
				'prove'=>I('prove'),
				'user'=>I('user'),
				'opinion'=>I('opinion'),
				'org_id'=>$admin['info']['org_id'],
				'type_id'=>3,
				'user_id'=>$user_id
			);
		
		  if(empty(I('department'))){
				$this->error('使用单位不能为空');
			}
			if(empty(I('total'))){
				$this->error('金额合计不能为空');
			}
			if(empty(I('charge'))){
				$this->error('单位负责人不能为空');
			}
			if(empty(I('user'))){
				$this->error('经报人不能为空');
			}
			if(empty(I('prove'))){
				$this->error('保管验收或证明不能为空');
			}
		  $useto=I('useto');
		  if(empty($useto[0])){
		  	$this->error('用途不能为空');
		  }

			$amount=I('amount');
			if(empty($amount[0])){
		  	$this->error('单据张数不能为空');
		  }
			$money=I('money');
			if(empty($money[0])){
		  	$this->error('金额不能为空');
		  }
			if(count($useto)!=count($amount)||count($useto)!=count($money)||count($amount)!=count($money)){
				$this->error('单据张数/用途/金额数据缺损');
			}

		$res=M('expaccount')->add($data);
		if($res){
			//添加详细信息
			$arrlist=array();
			foreach ($useto as $k => $v) {
				if(isset($v)){
						$arrlist[$k]=array(
							'amount'=>$amount[$k],
							'money'=>$money[$k],
							'useto'=>$v,
							'account_id'=>$res
							);
					}
					
			}

			foreach ($arrlist as $kk => $vv) {
				if(!empty($vv['useto'])){
					$flag=M('expaccountDetial')->add($vv);
				}
				
			}
			if($flag){
				$this->success('操作成功',U('index'));
			}else{
				$this->error('操作失败');
			}
			
		}else{
			$this->error('操作失败');
		}

		}else{
			//获取部门列表信息
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
			}
			$this->assign('depart',$list_dep);
			$this->assign('timedate',time());
			$this->display();
		}
		
	}
}