<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-17 15:07:51
// +----------------------------------------------------------------------
namespace Qing;
/**
 * 
 */
class Dumper
{
    private $mdir='';//目录切分符
	private $m_dump='';//备份文件内容
    private $m_num=0;//备份文件计量
	private $db=null;//数据库模型
	private $vol=1;//当前分卷号
	private $voler='';//分卷号文件
	private $taber='';//备份列表文件
	private $path='';//备份文件目录
	private $name='';//当前处理文件名
	private $size=0;//单卷最大字节
	private $len=300;//查询数据长度
	private $short=true;//短语句标示
    private $author='QY';//备份作者
    //构造
    public function __construct()
    {
        $this->mdir=DIRECTORY_SEPARATOR;
    	$this->db=M();
    	$this->name=date('YmdHis',time());
    	$this->size=10*1024*1024;
    	$this->path=RUNTIME_PATH.'Data/backup/';
    	$this->voler=$this->path.'voler.log';
    	$this->taber=$this->path.'taber.log';
    	if(!is_dir($this->path)){mkdir($this->path);chmod($this->path,0777);}
    }

    //设置要备份的表
    public function tables($tbs)
    {
    	$tbs=(array)$tbs;$arr=array();
    	foreach($tbs as $tb){$arr[$tb]=-1;}
    	$this->settab($arr);
    	return $this;
    }

    //获取所有备份文件
    public function backfiles()
    {
        return $this->getfiles($this->path,'sql');
    }

    //获取文件信息
    public function backinfo($n)
    {
        $fs=$this->getfiles($this->path,'sql',$n);
        if($fs)
        {
            $arr=array();
            foreach($fs as $f){array_push($arr,$this->gethead($this->path.$f));}
            sort_by($arr,'vol');
            $info=$arr[count($arr)-1];
        }else{$info=false;}
        return $info;
    }

    //删除备份文件
    public function delfile($n)
    {
        $fs=$this->getfiles($this->path,'sql',$n);$res=true;
        if($fs){foreach($fs as $f){if(false===$res=@unlink($f)){break;}}}
        return $res?true:false;
    }

    //设置
    public function option($arr)
    {
    	$arr=(array)$arr;
    	foreach($arr as $k=>$v)
    	{
    		switch($k)
    		{
    			case 'path':$this->path=$v;break;
    			case 'name':$this->name=$v;break;
    			case 'size':$this->size=$v;break;
    			case 'author':$this->author=$v;break;
    			case 'short':$this->short=$v;break;
    			case 'len':$this->len=$v;break;
    		}
    	}
    	return $this;
    }

    //备份表
    public function dump()
    {
    	$vol=$this->getvol();$this->vol=$vol>1?$vol:$this->vol;$tables=$this->gettab();
        if($tables===false){return false;}if(empty($tables)){return $tables;}
        $this->m_dump=$this->sethead($this->vol);
        foreach($tables as $table=>$pos)
        {
            if($pos==-1)
            {
                $info=$this->getdef($table,true);
                if(strlen($this->m_dump)+strlen($info)>$this->size-32){
                    if($this->m_num==0){
                        $this->m_dump.=$info;$this->m_num+=2;$tables[$table]=0;
                    }
                    break;
                }else{
                    $this->m_dump.=$info;$this->m_num+=2;$pos = 0;
                }
            }
            $pps=$this->getdata($table,$pos);
            if($pps==-1){unset($tables[$table]);}else{$tables[$table]=$pps;break;}
        }
        $this->m_dump.='--END QYPHP Dumper';
        $this->save();$this->settab($tables);
        return $this->checkover()?1:-1;
    }

