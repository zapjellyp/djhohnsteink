<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-10
 * Time: PM9:14
 */

namespace Weibo\Controller;

use Think\Controller;
use Weibo\Api\WeiboApi;
use Think\Exception;
use Common\Exception\ApiException;

class IndexController extends Controller
{
    /**
     * 业务逻辑都放在 WeiboApi 中
     * @var
     */
    private $weiboApi;

    public function _initialize()
    {
        $this->weiboApi = new WeiboApi();
    }

    public function index()
    {
        //载入第一页微博
        $result = $this->weiboApi->listAllWeibo();

        //显示页面
        $this->assign('list', $result['list']);
        $this->assign('tab', 'all');
        $this->assign('loadMoreUrl', U('loadWeibo'));
        $this->assignSelf();
        $this->assignAtWhoUsers();
        $this->display();
    }

    public function myconcerned()
    {
        //载入我关注的微博
        $result = $this->weiboApi->listMyFollowingWeibo();

        //显示页面
        $this->assign('list', $result['list']);
        $this->assign('tab', 'concerned');
        $this->assign('loadMoreUrl', U('loadConcernedWeibo'));
        $this->assignSelf();
        $this->assignAtWhoUsers();
        $this->display('index');
    }

    public function weiboDetail($id)
    {
        //读取微博详情
        $result = $this->weiboApi->getWeiboDetail($id);

        //显示页面
        $this->assign('weibo', $result['weibo']);
        $this->assignSelf();
        $this->assignAtWhoUsers();
        $this->display();
    }

    public function loadWeibo($page = 1)
    {
        //载入全站微博
        $result = $this->weiboApi->listAllWeibo($page);

        //如果没有微博，则返回错误
        if (!$result['list']) {
            $this->error('没有更多了');
        }

        //返回html代码用于ajax显示
        $this->assign('list', $result['list']);
        $this->display();
    }

    public function loadConcernedWeibo($page = 1)
    {
        //载入我关注的人的微博
        $result = $this->weiboApi->listMyFollowingWeibo($page);

        //如果没有微博，则返回错误
        if (!$result['list']) {
            $this->error('没有更多了');
        }

        //返回html代码用于ajax显示
        $this->assign('list', $result['list']);
        $this->display('loadweibo');
    }

    public function doSend($content)
    {
        //发送微博
        $result = $this->weiboApi->sendWeibo($content);

        //返回成功结果
        $this->ajaxReturn(apiToAjax($result));
    }

    public function doComment($weibo_id, $content, $comment_id = 0)
    {
        //发送评论
        $result = $this->weiboApi->sendComment($weibo_id, $content, $comment_id);

        //返回成功结果
        $this->ajaxReturn(apiToAjax($result));
    }

    public function loadComment($weibo_id)
    {
        //读取数据库中全部的评论列表
        $result = $this->weiboApi->listComment($weibo_id, 1, 10000);
        $list = $result['list'];
        $weiboCommentTotalCount = count($list);

        //返回html代码用于ajax显示
        $this->assign('list', $list);
        $this->assign('weiboId', $weibo_id);
        $this->assign('weiboCommentTotalCount', $weiboCommentTotalCount);
        $this->display();
    }

    /**
     * @param $user
     * @return array
     */
    private function getAtWhoUsers()
    {
        //获取能AT的人，UID列表
        $uid = get_uid();
        $follows = D('Follow')->where(array('who_follow'=>$uid,'follow_who'=>$uid,'_logic'=>'or'))->limit(999)->select();
        $uids = array();
        foreach($follows as &$e) {
            $uids[] = $e['who_follow'];
            $uids[] = $e['follow_who'];
        }
        unset($e);
        $uids = array_unique($uids);

        //加入拼音检索
        $users = array();
        foreach($uids as $uid) {
            $user = query_user(array('username','id'),$uid);
            $user['search_key'] = $user['username'].D('PinYin')->Pinyin($user['username']);
            $users[] = $user;
        }

        //返回at用户列表
        return $users;
    }

    public function doDelWeibo($weibo_id = 0)
    {
        if (intval($weibo_id)) {

            if (is_administrator()) {
                $del = D('Weibo')->where(array('id' => $weibo_id))->setField('status', 0); //管理员即可直接删除
            } else {
                $del = D('Weibo')->where(array('id' => $weibo_id, 'uid' => is_login()))->setField('status', 0); //删除带检测权限
            }
            if ($del) {
                D('WeiboComment')->where(array('weibo_id' => $weibo_id))->setField('status', 0);
            }
            exit(json_encode(array('status' => $del)));
        }
    }

    public function doDelComment($comment_id = 0)
    {
        if (intval($comment_id)) {
            if (is_administrator()) {
                $del = D('WeiboComment')->where(array('id' => $comment_id))->setField('status', 0); //管理员即可直接删除
            } else {
                $del = D('WeiboComment')->where(array('id' => $comment_id, 'uid' => is_login()))->setField('status', 0); //先删除带检测权限
            }
            if ($del) {
                $comment = D('WeiboComment')->find($comment_id);
                $count = D('WeiboComment')->where(array('weibo_id' => $comment['weibo_id'], 'status' => 1))->count();
                D('Weibo')->where(array('id' => $comment['weibo_id']))->setField('comment_count', $count);
            }
            exit(json_encode(array('status' => $del)));
        }
    }

    private function assignAtWhoUsers()
    {
        $cacheKey = 'weibo_at_who_users_' . get_uid();
        $atusers = op_cache($cacheKey, function(){
            return $this->getAtWhoUsers();
        }, 600);
        $this->assign('atwhousers', $atusers);
    }

    private function assignSelf()
    {
        $self = query_user(array('avatar128', 'username', 'uid', 'space_url', 'icons_html', 'score', 'title', 'fans', 'following', 'weibocount', 'rank_link'));
        $this->assign('self', $self);
    }
}