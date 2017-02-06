<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-12-02 14:21:09
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class UploadController extends Controller
{
	public function index(){$this->img();}
	public function _empty(){$this->img();}

	//上传图片
    public function img()
    {
        $this->success('上传成功！',ltrim($this->upload('img'),'.'));
    }

    //上传文件
    public function file()
    {
    	$this->success('上传成功！',ltrim($this->upload('file'),'.'));
    }

    //导入文件
    public function import()
    {
    	$s=$_SERVER;
        $act=I('act');$act=$act?:'import';
    	$ps=explode('/'.MODULE_NAME.'/',$s['HTTP_REFERER']);
    	$p=MODULE_NAME.'/'.$ps[1];$pp=$p;$ps=explode('/',$p);$p=$ps[0].'/'.$ps[1];
        $pm=$p.'/'.$act;session('nact',$pm);
    	$o=A($p);if(!$o){$this->error("找不到指定的控制器{$p}");}
    	$t=$o->inlist;if(!$t){$this->error('没有指定导入的列！');}
    	$f=$this->upload('file');$d=Importer($f,$t,2);
    	if(!$d['status']){$this->error($d['data']);}
    	if(call_user_func_array(array($o,$act),array($d['data']))){$this->ajaxReturn(array('status'=>1,'info'=>array('status'=>1,'info'=>'导入成功！','call'=>'/'.$pp)));}
        $this->error('导入失败！');
    }

    //下载模板
    public function download()
    {
    	$mod=I('mod');$mod=$mod?:MODULE_NAME;$ctr=I('ctr');$act=I('act');$act=$act?:'download';
        if(!$ctr){$this->error('没有指定控制器！');}
        $p=$mod.'/'.$ctr;$pm=$p.'/'.$act;session('nact',$pm);
        $o=A($p);if(!$o){$this->error("找不到指定的控制器{$p}");}
    	$t=$o->outlist;$name=I('name')?:'';$name=$name.'模板';
    	if(!@method_exists($o,$act)){
    		$d[]=$t;foreach($d[0] as $k=>$v){$d[0][$k]='';}
    	}else{
    		$d=call_user_func_array(array($o,$act),array(I()));
    	}
    	Exporter($name,$d,$t);
    }

    //导出文件
    public function export()
    {
        $mod=I('mod');$mod=$mod?:MODULE_NAME;$ctr=I('ctr');$act=I('act');$act=$act?:'export';
        if(!$ctr){$this->error('没有指定控制器！');}
        $p=$mod.'/'.$ctr;$pm=$p.'/'.$act;session('nact',$pm);
        $o=A($p);if(!$o){$this->error("找不到指定的控制器{$p}");}
        $d=call_user_func_array(array($o,$act),array(I()));
        $d?$this->ajaxReturn($d):'';
    }

    //上传
    private function upload($type='img')
    {
    	$c['rootPath']='./Uploads/';$c['savePath']=$type=='img'?'imgs/':'files/';
        $c['autoSub']=false;$c['subName']='';$c['saveName']=date('YmdHis');
        $u=new \Think\Upload($c);$info=$u->upload();$url='';
        if(!$info){$this->error($u->getError());}
        foreach($info as $i){$url=$c['rootPath'].$i['savepath'].$i['savename'];break;}
        return $url;
    }
}
?>