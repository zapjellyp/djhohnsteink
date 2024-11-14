<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 2/19/14
 * Time: 5:14 PM
 */

namespace Addons\LocalComment\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function addComment()
    {
        //获取参数
        $app = strval($_REQUEST['app']);
        $mod = strval($_REQUEST['mod']);
        $row_id = intval($_REQUEST['row_id']);
        $content = strval($_REQUEST['content']);
        $uid = intval($_REQUEST['uid']);

        //调用API接口，添加新评论
        $data = array('app' => $app, 'mod' => $mod, 'row_id' => $row_id, 'content' => $content);
        $commentModel = D('Addons://LocalComment/LocalComment');
        $data = $commentModel->create($data);
        if (!$data) {
            $this->error('评论失败：' . $commentModel->getError());
        }
        $commentModel->add($data);

        //给评论对象发送消息
        if($uid){
            $user = D('User/UcenterMember')->find(get_uid());
            $title = $user['username'] . '评论了您';
            $message = '评论内容：' . $content;
            $url = $_SERVER['HTTP_REFERER'];
            D('Common/Message')->sendMessage($uid, $message, $title, $url, get_uid(), 0, $app);
        }

        //通知被@到的人
        $uids = get_at_uids($content);
        $uids = array_unique($uids);
        $uids = array_subtract($uids, array($uid));
        foreach ($uids as $uid) {
            $user = D('User/UcenterMember')->find($uid);
            $title = $user['username'] . '@了您';
            $message = '评论内容：' . $content;
            $url = $_SERVER['HTTP_REFERER'];
            D('Common/Message')->sendMessage($uid, $message, $title, $url, get_uid(), 0, $app);
        }

        //返回结果
        $this->success('评论成功', 'refresh');
    }
}