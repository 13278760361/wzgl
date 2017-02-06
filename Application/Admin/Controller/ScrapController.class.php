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
class ScrapController extends BaseController 
{

     public $outlist=array('id'=>'序号','order_sn'=>'卡片单号','assets_name'=>'物品名称','spec'=>'规格型号','cate_name'=>'资产分类','department'=>'部门','unit'=>'单位','original_value'=>'原值','start_date'=>'购入时间','remark'=>'报废报损原因');
    // public $inlist=array('username','name','sex','depart','role','tel','email','audit');

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
      $this->admin = session('admin');
      $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

      $lists = $cat->getList(0);

      // dump($lists);

      $this->departs = $this->is_supper ? $lists : $cat->getChildren($this->org_id,$lists);
      $this->admin = session('admin');

    
      $this->assign('is_supper',$this->is_supper);
      $this->assign('departs',$this->departs);

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
    //列表
    public function index()
    {   

        //PC端查询条件
        $assets_name = I('assets_name');
        $s_time = strtotime(  I('s_time') );
        $e_time = strtotime(  I('e_time') );
        $order_sn = I('order_sn');
        //手机端查询条件
        $keyword =I('keyword');
        $len = 10;
        $type_id=I('type_id');
        $table ='k_scrap s,k_scrap_goods sg,k_cards c,k_base_goods bg,k_category ca'; 

        $field='s.id,bg.assets_name,bg.unit,c.spec,c.department,c.original_value,c.start_date,s.remark,c.order_sn,c.keeper,ca.cate_name';
        $where ="s.id = sg.scrap_id and sg.card_sn = c.order_sn and c.goods_id = bg.id and bg.cate_id = ca.id and s.status = 4 and s.type =3 ";

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and s.org_id = ({$this->org_id})";
        }


        if (!empty($assets_name)) {
          $where .=" and bg.assets_name like '%{$assets_name}%'";
        }

        if ( !empty($order_sn) ) {
            $where .= " and c.order_sn like '%{$order_sn}%'";
        }

        if (!empty($s_time) && !empty($e_time) ) {
          $where .= " and s.time between {$s_time} and {$e_time}";
        }

        

         if ( !empty($keyword) ) {
             $where .= " and (bg.assets_name like '%{$keyword}%' OR c.order_sn like '%{$keyword}%' OR c.department like '%{$keyword}%' )";
         }
        if($type_id){
            $where.=" AND bg.type_id='{$type_id}'";
        }
        if(I('cate_id')){
            $where.=" AND bg.cate_id='".I('cate_id')."'";
        }
        $count          =  $this->db->table($table)->where($where)->count();
        $page           = new \Think\PageA($count,$len);
        $show           = $page->show();


         $lists =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($page->firstRow.','.$page->listRows)
        ->where($where)
        ->order('s.time DESC')
        ->select();

        $s_total = $this->db
        ->table($table)
        ->field($field)      
        
        ->where($where)

        ->getField('SUM(original_value) as num');
        $p_total=0;
        foreach ($lists as $key => $value) {
            # code...
            $p_total+=$value['original_value'];
        }
        if($type_id){
            $cat2=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
            $this->assign('cateS',$cat2->getList(0));
        }
        // echo $this->db->getLastSql();
        $this->assign('p_total',$p_total);
        $this->assign('s_total',$s_total?$s_total:0);
        $this->assign('lists',$lists);
        $this->assign('page',$show);
        $this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',I('cate_id'));
        $this->assign('assets_name',$assets_name);
        $this->assign('order_sn',$order_sn);
        $this->assign('s_time',!empty($s_time)?date('Y-m-d',$s_time):'');
        $this->assign('e_time',!empty($e_time)?date('Y-m-d',$e_time):'');
        //手机端

