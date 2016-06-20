<?php

namespace Imj;

/**
 * Class Frequency
 * @package Imj
 */
class Frequency
{
    /**
     * @var \Redis
     */
    protected $redis;

    protected $config;

    protected $hash_max_length;

    protected $time;

    /**
     * Frequency constructor
     * @param $redis
     * @param $config
     * @param int $hash_max_length
     */
    public function __construct($redis, $config, $hash_max_length = 50)
    {
        if (!($redis instanceof \Redis)) {
            throw new \InvalidArgumentException('Redis instance required');
        }

        if (!$config || !is_array($config)) {
            throw new \InvalidArgumentException('Config required');
        }

        $this->redis = $redis;
        $this->config = $config;
        $this->hash_max_length = $hash_max_length;
        $this->time = time();
    }

    /**
     * Check frequency
     * @param $value
     * @param string $key_cover
     * @param bool|true $incr
     * @return bool|null
     */
    public function check($value, $key_cover = '', $incr = true)
    {
        $time_in_min = intval(($this->time - $this->time % ($this->config['time_unit'] * 60)) / 60);
        $expire = $this->config['recycle'] * 60;

        if ($incr) {
            $this->incr($value, $time_in_min, $expire, $key_cover);
        }

        $ret = $this->checkRules($value, $time_in_min, $key_cover);

        return $ret === false ? $this->config['default'] : $ret;
    }

    /**
     * Returns the number of a period of time
     * @param $value
     * @param $times
     * @param string $key_cover
     * @return int|mixed
     */
    public function checkTimes($value, $times, $key_cover = '')
    {
        $key = self::getHashKey($value, $key_cover);
        $time_in_min_now = intval($this->time / 60);
        $data = $this->redis->hGetAll($key);
        if (empty($data)) {
            return 0;
        }

        end($data);
        $time_key = key($data);
        $time_count = $time_in_min_now - $time_key;
        $count = 0;

        while ($time_count < $times) {
            $count += current($data);
            $r = prev($data);
            if (!$r) {
                break;
            }
            $prev_time_key = key($data);
            $time_count += ($time_key - $prev_time_key);
            $time_key = $prev_time_key;
        }
        return $count;
    }

    /**
     * Increments the value of a value
     * @param $value
     * @param $time
     * @param $expire
     * @param $key_cover
     * @return int
     */
    protected function incr($value, $time, $expire, $key_cover)
    {
        $key = self::getHashKey($value, $key_cover);
        $hash_keys = $this->redis->hKeys($key);

        // recycle
        if (isset($hash_keys[$this->hash_max_length])) {
            $this->redis->hDel($key, $hash_keys[0]);
        }

        $ret = $this->redis->hIncrBy($key, $time, 1);
        $this->redis->expire($key, $expire);

        return $ret;
    }

    /**
     * Check rules
     * @param $value
     * @param $time_in_min
     * @param $key_cover
     * @return bool|null
     */
    protected function checkRules($value, $time_in_min, $key_cover)
    {
        ksort($this->config['rules']);
        $key = self::getHashKey($value, $key_cover);

        $count = 0;
        $sum = 0;
        $data = $this->redis->hGetAll($key);
        $time_key = &$time_in_min;

        foreach ($this->config['rules'] as $time => $rule) {
            do {
                if (isset($data[$time_key])) {
                    $sum += $data[$time_key];
                }
                $count += $this->config['time_unit'];
                $time_key = $time_key - $this->config['time_unit'];
            } while ($count < $time);

            // 匹配最大次数的返回
            if (!empty($sum)) {
                $matched = false;
                $matched_ret = null;
                foreach ($rule as $count => $ret) {
                    if ($sum >= $count) {
                        $matched_ret = $ret;
                        $matched = true;
                    }
                }

                if ($matched) {
                    return $matched_ret;
                }
            }
        }

        return false;
    }

    /**
     * Return the hash key
     * @param $value
     * @param $key_cover
     * @return string
     */
    protected static function getHashKey($value, $key_cover)
    {
        return "FREQ_{$value}_{$key_cover}";
    }
}
