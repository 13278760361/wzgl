<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: mr.king (277429358@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-28 20:44:13
// +----------------------------------------------------------------------
/**
 * 低值品：盘点、折旧、月结 控制器
 */
namespace Admin\Controller;
use Think\Controller;

class LowCalculateController extends BaseController 
{
	private $c;
	private $mPage = 10;//手机端分页请求每页大小
	private $page  = 10;//列表页分页大小；
	private $applyPage = 10;//申请单物品选择框物品条数
	public function _initialize()
	{
		parent::_initialize();
		$this->c = D("Calculate");
       	//$Depart = new \Org\Util\Category('Depart',array('id','pid','depart_name'));
       	//$this->departs = $Depart->getList('',0,'add_time asc');
		$this->departs = $this->c->getDepartByOrg();
       	$this->assign('departs',$this->departs);
	   	//分页数量统一
	    $this->mPage = C('wapsize')?C('wapsize'):$this->mPage; 
	    $this->page  = C('pagesize')?C('pagesize'):$this->page; 
	    $this->applyPage = C('seachsize')?C('seachsize'):$this->applyPage; 
	}
	
    public function index()
    {
    	//$this->display();
		echo "hello world!";
    }

	/*低值品盘点*/
	public function ass()
	{
		$assCondition = $this->assCondition();
		$condition = $assCondition['condition'];
		$status    = $assCondition['status'];
		$p = I('get.p')?I('get.p'):1;
		$page = $this->page;
		if($this->is_mobile()) $page = $this->mPage;
		$condition['g.type_id'] = 2;//低值品
		if($status) $list = $this->c->ass_status($condition,$status,$page);//账面处理
		else $list = $this->c->ass($condition,$page);
		//$calculate = D("Calculate");
		//print_r($list['list']);
		$this->assign('tdk',array('title'=>"低值品盘点",'description'=>"",'keyword'=>""));
		$this->assign("dateRange",$this->c->assDate());
		$this->assign('list',$list['list']);
		$this->assign('page',$list['page']);
		$this->assign('current_total',$list['current_total']);
		$this->assign('index',$page*($p-1));//序号算法
		//手机端：关键词
		$this->assign("assets_name",I('assets_name'));
		$this->display();
	}
	
	/*低值品盘点：手机端*/
	public function assApi()
	{
		$assCondition = $this->assCondition();
		$condition = $assCondition['condition'];
		$condition['g.type_id'] = 2;//低值品
		$list = $this->c->ass($condition,$this->mPage);
		$this->ajaxReturn($list); 
	}
	
	/*盘点条件*/
	private function assCondition() 
	{
		$status = 0;
		$condition = array();
		$pData = I();
		if($pData){
			//$pData = I('post.');
			$status = $pData['status'];//账面
			if($pData['assets_name']){
				$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");				
			}
			if($pData['department'])
			$condition['c.department'] = array('EQ',$pData['department']);
			/*if($pData['inputer'])
			$condition['c.inputer'] = array('LIKE',"%".$pData['inputer']."%");
			if($pData['input_time'])
			$condition['c.input_time'] = array(array('EGT',strtotime($pData['input_time'].' 00:00:00')),
											   array('ELT',strtotime($pData['input_time'].' 23:59:59')));	*/	
										   
			//时间筛选 字段：kd.time
			//年
			$year = $pData['year']?$pData['year']:date("Y",time());
			//季度
			$season = $pData['season'];
			if($season) {
				switch($season) {
					case 1://上半年
					case 2://下半年
						$seasonCondition = $this->c->assDateYear($year,$season);
					break;
					case 3://一季度
					case 4://二季度
					case 5://三季度
					case 6://四季度
						switch($season) {
						 case 3:
							 $seasonIndex = 1;
						 break;
						 case 4:
							 $seasonIndex = 4;
						 break;
						 case 5:
							 $seasonIndex = 7;
						 break;
						 case 6:
							 $seasonIndex = 10;
						 break;
						}
						$seasonCondition = $this->c->assDateSeason($year,$seasonIndex);
					break;
				}
			}
			//月份
			$mon = $pData['mon'];
			if($mon){
				$bMonth = mktime(0,0,0,$mon,1,$year);//开始
				$eMonth = mktime(23,59,59,$mon,date('t'),$year);//结束
				$monCondition = array(array('EGT',$bMonth),array('ELT',$eMonth));
			}
			//set condition
			if(!$seasonCondition&&!$monCondition) {
				$condition['kd.time'] = $this->c->assDateYear($year);
			}else{
				if($seasonCondition&&$monCondition){
					$condition['kd.time'] = $seasonCondition;
				}else if($seasonCondition){
					$condition['kd.time'] = $seasonCondition;
				}else {
					$condition['kd.time'] = $monCondition;
				}
			}
			//$pData['mon'] = "";							   							   	
			$condition['query'] = array('assets_name'=>$pData['assets_name'],'department'=>$pData['department'],'year'=>$year,'mon'=>$mon,'season'=>$season);		   
		}
		return array('status'=>$status,'condition'=>$condition);
	}

	/**折旧**/
    public function depr()
    {
		$condition = array();
		$pData = I();
		if($pData){
			if($pData['department'])
			$condition['c.department'] = array('EQ',$pData['department']);
			$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");
			$condition['query'] = array('department'=>$pData['department'],'assets_name'=>$pData['assets_name']);		  
		}
		$p = I('get.p')?I('get.p'):1;
		$condition['g.type_id'] = 2;//低值品
		$list = $this->c->depr($condition,$this->page);
		//$department = $this->c->getDepartment();
		$p_total = 0;$p_num=0;//分页统计
        $s_total = 0;$s_num=0;//总计

        foreach ($list['list'] as $key => $value) {
        	# code...
        	$p_total+=$value['mon_depr_sum'];
        	$p_num+=$value['total'];
        }
        $s_list = $this->c->depr($condition,0);
        foreach ($s_list['list'] as $k => $v) {
        	# code...
        	$s_total+=$v['mon_depr_sum'];
        	$s_num+=$v['total'];
        }
        $this->assign('p_total',$p_total);
		$this->assign('s_total',$s_total);
		$this->assign('p_num',$p_num);
		$this->assign('s_num',$s_num);
		$this->assign('tdk',array('title'=>"低值品折旧",'description'=>"",'keyword'=>""));
		//$this->assign('department',$department);
		$this->assign('list',$list['list']);
		$this->assign('page',$list['page']);
		$this->assign('index',$page*($p-1));//序号算法
		//手机端：关键词
		$this->assign("assets_name",I('assets_name'));
		$this->display();
    }
	
	/**低值品折旧：手机端**/
    public function deprApi()
    {
		$condition = array();
		$pData = I();
		if($pData){
			if($pData['department'])
			$condition['c.department'] = array('EQ',$pData['department']);
			$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");		  
		}
		$condition['g.type_id'] = 2;//低值品
		$list = $this->c->depr($condition,$this->mPage);
		$this->ajaxReturn($list['list']);
    }
	/**月结**/
    public function mon($orgid=0)
    {
		$p = I('get.p')?I('get.p'):1;
		$condition = array();
		$pData = I();
		if($pData) {
			$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");
		}
		if($orgid){
			$condition['c.org_id'] = $orgid;
		}
		$list = $this->c->mon($condition,$this->page);
		$this->assign('tdk',array('title'=>"低值品月结",'description'=>"",'keyword'=>""));
		$this->assign('list',$list['list']);
		$this->assign('last_all_total',$list['last_all_total']);
		$this->assign('current_all_total',$list['current_all_total']);
		$this->assign('monStatus',$list['monStatus']);
		$this->assign('page',$list['page']);
		$this->assign('index',$this->page*($p-1));//序号算法
		$this->assign('orgid',$orgid);
		//手机端：关键词
		$this->assign("assets_name",I('assets_name'));
		$this->display();
    }
	
	/**低值品月结：手机端**/
    public function monApi($orgid=0)
    {
		$condition = array();
		$pData = I();
		if($pData) {
			$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");
		}
		if($orgid){
			$condition['c.org_id'] = $orgid;
		}
		$list = $this->c->mon($condition,$this->mPage);
		$this->ajaxReturn($list['list']);
    }
	
	/*月结事件*/
	public function monA($type=0){
		//月结1 反月结2 
		if(!in_array($type,array(1,2))){
			$this->error("非法操作",U('LowCalculate/mon'));
		}else{
			$status = $this->c->monA($type);
			if($status[0]==1) $this->success($status[1],U('LowCalculate/mon'));
			else  $this->error($status[1],U('LowCalculate/mon'));
		}
	}
	
	/*月结总管理*/
	public function monmanage(){
		$condition['org_id'] = array('NEQ','');
		if(IS_POST) {
    		$pData = I('post.');
			$status = ($pData['status']==1)?0:1;
			$condition['id'] = $pData['id'];
			//type:1月结、2反月结
			$updMonManage = $this->c->updMonManage(array('status'=>$status),$condition);
			if($updMonManage)$this->success("操作成功！",U('LowCalculate/monmanage'));
			else $this->success("操作失败！",U('LowCalculate/monmanage'));
		}else {
			$p = I('get.p')?I('get.p'):1;
			$this->assign('index',$this->page*($p-1));//序号算法
			$this->assign('tdk',array('title'=>"低值品月结总管理",'description'=>"",'keyword'=>""));
			$monManage = $this->c->monManage($condition);
			$this->assign('list',$monManage);
			//monStatus
			$this->display();
		}
	}
	
	//导出功能
	public function export($type,$orgid=0) 
	{
		$name = "报表";//名称
		$data = array();//data
		//$f    = array();//field
		switch($type) {
			case "ass"://盘点
				$name = "低值品盘点报表";
				//data
				$assCondition = $this->assCondition();
				$condition = $assCondition['condition'];
				$status    = $assCondition['status'];
				if($status) $list = $this->c->ass_status($condition,$status);//账面处理
				else $list = $this->c->ass($condition);
				$getData = $list['list'];
				$current_total = $list['current_total'];
				//头部输出
				header("Content-type:application/vnd.ms-excel");
				header("Content-Disposition:filename=".$name.date("Ymdhis",time()).".xls");
				$excel =  
					'<table border=1 >
					 <thead>
					 <tr><td colspan="18" align="center" style="font-size:18px;">'.$name.'</td></tr>
					  <tr>
						<th rowspan="2">资产代码</th>
						<th rowspan="2">物品名称</th>
						<th rowspan="2">规格型号</th>
						<th rowspan="2">部门</th>
						<th rowspan="2">使用时间</th>
						<th rowspan="2">购置年限</th>
						<th rowspan="2">单位</th>
						<th colspan="2">入库</th>
						<th colspan="2">出库转资产</th>
						<th colspan="2">增值税</th>
						<th colspan="2">其中：报废报损</th>
						<th colspan="2">账面结存</th>
						<th rowspan="2">盘点</th>
					  </tr>
					  <tr>
						<th>数量</th>
						<th>金额</th>
						<th>数量</th>
						<th>金额</th>
						<th>税率</th>
						<th>税额</th>
						<th>数量</th>
						<th>金额</th>
						<th>数量</th>
						<th>金额</th>
					  </tr>
					</thead>
					<tbody>';
				
				$excelContent = array();
				 foreach($getData as $k=>$vo)
				 {
					$excelContent[] =
						"<tr>
						  <td>".$vo['sn']."</td>
						  <td>".$vo['name']."</td>
						  <td>".$vo['spec']."</td>
						  <td>".$vo['department']."</td>
						  <td>".date("Y-m-d h:i:s",$vo['start_date'])."</td>
						  <td>".$vo['service_life']."</td>
						  <td>".$vo['unit']."</td>
						  <td>".$vo['inStock_amount']."</td>
						  <td>".$vo['inStock_totalPrice']."</td>
						  <td>".$vo['outStock_amount']."</td>
						  <td>".$vo['outStock_totalPrice']."</td>
						  <td>".$vo['vat_rate']."</td>
						  <td>".$vo['taxval']."</td>
						  <td>".$vo['scrap_num']."</td>
						  <td>".$vo['scrap_total']."</td>
						  <td>".$vo['account_num']."</td>
						  <td>".$vo['account_total']."</td>
						  <td>".($vo['is_ventory']==1?"已盘点":"未盘点")."
						  </td>
						</tr>";
 
				 }
				 $excel.= join("",$excelContent);
				 $excel.= 
					'<tr>
					<td colspan="5" class="bgc-5">合计</td>
					<td colspan="13">'.$current_total.'</td>
					</tr>
					</tbody>
					</table>';
				echo $excel;
				exit;			
				
			break;
			case "depr"://折旧
				$name = "低值品折旧报表";
				$condition = array();
				$pData = I();
				if($pData){
					if($pData['department'])
					$condition['c.department'] = array('EQ',$pData['department']);
					$condition['g.assets_name'] = array('LIKE',"%".$pData['assets_name']."%");
					$condition['query'] = array('department'=>$pData['department'],'assets_name'=>$pData['assets_name']);		  
				}
				$p = I('get.p')?I('get.p'):1;
				$condition['g.type_id'] = 2;//低值品
				$list = $this->c->depr($condition);
				$data = $list['list'];
				$heads = array(
					'catname'=>"资产分类",'name'=>"物品名称",
					'spec'=>"规格型号",'department'=>"部门",'unit'=>"单位",
					'total'=>"数量",'original_value'=>"原值",'start_date'=>"使用时间",
					'service_life'=>"使用年限",'net_salvage'=>"净残率",'net_residual_value'=>"净残值",
					'mon_depr_rate'=>"月折旧率",'mon_depr_amount'=>"月折旧额",
					'sueM'=>"计提时间",'mon_depr_sum'=>"累计折旧",
					'net_worth'=>"净值",'k_net_worth'=>"剩余可提折旧"
				);
				$f = array();
				$head = array();
				foreach($heads as $k=>$v) {
				 $f[] = $k;
				 $head[] = $v;
				}
				//data
				if($data) {
					$nData = array();
					foreach($data as $k=>$v) {
						$one = array();
						foreach($f as $kk=>$vv){
							if($vv=='start_date')
							$one[] = date("Y-m-d h:i:s",$v[$vv]);
							else $one[] = $v[$vv];
							
						}
						$nData[]= $one;
						//$nData[] = $one;
					}
					$data = $nData;
				}
			break;
			case "mon"://月结
				$name = "低值品月结报表";
				if($orgid){
					$condition['c.org_id'] = $orgid;
				}
				$list = $this->c->mon($condition);
				$getData = $list['list'];
				$last_all_total = $list['last_all_total'];
				$current_all_total = $list['current_all_total'];
				$monStatus = $list['monStatus'];
				//头部输出
				header("Content-type:application/vnd.ms-excel");
				header("Content-Disposition:filename=".$name.date("Ymdhis",time()).".xls");
				$excel =  
				'<table border=1 >
				 <thead>
				 <tr><td colspan="17" align="center" style="font-size:18px;">'.$name.'</td></tr>
				  <tr>
				  
				  <th rowspan="2">资产分类</th>
				  <th rowspan="2">物品名称</th>
				  <th rowspan="2">规格型号</th>
				  <th colspan="2">期初结存</th>
				  <th colspan="2">本期入库</th>
				  <th colspan="2">增值税</th>
				  <th colspan="2">本期出库</th>
				  <th colspan="2">本期报废报损</th>
				  <th colspan="2">本期维修</th>
				  <th colspan="2">本期结存</th>
				</tr>
				<tr style="background: #F6F6F6;">
				  <th>数量</th>
				  <th>金额</th>
				  <th>数量</th>
				  <th>金额</th>
				  <th>数量</th>
				  <th>金额</th>
				  <th>数量</th>
				  <th>金额</th>
				  <th>数量</th>
				  <th>金额</th>
				  <th>数量</th>
				  <th>金额</th>
				  <th>数量</th>
				  <th>金额</th>
				</tr>
			  </thead>
			  <tbody>
			  ';
				$excelContent = array();
				foreach($getData as $k=>$vo) {
				  $excelContent[] = 
				  '<tr>
					<td>'.$vo['catname'].'</td>
					<td>'.$vo['name'].'</td>
					<td>'.$vo['spec'].'</td>
					<td>'.$vo['last_num'].'</td>
					<td>'.$vo['last_total'].'</td>
					<td>'.$vo['ent_num'].'</td>
					<td>'.$vo['ent_total'].'</td>
					<td>'.$vo['total'].'</td>
					<td>'.$vo['totaltaxval'].'</td>
					<td>'.$vo['out_num'].'</td>
					<td>'.$vo['out_total'].'</td>
					<td>'.$vo['scrap_num'].'</td>
					<td>'.$vo['acrap_total'].'</td>
					<td>'.$vo['repair_num'].'</td>
					<td>'.$vo['repair_total'].'</td>
					<td>'.$vo['current_num'].'</td>
					<td>'.$vo['current_total'].'</td>
				  </tr>';
				}
				$excel.=join("",$excelContent);
				$excel.='
				<tr>
					  <td colspan="3" class="bgc-2">上期结存合计</td>
					  <td colspan="4">'.$last_all_total.'</td>
					  <td colspan="3" class="bgc-2">本期结存合计</td>
					  <td colspan="7">'.$current_all_total.'</td>
					</tr>
				  </tbody>
				</table>';
				echo $excel;
				exit;	
			break;
			
		}
		Exporter($name,$data,$head);
	}
	/*检测手机设备*/
	private function is_mobile() {
	  $user_agent = $_SERVER ['HTTP_USER_AGENT'];
	  $mobile_browser = Array (
		  "mqqbrowser",  
		  "opera mobi",  
		  "juc",
		  "iuc",  
		  "fennec",
		  "ios",
		  "applewebKit/420",
		  "applewebkit/525",
		  "applewebkit/532",
		  "ipad",
		  "iphone",
		  "ipaq",
		  "ipod",
		  "iemobile",
		  "windows ce",  
		  "240×320",
		  "480×640",
		  "acer",
		  "android",
		  "anywhereyougo.com",
		  "asus",
		  "audio",
		  "blackberry",
		  "blazer",
		  "coolpad",
		  "dopod",
		  "etouch",
		  "hitachi",
		  "htc",
		  "huawei",
		  "jbrowser",
		  "lenovo",
		  "lg",
		  "lg-",
		  "lge-",
		  "lge",
		  "mobi",
		  "moto",
		  "nokia",
		  "phone",
		  "samsung",
		  "sony",
		  "symbian",
		  "tablet",
		  "tianyu",
		  "wap",
		  "xda",
		  "xde",
		  "zte"
	  );
	  $is_mobile = false;
	  foreach ( $mobile_browser as $device ) {
		if (stristr ( $user_agent, $device )) {
		  $is_mobile = true;
		  break;
		}
	  }
	  return $is_mobile;
	}

}