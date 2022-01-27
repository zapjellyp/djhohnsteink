<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Forum\Widget;

use Think\Action;

/**
 * 分类widget
 * 用于动态调用分类信息
 */
class HotPostWidget extends Action
{

    /* 显示指定分类的同级分类或子分类列表 */
    public function lists($forum_id)
    {
        $posts = S('forum_hot_posts_' . $forum_id);
        if (empty($posts)) {
            if ($forum_id) {
                $posts = D('ForumPost')->where(array('forum_id'=>$forum_id, 'status'=>1))->order('reply_count desc')->limit(9)->select();
            } else {
                $posts = D('ForumPost')->where(array('status'=>1))->order('reply_count desc')->limit(9)->select();
            }
            S('forum_hot_posts_' . $forum_id, $posts, 300);
        }

        $this->assign('posts', $posts);
        $this->display('Widget/hot');

    }

}
