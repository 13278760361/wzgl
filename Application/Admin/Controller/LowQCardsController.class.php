<?php
// +----------------------------------------------------------------------
// | QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com/)
// +----------------------------------------------------------------------
// | Author: wu hui (13278760361@163.com)
// +----------------------------------------------------------------------
// | Date: 2017-01-12 20:57:48
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 扫描不登录也能查看
 */
class LowQCardsController extends Controller {
    
	public function _initialize()
    {
        $c=isMobile()?C('mobile'):C('default');foreach($c as $k=>$v){C($k,$v);}
    }
    public function index(){
    	if(!$_SESSION['admin']['info']['id']){
    		$id = I('get.id');
               // print_r($id);exit;
            $where = "c.goods_id=bg.id AND bg.cate_id=cg.id AND bg.type_id=t.id AND c.department=d.id AND c.supper_id=s.id";
            if($id!=""){
                $where.=" AND c.id='{$id}'";
            }
            if(I('get.order_sn')!=""){
                $where.=" AND c.order_sn='".I('get.order_sn')."'";
            }
            $field = "c.*,bg.assets_name,cg.cate_name,cg.sn,t.type_name,d.depart_name as department,s.company_name";
            $info = M('Cards')->field($field)->table(array('k_cards'=>'c','k_base_goods'=>'bg','k_category'=>'cg','k_type'=>'t','k_depart'=>'d','k_supplier'=>'s'))->where($where)->find();
            //$info['qrcode'] = $_SERVER['HTTP_HOST'].'/'.$info['qrcode'];
            //print_r(count($this->
            //));exit;
            $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

            $lists = $cat->getList(0);

            $self =  $cat->  getInfo($info['org_id'],$lists);
            //print_r($info['org_id']);exit;
            $info['depart_name'] = $self['depart_name'];
            $data['depr'] = $info['depr_method'];
            $data['start_date'] = date('Y-m-d',$info['start_date']);
            $data['service_life'] = $info['service_life'];
            $data['original_value'] = intval($info['original_value']);
            $data['net_salvage'] = intval($info['net_salvage']);
            $data['vat_rate'] = $info['vat_rate'];
            $data['month_works'] = $info['month_works'];
            $data['mon_total'] = $info['mon_total'];
           // $data['user'] = end(explode('/',$info['user']));

            $jg = deprs($data);
            $info['net_salvage'] = round($info['net_salvage'],2);
            $info['net_residual_value'] = $jg['net_residual_value'];
            $info['mon_depr_sum'] = $jg['mon_depr_sum'];
            $info['mon_depr_rate'] = $jg['mon_depr_rate'];
            $info['mon_depr_amount'] = $jg['mon_depr_amount'];
            $info['net_worth'] = $jg['net_worth'];
            $info['k_net_worth'] = $jg['k_net_worth'];
            $info['mon_total'] = $jg['sueM'];
            $info['use_depart'] = end(explode('/',$info['use_depart']));
            $info['use_status'] = $info['use_status']?$info['use_status']:"使用中";
            $this->assign('use_status',$this->use_status);
            $this->assign('depr_method',$this->depr_method);
            $this->assign('info',$info);
            $this->display();
    	}else{
    		redirect(U('Cards/edit',array('order_sn'=>I('get.order_sn'))));
    	}
    }
}