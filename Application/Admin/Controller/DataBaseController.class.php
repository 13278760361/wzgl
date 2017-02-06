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
namespace Admin\Controller;
use Think\Controller;
vendor('Dumper');
/**
 * 数据库操
 */
class DataBaseController extends BaseController
{
    private $dp=null;private $name='wzgl_bak';private $size=10485760;
    private $lock='';
    public function _initialize()
    {
        parent::_initialize();
        $this->dp=new \Qing\Dumper();
        $this->lock=RUNTIME_PATH.'Data/backup/backup.lock';
    }
    //列出数据备份
    public function index()
    {
        $this->display();
    }

    //备份数据
    public function backup()
    {
    	if(IS_POST||I('get.act')=='goon'){
    		if(IS_POST){
                file_put_contents($this->lock,'1');
    			$tbs=M()->db()->getTables();
    			$this->dp=$this->dp->tables($tbs);
    		}
    		$info=session('admin');
    		$this->dp=$this->dp->option(array('name'=>$this->name,'size'=>$this->size,'author'=>$info['info']['name']));
    		$res=$this->dp->dump();
    		if($res<0){
    			redirect(U('backup',array('act'=>'goon')));
    		}else if($res>0){
                @unlink($this->lock);
                $this->success('操作成功！',U('index'));
            }else{
    			$this->error('操作失败！');
    		}
    	}else{
    		$this->display();
    	}
    }

    //还原数据
    public function recover()
    {
    	if(IS_POST||I('get.act')=='goon'){
            if(is_file($this->lock)){$this->error('上次备份数据已损坏，不能恢复！');}
    		$file=$this->name;
	    	$res=$this->restore($file);
	    	if($res<0){
	    		redirect(U('recover',array('act'=>'goon')));
	    	}else{
	    		$res?$this->success('还原成功！',U('index')):$this->error('还原失败！');
	    	}
    	}else{
    		$this->error('操作失败！');
    	}
    }

    //写入数据
    private function restore($file)
    {
    	return $this->dp->recover($file);
    }
}
?>