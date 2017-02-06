<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-25 13:44:13
// +----------------------------------------------------------------------

/**
 * 数组转换成字符转ArrayToString
 * @param  array  $c 数组
 * @return string
 */
function A2S($c)
{
    $str='';
    foreach ($c as $k=>$v)
    {
        $str.='"'.$k.'"=>';
        $str=is_array($v)?$str.'array('.A2S($v).')':$str.'"'.$v.'"';
        $str.=',';
    }
    return $str;
}

/**
 * 获取类别组织
 * @param string $m [模型]
 * @param array  $f [字段列表]
 * @param string $c [过滤条件]
 * @param string $o [排序方式]
 */
function CAT($m='',$f=array(),$c='',$o='')
{
	vendor('Category');
	return new \Qing\Category($m,$f,$c,$o);
}

/**
 * 检测客户端是否手机
 */
function isMobile()
{
    static $mobilebrowser_list='Mobile|iPhone|Android|WAP|NetFront|JAVA|OperasMini|UCWEB|WindowssCE|Symbian|Series|webOS|SonyEricsson|Sony|BlackBerry|Cellphone|dopod|Nokia|samsung|PalmSource|Xphone|Xda|Smartphone|PIEPlus|MEIZU|MIDP|CLDC';
    return !preg_match("/$mobilebrowser_list/i",$_SERVER['HTTP_USER_AGENT'])?
    (!preg_match('/(mozilla|chrome|safari|opera|m3gate|winwap|openwave)/i',$_SERVER['HTTP_USER_AGENT'])?($_GET['mobile']==='yes'?true:false):false):true;
}

/**
 * /
 * @param  [string] $p [文件夹路径]
 * @param  string $t [文件后缀名,*表示所有文件]
 * @param  string $f [文件名格式,*表示所有文件名]
 * @return [array]
 */
function listfile($p,$t='*',$f='*')
{
	$p=rtrim(str_replace('/',DIR,$p),DIR).DIR;
    $t=$t?:'*';$f=$f?:'*';$list=array();$f=str_replace('*','.*?',$f);
    $r=opendir($p);
    while($d=readdir($r))
    {
        if($d=='.'||$d=='..'||strpos($d,'.')<1){continue;}
        $fs=explode('.',$d);$name=$fs[0];$ext=$fs[1];
        if($t!='*'&&$t!=$ext){continue;}
        if(preg_match('/^'.$f.'$/',$name)){array_push($list,$d);}
    }
    closedir($r);
    return $list;
}

/**
 * CURL请求
 * @param  string  $url 请求地址
 * @param  array   $data 请求参数
 * @param  string  $method 请求类型----post|get
 * @param  string  $verify 请求验证
 * @return array   请求结果----错误信息或者数据
 */
function curl($url,$data,$method='POST',$verify=false)
{
    $ch=curl_init();//初始化CURL句柄 
    curl_setopt($ch,CURLOPT_URL, $url); //设置请求的URL
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出 
    curl_setopt($ch,CURLOPT_CUSTOMREQUEST,$method); //设置请求方式
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,$verify); // https请求 不验证证书和hosts
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,$verify);
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data)); //设置提交的字符串
    curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type:application/json'));
    $list=curl_exec($ch); //执行预定义的CURL
    $err=curl_errno($ch);
    $res=$err?array('status'=>0,'info'=>curl_error($ch)):array('status'=>1,'info'=>$list);
    curl_close($ch);
    return $res;
}

/**
 * 发送短信息
 * @param  [string] $phone [手机号码]
 * @param  [string] $value [发送内容]
 * @return [bool]
 */
function sendSMSMsg($phone,$value)
{
    $send['Account']=C('MSG.msg_account');
    $send['Password']=C('MSG.msg_pass');
    $send['Mobiles']=$phone;
    $send['Content']=$value;
    return CURL(C('MSG.msg_url'),$send);
}

/**
 * 发送邮件Email
 * @param  string $to 目标用户
 * @param  mixed  $title 标题
 * @param  mixed  $content 内容
 * @return bool
 */
