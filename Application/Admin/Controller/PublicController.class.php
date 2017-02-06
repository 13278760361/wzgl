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
    public function _initialize()
    {
        $c=isMobile()?C('mobile'):C('default');foreach($c as $k=>$v){C($k,$v);}
        
    }
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
                if($_SESSION['url']&&isMobile()){$this->success('登录成功！',$_SESSION['url']);}else{
                $this->success('登录成功！',U('Index/index'));}
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
        session('url','');
        redirect(U('login'));
    }


    //获取登录用户权限
    public function getPerms()
    {
        $info=session('admin');
        if($info['info']['id']>0)
        {
            $group=M()->field('g.*')->table('k_admin_group g,k_admin_group_access gs')->where("gs.group_id=g.id AND gs.uid=".$info['info']['id'])->find();
            $rules=$group['rules'];
            if ($rules) {
                $perms=M('AdminRule')->where("status=1 AND id IN ({$rules})")->order('sort asc')->select();
            }else{
                $perms=array();
            }
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
    public function getCate(){
        return M('category')->select();
    }
    private function otherNodes()
    {
        return array(
            array('id'=>-1,'name'=>'Admin/Main/index','add'=>1),
            array('id'=>-1,'name'=>'Admin/Index/online','add'=>1),
            // array('id'=>-1,'name'=>'Admin/Role/getNode','add'=>1),
            array('id'=>-1,'name'=>'Admin/Role/getNode','add'=>1),
            array('id'=>-1,'name'=>'Admin/Exchange/goodsList','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowExchange/goodsList','add'=>1),
            array('id'=>-1,'name'=>'Admin/Calculate/assApi','add'=>1),
            array('id'=>-1,'name'=>'Admin/Calculate/deprApi','add'=>1),

            array('id'=>-1,'name'=>'Admin/Cards/get_goods','add'=>1),
            
            array('id'=>-1,'name'=>'Admin/Cards/depr','add'=>1),
            array('id'=>-1,'name'=>'Admin/Cards/ajaxindex','add'=>1),
            array('id'=>-1,'name'=>'Admin/Cards/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowCards/ajaxgetcate','add'=>1),

            array('id'=>-1,'name'=>'Admin/Cards/qrcode_list','add'=>1),
            array('id'=>-1,'name'=>'Admin/Cards/del','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowCards/get_goods','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowCards/depr','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowCards/ajaxindex','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowCards/qrcode_list','add'=>1),
            
            array('id'=>-1,'name'=>'Admin/Purchase/get_goods','add'=>1),
            array('id'=>-1,'name'=>'Admin/Purchase/ajaxindex','add'=>1),
            array('id'=>-1,'name'=>'Admin/Scrap/getData','add'=>1),
            array('id'=>-1,'name'=>'Admin/Weixiu/goodsList','add'=>1),

            array('id'=>-1,'name'=>'Admin/Products/ajaxgetcate','add'=>1),

            array('id'=>-1,'name'=>'Admin/User/ajaxindex','add'=>1),
            array('id'=>-1,'name'=>'Admin/Role/ajaxindex','add'=>1),

            
            array('id'=>-1,'name'=>'Admin/Scrap/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/Scrap/ajaxScrap','add'=>1),
            array('id'=>-1,'name'=>'Admin/DScrap/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/ScrapApply/ajaxScrapApply','add'=>1),
            array('id'=>-1,'name'=>'Admin/Text/index','add'=>1),
            array('id'=>-1,'name'=>'Admin/StockInv/ajaxSv','add'=>1),
            array('id'=>-1,'name'=>'Admin/Entstock/import','add'=>1),

            array('id'=>-1,'name'=>'Admin/BarCode/doCode','add'=>1),

            array('id'=>-1,'name'=>'Admin/Weixiu/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowWeixiu/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/Exchange/ajaxindex','add'=>1),//资产变更
            array('id'=>-1,'name'=>'Admin/Exchange/ajaxauditlist','add'=>1),//资产变更
            array('id'=>-1,'name'=>'Admin/LowExchange/ajaxindex','add'=>1),//资产变更
            array('id'=>-1,'name'=>'Admin/LowExchange/ajaxauditlist','add'=>1),//资产变更
            array('id'=>-1,'name'=>'Admin/Exchange/ajaxgetcate','add'=>1),//资产变更
            array('id'=>-1,'name'=>'Admin/LowExchange/ajaxgetcate','add'=>1),//资产变更
            array('id'=>-1,'name'=>'Admin/Accep/get_goods','add'=>1),
            array('id'=>-1,'name'=>'Admin/Supplier/ajaxindex','add'=>1),
            array('id'=>-1,'name'=>'Admin/Receive/ajaxindex','add'=>1),
            array('id'=>-1,'name'=>'Admin/Weixiu/ajaxindex','add'=>1),
            array('id'=>-1,'name'=>'Admin/Weixiu/ajaxcheck','add'=>1),
            array('id'=>-1,'name'=>'Admin/Receive/ajaxcheck','add'=>1),
            array('id'=>-1,'name'=>'Admin/Supplier/ajaxList','add'=>1),
            array('id'=>-1,'name'=>'Admin/Supplier/tiaoma','add'=>1),
            array('id'=>-1,'name'=>'Admin/Weixiu/goodsList','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowWeixiu/goodsList','add'=>1),
            array('id'=>-1,'name'=>'Admin/Receive/goodsList','add'=>1),
            array('id'=>-1,'name'=>'Admin/LowWeixiu/ajaxSignature','add'=>1),
            array('id'=>-1,'name'=>'Admin/Weixiu/ajaxSignature','add'=>1),
            
            array('id'=>-1,'name'=>'Admin/Entstock/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/Receive/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/Outstock/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/StockInv/ajaxgetcate','add'=>1),
            array('id'=>-1,'name'=>'Admin/Purchase/ajaxgetcate','add'=>1),

            array('id'=>-1,'name'=>'Admin/Calculate/ajaxgetcate','add'=>1),
        );


    }
}
?>