<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-12-10 21:53:30
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class MenusController extends BaseController
{
	public function _empty($name)
	{
		$this->getmenus();
		$this->display('index');
	}

    private function getmenus()
    {
    	$my=session('admin');
    	$perms=$my['perms'];$info=array();
    	$url=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
    	foreach($perms as $p){if($p['name']==$url){$info=$p;break;}}
    	$cat=CAT('',array('id','pid','title'));
    	$lists=$cat->getTree($info['id'],$perms);
    	$this->assign('lists',$lists);
    }
}
?>