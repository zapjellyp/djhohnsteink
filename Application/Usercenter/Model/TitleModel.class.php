<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 1/17/14
 * Time: 7:51 PM
 */

namespace Usercenter\Model;

use Think\Model;

class TitleModel extends Model
{
    public function getTitle($uid)
    {
        $score = query_user(array('score'), $uid);
        return $this->getTitleByScore($score['score']);
    }

    public function getTitleByScore($score)
    {
        //根据积分查询对应头衔
        $config = $this->getTitleConfig();
        $config = array_reverse($config, true);
        foreach ($config as $min => $title) {
            if ($score >= $min) {
                return $title;
            }
        }

        //查询无结果，返回最高头衔
        $keys = array_keys($config);
        $max_key = $keys[count($config) - 1];
        return $config[$max_key];
    }

    public function getTitleConfig()
    {
        return C('TITLE');
    }

    public function getScoreTotal($userScore)
    {
        $titles = $this->getTitleConfig();
        array_reverse($titles);
        foreach ($titles as $score => $title) {
            if ($userScore < $score) {
                return $score;
            }
        }

    }
}