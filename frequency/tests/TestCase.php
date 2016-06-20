<?php

namespace Imj\test;

/**
 * Class TestCase
 * @package Imj
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    public function getRedis()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $redis->auth('123456');
        $redis->select(0);
        return $redis;
    }

    public function getConfig()
    {
        return [
            'time_unit' => 5,
            'recycle'   => 120,
            'default'   => 0,
            'rules'	=> [
                10 => [
                    10 => 1,  // 10分钟操作10次及以上返回1
                    20 => 2,
                    30 => 3,
                    40 => 4,
                ],
                20 => [
                    80 => 5
                ]
            ]
        ];
    }
}
