<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-25 13:44:13 76891f4954db98b13e22a386378e4d12
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class BaseController extends Controller
{
	public function _initialize()
	{
		$c=isMobile()?C('mobile'):C('default');foreach($c as $k=>$v){C($k,$v);}
		$info=session('admin');
		A('Public')->getPerms();
		if(!$info['info']['id']){session('url',$_SERVER['REQUEST_URI']);redirect(U('Public/login'));}
		if(!$this->checkPerm()){$this->error('没有权限！');}
		$this->assign('nurl',$this->gettop());
		$this->assign('nav',$this->getnav());
		$this->assign('menus',$this->getMenus());
	}

	//获取菜单
	private function getMenus()
	{
		$arr=array();
		$info=session('admin');$perms=$info['perms'];
		foreach($perms as $p){
			if(!$p['add']){
				if (isMobile()) {
					if (!$p['wapdisable']) {
						array_push($arr,$p);
					}
				}else{
					array_push($arr,$p);
				}
			}
		}
		$brr=CAT('',array('id','pid','title'),'')->getTree(0,$arr);
		return $brr;
	}

	//获取顶级菜单
	private function gettop()
	{
		$my=session('admin');$perms=$my['perms'];$info=array();
		$url=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
		foreach($perms as $p){if($p['name']==$url){$info=$p;break;}}
		$cat=CAT('',array('id','pid','title'));
		$path=$cat->getPath($info['id'],$perms);
		return $path[0]?$path[0]['name']:$url;
	}

	//手机版小导航
	private function getnav()
	{
		$my=session('admin');$perms=$my['perms'];$info=array();
		$url=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
		foreach($perms as $p){if($p['name']==$url){$info=$p;break;}}
		$cat=CAT('',array('id','pid','title'));$info['cu']=1;
		$lists=$cat->getPath($info['id'],$perms);array_push($lists,$info);
		if (sizeof($lists)>2) {
			$lists[1]['name']=$lists[0]['name'];
		}
		return $lists;
	}

	//验证权限
	private function checkPerm()
	{
		$info=session('admin');$perms=array_field($info['perms'],'name');
		$url=session('nact')?:MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
		session('nact',null);
		
		//暂时处理方法
		foreach ($perms as $key => $value) {
			$s=explode('/', $value);
			if (sizeof($s)>3) {
				$perms[$key]=$s[0].'/'.$s[1].'/'.$s[2];
			}
		}
		return in_array($url,$perms);
	}
}