function EM($to,$title,$content)
{
    vendor('PHPMailer');
    $em=new PHPMailer();
    $em->IsSMTP();
    $em->Host=C('MSG.email_url');
    $em->SMTPAuth=true;
    $em->Username=C('MSG.email_account');
    $em->Password=C('MSG.email_pass');
    $em->From=C('MSG.emial_account');
    $em->FromName='';
    $em->CharSet='utf-8';
    $em->Encoding='base64';
    $em->IsHTML(true);
    $em->AddReplyTo(C('MSG.email_account'),'');
    $em->WordWrap=50;
    $em->AddAddress($to);
    $em->Subject=$title;
    $em->Body=$content;
    $em->AltBody=$content;
    return $em->Send();
}

/**
 * 导出excel
 * @param  string $name 文件名称
 * @param  array  $data 导出内容，二维数组
 * @param  array  $title 标题，数组
 * @return bool   成功失败
 */
function Exporter($name,$data,$title=null)
{
	vendor('PHPExcel');
    if(!$name||!is_string($name)||!$data||!is_array($data)){return false;}
    $ex=new PHPExcel();$file=$name.'_'.date('_YmdHis');$cn=array();
    foreach(array('','A') as $c){foreach(range('A','Z') as $v){array_push($cn,$c.$v);}}
    $t=$title?count($title)-1:count($data[0])-1;
    $ex->getActiveSheet(0)->mergeCells('A1:'.$cn[$t].'1');
    $ex->setActiveSheetIndex(0)->setCellValue('A1',$name);
    $ex->getActiveSheet()->getStyle('A1:'.$cn[count($data[0])-1].'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//居中
    $font=$ex->getActiveSheet()->getStyle('A1')->getFont();
    $font->setSize('20')->setBold(true);//20号字加粗
    if(is_array($title))
    {
        $tits=array_keys($title);array_unshift($data,$title);sort_fields($data,$tits);$tn=count($tits);
        for($i=0;$i<count($data);$i++){for($j=0;$j<$tn;$j++){$k=$tits[$j];$v=$data[$i][$k];$ex->setActiveSheetIndex(0)->setCellValue($cn[$j].($i+2),$v);if(!$i){$ex->getActiveSheet()->getStyle($cn[$j].($i+2))->getFont()->setBold(true);}}}
    }else
    {
        for($i=0;$i<count($data);$i++){foreach($vv as $v){$ex->getActiveSheet(0)->setCellValue($cn[$n].($i+2),$v);$n++;}}
    }
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$name.'.xls"');
    header("Content-Disposition:attachment;filename=$file.xls");
    $obj = PHPExcel_IOFactory::createWriter($ex,'Excel5');$obj->save('php://output');
    return true;
}

/**
 * 导入excel
 * @param  string $file     文件导入 错误信息
 * @param  array  $field    对应字段名
 * @param  bool   $out      排除行数
 * @return array  导入数据  三维(status,data)
 */
