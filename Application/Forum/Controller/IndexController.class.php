<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-8
 * Time: PM4:30
 */

namespace Forum\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function index($page = 1)
    {
        redirect(U('forum?id=1', array('page' => $page)));
    }

    public function forum($id, $page = 1)
    {
        $this->requireForumAllowView($id);
        //读取帖子列表
        $map = array('forum_id' => $id, 'status' => 1);
        $list = D('ForumPost')->where($map)->order('last_reply_time desc')->page($page, 10)->select();
        $totalCount = D('ForumPost')->where($map)->count();
        //读取置顶列表

        $list_top = D('ForumPost')->where('is_top=1 OR is_forum_top=1')->order('is_top desc,is_forum_top desc,last_reply_time desc')->select();


        //显示页面
        $this->assign('forum_id', $id);
        $this->assign('list', $list);
        $this->assign('list_top', $list_top);
        $this->assign('totalCount', $totalCount);
        $this->display();
    }

    public function detail($id, $page = 1)
    {
        //读取帖子内容
        $post = D('ForumPost')->where(array('id' => $id, 'status' => 1))->find();
        if (!$post) {
            $this->error('找不到该帖子');
        }

        //增加浏览次数
        D('ForumPost')->where(array('id' => $id))->setInc('view_count');

        //读取回复列表
        $map = array('post_id' => $id, 'status' => 1);
        $replyList = D('ForumPostReply')->where($map)->order('create_time asc')->page($page, 10)->select();
        $replyTotalCount = D('ForumPostReply')->where($map)->count();

        //判断是否需要显示1楼
        if ($page == 1) {
            $showMainPost = true;
        } else {
            $showMainPost = false;
        }

        //判断是否已经收藏
        $isBookmark = D('ForumBookmark')->exists(is_login(), $id);

        //显示页面
        $this->assign('isBookmark', $isBookmark);
        $this->assign('forum_id', $post['forum_id']);
        $this->assign('post', $post);
        $this->assign('replyList', $replyList);
        $this->assign('replyTotalCount', $replyTotalCount);
        $this->assign('showMainPost', $showMainPost);
        $this->display();
    }

    public function edit($forum_id = null, $post_id = null)
    {
        //检查参数
        if (!$forum_id && !$post_id) {
            $this->error('参数错误，两个参数不能同时为空');
        }
        //确认用户已经登录
        $this->requireLogin();
        //判断是不是为编辑模式
        $isEdit = $post_id ? true : false;
        //如果是编辑模式的话，读取帖子，并判断帖子是否为自己的
        if ($isEdit) {
            $post = D('ForumPost')->where(array('id' => $post_id, 'status' => 1))->find();
            $this->requireAllowEditPost($post_id);
        } else {
            $post = array('forum_id' => $forum_id);
        }
        //确认贴吧能发帖
        if ($forum_id) {
            $this->requireForumAllowPublish($forum_id);
        }

        //获取贴吧编号
        $forum_id = $forum_id ? $forum_id : $post['forum_id'];

        //显示页面
        $this->assign('forum_id', $forum_id);
        $this->assign('post', $post);
        $this->assign('isEdit', $isEdit);
        $this->display();
    }

    public function doEdit($post_id = null, $forum_id, $title, $content)
    {
        //确认用户已经登录
        $this->requireLogin();
        //判断是不是编辑模式
        $isEdit = $post_id ? true : false;
        //如果是编辑模式，确认当前用户能编辑帖子
        if ($isEdit) {
            $this->requireAllowEditPost($post_id);
        }
        //确认当前贴吧能发帖
        $this->requireForumAllowPublish($forum_id);
        //写入帖子的内容
        $model = D('ForumPost');
        if ($isEdit) {
            $data = array('id' => $post_id, 'title' => $title, 'content' => $content, 'parse' => 0);
            $result = $model->editPost($data);
            if (!$result) {
                $this->error('编辑失败：' . $model->getError());
            }
        } else {
            $data = array('uid' => is_login(), 'title' => $title, 'content' => $content, 'parse' => 0, 'forum_id' => $forum_id);
            $result = $model->createPost($data);
            if (!$result) {
                $this->error('发表失败：' . $model->getError());
            }
            $post_id = $result;
        }
        //显示成功消息
        $message = $isEdit ? '编辑成功' : '发表成功';
        $this->success($message, U('Forum/Index/detail', array('id' => $post_id)));
    }

    public function doReply($post_id, $content)
    {
        //确认有权限回复
        $this->requireAllowReply($post_id);
        //添加到数据库
        $model = D('ForumPostReply');
        $result = $model->addReply($post_id, $content);
        if (!$result) {
            $this->error('回复失败：' . $model->getError());
        }
        //显示成功消息
        $this->success('回复成功');
    }

    public function doBookmark($post_id, $add = true)
    {
        //确认用户已经登录
        $this->requireLogin();

        //写入数据库
        if ($add) {
            $result = D('ForumBookmark')->addBookmark(is_login(), $post_id);
            if (!$result) {
                $this->error('收藏失败');
            }
        } else {
            $result = D('ForumBookmark')->removeBookmark(is_login(), $post_id);
            if (!$result) {
                $this->error('取消失败');
            }
        }

        //返回成功消息
        if ($add) {
            $this->success('收藏成功');
        } else {
            $this->success('取消成功');
        }
    }

    private function requireLogin()
    {
        if (!is_login()) {
            $this->error('需要登录才能操作');
        }
    }

    private function requireForumAllowPublish($forum_id)
    {
        $this->requireForumExists($forum_id);
        $this->requireLogin();
    }

    private function requireAllowEditPost($post_id)
    {
        $this->requirePostExists($post_id);
        $this->requireLogin();

        //确认帖子时自己的
        $post = D('ForumPost')->where(array('id' => $post_id, 'status' => 1))->find();
        if ($post['uid'] != is_login()) {
            $this->error('没有权限编辑帖子');
        }
    }

    private function requireForumAllowView($forum_id)
    {
        $this->requireForumExists($forum_id);
    }

    private function requireForumExists($forum_id)
    {
        $forum = D('Forum')->where(array('id' => $forum_id, 'status' => 1));
        if (!$forum) {
            $this->error('贴吧不存在');
        }
    }

    private function requireAllowReply($post_id)
    {
        $this->requirePostExists($post_id);
        $this->requireLogin();
    }

    private function requirePostExists($post_id)
    {
        $post = D('ForumPost')->where(array('id' => $post_id))->find();
        if (!$post) {
            $this->error('帖子不存在');
        }
    }
}