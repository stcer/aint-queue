<?php

/*
 * This file is part of the littlesqx/aint-queue.
 *
 * (c) littlesqx <littlesqx@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

use Littlesqx\AintQueue\Driver\Redis\Queue as RedisQueue;
use Littlesqx\AintQueue\Serializer\AliasMessageEncoder;

return [
    'example' => [
        'driver' => [
            'class' => RedisQueue::class,
            'connection' => [
                'host' => 'redis',
                'port' => 6379,
                'database' => '0',
                // 'password' => 'password',
            ],
            'encoder' => function() {
                $encoder = new AliasMessageEncoder();
                $encoder->addClassMap('simpleJob', \App\Job\SimpleJob::class);
                return $encoder;
            },
        ],
        'logger' => [
            'class' => \Littlesqx\AintQueue\Logger\DefaultLogger::class,
            'options' => [
                'level' => \Monolog\Logger::DEBUG,
            ],
        ],
        'pid_path' => '/var/run/aint-queue',
        'consumer' => [
            'sleep_seconds' => 1,
            'memory_limit' => 96,
            'dynamic_mode' => true,
            'capacity' => 20,
            'flex_interval' => 5 * 60,
            'min_worker_number' => 5,
            'max_worker_number' => 30,
        ],
        'job_snapshot' => [
            'interval' => 5 * 60,
            'handler' => [],
        ],
    ],
];
