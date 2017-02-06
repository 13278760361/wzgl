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
class EntstockController extends BaseController
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
      $this->db_t = M('Type');
      $this->db_sp = M('spec');
      $suppliers = $this->db_su->order('add_time DESC')->getField('id,company_name');
      $this->assign('suppliers',$suppliers);
      

      $this->is_supper = $_SESSION['admin']['info']['is_supper']; //是否是超级管理员 可以查看添加所有数据
      
      $this->org_id    = $_SESSION['admin']['info']['org_id'];  //部门ID 顶级
 
      $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

      $lists = $cat->getList(0);
       
      $this->departs = $this->is_supper ? $lists : $cat->getChildren($this->org_id,$lists);
      $this->admin = session('admin');
      $this->assign('is_supper',$this->is_supper);
      $this->assign('departs',$this->departs);
    } 
    public $outlist=array('assets_name'=>'物品名称','company_name'=>'供应商名称','spec'=>'规格型号','num'=>'数量','price'=>'单价','remark'=>'备注');
    public $inlist=array('code','assets_name','company_name','spec','num','price','remark');

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
    public function import($data)
    { 
        foreach ($data as $key => $val) {
                $assets_name = trim( $val['assets_name'] );//去除格式
                $company_name = trim( $val['company_name'] );
               if ( !$this->db_bg->where( array('assets_name'=>$assets_name) )->find() ) {
                     unset( $data[$key] ); //销毁
                     continue; //跳出循环
               }
               $data[$key]['goods_id'] = $this->db_bg->where( array('assets_name'=>$assets_name) )->getField('id');

               $data[$key]['total'] = $val['num'] * $val['price'];//计算

               if ( $info = $this->db_su->where( array('company_name'=>$company_name) )->find() ) { //寻找供应
                   $data[$key]['supplier_id'] = $info['id'];
                }else{
                   $dataS['company_name'] = $company_name;
                   $res =   $this->db_su->add($dataS);
                
                   if ($res) {
                        $data[$key]['supplier_id'] = $res;
                   }else{
                        $this->error('程序出错');
                   }
                } 

                $data[$key]['unit'] = $this->db_bg->where( array('assets_name'=>$assets_name) )->getField('unit');
                $type_id = $this->db_bg->where( array('assets_name'=>$assets_name) )->getField('type_id');
                $data[$key]['type_name'] = $this->db_t->where( array('id'=>$type_id) )->getField('type_name');

        }
          
        $data =  array_merge($data);   //重新索引

        // dump($data);exit();
        
      $this->ajaxReturn( array('status'=>1,'info'=>$data) );die();
    }

    //列表
    public function index()
    {
        $assets_name = I('assets_name');
        $s_time = strtotime(  I('s_time') );
        $e_time = strtotime(  I('e_time') );
        $order_sn = I('order_sn');
        $supplier_id = I('supplier_id');
        $type_id = I('type_id');
        $table ='k_entstock e,k_entstock_goods eg,k_base_goods bg,k_type t,k_supplier s,k_category cg'; 

        $field='e.id,e.apply_time,e.order_sn,e.receipt,eg.num,eg.price,eg.total,eg.spec,bg.assets_name,bg.unit,t.type_name,s.company_name';
        $where ="e.id = eg.entstock_id and eg.supplier_id = s.id and eg.goods_id = bg.id and bg.type_id = t.id and bg.cate_id=cg.id and e.status = 4";

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and e.org_id = ({$this->org_id})";
        }

        if ( !empty($assets_name) ) {
          $where .=" and bg.assets_name like '%{$assets_name}%'";
        }

        if ( !empty($order_sn) ) {
            $where .= " and e.order_sn like '%{$order_sn}%'";
        }

        if (!empty($s_time) && !empty($e_time) ) {
          $where .= " and e.apply_time between {$s_time} and {$e_time}";
        }

        if ( !empty($supplier_id) ) {
          $where .=" and eg.supplier_id = {$supplier_id}";
        }
        if($type_id){
          $where.=" and t.id='$type_id'";
        }
        
        if(I('cate_id')){
          $where.=" and cg.id='".I('cate_id')."'";
        }
        if(I('org_id')){
          $where.=" and e.org_id='".I('org_id')."'";
        }
        if(I('receipt')){
          $where.=" and e.receipt '%{".I('receipt')."}%'";
        }
        $count          =  $this->db->table($table)->where($where)->count();
        $page           = new \Think\PageA($count,10);
        $show           = $page->show();


         $lists =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($page->firstRow.','.$page->listRows)
        ->where($where)
        ->order('e.apply_time DESC')
        ->select();
        $s_lists =  $this->db
        ->table($table)
        ->field($field)      
        ->where($where)
        ->order('e.apply_time DESC')
        ->select();
        // dump($lists);
        $p_total = 0;//分页统计
        $s_total = 0;//总计
        $p_num = 0;//分页数量
        $s_num = 0;//总数量
        // echo $this->db->getLastSql();
        foreach ($lists as $key => $value) {
          # code...
          $p_total+=$value['total'];
          $p_num+=$value['num'];
        }
        foreach ($s_lists as $k => $v) {
          # code...
          $s_total+=$v['total'];
          $s_num+=$v['num'];
        }
        if($type_id){
            $cat2=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
            $this->assign('cateS',$cat2->getList(0));
        }
        $org_id=I('get.org_id')?:0;
        $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
        $cate =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
        $top_departs = $cate->getList($c_id);
        $this->assign('top_departs',$top_departs);
        $this->assign('org',$org_id);
        $this->assign('lists',$lists);
        $this->assign('page',$show);
        $this->assign('assets_name',$assets_name);
        $this->assign('order_sn',$order_sn);
        $this->assign('s_time',!empty($s_time)?date('Y-m-d',$s_time):'');
        $this->assign('e_time',!empty($e_time)?date('Y-m-d',$e_time):'');
        $this->assign('receipt',I('receipt'));
        $this->assign('supplier_id',$supplier_id);
        $this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',I('cate_id'));
        $this->assign('p_total',$p_total);
        $this->assign('s_total',$s_total);
        $this->assign('p_num',$p_num);
        $this->assign('s_num',$s_num);
        $this->display();
    }

    //添加
    public function add()
    {
        
        if (IS_POST) {
            $_data = I('post.');

            // dump($_data);exit();

           if ($this->is_supper) {
                if ( empty($_data['data']['org_id']) ) {
                     $this->error('请选择部门');
                }
            }

            if ( empty($_data[1]['assets_name']) ) { //检测第一个
                 $this->error('请添加入库信息');
            }

            if ( empty($_data['data']['total_price']) ) {
                $this->error('请填写合计');
            }

             if ( empty($_data['data']['address']) ) {
                 $this->error('请填写入库地址');
            }

              if ( empty($_data['data']['project']) ) {
                 $this->error('请填写所属项目');
            }

              if ( empty($_data['data']['remark']) ) {
                 $this->error('请填写入库说明');
            }

             if ( empty($_data['data']['receipt']) ) {
                 $this->error('请填收货方');
            }

              if ( empty($_data['data']['managers']) ) {
                 $this->error('请填经办人');
            }


              if ( empty($_data['data']['managers_phone']) ) {
                 $this->error('请填经办人联系电话');
             }else{
                 $isMob="/^1[3-5,7,8]{1}[0-9]{9}$/";
                 if(!preg_match($isMob,$_data['data']['managers_phone'])){
                      $this->error('电话号码格式不正确，请重新输入'); 
                 }
             }



            if ( empty($_data['data']['depart_id']) ) {
                 $this->error('请选择审核人部门');
            }

            // dump($_data);exit();
             $this->db->startTrans();
             //数据入库
             $dataE['order_sn'] = $_data['data']['order_sn'];
             $dataE['apply_time'] = !empty($_data['data']['time'])?strtotime($_data['data']['time']):time();

             $dataE['applyer'] = $_SESSION['admin']['info']['name'];
             $dataE['remark'] = $_data['data']['remark'];

             $dataE['total_price'] = $_data['data']['total_price'];
             $dataE['create_id'] = $_SESSION['admin']['info']['id'];
             $dataE['creater'] = $_SESSION['admin']['info']['name'];
             $dataE['address'] = $_data['data']['address'];
             $dataE['project'] = $_data['data']['project'];
             $dataE['receipt'] = $_data['data']['receipt'];

             $dataE['managers'] = $_data['data']['managers'];
             $dataE['managers_phone'] = $_data['data']['managers_phone'];

             $dataE['org_id'] =  $this->is_supper?$_data['data']['org_id']:$this->org_id; //部门ID

             $dataE['status'] = 1;//默认为未审核
             $dataE['last_op_time'] = time();

             $res = $this->db->add($dataE);

             if (is_array($_data['data']['depart_id'])) { //多个审核人
                 # code...
               foreach ($_data['data']['depart_id'] as $key => $val) { //审核人员入库
                $map['is_auditer'] = 1;
                $map['depart_id'] = $val;

                   
                     $dataEA['entstock_id']= $res;
                     $dataEA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataEA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataEA['time'] = time();
                     $dataEA['sort'] = $key;

                     if ( empty( $dataEA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }
                     

                     if ( $this->db_ea->where($dataEA)->find() ) {
                         $this->error('请勿重复添加，审核部门');
                     }

                     // dump($dataEA);

                     $res_2 = $this->db_ea->add($dataEA); 


                     if ($key == 0 ) { //第一个审核人 发送消息
                         $dataM['title'] = '入库申请审核信息';
                         $dataM['apply_id'] = $res;
                         $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                         $dataM['receive_id'] = $dataEA['audit_id'];
                         $dataM['status'] = 1;
                         $dataM['url'] = U('EntstockApply/info',array('id'=>$res));
                         $dataM['send_time'] = time();
                         $dataM['type'] = 20;
                       
                         //消息推送
                         $res_3 = $this->db_m->add($dataM);
                     }

                     // $dataM['title'] = '入库申请审核信息';
                     // $dataM['apply_id'] = $res;
                     // $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     // $dataM['receive_id'] = $dataEA['audit_id'];
                     // $dataM['status'] = 1;
                     // $dataM['url'] = U('EntstockApply/info',array('id'=>$res));
                     // $dataM['send_time'] = time();
                   
                     // //消息推送
                     // $res_3 = $this->db_m->add($dataM);

                }    
            }else{ //一个审核人
                $map['is_auditer'] = 1;
                $map['depart_id'] = $_data['data']['depart_id'];
                     $dataEA['entstock_id']= $res;
                     $dataEA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataEA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataEA['time'] = time();
                     $dataEA['sort'] = 0;

                     if ( empty( $dataEA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }

                     $res_2 = $this->db_ea->add($dataEA); 

                     $dataM['title'] = '入库申请审核信息';
                     $dataM['apply_id'] = $res;
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $dataEA['audit_id'];
                     $dataM['status'] = 1;
                     $dataM['url'] = U('EntstockApply/info',array('id'=>$res));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 20;
                     //消息推送
                     $res_3 = $this->db_m->add($dataM);
            }


             unset($_data['data']); //去除

             // dump($_data);exit();


             foreach ($_data as $key => $val) { //报废卡片入库
              if (!$val['goods_id']) {
                $this->error('物品【'.$val['assets_name'].'】不存在！');
              }
               if (  empty(  $_data[$key]['assets_name']  )  ) { //去除空行
                        unset( $_data[$key] );
                         if ( empty( $_data ) ) {
                           $this->error('请添加报废信息');
                         } 
                        continue;
                      
               }
                  
                  if ( empty($val['company_name']) ) {
                       $this->error('供应商不能为空');
                  }

                  if ( empty($val['spec']) ) {
                       $this->error('规格不能为空');
                  }

                  if ( empty($val['num']) ) {
                       $this->error('数量不能为空');
                  }

                  if ( empty($val['price']) ) {
                       $this->error('单价不能为空');
                  }

                  if ( empty($val['total']) ) {
                       $this->error('金额不能为空');
                  }

                  $dataEG['entstock_id'] = $res;
                  $dataEG['goods_id'] = $val['goods_id'];

                  $sp_info=$this->db_su->where(array('company_name'=>trim($val['company_name'])))->find();
                  if ( empty( $sp_info  ) ) { //没有关联到供应商 新增
                      $dataSU['company_name'] = $val['company_name'];
                      $dataSU['user_id'] = $_SESSION['admin']['info']['id'];
                      $res_4 =$this->db_su->add($dataSU); 
                      $dataEG['supplier_id'] = $res_4;
                  }else{
                      $res_4 = true;
                      $dataEG['supplier_id'] = $sp_info['id'];
                  }
                  
                  $dataEG['goods_id'] = $val['goods_id'];

   
                  $dataEG['spec'] = $val['spec'];

                  
                  $dataEG['num'] = $val['num'];
                  $dataEG['price'] = $val['price'];
                  $dataEG['total'] = $val['total'];
                  $dataEG['remark'] = $val['remark'];

                  $res_5 = $this->db_eg->add($dataEG);

                      //需求更变要入库
                       $whereS['goods_id'] = $val['goods_id'];
                       $whereS['spec'] = $val['spec'];
                      // print_r($whereS);exit;
                      if ( $info =  $this->db_s->where($whereS)->find() ) { //如果存在改物品 加数量和总价
                           $res_6 =  $this->db_s->where($whereS)->setInc('num',$val['num']);
                           $res_7 =  $this->db_s->where($whereS)->setInc('total',$val['total']);
                           
                           $dataSD['stock_id'] = $info['id'];
                           $dataSD['time'] = time();
                           $dataSD['goods_num'] = $val['num'];
                           $dataSD['func'] = '+';
                           $dataSD['price'] = $val['price'];
                           $dataSD['total'] = $val['total'];
                           $dataSD['order_sn'] = $dataE['order_sn'];
                           
                           $sp_info = $this->db_sp->where(array('spec_name'=>trim($val['spec'])))->find();
                           
                           if(!empty($sp_info)){
                             $s_rs = $sp_info['id'];
                           }else{
                             $s_rs = $this->db_sp->add($s_data);
                           }

                           $res_8 = $this->db_sd->add($dataSD);
                       }else{
                           $dataS['goods_id'] = $val['goods_id'];
                           $dataS['supplier_id'] =$dataEG['supplier_id'];
                           $dataS['spec'] =$val['spec'];
                           $dataS['num'] =$val['num'];
                           $dataS['total'] = $val['total'];
                           $dataS['remark'] = $val['remark'];
                           $dataS['org_id'] = $dataE['org_id'];
                           // $code = $val['goods_id'].'-'.$dataEG['supplier_id'];
                           $s_data['spec_name'] = $val['spec'];
                           $sp_info = $this->db_sp->where(array('spec_name'=>trim($dataS['spec'])))->find();

                           if(!empty($sp_info)){
                             $s_rs = $sp_info['id'];
                           }else{
                             $s_rs = $this->db_sp->add($s_data);
                           }
                           
                           if ( empty( $val['code'] ) ) { //缺少条形码 
                            $dataS['code']  =  str_repeat(0, 6-strlen( $val['goods_id'] ) ).$val['goods_id'].'-'.str_repeat(0, 6-strlen( $dataEG['spec_id'] ) ).$s_rs;                               
                           }else{
                            $dataS['code'] = $val['code'];
                           }
                        

                           $dataS['bar_code'] = A('BarCode')->doCode( $dataS['code'] );

                           $res_6 = $this->db_s->add($dataS);

                    
                        
                           $dataSD['stock_id'] = $res_6;
                           $dataSD['time'] = time();
                           $dataSD['goods_num'] = $val['num'];
                           $dataSD['func'] = '+';
                           $dataSD['price'] = $val['price'];
                           $dataSD['total'] = $val['total'];
                           $dataSD['order_sn'] = $dataE['order_sn'];
                        


                           $res_7 = $this->db_sd->add($dataSD);

                           $res_8 = true;
                       }
                       //需求更变要入库   
             }

             if ($res && $res_2 && $res_3 && $res_4 && $res_5 && $res_6 && $res_7 && $res_8 && $s_rs) {
                 $this->db->commit();
                 $this->success('添加成功',U('Entstock/index'));
             }else{
                $this->db->rollback();
                 $this->error('添加失败');
             }



         }else{
           $order_sn = createOrderSn(C('RKSN')); //生成单号
           $this->assign('order_sn',$order_sn);
           $org_id=I('get.org')?:0;
           $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
           $cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
           $top_departs = $cat->getList($c_id);
           $this->assign('top_departs',$top_departs);
           $this->assign('org',$org_id);
           $this->assign('list_dep',$this->getDeparts($org_id));
           $this->display();
         } 
        
    }

    public function info(){ 
        $id = I('get.id');
        
        $table = "k_entstock_goods eg,k_base_goods bg,k_type t,k_supplier su";
        $field="bg.assets_name,bg.unit,t.type_name,su.company_name,eg.supplier_id,eg.num,eg.price,eg.total,eg.remark,eg.spec,bg.id as goods_id";
        $where =" eg.entstock_id = {$id}  and  eg.supplier_id = su.id  and bg.type_id = t.id and bg.id = eg.goods_id";

        $goods_lists =  $this->db_eg->table($table)->field($field)->where($where)->select();
       
       // dump($goods_lists);
    
        
       

        $info = $this->db->where(array('id'=>$id))->find();
        // dump($info);
        $info['apply_time'] = date('Y-m-d',$info['apply_time']);

        $table_1 ="k_entstockt_audit ea,k_admin a,k_depart d";
        $field_1="a.depart_id,a.name,a.signature,d.depart_name,ea.explain,ea.status,ea.time";
        $where_1="ea.audit_id = a.id and a.depart_id = d.id  and  ea.entstock_id ={$id}";

        $audits_lists= $this->db_ea->table($table_1)->field($field_1)->where($where_1)->select();
       // dump($audits_lists);

       foreach ($audits_lists as $key => $val) {
             $audits_lists[$key]['time']= date('Y-m-d',$val['time']);
        }
 
        $this->assign('goods_lists',$goods_lists);//卡片物品列表
        $this->assign('info',$info); //报废详情
        $this->assign('audits_lists',$audits_lists); //审核人员

        $this->display();
    }

    //编辑
    public function edit()
    {
        if (IS_POST) {
             $_data = I('post.');

            // dump($_data);exit();
          if ($this->is_supper) {
                if ( empty($_data['data']['org_id']) ) {
                     $this->error('请选择部门');
                }
            }

            if ( empty($_data['e0']['assets_name']) ) { //检测第一个
                 $this->error('请添加入库信息');
            }

            if ( empty($_data['data']['total_price']) ) {
                $this->error('请填写合计');
            }

             if ( empty($_data['data']['address']) ) {
                 $this->error('请填写入库地址');
            }

              if ( empty($_data['data']['project']) ) {
                 $this->error('请填写所属项目');
            }

              if ( empty($_data['data']['remark']) ) {
                 $this->error('请填写入库说明');
            }

             if ( empty($_data['data']['receipt']) ) {
                 $this->error('请填收货方');
            }

              if ( empty($_data['data']['managers']) ) {
                 $this->error('请填经办人');
            }


              if ( empty($_data['data']['managers_phone']) ) {
                 $this->error('请填经办人联系电话');
            }

            if ( empty($_data['data']['depart_id']) ) {
                 $this->error('请选择审核人部门');
            }

            // dump($_data);exit();
            $this->db->startTrans();
             //数据入库
             $dataE['id'] = $_data['data']['id'];
             $dataE['order_sn'] = $_data['data']['order_sn'];
             $dataE['apply_time'] = !empty($_data['data']['time'])?strtotime($_data['data']['time']):time();

             $dataE['applyer'] = $_SESSION['admin']['info']['name'];
             $dataE['remark'] = $_data['data']['remark'];

             $dataE['total_price'] = $_data['data']['total_price'];
             $dataE['create_id'] = $_SESSION['admin']['info']['id'];
             $dataE['creater'] = $_SESSION['admin']['info']['name'];
             $dataE['address'] = $_data['data']['address'];
             $dataE['project'] = $_data['data']['project'];
             $dataE['receipt'] = $_data['data']['receipt'];

             $dataE['managers'] = $_data['data']['managers'];
             $dataE['managers_phone'] = $_data['data']['managers_phone'];

             $dataE['org_id'] =  $this->is_supper?$_data['data']['org_id']:$this->org_id; //部门ID

             $dataE['status'] = 1;//默认为未审核
             $dataE['last_op_time'] = time();

             $res = $this->db->save($dataE);

             //修改之前先对比 减去库存数量
             $hist = $this->db_eg->where( array('entstock_id'=>$_data['data']['id']) )->select();
             foreach ($hist as $key => $val) {
                $mapD['goods_id'] = $val['goods_id'];
                $mapD['spec'] = $val['spec'];
                $d_s_diffN = $this->db_s->where($mapD)->setDec('num',$val['num']); //数量
                $d_s_diffT = $this->db_s->where($mapD)->setDec('total',$val['total']); //总价
             }
             

             $d_eg = $this->db_eg ->where( array('entstock_id'=>$_data['data']['id']) )->delete();
             $d_ea = $this->db_ea ->where( array('entstock_id'=>$_data['data']['id']) )->delete();
             $d_m = $this->db_m->where( array('apply_id'=>$_data['data']['id'],'type'=>20) )->delete();//
             $d_sd = $this->db_sd->where( array('order_sn'=>$_data['data']['order_sn']) )->delete(); //清空库存详情

             if (is_array($_data['data']['depart_id'])) { //多个审核人
                 # code...
               foreach ($_data['data']['depart_id'] as $key => $val) { //审核人员入库
                $map['is_auditer'] = 1;
                $map['depart_id'] = $val;

                   
                     $dataEA['entstock_id']= $_data['data']['id'];
                     $dataEA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataEA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataEA['time'] = time();
                     $dataEA['sort'] = $key;

                     if ( empty( $dataEA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }
                     

                     if ( $this->db_ea->where($dataEA)->find() ) {
                         $this->error('请勿重复添加，审核部门');
                     }

                     // dump($dataEA);

                     $res_2 = $this->db_ea->add($dataEA); 


                     if ($key == 0 ) { //第一个审核人 发送消息
                         $dataM['title'] = '入库申请审核信息';
                         $dataM['apply_id'] = $_data['data']['id'];
                         $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                         $dataM['receive_id'] = $dataEA['audit_id'];
                         $dataM['status'] = 1;
                         $dataM['url'] = U('EntstockApply/info',array('id'=>$_data['data']['id']));
                         $dataM['send_time'] = time();
                         $dataM['type'] = 20;
                   
                         //消息推送
                         $res_3 = $this->db_m->add($dataM);
                     }

                     // $dataM['title'] = '入库申请审核信息';
                     // $dataM['apply_id'] = $_data['data']['id'];
                     // $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     // $dataM['receive_id'] = $dataEA['audit_id'];
                     // $dataM['status'] = 1;
                     // $dataM['url'] = U('EntstockApply/info',array('id'=>$_data['data']['id']));
                     // $dataM['send_time'] = time();
                   
                     // //消息推送
                     // $res_3 = $this->db_m->add($dataM);

                }    
            }else{ //一个审核人
                $map['is_auditer'] = 1;
                $map['depart_id'] = $_data['data']['depart_id'];
                     $dataEA['entstock_id']= $_data['data']['id'];
                     $dataEA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataEA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataEA['time'] = time();
                     $dataEA['sort'] = 0;

                     if ( empty( $dataEA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }

                     $res_2 = $this->db_ea->add($dataEA); 

                     $dataM['title'] = '入库申请审核信息';
                     $dataM['apply_id'] = $_data['data']['id'];
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $dataEA['audit_id'];
                     $dataM['status'] = 1;
                     $dataM['url'] = U('EntstockApply/info',array('id'=>$_data['data']['id']));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 20;
                     //消息推送
                     $res_3 = $this->db_m->add($dataM);
            }


             unset($_data['data']); //去除

             

             foreach ($_data as $key => $val) { //去除空行
                 if ($val['assets_name'] == '') {
                     unset($_data[$key]);
                 }
             }


             foreach ($_data as $key => $val) { //报废卡片入库
                  
                  if ( empty($val['company_name']) ) {
                       $this->error('供应商不能为空');
                  }

                  if ( empty($val['spec']) ) {
                       $this->error('规格不能为空');
                  }

                  if ( empty($val['num']) ) {
                       $this->error('数量不能为空');
                  }

                  if ( empty($val['price']) ) {
                       $this->error('单价不能为空');
                  }

                  if ( empty($val['total']) ) {
                       $this->error('金额不能为空');
                  }

                  $dataEG['entstock_id'] = $dataEA['entstock_id'];
                  $dataEG['goods_id'] = $val['goods_id'];


                  if ( empty( $val['supplier_id']  ) ) { //没有关联到供应商 新增
                      $dataSU['company_name'] = $val['company_name'];
                      $res_4 =$this->db_su->add($dataSU); 
                      $dataEG['supplier_id'] = $res_4;
                  }else{
                      $res_4 = true;
                      $dataEG['supplier_id'] = $val['supplier_id'];
                  }
                  
                  $dataEG['goods_id'] = $val['goods_id'];

   
                  $dataEG['spec'] = $val['spec'];

                  
                  $dataEG['num'] = $val['num'];
                  $dataEG['price'] = $val['price'];
                  $dataEG['total'] = $val['total'];
                  $dataEG['remark'] = $val['remark'];

                  $res_5 = $this->db_eg->add($dataEG);

                  //需求更变要入库
                       $whereS['goods_id'] = $val['goods_id'];
                       $whereS['spec'] = $val['spec'];
                      if ( $info =  $this->db_s->where($whereS)->find() ) { //如果存在改物品 加数量和总价
                           $res_6 =  $this->db_s->where($whereS)->setInc('num',$val['num']);
                           $res_7 =  $this->db_s->where($whereS)->setInc('total',$val['total']);
                           
                           $dataSD['stock_id'] = $info['id'];
                           $dataSD['time'] = time();
                           $dataSD['goods_num'] = $val['num'];
                           $dataSD['func'] = '+';
                           $dataSD['price'] = $val['price'];
                           $dataSD['total'] = $val['total'];
                           $dataSD['order_sn'] = $dataE['order_sn'];
                           

                           $res_8 = $this->db_sd->add($dataSD);
                       }else{
                           $dataS['goods_id'] = $val['goods_id'];
                           $dataS['supplier_id'] =$dataEG['supplier_id'];
                           $dataS['spec'] =$val['spec'];
                           $dataS['num'] =$val['num'];
                           $dataS['total'] = $val['total'];
                           $dataS['remark'] = $val['remark'];
                           $dataS['org_id'] = $dataE['org_id'];
                           $s_data['spec_name'] = $val['spec'];
                           $sp_info = $this->db_sp->where(array('spec_name'=>$dataS['spec']))->find();
                           if(!empty($sp_info)){
                             $s_rs = $sp_info['id'];
                           }else{
                             $s_rs = $this->db_sp->add($s_data);
                           }
                           $code = $val['goods_id'].'-'.$s_rs;
                           $dataS['bar_code'] = A('BarCode')->doCode($code);
                           $res_6 = $this->db_s->add($dataS);

                    
                        
                           $dataSD['stock_id'] = $res_6;
                           $dataSD['time'] = time();
                           $dataSD['goods_num'] = $val['num'];
                           $dataSD['func'] = '+';
                           $dataSD['price'] = $val['price'];
                           $dataSD['total'] = $val['total'];
                           $dataSD['order_sn'] = $dataE['order_sn'];
                        


                           $res_7 = $this->db_sd->add($dataSD);

                           $res_8 = true;
                       }
             }

             if ($res!==false && $d_s_diffN!==false && $d_s_diffT!==false && $d_eg!==false && $d_ea!==false && $d_m!==false && $d_sd!==false && $res_2!==false && $res_3!==false && $res_4!==false && $res_5!==false && $res_6!==false && $res_7!==false && $res_8!==false && $s_rs!==false) {
                 $this->db->commit();
                 $this->success('修改成功',U('EntstockApply/index'));
             }else{
                $this->db->rollback();
                 $this->error('修改失败');
             }
        }else{
              $id = I('get.id');
              
              $table = "k_entstock_goods eg,k_base_goods bg,k_type t,k_supplier su";
              $field="bg.assets_name,bg.unit,t.type_name,su.company_name,eg.supplier_id,eg.num,eg.price,eg.total,eg.remark,eg.spec,bg.id as goods_id";
              $where =" eg.entstock_id = {$id}  and  eg.supplier_id = su.id  and bg.type_id = t.id and bg.id = eg.goods_id";

              $goods_lists =  $this->db_eg->table($table)->field($field)->where($where)->select();
             
             // dump($goods_lists);
          
              
             

              $info = $this->db->where(array('id'=>$id))->find();
              // dump($info);
              $info['apply_time'] = date('Y-m-d',$info['apply_time']);

              $table_1 ="k_entstockt_audit ea,k_admin a,k_depart d";
              $field_1="a.depart_id,a.name,d.depart_name,ea.explain,ea.status,ea.time";
              $where_1="ea.audit_id = a.id and a.depart_id = d.id  and  ea.entstock_id ={$id}";

              $audits_lists= $this->db_ea->table($table_1)->field($field_1)->where($where_1)->select();
             // dump($audits_lists);

             foreach ($audits_lists as $key => $val) {
                   $audits_lists[$key]['time']= date('Y-m-d',$val['time']);
              }
       
              $this->assign('goods_lists',$goods_lists);//卡片物品列表
              $this->assign('info',$info); //报废详情
              $this->assign('audits_lists',$audits_lists); //审核人员



                $cat =  CAT('Depart',array('id','pid','depart_name'),'pid = 0','add_time asc'); //顶级部门
                $top_departs = $cat->getList();

                $this->assign('top_departs',$top_departs);

              $this->display();
        }
    }

    //删除
    public function del()
    {
        $res!==false?$this->success(''):$this->error('');
    }

    //导出数据
    public function export() 
    {
        $assets_name = I('assets_name');
        $s_time = strtotime(  I('s_time') );
        $e_time = strtotime(  I('e_time') );
        $order_sn = I('order_sn');
        $supplier_id = I('supplier_id');
        $len = I('size')?:10;
        $page = I('page');
        $first = $len *($page-1);

        $table ='k_entstock e,k_entstock_goods eg,k_base_goods bg,k_type t,k_supplier s'; 

        $field='e.id,e.apply_time,e.order_sn,e.receipt,eg.num,eg.price,eg.total,eg.spec,bg.assets_name,bg.unit,t.type_name,s.company_name';
        $where ="e.id = eg.entstock_id and eg.supplier_id = s.id and eg.goods_id = bg.id and bg.type_id = t.id and e.status and e.status = 4";


        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and e.org_id = ({$this->org_id})";
        }

        if ( !empty($assets_name) ) {
          $where .=" and bg.assets_name like '%{$assets_name}%'";
        }

        if ( !empty($order_sn) ) {
            $where .= " and e.order_sn like '%{$order_sn}%'";
        }

        if (!empty($s_time) && !empty($e_time) ) {
          $where .= " and e.apply_time between {$s_time} and {$e_time}";
        }

        if ( !empty($supplier_id) ) {
          $where .=" and eg.supplier_id = {$supplier_id}";
        }



        // $count          =  $this->db->table($table)->where($where)->count();
        // $page           = new \Think\PageA($count,10);
        // $show           = $page->show();


         $lists =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($first,$len)
        ->where($where)
        ->order('e.apply_time DESC')
        ->select();

        foreach ($lists as $key => $val) {
           $lists[$key]['apply_time'] = date('Y-m-d H:i');
        }

        return $lists;
    }

    public function getData(){

        $_data = I('post.');
   
         
        $table = "k_base_goods bg";//,k_type t,k_stock s,k_supplier su
        $field="bg.assets_name,bg.unit,t.type_name,su.company_name,s.supplier_id,bg.id as goods_id";
        $where ="{$_data['field']} like '{$_data['value']}%'";

        $data =  M()->table($table)->field($field)
                 ->join('k_type t ON bg.type_id=t.id','left')
                 ->join('k_stock s ON bg.id=s.goods_id','left')
                 ->join('k_supplier su ON s.supplier_id=su.id','left')
                 ->where($where)
                 ->limit(10)->select();

          foreach ($data as $key => $value) {
            # code...
            $data[$key]['company_name']=$value['company_name']==null?'':$value['company_name'];
            $data[$key]['supplier_id']=$value['supplier_id']==null?'':$value['supplier_id'];
          }
       // if ( empty($data) ) {
    //     $table2 = "k_base_goods bg";//,k_type t
    //     $field2="bg.assets_name,bg.unit,t.type_name,bg.id as goods_id";
    //     $where2 ="{$_data['field']} like '{$_data['value']}%'";

    //     $data2 =  M()->table($table2)
    //              ->join('k_type t ON bg.type_id=t.id','left')
    //              ->field($field)
    //              ->where($where)
    //              ->limit(10)->select();
    // //    }


        // dump($data);exit();

        // foreach ($data as $key => $val) {
        //      $data[$key]['start_date'] = date('Y-m-d',$val['start_date']);
        // }
      //  $data = array_merge($data,$data2);       
        $this->ajaxReturn($data); 

        

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
?>