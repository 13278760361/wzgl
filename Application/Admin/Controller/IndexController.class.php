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
class IndexController extends Controller
{
    public function _initialize()
    {
        $c=isMobile()?C('mobile'):C('default');foreach($c as $k=>$v){C($k,$v);}
        $this->checklogin();
        $this->assign('menus',$this->getmenus());
        $this->assign('nurl',MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME);
    }
	
    //测试页面
    public function test(){
		$this->display();
	}

    //首页
    public function index()
    {
    	$this->display();
    }

    //消息页面
    public function messages()
    {
        $info=session('admin');
        $id=$info['info']['id'];
        if (isMobile()) {
            $w=array('status'=>1,'receive_id'=>$id,'type'=>array('not in','20,40,50,90,100'));
        }else{
            $w=array('status'=>1,'receive_id'=>$id);
        }
        
        $lists=M('Message')->where($w)->order('send_time desc')->select();
        $this->assign('lists',$lists);
        $this->display();
    }

    //获取消息
    public function ajaxmessages()
    {
        $info=session('admin');
        $id=$info['info']['id'];
        if (isMobile()) {
            $w=array('status'=>1,'receive_id'=>$id,'type'=>array('not in','20,40,50,90,100'));
        }else{
            $w=array('status'=>1,'receive_id'=>$id);
        }
        $lists=M('Message')->where($w)->order('send_time desc')->select();
        count($lists)?$this->success($lists):$this->error('没有消息');
    }  

    //个人中心
    public function personal()
    {
        $my=session('admin');
        $id=$my['info']['id'];
        $group=$my['group'];
        if(IS_POST)
        {
            if($id<0){$this->error('超级管理员不能修改信息！');}
            $data=I('post.');
            $res=M('Admin')->where(array('id'=>$id))->save($data);
            if($res!==false)
            {
                $info['info']=M('Admin')->where(array('id'=>$id))->find();
                $info['group']=$my['group'];
                $info['perms']=$my['perms'];
                session('admin',$info);
                $this->success('修改成功！',-1);
            }else
            {
                $this->error('修改失败！');
            }
        }else
        {
            $depart=M('Depart')->where(array('id'=>$my['info']['depart_id']))->find();
            $info=$my['info'];
            $info['group']=$group['title'];
            $info['depart']=$depart['depart_name'];
            $this->assign('info',$info);
            $this->display();
        }
    }

    //修改密码
    public function changer()
    {
        if(IS_POST)
        {
            $my=session('admin');
            $id=$my['info']['id'];
            $data=I('post.');
            if(!$data['opassword']){$this->error('原密码不能为空！');}
            if(encrypt_md5($data['opassword'])!=$my['info']['password']){$this->error('原密码不正确！');}
            if(!$data['npassword']){$this->error('新密码不能为空！');}
            if($data['npassword']!=$data['npassword2']){$this->error('两次输入密码不一致！');}
            $res=M('Admin')->where(array('id'=>$id))->save(array('password'=>encrypt_md5($data['npassword'])));
            if($res!==false)
            {
                session('[destroy]');
                $this->success('密码修改成功，请重新登录！',U('Public/login'));
            }else
            {
                $this->error('密码修改失败！');
            }
        }else
        {
            $this->display();
        }
    }

    //保持在线
    public function online()
    {
        $my=session('admin');
        $id=$my['info']['id'];
        if(!$id){$this->error('下线！');}
        if($id<0){$this->error('超级管理员！');}
        $res=M('Admin')->where(array('id'=>$id))->save(array('online'=>time()));
        ($res!==false && session('admin'))?$this->success('在线！'.$id):$this->error('下线！');
    }

    //验证登录
    private function checklogin()
    {
        $info=session('admin');
        if(!$info['info']['id']){redirect(U('Public/login'));}
    }

    //组织菜单
    private function getMenus()
    {
        $info=session('admin');$menus=array();
        $perms=$info['perms'];
        $cat=CAT('',array('id','pid','title'));$list=$cat->getList(0,$perms);
        $list1=$cat->getChild(0);
        foreach($list1 as $k=>$one)
        {
            if($one['add']){continue;}
            if(isMobile()){
                if (!$one['wapdisable']) {
                    array_push($menus,$one);
                }
                continue;
            }else{
                $list2=$cat->getChild($one['id']);
                foreach ($list2 as $key => $value) {
                    if ($value['ishide']!=1) {
                        $two=$value;break;
                    }
                }
                // $two=$list2[0];
                $three=$cat->getchild($two['id']);
                foreach ($three as $k => $v) {
                    if ($v['ishide']!=1) {
                        $tmp=$v;break;
                    }
                }
                $one['name']=$tmp['name'];
                array_push($menus,$one);
            }
           
        }
        return $menus;
    }
}