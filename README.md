
A simple frequency control system
===========================

Installation
------------

  composer require imj\frequency


Basic Usage
------------
```
use Imj\Frequency;

$config = [
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

$redis = getRedis();
$ip = getIp();
$ret= $freq->check($ip);
$min = 10;
$times = $freq->checkTimes($ip, $min);
```

License
------------

licensed under the MIT License - see the `LICENSE` file for details
