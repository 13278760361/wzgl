<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com/)
// +----------------------------------------------------------------------
// | Author: Liu jin bi (hiworld@sina.cn)
// +----------------------------------------------------------------------
// | Date: 2016-11-29 18:28:37
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class LowExchangeController extends BaseController
{
	//导出模板
    public $outlist=array('assets_name'=>'物品名称','spec'=>'规格型号','depart_name'=>'部门','order_sn'=>'卡片单号','cate_name'=>'资产分类',''=>'金额','time'=>'变更日期');

	public function _initialize()
	{
		parent::_initialize();
		$this->admin=session('admin');
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
	public function index()
	{
		$goods_name=I('goods_name');
		$start_time=I('start_time');
		$end_time=I('end_time');
		$card_sn=I('card_sn');
		$type_id = I('type_id');
		$key_word=I('key_word');

		$w="c.id=g.change_id and g.card_sn=card.order_sn and card.goods_id=bg.id and bg.cate_id=cat.id and c.status= 4 and c.type=2  and card.department=fd.id";
		if ($key_word) {
			$w.=" and (bg.assets_name like '%{$key_word}%' or c.order_sn like '%{$key_word}%' or fd.depart_name like '%{$key_word}%')";
		}
		if ($goods_name) {
			$w.=" and bg.assets_name like '%{$goods_name}%' ";
		}
		if ($start_time&&$end_time) {
			$tst=strtotime($start_time);
			$tet=strtotime($end_time);
			$w.=" and c.time BETWEEN $tst and $tet ";
		}elseif ($start_time) {
			$tst=strtotime($start_time);
			$w.=" and c.time>$tst ";
		}elseif ($end_time) {
			$tet=strtotime($end_time);
			$w.=" and c.time<$tet ";
		}
		if ($card_sn) {
			$w.=" and card.order_sn like '%{$card_sn}%'";
		}
		if($type_id){
            $w.=" AND bg.type_id='{$type_id}'";
        }
        if(I('cate_id')){
            $w.=" AND bg.cate_id='".I('cate_id')."'";
        }
		$p_total = 0;
		$count=M()
			->table('k_change c,k_change_goods g,k_base_goods bg,k_category cat,k_depart fd,k_cards card')
			->where($w)
			->count();
		// print_r(M()->getLastSql());exit();
		$page = new \Think\PageA($count,c('pagesize'),I());
    	$show           = $page->show();

		$lists=M()->field('c.id,bg.assets_name,cat.cate_name,c.time,c.order_sn,card.order_sn as cardSN,card.spec,fd.depart_name as department,card.original_value,d.depart_name as original_department')
			->table('k_change c,k_base_goods bg,k_category cat,k_cards card,k_depart fd, k_change_goods g')
			->join('k_depart d on g.original_department=d.id ')
			->limit($page->firstRow.','.$page->listRows)
			->where($w)
			->select();
		foreach ($lists as $key => $value) {
			# code...
			$p_total+=$value['original_value'];
		}
		$s_total = M()
			    ->table('k_change c,k_base_goods bg,k_category cat,k_cards card,k_depart fd, k_change_goods g')
				->join('k_depart d on g.original_department=d.id ')
				->where($w)->getField('SUM(original_value) as num');
		//print_r($s_total);exit;
		if($type_id){
            $cat2=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
            $this->assign('cateS',$cat2->getList(0));
        }
		$this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',I('cate_id'));
		$this->assign('s_total',$s_total?$s_total:0);
		$this->assign('p_total',$p_total);
		$this->assign('w',I());	
		$this->assign('page',$show);
		// $lists=array_merge($lists,$lists,$lists,$lists,$lists,$lists);
		// $lists=array_merge($lists,$lists,$lists);
		$this->assign('lists',$lists);
		$this->display();
	}

	/**
	 * 返回手机端列表数据
	 * @return [type] [description]
	 */
	public function ajaxindex()
	{
		$keyword=I('key_word');
		$size=C('seachsize');
		$page=I('page');

		$w="c.id=g.change_id and g.card_sn=card.order_sn and card.goods_id=bg.id and bg.cate_id=cat.id and c.status= 4 and c.type=2  and card.department=fd.id";

		if ($keyword) {
			$w.=" and (bg.assets_name like '%{$key_word}%' or c.order_sn like '%{$key_word}%' or fd.depart_name like '%{$key_word}%')";
		}

		$lists=M()->field('c.id,bg.assets_name,cat.cate_name,c.time,c.order_sn,card.order_sn as cardSN,card.spec,fd.depart_name as department,card.original_value,d.depart_name as original_department')
			->table('k_change c,k_base_goods bg,k_category cat,k_cards card,k_depart fd, k_change_goods g')
			->join('k_depart d on g.original_department=d.id ')
			->page($page,$size)
			->where($w)
			->select();
		$this->ajaxReturn($lists);
	}

	/**
	 * 添加申请单
	 * @return [type] [description]
	 */
	public function apply()
	{
		if (IS_POST) {
			$admin=$this->admin['info'];
			$ni=I();
			$other=$ni['o'];
			unset($ni['o']);
			!$ni&&$this->error('未添加审核物品！');
			foreach ($ni as $key => $value) {
				if (empty($value['order_sn'])) {
					unset($ni[$key]);
				}
			}
			!$ni&&$this->error('未添加审核物品！');
			if ($admin['is_supper']) {
				if (!$other['org_id']) {$this->error('请选择申请单所属部门！');}
				$org_id=$other['org_id'];
			}else{
				$org_id=$admin['org_id'];
			}
			!$other['department_id']&&$this->error('未选择审核部门！');
			$other['department_id']=(array)$other['department_id'];
			foreach ($other['department_id'] as $key => $value) {
				if ($value==0) {
					unset($other['department_id'][$key]);
				}
			}
			if (!$other['department_id']) {
				$this->error('未选择审核部门！');
			}
			if (count($other['department_id'])!=count(array_unique($other['department_id']))) {
				$this->error('请勿重复添加相同审核部门！');
			}
			$time=$other['date']?strtotime($other['date']):time();
			$order=array(
				'order_sn'  =>$other['ordersn'],
				'time'      =>$time,
				'remark'    =>$other['remark'],
				'applicant_id' =>$admin['id'],
				'applicant' =>$admin['name'],
				'status'    =>1,
				'org_id'	=>$org_id,
				'type'		=>2
			);
			$M=M('Change');$M->startTrans();
			//创建申请单
			$orderID=$M->add($order);
			if (!$orderID) {
				$M->rollback();$this->error('保存失败！');
			}
			$goods=array();
			foreach ($ni as $key => $value) {
				if (!$value['cur_user']&&!$value['cur_department']) {
					$this->error($value['order_sn'].':未填写变更信息！');
				}
				$card=M('Cards')->field('keeper,department')->where(array('order_sn'=>$value['order_sn']))->find();
				if ($card) {
					$goods[]=array(
						'change_id'           =>$orderID,
						'card_sn'             =>$value['order_sn'],
						'cur_user'            =>$value['cur_user'],
						'cur_department'      =>$value['cur_department'],
						'original_user'       =>$card['keeper'],
						'original_department' =>$card['department']
					);
				}
			}
			!$goods&&$this->error('未添加审核物品！');
			//保存申请单物品
			$re=M('ChangeGoods')->addAll($goods);
			if (!$re) {
				$M->rollback();$this->error('保存失败！');
			}
			$users=array();
			foreach ($other['department_id'] as $key=>$vd) {
				$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->field('id,name')->where("depart_id=$vd and is_auditer=1")->find();
				if (!$tuser) {
					$this->error($department.' 没有审核人员!');
				}
				$users[]=array(
					'change_id' =>$orderID,
					'audit_id'  =>$tuser['id'],
					'audit_name'=>$tuser['name'],
					'sort'      =>$key
				);
			}
			//保存申请单审核人
			$re_audit=M('ChangeAudit')->addAll($users);
			if (!$re_audit) {
				$M->rollback();$this->error('保存失败！');
			}
			$receive=array_shift($users);
			$msg=array(
				'title'=>'资产变更申请审核信息',
				'apply_id'=>$orderID, //申请单ID
				'send_id'=>$admin['id'],
				'type'=>65,
				'receive_id'=>$receive['audit_id'],
				'url'=>U('audit',array('id'=>$orderID)),
				'send_time'=>time()
			);
			//添加消息
			$re_msg=M('Message')->add($msg);
			if ($re_msg) {
				$M->commit();$this->success('保存成功！',U('index'));
			}else{
				$M->rollback();$this->error('保存失败！');
			}
		}else{
			// $lists=M()
			// ->field('c.id,c.order_sn,c.spec,c.keeper,c.department,c.original_value,bg.assets_name,bg.unit,cat.cate_name')
			// ->table('k_cards c,k_base_goods bg,k_category cat')
			// ->where("c.goods_id=bg.id and bg.cate_id=cat.id")
			// ->select();
			$card_sn=I('card_sn');
			if ($card_sn) {
				$goods=M()
				->field('c.id,c.org_id,c.order_sn,c.spec,c.keeper,d.depart_name as department,c.original_value,bg.assets_name,bg.unit,cat.cate_name')
				->table('k_base_goods bg,k_category cat,k_cards c')
				->join('k_depart d on c.department=d.id ')
				->where("c.goods_id=bg.id and bg.cate_id=cat.id and c.order_sn = '".$card_sn."' and bg.type_id=2")
				->find();
				$this->assign('goods',$goods);
				// echo "<pre />";
				// print_r($goods);exit;
			}
			if ($goods) {
				$org_id=$goods['org_id'];
			}else{
				if (I('org')) {
					$org_id=I('org');
				}else{
					if ($this->admin['info']['is_supper']) {
						$org_id=0;
					}else{
						$org_id=$this->admin['info']['org_id'];
					}
				}
			}
			// print_r($org_id);exit;
			$this->assign('list_dep',$this->getDeparts($org_id));
			$c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
			if ($this->admin['info']['is_supper']) {
				$topDepart=M('Depart')->field('id,depart_name')->where(array('pid'=>$c_id))->select();
				$this->assign('topDepart',$topDepart);
			}
			$this->assign('order_sn',createOrderSN(C('BGSN')));
			$this->assign('org',$org_id);
			// $lists=$lists?json_encode($lists):'""';
			// $this->assign('lists',$lists);
			$this->assign('isSupper',$this->admin['info']['is_supper']);
			$this->display();
		}
	}

	/**
	 * ajax获取物品
	 * @return [type] [description]
	 */
	public function goodsList()
	{
		$org=I('org')?:$this->admin['info']['org_id']?:0;
		$order_sn=I('field');
		$value=I('value');
		$lists=M()
		->field('c.id,c.order_sn,c.spec,c.keeper,d.depart_name as department,c.original_value,bg.assets_name,bg.unit,cat.cate_name')
		->table('k_base_goods bg,k_category cat,k_cards c')
		->join('k_depart d on c.department=d.id ')
		->where("c.goods_id=bg.id and bg.cate_id=cat.id and c.".$order_sn." LIKE '".$value."%' and c.org_id=$org and bg.type_id=2")
		->page(1,C('seachsize'))
		->select();
		$this->ajaxReturn($lists);
	}

	/**
	 * 查看
	 * @return [type] [description]
	 */
	public function look(){
		$id=I('id');
		!$id&&$this->error('参数错误！');
		//查出审核单
		$order=M('Change')->where(array('id'=>$id))->find();
		$this->assign('order',$order);
		//查出物品
		$goods=M()
			// ->field('c.id,c.order_sn,c.spec,c.keeper,c.department,c.original_value,bg.assets_name,bg.unit,cat.cate_name,cg.cur_department,cg.cur_user')
			->field('c.id,c.order_sn,c.spec,cg.original_user as keeper,cg.original_department as department,c.original_value,bg.assets_name,bg.unit,cat.cate_name,cg.cur_department,cg.cur_user')
			->table('k_cards c,k_base_goods bg,k_category cat,k_change_goods cg')
			->where("c.goods_id=bg.id and bg.cate_id=cat.id and c.order_sn=cg.card_sn and cg.change_id=$id")
			->select();
		foreach ($goods as $key => $value) {
			$w="id in({$goods[$key]['department']},{$goods[$key]['cur_department']})";
			$dep=M('Depart')->where($w)->select();
			foreach ($dep as $k => $v) {
				if ($v['id']==$goods[$key]['department']) {
					$goods[$key]['department']=$v['depart_name'];
				}
				if ($v['id']==$goods[$key]['cur_department']) {
					$goods[$key]['cur_department']=$v['depart_name'];
				}
			}
		}
		$this->assign('goods',$goods);
		//查出审核人
		$audits=M()->field('a.name,d.depart_name,a.signature,ca.status,ca.audit_id,ca.time')
			->table('k_admin a,k_change_audit ca,k_depart d')
			->where("a.id=ca.audit_id and d.id=a.depart_id and ca.change_id=$id")
			->order('ca.sort')
			->select();
		$this->assign('audits',$audits);
		$this->display();
	}

	/**
	 * 审核列表
	 * @return [type] [description]
	 */
	public function auditlist()
	{

		$ni=I();
		$goods_name=$ni['goods_name'];
		$time=$ni['time'];
		$applicant=$ni['applicant'];
		$ni['status']=$ni['status']?:6;

		$w="c.id=g.change_id and g.card_sn=card.order_sn and card.goods_id=bg.id and bg.cate_id=cat.id and c.type=2 ";
		if ($goods_name) {
			$w.=" and bg.assets_name like '%{$goods_name}%' ";
		}
		if ($time) {
			$tt=strtotime($time);
			$w.=" and c.time='{$tt}'";
		}
		
		if ($applicant) {
			$w.=" and c.applicant like '%{$applicant}%'";
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

		$count=M()
			->table('k_change c,k_change_goods g,k_base_goods bg,k_category cat,k_cards card')
			->where($w)->group('c.order_sn')->count();

		$page = new \Think\PageA($count,15,I());
    	$show           = $page->show();

		$lists=M()->field('c.id,c.applicant_id,bg.assets_name,cat.cate_name,c.time,c.order_sn,c.last_op_time,c.applicant,c.status,card.order_sn as cardSN,card.spec,card.keeper,card.department,card.original_value,g.cur_user,g.cur_department')
			->table('k_change c,k_change_goods g,k_base_goods bg,k_category cat,k_cards card')
			->limit($page->firstRow.','.$page->listRows)
			->where($w)
			->group('c.order_sn')
			->select();
		$this->assign('admin',$this->admin['info']['id']);
		$this->assign('w',$ni);	
		$this->assign('page',$show);
		$this->assign('lists',$lists);
		$this->display();
	}

	/**
	 * 获取审核列表数据
	 * @return [type] [description]
	 */
	public function ajaxauditlist()
	{
		$ni=I();
		// $goods_name=$ni['goods_name'];
		// $time=$ni['time'];
		// $applicant=$ni['applicant'];
		$ni['status']=$ni['status']?:6;
		$page=$ni['page'];
		$size=C('seachsize');
		$w="c.id=g.change_id and g.card_sn=card.order_sn and card.goods_id=bg.id and bg.cate_id=cat.id and c.type=2 ";
		// if ($goods_name) {
		// 	$w.=" and bg.assets_name like '%{$goods_name}%' ";
		// }
		// if ($time) {
		// 	$tt=strtotime($time);
		// 	$w.=" and c.time='{$tt}'";
		// }
		
		// if ($applicant) {
		// 	$w.=" and c.applicant like '%{$applicant}%'";
		// }
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

		$lists=M()->field("c.id,c.applicant_id,bg.assets_name,cat.cate_name,c.time,c.order_sn,c.last_op_time,c.applicant,c.status,card.order_sn as cardSN,card.spec,card.keeper,card.department,card.original_value,g.cur_user,g.cur_department")
			->table('k_change c,k_change_goods g,k_base_goods bg,k_category cat,k_cards card')
			->page($page,$size)
			->where($w)
			->group('c.order_sn')
			->select();
		foreach ($lists as $key => $value) {
			$lists[$key]['time']=dateFormat($lists[$key]['time'],'Y-m-d');
		}
		$this->ajaxReturn($lists);
	}

	/**
	 * 编辑操作
	 * @return [type] [description]
	 */
	public function edit()
	{
		if (IS_POST) {
			$admin=$this->admin['info'];
			$ni=I();
			$other=$ni['o'];
			unset($ni['o']);
			//先删除之前的申请单
			// $MDC=M('Change');
			$M=M('Change');$M->startTrans();
			$order=$M->field('id,status')->where(array('order_sn'=>$other['ordersn']))->find();
			if ($order['status']!=1) {
				$this->error('申请单已被审核不能修改！');
			}
			// $MDC->startTrans();
			$d_order=$M->where(array('id'=>$order['id']))->delete();
			$d_audit=M('ChangeAudit')->where(array('change_id'=>$order['id']))->delete();
			$d_goods=M('ChangeGoods')->where(array('change_id'=>$order['id']))->delete();
			$d_msg=M('Message')->where(array('type'=>65,'apply_id'=>$order['id']))->delete();
			if (!$d_order||!$d_audit||!$d_goods||!$d_msg) {
				$M->rollback();
				$this->error('保存失败！');
			}
			// $MDC->commit();
			!$ni&&$this->error('未添加审核物品！');
			foreach ($ni as $key => $value) {
				if (empty($value['order_sn'])) {
					unset($ni[$key]);
				}
			}
			!$ni&&$this->error('未添加审核物品！');
			// if ($admin['is_supper']) {
			// 	if (!$other['org_id']) {$this->error('请选择申请单所属部门！');}
			// 	$org_id=$other['org_id'];
			// }else{
			// 	$org_id=$admin['org_id'];
			// }
			!$other['department_id']&&$this->error('未选择审核部门！');
			$other['department_id']=(array)$other['department_id'];
			foreach ($other['department_id'] as $key => $value) {
				if ($value==0) {
					unset($other['department_id'][$key]);
				}
			}
			if (!$other['department_id']) {
				$this->error('未选择审核部门！');
			}
			if (count($other['department_id'])!=count(array_unique($other['department_id']))) {
				$this->error('请勿重复添加相同审核部门！');
			}
			$time=$other['date']?strtotime($other['date']):time();
			$addorder=array(
				'order_sn'  =>$other['ordersn'],
				'time'      =>$time,
				'remark'    =>$other['remark'],
				'applicant_id' =>$admin['id'],
				'applicant' =>$admin['name'],
				'status'    =>1,
				'org_id'	=>$order['org_id'],
				'type'		=>2
			);
			// $M=M('Change');$M->startTrans();
			//创建申请单
			$orderID=$M->add($addorder);
			if (!$orderID) {
				$M->rollback();$this->error('保存失败！');
			}
			$goods=array();
			foreach ($ni as $key => $value) {
				if (!$value['cur_user']&&!$value['cur_department']) {
					$this->error($value['order_sn'].':未填写变更信息！');
				}
				$card=M('Cards')->field('keeper,department')->where(array('order_sn'=>$value['order_sn']))->find();
				if ($card) {
					$goods[]=array(
						'change_id'           =>$orderID,
						'card_sn'             =>$value['order_sn'],
						'cur_user'            =>$value['cur_user'],
						'cur_department'      =>$value['cur_department'],
						'original_user'       =>$card['keeper'],
						'original_department' =>$card['department']
					);
				}
			}
			!$goods&&$this->error('未添加审核物品！');
			//保存申请单物品
			$re=M('ChangeGoods')->addAll($goods);
			if (!$re) {
				$M->rollback();$this->error('保存失败！');
			}
			$users=array();
			foreach ($other['department_id'] as $key=>$vd) {
				$department=M('Depart')->where("id=$vd")->getField('depart_name');
				$tuser=M('Admin')->field('id,name')->where("depart_id=$vd and is_auditer=1")->find();
				if (!$tuser) {
					$this->error($department.' 没有审核人员!');
				}
				$users[]=array(
					'change_id' =>$orderID,
					'audit_id'  =>$tuser['id'],
					'audit_name'=>$tuser['name'],
					'sort'      =>$key
				);
			}
			//保存申请单审核人
			$re_audit=M('ChangeAudit')->addAll($users);
			if (!$re_audit) {
				$M->rollback();$this->error('保存失败！');
			}
			$receive=array_shift($users);
			$msg=array(
				'title'=>'资产变更申请审核信息',
				'apply_id'=>$orderID, //申请单ID
				'send_id'=>$admin['id'],
				'type'=>65,
				'receive_id'=>$receive['audit_id'],
				'url'=>U('audit',array('id'=>$orderID)),
				'send_time'=>time()
			);
			//添加消息
			$re_msg=M('Message')->add($msg);
			if ($re_msg) {
				$M->commit();$this->success('保存成功！',U('auditlist'));
			}else{
				$M->rollback();$this->error('保存失败！');
			}

		}else{
			$id=I('id');
			!$id&&$this->error('参数错误！');
			//查出审核单
			$order=M('Change')->where(array('id'=>$id))->find();
			if ($order['status']!=1) {
				$this->error('申请单已被审核不能修改！');
			}
			$this->assign('order',$order);
			//查出物品
			$goods=M()
				// ->field('c.id,c.order_sn,c.spec,c.keeper,c.department,c.original_value,bg.assets_name,bg.unit,cat.cate_name,cg.cur_department,cg.cur_user')
				->field('c.id,c.order_sn,c.spec,cg.original_user as keeper,cg.original_department as department,c.original_value,bg.assets_name,bg.unit,cat.cate_name,cg.cur_department,cg.cur_user')
				->table('k_cards c,k_base_goods bg,k_category cat,k_change_goods cg')
				->where("c.goods_id=bg.id and bg.cate_id=cat.id and c.order_sn=cg.card_sn and cg.change_id=$id")
				->select();
			foreach ($goods as $key => $value) {
				$w="id in({$goods[$key]['department']},{$goods[$key]['cur_department']})";
				$dep=M('Depart')->where($w)->select();
				foreach ($dep as $k => $v) {
					if ($v['id']==$goods[$key]['department']) {
						$goods[$key]['department']=$v['depart_name'];
					}
				}
			}
			$this->assign('list_dep',$this->getDeparts($order['org_id']));
			$this->assign('goods',$goods);
			//查出审核人
			$audits=M()->field('a.name,d.id as depart_id,d.depart_name,a.signature,ca.status,ca.audit_id')
				->table('k_admin a,k_change_audit ca,k_depart d')
				->where("a.id=ca.audit_id and d.id=a.depart_id and ca.change_id=$id")
				->order('ca.sort')
				->select();
			// echo "<pre />";
			// print_r($audits);exit();
			$this->assign('audits',$audits);
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
			//查处变更单
			$order=M('Change')->where(array('id'=>$ni['id']))->find();
			!$order&&$this->error('参数错误！');
			//如果申请单状态为已通过或者未通过则不能继续审核
			if ($order['status']==3||$order['status']==4) {
				$this->error('该申请单已关闭，不能操作！');
			}
			//查出审核人
			$CAM=M('ChangeAudit');
			$Taudits=$CAM
				->field('status,audit_id,sort')
				->where("change_id={$ni['id']}")
				->order('sort')
				->select();
			!$Taudits&&$this->error('参数错误！');
			$audits=array_reduce($Taudits,function(&$audits,$v){
	            $audits[$v['audit_id']] = $v;
	            return $audits;
	        });
			$count=count($audits)-1;
			$cur=$audits[$this->admin['info']['id']];
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
			$re=$CAM->where(array('change_id'=>$ni['id'],'audit_id'=>$cur['audit_id']))->save(array('time'=>$ni['time'],'remark'=>$ni['remark'],'status'=>$ni['status']));
			if (!$re&&$re!==0) {
				$CAM->rollback();$this->error('审核失败！1');
			}
			if ($cur['sort']==$count) {
				//最后一个审核人
				if ($ni['status']==2) {//最后一个人审核通过则修改卡片信息
					//查出物品
					$goods=M('ChangeGoods')->where(array('change_id'=>$ni['id']))->select();

					foreach ($goods as $key => $value) {
						//处理卡片
						$re_c=M('Cards')->where(array('order_sn'=>$value['card_sn']))->save(array('department'=>$value['cur_department'],'keeper'=>$value['cur_user']));
						if (!$re_c&&$re_c!==0) {
							$re_c==false;break;
						}
					}
					if (!$re_c) {
						$CAM->rollback();$this->error('审核失败！2');
					}
					//申请单状态处理  1未审核  2审核中 3未通过  4通过
					$re_change=M('Change')->where(array('id'=>$ni['id']))->save(array('status'=>4,'last_op_time'=>time()));

					if (!$re_change&&$re_change!==0) {
						$CAM->rollback();$this->error('审核失败！3');
					}
					//最后一个审核通过 给申请人推送 审核通过消息
					$msg=array(
						'title'=>'资产变更申请审核通过信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$cur['audit_id'],
						'type'=>65,
						'receive_id'=>$order['applicant_id'],
						'url'=>U('audit',array('id'=>$order['id'])),
						'send_time'=>time()
					);
					//添加消息
					$re_msg=M('Message')->add($msg);

					
				}else{//最后一个人审核未通过
					//申请单状态处理  1未审核  2审核中 3未通过  4通过
					$re_change=M('Change')->where(array('id'=>$ni['id']))->save(array('status'=>3,'last_op_time'=>time()));
					if (!$re_change&&$re_change!==0) {
						$CAM->rollback();$this->error('审核失败！4');
					}

					//最后一个审核未通过 给申请人推送 审核未通过消息
					$msg=array(
						'title'=>'资产变更申请审核未通过信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$cur['audit_id'],
						'type'=>65,
						'receive_id'=>$order['applicant_id'],
						'url'=>U('audit',array('id'=>$order['id'])),
						'send_time'=>time()
					);
					//添加消息
					$re_msg=M('Message')->add($msg);
				}
			}else{//不是最后一个审核人
				if ($ni['status']==2) {//审核通过
					//申请单状态处理  1未审核  2审核中 3未通过  4通过
					$re_change=M('Change')->where(array('id'=>$ni['id']))->save(array('status'=>2,'last_op_time'=>time()));
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
						'title'=>'资产变更申请审核信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$order['applicant_id'],
						'type'=>65,
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
					$re_change=M('Change')->where(array('id'=>$ni['id']))->save(array('status'=>3,'last_op_time'=>time()));
					if (!$re_change&&$re_change!==0) {
						$CAM->rollback();$this->error('审核失败！6');
					}

					//审核未通过 给申请人推送 审核未通过消息
					$msg=array(
						'title'=>'资产变更申请审核未通过信息',
						'apply_id'=>$order['id'], //申请单ID
						'send_id'=>$cur['audit_id'],
						'type'=>65,
						'receive_id'=>$order['applicant_id'],
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
			$wm=array('type'=>65,'apply_id'=>$order['id'],'receive_id'=>$cur['audit_id']);

			$re_m=M('Message')->where($wm)->save(array('status'=>2));
			if ($re_m!==false) {
				$CAM->commit();
				$this->success('审核成功！',U('auditlist'));
			}else{
				$CAM->rollback();
				$this->error('审核失败！8');
			}
			
		}else{
			$id=I('id');
			!$id&&$this->error('参数错误！');
			//查出审核单
			$order=M('Change')->where(array('id'=>$id))->find();
			$admin=$this->admin['info'];
			if (($order['status']==3||$order['status']==4) && $order['applicant_id']==$admin['id']) {
				$wm=array('type'=>65,'apply_id'=>$order['id'],'receive_id'=>$admin['id']);
				M('Message')->where($wm)->save(array('status'=>2));
			}
			$this->assign('order',$order);
			//查出物品
			$goods=M()
				->field('c.id,c.order_sn,c.spec,cg.original_user as keeper,cg.original_department as department,c.original_value,bg.assets_name,bg.unit,cat.cate_name,cg.cur_department,cg.cur_user')
				->table('k_cards c,k_base_goods bg,k_category cat,k_change_goods cg')
				->where("c.goods_id=bg.id and bg.cate_id=cat.id and c.order_sn=cg.card_sn and cg.change_id=$id")
				->select();
			foreach ($goods as $key => $value) {
				$w="id in({$goods[$key]['department']},{$goods[$key]['cur_department']})";
				$dep=M('Depart')->where($w)->select();
				foreach ($dep as $k => $v) {
					if ($v['id']==$goods[$key]['department']) {
						$goods[$key]['department']=$v['depart_name'];
					}
					if ($v['id']==$goods[$key]['cur_department']) {
						$goods[$key]['cur_department']=$v['depart_name'];
					}
				}
			}
			$this->assign('goods',$goods);
			//查出审核人
			$Taudits=M()->field('a.name,d.depart_name,a.signature,ca.status,ca.audit_id,ca.sort,ca.time,ca.remark,ca.audit_name')
				->table('k_admin a,k_change_audit ca,k_depart d')
				->where("a.id=ca.audit_id and d.id=a.depart_id and ca.change_id=$id")
				->order('ca.sort')
				->select();
			$user=$this->admin['info'];

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
        	$De = $cat->getPath($topid);
            $De[0]['fullname'] = $De[0]['depart_name'];
            $Depart = $cat->getChildren($org,$lists) ;
            $Depart = array_merge($De,$Depart);
        }else{
        	$Depart = $this->admin['info']['is_supper'] ? $lists : $cat->getChildren($org,$lists);
        }
        return $Depart;
    }

    public function export()
    {
    	
    	$goods_name=I('goods_name');
		$start_time=I('start_time');
		$end_time=I('end_time');
		$card_sn=I('card_sn');

		$key_word=I('key_word');

		$w="c.id=g.change_id and g.card_sn=card.order_sn and card.goods_id=bg.id and bg.cate_id=cat.id and c.status= 4 and c.type=2  and card.department=fd.id";
		if ($key_word) {
			$w.=" and (bg.assets_name like '%{$key_word}%' or c.order_sn like '%{$key_word}%' or fd.depart_name like '%{$key_word}%')";
		}
		if ($goods_name) {
			$w.=" and bg.assets_name like '%{$goods_name}%' ";
		}
		if ($start_time&&$end_time) {
			$tst=strtotime($start_time);
			$tet=strtotime($end_time);
			$w.=" and c.time BETWEEN $tst and $tet ";
		}elseif ($start_time) {
			$tst=strtotime($start_time);
			$w.=" and c.time>$tst ";
		}elseif ($end_time) {
			$tet=strtotime($end_time);
			$w.=" and c.time<$tet ";
		}
		if ($card_sn) {
			$w.=" and card.order_sn like '%{$card_sn}%'";
		}

		$lists=M()->field('bg.assets_name,cat.cate_name,c.time,c.order_sn,card.spec,fd.depart_name,card.original_value')
			->table('k_change c,k_base_goods bg,k_category cat,k_cards card,k_depart fd, k_change_goods g')
			// ->join('k_depart d on g.original_department=d.id ')
			->page(I('page'),50)
			->where($w)
			->select();
		return $lists;
    }
}