    //还原表
    public function recover($file,$im=false)
    {
        if(!$im)
        {
            $tables=$this->gettab();
            if($tables===false)
            {
                $file=$this->path.$file.'.sql';$minfo=$this->gethead($file);
                $name=$minfo['name'];$files=$this->backfiles($this->path,'sql');$arr=array();$tables=array();
                if($files)
                {
                    foreach($files as $f)
                    {
                        $info=$this->gethead($this->path.$f);if($info['name']==$name)
                        {array_push($arr,array('vol'=>$info['vol'],'file'=>$this->path.$f));}
                    }
                    sort_by($arr,'vol asc');
                }else{array_push($arr,array('vol'=>1,'file'=>$file));}
                foreach($arr as $v){$tables[$v['vol']]=$v['file'];}
                $this->settab($tables);
            }
            if(empty($tables)){$this->delvol();$this->deltab();return true;}
            $vol=$this->getvol();$this->vol=$vol>1?$vol:$this->vol;
            $file=$tables[$this->vol];
        }
    	$arr=array_filter(file($file),'self::comment');
    	$str=str_replace("\r",'',implode('',$arr));
    	$ret=explode(";\n",$str);$count=count($ret);
		for($i=0;$i<$count;$i++)
        {
            $ret[$i]=trim($ret[$i]," \r\n;");
            if(!empty($ret[$i])){
            	if((strpos($ret[$i],'CREATE TABLE')!==false)&&(strpos($ret[$i],'ENGINE')===false))
            	{$ret[$i]=$ret[$i].' ENGINE=InnoDB ';}
                if((strpos($ret[$i],'CREATE TABLE')!==false)&&(strpos($ret[$i],'DEFAULT CHARSET')===false))
                {$ret[$i]=$ret[$i].' DEFAULT CHARSET=utf8 ';}
                $res=$this->db->execute($ret[$i]);
                if($res===false){break;}
            }
        }
        if(!$im)
        {
            unset($tables[$this->vol]);$this->settab($tables);
            return $this->checkover()?1:-1;
        }else
        {
            return $res!==false?true:false;
        }
    }

    //获取文件头
    public function gethead($file)
    {
        $info=array('date'=>'','name'=>'','from'=>'','php_ver'=>0,'qy_ver'=>'','author'=>'','vol'=>0);
        $fp=fopen($file,'rb');$str=fread($fp,250);fclose($fp);$arr=explode("\n",$str);
        foreach($arr AS $val)
        {
            $pos=strpos($val,':');if($pos<=0){continue;}
            $type=trim(substr($val,0,$pos),"-\n\r\t ");
            $value=trim(substr($val,$pos+1),"/\n\r\t ");
            switch($type)
            {
            	case 'DATE':$info['date']=$value;break;
            	case 'NAME':$info['name']=$value;break;
            	case 'FROM':$info['from']=$value;break;
            	case 'PHP VERSION':$info['php_ver']=$value;break;
            	case 'QYPHP VERSION':$info['qy_ver']=$value;break;
            	case 'AUTHOR':$info['author']=$value;break;
            	case 'VOL':$info['vol']=$value;break;
            }
        }
        return $info;
    }

    //检测是否完成
    public function checkover()
    {
    	$tbs=$this->gettab();
		if(empty($tbs))
		{
    		$this->delvol();$this->deltab();return true;
    	}else
    	{
    		$vol=$this->vol+1;$this->setvol($vol);return false;
    	}
    }

    //设置文件头
    private function sethead($vol)
    {
    	$host=defined('__HOST__')?__HOST__:trim($_SERVER["SERVER_NAME"]);
    	$v=C('VERSION')?:'1.0';
        $info['os']         = PHP_OS;
        $info['web_server'] = $host;
        $info['file_name'] = $this->name;
        $info['php_ver']    = PHP_VERSION;
        $info['date']       = date('Y-m-d H:i:s');
        $head = "--QYPHP Dumper BEGIN\r\n".
                "--".$info['web_server']."\r\n".
                "--NAME:".$info['file_name']."\r\n".
                "--DATE:".$info["date"]."\r\n".
                "--FROM:MYSQL\r\n".
                "--PHP VERSION:".$info['php_ver']."\r\n".
                "--QYPHP VERSION:".$v."\r\n".
                "--AUTHOR:".$this->author."\r\n".
                "--VOL:".$vol."\r\n";
        return $head;
    }

    //获取表定义
    private function getdef($table,$drop=false)
    {
		$td=$drop?"DROP TABLE IF EXISTS `$table`;\r\n":'';
        $arr=$this->db->query("SHOW CREATE TABLE `$table`");
        $sql=$arr[0]['create table'];
        $sql=substr($sql,0,strrpos($sql,")")+1);
        $td.=$sql." ENGINE=InnoDB DEFAULT CHARSET=utf8;\r\n";
        return $td;
    }

