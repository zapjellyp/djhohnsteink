<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 想天
 * 创建日期: 3/13/14
 * 创建时间: 7:41 PM
 * 版权所有 想天工作室(www.ourstu.com)
 */

namespace Common\Model;

use Think\Model;

class TalkModel extends Model
{
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
        array('status', '1', self::MODEL_INSERT),
    );

    public function getUids($uids)
    {
        preg_match_all('/\[(.*?)\]/', $uids, $uids_array);
        return $uids_array[1];
    }

    public function getCurrentSessions()
    {
        $list = $this->where('uids like' . '"%[' . is_login() . ']%"' . ' and status=1')->order('update_time desc')->select();
        foreach ($list as &$li) {
            $uids = $this->getUids($li['uids']);
            foreach ($uids as $uid) {
                if ($uid != is_login()) {
                    $li['first_user'] = query_user(array('avatar64', 'username'), $uid);
                    $li['last_message'] = $this->getLastMessage($li['id']);
                    break;
                }
            }
        }
        unset($li);
        return $list;
    }

    public function getLastMessage($talk_id)
    {
        $last_message = D('TalkMessage')->where('talk_id=' . $talk_id)->order('create_time desc')->find();
        $last_message['user'] = query_user(array('username', 'space_url', 'id'), $last_message['uid']);
        $last_message['content'] = op_t($last_message['content']);
        return $last_message;
    }

    public function createTalk($members, $message = '',$title='')
    {

        $orin_member=$members;
        if (is_array($members)) {
            $members[]=is_login();
            foreach($members as &$mem){
               $mem= '[' .$mem.']';
            }
            unset($mem);

            $talk['uids'] = implode(',', $members);
        } else {
            /*创建talk*/
            $talk['uids'] = implode(',', array('[' . is_login() . ']', '[' . $members . ']'));

        }
        if ($message != '') {
            $talk['appname'] = $message['appname'];
            $talk['apptype'] = $message['apptype'];
            $talk['source_id'] = $message['source_id'];
            $talk['message_id'] = $message['id'];
            //通过消息获取到对应应用内的消息模型
            $messageModel = $this->getMessageModel($message);
            //从对应模型内取回对话源资料
            $talk = array_merge($messageModel->getSource($message), $talk);
        }else{
            if(count($orin_member)==1){
                $user_one=query_user(array('username'),$orin_member[0]);
                $user_two=query_user(array('username'));
                $talk['title']=$user_one['username'].' 和 '.$user_two['username'].'的会话';
            }
        }


        //创建会话
        $talk = D('Talk')->create($talk);
        $talk['id'] = D('Talk')->add($talk);
        return $talk;
        /*创建talk end*/

    }
    /**
     * @param $message
     * @return \Model
     */
    private function getMessageModel($message)
    {

        $appname = ucwords($message['appname']);
        $messageModel = D($appname . '/' . $appname . 'Message');
        return $messageModel;
    }
}