function Importer($file,$field=false,$out=0)
{
	vendor('PHPExcel');$ex=new PHPExcel();
    if(!file_exists($file)){return array("status"=>0,'data'=>'文件不存在！');}
    $od=PHPExcel_IOFactory::createReader('Excel5');$rd=$od->load($file);
    if(!isset($rd)){return array("status"=>0,'data'=>'文件读取错误！');}
    $sht=$rd->getAllSheets();$i=0;$data=array();
    foreach($sht as $s)
    {
        $sn=$s->getTitle();$rs=$s->getHighestRow();$cs=PHPExcel_Cell::columnIndexFromString($s->getHighestColumn());$arr=array();$ms=array();
        foreach($s->getMergeCells() as $c){foreach(PHPExcel_Cell::extractAllCellReferencesInRange($c) as $cr){$ms[$cr]=true;}}
        $num=$out?(intval($out)?:0):0;$num++;
        for($j=$num;$j<=$rs;$j++)
        { 
            $row=array();
            $cs=min($cs,count($field));
            for($k=0;$k<$cs;$k++)
            {
                $cell=$s->getCellByColumnAndRow($k,$j);$afCol=PHPExcel_Cell::stringFromColumnIndex($k+1);$bfCol=PHPExcel_Cell::stringFromColumnIndex($k-1);$col=PHPExcel_Cell::stringFromColumnIndex($k);$ad=$col.$j;$val=$s->getCell($ad)->getValue();
                if(substr($val,0,1)=='='){return array("status"=>0,'data'=>'数据中包含特定数据！');}
                if($cell->getDataType()==PHPExcel_Cell_DataType::TYPE_NUMERIC){
                    $fm=$cell->getStyle($cell->getCoordinate())->getNumberFormat();
                    $fc=$fm->getFormatCode();
                    $val=preg_match('/^([$[A-Z]*-[0-9A-F]*])*[hmsdy]/i',$fc)?gmdate("Y-m-d",PHPExcel_Shared_Date::ExcelToPHP($val)):PHPExcel_Style_NumberFormat::toFormattedString($val,$fc);
                }
                $key=$field?$field[$k]:$k;
                if($ms[$col.$j]&&$ms[$afCol.$j]&&!empty($val)){$temp=$val;}elseif($ms[$col.$j]&&$ms[$col.($j-1)]&&empty($val)){$val=$arr[$j-1][$key];}elseif($ms[$col.$j]&&$ms[$bfCol.$j]&&empty($val)){$val=$temp;}
                $row[$key]=$val?:($val===0?0:($val==='0'?'0':''));
            }
            $arr[$j]=$row;$data[]=$row;
        }
    }
    unset($sht);unset($s);unset($od);unset($rd);return array("status"=>1,"data"=>$data);
}

/**
 * 获取验证码类
 */
function verify($id=''){vendor('Verify');$v=new Verify();$v->entry($id);}
/**
 * 验证验证码
 */
function vercheck($v,$id=''){vendor('Verify');$o=new Verify();$o->check($v,$id);}

/**
 * 获取客户端ip
 * return string 客户端ip
 */
function IP()
{
    $ip = false;
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
        for ($i = 0; $i < count($ips); $i++) {
            if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

/**
 * 按照给定字段排序
 * @param  [array] &$data [二维数据]
 * @param  [arrar] $field [排序顺序数组]
 * @return [bool]
 */
function sort_fields(&$data,$field)
{
    if(!$data||!$field||!is_array($data)||!is_array($field)){return false;}$arr=array();
    foreach($data as $vv){$a=array();foreach($field as $v){if($vv[$v]){$a[$v]=$vv[$v];}}array_push($arr,$a);}
    $data=$arr;
    return true;
}

/**
 * 给二维数组排序
 * @param  [array] &$data [待排序数据]
 * @param  string $fs    [排序字段]
 * @return [bool]
 */
function sort_by(&$data,$fs='')
{
    if(!$data||!is_array($data)||!$fs){return false;}
    $GLOBALS['field']=array();$fs=preg_replace('/\s+/',' ',$fs);$fs=explode(',',$fs);
    foreach($fs as $su){$s=explode(' ',$su);$s=count($s)>1?$s:array($s[0],'asc');array_push($GLOBALS['field'],$s);}
    usort($data,function($a,$b){foreach($GLOBALS['field'] as $v){if($a[$v[0]]==$b[$v[0]]){continue;}return (($v[1]=='desc')?-1:1)*(($a[$v[0]]<$b[$v[0]])?-1:1);}return 0;});
}

/**
 * 给二维数组去重
 * @param  [array] &$data [待去重数据]
 * @param  string $fs    [去重字段]
 * @return [bool]
 */
function unique_by(&$data,$fs='')
{
    if(!$data||!is_array($data)||!$fs){return false;}
    $fields=explode(',',$fs);
    foreach($fields as $f)
    {
        $arr=array();$f=trim($f);
        $ds=array_unique(array_field($data,$f));
        foreach($data as $k=>$v)
        {
            if(in_array($v[$f],$arr)){unset($data[$k]);continue;}
            if(in_array($v[$f],$ds)){array_push($arr,$v[$f]);}
        }
    }
}

/**
 * 取二维数组中一列
 * @param  [array] $arr [二维数据]
 * @param  [string] $f   [列名]
 * @return [array]      [选定列以为数组]
 */
function array_field($arr,$f)
{
    $brr=array();
    if(!is_array($arr)||!is_string($f)){return false;}
    foreach($arr as $k=>$v){array_push($brr,$v[$f]);}
    return count($brr)>0?$brr:false;
}

/**
 * 判断编码是否UTF-8
 * @param  [string] $str [待检测字符串]
 * @return [bool]      [是否UTF-8编码]
 */
function isutf8($str)
{    
    return preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$str)|| preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$str)|| preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$str);
}
/**
 * 获取字符串编码
 * @param  [string] $str [待测字符串]
 * @return [string]      [utf-8或者gbk]
 */
