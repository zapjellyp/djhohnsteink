<?php

namespace Addons\InsertImage;

use Common\Controller\Addon;

/**
 * 插入图片插件
 * @author 想天软件工作室
 */
class InsertImageAddon extends Addon
{

    public $info = array(
        'name' => 'InsertImage',
        'title' => '插入图片',
        'description' => '微博上传图片',
        'status' => 1,
        'author' => '想天软件工作室',
        'version' => '0.1'
    );

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    //实现的InsertImage钩子方法
    public function weiboType($param)
    {

        $this->display('insertImage');
    }

    public function fetchImage($weibo)
    {

        $weibo_data = unserialize($weibo['data']);
        $weibo_data['attach_ids'] = explode(',', $weibo_data['attach_ids']);

        foreach ($weibo_data['attach_ids'] as $k_i => $v_i) {
            if(strtolower(C('PICTURE_UPLOAD_DRIVER'))=='sae'){
                $weibo_data['image'][$k_i]['small'] =  getThumbImageById($v_i, 100, 100);
                // dump( $weibo_data['image'][$k_i]['small']);exit;
                $bi = M('Picture')->where(array('status' => 1))->getById($v_i);
                $weibo_data['image'][$k_i]['big'] = $bi['path'];
            }else{
                $weibo_data['image'][$k_i]['small'] = '/' . getThumbImageById($v_i, 100, 100);
                $bi = M('Picture')->where(array('status' => 1))->getById($v_i);
                $weibo_data['image'][$k_i]['big'] =  '/' . $bi['path'];
            }


            $param['weibo'] = $weibo;
            $param['weibo']['weibo_data'] = $weibo_data;
        }

        $this->assign($param);
        return $this->fetch('display');
    }

}