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
class StockInvController extends BaseController
{
    public function _initialize(){
      parent::_initialize();
      $this->db = M('Stock'); //当前模块数据库
      $this->db_sd = M('Stock_detial');
      $this->db_bg =M('Base_goods'); 

      //年份查询 取出库存最小年份

      $min_year =     date('Y',$this->db_sd->getField("MIN(time)")) ;  

   
      //获取当前年份 
      $years = array();
      $max_year = date('Y',mktime(0,0,0,date("m",time()),date("d",time()),date("Y",time())));
      for ($i=(int)$min_year; $i <=(int)$max_year ; $i++) { 
          $years[]= $i; //年份 select
      }
      $this->assign('years',$years);


      $this->is_supper = $_SESSION['admin']['info']['is_supper']; //是否是超级管理员 可以查看添加所有数据
      
      $this->org_id    = $_SESSION['admin']['info']['org_id'];  //部门ID 顶级
 
      $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');

      $lists = $cat->getList(0);

      $this->departs = $this->is_supper ? $lists : $cat->getChildren($this->org_id,$lists);
    
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

      //dump(I('get.'));exit;
        $assets_name = I('assets_name'); 
        $year = I('year');
        $jd = I('jd');
        $month = I('month');
        $type_id=I('type_id');
        $cate_id=I('cate_id');
        $keyword =I('keyword');
        $d_id = I('org_id');
        $len = 10;

        $table ='k_stock s,k_stock_detial sd,k_base_goods bg,k_type t,k_category cg'; 

        $field="bg.assets_name,bg.unit,s.spec,s.id,
        SUM(IF(sd.func='+',sd.goods_num,0)) AS in_num, 
        SUM(IF(sd.func='+',sd.total,0)) AS in_total,
        SUM(IF(sd.func='-',sd.goods_num,0)) AS out_num,
        SUM(IF(sd.func='-',sd.total,0)) AS out_total,
        ( SUM(IF(sd.func='+',sd.goods_num,0)) - SUM(IF(sd.func='-',sd.goods_num,0)) ) AS diff_num,
        ( SUM(IF(sd.func='+',sd.total,0)) - SUM(IF(sd.func='-',sd.total,0)) ) AS diff_total
        ";

        $where ="sd.stock_id = s.id and s.goods_id = bg.id and bg.type_id=t.id and bg.cate_id=cg.id";

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and s.org_id = ({$this->org_id})";
        }

        if (!empty($assets_name)) {
          $where .=" and bg.assets_name like '%{$assets_name}%'";
        }

        if (!empty($year) && empty($jd) && empty($month) ) {
          $where .= " and FROM_UNIXTIME(sd.time, '%Y') = '{$year}'";
        }

        if (!empty($year) && !empty($jd) ) {

            switch ($jd) {
              case 1:
                 $s_time = strtotime($year.'-1');
                 $e_time = strtotime($year.'-7');
        
                break;

              case 2:
                 $s_time = strtotime($year.'-7');
                 $e_time = strtotime($year.'-12-31 23:59:59');
               
                break;  

              case 3:
                 $s_time = strtotime($year.'-1');
                 $e_time = strtotime($year.'-4');
                 
                break;

              case 4:
                 $s_time = strtotime($year.'-4');
                 $e_time = strtotime($year.'-7');
                 
                break;

              case 5:
                 $s_time = strtotime($year.'-7');
                 $e_time = strtotime($year.'-10');
                 
                break;

              case 6:
                 $s_time = strtotime($year.'-10');
                 $e_time = strtotime($year.'-12-31 23:59:59');
                 
                break; 

              
              default:
                # code...
                break;
            }

             $where .=" and sd.time between {$s_time} and {$e_time}";
        }


         if (!empty($year) && !empty($month) ) {

               $time = $year.'-'.$month;

               $where .= " and FROM_UNIXTIME(sd.time, '%Y-%m') = '{$time}'";
        }

         if ( !empty($keyword) ) {
             $where .= " and (bg.assets_name like '%{$keyword}%' OR sd.order_sn like '%{$keyword}%' )";
         }
       
         if($cate_id){$where.=" and cg.id='$cate_id'";}
         if($type_id){$where.=" and t.id='$type_id'";}
         if($d_id){
          $where.=" and s.org_id='$d_id'";
         }
        
