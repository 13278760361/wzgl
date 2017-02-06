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
class SupplierController extends BaseController
{
	//展示供货商的列表信息
	public function index(){
		//参数筛选
		$w='is_hide!=1 ';
		$admin=session('admin');
		$company_name=trim(I('company_name'));
		$Smobile=trim(I('Smobile'));
		if($company_name){
			$w.='and company_name like "%'.$company_name.'%"';
		}
		if($Smobile){
			$w.='and company_name like "%'.$Smobile.'%"';
		}
		$size=20;
  	$count = M('supplier')->where($w)->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show= $Page->show();// 分页显示输出
		$list=M('supplier')->where($w)->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('list',$list);
		$this->assign('page',$show);
		$this->assign('Smobile',$Smobile);
		$this->assign('company_name',$company_name);
		$this->assign('adminId',$admin['info']['id']);
			$this->display();
	}
	//分页
	public function ajaxindex(){
				$adminId=session('admin')['info']['id'];
				$page=I('get.page');
        $len=I('get.size')?:2;
        $key=I('get.key_word');
        $first=$len*($page-1);
        $w="1=1";
        if($key)
        {
           $w.='and company_name like "%'.$key.'%"';
        }

        $list=M('supplier')->where($w)->order('add_time desc')->limit($first,$len)->select();
        foreach($list as $k=>$v){
        		if($v['mobile_phone']==''){
        				$list[$k]['mobile_phone']='';
        		}
        		if($v['contacts']==''){
        				$list[$k]['contacts']='';
        		}
        		if($adminId==$v['user_id']){
        			$list[$k]['edit']='<a href="'.U('edit',array('id'=>$v['id'])).'">修改</a>';
        		}else{
        			$list[$k]['edit']='';
        		}
        		$list[$k]['url']=U('detaillist',array('id'=>$v['id']));
        }
        // print_r($list);
        // return $list;
        $this->ajaxReturn($list);
	}
	//获取供货商的商品信息列表
	public function detaillist(){

		$admin=session('admin');
		$id=I('id');
		$p=I('p');
		$w=' 1=1';
		$w.=' and supplier_id ='.$id;
		if($admin['info']['is_supper']!=1){
				//超级管理员
				$w.=' and org_id='.$admin['info']['org_id'];
			}
		$size=18;
  	$count = M('stock')->field('k_stock.id as sid,k_stock.*,k_base_goods.*')->join('k_base_goods ON goods_id=k_base_goods.id')->where($w)->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$list=M('stock')->field('k_stock.id as sid,k_stock.*,k_base_goods.*')->join('k_base_goods ON goods_id=k_base_goods.id')->where($w)->limit($Page->firstRow.','.$Page->listRows)->select();
		$hejilist=M('stock')->field('k_stock.id as sid,k_stock.*,k_base_goods.*')->join('k_base_goods ON goods_id=k_base_goods.id')->where($w)->select();
		$sum=0;
		foreach ($hejilist as $k => $v) {
			$sum+=$v['total'];
		}
		// echo $sum;
		$info=M('supplier')->where(array('id'=>$id))->find();
		$show= $Page->show();// 分页显示输出
		// print_r($hejilist);
		$this->assign('page',$show);
		$this->assign('heji',$sum);
		$this->assign('list',$list);
		$this->assign('info',$info);
		$this->assign('id',$id);
		$this->assign('p',$p);
		$this->display();
	}
	public function tiaoma(){
		$admin=session('admin');
		$id=I('id');
		$p=I('p');
		$w=' 1=1';
		$w.=' and supplier_id ='.$id;
		// if($admin['info']['is_supper']!=1){
		// 		//超级管理员
		// 		$w.=' and org_id='.$admin['info']['org_id'];
		// 	}
		$size=18;
  	$count = M('stock')->field('k_stock.id as sid,k_stock.*,k_base_goods.*,k_supplier.*,k_base_goods.add_time as time')->join('k_base_goods ON goods_id=k_base_goods.id')->join('k_supplier ON supplier_id=k_supplier.id')->where($w)->count();
		$Page= new \Think\PageA($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$list=M('stock')->field('k_stock.id as sid,k_stock.*,k_base_goods.*,k_supplier.*,k_base_goods.add_time as time')->join('k_base_goods ON goods_id=k_base_goods.id')->join('k_supplier ON supplier_id=k_supplier.id')->where($w)->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('lists',$list);
		$this->display();
	}
	public function ajaxList(){
		$goods_id=I('goodsId');
		$sid=I('sid');
		$info=M('stock')->field('bar_code,spec,num,company_name,total,assets_name,k_base_goods.add_time as time')->join('k_supplier ON k_stock.supplier_id=k_supplier.id')->join('k_base_goods ON k_stock.goods_id=k_base_goods.id')->where(array('k_stock.id'=>$sid))->find();
		$str='<tr>
							<td colspan="2" style="text-align: center;">
								<img src="'.$info["bar_code"].'">
							</td>
						</tr>
						<tr>
							<td>供货商</td>
							<td>'.$info["company_name"].'</td>
						</tr>
						<tr>
							<td >物品名称</td>
							<td>'.$info["assets_name"].'</td>
						</tr>
						<tr>
							<td>规格型号</td>
							<td>'.$info["spec"].'</td>
						</tr>
						<tr>
							<td>日期</td>
							<td>'.date('Y-m-d',$info['time']).'</td>
						</tr>
						<tr>
							<td>数量</td>
							<td>'.$info["num"].'</td>
						</tr>
						<tr>
							<td>价格</td>
							<td>'.$info["total"].'</td>
						</tr>
							';
		echo $str;die;
	}
	public function edit(){
		$id=I('id','intval');
		$d=M('supplier')->where(array('id'=>$id))->find();
		$this->assign('d',$d);
		if(IS_POST){
			//处理表单提交
				if(!empty(I('mobile_phone'))){
				if(!preg_match("/^1[34578]{1}\d{9}$/",I('mobile_phone'))){  
		    $this->error('移动电话格式错误');
				} 
			}
			 if(!empty(I('tel_phone'))){
				if(!preg_match("/^([0-9]{3,4}-)?[0-9]{7,8}$/",I('tel_phone'))){  
		    $this->error('电话格式错误');
				} 
			}
			$data=array(
					'company_name'=>I('company_name'),
					'legaler'=>I('legaler'),
					'business_no'=>I('business_no'),
					'register_price'=>I('register_price'),
					'tax_no'=>I('tax_no'),
					'scale'=>I('scale'),
					'org_code'=>I('org_code'),
					'type'=>I('type'),
					'supplier_type'=>I('supplier_type'),
					'post_code'=>I('post_code'),
					'management'=>I('management'),
					'company_url'=>I('company_url'),
					'service'=>I('service'),
					'address'=>I('address'),
					'bank'=>I('bank'),
					'bank_account'=>I('bank_account'),
					'certification'=>I('certification'),
					'certificate_num'=>I('certificate_num'),
					'certificate_date'=>I('certificate_date'),
					'certification_org'=>I('certification_org'),
					'contacts'=>I('contacts'),
					'job'=>I('job'),
					'tel_phone'=>I('tel_phone'),
					'mobile_phone'=>I('mobile_phone'),
					'add_time'=>time()
				);
			if(empty(I('company_name'))){
				$this->error('请填写企业的名称信息');
			}
					//查询该企业有没有存在
			$res=M('supplier')->where(array('company_name'=>I('company_name')))->find();
			if($res){
				$final=M('supplier')->where(array('id'=>$id))->save($data);
				if($final){
					$this->success('企业信息修改成功',U('index'));
				}else{
					$this->error('修改失败');
				}
			}else{
				$this->error('您修改的企业信息不存在');
			}
		}
		$this->display();
	}
	//添加供货商
	public function add(){
		if(IS_POST){
			//处理表单提交
			$admin=session('admin');
			if(!empty(I('mobile_phone'))){
				if(!preg_match("/^1[34578]{1}\d{9}$/",I('mobile_phone'))){  
		    $this->error('移动电话格式错误');
				} 
			}

		if(!empty(I('tel_phone'))){
				if(!preg_match("/^([0-9]{3,4}-)?[0-9]{7,8}$/",I('tel_phone'))){  
		    $this->error('电话格式错误');
				} 
			}
			$data=array(
					'company_name'=>I('company_name'),
					'legaler'=>I('legaler'),
					'business_no'=>I('business_no'),
					'register_price'=>I('register_price'),
					'tax_no'=>I('tax_no'),
					'scale'=>I('scale'),
					'org_code'=>I('org_code'),
					'type'=>I('type'),
					'supplier_type'=>I('supplier_type'),
					'post_code'=>I('post_code'),
					'management'=>I('management'),
					'company_url'=>I('company_url'),
					'service'=>I('service'),
					'address'=>I('address'),
					'bank'=>I('bank'),
					'bank_account'=>I('bank_account'),
					'certification'=>I('certification'),
					'certificate_num'=>I('certificate_num'),
					'certificate_date'=>I('certificate_date'),
					'certification_org'=>I('certification_org'),
					'contacts'=>I('contacts'),
					'job'=>I('job'),
					'tel_phone'=>I('tel_phone'),
					'mobile_phone'=>I('mobile_phone'),
					'user_id'=>$admin['info']['id'],
					'add_time'=>time()
				);
			if(empty(I('company_name'))){
				$this->error('请填写企业的名称信息');
			}
			//查询该企业有没有添加过
			$res=M('supplier')->where(array('company_name'=>I('company_name')))->find();
			if($res){
				$this->error('该企业信息已经录入过啦！');
			}else{
				$final=M('supplier')->add($data);
				if($final){
					$this->success('企业信息录入成功',U('index'));
				}else{
					$this->error('录入失败');
				}
			}
			
		}else{
			$this->display();
		}
		
	}
}