<?php
// +----------------------------------------------------------------------
// | QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com/)
// +----------------------------------------------------------------------
// | Author: wu hui (13278760361@163.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-28 10:18:02
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 卡片管理
 */
class CardsController extends BaseController {
    
    function _initialize(){
        parent::_initialize();
        $this->use_status = array('1'=>'使用中','2'=>'变更中','3'=>'报废中','4'=>'维修中','5'=>'报废');//卡片使用状态
        $this->depr_method = array('1'=>'年限折旧法','2'=>'工作量法','3'=>'双倍余额递减法','4'=>'年数总和法');
        $this->add_method = array('直接购入','投资者投入','合资投资购入','在建工程转入','行内调入','融资租入','盘盈','捐赠','其他');
        $this->is_supper = $_SESSION['admin']['info']['is_supper']; //是否是超级管理员 可以查看添加所有数据
      
        $this->org_id   = $_SESSION['admin']['info']['org_id'];  //部门ID 顶级
 
        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

        $lists = $cat->getList(0);

        $self =  $cat->  getInfo($this->org_id,$lists);

      //  $childs = $cat-> getChild($this->org_id,$lists);
        $childs = $cat-> getList($this->org_id,$lists);
        $this->departs = $this->is_supper ? $lists : array_merge(array($self) ,$childs) ;
    
        $this->assign('is_supper',$this->is_supper);
        $this->assign('departs',$this->departs);
        $this->assign('depr_method',$this->depr_method);
        $this->assign('add_method',$this->add_method);
    }
    //导出模板
    public $outlist=array('type'=>'所属分类','assets_name'=>'物品名称','p_org_name'=>'所属单位','org_name'=>'使用部门','user'=>'使用人','spec'=>'规格型号','add_method'=>'增加方式','use_status'=>'使用状态','unit'=>'单位','original_value'=>'原值','start_date'=>'开始使用时间','service_life'=>'使用年限','depr_method'=>'折旧方法','net_salvage'=>'净残值率','keeper'=>'保管人','store_address'=>'保管地点','department'=>'保管部门','company_name'=>'供应商','tel_phone'=>'供应商联系电话','contacts'=>'供应商联系人','inputer'=>'添加人','taxpayer_type'=>'纳税人类型','vat_rate'=>'增值税税率');
    //导入
    public $inlist=array('type','assets_name','p_org_name','org_name','user','spec','add_method','use_status','unit','original_value','start_date','service_life','depr_method','net_salvage','keeper','store_address','department','company_name','tel_phone','contacts','inputer','taxpayer_type','vat_rate');//,'store_address','keeper','cards_num','service_life','depr_method','start_date','currency','original_value','net_salvage','company_name','taxpayer_type','vat_rate','inputer'
    
    //卡片列表
    public function index(){
        $cards = M('Cards');
        $data = I('get.');
        
        //$len=I('size')?:15;
        $len=I('size')?:20;
        $keys=I('get.key_word')?:'';
       // $where = "c.goods_id=bg.id AND bg.cate_id=cg.id AND c.supper_id=s.id AND c.department=d.id AND bg.type_id=3";
       $where = "bg.type_id=3";
        // echo "<pre>";
        // print_r($_SESSION['admin']['info']['org_id']);exit;
        if($_SESSION['admin']['info']['org_id']!="" && $_SESSION['admin']['info']['org_id']!=0){
            $where.=" AND c.org_id=".$_SESSION['admin']['info']['org_id'];
        }
        if($data['str_time']!='' && $data['end_time']!=''){
            $where.=" AND c.start_date between ".strtotime($data['str_time'])." and ".strtotime($data['end_time']);
        }
        $cs = $data['card_sn'];
        if($cs!=''){
            $where.=" AND c.order_sn LIKE '%{$cs}%'";
        }
        $assets_name = $data['goods_name'];
        if($assets_name!=''){
            $where.=" AND bg.assets_name LIKE '%{$assets_name}%'";
        }
        $department = $data['department'];
        if($department){
            $where.=" AND d.id='{$department}'";
        }
        if($keys){
            $where.=isMobile()?
            " AND (c.order_sn LIKE '%{$keys}%' OR bg.assets_name LIKE '%{$keys}%' OR d.depart_name LIKE '%{$keys}%')":
            " AND (c.order_sn LIKE '%{$keys}%' OR bg.assets_name LIKE '%{$keys}%' OR d.depart_name LIKE '%{$keys}%')";
        }
        $field = "c.*,bg.assets_name,cg.cate_name,cg.sn,s.company_name,d.depart_name as department";
       // $count = $cards->table(array('k_cards'=>'c','k_base_goods'=>'bg','k_category'=>'cg','k_supplier'=>'s','k_depart'=>'d'))
        $count = M()->table('k_cards c')
                ->join("k_base_goods bg ON c.goods_id=bg.id","left")
                ->join("k_category cg ON bg.cate_id=cg.id","left")
                ->join("k_supplier s ON c.supper_id=s.id","left")
                ->join("k_depart d ON c.department=d.id","left")
                ->where($where)

                ->count();
        $page = new \Think\PageA($count,$len);
        $show           = $page->show();
        $list = M()->field($field)->table('k_cards c')
                ->join("k_base_goods bg ON c.goods_id=bg.id","left")
                ->join("k_category cg ON bg.cate_id=cg.id","left")
                ->join("k_supplier s ON c.supper_id=s.id","left")
                ->join("k_depart d ON c.department=d.id","left")
                ->limit($page->firstRow.','.$len)
                ->where($where)
                ->order('c.id DESC')->select();
        // echo "<pre>";
        
        if(!empty($list)){
            foreach ($list as $key => $value) {
                # code...
                $list[$key]['start_date'] = date('Y-m-d',$value['start_date']);
            }
        }
        // echo "<pre>";
        // print_r($list);exit;
        $this->assign('card_sn',$cs);
        $this->assign('goods_name',$assets_name);
        $this->assign('str_time',$data['str_time']);
        $this->assign('end_time',$data['end_time']);
        $this->assign('depr_method',$this->use_status);
        $this->assign('department',$department);
        $this->assign('list',$list);
        //print_r($key2);exit;
        $this->assign('key_word',$keys);
        $this->assign('ajaxlen',$len);
        $this->assign('page',$show);
        $this->display();
    }

