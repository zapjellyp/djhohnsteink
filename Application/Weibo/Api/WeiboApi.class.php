<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 4/2/14
 * Time: 9:14 AM
 */

namespace Weibo\Api;

use Common\Api\Api;
use Common\Exception\ApiException;

class WeiboApi extends Api
{
    private $weiboModel;

    public function __construct()
    {
        // 模型名称请使用完整路径，否则其他应用中无法调用接口。
        $this->weiboModel = D('Weibo/Weibo');
        $this->followModel = D('Weibo/Follow');
    }

    public function listAllWeibo($page = 1, $count = 10)
    {
        //获取微博列表
        $map = array('status' => 1);
        $model = $this->weiboModel;
        $list = $model->where($map)->order('create_time desc')->page($page, $count)->select();

        //确认正确获取了微博列表
        if (!$list) {
            return $this->apiError('没有更多微博了');
        }

        //获取每个微博详情
        foreach ($list as &$e) {
            $e = $this->getWeiboStructure($e['id']);
        }
        unset($e);

        //返回微博列表
        return $this->apiSuccess('获取成功', array('list' => arrayval($list)));
    }

    public function listMyFollowingWeibo($page = 1, $count = 10)
    {
        $this->requireLogin();

        //获取我关注的人
        $result = $this->followModel->where(array('who_follow' => get_uid()))->select();
        foreach ($result as &$e) {
            $e = $e['follow_who'];
        }
        unset($e);
        $followList = $result;
        $followList[] = is_login();

        //获取我关注的微博
        $list = D('Weibo')->where('status=1 and uid in(' . implode(',', $followList) . ')')->order('id desc')->page($page, $count)->select();

        //获取每个微博的详细信息
        foreach ($list as &$e) {
            $e = $this->getWeiboStructure($e['id']);
        }
        unset($e);

        //返回我关注的微博列表
        return $this->apiSuccess('获取成功', array('list' => arrayval($list)));
    }

    public function getWeiboDetail($weibo_id)
    {
        $this->requireWeiboExist($weibo_id);

        //获取微博详情
        $weibo = $this->getWeiboStructure($weibo_id);

        //返回微博详情
        return $this->apiSuccess('获取成功', array('weibo' => $weibo));
    }

    public function sendWeibo($content)
    {
        $this->requireLogin();

        //写入数据库
        $model = $this->weiboModel;
        $score_before = getMyScore();
        $result = $model->addWeibo(is_login(), $content);
        $score_after = getMyScore();
        if (!$result) {
            throw new ApiException('发布失败：' . $model->getError());
        }

        //显示成功页面
        $message = '发表微博成功。' . getScoreTip($score_before, $score_after);
        $score_increase = $score_after - $score_before;
        return $this->apiSuccess($message, array('score_increase'=>$score_increase));
    }

    private function getWeiboStructure($id)
    {
        $weibo = $this->weiboModel->where(array('id' => $id))->find();
        return array(
            'id' => intval($weibo['id']),
            'content' => strval($weibo['content']),
            'create_time' => intval($weibo['create_time']),
            'comment_count' => intval($weibo['comment_count']),
            'user' => $this->getUserStructure($weibo['uid']),
        );
    }

    private function requireLogin()
    {
        if (!is_login()) {
            throw new ApiException('需要登录', 400);
        }
    }

    private function requireWeiboExist($id)
    {
        $weibo = $this->weiboModel->where(array('id' => $id, 'status' => 1))->find();
        if (!$weibo) {
            throw new ApiException('微博不存在');
        }
    }
}