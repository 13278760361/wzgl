<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-12-07 14:44:44
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class LogController extends BaseController
{
    //日志列表
    public function index()
    {
    	$len=I('get.size')?:20;//C('WEB.bg_page_size');
    	$key=I('get.key_word')?:'';
    	$where="1=1";
    	if($key){
            $where.=isMobile()?
            " AND (log_name LIKE '%{$key}%' OR log_desc LIKE '%{$key}%' OR log_ip LIKE '%{$key}%')":
            " AND (log_name LIKE '%{$key}%' OR log_action LIKE '%{$key}%' OR log_desc LIKE '%{$key}%' OR log_ip LIKE '%{$key}%')";}
    	$md=M('AdminLog');
    	$count=$md->where($where)->count();
    	$page=new \Think\PageA($count,$len,I('get.'));
    	$lists=$md->where($where)->limit($page->firstRow,$len)->order("log_time desc")->select();
    	foreach($lists as $k=>$v)
    	{
    		$lists[$k]['log_time']=date('Y-m-d H:i:s',$v['log_time']);
    		switch($v['log_type'])
    		{
    			case 1:
    				$parm=json_decode($v['log_parm'],true);
    				$lists[$k]['log_type']='登录日志';
    				$lists[$k]['log_name']=$v['log_name']?:$parm['username'];
    			break;
    			default:$lists[$k]['log_type']='';break;
    		}
    	}
    	$this->assign('key_word',$key);
        $this->assign('ajaxlen',$len);
    	$this->assign('lists',$lists);
    	$this->assign('page',$page->show());
    	$this->display();
    }

    public function ajaxindex()
    {
        $len=I('get.size')?:20;
        $page=I('get.page')?:1;
        $key=I('get.key_word')?:'';
        $first=$len*($page-1);
        $where="1=1";
        if($key){$where.=" AND (log_name LIKE '%{$key}%' OR log_action LIKE '%{$key}%' OR log_desc LIKE '%{$key}%' OR log_ip LIKE '%{$key}%')";}
        $md=M('AdminLog');
        $lists=$md->where($where)->limit($first,$len)->order("log_time desc")->select();
        foreach($lists as $k=>$v)
        {
            $lists[$k]['log_time']=date('Y-m-d H:i:s',$v['log_time']);
            switch($v['log_type'])
            {
                case 1:
                    $parm=json_decode($v['log_parm'],true);
                    $lists[$k]['log_type']='登录日志';
                    $lists[$k]['log_name']=$v['log_name']?:$parm['username'];
                break;
                default:$lists[$k]['log_type']='';break;
            }
        }
        $this->ajaxReturn($lists);
    }

    //日志删除、清除
    public function del()
    {
        $act=I('post.act');
        if($act=='all')
        {
            $res=M()->execute("truncate table k_admin_log");
        }else
        {
            $ids=(array)I('post.ids');
            if(!$ids){$this->error('非法操作！');}
            $ids=implode(',',$ids);
            $where="log_id IN (".$ids.")";
            $res=M('AdminLog')->where($where)->delete();
        }
        $res!==false?$this->success('删除成功！',-1):$this->error('删除失败！');
    }

    //审核人列表
    public function auditer()
    {
        if(IS_POST)
        {
            $lists=M()->field('a.*,d.depart_name as depart,g.title as role')->table('k_admin a')->join("k_depart d ON d.id=a.depart_id","LEFT")->join("k_admin_group_access ga ON ga.uid=a.id","LEFT")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->where("a.is_auditer=1 AND a.online>unix_timestamp(now() - INTERVAL 10 SECOND)")->order('a.id asc')->select();
            foreach($lists as $k=>$v){$lists[$k]['sex']=$v['sex']==1?'男':'女';}
            $this->ajaxReturn($lists);
        }else
        {
            $lists=M()->field('a.*,d.depart_name as depart,g.title as role')->table('k_admin a')->join("k_depart d ON d.id=a.depart_id","LEFT")->join("k_admin_group_access ga ON ga.uid=a.id","LEFT")->join("k_admin_group g ON g.id=ga.group_id","LEFT")->where("a.is_auditer=1 AND a.online>unix_timestamp(now() - INTERVAL 10 SECOND)")->order('a.id asc')->select();
            foreach($lists as $k=>$v){$lists[$k]['sex']=$v['sex']==1?'男':'女';}
            $this->assign('lists',$lists);
            $this->display();
        }
    }
}
?>