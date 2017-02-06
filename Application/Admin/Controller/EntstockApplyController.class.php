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
class EntstockApplyController extends BaseController 
{
    public function _initialize(){
      parent::_initialize();
      $this->db = M('Entstock'); //当前模块数据库
      $this->db_eg =M('Entstock_goods');
      $this->db_ea = M('Entstockt_audit');
      $this->db_d = M('Depart');
      $this->db_a = M('Admin');
      $this->db_m =M('Message');
      $this->db_bg =M('Base_goods');
      $this->db_su =M('Supplier');
      $this->db_s = M('Stock');
      $this->db_sd = M('Stock_detial');
      $this->db_c = M('Cancel'); //当前模块数据库


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
        
        

        $field='id,order_sn,total_price,status,creater,apply_time,last_op_time,create_id';

        if ($status == 'all') {
            $where = "status IN (1,2,3,4)";
        }elseif ( $status == 'do' ) { //已审核
            $where = "status IN (3,4)";
        }else{
            $where = "status IN ({$status})"; 
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
          $where .= " and FROM_UNIXTIME(apply_time, '%Y-%m-%d') = '{$time}'";
        }

        $count          =  $this->db->where($where)->count();
        $page           = new \Think\PageA($count,10);
        $show           = $page->show();


         $lists =  $this->db->table($table)
        ->field($field)      
        ->limit($page->firstRow.','.$page->listRows)
        ->where($where)
        ->order('apply_time DESC')
        ->select();

        $this->assign('lists',$lists);
        $this->assign('page',$show);
        $this->assign('order_sn',$order_sn);
        $this->assign('creater',$creater);
        $this->assign('time',!empty($time)?$time:'');
        $this->display();
    }

