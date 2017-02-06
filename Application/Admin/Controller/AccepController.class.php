<?php
// +----------------------------------------------------------------------
// | QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com/)
// +----------------------------------------------------------------------
// | Author: wu hui (13278760361@163.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-30 10:51:43
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 资产验收
 */
class AccepController extends BaseController {
    
    function _initialize(){
        parent::_initialize();
        $this->accep = M('acceptance_goods');
        $this->is_supper = $_SESSION['admin']['info']['is_supper']; //是否是超级管理员 可以查看添加所有数据
      
        $this->org_id   = $_SESSION['admin']['info']['org_id'];  //部门ID 顶级
 
        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

        $lists = $cat->getList(0);

        $self =  $cat->  getInfo($this->org_id,$lists);

        $childs = $cat-> getChildren($this->org_id,$lists);
       // $childs = $cat-> getList($this->org_id,$lists);
        $this->departs = $this->is_supper ? $lists : $childs ;
        $this->admin = session('admin');
        $this->assign('is_supper',$this->is_supper);
        $this->assign('departs',$this->departs);
    }
    public $outlist=array('id'=>'序号','assets_name'=>'物品名称','spec'=>'规格型号','num'=>'数量','department'=>'部门','buyer'=>'采购人','enter_time'=>'到货日期','address'=>'存放地点','live_time'=>'使用年限');
    
