<?php

namespace Imj\test;

use Imj\Frequency;

/**
 * Class FrequencyTest
 * @package Imj
 */
class FrequencyTest extends \PHPUnit_Framework_TestCase
{
    public function testCheck()
    {
        $t = new TestCase();
        $config = $t->getConfig();
        $freq = new Frequency($t->getRedis(), $config);

        $expt_ret_fun = function($t, $rule) use ($config){
            foreach ($rule as $times => $expt_ret) {
                if ($times <= $t) {
                    return $expt_ret;
                }
            }
            return $config['default'];
        };

        $rule = current($config['rules']);
        $value = uniqid();
        krsort($rule);
        $max = key($rule);
        for ($i = 1; $i <= $max; $i++) {
            $ret= $freq->check($value);
            $this->assertEquals($ret, $expt_ret_fun($i, $rule));
        }

    }

    public function testCheckTimes()
    {
        $t = new TestCase();
        $config = $t->getConfig();
        $freq = new Frequency($t->getRedis(), $config);
        $value = uniqid();
        $times = 100;
        for ($i = 1; $i <= $times; $i++) {
            $ret= $freq->checkTimes($value, 10);
            if ($times / 3 == 0) {
                $this->assertEquals($ret, $i);
            }
        }
    }
}