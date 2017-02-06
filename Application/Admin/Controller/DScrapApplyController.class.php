<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-24 11:22:44
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 物资基础信息处理
 */
class DScrapApplyController extends BaseController
{
   

    public function _initialize(){
      parent::_initialize();
      $this->db = M('Scrap'); //当前模块数据库
      $this->db_sg =M('Scrap_goods');
      $this->db_sa = M('Scrap_audit');
      $this->db_d = M('Depart');
      $this->db_a = M('Admin');
      $this->db_c = M('Cards');
      $this->db_m =M('Message');  

      $this->is_supper = $_SESSION['admin']['info']['is_supper']; //是否是超级管理员 可以查看添加所有数据
      
      $this->org_id    = $_SESSION['admin']['info']['org_id'];  //部门ID 顶级
 
      $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

      $lists = $cat->getList(0);

      $this->departs = $this->is_supper ? $lists : $cat->getChildren($this->org_id,$lists);
    
      $this->assign('is_supper',$this->is_supper);
      $this->assign('departs',$this->departs);
    } 

    //列表
    public function index()
    {
        $order_sn = I('order_sn');
        $time = I('time');
        $creater = I('creater');
        $status = isset($_GET['status'])?I('status'):'all'; 
        $len = 10;
 
        $field='id,order_sn,creater,last_op_time,time,status,create_id';

      if ($status == 'all') {
            $where = "type = 2 and status IN (1,2,3,4)";
        }elseif ( $status == 'do' ) { //已审核
            $where = "type = 2  and status IN (3,4)";
        }else{
            $where = "type = 2  and status IN ({$status})"; 
        }
        
        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and org_id = ({$this->org_id})";
        }


        if (!empty($order_sn)) {
          $where .=" and order_sn like '%{$order_sn}%'";
        }

        if ( !empty($creater) ) {
            $where .= " and creater like '%{$creater}%'";
        }

        if (!empty($time) ) {
          $where .= " and FROM_UNIXTIME(time, '%Y-%m-%d') = '{$time}'";
        }

        $count          =  $this->db->where($where)->count();
        $page           = new \Think\PageA($count,$len);
        $show           = $page->show();


         $lists =  $this->db
        ->field($field)      
        ->limit($page->firstRow.','.$page->listRows)
        ->where($where)
        ->order('time DESC')
        ->select();

