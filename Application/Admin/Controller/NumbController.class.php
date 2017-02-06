<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-12-04 13:47:37
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
/**
 * 
 */
class NumbController extends BaseController
{
    //列表
    public function index()
    {
        if(IS_POST)
        {
            $file=CONF_PATH.'sn_config.php';
            $post=I('post.');
            $str='<?php return array('.A2S($post).');?>';
            $res=file_put_contents($file,$str);
            $res!==false?$this->success('保存成功！',U('index')):$this->error('保存失败！');
        }else
        {
            $this->display();
        }
    }
}
?>