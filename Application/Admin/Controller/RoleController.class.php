<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-29 13:33:55
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class RoleController extends BaseController
{
    //列表
    public function index()
    {
    	$sorter=I('get.sot')?:'id';
        $len=I('get.size')?:10;
        $key=I('get.key_word');
        $where="status=1";
        if($key)
        {
            $where.=" AND (title LIKE '%{$key}%' OR describe LIKE '%{$key}%')";
        }
        $lists=M('AdminGroup')->where($where)->order($sorter)->limit($len)->select();
        $this->assign('sorter',$sorter);
        $this->assign('key_word',$key);
        $this->assign('lists',$lists);
        $this->display();
    }

    public function ajaxindex()
    {
        $page=I('get.page');
        $len=I('get.size')?:10;
        $key=I('get.key_word')?:'';
        $first=$len*($page-1);
        $where="status=1";
        if($key)
        {
            $where.=" AND (title LIKE '%{$key}%' OR describe LIKE '%{$key}%')";
        }
        $lists=M('AdminGroup')->where($where)->order('id asc')->limit($first,$len)->select();
        $this->ajaxReturn($lists);
    }

    //添加
    public function add()
    {
    	if(IS_POST)
        {
            $data=I('post.');
            if(!$data['title']){$this->error('角色名不能为空！');}
            if(!$data['rules']){$this->error('角色权限不能为空！');}
            $data['status']=1;$data['create_time']=time();
            $res=M('AdminGroup')->add($data);
            $res!==false?$this->success('添加成功！',U('index')):$this->error('添加失败！');
        }else
        {
            $this->display();
        }
    }

    //编辑
    public function edit()
    {
    	if(IS_POST)
        {
            $data=I('post.');
            if(!$data['title']){$this->error('角色名不能为空！');}
            if(!$data['rules']){$this->error('角色权限不能为空！');}
            $data['status']=1;
            $res=M('AdminGroup')->save($data);
            A('Public')->getPerms();
            $res!==false?$this->success('修改成功！',U('index')):$this->error('修改失败！');
        }else
        {
            $id=I('get.id');
            $info=M('AdminGroup')->where(array('id'=>$id))->find();
            $this->assign('info',$info);
            $this->display();
        }
    }

    //删除
    public function del()
    {
    	$ids=implode(',',(array)I('post.ids'));
        if(!$ids){$this->error('非法操作！');}
        $where="id IN (".$ids.")";
        $res=M('AdminGroup')->where($where)->delete();
        $res!==false?$this->success('删除成功！',-1):$this->error('删除失败！');
    }

    //获取节点
    public function getNode()
    {
        $id=I('post.id');
        $data=array();
        if($id)
        {
            $info=M('AdminGroup')->where(array('id'=>$id))->find();
            $rules=explode(',',$info['rules']);
        }
        $list=CAT('AdminRule',array('id','pid','title'),'status=1')->getList(0);
        foreach($list as $k=>$v)
        {
            $data[$k]['id']=$v['id'];
            $data[$k]['pId']=$v['pid'];
            $data[$k]['name']=$v['title'];
            if($rules&&in_array($v['id'],$rules)){$data[$k]['checked']=true;}
        }
        $this->ajaxReturn($data);
    }
}
?>