<?php
// +----------------------------------------------------------------------
// | QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com/)
// +----------------------------------------------------------------------
// | Author: wu hui (13278760361@163.com)
// +----------------------------------------------------------------------
// | Date: 2016-12-05 10:02:37
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 *采购管理
 */
class PurchaseController extends BaseController {
    
    function _initialize(){
       // $this->departs = M('depart');
    	parent::_initialize();
    	$this->accep = M('acceptance_goods');
        $this->is_supper = $_SESSION['admin']['info']['is_supper']; //是否是超级管理员 可以查看添加所有数据
      	$this->db = M('shopping');
        $this->org_id   = $_SESSION['admin']['info']['org_id'];  //部门ID 顶级
 
        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

        $lists = $cat->getList(988);

        $self =  $cat->  getInfo($this->org_id,$lists);

        $childs = $cat-> getChildren($this->org_id,$lists);
       // $childs = $cat-> getList($this->org_id,$lists);
        $this->departs = $this->is_supper ? $lists : $childs ;
        $this->admin = session('admin');
        $this->assign('is_supper',$this->is_supper);
        $this->assign('departs',$this->departs);
		
    }
    public $outlist=array('id'=>'序号','order_sn'=>'单号','depart'=>'部门','applyer'=>'申请人','apply_time'=>'申请日期','total_price'=>'预算金额','creater'=>'创建人','goods_name'=>'物品名称','spec'=>'规格型号','num'=>'数量');
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
    public function index(){
    	$len=I('size')?:15;
        $keys=I('get.key_word')?:'';
    	$ws = I('get.');
    	$order_sn = $ws['order_sn'];
        $type_id = $ws['type_id'];
        $p_total = 0;
    	$where = "status=4";//s.id=sg.shop_id AND s.depart=d.id AND sg.goods_id=bg.id AND bg.type_id=t.id AND cg.type_id=t.id AND
    	if($order_sn!=''){
    		$where.=" AND order_sn LIKE '%{$order_sn}%'";
    	}
    	$gname = $ws['assets_name'];
    	if($gname!=''){
    		$where.=" AND goods_name LIKE '%{$gname}%'";
    	}
    	$depart = $ws['depart'];
       // print_r($depart);exit;
    	if($depart){
    		$where.=" AND s.depart='{$depart}'";
    	}
    	if($ws['str_time']!='' && $ws['end_time']!=''){
    		$where.=" AND apply_time between ".strtotime($ws['str_time'])." and ".strtotime($ws['end_time']);
    	}

    	if($_SESSION['admin']['info']['is_supper']!=1){
    		$where.=" AND s.org_id='".$_SESSION['admin']['info']['org_id']."'";
    	}
    	if($keys){
    		$where.=isMobile()?
    		" AND (sg.goods_name LIKE '%{$keys}%' OR s.order_sn LIKE '%{$keys}%' OR s.depart LIKE '%{$keys}%')":
    		" AND (sg.goods_name LIKE '%{$keys}%' OR s.order_sn LIKE '%{$keys}%' OR s.depart LIKE '%{$keys}%')";
    	}
        if($type_id){
            $where.=" AND t.id='$type'";
        }
        if($ws['cate_id']){$where.=" AND cg.id='".$ws['cate_id']."'";}
    	$count = M()->table('k_shopping_goods sg')
                 ->join('k_shopping s ON sg.shop_id=s.id')
                 ->join('k_depart d ON s.depart=d.id')
                 ->join('k_base_goods bg ON sg.goods_id=bg.id')
                 ->join('k_type t ON bg.type_id=t.id')
                 ->join('k_category cg ON bg.cate_id=cg.id')
                // ->table(array('k_shopping'=>'s','k_shopping_goods'=>'sg','k_depart'=>'d','k_type'=>'t','k_category'=>'cg','k_base_goods'=>'bg'))
                 ->where($where)
                 ->count();//count("DISTINCT sg.shop_id")分页可以用分组
     //  dump($count);exit;
    	$page = new \Think\PageA($count,C('pagesize'));
    	$show = $page->show();
    	$field = "s.*,sg.goods_name,sg.spec,sg.num,sg.bud_total,sg.remark as remarks,d.depart_name as departs,bg.assets_name,t.type_name,cg.cate_name";
    	$lists = M()->field($field)->table('k_shopping_goods sg')
                 ->join('k_shopping s ON sg.shop_id=s.id')
                 ->join('k_depart d ON s.depart=d.id')
                 ->join('k_base_goods bg ON sg.goods_id=bg.id')
                 ->join('k_type t ON bg.type_id=t.id')
                 ->join('k_category cg ON bg.cate_id=cg.id')
                 ->limit($page->firstRow.','.$len)
                 ->where($where)->select();
     
        foreach ($lists as $key => $value) {
            # code...
            $p_total+=$value['bud_total'];
            $p_num+=$value['num'];
        }
        $in_total = M()->field('SUM(bud_total) as total,SUM(num) as num')
                 ->table('k_shopping_goods sg')
                 ->join('k_shopping s ON sg.shop_id=s.id')
                 ->join('k_depart d ON s.depart=d.id')
                 ->join('k_base_goods bg ON sg.goods_id=bg.id')
                 ->join('k_type t ON bg.type_id=t.id')
                 ->join('k_category cg ON bg.cate_id=cg.id')
                 ->where($where)
                 ->find();
        if($type_id){
            $cat2=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
            $this->assign('cateS',$cat2->getList(0));
        }
       // dump($cat2->getList(0));exit;
       // print_r();exit;
        //$this->assign('cate_id',$ws['cate_id']);
        
     //   $thia->assign('cateS',$cat2->getList(0));
    	$this->assign('order_sn',$order_sn);
    	$this->assign('assets_name',$gname);
    	$this->assign('depart',$depart);
    	$this->assign('lists',$lists);
    	$this->assign('ajaxlen',$len);
    	$this->assign('key_word',$keys);
    	$this->assign('page',$show);
        $this->assign('depart',$depart);
        $this->assign('str_time',$ws['str_time']);
        $this->assign('end_time',$ws['end_time']);
        $this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',$ws['cate_id']);
        $this->assign('s_total',$in_total['total']);
        $this->assign('p_total',$p_total);
        $this->assign('s_num',$in_total['num']);
        $this->assign('p_num',$p_num);
    	$this->display();
    }
    //wp
    public function ajaxindex()
    {
    	//$pur = M('Purchase');
        $page=I('get.page');
        $len=I('get.size')?:2;
        $key=I('get.key_word');
        $first=$len*($page-1);
        $where = "s.id=sg.shop_id AND status=4";

        $field = "s.*,sg.goods_name,sg.spec,sg.num,sg.bud_total,sg.remark";
       if($key){
    		$where.=isMobile()?" AND sg.goods_name LIKE '%{$keys}%' OR s.order_sn LIKE '%{$keys}%' OR s.depart LIKE '%{$keys}%'":
    		" AND sg.goods_name LIKE '%{$keys}%' OR s.order_sn LIKE '%{$keys}%' OR s.depart LIKE '%{$keys}%'";
    	}
        // $lists=M()->field('a.*,g.title as role,d.depart_name')->table("k_admin a")->join("k_admin_group_access ga ON a.id=ga.uid","left")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->join("k_depart d ON a.depart_id=d.id","LEFT")->where($where)->order('a.update_time desc')->limit($first,$len)->select();
       
    	$lists = M('shopping')->field($field)->table(array('k_shopping'=>'s','k_shopping_goods'=>'sg'))->where($where)->limit($first.','.$len)->group('sg.shop_id')->select();
        $this->ajaxReturn($lists);
    }
    //新增
    public function add(){

    	if(IS_POST){
    		$datas = I('post.');
    		// echo "<pre>";
    		// print_r($datas);exit;
    		
    		$admin_info = session('admin.info');
    		$data['order_sn'] = $datas['shop']['order_sn'];
    		// $data['depart'] = $datas['shop']['depart'];
    		// if($data['depart']==''){
    		// 	$this->error("请填写部门！！");
    		// }
            $t_data = $datas;
            if(isset($t_data)){unset($t_data['shop']);}
            foreach ($t_data as $kt => $vt) {
                # code...
                if($vt['assets_name']==""){
                     $this->error("物品名称不能为空！！");
                    
                }
                if(!$vt['goods_id']){
                     $this->error("物品表里没有“{$vt['assets_name']}”,在物品表里添加再操作！！");
                    
                }
                // if(!preg_match('/^[0-9]*$/',$vt['num']) || !preg_match('/([0-9]+\.[0-9]{2})[0-9]*/',$vt['bud_price']) || !preg_match('/([0-9]+\.[0-9]{2})[0-9]*/',$vt['bud_total'])){
                //     $this->error("数量只能为整数，价格为整数或是小数点后两位！！");
                // }
            }
    		$data['applyer'] = $datas['shop']['applyer'];
    		if($data['applyer']==''){
    			$this->error("请填写申请人！！");
    		}
    		// foreach ($datas as $dk => $dv) {
    		// 	# code...
    		// 	if($dk=="shop"){break;}
    		// 	if($dv['assets_name']==""){
    		// 		$this->error("物品名称不能为空！！");
    		// 		break;
    		// 	}
    		// 	$is_g = M('base_goods')->where(array('assets_name'=>$dv['assets_name']))->find();
    		// 	if(empty($is_g)){
    		// 		$this->error("物品表里没有“{$dv['assets_name']}”,在物品表里添加再操作！！");
    		// 		//break;
    		// 	}
    		// 	if(!preg_match('/^[0-9]*$/',$dv['num']) || !preg_match('/([0-9]+\.[0-9]{2})[0-9]*/',$dv['bud_price']) || !preg_match('/([0-9]+\.[0-9]{2})[0-9]*/',$dv['bud_total'])){
    		// 		$this->error("数量只能为整数，价格为整数或是小数点后两位！！");
    		// 	}
    			

    		// }
    		
    		$data['apply_time'] = $datas['shop']['apply_time']?strtotime($datas['shop']['apply_time']):time();
    		$data['remark'] = $datas['shop']['remark'];
    		$data['total_price'] = $datas['shop']['total_price'];
    		$data['create_id'] = $admin_info['id'];
    		$data['creater'] = $admin_info['username'];
    		$data['org_id'] = $_SESSION['admin']['info']['is_supper']?$datas['shop']['depart']:$_SESSION['admin']['info']['org_id'];
            $data['depart'] = $datas['shop']['depart']!=""?$datas['shop']['depart']:$_SESSION['admin']['info']['depart_id'];
    		//if(count((array)$datas['shop']['audit'])>1){
    		foreach ((array)$datas['shop']['audit'] as $key => $value) {
    			# code...
    			$ad = M('admin')->where(array('depart_id'=>$value,'is_auditer'=>1))->find();
    			if(empty($ad)){
    				$dp = M('depart')->where(array('id'=>$value))->find();
    				$this->error("{$dp['depart_name']}没审核人员！请重新选择!!!");
    			}
    			// if($value){

    			// }
    		}
    		//判断是否有相同部门
    		if (count((array)$datas['shop']['audit']) != count(array_unique((array)$datas['shop']['audit']))) {    
			   $this->error("不允许有相同的部门！！");
			}
	    	//}
	    	// echo "<pre>";
	    	// print_r((array)$datas['shop']['audit']);exit;
    		$rs = M('shopping')->data($data)->add();
    		if($rs){
    			foreach((array)$datas['shop']['audit'] as $k=>$v){
    				$aData['shop_id'] = $rs;
    				$aData['audit_id'] = $v;
    				//$aData['time'] = time();
    				$aData['sort'] = $k+1;
    				M('shopping_audit')->data($aData)->add();

    				if($aData['sort']==1){//给第一个审核人发信息
    					//$adinfo =
    					$send['apply_id'] = $rs;
    					$send['send_id'] = $_SESSION['admin']['info']['id'];
    					$send['receive_id'] =  M('admin')->where(array('depart_id'=>$v))->getField('id');
    					$send['title'] = "采购申请审核信息";
    					$send['url'] = "/Admin/Purchase/audit/shop_id/".$rs.'.html';
    					$send['send_time'] = time();
                        $send['type'] = 10;
    					M('Message')->data($send)->add();
    				}
    			}
    			if(isset($datas['shop'])){unset($datas['shop']);}
    			foreach($datas as $key=>$value){
                    if($value['assets_name']!=""){
        				$sData['shop_id'] = $rs;
        				$sData['goods_name'] = $value['assets_name'];
        				$sData['goods_id'] = $value['goods_id'];
        				$sData['spec'] = $value["spec"];
        				$sData['num'] = $value['num'];
        				$sData['bud_price'] = $value['bud_price'];
        				$sData['bud_total'] = $value['bud_total'];
        				$sData['remark'] = $value['remark'];
        				M('shopping_goods')->data($sData)->add();
                    }
    			} 
    			$this->success("添加成功！！",U('index'));
    		}else{
    			$this->error("添加失败！！");
    		}
    	}else{
            $org_id=I('get.org')?:0;
            $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
    		$cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
	        
            $top_departs = $cat->getList($c_id);
	        $this->assign('top_departs',$top_departs);
            $this->assign('org',$org_id);
            $this->assign('list_dep',$this->getDeparts($org_id));
           // $this->ajaxReturn('departs',$this->departs);
            $this->assign('order_sn',createOrderSn('CGSN'));
    		$this->display();
    	}
    	
    }
    //获取物品列表
    public function get_goods(){
    	//print_r(I('post.'));exit;
    	$aname = I('post.value');
    	$where = "bg.type_id=t.id";
		$where.= " AND assets_name LIKE '{$aname}%'";
		$field="bg.*,bg.id as goods_id,t.type_name";
		$lists = M("base_goods")->field($field)
                 ->table(array('k_base_goods'=>'bg','k_type'=>'t'))
                 ->where($where)
                 ->limit(10)->select();
        foreach ($lists as $key => $value) {
            # code...
            $lists[$key]['unit'] = $value['unit']==null?"":$value['unit'];
        }
		$this->ajaxReturn($lists);
    }
    //编辑
    public function edit(){
    	$admin_info = session('admin.info');
    	$shop_id = I('get.shop_id');
    	if(IS_POST){
    		$datas = I('post.');

    		// echo "<pre>";
    		// print_r($datas);exit;
    		$admin_info = session('admin.info');
    		$data['order_sn'] = createOrderSn('CGSN');
    		$shop_id = $datas['shop']['id'] ;
    		// $data['depart'] = $datas['shop']['depart'];
    		// if($data['depart']==''){
    		// 	$this->error("请填写部门！！");
    		// }
    		$data['applyer'] = $datas['shop']['applyer'];
    		if($data['applyer']==''){
    			$this->error("请填写申请人！！");
    		}
    		// foreach ($datas as $dk => $dv) {
    		// 	# code...
    		// 	if($dk=="shop"){break;}
    		// 	if($dv['assets_name']==""){
    		// 		$this->error("物品名称不能为空！！");
    		// 		break;
    		// 	}
    		// 	$is_g = M('base_goods')->where(array('assets_name'=>$dv['assets_name']))->find();
    		// 	if(empty($is_g)){
    		// 		$this->error("物品表里没有“{$dv['assets_name']}”,在物品表里添加再操作！！");
    		// 		//break;
    		// 	}
    		// 	if(!preg_match('/^[0-9]*$/',$dv['num']) || !preg_match('/([0-9]+\.[0-9]{2})[0-9]*/',$dv['bud_price']) || !preg_match('/([0-9]+\.[0-9]{2})[0-9]*/',$dv['bud_total'])){
    		// 		$this->error("数量只能为整数，价格为整数或是小数点后两位！！");
    		// 	}
    			

    		// }
    		

    		//if(count((array)$datas['shop']['audit'])>1){
    		foreach ((array)$datas['shop']['audit'] as $key => $value) {
    			# code...
    			$ad = M('admin')->where(array('depart_id'=>$value,'is_auditer'=>1))->find();
    			if(empty($ad)){
    				$dp = M('depart')->where(array('id'=>$value))->find();
    				$this->error("{$dp['depart_name']}没审核人员！请重新选择!!!");
    			}
    			// if($value){

    			// }
    		}
    		//判断是否有相同部门
    		if (count((array)$datas['shop']['audit']) != count(array_unique((array)$datas['shop']['audit']))) {    
			   $this->error("不允许有相同的部门！！");
			}
			$this->db->startTrans();
	    	$data['apply_time'] = $datas['shop']['apply_time']?strtotime($datas['shop']['apply_time']):time();
    		$data['remark'] = $datas['shop']['remark'];
    		$data['total_price'] = $datas['shop']['total_price'];
    		$data['create_id'] = $admin_info['id'];
    		$data['creater'] = $admin_info['username'];
    		$data['org_id'] = $_SESSION['admin']['info']['org_id'];
            $data['depart'] = $datas['shop']['depart']!=""?$datas['shop']['depart']:$_SESSION['admin']['info']['depart_id'];
    		$rs = M('shopping')->where(array('id'=>$datas['shop']['id']))->save($data);
    		$d_m = M('Message')->where(array('apply_id'=>$datas['shop']['id']))->delete();//删除消息表
    		$d_sa = M("shopping_audit")->where(array('shop_id'=>$datas['shop']['id']))->delete();//删除审核人表
    		$d_gs = M('shopping_goods')->where(array('shop_id'=>$datas['shop']['id']))->delete();//删除审核物品表
			foreach((array)$datas['shop']['audit'] as $k=>$v){
				$aData['shop_id'] = $datas['shop']['id'];
				$aData['audit_id'] = $v;
				//$aData['time'] = time();
				$aData['sort'] = $k+1;
				$rs_sa = M('shopping_audit')->data($aData)->add();

				if($aData['sort']==1){//给第一个审核人发信息
					//$adinfo =
					$send['apply_id'] = $datas['shop']['id'];
					$send['send_id'] = $_SESSION['admin']['info']['id'];
					$send['receive_id'] =  M('admin')->where(array('depart_id'=>$v))->getField('id');
					$send['title'] = "采购申请审核信息";
					$send['url'] = "/Admin/Purchase/audit/shop_id/".$datas['shop']['id'].'.html';
					$send['send_time'] = time();
                    $send['type'] = 10;
				    $rs_m = M('Message')->data($send)->add();
				}
			}
			if(isset($datas['shop'])){unset($datas['shop']);}
			foreach($datas as $key=>$value){
				if($value['assets_name']!=""){
					$sData['shop_id'] = $shop_id;
					$sData['goods_name'] = $value['assets_name'];
					$sData['goods_id'] = $value['goods_id'];
					$sData['spec'] = $value["spec"];
					$sData['num'] = $value['num'];
					$sData['bud_price'] = $value['bud_price'];
					$sData['bud_total'] = $value['bud_total'];
					$sData['remark'] = $value['remark'];
					$rs_sg = M('shopping_goods')->data($sData)->add();
				}
			}
			if($rs!=false && $d_m!=false && $d_sa!=false && $d_gs!=false && $rs_sa!=false && $rs_m!=false && $rs_sg!=false){
				$this->db->commit();
				$this->success("编辑成功！！",U('audit_list',array('status'=>1)));
			}else{
				$this->db->rollback();
				$this->error("编辑失败！！");
			}
    	}else{
            $org_id = I('get.org')?:0;
    		$sgw = "sg.goods_id=bg.id AND bg.type_id=t.id AND sg.shop_id='{$shop_id}'";

	    	$saw = "sa.audit_id=d.id AND a.depart_id=d.id AND sa.shop_id='{$shop_id}' AND is_auditer=1";

	    	$sglist = M('shopping_goods')->field('sg.*,bg.unit,bg.type_id,t.type_name')->table(array('k_shopping_goods'=>'sg','k_base_goods'=>'bg','k_type'=>'t'))->where($sgw)->select();

	    	$departs = M('shopping_audit')->field('sa.*,d.depart_name,d.id as d_id,a.username,a.name,a.signature')->table(array('k_shopping_audit'=>'sa','k_depart'=>'d','k_admin'=>'a'))->where($saw)->order('sa.sort')->select();
	    	$s_where = "s.depart=d.id AND s.id='$shop_id'";
            $info = M('shopping')->field("s.*,d.depart_name")->table(array("k_shopping"=>'s','k_depart'=>'d'))->where($s_where)->find();
            if($info['create_id']!=$_SESSION['admin']['info']['id']){
                $this->error("您没有编辑权限！！");
            }
	    	$info['sglists'] = $sglist;
	    	$info['departs'] = $departs;
            if($org_id){
                $info['depart'] = $org_id;
                $info['org_id'] = $org_id;
            }
	    	$is_auditer = false;
	    	$opera = 0;
	    	if($admin_info['is_auditer']==1){
	    		$is_auditer = true;
	    		//当前审核人
	    		$auditer = M('shopping_audit')->where(array('audit_id'=>$admin_info['depart_id'],'shop_id'=>$shop_id))->find();
	    		if(!empty($auditer)){
	    		//查找上一个审核人
	    			$mw = "sort<".$auditer['sort']." AND shop_id='{$shop_id}'";
		    		
		    		$maps = M('shopping_audit')->where($mw)->find();
		    		if(!empty($maps)){
		    			if($maps['status']==0){$opera = 1;}
		    			if($maps['status']==1){$opera = 1;}
		    		}
	    	    }
	    	}
	    	
	    	// //M('shopping_audit')->where(array('shop_id'=>$shop_id,''))->find();
            $c_id  = M('Depart')->where(array('pid'=>0))->getField('id');
	    	$cat =  CAT('Depart',array('id','pid','depart_name'),'pid = '.$c_id,'add_time asc'); //顶级部门
            $top_departs = $cat->getList($c_id);
            $this->assign('top_departs',$top_departs);

	    	$this->assign('info',$info);
	    	$this->assign('admin_info',$admin_info);
	    	$this->assign('status',$auditer['status']==""?1:$auditer['status']);//判断是否审核
            $this->assign('list_dep',$this->getDeparts($info['org_id']));

	    	$this->assign('opera',$opera);//是否有操作权限
	    	$this->display();
    	}
    }
    //采购申请
    public function audit(){
    	$admin_info = session('admin.info');
    	$shop_id = I('get.shop_id');
    	//改变消息状态
    	M('Message')->where(array('apply_id'=>$shop_id,'receive_id'=>$_SESSION['admin']['info']['id']))->setField(array('read_time'=>time(),'status'=>2));
    	
        if(IS_POST){
    		$datas = I('post.');
    		if($datas['status']==""){
                $this->error("请选择同意或不同意！！");
            }
            if($datas['remark']==""){
                $this->error("请填写原因说明！！");
            }
    		$sort = $datas['sort'];
    	//	print_r($datas);exit;
    		$data['status'] = $datas['status'];
    		$data['time'] = $datas['time']?strtotime($datas['time']):time();
    		$data['audit_name'] = $admin_info['name'];
    		$data['remark'] = $datas['remark'];
            $t_shop = M('shopping')->where(array('id'=>$datas['shop_id']))->find();
    		$rs = M('shopping_audit')->data($data)->where(array('audit_id'=>$datas['audit_id'],'shop_id'=>$datas['shop_id']))->save();
    		if($rs){
    			$where2= "shop_id='".$datas['shop_id']."' AND sort>'{$sort}'";
    			$maps2 = M('shopping_audit')->where($where2)->find();//下一个审核人
    			$sdata['status'] = 2;//审核中

    			
    			if(empty($maps2)){//只有一个审核人
    				if($datas['status']==1){
    					$sdata['status'] = 3;//未通过
                        //发送消息
                        $send['apply_id'] = $datas['shop_id'];
                        $send['send_id'] = $_SESSION['admin']['info']['id'];
                       // $send['receive_id'] = M('admin')->where(array('depart_id'=>$t_shop['depart']))->getField('id',true);
                        $send['receive_id'] = $t_shop['create_id'];
                        $send['title'] = "采购申请审核信息";
                        $send['url'] = "/Admin/Purchase/audit/shop_id/".$datas['shop_id'].'.html';
                        $send['send_time'] = time();
                        $send['type'] = 10;
                        M('Message')->data($send)->add();
    				}else{
    					$sdata['status'] = 4;//已通过
                        //发送消息
                        $send['apply_id'] = $datas['shop_id'];
                        $send['send_id'] = $_SESSION['admin']['info']['id'];
                        $send['receive_id'] = $t_shop['create_id'];
                        $send['title'] = "采购申请审核信息";
                        $send['url'] = "/Admin/Purchase/audit/shop_id/".$datas['shop_id'].'.html';
                        $send['send_time'] = time();
                        $send['type'] = 10;
                        M('Message')->data($send)->add();
    				}
    			}else{
                    if($datas['status']==1){
                        $sdata['status'] = 3;//未通过
                        //发送消息
                        $send['apply_id'] = $datas['shop_id'];
                        $send['send_id'] = $_SESSION['admin']['info']['id'];
                       // $send['receive_id'] = M('admin')->where(array('depart_id'=>$t_shop['depart']))->getField('id',true);
                        $send['receive_id'] = $t_shop['create_id'];
                        $send['title'] = "采购申请审核信息";
                        $send['url'] = "/Admin/Purchase/audit/shop_id/".$datas['shop_id'].'.html';
                        $send['send_time'] = time();
                        $send['type'] = 10;
                        M('Message')->data($send)->add();
                    }else{
        			//if($datas['status']!=1 && $datas['status']!=""){
    	    			//发送消息
                        $ad = M('admin')->where(array('depart_id'=>$maps2['audit_id']))->find();
    	    			$send['apply_id'] = $datas['shop_id'];
    					$send['send_id'] = $_SESSION['admin']['info']['id'];
    					$send['receive_id'] = $ad['id'];
    					$send['title'] = "采购申请审核信息";
    					$send['url'] = "/Admin/Purchase/audit/shop_id/".$datas['shop_id'].'.html';
    					$send['send_time'] = time();
                        $send['type'] = 10;
    					M('Message')->data($send)->add();
    				//}
                    }
                }
				//改变状态
    			$sdata['last_op_time'] = time();
    			M('shopping')->data($sdata)->where(array('id'=>$datas['shop_id']))->save();
    			$this->success("审核成功！",-1);
    		}else{
    			$this->error("审核失败！！");
    		}
    	}else{

	    	
	    	$sgw = "sg.goods_id=bg.id AND bg.type_id=t.id AND sg.shop_id='{$shop_id}'";

	    	$saw = "sa.audit_id=d.id AND a.depart_id=d.id AND sa.shop_id='{$shop_id}' AND is_auditer=1";

	    	$sglist = M('shopping_goods')->field('sg.*,bg.unit,bg.type_id,t.type_name')->table(array('k_shopping_goods'=>'sg','k_base_goods'=>'bg','k_type'=>'t'))->where($sgw)->select();

	    	$departs = M('shopping_audit')->field('sa.*,d.depart_name,d.id as d_id,a.username,a.name,a.signature')->table(array('k_shopping_audit'=>'sa','k_depart'=>'d','k_admin'=>'a'))->where($saw)->order('sa.sort')->select();
            $s_where = "s.depart=d.id AND s.id='$shop_id'";
	    	$info = M('shopping')->field("s.*,d.depart_name as depart")->table(array("k_shopping"=>'s','k_depart'=>'d'))->where($s_where)->find();
	    	$info['sglists'] = $sglist;
	    	$info['departs'] = $departs;

	    	$is_auditer = false;
	    	$opera = 0;
	    	if($admin_info['is_auditer']==1){
	    		$is_auditer = true;
	    		//当前审核人
	    		$auditer = M('shopping_audit')->where(array('audit_id'=>$admin_info['depart_id'],'shop_id'=>$shop_id))->find();
	    		if(!empty($auditer)){
	    		//查找上一个审核人
	    			$mw = "sort<".$auditer['sort']." AND shop_id='{$shop_id}'";
		    		
		    		$maps = M('shopping_audit')->where($mw)->find();
		    		if(!empty($maps)){
		    			if($maps['status']==0){$opera = 1;}
		    			if($maps['status']==1){$opera = 1;}
		    		}
	    	    }
	    	}
	    	
	    	// //M('shopping_audit')->where(array('shop_id'=>$shop_id,''))->find();
	    	
	    	$this->assign('info',$info);
	    	$this->assign('admin_info',$admin_info);
	    	$this->assign('status',$auditer['status']==""?1:$auditer['status']);//判断是否审核
	    	$this->assign('opera',$opera);//是否有操作权限
	    	$this->display();
    	}
    }