    public function ajaxindex()
    {
        $cards = M('Cards');
        $page=I('get.page');
        $len=I('get.size')?:5;
        $key=I('get.key_word');
        $first=$len*($page-1);
        $where = "c.goods_id=bg.id AND bg.cate_id=cg.id AND c.department=d.id AND bg.type_id=3";
        if($_SESSION['admin']['info']['org_id']!="" && $_SESSION['admin']['info']['org_id']!=0){
            $where.=" AND c.org_id=".$_SESSION['admin']['info']['org_id'];
        }
        $field = "c.*,bg.assets_name,cg.cate_name,cg.sn,d.depart_name as department";
       if($key){
            $where.=isMobile()?" AND c.order_sn LIKE '%{$key}%' or bg.assets_name LIKE '%{$key}%' or d.depart_name LIKE '%{$key}%'":" AND c.order_sn LIKE '%{$key}%' or bg.assets_name LIKE '%{$key}%' or d.depart_name LIKE '%{$key}%'";
        }
        // $lists=M()->field('a.*,g.title as role,d.depart_name')->table("k_admin a")->join("k_admin_group_access ga ON a.id=ga.uid","left")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->join("k_depart d ON a.depart_id=d.id","LEFT")->where($where)->order('a.update_time desc')->limit($first,$len)->select();
       
        $lists = $cards->field($field)
                ->table('k_cards c')
                ->join("k_base_goods bg ON c.goods_id=bg.id","left")
                ->join("k_category cg ON bg.cate_id=cg.id","left")
                ->join("k_supplier s ON c.supper_id=s.id","left")
                ->join("k_depart d ON c.department=d.id","left")
                ->limit($first.','.$len)
                ->where($where)->order('c.id DESC')
                ->select();
        $this->ajaxReturn($lists);
    }
    //卡片新增
    public function add(){
        if(IS_POST){
            $datas = I('post.');

            if($datas['assets_name']==""){
                $this->error("物品名称不能为空！！");
            }
            $is_goods = M('base_goods')->where(array('id'=>$datas['id']))->find();
            
            if(empty($is_goods)){
                $this->error("物品不存在，请先录入物品！！");
            }
            if($datas['cards_num'] == ""){
                $this->error("卡片数量不能为空！！");
            }
            if(intval($datas['cards_num'])<1){
                $this->error("数量必须大于0！！");
            }
            if(!preg_match('/^[0-9]*$/',$datas['cards_num'])){
                $this->error("数量必须为数字！！");
            }
            if($datas['original_value']==""){
                $this->error("原值不能为空！！");
            }
            if($datas['net_salvage']==""){
                $this->error("净残值率不能为空！！");
            }
            if($datas['service_life']==""){
                $this->error("使用那些不能为空！！");
            }
            $s = M('supplier')->where(array('company_name'=>$datas['supper_id']))->find();
            if($s){
                $datas['supper_id'] = $s['id'];
            }else{
                $rss = M('supplier')->data(array('company_name'=>$datas['supper_id']))->add();
                $datas['supper_id'] = $rss;
            }
            $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

            $lists = $cat->getList(0);
            $self =  $cat->  getInfo($datas['use_depart'],$lists);
            $depars = $cat->getPath($datas['use_depart']);
            $ar = array();
            foreach($depars as $k=>$v){
                $ar[] = $v['depart_name'];
            }
            $datas['use_depart'] = implode('/',$ar).'/'.$self['depart_name'];

            for($i=0;$i<intval($datas['cards_num']);$i++){
                $data['order_sn'] = createOrderSn(C('KPSN'));
                $data['goods_id'] = $datas['id'];
                $data['spec'] = $datas['spec'];
                $data['department'] = $datas['department'];
                $data['add_method'] = $datas['add_method'];
                $data['store_address'] = $datas['store_address'];
                $data['keeper'] = $datas['keeper'];
                $data['service_life'] = $datas['service_life'];
                $data['depr_method'] = $datas['depr_method'];
                $data['start_date'] = strtotime($datas['start_date']);
                $data['original_value'] = $datas['original_value'];
                $data['net_salvage'] = $datas['net_salvage'];
                $data['net_residual_value'] = $datas['net_residual_value'];
                $data['mon_depr_rate'] = substr($datas['mon_depr_rate'],0,-1);
                $data['mon_depr_amount'] = $datas['mon_depr_amount'];
                $data['supper_id'] = $datas['supper_id']?$datas['supper_id']:0;
                $data['taxpayer_type'] = $datas['taxpayer_type']?$datas['taxpayer_type']:0;
                $data['vat_rate'] = intval($datas['vat_rate']);
                $data['tax_val'] = $datas['tax_val'];
                $data['inputer'] = $datas['inputer'];
                $data['input_time'] = strtotime($datas['input_time']);
                $data['create_times'] = time();
                $data['mon_total'] = $datas['mon_total'];
                $data['org_id'] = $_SESSION['admin']['info']['org_id'];
                $data['use_depart'] = $datas['use_depart'];
                //$data['use_status'] = $datas['use_status'];
                if($data['depr_method']==1){
                    $data['month_works'] = $datas['month_works'];
                }
                $data['qrcode'] = $this->qrcode($data['order_sn']);//二维码
                
                $rs = M('Cards')->add($data);
            }

            if($rs){
                $this->success("添加成功！！",U('index'));
            }else{
                $this->error("添加失败！！");
            }
        }else{
            if(I('get.goods_id')!=""){
                $where = "bg.cate_id=c.id AND bg.type_id=t.id AND bg.id=og.goods_id AND bg.id='".I('get.goods_id')."'";
                $field = "bg.*,c.cate_name,c.sn,t.type_name,og.num";
                $ginfo = M()->field($field)->table(array('k_base_goods'=>'bg','k_category'=>'c','k_type'=>'t','k_outstock_goods'=>'og'))->where($where)->find();
                $this->assign('ginfo',$ginfo);
            }
            
            $this->display();
        }

    }
    //根据物品ID获取规格
    public function getSpecById(){
        $id = I('post.id');
        // $spec = 
        $lists = M('stock')->where(array('goods_id'=>$id))->select();

        echo json_encode($lists);
        
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
        //print_r($lists);exit;
        $this->ajaxReturn($lists);
    }
    //生成二维码
    public function qrcode($kpsn){
        Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $qrcode_path='';
       
        $url = "http://".$_SERVER['HTTP_HOST'].'/Admin/Cards/edit/order_sn/'.$kpsn;
        $errors=array();
        if(!empty($url)){
            $content = trim($url); //二维码内容
           // $contentSize=$this->getStringLength($content);
            // if($contentSize>150){
            //     $errors[]='字数过长，不能多于150个字符！';
            // }
            // if(isset($_FILES['upimage']['tmp_name']) && $_FILES['upimage']['tmp_name'] && is_uploaded_file($_FILES['upimage']['tmp_name'])){
            //     if($_FILES['upimage']['size']>512000){
            //         $errors[]="你上传的文件过大，最大不能超过500K。";
            //     }
            //     $file_tmp_name=$_FILES['upimage']['tmp_name'];
            //     $fileext = array("image/pjpeg","image/jpeg","image/gif","image/x-png","image/png");
            //     if(!in_array($_FILES['upimage']['type'],$fileext)){
            //         $errors[]="你上传的文件格式不正确，仅支持 png, jpg, gif格式。";
            //     }
            // }
           // $tpgs=$_POST['tpgs'];//图片格式
            $qrcode_bas_path='Uploads/images/qrcode/';
            if(!is_dir($qrcode_bas_path)){
                mkdir($qrcode_bas_path, 0777, true);
            }
            $uniqid_rand=date("Ymdhis").uniqid(). rand(1,1000);
            //$qrcode_path=$qrcode_bas_path.$uniqid_rand. "_1.".$tpgs;//原始图片路径
            $qrcode_path_new=$qrcode_bas_path.$uniqid_rand."_2.".'gif';//二维码图片路径
            // if(Helper::getOS()=='Linux'){
            //     $mv = move_uploaded_file($file_tmp_name, $qrcode_path);
            // }else{
            //     //解决windows下中文文件名乱码的问题
            //     $save_path = Helper::safeEncoding($qrcode_path,'GB2312');
            //     if(!$save_path){
            //         $errors[]='上传失败，请重试！';
            //     }
            //     $mv = move_uploaded_file($file_tmp_name, $qrcode_path);
            // }
            if(empty($errors)){
                $errorCorrectionLevel = $_POST['errorCorrectionLevel'];//容错级别
                $matrixPointSize = 4;//生成图片大小
                $matrixMarginSize = 0;//边距大小
                //生成二维码图片
                $object::png($content,$qrcode_path_new, $errorCorrectionLevel, $matrixPointSize, $matrixMarginSize);
                $QR = $qrcode_path_new;//已经生成的原始二维码图
              //  $logo = $qrcode_path;//准备好的logo图片
                if (file_exists($logo)) {
                    $QR = imagecreatefromstring(file_get_contents($QR));
                 //   $logo = imagecreatefromstring(file_get_contents($logo));
                    $QR_width = imagesx($QR);//二维码图片宽度
                    $QR_height = imagesy($QR);//二维码图片高度
                   // $logo_width = imagesx($logo);//logo图片宽度
                  ///  $logo_height = imagesy($logo);//logo图片高度
                  //  $logo_qr_width = $QR_width / 5;
                    $scale = $logo_width/$logo_qr_width;
                   // $logo_qr_height = $logo_height/$scale;
                 //   $from_width = ($QR_width - $logo_qr_width) / 2;
                    //重新组合图片并调整大小
                    // imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                    // $logo_qr_height, $logo_width, $logo_height);
                    //输出图片
                    //header("Content-type: image/png");
                    imagepng($QR,$qrcode_path_new);
                    imagedestroy($QR);
                }else{
                    $qrcode_path=$qrcode_path_new;
                }
            }else{
                $qrcode_path='';
            }
        }
        $data=array('data'=>array('errors'=>$errors,'qrcode_path'=>$qrcode_path));
       // print_r($data);exit;
        return $qrcode_path;
       }
       //查看
       public function edit(){
        // echo "<pre>";
        // print_r($_SESSION['admin']['info']);exit;
            if(IS_POST){
                $is_pd = I('post.is_pd');
                $goods_id = I('post.goods_id');
                if($is_pd==0){
                    $rs = M('Cards')->where(array('goods_id'=>$goods_id))->data(array('is_inventory'=>1))->save();
                    if($rs){
                        $this->success("盘点成功！！",-1);
                    }else{
                        $this->error("盘点失败！！");
                    }
                }else{
                    $this->error("你已经盘点过了！！");
                }
            }else{

                $id = I('get.id');
               // print_r($id);exit;
                $where = "c.goods_id=bg.id AND bg.cate_id=cg.id AND bg.type_id=t.id AND c.department=d.id";
                if($id!=""){
                    $where.=" AND c.id='{$id}'";
                }
                if(I('get.order_sn')!=""){
                    $where.=" AND order_sn='".I('get.order_sn')."'";
                }
                $field = "c.*,bg.assets_name,cg.cate_name,cg.sn,t.type_name,d.depart_name as department";
                $info = M('Cards')->field($field)->table(array('k_cards'=>'c','k_base_goods'=>'bg','k_category'=>'cg','k_type'=>'t','k_depart'=>'d'))->where($where)->find();
                //$info['qrcode'] = $_SERVER['HTTP_HOST'].'/'.$info['qrcode'];
                //print_r(count($this->
                //));exit;

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
            }
            
       }
       //折旧算法
       public function depr(){
            $datas = I('post.');
            $data = deprs($datas);
            echo json_encode($data);


       }
           //导出数据
    public function  export(){

        $cards = M('Cards');
        $data = I('get.');
        //
        $where = "c.goods_id=bg.id AND bg.cate_id=cg.id";
        if($_SESSION['admin']['info']['org_id']!=""){
            $where.=" AND c.org_id=".$_SESSION['admin']['info']['org_id'];
        }
        if($data['str_time']!='' && $data['end_time']!=''){
            $where.=" AND c.input_time between ".strtotime($data['str_time'])." and ".strtotime($data['end_time']);
        }
        $cs = $data['card_sn'];
        if($cs!=''){
            $where.=" AND c.order_sn LIKE '%{$cs}%'";
        }
        $assets_name = $data['goods_name'];
        if($assets_name!=''){
            $where.=" AND bg.assets_name LIKE '%{$assets_name}%'";
        }

        $field = "c.*,bg.assets_name,cg.cate_name,cg.sn,s.company_name";
        
        $lists = $cards->field($field)->table(array('k_cards'=>'c','k_base_goods'=>'bg','k_category'=>'cg','k_supplier'=>'s'))->where($where)->group('c.order_sn')->order('c.id DESC')->select();
        if(!empty($lists)){
            foreach ($lists as $key => $value) {
                # code...
                $lists[$key]['start_date'] = date('Y-m-d',$value['start_date']);
                $lists[$key]['id'] = $key+1;

            }
        }
        
        return $lists;
    }
    //导出卡片数据
    public function c_export(){
        $cat =  CAT('Category',array('id','pid','cate_name'),'');
        $derp = CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $cards = M('Cards');
        $data = I('get.');
        $len = I('size')?:50;
        $page = I('page');
        $first = $len*($page-1);
       // if($page>10){return "";}
        $d_lists = $derp->getList(0);
        
        $where = "bg.type_id=3";
        
        if($_SESSION['admin']['info']['org_id']!="" && $_SESSION['admin']['info']['org_id']!=0){
            $where.=" AND c.org_id=".$_SESSION['admin']['info']['org_id'];
        }
        if($data['str_time']!='' && $data['end_time']!=''){
            $where.=" AND c.input_time between ".strtotime($data['str_time'])." and ".strtotime($data['end_time']);
        }
        $cs = $data['card_sn'];
        if($cs!=''){
            $where.=" AND c.order_sn LIKE '%{$cs}%'";
        }
        $assets_name = $data['goods_name'];
        if($assets_name!=''){
            $where.=" AND bg.assets_name LIKE '%{$assets_name}%'";
        }

        $field = "c.*,bg.assets_name,bg.unit,cg.cate_name,cg.id as c_id,cg.sn,s.company_name,s.tel_phone,s.contacts,d.depart_name as department";
        
        $lists = M()->field($field)->table('k_cards c')
                ->join("k_base_goods bg ON c.goods_id=bg.id","left")
                ->join("k_category cg ON bg.cate_id=cg.id","left")
                ->join("k_supplier s ON c.supper_id=s.id","left")
                ->join("k_depart d ON c.department=d.id","left")
                ->where($where)->limit($first,$len)->select();
        // echo "<pre>";
        // print_r($lists);exit;
        if(!empty($lists)){
            foreach ($lists as $key => $value) {
                # code...
                $lists[$key]['start_date'] = date('Y-m-d',$value['start_date']);
                $lists[$key]['id'] = ($page-1)*$len+$key+1;
                $c_arr = array();
                foreach($cat->getPath($value['c_id']) as $k=>$v){
                    $c_arr[] = $v['cate_name'];
                }
                $lists[$key]['type'] = count($c_arr)>0?implode('/',$c_arr).'/'.$value['cate_name']:$value['cate_name'];
                
                $self =  $derp->getInfo($value['belg_id'],$d_lists);

                $self2 =  $derp->getInfo($value['use_id'],$d_lists);
                $lists[$key]['p_org_name'] = $self['depart_name'];
                $lists[$key]['org_name'] = $value['use_depart'];
                //折旧率
                $data['depr'] = $value['depr_method'];
                $data['start_date'] = date('Y-m-d',$value['start_date']);
                $data['service_life'] = $value['service_life'];
                $data['original_value'] = intval($value['original_value']);
                $data['net_salvage'] = intval($value['net_salvage']);
                $data['vat_rate'] = $value['vat_rate'];
                $data['month_works'] = $value['month_works'];
                $data['mon_total'] = $value['mon_total'];
                $jg = deprs($data);
                $lists[$key]['mon_depr_amount'] = $jg['mon_depr_amount'];
                $lists[$key]['mon_depr_sum'] = $jg['mon_depr_sum'];
                $lists[$key]['k_net_worth'] = $jg['k_net_worth'];
                $lists[$key]['net_residual_value'] = $jg['net_residual_value'];
                $lists[$key]['company_name'] = $value['company_name']==0?"":$value['company_name'];
                switch ($value['depr_method']) {
                    case '1':
                        # code...
                        $lists[$key]['depr_method']="年限折旧法";
                        break;
                    case '2':
                        # code...
                        $lists[$key]['depr_method']="工作量法";
                        break;
                    case '3':
                        # code...
                        $lists[$key]['depr_method']="双倍余额递减法";
                        break;
                    default:
                        # code...
                        $lists[$key]['depr_method']="年数总和法";
                        break;
                }
            }
        }else{
            return "";
        }
        
       //  echo "<pre>";
       //  print_r($lists);exit;
       //  //导出数据
       //  $outdata=array('id'=>'序号','cate_sn'=>'资产编码','type'=>'所属分类','assets_name'=>'资产名称','p_org_name'=>'所属单位','org_name'=>'使用部门','user'=>'使用人','spec'=>'规格型号','add_method'=>'增加方式','use_status'=>'使用状态','unit'=>'单位','original_value'=>'原值','start_date'=>'会计投入使用时间','service_life'=>'会计预计使用年限','depr_method'=>'折旧方法','mon_depr_amount'=>'会计月折旧额','mon_depr_sum'=>'会计累计折旧','k_net_worth'=>'会计折余折旧','net_salvage'=>'会计净残值率','net_residual_value'=>'会计净残值','keeper'=>'保管人','store_address'=>'保管地点','department'=>'保管部门','company_name'=>'供应商','tel_phone'=>'供应商联系电话','contacts'=>'供应商联系人','inputer'=>'添加人','taxpayer_type'=>'纳税人类型','vat_rate'=>'增值税税率');
       // // Exporter("卡片导出数据",$lists,$outdata);
       $this->ajaxReturn($lists);
    }
   
