<?php

/** @var ClassLoader $loader */

use Composer\Autoload\ClassLoader;
use Littlesqx\AintQueue\Driver\DriverFactory;
use Littlesqx\AintQueue\Driver\Redis\Queue;

require(__DIR__ . '/boot.inc.php');

$config = require dirname(__DIR__) . '/config/aint-queue.php';

$channel = 'example';
$driverOption = $config[$channel]['driver'] ?? [];

/** @var Queue $queue */
$queue = DriverFactory::make($channel, $driverOption);

while (true) {
    for ($i = 0; $i < 50; $i++) {
        echo "Start push {$i}\n";
        $expire = rand(36, 1296000);
//        $expire = rand(1, 10);
        $date = time();
        $queue->push([
            // SimpleJob::class,
            'NTY',
            [
                [
                    'gid' => rand(1,1000000),
                    'prt' => rand(1,1000000),
                    'st' => $date,
                    'e' => $expire,
                    'ed' => $date + $expire,
                    't' => "物料名称abc, 半成熟" . $i,
                    'pu' => '测试者' . $i
                ]
            ]
        ], $expire);
    }
    sleep(1);
}


echo "Client send ok\n";