function codetype($str){return isutf8($str)?'utf-8':'gbk';}
/**
 * 转换编码
 * @param  [string] $from [原来编码]
 * @param  [string] $to   [转换编码]
 * @param  [string] $str  [待转字符串]
 * @return [string]       [转换后字符串]
 */
function iconvstr($from,$to,$str)
{
    if(is_string($str))
    {
        if(function_exists('mb_convert_encoding')){
            return mb_convert_encoding($str,$to,$from);
        } else if(function_exists('iconv')){
            return iconv($from,$to,$str);
        } else {
            return $str;
        }
    }
}
/**
 * UTF-8编码字符串转换为GBK编码
 * @param  [string] $str [待转字符串]
 * @return [string]      [转换后字符串]
 */
function utf82gbk($str)
{
    if(is_array($str))
    {
        foreach($str as $k=>$v){$str[$k]=utf82gbk($v);}
        return $str;
    }else
    {
        return iconv("UTF-8", "GB2312//IGNORE", $str);
        return codetype($str)=='gbk'?$str:iconvstr('utf-8','gbk',$str);
    }
}
/**
 * GBK编码字符串转换为UTF-8编码
 * @param  [string] $str [待转字符串]
 * @return [string]      [转换后字符串]
 */
function gbk2utf8($str)
{
    if(is_array($str))
    {
        foreach($str as $k=>$v){$str[$k]=gbk2utf8($v);}
        return $str;
    }else
    {
        
        return iconv("GB2312", "UTF-8//IGNORE", $str);
        return codetype($str)=='gbk'?iconvstr('gbk','utf-8',$str):$str;
    }
}
/**
 * 中英混合字符串长度获取
 * @param  [string] $str [待测字符串]
 * @return [int]      [字符串的字位长度]
 */
function zhlen($str)
{
    $strlen=strlen($str);$cind=0;$reallen=0;
    if(isutf8($str))
    {
        while($cind<$strlen){if(ord(substr($str,$cind,1))>127){$cind+=3;}else{$cind++;}$reallen++;}
    }else
    {
        while($cind<$strlen){if(ord(substr($str,$cind,1))>127){$cind+=2;}else{$cind++;}$reallen++;}
    }
    return $reallen;
}
/**
 * 中英混合字符串截取
 * @param  [string]  $str   [待截取字符串]
 * @param  [int]  $start [开始位置,从0开始,默认0]
 * @param  integer $len   [截取长度，默认一个字位]
 * @param  boolean $ls    [是否需要用省略号代替，默认不用]
 * @return [string]         [截取后字符串]
 */
function zhsubstr($str,$start=0,$len=1,$ls=false)
{
    $start=abs(intval($start));$len=abs(intval($len));
    $strlen=strlen($str);$sind=0;$slen=0;$cind=0;$reallen=0;
    if(isutf8($str))
    {
        if($start>0){while($sind<$strlen){$s=ord(substr($str,$sind,1))>127?3:1;$sind+=$s;$slen++;if($slen==$start){break;}}}
        if($len>0){while($cind<$strlen){$s=ord(substr($str,$cind,1))>127?3:1;$cind+=$s;$reallen++;if($reallen==$len+$slen){break;}}}
    }else
    {
        if($start>0){while($sind<$strlen){$s=ord(substr($str,$sind,1))>127?2:1;$sind+=$s;$slen++;if($slen==$start){break;}}}
        if($len>0){while($cind<$strlen){$s=ord(substr($str,$cind,1))>127?2:1;$cind+=$s;$reallen++;if($reallen==$len+$slen){break;}}}
    }
    $mylen=abs($len)>0?abs($cind-$sind):$strlen;$rstr=substr($str,$sind,$mylen);
    return $ls?$rstr.'……':$rstr;
}
/**
 * 中英混合字符串切分字位,固定长度切分
 * @param  [string]  $str [待切字符串]
 * @param  integer $len [切分长度]
 * @return [array]       [切分后的字符串数组]
 */