    //采购列表
    public function audit_list(){
    	$ws = I('get.');
    	$len=I('size')?:15;
    	//$key = I('get.key_word');
    	$order_sn = $ws['order_sn'];
    	$where = "s.depart=d.id";
    	if($order_sn!=''){
    		$where.=" AND s.order_sn LIKE '%{$order_sn}%'";
    	}
    	$creater = $ws['creater'];
    	if($depart!=''){
    		$where.=" AND s.creater LIKE '%{$creater}%'";
    	}
    	$depart = $ws['depart'];
    	if($depart!=''){
    		$where.=" AND s.depart='{$depart}'";
    	}
    	if($ws['apply_time']!=''){
    		$where.=" AND s.apply_time=".strtotime($ws['apply_time']);
    	}
    	$status = $ws['status'];
    	if($status){
    		$where.=" AND s.status='{$status}'";
    	}
    	if($status==4){
    		$where.=" or s.status=3";
    	}
    	if($_SESSION['admin']['info']['is_supper']!=1){
    		$where.=" AND s.org_id='".$_SESSION['admin']['info']['org_id']."'";
    	}
    	$count = M('shopping')->table(array('k_shopping'=>'s','k_depart'=>'d'))
                 ->where($where)->count();
    	$page = new \Think\PageA($count,$len);
    	$show = $page->show();

    	$lists = M('shopping')->field('s.*,d.depart_name as depart')
                 ->table(array('k_shopping'=>'s','k_depart'=>'d'))
                 ->where($where)->limit($page->firstRow.','.$len)
                 ->order('id desc')->select();
    	//print_r($_SESSION['admin']['info']['org_id']);exit;
    	$this->assign('lists',$lists);
    	$this->assign('page',$show);
    	$this->assign('order_sn',$order_sn);
    	$this->assign('creater',$creater);
    	$this->assign('depart',$depart);
    	$this->assign('apply_time',$ws['apply_time']);
    	$this->assign('ajaxlen',$len);
    	$this->assign('org_id',$_SESSION['admin']['info']['org_id']);
    	$this->assign('status',$status==''?'0':$status);
        //$this->assign('s_name',$s_name);
    	$this->display();
    }
    //移动端的审查历史
    public function ajaxaudit(){
    	$page=I('get.page');
        $len=I('get.size')?:15;
        //$key=I('get.key_word');
        $first=$len*($page-1);
        $where = "1=1";

        $lists = M('shopping')->where($where)->limit($first.','.$len)->order('id desc')->select();

        $this->ajaxReturn($lists);
    }
    //采购申请查看
    public function check(){
    	
    	$shop_id = I('get.shop_id');
    	$sgw = "sg.goods_id=bg.id AND bg.type_id=t.id AND sg.shop_id='{$shop_id}'";

    	$saw = "sa.audit_id=d.id AND a.depart_id=d.id AND sa.shop_id='{$shop_id}' AND is_auditer=1";

    	$sglist = M('shopping_goods')->field('sg.*,bg.unit,bg.type_id,t.type_name')->table(array('k_shopping_goods'=>'sg','k_base_goods'=>'bg','k_type'=>'t'))->where($sgw)->select();

    	$departs = M('shopping_audit')->field('sa.*,d.depart_name,d.id as d_id,a.username,a.name,a.signature')->table(array('k_shopping_audit'=>'sa','k_depart'=>'d','k_admin'=>'a'))->where($saw)->select();
    	$info = M('shopping')->where(array('id'=>$shop_id))->find();
    	$info['sglists'] = $sglist;
    	$info['departs'] = $departs;
    	
    	$this->assign('info',$info);
    	$this->assign('admin_info',$admin_info);
    	$this->display();
    }