    //导入数据
    public function import($datas)
    {
        // echo "<pre>";
        // print_r($datas);exit;
        $derp = CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $boo=true;
        $md=M('Cards');
        $sup = M('supplier');
       // $md->startTrans();
      //  $sup->startTrans();
        $ret = array();
        $i_error = 0;
        $ii = 0;
        $status = 0;
        if(count($datas)<=1000){
            foreach($datas as $k=>$v){
                //if($ii>0){break;}
                $ctype = explode('/',$v['type']);
               // $ccount = count($ctype);
                $c_name = end($ctype);
                $cinfo = M('category')->where(array('cate_name'=>trim($c_name)))->find();//所属分类是否存在

                $cpinfo = M('category')->where(array('cate_name'=>trim($ctype[0])))->find();//顶级分类

               /// $dcount = count($departs);
              //  $d_name = end($departs);
              //  print_r($d_name);exit;
            
                $dpinfo = M('depart')->where(array('depart_name'=>trim($v['p_org_name'])))->find();//顶级部门
              //  $dlists = M('depart')->where(array('depart_name'=>trim($d_name)))->select();//部门是否存在

                
                $departs = explode('/',$v['org_name']);
                
                // $lists = $derp->getList(0);
                //print_r($lists);exit;
                $kpinfo = M('depart')->where(array('depart_name'=>trim($v['department'])))->find();
                switch (trim($v['depr_method'])) {
                    case '平均年限法':
                        # code...
                        $v['depr_method'] = 1;
                        break;
                    case '工作量法':
                        # code...
                        $v['depr_method'] = 2;
                        break;
                    case '双倍余额递减法':
                        # code...
                        $v['depr_method'] = 3;
                        break;
                    default:
                        # code...
                        $v['depr_method'] = 4;
                        break;
                }
               
                $base_goods = M('base_goods')->where(array('assets_name'=>trim($v['assets_name']),'type_id'=>3))->find();
                $data['goods_id'] = $base_goods['id'];

                $v['cards_num'] = $v['cards_num']?$v['cards_num']:1;
                if(!empty($base_goods) && !empty($dpinfo)){
                    $supplier = $sup->where(array('company_name'=>trim($v['company_name'])))->find();
                    $data['supper_id'] = $supplier['id'];
                     
                    if(empty($supplier)){
                        $sudata['company_name'] = $v['company_name'];
                        $sudata['tel_phone'] = $v['tel_phone'];
                        $sudata['contacts'] = $v['contacts'];
                        $sudata['org_id'] = $_SESSION['admin']['info']['org_id'];
                        $rs = $sup->add($sudata);
                        $data['supper_id'] = $rs;
                    }
                    for($i=0;$i<intval($v['cards_num']);$i++){

                        $data['order_sn'] = createOrderSn('KPSN');
                        $data['qrcode'] = $this->qrcode($data['order_sn']);//二维码
                        $data['spec'] =trim($v['spec']);
                        $data['add_method'] = trim($v['add_method']);
                        $data['store_address'] = trim($v['store_address']);
                        $data['keeper'] = trim($v['keeper']);
                        $data['service_life'] = intval(trim($v['service_life']));
                        $data['depr_method'] = trim($v['depr_method']);
                        $data['department'] = $kpinfo['id'];
                        $data['belg_id'] = $dpinfo['id'];
                        $data['use_depart'] = trim($v['org_name']);//使用部门
                        $data['currency'] = trim($v['currency']);
                        $data['original_value'] = intval(trim($v['original_value']));
                        $data['use_status'] = trim($v['use_status']);
                        $zdata['depr'] = trim($v['depr_method'])==''?1:trim($v['depr_method']);
                        $zdata['start_date'] = trim($v['start_date']);
                        $zdata['service_life'] = intval(trim($v['service_life']));
                        $zdata['original_value'] = intval($v['original_value']);
                        $zdata['net_salvage'] = intval($v['net_salvage']);
                        $zdata['vat_rate'] = trim($v['vat_rate']);
                        $zdata['month_works'] = trim($v['month_works']);

                        $zj = deprs($zdata);

                        $data['net_salvage'] = round(trim($v['net_salvage']),2);
                        $data['net_residual_value'] = $zj['net_residual_value'];
                        //$data['mon_depr_sum'] = $zj['mon_depr_sum'];
                        $data['mon_depr_rate'] = $zj['mon_depr_rate'];
                        $data['mon_depr_amount'] = $zj['mon_depr_amount'];
                        //$data['net_worth'] = $zj['net_worth'];
                        //$data['k_net_worth'] = $zj['k_net_worth'];
                        $data['start_date'] = strtotime($v['start_date']);
                        $data['vat_rate'] = trim($v['vat_rate'])?round($v['vat_rate'],2):0;
                        $data['tax_val'] = trim($v['net_salvage'])*trim($v['vat_rate'])/100;
                        
                        $data['taxpayer_type'] = trim($v['taxpayer_type']);
                        $data['inputer'] = trim($v['inputer']);
                        $data['input_time'] = time();
                        $data['create_times'] = time();
                        $data['org_id'] = $dpinfo['id'];
                        $data['cate_sn'] = $dpinfo['depart_no'].$cpinfo['sn'].sprintf('%04s', $k);
                        $data['user'] = trim($v['user']);
                        $boo=$md->add($data);
                        // $md->getLastSql(); exit;
                    }
                }else{
                    $i_error = 1;
                    $ret[] = $v;
                    F('Cards/data',$v);
                    $status = 1;
                    continue;
                }
                
                if(!$boo){$boo=false;break;}
              
                if(!$boo){$boo=false;break;}
            }
        }else{
            $boo=false;
            $i_error = 1;
            exit;
            
        }

        if($i_error==1){
            $this->ajaxReturn(array('status'=>1,'info'=>$ret));
        }
       //var_dump($status);exit;
        
        return $boo?true:false;
    }
    //保存下载
    
