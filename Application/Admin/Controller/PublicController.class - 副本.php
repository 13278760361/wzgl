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
class PublicController extends Controller
{
	//空页
    public function index(){redirect(U('login'));}

    //登录
    public function login()
    {
        if(IS_POST)
        {
            $data=I('post.');$u=array();
            $user=$data['username'];
            $pass=encrypt_md5($data['password']);
            if(!$this->logininfo($user,$pass)){
                adminlog(1,'账户或密码错误！');
                $this->error('用户名或密码错误！');
            }
            $info=session('admin');
            $this->getPerms();
            if($info['info']['id']){
                adminlog(1,'登录成功！');
                $this->success('登录成功！',U('Index/index'));
            }
        } else {
            //echo   encrypt_md5('admin123');
            $this->display();
        }
    }

    /* 退出登录 */
    public function logout()
    {
        session('[destroy]');
        $this->success('退出成功！',U('login'));
    }


    //获取登录用户权限
    public function getPerms()
    {
        $info=session('admin');
        if($info['info']['id']>0)
        {
            $group=M()->field('g.*')->table('k_admin_group g,k_admin_group_access gs')->where("gs.group_id=g.id AND gs.uid=".$info['info']['id'])->find();
            $rules=$group['rules'];
            $perms=M('AdminRule')->where("status=1 AND id IN ({$rules})")->order('sort asc')->select();
        }else
        {
            $group=array();
            $perms=M('AdminRule')->order('sort asc')->select();
        }
        $out=$this->otherNodes();
        if($out){foreach($out as $o){array_push($perms,$o);}}
        $info=session('admin');
        $info['info']=$info['info'];
        $info['perms']=$perms;
        $info['group']=$group;
        session('admin',$info);
    }

    private function logininfo($u,$p)
    {
        $admins=C('ADMIN_LIST');
        foreach($admins as $k=>$v)
        {
            if($v['username']==$u&&$v['password']==$p){
                session('admin',array('info'=>$v));return true;
            }
        }
        $user=M('Admin')->where(array('username'=>$u,'password'=>$p))->find();
        if($user['id'])
        {
            session('admin',array('info'=>$user));
            M('Admin')->where(array('id'=>$user['id']))->save(array('login_time'=>time()));
            return true;
        }
        return false;
    }

    private function otherNodes()
    {
        return array(
            array('id'=>-1,'name'=>'Admin/Main/index','add'=>1),
            array('id'=>-1,'name'=>'Admin/Index/online','add'=>1),
            array('id'=>-1,'name'=>'Admin/Role/getNode','add'=>1)
        );
    }
   
}
?>