<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-29 13:33:27
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class DepartController extends BaseController
{
    //列表
    public function index()
    {
        $admin=session('admin.info');
        $cat =  CAT('Depart',array('id','pid','depart_name'),'','add_time asc');
        $lists = $cat->getList(0);
        if (!$admin['is_supper']) {
            $clist=$cat->getChildren($admin['org_id'],$lists) ;
        }
        $returns=$admin['is_supper']?$lists:$clist;
        $tre=array_reduce($lists,function(&$tre,$v){
            $tre[$v['id']] = $v;
            return $tre;
        });
        foreach ($returns as $key => $value) {
            if ($value['pid']) {
                $returns[$key]['parent_name']=$tre[$value['pid']]['depart_name'];
            }
        }
        $this->assign('lists',$returns);
        $this->display();
     //    $len=I('get.size')?:20;
     //    $key=I('get.key_word')?:'';
     //    $where='1=1';
     //    if($key){$where.=" AND (d.depart_no LIKE '%{$key}%' OR dp.depart_name LIKE '%{$key}%' OR d.depart_name LIKE '%{$key}%')";}
     //    $count=M()->table('k_depart d')->join("k_depart dp ON dp.id=d.pid","LEFT")->where($where)->count();
     //    $page=new \Think\PageA($count,$len,I('get.'));
     //    $lists=M()->field('d.*,dp.depart_name as parent_name')->table('k_depart d')
     //    ->join("k_depart dp ON dp.id=d.pid","LEFT")->where($where)->order("d.depart_no asc")->limit($page->firstRow,$len)->select();
     //    $this->assign('lists',$lists);
     //    $this->assign('ajaxlen',$len);
     //    $this->assign('key_word',$key);
     //    $this->assign('page',$page->show());
    	// $this->display();
    }

    public function ajaxindex()
    {
        $len=I('get.size')?:20;
        $page=I('get.page')?:1;
        $key=I('get.key_word')?:'';
        $first=$len*($page-1);
        $where='1=1';
        if($key){$where.=" AND (d.depart_no LIKE '%{$key}%' OR dp.depart_name LIKE '%{$key}%' OR d.depart_name LIKE '%{$key}%')";}
        $lists=M()->field('d.*,dp.depart_name as parent_name')->table('k_depart d')
        ->join("k_depart dp ON dp.id=d.pid","LEFT")->where($where)->order("d.add_time desc")->limit($first,$len)->select();
        $this->ajaxReturn($lists);
    }

    //添加
    public function add()
    {
    	if(IS_POST)
        {
            $data=I('post.');
            if(!$data['depart_name']){$this->error('部门名称不能为空！');}
            if(!$data['depart_no']){$this->error('部门编号不能为空！');}
            $data['create_time']=time();$data['update_time']=time();
            $res=M('Depart')->add($data);
            $res!==false?$this->success('添加成功！',U('index')):$this->error('添加失败！');
        }else
        {
            $this->assign('depts',$this->getDepts());
            $this->display();
        }
    }

    //编辑
    public function edit()
    {
    	if(IS_POST)
        {
            $data=I('post.');
            if(!$data['depart_name']){$this->error('部门名称不能为空！');}
            if(!$data['depart_no']){$this->error('部门编号不能为空！');}
            $data['update_time']=time();
            $res=M('Depart')->save($data);
            $res!==false?$this->success('修改成功！',U('index')):$this->error('修改失败！');
        }else
        {
            $id=I('get.id');
            $info=M('Depart')->where(array('id'=>$id))->find();
            $this->assign('info',$info);
            $this->assign('depts',$this->getDepts($id));
            $this->display();
        }
    }

    //删除
    public function del()
    {
    	$ids=implode(',',(array)I('post.ids'));
        if(!$ids){$this->error('非法操作！');}
        $where="id IN (".$ids.")";
        $res=M('Depart')->where($where)->delete();
        $res!==false?$this->success('删除成功！',-1):$this->error('删除失败！');
    }

    private function getDepts()
    {
        $admin=session('admin.info');
        $cat=CAT('Depart',array('id','pid','depart_name'));
        $lists=$cat->getList(0);
        if ($admin['is_supper']) {
            return $lists;
        }else{
            return $cat->getChildren($admin['org_id'],$lists);
        }
    }
}
?>