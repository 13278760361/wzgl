<?php
namespace Qing;
//分类管理
class Category
{
    private $model;
    private $condition='';
    private $order='';
    private $rawList=array();
    private $formatList=array();
    private $icon=array('&nbsp;&nbsp;│', '&nbsp;&nbsp;├ ', '&nbsp;&nbsp;└ ');
    private $fields=array();
    public function __construct($model='',$fields=array(),$condition='',$order='')
    {
        $this->model=$model?is_object($model)?$model:M($model):'';
        $this->fields['cid']=$fields['0']?:'id';
        $this->fields['fid']=$fields['1']?:'pid';
        $this->fields['name']=$fields['2']?:'name';
        $this->fields['fullname']=$fields['3']?:'fullname';
        $this->condition=$condition?:'1=1';
        $this->order=$order?:$this->fields['cid'];
    }

    //获取行的子级行
    public function getChild($fid,$data='')
    {
        $childs=array();
        if($data){
            if(is_array($data)){$this->rawList=$data;}else{if(!$this->rawList){$this->_findAllCat();}}
        }else{
            if(!$this->rawList){$this->_findAllCat();}
        }
        foreach($this->rawList as $c){if($c[$this->fields['fid']]==$fid){$childs[]=$c;}}
        return $childs;
    }

    //获取所有子级
    public function getChildren($cid,$data='')
    {
        $info=$this->getInfo($cid,$data);
        $info = array($info);
        $childs=$this->getChild($cid,$data);
        if($childs)
        {
            foreach($childs as $c)
            {
                $ch=$this->getChildren($c[$this->fields['cid']],$data);
                $info=array_merge($info,$ch);
            }
            
        }
        return $info;
    }

    //通过类id获取行内容
    public function getInfo($cid,$data='')
    {
        if($data){
            if(is_array($data)){$this->rawList=$data;}else{if(!$this->rawList){$this->_findAllCat();}}
        }else{
            if(!$this->rawList){$this->_findAllCat();}
        }
        foreach($this->rawList as $c){if($c[$this->fields['cid']]==$cid){return $c;}}
        return array();
    }

    //获取栏目下所有子栏目的列表
    public function getList($cid=0,$data='',$num=5)
    {
        unset($this->rawList,$this->formatList);
        if($data){
            if(is_array($data)){$this->rawList=$data;}else{$this->_findAllCat();}
        }else{
            $this->_findAllCat();
        }
        $this->_searchList($cid,'','',$num);
        return $this->formatList;
    }

    //获取栏目下所有子栏目的树形结构
    public function getTree($cid=0,$data='',$field='_child')
    {
        unset($this->rawList,$this->formatList);
        if($data){
            if(is_array($data)){$this->rawList=$data;}else{$this->_findAllCat();}
        }else{
            $this->_findAllCat();
        }
        return $this->_searchTree($cid,$field);
    }

    //获取栏目所有父栏目
    public function getPath($cid,$data='')
    {
        unset($this->rawList,$this->formatList);
        if($data){if(is_array($data)){$this->rawList=$data;}else{$this->_findAllCat($data);}}else{$this->_findAllCat();}
        $cat=$this->getInfo($cid);
        $this->_searchPath($cat[$this->fields['fid']]);//查询分类路径
        return $this->formatList?array_reverse($this->formatList):array();
    }

    //调用模型，查询数据
    private function _findAllCat($condition='')
    {
        $condition=$condition?:$this->condition?:'1=1';
        $this->rawList=$this->model?$this->model->where($condition)->order($this->order)->select():array();
    }

    //递归获取子级行，组成二维数组
    private function _searchList($cid=0,$space="",$path='',$num=5,$curnum=1)
    {
        if($curnum>$num){return;}
        $childs=$this->getChild($cid);
        if(!($n=count($childs))){return;}
        $curnum++;$m=1;
        for($i=0;$i<$n;$i++)
        {
            $pre=$n==$m?$this->icon[2]:$this->icon[1];
            $pad=$n==$m?"":($space?$this->icon[0]:"");
            $childs[$i][$this->fields['fullname']]=($space?$space.$pre:"").$childs[$i][$this->fields['name']];
            $childs[$i]['path']=trim($path."-"."$cid",'-');
            $this->formatList[]=$childs[$i];
            $this->_searchList($childs[$i][$this->fields['cid']],$space.$pad."&nbsp;&nbsp;",$childs[$i]['path'],$num,$curnum);
            $m++;
        }
    }

    //递归获取子级行，组成多位数组
    private function _searchTree($pid=0,$field='_child',$s="",$p='',$t=5,$c=1)
    {
        $arr=array();$icon=$this->icon;
        if($c>$t){return array();}
        $m=0;$n=count($this->getChild($pid));
        foreach($this->rawList as $ch)
        {
            if($ch[$this->fields['fid']]==$pid)
            {
                $m++;
                $ch['path']=trim($p.'-'.$pid,'-');
                $r=$n==$m?$icon[2]:$icon[1];$d=$n==$m?"":($s?$icon[0]:"");
                $ch[$this->fields['fullname']]=($s?$s.$r:"").$ch[$this->fields['name']];
                $ch[$field]=$this->_searchTree($ch[$this->fields['cid']],$field,$s.$d."&nbsp;&nbsp;",$ch['path'],$t,$c);
                $arr[]=$ch;
            }
        }
        return $arr;
    }

    //递归获取父级行
    private function _searchPath($pid)
    {
        $parent=$this->getInfo($pid);
        if(!$parent){return;}
        $this->formatList[]=$parent;
        $this->_searchPath($parent[$this->fields['fid']]);
    }
}
?>