    public function save(){
        $mycon = array('卡片单号','物品名称','规格型号','部门','保管人','存放地点');
        $id = I('get.id');
        $where = "c.goods_id=bg.id AND c.department=d.id AND c.id='{$id}'";
        $field = "c.order_sn,bg.assets_name,c.spec,c.keeper,c.store_address,c.qrcode,d.depart_name";
        $info = M('Cards')->field($field)->table(array('k_cards'=>'c','k_base_goods'=>'bg','k_depart'=>'d'))->where($where)->find();
        
        $data = array("{$info['order_sn']}","{$info['assets_name']}","{$info['spec']}","{$info['depart_name']}","{$info['keeper']}","{$info['store_address']}");
        //1、创建画布
        $im = imagecreate(540,186);//新建一个真彩色图像，默认背景是黑色，返回图像标识符。另外还有一个函数 imagecreate 已经不推荐使用。
        imagecolorallocate($im, 255, 255, 255); 
        //2、绘制所需要的图像
        $red = imagecolorallocate($im,190, 190, 190);//创建一个颜色，以供使用
        imageline($im,0,0,540,0,$red);//画一条直线。参数说明：30，30表示起点坐标；240，140表示终点坐标
        //$red = imagecolorallocate($im,255,255,255);//创建一个颜色，以供使用
        //画行
        $x1 = 540;
        $x2 = 180;
        $y1 = 0;
        $y2 = 0;
        //imageline($im,240,0,0,240,$red);//
        //imageline($im,540,31,180,31,$red);//
        $fontcolor = imagecolorallocate($im,0, 0, 0);
        $tX = 0;
        $ttf = './Public/Fonts/simsun.ttc';
        
        for($i=0;$i<7;$i++){
            $y1+=31;
            $y2+=31;
            $tX+=29;
            imageline($im,$x1,$y1,$x2,$y2,$red);//
            imagettftext($im,12,0,190,$tX,$fontcolor,$ttf,$mycon[$i]);
            imagettftext($im,12,0,310,$tX,$fontcolor,$ttf,$data[$i]);
        }
        imageline($im,0,185,540,185,$red);//
        imageline($im,186,0,360,0,$red);//
        
        //画列
        imageline($im,0,0,0,360,$red);//
        $cX1 = 0;
        $cY1=0;
        $cX2=0;
        $cY2=186;
        //imageline($im,$cX1,0,$cX2,186,$red);//
        for($j=0;$j<1;$j++){
            $cX1+=180;
            $cX2+=180;
            imageline($im,$cX1,0,$cX2,186,$red);//
        }
        imageline($im,300,0,300,186,$red);//
        imageline($im,539,0,539,186,$red);//

        $qr = imagecreatefromstring(file_get_contents("http://".$_SERVER['HTTP_HOST']."/".$info['qrcode']));
        //获取图片的宽高

        list($src_w, $src_h) = getimagesize("http://".$_SERVER['HTTP_HOST']."/".$info['qrcode']);
       // imagecopyresampled($im, $qr, 0, 0, 0, 0, 180, 186, 60, 60);
        // header("content-type: image/png");
        //将水印图片复制到目标图片上，最后个参数50是设置透明度，这里实现半透明效果
        imagecopymerge($im, $qr, 20, 20, 0, 0, $src_w, $src_h, 100);
        header("Content-type: application/octet-stream"); 
        header("Content-Disposition: attachment; filename=卡片.png");
        imagepng($im);//输出到页面。如果有第二个参数[,$filename],则表示保存图像
        
        //4、销毁图像，释放内存
        imagedestroy($im);
    }
    //二维码列表
    public function qrcode_list(){
        $where = "c.goods_id=bg.id AND c.department=d.id AND bg.type_id=3";
    //  $where = "c.goods_id=bg.id AND bg.cate_id=cg.id";
        if($_SESSION['admin']['info']['org_id']!="" && $_SESSION['admin']['info']['org_id']!=0){
            $where.=" AND c.org_id=".$_SESSION['admin']['info']['org_id'];
        }
        $lists = M('Cards')->field('c.*,bg.assets_name,d.depart_name as department')->table(array('k_cards'=>'c','k_base_goods'=>'bg','k_depart'=>'d'))->where($where)->select();
        
        $this->assign('lists',$lists);
        $this->display();
    }
    //出库转资产
    public function asset_to_trea(){
        //序号，物品名称，资产分类，数量
        $data = I('get.');
        $len = I('size')?:20;
        $where="ct.outstock_id=og.outstock_id AND og.goods_id=bg.id AND bg.type_id=c.id AND bg.type_id=3";
        if($data['assets_name']!=""){
            $where.=" AND bg.assets_name LIKE '".$data['assets_name']."'";
        }
        if($data['str_time']!="" && $data['end_time']!=""){
            $where.=" AND ct.outstock_at between '".strtotime($data['str_time'])."' AND '".strtotime($data['end_time'])."'";
        }
        $count = M('cards_temp')
                 ->table(array('k_cards_temp'=>'ct','k_outstock_goods'=>'og','k_base_goods'=>'bg','k_category'=>'c'))
                 ->where($where)->count();
        $page = new \Think\PageA($count,$len);
        $show           = $page->show();
        $lists = M('cards_temp')
                 ->field("ct.id,bg.assets_name,bg.id as goods_id,c.cate_name,og.num")
                 ->table(array('k_cards_temp'=>'ct','k_outstock_goods'=>'og','k_base_goods'=>'bg','k_category'=>'c'))
                 ->limit($page->firstRow.','.$len)
                 ->order('ct.id desc')
                 ->where($where)->select();
        
        $this->assign('lists',$lists);
        $this->assign('page',$show);
        $this->assign('assets_name',$data['assets_name']);
        $this->assign('str_time',$data['str_time']);
        $this->assign('end_time',$data['end_time']);
        $this->display();

    }

    //出库转资产手机端
    public function ajaxasset(){
        $page=I('get.page');
        $len=I('get.size')?:5;
        $key=I('get.key_word');
        $first=$len*($page-1);
        $where="ct.outstock_id=og.outstock_id AND og.goods_id=bg.id AND bg.type_id=c.id AND bg.type_id=3";
        if($key!=""){
            $where.=" AND bg.assets_name LIKE'%{$key}%'";
        }
        $lists = M('cards_temp')
                 ->field("ct.id,bg.assets_name,bg.id as goods_id,c.cate_name,og.num")
                 ->table(array('k_cards_temp'=>'ct','k_outstock_goods'=>'og','k_base_goods'=>'bg','k_category'=>'c'))
                 ->limit($page->firstRow.','.$len)
                 ->order('ct.id desc')
                 ->where($where)->select();
        $this->ajaxReturn($lists);
    }

}