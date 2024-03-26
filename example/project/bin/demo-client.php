<?php

/** @var ClassLoader $loader */

use App\Job\SimpleJob;
use Composer\Autoload\ClassLoader;
use Littlesqx\AintQueue\Driver\DriverFactory;
use Littlesqx\AintQueue\Driver\Redis\Queue;

$loader = require __DIR__ . '/../../../vendor/autoload.php';
$loader->addPsr4('App\\', dirname(__DIR__) . '/app');

$config = require dirname(__DIR__) . '/config/aint-queue.php';

$channel = 'example';
$driverOption = $config[$channel]['driver'] ?? [];

/** @var Queue $queue */
$queue = DriverFactory::make($channel, $driverOption);
//$queue->push(function (){
//    echo "Hello AintQueue\n";
//});

$queue->push(SimpleJob::class, 2);

$queue->push([
    SimpleJob::class,
    [
        ['info_id' => 10, 'delay' => 5]
    ]
], 5);


$queue->push([
    SimpleJob::class,
    [
        ['info_id' => 12, 'delay' => 10]
    ]
], 10);

//$queue->push(new CoroutineJob());

echo "Client send ok\n";