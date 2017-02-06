<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-12-04 11:20:21
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class ProductsController extends BaseController
{
    //导入
    public $inlist=array('cate_name','assets_name','unit');
    //列表
    public function index()
    {
        $len=I('get.size')?:20;
        $key=I('get.key_word')?:'';
        $where='t.id=g.type_id';
        if($key)
        {
            $where.=" AND (m.cate_name LIKE '%{$key}%' OR c.sn LIKE '%{$key}%' OR g.assets_name LIKE '%{$key}%' OR g.unit LIKE '%{$key}%')";
        }

        $count=M()->table("k_type t,k_base_goods g")
        ->join("k_category c ON c.id=g.cate_id","LEFT")
        ->join("k_category m ON m.id=g.top_id","LEFT")
        ->where($where)->count();
        $page=new \Think\PageA($count,$len);
        $lists=M()
        ->field('g.*,t.type_name as type,m.cate_name as topname,c.sn as catecode,c.cate_name as catname')
        ->table("k_type t,k_base_goods g")
        ->join("k_category c ON c.id=g.cate_id","LEFT")
        ->join("k_category m ON m.id=g.top_id","LEFT")
        ->where($where)->order("g.top_id asc,g.cate_id asc,g.id asc")
        ->limit($page->firstRow,$len)->select();
        
        // $last='!_!';
        // foreach($lists as $k=>$v)
        // {
        //     if($v['topname']!=$last)
        //     {
        //         $count=$this->count($lists,'topname',$v['topname']);
        //         $lists[$k]['top']='<td rowspan="'.$count.'" class="name_table_td">'.$v['topname'].'</td>';
        //         $last=$v['topname'];
        //     }
        // }
        $this->assign('key_word',$key);
        $this->assign('lists',$lists);
        $this->assign('ajaxlen',$len);
        $this->assign('page',$page->show());
    	$this->display();
    }

    public function ajaxindex()
    {
        $page=I('get.page')?:1;
        $len=I('get.size')?:1;
        $key=I('get.key_word')?:'';
        $first=$len*($page-1);
        $where='t.id=g.type_id';
        if($key)
        {
            $where.=" AND (m.cate_name LIKE '%{$key}%' OR c.sn LIKE '%{$key}%' OR g.assets_name LIKE '%{$key}%' OR g.unit LIKE '%{$key}%')";
        }

        $lists=M()
        ->field('g.*,t.type_name as type,m.cate_name as topname,c.sn as catecode,c.cate_name as catname')
        ->table("k_type t,k_base_goods g")
        ->join("k_category c ON c.id=g.cate_id","LEFT")
        ->join("k_category m ON m.id=g.top_id","LEFT")
        ->where($where)->order("g.top_id asc,g.cate_id asc,g.id asc")
        ->limit($first,$len)->select();

        $last='!_!';
        foreach($lists as $k=>$v)
        {
            if($v['topname']!=$last)
            {
                $count=$this->count($lists,'topname',$v['topname']);
                $lists[$k]['top']='<td rowspan="'.$count.'" class="name_table_td">'.$v['topname'].'</td>';
                $last=$v['topname'];
            }
        }
        $this->ajaxReturn($lists);
    }

    //固定资产分类
    public function typegd()
    {
        if(IS_POST)
        {
            $datas=I('post.');$data=array();
            if(!$datas['sn']){$this->error('编码不能为空！');}
            if(!$datas['name']){$this->error('名称不能为空！');}
            //如果type为空则是修改
            if (!$datas['type']&&$datas['oid']) {
                $saveData=array(
                    'cate_name'=>$datas['name'],
                    'sn'=>$datas['sn'],
                    'netsalvage'=>$datas['netsalvage']
                );
                $re_f=M('Category')->where(array('id'=>$datas['oid']))->save($saveData);
                $re_f!==false?$this->success('修改成功！',U('typegd')):$this->error('修改失败！');
            }
            if(!$datas['type']||!$datas['oid']){$this->error('请选择要新增的位置！');}
            if($datas['level']>1&&$datas['type']>1){$this->error('资产类别最多只能添加两级！');}
            if($datas['type']==1)
            {
                $cat=M('Category')->where(array('id'=>$oid))->find();
                $oid=$cat['pid']?:0;
                $data['cate_name']=$datas['name'];
                $data['sn']=$datas['sn'];
                $data['netsalvage']=$datas['netsalvage'];
                $data['pid']=$oid;
                $data['add_time']=time();
                $data['type_id']=3;
            }else
            {
                $data['cate_name']=$datas['name'];
                $data['sn']=$datas['sn'];
                $data['netsalvage']=$datas['netsalvage'];
                $data['pid']=$datas['oid'];
                $data['add_time']=time();
                $data['type_id']=3;
            }
            $res=M('Category')->add($data);
            $res!==false?$this->success('保存成功！',-1):$this->error('保存失败！');
        }else
        {
            $arr=array();
            $cat=CAT('Category',array('id','pid','cate_name'),'type_id=3');
            $lists=$cat->getList(0);
            foreach($lists as $l)
            {
                $a=array();$a['id']=$l['id'];$a['pId']=$l['pid'];
                $a['name']=$l['cate_name'];$a['sn']=$l['sn'];
                $a['netsalvage']=$l['netsalvage'];
                array_push($arr,$a);
            }
            $arr=empty($arr)?'""':json_encode($arr);
            $this->assign('types',$arr);
            $this->display();
        }
    }

    //低值品分类
    public function typedz()
    {
        if(IS_POST)
        {
            $datas=I('post.');$data=array();
            if(!$datas['sn']){$this->error('编码不能为空！');}
            if(!$datas['name']){$this->error('名称不能为空！');}
            //如果type为空则是修改
            if (!$datas['type']&&$datas['oid']) {
                $saveData=array(
                    'cate_name'=>$datas['name'],
                    'sn'=>$datas['sn']
                );
                $re_f=M('Category')->where(array('id'=>$datas['oid']))->save($saveData);
                $re_f!==false?$this->success('修改成功！',U('typedz')):$this->error('修改失败！');
            }
            if(!$datas['type']||!$datas['oid']){$this->error('请选择要新增的位置！');}
            if($datas['level']>1&&$datas['type']>1){$this->error('资产类别最多只能添加两级！');}
            if($datas['type']==1)
            {
                $cat=M('Category')->where(array('id'=>$oid))->find();
                $oid=$cat['pid']?:0;
                $data['cate_name']=$datas['name'];
                $data['sn']=$datas['sn'];
                $data['pid']=$oid;
                $data['add_time']=time();
                $data['type_id']=2;
            }else
            {
                $data['cate_name']=$datas['name'];
                $data['sn']=$datas['sn'];
                $data['pid']=$datas['oid'];
                $data['add_time']=time();
                $data['type_id']=2;
            }
            $res=M('Category')->add($data);
            $res!==false?$this->success('保存成功！',-1):$this->error('保存失败！');
        }else
        {
            $arr=array();
            $cat=CAT('Category',array('id','pid','cate_name'),"type_id=2");
            $lists=$cat->getList(0);
            foreach($lists as $l)
            {
                $a=array();$a['id']=$l['id'];$a['pId']=$l['pid'];
                $a['name']=$l['cate_name'];$a['sn']=$l['sn'];
                array_push($arr,$a);
            }
            $arr=empty($arr)?'""':json_encode($arr);
            $this->assign('types',$arr);
            $this->display();
        }
    }

    //易耗品分类
    public function typeyh()
    {
        if(IS_POST)
        {
            $datas=I('post.');$data=array();
            if(!$datas['sn']){$this->error('编码不能为空！');}
            if(!$datas['name']){$this->error('名称不能为空！');}
            //如果type为空则是修改
            if (!$datas['type']&&$datas['oid']) {
                $saveData=array(
                    'cate_name'=>$datas['name'],
                    'sn'=>$datas['sn']
                );
                $re_f=M('Category')->where(array('id'=>$datas['oid']))->save($saveData);
                $re_f!==false?$this->success('修改成功！',U('typeyh')):$this->error('修改失败！');
            }
            if(!$datas['type']||!$datas['oid']){$this->error('请选择要新增的位置！');}
            if($datas['level']>1&&$datas['type']>1){$this->error('资产类别最多只能添加两级！');}
            if($datas['type']==1)
            {
                $cat=M('Category')->where(array('id'=>$oid))->find();
                $oid=$cat['pid']?:0;
                $data['cate_name']=$datas['name'];
                $data['sn']=$datas['sn'];
                $data['pid']=$oid;
                $data['add_time']=time();
                $data['type_id']=1;
            }else
            {
                $data['cate_name']=$datas['name'];
                $data['sn']=$datas['sn'];
                $data['pid']=$datas['oid'];
                $data['add_time']=time();
                $data['type_id']=1;
            }
            $res=M('Category')->add($data);
            $res!==false?$this->success('保存成功！',-1):$this->error('保存失败！');
        }else
        {
            $arr=array();
            $cat=CAT('Category',array('id','pid','cate_name'),"type_id=1");
            $lists=$cat->getList(0);
            foreach($lists as $l)
            {
                $a=array();$a['id']=$l['id'];$a['pId']=$l['pid'];
                $a['name']=$l['cate_name'];$a['sn']=$l['sn'];
                array_push($arr,$a);
            }
            $arr=empty($arr)?'""':json_encode($arr);
            $this->assign('types',$arr);
            $this->display();
        }
    }

    //删除类别
    public function typedel()
    {
        $id=I('post.oid');
        if(!$id){$this->error('没有选中任何选项！');}
        if(M('Category')->where("pid={$id}")->find()){$this->error('请先删除子项目！');}
        $res=M('Category')->where(array('id'=>$id))->delete();
        $res!==false?$this->success('删除成功！',-1):$this->error('删除失败！');
    }

    //添加
    public function add()
    {
    	if(IS_POST)
        {
            $data=I('post.');
            if(!$data['assets_name']){$this->error('物品名称不能为空！');}
            if(!$data['unit']){$this->error('物品单位不能为空！');}
            if(!$data['cate_id']){$this->error('资产分类不能为空！');}
            $cat=M('Category')->where(array('id'=>$data['cate_id']))->find();
            $data['top_id']=$cat['pid'];
            $data['add_time']=time();$data['update_time']=time();
            $res=M('BaseGoods')->add($data);
            $res!==false?$this->success('添加成功！',U('index')):$this->error('添加失败！');
        }else
        {
            $this->assign('types',$this->getType());
            $this->assign('cates',$this->getCate());
            $this->display();
        }
    }

    //导入数据
    public function import($datas)
    {   
        // // print_r(count($datas));
        // $M=M('BaseGoods');
        // // $M->startTrans();
        // foreach ($datas as $key => $value) {
        //     // \Think\Log::write($key,'WARN');
        //     if (empty($value)) {
        //         continue;
        //     }
        //     // $has=$M->where(array('assets_name'=>$value['assets_name']))->count();
        //     // if ($has) {
        //     //     continue;
        //     // }
        //     // $cArray=explode('/', $value['cate_name']);
        //     // $last=array_pop($cArray);
            
        //     // $lname=trim($last);

        //     // $cate_id=M('Category')->where(array('cate_name'=>$value['cate_name'],'type_id'=>1))->getField('id');
        //     // if (!$cate_id) {
        //     //     continue;
        //     // }
        //     // $data=array(
        //     //     'assets_name'=>$value['assets_name'],
        //     //     'cate_id'=>$cate_id,
        //     //     'add_time'=>time(),
        //     //     'type_id'=>1,
        //     //     'unit'=>$value['unit']
        //     // );
        //     // $re=$M->add($data);
        //     // if (!$re) {
        //     //     $M->rollabck();
        //     //     exit();
        //     // }
        // }
        // // if ($re) {
        // //     $M->commit();
        // // }else{
        // //     $M->rollabck();
        // // }
        
    }

    // public function addCate()
    // {
    //     // if (IS_POST) {
    //     //     $d=I('d');
    //     //     if ($d) {
    //     //         foreach ($d as $key => $value) {
    //     //             $value['depart_name']=trim(str_replace('－', '', $value['depart_name']));
    //     //             $re=M('Depart')->add($value);
    //     //         }
    //     //     }else{
    //     //          print_r('----');
    //     //     }
    //     // }else{
    //     //     $this->display();
    //     // }
    //     $lists=M('Depart')
    //     // ->page(3,100)
    //     // ->where(array('type_id'=>1))
    //     ->order('id asc')
    //     ->select();
    
    //     foreach ($lists as $key => $value) {
    //         if (strlen($value['depart_no'])==11) {
    //             $s=$value;
    //             $sn=substr($value['depart_no'], 0,8);
    //             $pid=M('Depart')->where(array('depart_no'=>$sn))->getField('id');
    //             M('Depart')->where(array('id'=>$value['id']))->save(array('pid'=>$pid));
    //         }
    //     }
    //     var_dump($s);
    // }

    //编辑
    public function edit()
    {
    	if(IS_POST)
        {
            $data=I('post.');
            if(!$data['assets_name']){$this->error('物品名称不能为空！');}
            if(!$data['unit']){$this->error('物品单位不能为空！');}
            if(!$data['cate_id']){$this->error('资产分类不能为空！');}
            $cat=M('Category')->where(array('id'=>$data['cate_id']))->find();
            $data['top_id']=$cat['pid'];
            $data['update_time']=time();
            $res=M('BaseGoods')->save($data);
            $res!==false?$this->success('修改成功！',U('index')):$this->error('修改失败！');
        }else
        {
            $id=I('get.id');
            $info=M('BaseGoods')->where(array('id'=>$id))->find();

            $this->assign('types',$this->getType());
            $this->assign('cates',$this->getCate());
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
        $res=M('BaseGoods')->where($where)->delete();
        $res!==false?$this->success('删除成功！',U('index')):$this->error('删除失败！');
    }

    private function getType()
    {
        return M('Type')->select();
    }

    private function getCate()
    {
        $cat=CAT('Category',array('id','pid','cate_name'));
        return $cat->getList(0);
    }

    public function ajaxgetcate()
    {
        $type_id=I('type_id');
        !$type_id&&$this->error('参数错误！');
        $cat=CAT('Category',array('id','pid','cate_name'),"type_id=$type_id");
        $this->ajaxReturn($cat->getList(0));
    }

    private function count($arr,$f='',$d='')
    {
        $n=0;
        foreach($arr as $k=>$v){if($v[$f]==$d){$n++;}}
        return $n;
    }
}
?>