    //获取表数据
    private function getdata($tb,$pos)
    {
        $pps=$pos;
        $list=$this->db->query("SELECT COUNT(*) as cnt FROM $tb");$total=$list[0]['cnt'];
        if($total==0||$pos>=$total){return -1;}$tms=ceil(($total-$pos)/$this->len);
        for($i=0;$i<$tms;$i++)
        {
            $data=$this->db->query("SELECT * FROM $tb LIMIT ".($this->len*$i+$pos).','.$this->len);
            $count=count($data);
            //$fields=array_keys($data[0]);$start="INSERT INTO `$tb`(`".implode("`,`",$fields)."`) VALUES ";
            $start="INSERT INTO `$tb` VALUES ";
            for($j=0;$j<$count;$j++)
            {
                $record=array_map("self::formatNull",$data[$j]);
                if($this->short){
                	$tmp="('".implode("','",$record)."')";
                    $end=($pps==$total-1)?";":(($j==$count-1)?";":",");
                    $tmp=$tmp.$end."\r\n";
                    if($pps==$pos){$tmp=$start."\r\n".$tmp;}
                    else{if($j==0){$tmp=$start."\r\n".$tmp;}}
                }else{
                    $tmp=$start."('".implode("','",$record)."');\r\n";
                }
                $tmp=str_replace("'NULL'",'NULL',$tmp);
                if(strlen($this->m_dump)+strlen($tmp)>$this->size-32)
                {
                    if($this->m_num==0)
                    {
                        $this->m_dump.=$tmp;$this->m_num++;$pps++;
                        if($pps==$total){return -1;}
                    }
                    $this->m_dump=substr($this->m_dump,0,-3).";\r\n";
                    return $pps;
                }else{
                    $this->m_dump.=$tmp;$this->m_num++;$pps++;
                }
            }
        }
        return -1;
    }

    //记录备份表名
    private function settab($tbs)
    {
    	$str='';if(!is_array($tbs)){return false;}
        foreach($tbs as $k=>$v){$str.= $k.'::'.$v.";\r\n";}
    	return file_put_contents($this->taber,$str);
    }
    //获取备份表名
    public function gettab()
    {
    	$arr=array();
    	if(!is_file($this->taber)){return false;}$str=@file_get_contents($this->taber);
        if(empty($str)){return $arr;}$tmp_arr=explode("\n",$str);
        foreach($tmp_arr as $val)
        {
            $val=trim($val,"\r;");if(empty($val)){continue;}
            list($tb,$ct)=explode('::',$val);$arr[$tb]=$ct;
        }
        return $arr;
    }
    //删除备份表名
    private function deltab(){return @unlink($this->taber);}

    //记录最后卷标
    public function setvol($vol){return file_put_contents($this->voler,$vol);}
    //获取最后卷标
    private function getvol()
    {
        if(!is_file($this->voler)){return 0;}
        $v=file_get_contents($this->voler);
        return is_numeric($v)?intval($v):$this->vol;
    }
    //删除卷标文件
    private function delvol(){return @unlink($this->voler);}

    //生成文件
    private function save()
    {
    	$file=$this->path.$this->name;
    	if($this->vol>1){$file.='_'.$this->vol;}$file.='.sql';
    	return file_put_contents($file,$this->m_dump);
    }

    //获取所有备份文件
    private function getfiles($p,$t='*',$f='*')
    {
        $p=rtrim(str_replace('/',$this->mdir,$p),$this->mdir).$this->mdir;
        $t=$t?:'*';$f=$f?:'*';$list=array();$f=str_replace('*','.*?',$f);
        $r=opendir($this->path);
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

    //格式化空
    private function formatNull($s){return !$s&&$s!==0?'NULL':$s;}
    //转义敏感字符
    private function escape($s){return PHP_VERSION>='4.3'?mysql_real_escape_string($s):mysql_escape_string($s);}
    //过滤注释内容
    private function comment($s){return (substr($s,0,2)!='--');}
}
?>