        $this->assign('keyword',$keyword);
        $this->assign('ajaxlen',$len);
        $this->display();
    }

     public function ajaxScrap()
    {
        $page=I('get.page');
        $len=I('get.size')?:1;
        $keyword=I('get.keyword');
        $first=$len*($page-1);

        // dump($key);exit();

        $table ='k_scrap s,k_scrap_goods sg,k_cards c,k_base_goods bg,k_category ca'; 

        $field='s.id,bg.assets_name,bg.unit,c.spec,c.department,c.original_value,c.start_date,s.remark,c.order_sn,c.keeper,ca.cate_name';
        $where ="s.id = sg.scrap_id and sg.card_sn = c.order_sn and c.goods_id = bg.id and bg.cate_id = ca.id and s.status = 4 and s.type= 3";

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and s.org_id = ({$this->org_id})";
        }

        if ( !empty($keyword) ) {
             $where .= " and (bg.assets_name like '%{$keyword}%' OR c.order_sn like '%{$keyword}%' OR c.department like '%{$keyword}%' )";
         }
        
         $lists =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($first,$len)
        ->where($where)
        ->order('s.time DESC')
        ->select();
        // echo $this->db->getLastSql();

        $this->ajaxReturn($lists); 
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

            if ( empty($_data['data']['status']) ) {
                $this->error('请选择报废理由');
            }

             if ( empty($_data['data']['depart_id']) ) {
                 $this->error('请选择审核人部门');
            }

            // dump($_data);exit();
            $this->db->startTrans();
             //数据入库
             $dataS['order_sn'] = $_data['data']['order_sn'];
             $dataS['time'] = !empty($_data['data']['time'])?strtotime($_data['data']['time']):time();
             $dataS['creater'] = $_SESSION['admin']['info']['name'];
             $dataS['create_id'] = $_SESSION['admin']['info']['id'];
             if ($_data['data']['status'] == '其他') { //其他显示填写原因
                 $dataS['remark'] = $_data['data']['reason'];
             }else{
                 $dataS['remark'] = $_data['data']['status']; //否则直接是status
             }
             $dataS['org_id'] =  $this->is_supper?$_data['data']['org_id']:$this->org_id; //部门ID
            
             $dataS['status'] = 1;//默认为未审核
             $dataS['last_op_time'] = time();
             $dataS['type'] = 3;

        

             $res = $this->db->add($dataS);

             if (is_array($_data['data']['depart_id'])) { //多个审核人

                // dump($_data['data']['depart_id']);exit();
                 # code...
               foreach ($_data['data']['depart_id'] as $key => $val) { //审核人员入库
                $map['is_auditer'] = 1;
                $map['depart_id'] = $val;

                   
                     $dataSA['scrap_id']= $res;
                     $dataSA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataSA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataSA['time'] =time();
                     $dataSA['sort'] = $key;

                     if ( empty( $dataSA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }
                     
                     $info['scrap_id'] = $dataSA['scrap_id'];
                     $info['audit_id'] = $dataSA['audit_id'];
                     if ( $this->db_sa->where( $info )->find() ) {
                         $this->error('请勿重复添加，审核部门');
                     }

                     $res_2 = $this->db_sa->add($dataSA); 

                     if ($key == 0 ) { //第一个审核人 发送消息
                         $dataM['title'] = '报废申请审核信息';
                         $dataM['apply_id'] = $res;
                         $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                         $dataM['receive_id'] = $dataSA['audit_id'];
                         $dataM['status'] = 1;
                         $dataM['url'] = U('ScrapApply/info',array('id'=>$res));
                         $dataM['send_time'] = time();
                         $dataM['type'] = 70;
                         //消息推送
                         $res_3 = $this->db_m->add($dataM);
                     }

                     // $dataM['title'] = '报废申请审核信息';
                     // $dataM['apply_id'] = $res;
                     // $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     // $dataM['receive_id'] = $dataSA['audit_id'];
                     // $dataM['status'] = 1;
                     // $dataM['url'] = U('ScrapApply/info',array('id'=>$res));
                     // $dataM['send_time'] = time();
                     // //消息推送
                     // $res_3 = $this->db_m->add($dataM);

                }    
            }else{ //一个审核人
                $map['is_auditer'] = 1;
                $map['depart_id'] = $_data['data']['depart_id'];
                     $dataSA['scrap_id']= $res;
                     $dataSA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataSA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataSA['time'] =time();
                     $dataSA['sort'] = 0;

                      if ( empty( $dataSA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }
                     
                     $res_2 = $this->db_sa->add($dataSA); 

                     $dataM['title'] = '报废申请审核信息';
                     $dataM['apply_id'] = $res;
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $dataSA['audit_id'];
                     $dataM['status'] = 1;
                     $dataM['url'] = U('ScrapApply/info',array('id'=>$res));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 70;
                     //消息推送
                     $res_3 = $this->db_m->add($dataM);
            }

             
             unset($_data['data']); //去除

             // dump($_data);exit();


        
             foreach ($_data as $key => $val) { //报废卡片入库

                  if (  empty(  $_data[$key]['order_sn']  )  ) { //去除空行
                        unset( $_data[$key] );
                         if ( empty( $_data ) ) {
                           $this->error('请添加报废信息');
                         } 
                        continue;
                      
                  }
                  
                  $dataSG['scrap_id'] = $res;
                  $dataSG['card_sn'] = $val['order_sn'];
                  $res_4 = $this->db_sg->add($dataSG);
             }

             if ($res && $res_2 && $res_3 && $res_4) {
                 $this->db->commit();
                 $this->success('添加成功',U('Scrap/index'));
             }else{
                $this->db->rollback();
                 $this->error('添加失败');
             }



         }else{
            $org_id=I('get.org')?:0;
            $order_sn = createOrderSn(C('BFSN')); //生成单号
            $this->assign('order_sn',$order_sn);

            $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
            $cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
            
            $top_departs = $cat->getList($c_id);

            $this->assign('top_departs',$top_departs);
            $this->assign('org',$org_id);
            $this->assign('list_dep',$this->getDeparts($org_id));
         if (isset($_GET['order_sn'])) {
              $order_sn = I('get.order_sn');

              $table = "k_cards c,k_base_goods bg,k_category ca";
              $field="c.order_sn,bg.assets_name,bg.unit,ca.cate_name,c.spec,c.original_value,c.start_date,c.service_life";
              $where ="c.goods_id = bg.id and bg.cate_id = ca.id and bg.type_id = 3  and c.order_sn = '{$order_sn}'";

              $data =  $this->db_c->table($table)->field($field)->where($where)->find();
              

             $data['start_date'] = date('Y-m-d',$data['start_date']);
            

             

             $this->assign('info',$data);   
         }

        
         
         $this->display();   
         } 
        
    }

    public function info(){
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
        $field_1="a.depart_id,a.name,a.signature,depart_name,sa.explain,sa.status,sa.time";
        $where_1="sa.audit_id = a.id  and a.depart_id = d.id and sa.scrap_id ={$id}";



        $audits_lists= $this->db_sa->table($table_1)->field($field_1)->where($where_1)->select();

        foreach ($audits_lists as $key => $val) {
             $audits_lists[$key]['time']= date('Y-m-d',$val['time']);
        }
 
        $this->assign('goods_lists',$goods_lists);//卡片物品列表
        $this->assign('info',$info); //报废详情
        $this->assign('audits_lists',$audits_lists); //审核人员

        $this->display();
    }


     //导出数据
    public function export() 
    {
        $assets_name = I('assets_name');
        $s_time = strtotime(  I('s_time') );
        $e_time = strtotime(  I('e_time') );
        $order_sn = I('order_sn');
        $len = I('size')?:10;
        $page = I('page');
        $first = $len *($page-1);
    

        $table ='k_scrap s,k_scrap_goods sg,k_cards c,k_base_goods bg,k_category ca'; 

        $field='s.id,bg.assets_name,bg.unit,c.spec,c.department,c.original_value,c.start_date,s.remark,c.order_sn,ca.cate_name';
        $where ="s.id = sg.scrap_id and sg.card_sn = c.order_sn and c.goods_id = bg.id and bg.cate_id = ca.id and s.status = 4 and s.type = 3";

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and s.org_id = ({$this->org_id})";
        }

        if (!empty($assets_name)) {
          $where .=" and bg.assets_name like '%{$assets_name}%'";
        }

        if ( !empty($order_sn) ) {
            $where .= " and s.order_sn like '%{$order_sn}%'";
        }

        if (!empty($s_time) && !empty($e_time) ) {
          $where .= " and s.time between {$s_time} and {$e_time}";
        }

        // $count          =  $this->db->table($table)->where($where)->count();
        // $page           = new \Think\PageA($count,10);
        // $show           = $page->show();


         $lists =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($first,$len)
        ->where($where)
        ->order('s.time DESC')
        ->select();

        foreach ($lists as $key => $val) {
              $lists[$key]['id'] = $key+1;
              $lists[$key]['start_date'] = date('Y-m-d H:i');
        }

        // dump($lists);exit();

        // dump($lists);exit();

        return $lists;
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

            if ( empty($_data['e0']['order_sn']) ) { //检测第一个
                 $this->error('请添加报废信息');
            }

            if ( empty($_data['data']['status']) ) {
                $this->error('请选择报废理由');
            }

             if ( empty($_data['data']['depart_id']) ) {
                 $this->error('请选择审核人部门');
            }

            // dump($_data);exit();
            $this->db->startTrans();
             //数据入库
             $dataS['id'] = $_data['data']['id'];
             $dataS['order_sn'] = $_data['data']['order_sn'];
             $dataS['time'] = !empty($_data['data']['time'])?strtotime($_data['data']['time']):time();
             $dataS['creater'] = $_SESSION['admin']['info']['name'];
             if ($_data['data']['status'] == '其他') { //其他显示填写原因
                 $dataS['remark'] = $_data['data']['reason'];
             }else{
                 $dataS['remark'] = $_data['data']['status']; //否则直接是status
             }
             $dataS['org_id'] =  $this->is_supper?$_data['data']['org_id']:$this->org_id; //部门ID
            
             $dataS['status'] = 1;//默认为未审核
             $dataS['last_op_time'] = time();
             $dataS['type'] = 3;

             $res = $this->db->save($dataS);


              $d_sa = $this->db_sa->where( array('scrap_id'=>$_data['data']['id']) )->delete();//清空审核人表

              $d_m = $this->db_m->where( array('apply_id'=>$_data['data']['id'],'type'=>70) )->delete();//清空消息表

              $d_sg = $this->db_sg->where( array('scrap_id'=>$_data['data']['id']) )->delete();//清空物品表



             if (is_array($_data['data']['depart_id'])) { //多个审核人
                 # code...
               foreach ($_data['data']['depart_id'] as $key => $val) { //审核人员入库
                $map['is_auditer'] = 1;
                $map['depart_id'] = $val;

                   
                     $dataSA['scrap_id']= $_data['data']['id'];
                     $dataSA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataSA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataSA['time'] =time();
                     $dataSA['sort'] = $key;

                     if ( empty( $dataSA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }

                     $info['scrap_id'] = $dataSA['scrap_id'];
                     $info['audit_id'] = $dataSA['audit_id'];
                     if ( $this->db_sa->where( $info )->find() ) {
                         $this->error('请勿重复添加，审核部门');
                     }


                     $res_2 = $this->db_sa->add($dataSA); 
                     
                     // if ( $this->db_sa->where($dataSA)->find() ) { //找到修改
                     //     $mapE['scrap_id'] = $dataSA['scrap_id'];
                     //     $mapE['audit_id'] = $dataSA['audit_id'];
                     //     $res_2 = $this->db_sa->where( $mapE )->save($dataSA);
                     // }else{ //找不到就添加
                     //     $res_2 = $this->db_sa->add($dataSA); 
                     // }

                      if ($key == 0 ) { //第一个审核人 发送消息
                         $dataM['title'] = '报废申请审核信息';
                         $dataM['apply_id'] = $_data['data']['id'];
                         $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                         $dataM['receive_id'] = $dataSA['audit_id'];
                         $dataM['status'] = 1;
                         $dataM['url'] = U('ScrapApply/info',array('id'=>$_data['data']['id']));
                         $dataM['send_time'] = time();
                         $dataM['type'] = 70;
                         //消息推送
                         $res_3 = $this->db_m->add($dataM);
                     }

                    

                     // $dataM['title'] = '报废申请审核信息';
                     // $dataM['apply_id'] = $_data['data']['id'];
                     // $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     // $dataM['receive_id'] = $dataSA['audit_id'];
                     // $dataM['status'] = 1;
                     // $dataM['url'] = U('ScrapApply/info',array('id'=>$_data['data']['id']));
                     // $dataM['send_time'] = time();
                     // //消息推送
                     // $res_3 = $this->db_m->add($dataM);

                     // if ( $this->db_m->where($dataM)->find ) { //找得到
                     //      $mapE['apply_id'] = $dataM['apply_id'];
                     //      $mapE['receive_id'] = $dataSA['audit_id'];
                     //      $res_3 = $this->db_m->where($mapE)->save($dataM);
                     // }else{
                     //      $res_3 = $this->db_m->add($dataM);
                     // }
                    

                }    
            }else{ //一个审核人
                $map['is_auditer'] = 1;
                $map['depart_id'] = $_data['data']['depart_id'];
                     $dataSA['scrap_id']= $_data['data']['id'];
                     $dataSA['audit_id'] = $this->db_a->where($map)->getField('id');
                     $dataSA['audit_name'] =$_SESSION['admin']['info']['name'];
                     $dataSA['time'] =time();
                     $dataSA['sort'] = 0;

                      if ( empty( $dataSA['audit_id'] ) ) {
                         $this->error('请检查部门下是否添加审核人员');
                     }

                     $res_2 = $this->db_sa->add($dataSA); 

                     $dataM['title'] = '报废申请审核信息';
                     $dataM['apply_id'] = $_data['data']['id'];
                     $dataM['send_id']  = $_SESSION['admin']['info']['id'];
                     $dataM['receive_id'] = $dataSA['audit_id'];
                     $dataM['status'] = 1;
                     $dataM['url'] = U('ScrapApply/info',array('id'=>$_data['data']['id']));
                     $dataM['send_time'] = time();
                     $dataM['type'] = 70;
                     //消息推送
                     $res_3 = $this->db_m->add($dataM);
            }


             unset($_data['data']); //去除

           

             foreach ($_data as $key => $val) { //去除空行
                 if ($val['order_sn'] == '') {
                     unset($_data[$key]);
                 }
             }
           
           
          
             foreach ($_data as $key => $val) { //报废卡片入库

                  $dataSG['scrap_id'] = $dataSA['scrap_id'];
                  $dataSG['card_sn'] = $val['order_sn'];
                  $res_4 = $this->db_sg->add($dataSG);
             }

             if ($res!==false && $d_sa!==false && $d_m!==false && $d_sg!==false  && $res_2!==false && $res_3!==false && $res_4!==false) {
                 $this->db->commit();
                 $this->success('修改成功',U('ScrapApply/index'));
             }else{
                $this->db->rollback();
                 $this->error('添加失败');
             }
        }else{
                $id = I('get.id');

                $table = "k_scrap_goods sg, k_cards c,k_base_goods bg,k_category ca";
                $field="c.order_sn,bg.assets_name,bg.unit,ca.cate_name,c.spec,c.original_value,c.start_date,c.service_life";
                $where ="sg.card_sn = c.order_sn and c.goods_id = bg.id and bg.cate_id = ca.id and sg.scrap_id ={$id} ";

                $goods_lists =  $this->db_sg->table($table)->field($field)->where($where)->select();

                // dump($goods_lists);
               // echo $this->db_sg->getLastSql();
            
                
                foreach ($goods_lists as $key => $val) {
                     $goods_lists[$key]['start_date'] = date('Y-m-d',$val['start_date']);
                }

                $info = $this->db->where(array('id'=>$id))->find();
                $info['time'] = date('Y-m-d',$info['time']);

                $table_1 ="k_scrap_audit sa,k_admin a,k_depart d";
                $field_1="a.depart_id,a.name,depart_name,sa.explain,sa.status,sa.time";
                $where_1="sa.audit_id = a.id  and a.depart_id = d.id and sa.scrap_id ={$id}";



                $audits_lists= $this->db_sa->table($table_1)->field($field_1)->where($where_1)->select();

                foreach ($audits_lists as $key => $val) {
                     $audits_lists[$key]['time']= date('Y-m-d',$val['time']);
                }
         
                $this->assign('goods_lists',$goods_lists);//卡片物品列表
                $this->assign('info',$info); //报废详情
                $this->assign('audits_lists',$audits_lists); //审核人员

                // dump($goods_lists);

                $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
                $cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
                
                $top_departs = $cat->getList($c_id);

                $this->assign('top_departs',$top_departs);

                $this->display();
        }
    }

    //删除
    public function del()
    {
        $res!==false?$this->success(''):$this->error('');
    }

    public function getData(){

        $_data = I('post.');

        // dump($_POST);exit();
   
         
        $table = "k_cards c,k_base_goods bg,k_category ca";
        $field="c.order_sn,bg.assets_name,bg.unit,ca.cate_name,c.spec,c.original_value,c.start_date,c.service_life";
        $where ="c.goods_id = bg.id and bg.cate_id = ca.id and bg.type_id = 3  and {$_data['field']} like '{$_data['value']}%'";

        $data =  $this->db_c->table($table)->field($field)->where($where)->limit(10)->select();

        foreach ($data as $key => $val) {
             $data[$key]['start_date'] = date('Y-m-d',$val['start_date']);
        }
        
        $this->ajaxReturn($data);

        // echo json_encode($data);

        

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