        $count          =  $this->db->table($table)->where($where)->count('DISTINCT bg.assets_name,s.spec');

        foreach ($count as $key => $val) {
          $count = $key;
        }

        // dump($count);


        $page           = new \Think\PageA($count,$len);
        $show           = $page->show();


         $lists =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($page->firstRow.','.$page->listRows)
        ->where($where)
        ->group('assets_name,spec')
        ->order('sd.time DESC')
        ->select();

        // echo $this->db->getLastSql();

        //统计

        $lists_all =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($page->firstRow.','.$page->listRows)
        ->where($where)
        ->group('assets_name,spec')
        ->order('sd.time DESC')
        ->select();
       
        $in_num_arr =array(); //入库数量
        $in_total_arr =array();//入库金额

        $out_num_arr =array(); //入库数量
        $out_total_arr =array();//入库金额

        foreach ($lists_all as $key => $val) {
            $in_num_arr[] = $val['in_num'];
            $in_total_arr[] =$val['in_total'];

            $out_num_arr[] = $val['out_num'];
            $out_total_arr[] =$val['out_total'];
        }
         
        $c['in_n'] = array_sum($in_num_arr);
        $c['in_t'] = array_sum($in_total_arr);
        $c['ot_n'] = array_sum($out_num_arr);
        $c['ot_t'] = array_sum($out_total_arr);
        $c['d_n'] =  array_sum($in_num_arr) - array_sum($out_num_arr);
        $c['d_t'] =  array_sum($in_total_arr) - array_sum($out_total_arr);;
        // dump($c);

        

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
        $this->assign('year',$year);
        $this->assign('jd',$jd);
        $this->assign('month',$month);
      
         
        $this->assign('c',$c);//统计

