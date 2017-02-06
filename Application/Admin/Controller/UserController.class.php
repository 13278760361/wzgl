<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-29 13:34:21
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class UserController extends BaseController
{
    public $outlist=array('username'=>'用户名','name'=>'姓名','sex'=>'性别','org'=>'单位','depart'=>'部门','role'=>'角色','tel'=>'联系电话','email'=>'邮箱','audit'=>'审核人');
    public $inlist=array('username','name','sex','org','depart','role','tel','email','audit');
    //列表
    public function index()
    {
    	$len=I('size')?:20;
        $key=I('get.key_word')?:'';
        $orgid=1;
        $my=session('admin');
        $orgid=$my['info']['is_supper']?0:$my['info']['org_id'];
        $where=$orgid?"a.org_id={$orgid}":'1=1';
        if($key){
            $where.=isMobile()?
            " AND (a.username LIKE '%{$key}%' OR a.name LIKE '%{$key}%' OR g.title LIKE '%{$key}%' OR d.depart_name LIKE '%{$key}%')":
            " AND (a.username LIKE '%{$key}%' OR a.name LIKE '%{$key}%' OR a.tel LIKE '%{$key}%' OR a.email LIKE '%{$key}%' OR g.title LIKE '%{$key}%' OR d.depart_name LIKE '%{$key}%')";
        }
        $count=M()->table("k_admin a")->join("k_admin_group_access ga ON a.id=ga.uid","left")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->join("k_depart d ON a.depart_id=d.id","LEFT")->where($where)->count();
        $page=new \Think\PageA($count,$len,I('get'));
        $lists=M()->field('a.*,g.title as role,d.depart_name')->table("k_admin a")->join("k_admin_group_access ga ON a.id=ga.uid","left")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->join("k_depart d ON a.depart_id=d.id","LEFT")->where($where)->order('a.id asc')->limit($page->firstRow,$len)->select();
        foreach($lists as $k=>$v){
            $lists[$k]['sex']=$v['sex']==1?'男':'女';
        }
        $this->assign('key_word',$key);
        $this->assign('lists',$lists);
        $this->assign('ajaxlen',$len);
        $this->assign('page',$page->show());
        $this->display();
    }

    public function ajaxindex()
    {
        $page=I('get.page');
        $len=I('get.size')?:20;
        $key=I('get.key_word');
        $first=$len*($page-1);
        $my=session('admin');
        $orgid=$my['info']['is_supper']?0:$my['info']['org_id'];
        $where=$orgid?"a.org_id={$orgid}":'1=1';
        if($key)
        {
            $where.=isMobile()?" AND (a.username LIKE '%{$key}%' OR a.name LIKE '%{$key}%' OR g.title LIKE '%{$key}%' OR d.depart_name LIKE '%{$key}%')":" AND (a.username LIKE '%{$key}%' OR a.name LIKE '%{$key}%' OR a.tel LIKE '%{$key}%' OR a.email LIKE '%{$key}%' OR g.title LIKE '%{$key}%' OR d.depart_name LIKE '%{$key}%')";
        }
        $lists=M()->field('a.*,g.title as role,d.depart_name as depart')->table("k_admin a")->join("k_admin_group_access ga ON a.id=ga.uid","left")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->join("k_depart d ON a.depart_id=d.id","LEFT")->where($where)->order('a.update_time desc')->limit($first,$len)->select();
        foreach($lists as $k=>$v){$lists[$k]['sex']=$v['sex']==1?'男':'女';}
        $this->ajaxReturn($lists);
    }

    //添加
    public function add()
    {
        if(IS_POST)
        {
            $datas=I('post.');
            $pass=$datas['password'];
            $group=$datas['group_id'];
            $dept=$datas['depart_id'];
            !$dept&&$this->error('请选择部门！');
            $isSupper=$datas['is_supper']?:0;
            if(!$pass){$pass='123456';}
            $account=$datas['username'];
            $pass=encrypt_md5($pass);
            $audit=$datas['is_auditer'];
            $user=M('Admin')->where(array('username'=>$account))->find();
            if($user){$this->error('该账号已经存在，请更换！');}
            if($audit)
            {
                $user=M('Admin')->where(array('depart_id'=>$dept,'is_auditer'=>1))->find();
                if($user){$this->error('该部门拥有审核权限的人只能存在一个！');}
            }
            //根据部门查出 org_id;
            $cat=CAT('Depart',array('id','pid','depart_name'))->getPath($dept);
            $data['org_id']=$cat[0]['id']?:$dept;
            $data['username']=$account;
            $data['password']=$pass;
            $data['name']=$datas['name'];
            $data['sex']=$datas['sex'];
            $data['tel']=$datas['tel'];
            $data['email']=$datas['email'];
            $data['depart_id']=$datas['depart_id'];
            $data['is_auditer']=$datas['is_auditer'];
            $data['signature']=$datas['signature'];
            $data['is_supper']=$isSupper;
            $data['add_time']=time();
            $data['update_time']=time();
            $md=M('Admin');
            $md->startTrans();
            $res=$md->data($data)->add();
            if($res!==false)
            {
                $res1=M('AdminGroupAccess')->data(array('uid'=>$res,'group_id'=>$group))->add();
            }
            if($res!==false&&$res1!==false){
                $md->commit();
                $this->success('添加成功！',U('index'));
            }else{
                $md->rollBack();
                $this->error('添加失败！');
            }
        }else
        {
            $admin=session('admin');
            $this->assign('is_supper',$admin['info']['is_supper']);
            $this->assign('roles',$this->getRoles());
            $this->assign('depts',$this->getDeparts());
            $this->display();
        }
    }

    //编辑
    public function edit()
    {
    	if(IS_POST)
        {
            $datas=I('post.');
            $id=$datas['id'];
            $group=$datas['group_id'];
            $pass=$datas['password'];
            $dept=$datas['depart_id'];
            $account=$datas['username'];
            $audit=$datas['is_auditer'];
            $user=M('Admin')->where(array('id'=>$id))->find();
            if(!$user['id']){$this->error('该管理员不存在！');}
            if($audit)
            {
                $u=M('Admin')->where("depart_id={$dept} AND is_auditer=1 AND id<>{$id}")->find();
                if($u){$this->error('该部门拥有审核权限的人只能存在一个！');}
            }
            $pass=$pass?encrypt_md5($pass):$user['password'];
            $data['name']=$datas['name'];
            $data['username']=$account;
            $data['password']=$pass;
            $data['sex']=$datas['sex'];
            $data['tel']=$datas['tel'];
            $data['email']=$datas['email'];
            $data['depart_id']=$datas['depart_id'];
            $data['is_auditer']=$datas['is_auditer'];
            $data['signature']=$datas['signature'];
            $data['update_time']=time();
            $md=M('Admin');
            $md->startTrans();
            $res=$md->where(array('id'=>$id))->data($data)->save();
            if($res!==false){
                $res1=M('AdminGroupAccess')->where(array('uid'=>$id))->data(array('group_id'=>$group))->save();
            }
            if($res!==false&&$res1!==false){
                $md->commit();
                $this->success('修改成功！',U('index'));
            }else{
                $md->rollBack();
                $this->error('修改失败！');
            }
        }else
        {
            $id=I('get.id');
            $info=M()->field('a.*,g.id as groupid')
            ->table('k_admin a')
            ->join('k_admin_group_access ga on a.id=ga.uid','left')
            ->join('k_admin_group g on g.id=ga.group_id','left')
            ->where('a.id='.$id)
            ->find();
            $this->assign('info',$info);
            $admin=session('admin');
            $this->assign('is_supper',$admin['info']['is_supper']);
            $this->assign('roles',$this->getRoles());
            $this->assign('depts',$this->getDeparts());
            $this->display();
        }
    }

    //删除
    public function del()
    {
    	$ids=I('post.ids');
        if(!$ids){$this->error('非法操作！');}
        $ids=(array)$ids;
        $ids=implode(',',$ids);
        $where="id IN (".$ids.")";
        $has=M('Admin')->where("id IN (".$ids.") and unable_del=1")->find();
        if ($has) {$this->error('管理员 '.$has['username'].' 不能删除！');}
        $res=M('Admin')->where($where)->delete();
        if($res!==false){M('AdminGroupAccess')->where("uid IN (".$ids.")")->delete();}
        $res!==false?$this->success('删除成功！',-1):$this->error('删除失败！');
    }

    //导出数据
    public function export() 
    {
        $len=I('size')?:10;
        $page=I('page');
        $key=I('key');
        $first=$len*($page-1);
        $my=session('admin');
        $orgid=$my['info']['is_supper']?0:$my['info']['org_id'];
        $where=$orgid?"a.org_id={$orgid}":'1=1';
        if($key)
        {
            $where.=" AND (a.username LIKE '%{$key}%' OR a.name LIKE '%{$key}%' OR a.tel LIKE '%{$key}%' OR a.email LIKE '%{$key}%' OR g.title LIKE '%{$key}%' OR d.depart_name LIKE '%{$key}%')";
        }
        $lists=M()->field('a.*,g.title as role,d.depart_name as depart')->table('k_admin a')->join("k_admin_group_access ga ON ga.uid=a.id","LEFT")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->join("k_depart d ON a.depart_id=d.id","LEFT")->where($where)->limit($first,$len)->select();
        // print_r($lists);
        // exit;
        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $Dlists = $cat->getList(0);
        
        foreach($lists as $k=>$v)
        {
            //获取顶级
            $DPath = $cat->getPath($v['depart_id']);
            // $org=array_shift($DPath);
            $lists[$k]['org']=$DPath[0]['depart_name'];
            $lists[$k]['sex']=$v['sex']==1?'男':'女';
            $lists[$k]['is_auditer']=$v['is_auditer']==1?'是':'否';
        }
        return $lists;
    }

    //导入数据
    public function import($datas)
    {
        $boo=true;
        $md=M('Admin');
        $md->startTrans();
        foreach($datas as $k=>$v)
        {
            $user=M('admin')->where(array('username'=>$v['username'],'name'=>$v['name']))->find();
            if($user){$boo=false;break;}
            $d=M('Depart')->where(array('depart_name'=>$v['depart']))->find();
            $org=M('Depart')->where(array('depart_name'=>$v['org'],'pid'=>0))->find();
            $g=M('AdminGroup')->where(array('title'=>$v['role']))->find();
            $data=$v;
            $data['password']=encrypt_md5('123456');
            $data['sex']=$v['sex']=='男'?1:2;
            $data['is_auditer']=$v['audit']=='是'?1:0;
            $data['depart_id']=$d?$d['id']:0;
            $data['org_id']=$org?$org['id']:0;
            $boo=$md->add($data);
            if(!$boo){$boo=false;break;}
            $boo=$g?M('AdminGroupAccess')->add(array('uid'=>$boo,'group_id'=>$g['id'])):true;
            if(!$boo){$boo=false;break;}
        }
        $boo?$md->commit():$md->rollBack();
        return $boo?true:false;
    }

    //所有可用角色
    private function getRoles()
    {
        return M('AdminGroup')->where(array('status'=>1))->select();
    }

    //所有部门
    private function getDeparts()
    {
        $admin=session('admin');
        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $top=M('Depart')->where(array('pid'=>0))->getfield('id');
        $lists = $cat->getList($top);
        $Depart = $admin['info']['is_supper'] ? $lists : $cat->getChildren($admin['info']['org_id'],$lists) ;
        return $Depart;
    }
}
?>