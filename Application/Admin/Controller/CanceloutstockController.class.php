<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com/)
// +----------------------------------------------------------------------
// | Author: Liu jin bi (hiworld@sina.cn)
// +----------------------------------------------------------------------
// | Date: 2017-01-04 19:40:06
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class CanceloutstockController extends BaseController {
	public function _initialize()
	{
		parent::_initialize();
		$this->admin=session('admin.info');
	}

	public function index()
	{
		$ni=I();
		$order_sn=$ni['order_sn'];
		$time=$ni['apply_time'];
		$applicant=$ni['applyer'];
		$depart=$ni['depart'];
		$ni['status']=$ni['status']?:6;

		$w="c.applyer_id=a.id and c.cancel_type='-' and c.depart=d.id ";
		if ($order_sn) {
			$w.=" and c.cancel_sn like '%{$order_sn}%' ";
		}
		if ($time) {
			$t=strtotime($time);
			$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
			$end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
			$w.=" and c.apply_time BETWEEN $start and $end ";
		}
		if ($applicant) {
			$w.=" and a.name like '%{$applicant}%'";
		}
		if ($depart) {
			$w.=" and d.id = $depart";
		}
		switch ($ni['status']) {
			case 1:
				$w.=" and c.status=1";
				break;
			case 2:
				$w.=" and c.status=2";
				break;
			case 5:
				$w.=" and c.status in(3,4)";
				break;
			case 6:
				// $w.=" and c.status=1";
				break;
			default:
				$w.=" and c.status=1";
				break;
		}

		$count=M()->table('k_cancel c,k_depart d, k_admin a')->where($w)->count();
		$page = new \Think\PageA($count,15,I());
    	$show           = $page->show();

		$lists=M()->field('c.id,c.cancel_sn,c.apply_time,c.status,d.depart_name as depart,a.name as applyer')
			->table('k_cancel c,k_depart d, k_admin a')
			->limit($page->firstRow.','.$page->listRows)
			->where($w)
			->select();
		$this->assign('admin',$this->admin['id']);
		$this->assign('w',$ni);	
		$this->assign('page',$show);
		$this->assign('lists',$lists);
		$this->assign('departs',$this->getDeparts());
		$this->display();
	}

	//所有部门
    private function getDeparts($topid=0)
    {
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
        	$Depart = $cat->getChildren($org,$lists) ;
        }else{
        	$Depart = $this->admin['info']['is_supper'] ? $lists : $cat->getChildren($org,$lists);
        }
        return $Depart;
    }
    
    public function add()
    {
    	if (IS_POST) {
    		$depart=I('depart_id');
    		!$depart&&$this->error('请选择审核部门！');
    		$depart=(array)$depart;
    		$oid=I('oid');
    		!$oid&&$this->error('参数错误！');
    		$time=I('time');
    		$time=$time?strtotime($time):time();
    		if (count($depart)!=count(array_unique($depart))) {
				$this->error('请勿重复添加相同审核部门！');
			}

			//查出出库单
			$outorder=M('Outstock')->field()->where(array('id'=>$oid))->find();

			//生成出库报废单
			$cancel=array(
				'cancel_sn'=>createOrderSN(C('BFSN')),
				'applyer_id'=>$this->admin['id'],
				'applyer'=>$this->admin['name'],
				'apply_time'=>$time,
				'cancel_type'=>'-',
				'order_sn'=>$outorder['order_sn'],
				'status'=>1,
				'depart'=>$outorder['depart'],
				'org_id'=>$outorder['org_id']
			);

			$MC=M('Cancel');
			$MC->startTrans();
			$orderID=$MC->add($cancel);
			if (!$orderID) {
				$M->rollback();$this->error('保存失败！');
			}

			$users=array();
			foreach ($depart as $key=>$vd) {
				$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->field('id,name')->where("depart_id=$vd and is_auditer=1")->find();
				if (!$tuser) {
					$this->error($department.' 没有审核人员!');
				}
				$users[]=array(
					'cancel_id' =>$orderID,
					'audit_id'  =>$tuser['id'],
					'audit_name'=>$tuser['name'],
					'sort'      =>$key
				);
			}
			//保存申请单审核人
			$re_audit=M('CancelAudit')->addAll($users);
			if (!$re_audit) {
				$MC->rollback();$this->error('保存失败！');
			}
			$receive=array_shift($users);
			$msg=array(
				'title'=>'出库报废申请审核信息',
				'apply_id'=>$orderID, //申请单ID
				'send_id'=>$admin['id'],
				'type'=>100,
				'receive_id'=>$receive['audit_id'],
				'url'=>U('audit',array('id'=>$orderID)),
				'send_time'=>time()
			);
			//添加消息
			$re_msg=M('Message')->add($msg);
			if ($re_msg) {
				$MC->commit();$this->success('保存成功！',U('index'));
			}else{
				$MC->rollback();$this->error('保存失败！');
			}
    	}else{
	    	$oid=I('oid');
	    	!$oid&&$this->error('参数错误！');
	    	$order=M()
	    	->field('o.*,d.depart_name')
	    	->table('k_outstock o')
	    	->join('k_depart d on o.depart=d.id')
	    	->where(array('o.id'=>$oid))->find();
	    	if (!$order||$order['status']!=3) {
	    		$this->error('该单不能报废！');
	    	}

	    	//获取物品所属单位组织
	    	$cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
	        $lists = $cat->getList(0);
	        $Depart = $cat->getChildren($order['org_id'],$lists) ;
	        $this->assign('depart',$Depart);

	    	//查出物品
			$goods=M()
				->field('og.*,bg.assets_name,bg.unit,cat.cate_name,s.company_name')
				->table('k_base_goods bg,k_category cat,k_outstock_goods og')
				->join('k_supplier s on og.supplier_id=s.id','left')
				->where("og.goods_id=bg.id and bg.cate_id=cat.id and og.outstock_id=$oid")
				->select();
			$this->assign('data',$order);
			$this->assign('gdata',$goods);
			$this->display();
		}
    }

    /**
	 * 审核操作
	 * @return [type] [description]
	 */
	public function audit(){
		if (IS_POST) {
			$ni=I();
			!$ni['id']&&$this->error('参数错误！');
			// !$ni['audit_id']&&$this->error('参数错误！');
			!$ni['status']&&$this->error('请选择同意或者不同意！');
			$ni['time']=$ni['time']?strtotime($ni['time']):time();
			//查出报废申请单
			$order=M('Cancel')->where(array('id'=>$ni['id']))->find();
			!$order&&$this->error('参数错误！');
			//如果申请单状态为已通过或者未通过则不能继续审核
			if ($order['status']==3||$order['status']==4) {
				$this->error('该申请单已关闭，不能操作！');
			}
			//查出审核人
			$CAM=M('CancelAudit');
			$Taudits=$CAM
				->field('status,audit_id,sort')
				->where("cancel_id={$ni['id']}")
				->order('sort')
				->select();
			!$Taudits&&$this->error('参数错误！');
			$audits=array_reduce($Taudits,function(&$audits,$v){
	            $audits[$v['audit_id']] = $v;
	            return $audits;
	        });
			$count=count($audits)-1;
			$cur=$audits[$this->admin['id']];
			!$cur&&$this->error('你不是审核人！');
			//操作验证 如果前一个审核没通过则不能操作
			$pre=null;
			foreach ($audits as $key => $value) {
				if ($value['sort']==$cur['sort']-1) {
					$pre=$value;break;
				}
			}
			if ($pre) {
				if ($pre['status']==0||$pre['status']==1) {
					$this->error('前一位审核人员审核未通过您不能操作！');
				}
			}

			$CAM->startTrans();
			//处理审核人审核状态
			$re=$CAM->where(array('cancel_id'=>$ni['id'],'audit_id'=>$cur['audit_id']))->save(array('time'=>$ni['time'],'remark'=>$ni['remark'],'status'=>$ni['status']));
			if (!$re&&$re!==0) {
				$CAM->rollback();$this->error('审核失败！1');
			}
			if ($cur['sort']==$count) {
				//最后一个审核人
				if ($ni['status']==2) {//最后一个人审核通过则修库存
					//先查处出库申请单
					$outorderID=M('Outstock')->where(array('order_sn'=>$order['order_sn']))->getField('id');
					//查出物品
					$goods=M('OutstockGoods')->where(array('outstock_id'=>$outorderID))->select();

					foreach ($goods as $key => $value) {
						//回库存
						$re_c=M('Stock')->where(array('goods_id'=>$value['goods_id'],'supplier_id'=>$value['supplier_id'],'spec'=>$value['spec']))->save(array('num'=>array('exp','num+'.$value['num']),'total'=>array('exp','total+'.$value['total'])));
						if (!$re_c&&$re_c!==0) {
							$re_c=false;break;
						}
						//处理库存明细
						// $stock=M('Stock')->where(array('goods_id'=>$value['goods_id'],'supplier_id'=>$value['supplier_id'],'spec'=>$value['spec']))->find();
						// $detail=array(
						// 	'stock_id'=>$stock['id'],
						// 	'time'=>time(),
						// 	'goods_num'=>$value['num'],
						// 	'func'=>'+',
						// 	'price'=>$value['price'],
						// 	'total'=>$value['total'],
						// 	'order_sn'=>$order['cancel_sn'],
						// 	'type'=>2
						// );
						// $re_cd=M('StockDetial')->add($detail);
						// if (!$re_cd) {
						// 	$re_cd=false;break;
						// }
						
					}
					//删除库存详细
					$del_sd=M('StockDetial')->where(array('order_sn'=>$order['order_sn']))->delete();
					if (!$re_c||!$del_sd) {
						$CAM->rollback();$this->error('审核失败！2');
					}
					//申请单状态处理  1未审核  2审核中 3未通过  4通过
					$re_change=M('Cancel')->where(array('id'=>$ni['id']))->save(array('status'=>4,'last_op_time'=>time()));

					if (!$re_change&&$re_change!==0) {
						$CAM->rollback();$this->error('审核失败！3');
					}
					//出库申请单状态处理
					$re_o=M('Outstock')->where(array('order_sn'=>$order['order_sn']))->save(array('is_scrap'=>1));
					if (!$re_o&&$re_o!==0) {
						$CAM->rollback();$this->error('审核失败！3');
					}
					//最后一个审核通过 给申请人推送 审核通过消息
					$msg=array(
						'title'=>'出库报废申请审核通过信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$cur['audit_id'],
						'type'=>100,
						'receive_id'=>$order['applyer_id'],
						'url'=>U('audit',array('id'=>$order['id'])),
						'send_time'=>time()
					);
					//添加消息
					$re_msg=M('Message')->add($msg);

					
				}else{//最后一个人审核未通过
					//申请单状态处理  1未审核  2审核中 3未通过  4通过
					$re_change=M('Cancel')->where(array('id'=>$ni['id']))->save(array('status'=>3,'last_op_time'=>time()));
					if (!$re_change&&$re_change!==0) {
						$CAM->rollback();$this->error('审核失败！4');
					}

					//最后一个审核未通过 给申请人推送 审核未通过消息
					$msg=array(
						'title'=>'出库报废申请审核未通过信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$cur['audit_id'],
						'type'=>100,
						'receive_id'=>$order['applyer_id'],
						'url'=>U('audit',array('id'=>$order['id'])),
						'send_time'=>time()
					);
					//添加消息
					$re_msg=M('Message')->add($msg);
				}
			}else{//不是最后一个审核人
				if ($ni['status']==2) {//审核通过
					//申请单状态处理  1未审核  2审核中 3未通过  4通过
					$re_change=M('Cancel')->where(array('id'=>$ni['id']))->save(array('status'=>2,'last_op_time'=>time()));
					if (!$re_change&&$re_change!==0) {
						$CAM->rollback();$this->error('审核失败！5');
					}

					//给下个审核人推送消息;
					$Saudits=array_reduce($Taudits,function(&$next_audit,$v){
			            $next_audit[$v['sort']] = $v;
			            return $next_audit;
			        });
					$next_audit=$Saudits[$cur['sort']+1];
					$msg=array(
						'title'=>'出库报废申请审核信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$order['applyer_id'],
						'type'=>100,
						'receive_id'=>$next_audit['audit_id'],
						'url'=>U('audit',array('id'=>$order['id'])),
						'send_time'=>time()
					);
					//添加消息
					$re_msg=M('Message')->add($msg);
					if (!$re_msg) {
						$CAM->rollback();$this->error('审核失败！99');
					}
				}else{//审核未通过
					//申请单状态处理  1未审核  2审核中 3未通过  4通过
					$re_change=M('Cancel')->where(array('id'=>$ni['id']))->save(array('status'=>3,'last_op_time'=>time()));
					if (!$re_change&&$re_change!==0) {
						$CAM->rollback();$this->error('审核失败！6');
					}

					//审核未通过 给申请人推送 审核未通过消息
					$msg=array(
						'title'=>'出库报废申请审核未通过信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$cur['audit_id'],
						'type'=>100,
						'receive_id'=>$order['applyer_id'],
						'url'=>U('audit',array('id'=>$order['id'])),
						'send_time'=>time()
					);
					//添加消息
					$re_msg=M('Message')->add($msg);
					if (!$re_msg) {
						$CAM->rollback();$this->error('审核失败！99');
					}
				}
			}
			
			//处理自己的消息表
			$wm=array('type'=>100,'apply_id'=>$order['id'],'receive_id'=>$cur['audit_id']);

			$re_m=M('Message')->where($wm)->save(array('status'=>2));
			if ($re_m!==false) {
				$CAM->commit();
				$this->success('审核成功！',U('index'));
			}else{
				$CAM->rollback();
				$this->error('审核失败！8');
			}
			
		}else{
			$id=I('id');
			!$id&&$this->error('参数错误！');
			//查出审核单
			$order=M('Cancel')->where(array('id'=>$id))->find();
			$admin=$this->admin;
			if (($order['status']==3||$order['status']==4) && $order['applyer_id']==$admin['id']) {
				$wm=array('type'=>100,'apply_id'=>$order['id'],'receive_id'=>$admin['id']);
				M('Message')->where($wm)->save(array('status'=>2));
			}
			$this->assign('order',$order);
			//先查出出库单
			$outorder=M('Outstock')->alias('o')
			->field('o.*,d.depart_name')
			->join('k_depart d on o.depart=d.id')
			->where(array('order_sn'=>$order['order_sn']))
			->find();
			$outorderID=$outorder['id'];
			$this->assign('outorder',$outorder);
			//查出物品
			$goods=M()
				->field('og.*,bg.assets_name,bg.unit,cat.cate_name,s.company_name')
				->table('k_base_goods bg,k_category cat,k_outstock_goods og')
				->join('k_supplier s on og.supplier_id=s.id','left')
				->where("og.goods_id=bg.id and bg.cate_id=cat.id and og.outstock_id=$outorderID")
				->select();
			$this->assign('goods',$goods);
			
			//查出审核人
			$Taudits=M()->field('a.name,d.depart_name,a.signature,ca.status,ca.audit_id,ca.sort,ca.time,ca.remark,ca.audit_name')
				->table('k_admin a,k_cancel_audit ca,k_depart d')
				->where("a.id=ca.audit_id and d.id=a.depart_id and ca.cancel_id=$id")
				->order('ca.sort')
				->select();
			$user=$this->admin;
			$audits=array_reduce($Taudits,function(&$audits,$v){
				$v['right']=false;
	            $audits[$v['audit_id']] = $v;
	            return $audits;
	        });
	        
	        $cur=$audits[$user['id']];
			if ($cur) {
				$pre=null;
				foreach ($audits as $key => $value) {
					if ($value['sort']==($cur['sort']-1)) {
						$pre=$value; break;
					}
				}
				if ($pre) {
					if ($pre['status']==2) {
						$cur['right']=true;
						$this->assign('submit',true);
					}
				}else{
					if ($cur['status']==0) {
						$cur['right']=true;
						$this->assign('submit',true);
					}
				}
				$audits[$user['id']]=$cur;
			}
			$this->assign('audits',$audits);
			$this->display();
		}
	}
}