        // echo $this->db->getLastSql();
          // dump($lists);
        $this->assign('lists',$lists);
        $this->assign('page',$show);
        $this->assign('order_sn',$order_sn);
        $this->assign('creater',$creater);
        $this->assign('time',!empty($time)?$time:'');
        //手机端
        $this->assign('status',$status);
        $this->assign('ajaxlen',$len);
        $this->display();
    }


     public function ajaxScrapApply()
    {
        $page=I('get.page');
        $len=I('get.size')?:1;
        $status=I('get.status');
        $first=$len*($page-1);

        // dump($key);exit();

        $field='id,order_sn,creater,last_op_time,time,status';

         if ($status == 'all') {
            $where = "type = 2  and status IN (1,2,3,4)";
        }else{
            $where = "type = 2  and status = {$status}"; 
        }

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and org_id = ({$this->org_id})";
        }
        
         $lists =  $this->db
        ->field($field)      
        ->limit($first,$len)
        ->where($where)
        ->order('time DESC')
        ->select();
        // echo $this->db->getLastSql();
        foreach ($lists as $key => $val) {
           $lists[$key]['time'] = date('Y-m-d H:i',$val['time']);
        }

        $this->ajaxReturn($lists);
    }

    public function info(){

        if (IS_POST) {
             
             $_data = I('post.');
             // dump($_data);exit();

             $this->db->startTrans();//开启事物

             $where['scrap_id'] = $_data['data']['scrap_id'];
             $where['audit_id'] = $_SESSION['admin']['info']['id'];
             
             $data['time'] = time();
             $data['status'] = $_data['data']['status'];
             $data['explain'] = $_data['data']['explain'];

             if ( empty($data['status']) ) {
                  $this->error('请选择审核状态');
             }

             if ( empty($data['explain']) ) {
                 $this->error('请填写原因');
             }

             $res =  $this->db_sa ->where($where)->save($data);

             $map['status'] = array('IN','0,1'); //找到未审核
             $map['scrap_id'] = $_data['data']['scrap_id'];


             $res_2  = $this->db_m->where( array('apply_id'=>$where['scrap_id'],'receive_id'=>$where['audit_id'],'type'=>75) )->setField('status',2); //更改消息状态

           

             if ( !$this->db_sa->where($map)->find() ) { //如果已经没有未操作，不通过的状态  改变入库单状态

                $goods_lists = $this->db_sg->where( array('scrap_id'=>$_data['data']['scrap_id']) )->getField('card_sn',true);
                foreach ($goods_lists as $key => $val) {
                   $res_3 = $this->db_c->where( array('order_sn'=>$val) )->setField('use_status',3);//更改状态3报废
                }     

                  $whereS['id'] = $_data['data']['scrap_id'];
                  $res_4 =  $this->db->where( $whereS )->setField('status',4); //更改单状态

                    //通过给创建人发送消息
                     $dataM['title'] = '低值品报废申请审核信息通过';
                     $dataM['apply_id'] = $_data['data']['scrap_id'];
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $this->db->where( array('scrap_id'=>$_data['data']['scrap_id']) )->getField('create_id');
                     $dataM['status'] = 1;
                     $dataM['url'] = U('ScrapApply/info',array('id'=> $_data['data']['scrap_id'] ));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 75;
                     $res_5 = $this->db_m->add($dataM);
             }else{ //不进行任何操作

                  
                  $whereS['id'] = $_data['data']['scrap_id'];

                  $mapJ['status'] = 1;
                  $mapJ['scrap_id'] = $_data['data']['scrap_id']; 
                  $count_j =  $this->db_sa->where($mapJ)->count();//拒绝个数

                  if ($count_j > 0) { //只要有拒绝
                      $res_3 =  $this->db->where( $whereS )->setField('status',3); //拒绝
                      $res_4 = true;

                     $dataM['title'] = '低值品报废申请审核信息未通过';
                     $dataM['apply_id'] = $_data['data']['scrap_id'];
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $this->db->where( array('scrap_id'=>$_data['data']['scrap_id']) )->getField('create_id');
                     $dataM['status'] = 1;
                     $dataM['url'] = U('ScrapApply/info',array('id'=> $_data['data']['scrap_id'] ));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 75;
                     $res_5 = $this->db_m->add($dataM);
                  }else{
                      $res_3 =  $this->db->where( $whereS )->setField('status',2); //审核中
                      //推送下个人消息
                         $dataM['title'] = '低值品报废申请审核信息';
                         $dataM['apply_id'] = $_data['data']['scrap_id'];
                         $dataM['send_id']  = $_SESSION['admin']['info']['id'];

                         $sort = $this->db_sa->where(array('scrap_id'=>$_data['data']['scrap_id'],'audit_id'=>$_SESSION['admin']['info']['id']) )->getField('sort'); //获取当前审核人排序

                         $dataM['receive_id'] =  $this->db_sa->where(array('scrap_id'=>$_data['data']['scrap_id'],'sort'=>$sort+1) )->getField('audit_id'); //获取下一个审核人
                         $dataM['status'] = 1;
                         $dataM['url'] = U('ScrapApply/info',array('id'=> $_data['data']['scrap_id'] ));
                         $dataM['send_time'] = time();
                         $dataM['type'] = 75;
                         //消息推送
                         $res_4 = $this->db_m->add($dataM);
                         $res_5 = true;
                  }

                  
             }

            
             if ($res !==false  && $res_2 !==false && $res_3 !==false && $res_4!==false && $res_5!==false) {
                  $this->db->commit(); 
                  $this->success('审核成功',U('index'));
             }else{
                  $this->db->rollback();
                  $this->error('审核失败');
             }



        }else{
                $id = I('get.id');

                $table = "k_scrap_goods sg, k_cards c,k_base_goods bg,k_category ca";
                $field="c.order_sn,bg.assets_name,bg.unit,ca.cate_name,c.spec,c.original_value,c.start_date,c.service_life";
                $where ="sg.card_sn = c.order_sn and c.goods_id = bg.id and bg.cate_id = ca.id and sg.scrap_id ={$id} ";

                $goods_lists =  $this->db_sg->table($table)->field($field)->where($where)->select();

            
                
                foreach ($goods_lists as $key => $val) {
                     $goods_lists[$key]['start_date'] = date('Y-m-d',$val['start_date']);
                }

                $info = $this->db->where(array('id'=>$id))->find();
                $info['time'] = date('Y-m-d',$info['time']);

                $table_1 ="k_scrap_audit sa,k_admin a,k_depart d";
                $field_1="a.id,a.name,a.signature, d.depart_name,sa.status,sa.explain,sa.time,sa.sort";
                $where_1="sa.audit_id = a.id  and a.depart_id = d.id and  sa.scrap_id ={$id}";

                $audits_lists= $this->db_sa->table($table_1)->field($field_1)->where($where_1)->order('sa.sort ASC')->select();

                // dump($audits_lists);
                
                $flag='';//开关按钮默认状态
                foreach ($audits_lists as $key => $val) { //判断是否能操作
                      $audits_lists[$key]['time'] = date('Y-m-d',$val['time']);
                    if ( $val['id'] == $_SESSION['admin']['info']['id'] ) {
                       // dump('in');
                         $map['sort'] = $val['sort'] - 1;
                         $map['scrap_id'] = $id;
                        $f_status = $this->db_sa->where($map)->getField('status');  //寻找上一个人个审核人状态
                            
                         if ($f_status === 0 ||  $f_status == 1) { //未操作、不通过 不能操作
                              $audits_lists[$key]['is_a'] = 0;
                               $flag = 0;//按钮开关   
                             
                         }elseif ( $f_status == 2 && $val['status'] == 0 || $f_status === null  && $val['status'] == 0) { //通过、上面没人的话 能操作
                 
                              $audits_lists[$key]['is_a'] = 1;
                              $flag = 1;//按钮开关
                            
                         }
                                                
                    }else{

                       $audits_lists[$key]['is_a'] = 0;
                       
                       if ($flag == 1) { //继续监测 1=可提交 0=不可提交 //监测到按钮开关开启 
                           $flag = 1;     
                       }elseif($flag == 0){
                           $flag = 0;  //监测到按钮开关关闭
                       }else{
                           $flag = 0;
                       }
                    }
                }
               

               if ($info['create_id'] ==  $_SESSION['admin']['info']['id']  &&  ($info['status'] == 3 || $info['status'] == 4 ) )  {
                   $this->db_m->where( array('apply_id'=>$id,'receive_id'=>$_SESSION['admin']['info']['id'],'type'=>75) )->setField('status',2); //更改消息状态
                }
                
                
               // dump($audits_lists);
                
         
                $this->assign('goods_lists',$goods_lists);//卡片物品列表
                $this->assign('info',$info); //报废详情
                $this->assign('audits_lists',$audits_lists); //审核人员
                $this->assign('scrap_id',$id);
                $this->assign('flag',$flag);


                $this->display();
        }
    }


   

    //添加
    public function add()
    {
        $this->display();
    }

    //编辑
    public function edit()
    {
        $this->display();
    }

    //删除
    public function del()
    {
        $res!==false?$this->success(''):$this->error('');
    }
}
?>