         //手机端
        $this->assign('types',$this->getType());
        $this->assign('type',$type_id);
        $this->assign('cates',$this->getCate());
        $this->assign('cate_id',$cate_id);
        $this->assign('keyword',$keyword);
        $this->assign('ajaxlen',$len);
        $this->display();
    }

    public function ajaxSv()
    {
        $page=I('get.page');
        $len=I('get.size')?:1;
        $keyword=I('get.keyword');
        $first=$len*($page-1);

        // dump($key);exit();

        $table ='k_stock s,k_stock_detial sd,k_base_goods bg'; 

        $field="bg.assets_name,bg.unit,s.spec,s.id,
        SUM(IF(sd.func='+',sd.goods_num,0)) AS in_num, 
        SUM(IF(sd.func='+',sd.total,0)) AS in_total,
        SUM(IF(sd.func='-',sd.goods_num,0)) AS out_num,
        SUM(IF(sd.func='-',sd.total,0)) AS out_total,
        ( SUM(IF(sd.func='+',sd.goods_num,0)) - SUM(IF(sd.func='-',sd.goods_num,0)) ) AS diff_num,
        ( SUM(IF(sd.func='+',sd.total,0)) - SUM(IF(sd.func='-',sd.total,0)) ) AS diff_total
        ";

        $where ="sd.stock_id = s.id and s.goods_id = bg.id";

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and s.org_id = ({$this->org_id})";
        }

        if ( !empty($keyword) ) {
             $where .= " and (bg.assets_name like '%{$keyword}%' OR sd.order_sn like '%{$keyword}%' )";
         }


          $lists =  $this->db
        ->table($table)
        ->field($field)      
        ->limit($first,$len)
        ->where($where)
        ->group('assets_name,spec')
        ->order('sd.time DESC')
        ->select();
        // echo $this->db->getLastSql();

        $this->ajaxReturn($lists); 
    }

   

     public function export(){//导出Excel
        $xlsName  = "库存盘点";
        $xlsCell  = array(
            array('id','序号'),
            array('assets_name','物品名称'),
            array('spec','规格型号'),
            array('unit','单位'),
            array('in_num','数量'),
            array('in_ave','平均值'),
            array('in_total','金额'),
            array('out_num','数量'),
            array('out_ave','平均值'),
            array('out_total','金额'),
             array('diff_num','数量'),
             array('diff_ave','平均值'),
            array('diff_total','金额'),
            // array('htime','上班时间'),
            // array('xtime','下班时间'),
            // array('leave_reason','异常类型'),
            // array('confirm','确认'),
        );
        // $xls_title = array(
        //   'A2'=> array('A2:A3','序号'),
        //   'B2'=> array('B2:B3','学号'),
        //   'C2'=> array('C2:C3','班级'),
        //   'D2'=> array('D2:D3','姓名'),
        //    $num[$endEX+1].'2' =>array( $num[$endEX+1].'2:'.$num[$endEX+1].'3' ,'课时统计'),
        //    $num[$endEX+2].'2' =>array( $num[$endEX+2].'2:'.$num[$endEX+2].'3' ,'是否合格'),
        //  ); 



         $assets_name = I('assets_name');
        $year = I('year');
        $jd = I('jd');
        $month = I('month');


        $table ='k_stock s,k_stock_detial sd,k_base_goods bg'; 

        $field="bg.assets_name,bg.unit,s.spec,s.id,
        SUM(IF(sd.func='+',sd.goods_num,0)) AS in_num, 
        SUM(IF(sd.func='+',sd.total,0)) AS in_total,
        SUM(IF(sd.func='-',sd.goods_num,0)) AS out_num,
        SUM(IF(sd.func='-',sd.total,0)) AS out_total,
        ( SUM(IF(sd.func='+',sd.goods_num,0)) - SUM(IF(sd.func='-',sd.goods_num,0)) ) AS diff_num,
        ( SUM(IF(sd.func='+',sd.total,0)) - SUM(IF(sd.func='-',sd.total,0)) ) AS diff_total
        ";

        $where ="sd.stock_id = s.id and s.goods_id = bg.id";

        if ( !$this->is_supper ) { //不是超级管理员
          $where .=" and s.org_id = ({$this->org_id})";
        }

        if (!empty($assets_name)) {
          $where .=" and bg.assets_name like '%{$assets_name}%'";
        }

        if (!empty($year) && empty($jd) && empty($month) ) {
          $where .= " and FROM_UNIXTIME(sd.time, '%Y') = '{$year}'";
        }

        if (!empty($year) && !empty($jd) ) {

            switch ($jd) {
              case 1:
                 $s_time = strtotime($year.'-1');
                 $e_time = strtotime($year.'-7');
        
                break;

              case 2:
                 $s_time = strtotime($year.'-7');
                 $e_time = strtotime($year.'-12-31 23:59:59');
               
                break;  

              case 3:
                 $s_time = strtotime($year.'-1');
                 $e_time = strtotime($year.'-4');
                 
                break;

              case 4:
                 $s_time = strtotime($year.'-4');
                 $e_time = strtotime($year.'-7');
                 
                break;

              case 5:
                 $s_time = strtotime($year.'-7');
                 $e_time = strtotime($year.'-10');
                 
                break;

              case 6:
                 $s_time = strtotime($year.'-10');
                 $e_time = strtotime($year.'-12-31 23:59:59');
                 
                break; 

              
              default:
                # code...
                break;
            }

             $where .=" and sd.time between {$s_time} and {$e_time}";
        }


         if (!empty($year) && !empty($month) ) {

               $time = $year.'-'.$month;

               $where .= " and FROM_UNIXTIME(sd.time, '%Y-%m') = '{$time}'";
        }
       

         $xlsData =  $this->db
        ->table($table)
        ->field($field)      
        // ->limit($page->firstRow.','.$page->listRows)
        ->where($where)
        ->group('assets_name,spec')
        ->order('sd.time DESC')
        ->select();

          //统计  
        $in_num_arr =array(); //入库数量
        $in_total_arr =array();//入库金额

        $out_num_arr =array(); //入库数量
        $out_total_arr =array();//入库金额

        foreach ($xlsData  as $key => $val) {
            $in_num_arr[] = $val['in_num'];
            $in_total_arr[] =$val['in_total'];

            $out_num_arr[] = $val['out_num'];
            $out_total_arr[] =$val['out_total'];
            $xlsData[$key]['in_ave'] = round($val['in_total']/$val['in_num'],2);
            $xlsData[$key]['out_ave'] = round($val['out_total']/$val['out_num'],2);
            $xlsData[$key]['diff_ave'] = round($val['diff_total']/$val['diff_num'],2);
        }
         
        $c['in_n'] = array_sum($in_num_arr);
        $c['in_t'] = array_sum($in_total_arr);
        $c['ot_n'] = array_sum($out_num_arr);
        $c['ot_t'] = array_sum($out_total_arr);
        $c['d_n'] =  array_sum($in_num_arr) - array_sum($out_num_arr);
        $c['d_t'] =  array_sum($in_total_arr) - array_sum($out_total_arr);;

        // dump($xlsData);exit();

        $this->exportExcel($xlsName,$xlsCell,$xlsData,$c);
    }


     public function exportExcel($expTitle,$expCellName,$expTableData,$c){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $expTitle;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $today = date('Y.m.d',time());
        vendor("PHPExcel");

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        foreach ($cellName as $key => $val) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($val)->setWidth(18);//单元格设置宽度
        }
        $styleThinBlackBorderOutline = array(
            'borders' => array (
                'outline' => array (
                    'style' => \PHPExcel_Style_Border::BORDER_THIN, //设置border样式 'color' => array ('argb' => 'FF000000'), //设置border颜色
                ),
            ),);
     

        // $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(50);//单元格设置宽度
        // echo 'A2:'.$cellName[$cellNum-1].'2';exit();
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:M2');//合并单元格
      
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '库存盘点');
       

      
  

        $objPHPExcel->getActiveSheet()->getStyle('A:M')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平居中
        // $objPHPExcel->getActiveSheet()->getStyle('A:J')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中

        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(14);//字体大小
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);//字体加粗
        $objPHPExcel->getActiveSheet()->getStyle('A3:M3')->getFont()->setBold(true);//字体加粗



         $objPHPExcel->getActiveSheet(0)->mergeCells('E3:G3');//
         $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3','入库');

         $objPHPExcel->getActiveSheet(0)->mergeCells('H3:J3');//
         $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H3','出库');

         $objPHPExcel->getActiveSheet(0)->mergeCells('K3:M3');//
         $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K3','库存');

         for($i=0;$i<$cellNum;$i++){

          if ($i < 4) {
            $objPHPExcel->getActiveSheet(0)->mergeCells($cellName[$i].'3'.':'.$cellName[$i].'4');//
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'3', $expCellName[$i][1]);
          }else{
            // $objPHPExcel->getActiveSheet(0)->mergeCells($cellName[$i+1].'3'.':'.$cellName[$i+2].'3');//
           
            // E3:F3  F3:G3
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'4', $expCellName[$i][1]);
          }
            
            
                     
        } 
       
        for($i=0;$i<$dataNum;$i++){
          for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+5), $expTableData[$i][$expCellName[$j][0]]);
          }             
        }  
        // echo 'A'.($i+7).':'.$cellName[$cellNum-1].($i+7);
        // dump($i);exit();
    
         //最后单元格设置  
          // $objPHPExcel->getActiveSheet(0)->mergeCells('A'.($i+7).':'.$cellName[$cellNum-1].($i+7));//合并单元格

        
          $objPHPExcel->getActiveSheet(0)->mergeCells('A'.($i+7).':'.'B'.($i+8));//合并单元格
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($i+7),'入库合计');
         
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($i+7),'统计数量');
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($i+8),'统计金额');

          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.($i+7),$c['in_n']);
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.($i+8),$c['in_t']);



          $objPHPExcel->getActiveSheet(0)->mergeCells('E'.($i+7).':'.'F'.($i+8));//合并单元格
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.($i+7),'出库合计');
         
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.($i+7),'统计数量');
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.($i+8),'统计金额');

          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.($i+7),$c['ot_n']);
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.($i+8),$c['ot_t']);




          $objPHPExcel->getActiveSheet(0)->mergeCells('J'.($i+7).':'.'K'.($i+8));//合并单元格
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J'.($i+7),'出入库差额合计');
         
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L'.($i+7),'统计数量');
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L'.($i+8),'统计金额');

          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M'.($i+7),$c['d_n']);
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M'.($i+8),$c['d_t']);


          // $objPHPExcel->getActiveSheet()->getStyle('A'.($i+4).':'.$cellName[$cellNum-1].($i+5))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //居中
          // $objPHPExcel->getActiveSheet()->getStyle('A'.($i+4).':'.$cellName[$cellNum-1].($i+5))->getFont()->setSize(11);
          // $objPHPExcel->getActiveSheet()->getStyle('A'.($i+4).':'.$cellName[$cellNum-1].($i+5))->getFont()->setBold(true);

        // dump($c);exit();
     
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
?>