function zhplit($str,$len=1)
{
    $arr=array();$total=zhlen($str);$num=0;
    while($num<$total){array_push($arr,zhsubstr($str,$num,$len));$num+=$len;}
    return $arr;
}
/**
 * 可逆的加密
 * @param  [string] $txt [待加密字符串]
 * @param  string $key [加密匙]
 * @return [string]      [加密后的字符串]
 */
function enpass($txt,$key='ronshn')
{
    srand((double)microtime()*1000000);$c=0;$t='';$k=md5(rand(0,32000));
    for($i=0;$i<strlen($txt);$i++){$c=$c==strlen($k)?0:$c;$t.=$k[$c].($txt[$i]^$k[$c++]);}
    $str=base64_encode(passkey($t,$key));
    return urlencode($str);
}
/**
 * 解密通过enpass加密的字符串
 * @param  [string] $txt [待解密字符串]
 * @param  string $key [解密匙]
 * @return [string]      [解密后的字符串]
 */
function depass($txt,$key='ronshn')
{
    $t='';$txt=passkey(base64_decode($txt),$key);
    for($i=0;$i<strlen($txt);$i++){$t.=$txt[$i]^$txt[++$i];}
    return $t;
}
/**
 * 加密
 * @param  [string] $txt [待加密字符串]
 * @param  [string] $key [加密匙]
 * @return [string]      [加密后的字符串]
 */
function passkey($txt,$key)
{
    $c=0;$t='';$key=md5($key);
    for($i=0;$i<strlen($txt);$i++){$c=$c==strlen($key)?0:$c;$t.=$txt[$i]^$key[$c++];}
    return $t;
}

/**
 * 创建订单编号
 * @param  [string] $prefix [编号前缀 如果没有前缀则不生成订单号]
 * @return [string]         [订单号]
 */
function createOrderSn($prefix)
{
    if ($prefix) {
        return $prefix.date('Ymd').str_pad(mt_rand(1, 9999999),7,'0',STR_PAD_LEFT);
    }else{
        return '';
    }
}
/**
 * 系统非常规MD5加密方法
 * @param string $str
 * 要加密的字符串
 * @return string
 */
function encrypt_md5($str,$key='')
{
    empty($key)&&$key=C( 'DATA_AUTH_KEY' );
    return ''=== $str?'':md5(sha1($str).$key);
}

/**
 * 添加日志
 * @param int       $type 日志类型
 * @param string    $desc 日志描述
 * @return bool
 */
