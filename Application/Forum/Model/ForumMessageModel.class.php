<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/16/14
 * 创建时间: 10:56 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Forum\Model;

use Common\Model\IMessage;
use Think\Model;

class ForumMessageModel extends Model implements IMessage
{
    /**
     * @param $id
     * @param $source_id
     * @param string $type
     * @return mixed
     */
    /*public function getData($id, $source_id, $type = 'reply')
    {

        $message = D('Message')->find($id);
        $messages = array();
        $source = array();
        if (strtolower($type) == 'reply') {
            $post = D('forum_post')->find($message['source_id']);
            $user = query_user(array('space_link', 'avatar64'), $post['uid']);
            $source['content'] = '<h2>[标题] ' . op_t($post['title']) . '</h2><p>[内容] ' . op_t($post['content']) . '</p><div class="pull-right"><img class="avatar-img" style="width:64px" src="' . $user['avatar64'] . '" ucard="' . $post['uid'] . '"</div>';
            $source['url'] = U('Forum/Index/detail', array('id' => $post['id']));
            //查找来源用户和当前用户的全部
            $forum_reply = D('forum_post_reply')->find($message['find_id']); //查找回帖信息
            $message['uid'] = $forum_reply['uid'];
            $message['ctime'] = friendlyDate($forum_reply['create_time']);
            $user = query_user(array('avatar128', 'username', 'space_url'), $forum_reply['uid']);
            $message = array_merge($user, $message);
            $message['content'] = $forum_reply['content'];

            $messages[] = $message;
        } else {
            $post = D('forum_post')->find($message['source_id']);
            $user = query_user(array('space_link', 'avatar64'), $post['uid']);
            $source['content'] = '<h2>[标题] ' . op_t($post['title']) . '</h2><p>[内容] ' . op_t($post['content']) . '</p><div class="pull-right"><img class="avatar-img" style="width:64px" src="' . $user['avatar64'] . '" ucard="' . $post['uid'] . '"</div>';
            $source['url'] = U('Forum/Index/detail', array('id' => $post['id']));
            //查找来源用户和当前用户的全部
            $forum_reply = D('forum_post_reply')->find($message['find_id']); //查找回帖信息
            $message['uid'] = $forum_reply['uid'];
            $message['ctime'] = friendlyDate($forum_reply['create_time']);
            $user = query_user(array('avatar128', 'username', 'space_url'), $forum_reply['uid']);
            $message = array_merge($user, $message);
            $message['content'] = $forum_reply['content'];
            $messages[] = $message;


        }
        $data['messages'] = $messages;
        $data['source'] = $source;
        return $data;
    }*/

    /**获取聊天源，一般用于创建聊天时对顶部来源进行赋值
     * @param $message
     * @return mixed
     */
    public function getSource($message)
    {
        if ($message['apptype'] == 'reply') {
            $post = D('ForumPost')->find($message['source_id']);
            $source['source_title'] = $post['title'];
            $source['source_content'] = $post['content'];
            $source['source_url'] = U('Forum/Index/detail', array('id' => $post['id']));
            $source['title'] = '基于' . $post['title'] . '的贴内对话';
        }

        return $source;
    }

    /**获得查找的内容，在第一次创建会话的时候获取第一个聊天的内容时触发
     * @param $message
     * @return mixed
     */
    public function getFindContent($message)
    {
        if ($message['apptype'] == 'reply') {
            $reply = D('ForumPostReply')->find($message['find_id']);
            return $reply['content'];
        }
    }

    /**在自己发送聊天消息的时候被触发，一般用于同步内容到对应的应用
     * @param $source_message
     * @param $talk
     * @param $content
     * @return array
     */
    public function postMessage($source_message, $talk, $content)
    {
        $lzlReplys = array();

        $uids = D('Talk')->getUids($talk['uids']);
        foreach ($uids as $uid) {
            if ($uid != is_login()) {
                $user = query_user(array('username'), $uid);
                $lzlReplys[] = D('Forum/ForumLzlReply')->addLZLReply($source_message['source_id'],$source_message['find_id'], $source_message['find_id'], $uid, '回复 ' . $user['username'] . '： ' . $content);
            }

        }

        return $lzlReplys;
    }


}