    public function info(){

        if (IS_POST) {
             
             $_data = I('post.');

            // dump($_data);exit();
             
             $this->db->startTrans();//开启事物
              
             $where['entstock_id'] = $_data['data']['entstock_id'];
             $where['audit_id'] = $_SESSION['admin']['info']['id'];
             
             $data['time'] = strtotime($_data['data']['time'])?strtotime($_data['data']['time']):time();
             $data['status'] = $_data['data']['status'];
             $data['explain'] = $_data['data']['explain'];

             if ( empty($data['status']) ) {
                  $this->error('请选择审核状态');
             }

             if ( empty($data['explain']) ) {
                 $this->error('请填写原因');
             }

             $res =  $this->db_ea ->where($where)->save($data);

             $map['status'] = array('IN','0,1'); //找到未审核
             $map['entstock_id'] = $_data['data']['entstock_id'];


             $res_2  = $this->db_m->where( array('apply_id'=>$where['entstock_id'],'receive_id'=>$where['audit_id'],'type'=>20) )->setField('status',2); //更改消息状态
            

             if ( !$this->db_ea->where($map)->find() ) { //如果已经没有未操作，不通过的状态  改变入库单状态
                  $whereE['id'] = $_data['data']['entstock_id'];
                  $res_3 =  $this->db->where( $whereE )->setField('status',4); 

                  $res_4 = true;

                    //给申请人发送消息 进行入库报废审核单添加
                     $dataM['title'] = '入库申请审核信息通过';
                     $dataM['apply_id'] = $_data['data']['entstock_id'];
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $this->db->where( array('id'=>$_data['data']['entstock_id']) )->getField('create_id');
                     
                     $dataM['status'] = 1;
                     $dataM['url'] = U('EntstockApply/info',array('id'=>$_data['data']['entstock_id']));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 20;
                     $res_5 = $this->db_m->add($dataM);
                  
                  //并且进行入库操作

                  //找到对应物品列表
                  // $table = "k_entstock_goods eg,k_base_goods bg,k_type t,k_supplier su";
                  // $field="bg.assets_name,bg.unit,t.type_name,su.company_name,eg.supplier_id,eg.num,eg.price,eg.total,eg.remark,eg.spec,bg.id as goods_id";
                  // $where =" eg.entstock_id = {$_data['data']['entstock_id']}  and  eg.supplier_id = su.id  and bg.type_id = t.id and bg.id = eg.goods_id";

                  // $goods_lists =  $this->db_eg->table($table)->field($field)->where($where)->select();

                  // foreach ($goods_lists as $key => $val) {
                  //      $whereS['goods_id'] = $val['goods_id'];
                  //      $whereS['spec'] = $val['spec'];
                  //     if ( $info =  $this->db_s->where($whereS)->find() ) { //如果存在改物品 加数量和总价
                  //          $res_4 =  $this->db_s->where($whereS)->setInc('num',$val['num']);
                  //          $res_5 =  $this->db_s->where($whereS)->setInc('total',$val['total']);
                           
                  //          $dataSD['stock_id'] = $info['id'];
                  //          $dataSD['time'] = time();
                  //          $dataSD['goods_num'] = $val['num'];
                  //          $dataSD['func'] = '+';
                  //          $dataSD['price'] = $val['price'];
                  //          $dataSD['total'] = $val['total'];
                           

                  //          $res_6 = $this->db_sd->add($dataSD);

                  //      }else{
                  //          $dataS['goods_id'] = $val['goods_id'];
                  //          $dataS['supplier_id'] =$val['supplier_id'];
                  //          $dataS['spec'] =$val['spec'];
                  //          $dataS['num'] =$val['num'];
                  //          $dataS['total'] = $val['total'];
                  //          $dataS['remark'] = $val['remark'];
                  //          $res_4 = $this->db_s->add($dataS);

                    
                        
                  //          $dataSD['stock_id'] = $res_4;
                  //          $dataSD['time'] = time();
                  //          $dataSD['goods_num'] = $val['num'];
                  //          $dataSD['func'] = '+';
                  //          $dataSD['price'] = $val['price'];
                  //          $dataSD['total'] = $val['total'];
                        


                  //          $res_5 = $this->db_sd->add($dataSD);

                  //          $res_6 = true;
                  //      } 

                     
                  // }
                 


             }else{ //不进行任何操作
                  
                  $whereE['id'] = $_data['data']['entstock_id'];

                  $mapJ['status'] = 1;
                  $mapJ['entstock_id'] = $_data['data']['entstock_id'];
                  $count_j =  $this->db_ea->where($mapJ)->count();//拒绝个数

                  if ($count_j > 0) { //只要有拒绝
                      $res_3 =  $this->db->where( $whereE )->setField('status',3); //拒绝

                      //给申请人发送消息 进行入库报废审核单添加
                     $dataM['title'] = '入库申请审核信息';
                     $dataM['apply_id'] = $_data['data']['entstock_id'];
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $this->db->where( array('id'=>$_data['data']['entstock_id']) )->getField('create_id');
                     $dataM['status'] = 1;
                     $dataM['url'] = U('EntstockApply/info',array('id'=>$_data['data']['entstock_id']));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 20; 
                     $res_4 = $this->db_m->add($dataM);
                     $res_5 = true;
                  }else{
                      $res_3 =  $this->db->where( $whereE )->setField('status',2); //审核中

                        //推送下个人消息
                         $dataM['title'] = '入库申请审核信息';
                         $dataM['apply_id'] = $_data['data']['entstock_id'];


                         $dataM['send_id']  = $_SESSION['admin']['info']['id'];

                         $sort = $this->db_ea->where(array('entstock_id'=>$_data['data']['entstock_id'],'audit_id'=>$_SESSION['admin']['info']['id']) )->getField('sort'); //获取当前审核人排序

                         $dataM['receive_id'] = $this->db_ea->where(array('entstock_id'=>$_data['data']['entstock_id'],'sort'=>$sort+1) )->getField('audit_id'); //获取下一个审核人
                        // dump($whereS);exit;
                         $dataM['status'] = 1;
                         $dataM['url'] = U('EntstockApply/info',array('id'=>$_data['data']['entstock_id']));
                         $dataM['send_time'] = time();
                         $dataM['type'] = 20;
                      
                         //消息推送
                         $res_4 = $this->db_m->add($dataM);
                         $res_5 =true;
                  }

                  // $res_2 = true;
                
                  // $res_5 =true;
                  // $res_6 = true;
                  //是否再推送消息 暂留
             }
             
                  // dump($res_3);exit();

            
            
             if ($res!==false  && $res_2!==false && $res_3!==false && $res_4!==false && $res_5!==false) {
                  $this->db->commit(); 
                  $this->success('审核成功',U('index'));
             }else{
                  $this->db->rollback();
                  $this->error('审核失败');
             }



        }else{
                $id = I('get.id');

                
        $table = "k_entstock_goods eg,k_base_goods bg,k_type t,k_supplier su";
        $field="bg.assets_name,bg.unit,t.type_name,su.company_name,eg.supplier_id,eg.num,eg.price,eg.total,eg.remark,eg.spec,bg.id as goods_id";
        $where =" eg.entstock_id = {$id}  and  eg.supplier_id = su.id  and bg.type_id = t.id and bg.id = eg.goods_id";

        $goods_lists =  $this->db_eg->table($table)->field($field)->where($where)->select();

            
                
                // foreach ($goods_lists as $key => $val) {
                //      $goods_lists[$key]['start_date'] = date('Y-m-d',$val['start_date']);
                // }

                $info = $this->db->where(array('id'=>$id))->find();
                $info['apply_time'] = date('Y-m-d',$info['apply_time']);
                // dump($info);exit();


        $table_1 ="k_entstockt_audit ea,k_admin a,k_depart d";
        $field_1="a.id,a.name,a.signature,d.depart_name,ea.status,ea.explain,ea.time,ea.sort";
        $where_1="ea.audit_id = a.id and  a.depart_id = d.id and ea.entstock_id ={$id}";
        $audits_lists= $this->db_ea->table($table_1)->field($field_1)->where($where_1)->order('ea.sort ASC')->select();

               //dump($audits_lists);exit();
              
                $flag='';//开关按钮默认状态
                foreach ($audits_lists as $key => $val) { //判断是否能操作
                      $audits_lists[$key]['time'] = date('Y-m-d',$val['time']);
                    if ( $val['id'] == $_SESSION['admin']['info']['id'] ) {
                         $map['sort'] = $val['sort'] - 1;
                         $map['entstock_id'] = $id;
                        $f_status = $this->db_ea->where($map)->getField('status');  //寻找上一个人个审核人状态

                         if ($f_status === 0 ||  $f_status == 1) { //未操作、不通过 不能操作
                              $audits_lists[$key]['is_a'] = 0;   
                              $flag = 0;//按钮开关
                         }elseif ( $f_status == 2 && $val['status'] == 0 || $f_status === null && $val['status'] == 0 ) { //通过、上面没人的话 能操作
                              $audits_lists[$key]['is_a'] = 1;
                              $flag = 1;//按钮开关
                         }
                                                
                       // $audits_lists[$key]['is_a'] = 1;
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

          
                //报废按钮 
                if ( $info['status'] == 3  &&  $_SESSION['admin']['info']['id'] == $info['create_id'] && !$this->db_c->where( array('order_sn'=>$info['order_sn']) )->find()  ) {  //该单被拒绝 并且 登录人是申请人
                     $bf = 1;
                }else{
                     $bf = 0;
                }
                $is_bf = $this->db_c->where( array('order_sn'=>$info['order_sn']) )->find();//查看是否报废
                if(!empty($is_bf)){
                    $bfs = 2;
                }

                if ($info['create_id'] ==  $_SESSION['admin']['info']['id']  &&  ($info['status'] == 3 || $info['status'] == 4 ) )  {
                   $this->db_m->where( array('apply_id'=>$id,'receive_id'=>$_SESSION['admin']['info']['id'],'type'=>20) )->setField('status',2); //更改消息状态
                }

                  // dump($flag);
                // dump($audits_lists);

         
                $this->assign('goods_lists',$goods_lists);//卡片物品列表
                $this->assign('info',$info); //报废详情
                $this->assign('audits_lists',$audits_lists); //审核人员
                $this->assign('entstock_id',$id);
                $this->assign('flag',$flag);
                $this->assign('bf',$bf);
                $this->assign('bfs',$bfs);

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