function adminlog($type=1,$desc='')
{
    $my=session('admin');$admin=$my['info'];
    $action=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
    $parm=I();if($type==1){unset($parm['password']);}$parm=json_encode($parm);
    $data['admin_id']=$admin['id'];$data['log_name']=$admin['name'];
    $data['log_action']=$action;$data['log_parm']=$parm;
    $data['log_type']=$type;$data['log_desc']=$desc;
    $data['log_ip']=IP();$data['log_time']=time();
    return M('AdminLog')->data($data)->add();
}
/** 
* 折旧公用方法
* @param array $datas 参数
* @return array 
*/
function deprs($datas){
   // print_r($datas);exit;
    $year = date('Y',strtotime($datas['start_date']));//开始使用时间
    $month = date('m',strtotime($datas['start_date']));
    $day = date('d',strtotime($datas['start_date']));
  //  print_r($year);exit;
    $newyear = date('Y',time());//当前时间
    $newmonth = date('m',time());//当前时间
    $usedyear = $newyear-$year;//开始使用已使用时间
    $useyear = $datas['service_life']-$usedyear;//可使用年限
    if($month<5){
        $useyear+=1;
    }
    $yearnum = 0;//使用年限的年数总和
    for($i=$datas['service_life'];$i>0;$i--){
        $yearnum+=$i;
        
    }
    $data['sueM'] = $usedyear*12+($newmonth-$month);
    if(time()>strtotime(($year+$datas['service_life']).'-'.$month.'-'.$day)){
        $data['sueM'] = $datas['service_life']*12;
    }
    if($datas['depr']==1){//年限折旧法
        $data['net_residual_value'] = round($datas['original_value']*$datas['net_salvage']/100,2);//净残值=原值*残值率
        $data['mon_depr_rate'] = round((1-$datas['net_salvage']/100)/$datas['service_life']/12*100,2);
        //月折旧率=（1-残值率）/ 使用年限/12
        $data['mon_depr_amount'] = round($data['mon_depr_rate']/100*$datas['original_value'],2);
        //月折旧额=月折旧率*原值
        $data['mon_depr_sum'] = round($data['mon_depr_amount']*$data['sueM'],3);
        //累计折旧=月折旧额*计提时间
        $data['net_worth'] = $datas['original_value']-$data['mon_depr_sum'];
        //净值=原值-累计折旧
        $data['k_net_worth'] = $datas['original_value']-$data['net_residual_value']-$data['mon_depr_sum'];
        //剩余可提折旧=原值-净残值-累计折旧
        if($data['sueM']/12 == $datas['service_life']){
            $data['mon_depr_sum'] = $datas['original_value']-$data['net_residual_value'];//累计折旧
            $data['net_worth'] = $data['net_residual_value'];//净值
            $data['k_net_worth'] = 0;//剩余可提折旧
        }
        
    }elseif ($datas['depr']==2) {//工作量法
        $data['net_residual_value'] = round($datas['original_value']*$datas['net_salvage']/100,2);//净残值=原值*残值率

        $data['mon_depr_amount'] = $datas["month_works"]*($datas['original_value']*(1-$datas['net_salvage']/100)/($datas['service_life']));//月折旧额=月工作量×（原值×(1-净残值率)/总工作量）

        $data['mon_depr_rate'] = round($data['mon_depr_amount']/$datas['original_value'],2)*100;

        //月折旧率=月折旧额/原值
        
        $data['mon_depr_sum'] = round($data['mon_depr_amount']*$data['sueM'],2);
        //累计折旧=月折旧额*计提时间

        $data['net_worth'] = $datas['original_value']-$data['mon_depr_sum'];
        //净值=原值-累计折旧
        
        $data['k_net_worth'] = $datas['original_value']-$data['net_residual_value']-$data['mon_depr_sum'];
        //剩余可提折旧=原值-净残值-累计折旧
        if($data['sueM']/12 == $datas['service_life']){
            $data['mon_depr_sum'] = $datas['original_value']-$data['net_residual_value'];//累计折旧
            $data['net_worth'] = $data['net_residual_value'];//净值
            $data['k_net_worth'] = 0;//剩余可提折旧
        }

    }elseif ($datas['depr']==3){//双倍余额递减法

        $data['net_residual_value'] = round($datas['original_value']*$datas['net_salvage']/100,2);//净残值=原值*残值率

         $be_use = $datas['service_life'];//可使用年限
        // $be_used = intval($datas['mon_total']/12);//累计使用年限
        // $month_used = intval($datas['mon_total']%12);//累计使用年限余月份
        $numderp = 0;//累计折旧额
        $data['mon_depr_rate'] = round(2/$be_use/12*100,2);//折旧率
        $numdepr = intval($data['sueM']/12);//累计使用年限
        $loadmonth = intval($data['sueM']%12);//剩余的月数
        

        //print_r($month_used);exit;
        //月折旧额=2/预计使用年限/12*原值 第二年：月折旧额=第一年净值*月折旧率
        if($be_use<3){//只有两或者的年用年限法
            $data['mon_depr_amount'] = round((1-$datas['net_salvage']/100)/$datas['service_life']/12*100,2)*100;
            $data['mon_depr_sum'] = $data['mon_depr_amount']*$data['sueM'];

        }else{
            for($j=0;$j<$numdepr;$j++){
                
                if($datas['service_life']-$numdepr<3 && $loadmonth==0){
                    $data['mon_depr_amount'] = round((1-$datas['net_salvage']/100)/$datas['service_life']/12*100,2)*100;
                    $data['mon_depr_sum'] = $data['mon_depr_amount']*$data['sueM'];
                }else{
                    if($j<1){
                        //第一年：月折旧额=原值*月折旧率
                        $data['mon_depr_amount'] = round($datas['original_value']*$data['mon_depr_rate']/100,2);
                        //第一年的净值
                        if($numdepr>1){
                            $data['mon_depr_sum'] = $data['mon_depr_amount']*12;
                        }
                        $data['net_worth'] =  $datas['original_value']-$data['mon_depr_sum'];//净值
                        
                    }elseif ($j>0 && $j<2){
                        $data['mon_depr_amount'] = round($data['mon_depr_rate']*$data['net_worth']/100,2);
                        $data['mon_depr_sum'] = $data['mon_depr_amount']*$data['sueM'];
                        $data['net_worth'] =  $datas['original_value']-$data['mon_depr_sum'];//净值
                    }else{
                    
                    }
                    
                }
            }
        }
        $data['k_net_worth'] = $datas['original_value']-$data['net_residual_value']-$data['mon_depr_sum'];//剩余可提折旧=原值-净残值-累计折旧

        $data['net_worth'] =  $datas['original_value']-$data['mon_depr_sum'];//净值
        
    }else {//年数总和法
        $data['net_residual_value'] = round($datas['original_value']*$datas['net_salvage']/100,3);//净残值=原值*残值率

        // $year = date('Y',strtotime($datas['start_date']));//开始使用时间
        // $month = date('m',strtotime($datas['start_date']));
        // $newyear = date('Y',time());//当前时间
        // //$newmonth = date('m',time());//当前时间
        // $usedyear = $newyear-$year;//开始使用已使用时间
        // $useyear = $datas['service_life']-$usedyear;//可使用年限
        // if($month<5){
        //  $useyear+=1;
        // }
        // $yearnum = 0;//使用年限的年数总和
        // for($i=$datas['service_life'];$i>0;$i--){
        //  $yearnum+=$i;
            
        // }
        $numdepr = intval($data['sueM']/12);//累计使用年限
        $loadmonth = intval($data['sueM']%12);//剩余的月数
        //print_r($numdepr);exit;
        $totaldepr = 0;
        for($i=$numdepr;$i>0;$i--){
            $totaldepr+=round(($datas['original_value']-$data['net_residual_value'])*($i/$yearnum/12),2)*12;//总折旧额
        }
        //print_r($useyear);exit;
        $totaldepr+=round(($datas['original_value']-$data['net_residual_value'])*($i/$yearnum/12),2)*$loadmonth;
        $data['mon_depr_rate'] = round($useyear/$yearnum/12*100,2);
        //月折旧率=尚可使用年限/使用年限的年数总和/12
        $data['mon_depr_amount'] = round(($datas['original_value']-$data['net_residual_value'])*$data['mon_depr_rate']/100);
        //月折旧额=(原值-净残值)×月折旧率
        
        $data['mon_depr_sum'] = round($totaldepr);
        //累计折旧=月折旧额*计提时间
        $data['net_worth'] = $datas['original_value']-$data['mon_depr_sum'];
        //净值=原值-累计折旧
        $data['k_net_worth'] = round($datas['original_value']-$data['net_residual_value']-$data['mon_depr_sum']);
        
        //剩余可提折旧=原值-净残值-累计折旧

    }
return $data;
}

/**
 * 时间格式化 传入时间空则返回空
 * @param  [type] $d [description]
 * @param  string $f [description]
 * @return [type]    [description]
 */
function dateFormat($d,$f='Y-m-d')
{
    $return = '';
    if ($d) {
        $return=date($f,$d);
    }
    return $return;
}
?>