        //导出数据
    public function export()
    {
    	$ws = I('get.');
        $len = I('size')?:50;
        $page = I('page');
        $key = I('key');
        $first = $len*($page-1);
    	$order_sn = $ws['order_sn'];
    	$where = "s.id=sg.shop_id AND s.depart=d.id AND s.status=4";
    	if($order_sn!=''){
    		$where.=" AND s.order_sn LIKE '%{$order_sn}%'";
    	}
    	$gname = $ws['assets_name'];
    	if($gname!=''){
    		$where.=" AND sg.goods_name LIKE '%{$gname}%'";
    	}
    	$depart = $ws['depart'];
    	if($depart!=0){
    		$where.=" AND s.depart LIKE '%{$depart}%'";
    	}
    	if($ws['str_time']!='' && $ws['end_time']!=''){
    		$where.=" AND s.apply_time between".strtotime($ws['str_time'])." and ".strtotime($ws['end_time']);
    	}
        if($_SESSION['admin']['info']['org_id'] !="" && $_SESSION['admin']['info']['org_id'] !=0){
            $where.=" AND s.org_id='".$_SESSION['admin']['info']['org_id']."'";
        }
      //  $where = "s.id=sg.shop_id";
        $field = "s.*,sg.goods_name,sg.spec,sg.num,sg.bud_total,sg.remark,d.depart_name as depart";
    	$lists = M('shopping')->field($field)->table(array('k_shopping'=>'s','k_shopping_goods'=>'sg','k_depart'=>'d'))->where($where)->limit($first.','.$len)->group('sg.shop_id')->select();
        foreach($lists as $k=>$v)
        {
        	$lists[$k]['id'] = ($page-1)*$len+$key+1;
            $lists[$k]['apply_time'] = date('Y-m-d',$v['apply_time']);
        }
        //echo "<pre>";
       // dump($lists);exit;
        return $lists;
    }
    //所有部门
    private function getDeparts($topid=0)
    {
       // print_r($topid);exit;
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