    public function index(){

    	$where = "a.department=d.id";
    	$order_sn = I('post.order_sn');
    	$str_time = strtotime(I("post.str_time"));
    	$end_time = strtotime(I("post.end_time"));
        if($_SESSION['admin']['info']['org_id'] !="" && $_SESSION['admin']['info']['org_id'] !=0){
            $where.=" AND a.org_id='".$_SESSION['admin']['info']['org_id']."'";
        }
    	if($order_sn!=""){
    		$where.=" AND a.order_sn LIKE '%{$order_sn}%'";
    	}
    	if($str_time!="" && $end_time!=""){
    		$where.=" AND a.check_date between '{$str_time}' and '{$end_time}'";
    	}
        $depar = I('post.department');
        if($depar!=0){
            $where.=" AND a.department='{$depar}'";
        }
    	$field = "a.id,a.check_date,a.time,a.order_sn,d.depart_name as department";
    	$count = M('acceptance')->table(array('k_acceptance'=>'a','k_depart'=>'d'))->where($where)->count();
    	$page = new \Think\PageA($count,15);
    	$show           = $page->show();
    	$lists =  M('acceptance')->field($field)->table(array('k_acceptance'=>'a','k_depart'=>'d'))->limit($page->firstRow.','.$page->listRows)->order('a.id DESC')->where($where)->select();
    	// if(!empty($lists)){
    	// 	foreach($lists as $key=>$val){
    	// 		$lists[$key]['number'] = $key;
    	// 	}
    	// }
    	//var_dump($where);exit;
    	$this->assign('assets_name',$g_name);
    	$this->assign('lists',$lists);
    	$this->assign('page',$show);
        $this->assign('depart',$depar);
        $this->assign('order_sn',$order_sn);
        $this->assign('str_time',I("post.str_time"));
        $this->assign('end_time',I("post.end_time"));
    	$this->display();
    }
    //添加
    public function add(){
    	
    	if(IS_POST){
    		// dump($datas);exit;
    		//$dAccep = $datas['assets_name'];
           // $datas = I('post.');
            // echo "<pre>";
            // print_r(I('post.'));exit;
            $datas = I('post.');
    		$dAccep['order_sn'] = $datas['acp']['order_sn'];
    		
            $dAccep['check_content'] = $datas['acp']['check_content'];
    		if($datas[1]['assets_name']==""){
                $this->error("请填写物品名称！！");
            }
            if($datas['acp']['check_content']==""){
    			$this->error("填写检验内容！！");
    		}
           //判断是否有相同部门
            if (count((array)$datas['acp']['depar']) != count(array_unique((array)$datas['acp']['depar']))) {    
               $this->error("不允许有相同的部门！！");
            }
    		$dAccep['last_op_time'] = time();
    		$dAccep['opinion'] = $datas['acp']['opinion'];
    		$dAccep['checker'] = $datas['acp']['checker'];
    		$dAccep['check_date'] = strtotime($datas['acp']['check_date']);
    		$dAccep['department'] = $datas['acp']['depart']?$datas['acp']['depart']:$_SESSION['admin']['info']['depart_id'];
            $dAccep['applicant'] = $_SESSION['admin']['info']['id'];
    		$dAccep['applicant'] = $_SESSION['admin']['info']['username'];
            $dAccep['org_id'] = $_SESSION['admin']['info']['org_id'];
           
    		$rs = M('acceptance')->data($dAccep)->add();
    		if($rs){
                foreach((array)$datas['acp']['depar'] as $k=>$v){
                    $a_data['change_id'] = $rs;
                    $a_data['audit_id'] = $v;
                    $a_data['time'] = time();
                    M('acceptance_audit')->add($a_data);

                }
                if(isset($datas['acp'])){unset($datas['acp']);}
    			foreach($datas as $key=>$val){
                    if($val['assets_name']!=""){
        				$data['spec'] = $val['spec'];
                        $data['supper'] = $val['supper'];
        				$data['goods_id'] = $val['id'];
        				$data['unit'] = $val['unit'];
        				$data['goods_num'] = $val['goods_num'];
        				$data['price'] = $val['price'];
        				$data['total'] = $val['total'];
        				$data['buyer'] = $val['buyer'];
        				$data['enter_time'] = strtotime($val['apply_time']);
        				$data['address'] = $val['address'];
        				$data['live_time'] = $val['live_time'];
        				$data['remark'] = $val['remark'];
        				$data['accep_id'] = $rs;
        				$this->accep->data($data)->add(); 
                    }
    			}
    			$this->success("提交成功！！",U('index'));
    		}else{
    			$this->error("提交失败！！");
    		}
    		// $data['spec'] = $datas['spec'];
    		// //$goodsinfo = M('base_goods')->where(array('assets_name'=>$datas['']))->find();
    		// $date['goods_id'] = $datas['goods_id'];
    		// $data['unit'] = $datas['unit'];
    		// $data['goods_num'] = $datas['goods_num'];
    		// $data['price'] = $datas['price'];
    		// $data['total'] = $datas['total'];
    		// $data['buyer'] = $datas['buyer'];
    		// $data['enter_time'] = strtotime($datas['enter_time']);
    		// $data['address'] = $datas['address'];
    		// $data['live_time'] = $datas['live_time'];
    		// $data['remark'] = $datas['remark'];
    		// $rs = 
    		// if($rs){
    		// 	$this->success("提交成功！！",U('index'));
    		// }else{
    		// 	$this->error("提交失败！！");
    		// }
    	}else{
    		$where = "bg.type_id=3";
    		$field = "bg.*";
            $org_id=I('get.org')?:0;
    		$lists = M('base_goods')->field($field)->table(array('k_base_goods'=>'bg'))->where($where)->select();
    	//	$depart = M('depart')->select();
    		if(!empty($lists)){
    			foreach($lists as $key=>$val){
    				$lists[$key]['apply_time'] = date('Y-m-d',$val['apply_time']);
    			}
    		}

            $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
            $cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
            $top_departs = $cat->getList($c_id);
            
            $this->assign('top_departs',$top_departs);
           // print_r($lists);exit;
    		$lists=$lists?json_encode($lists):'""';
    		$this->assign('lists',$lists);
           // print_r($org_id);exit;
            $this->assign('org',$org_id);
            $this->assign('list_dep',$this->getDeparts($org_id));
    	//	$this->assign('depart',$depart);
            $this->assign('order_sn',createOrderSn(C('YSSN')));
    		$this->display();	
    	}
    	
    }
    //根据物品名查找
    public function get_goods(){
        $gname = I('post.value');
        //print_r($gname);exit;
        !$gname&&$this->ajaxReturn('');
        $where = "bg.cate_id=c.id AND bg.type_id=t.id AND bg.type_id=3";
        $field = "bg.*,c.cate_name,c.sn,t.type_name";
        if($gname!=""){
            $where.= " AND bg.assets_name LIKE '%{$gname}%'";
        }
        
        $lists = M()->field($field)->table(array('k_base_goods'=>'bg','k_category'=>'c','k_type'=>'t'))->where($where)->limit(10)->select();
        foreach ($lists as $key => $value) {
            # code...
            $lists[$key]['add_time'] = date('Y-m-d',$value['add_time']);
        }
        //print_r($lists);exit;
        $this->ajaxReturn($lists);
    }
    //查看验收单
    public function edit(){
    	$accep_id = I('get.accep_id');
    	//$spec = I('post.spec');
    	if(IS_POST){

    	}else{
			$where = "a.id=ag.accep_id AND ag.goods_id=bg.id";
    		$field = "ag.*,bg.assets_name";
    		if($accep_id!=''){
    			$where.=" AND ag.accep_id='{$accep_id}'";
    		}
            $saw = "aa.audit_id=d.id AND ad.depart_id=d.id AND aa.change_id='{$accep_id}' AND is_auditer=1";

            $acw = "ac.department=d.id AND ac.id='{$accep_id}'";

    		$info = M('acceptance')->field('ac.*,d.depart_name')->table(array('k_acceptance'=>'ac','k_depart'=>'d'))->where($acw)->find();

    		$lists = $this->accep->table(array('k_acceptance_goods'=>'ag','k_base_goods'=>'bg','k_acceptance'=>'a'))->where($where)->select();
            
            $departs = M('acceptance_audit')->field('aa.*,d.depart_name,d.id as d_id,ad.username,ad.name,ad.signature')->table(array('k_acceptance_audit'=>'aa','k_depart'=>'d','k_admin'=>'ad'))->where($saw)->select();

            //echo M('acceptance_audit')->getLastSql();exit;
    		$this->assign('lists',$lists);
    		$this->assign('info',$info);
            $this->assign('departs',$departs);
    		$this->display();
    	}
    	
    }
    //导出数据
    public function export(){
      
        $where = "ag.goods_id=bg.id AND a.id=ag.accep_id AND a.department=d.id";
        $order_sn = I('get.order_sn');
        $str_time = strtotime(I("get.start_time"));
        $end_time = strtotime(I("get.end_time"));
        $depart = strtotime(I("get.depart"));
        $len = I('size')?:50;
        $page = I('page');
        $first = $len*($page-1);
        if($order_sn!=""){
            $where.=" AND bg.order_sn LIKE '%{$order_sn}%'";
        }
        if($str_time!="" && $end_time!=""){
            $where.=" AND a.check_date between '{$str_time}' and '{$end_time}'";
        }
        if($depart!=0){
            $where.=" AND a.department='{$depart}'";
        }
        if($_SESSION['admin']['info']['org_id'] !="" && $_SESSION['admin']['info']['org_id'] !=0){
            $where.=" AND a.org_id='".$_SESSION['admin']['info']['org_id']."'";
        }
        $field = "ag.*,bg.assets_name,a.check_date,d.depart_name as department";

        $lists = $this->accep->table(array('k_acceptance_goods'=>'ag','k_base_goods'=>'bg','k_acceptance'=>'a','k_depart'=>'d'))->order('a.id DESC')->limit($first,$len)->where($where)->select();
        if(!empty($lists)){
            foreach ($lists as $key => $value) {
                # code...
                $lists[$key]['id'] = ($page-1)*$len+$key+1;
                $lists[$key]['time'] = date('Y-m-d',$value['time']);
            }
        }
        
        //print_r($lists);exit;
        return $lists;
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

}