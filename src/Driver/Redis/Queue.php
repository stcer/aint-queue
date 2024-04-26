<?php

declare(strict_types=1);

/*
 * This file is part of the littlesqx/aint-queue.
 *
 * (c) littlesqx <littlesqx@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Littlesqx\AintQueue\Driver\Redis;

use Littlesqx\AintQueue\AbstractQueue;
use Littlesqx\AintQueue\Connection\RedisConnector;
use Littlesqx\AintQueue\Exception\InvalidArgumentException;
use Littlesqx\AintQueue\Exception\InvalidJobException;
use Littlesqx\AintQueue\JobInterface;
use Predis\Client;
use Predis\Collection\Iterator\Keyspace;
use Swoole\Coroutine;

class Queue extends AbstractQueue
{
    /**
     * @var RedisConnector|Client
     */
    private $connector;

    /**
     * Queue constructor.
     *
     * @param string $channel
     * @param array  $options
     */
    public function __construct(string $channel, array $options = [])
    {
        parent::__construct($channel, $options);
        $this->initConnection();
    }

    /**
     * Reset redis connection.
     */
    public function initConnection(): void
    {
        $this->connector = RedisConnector::create($this->options['connection'] ?? []);
    }

    /**
     * Disconnect the connection.
     */
    public function destroyConnection(): void
    {
        $this->connector->disconnect();
    }

    /**
     * Get a connection.
     *
     * @return Client|RedisConnector
     */
    public function getConnection()
    {
        if (Coroutine::getCid() > 0) {
            return Coroutine::getContext()[RedisConnector::class]
                ?? (Coroutine::getContext()[RedisConnector::class] = RedisConnector::create($this->options['connection'] ?? []));
        }

        return $this->connector;
    }

    /**
     * Push an executable job message into queue.
     *
     * @param \Closure|JobInterface|string|array $message
     * @param int                   $delay
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function push($message, int $delay = 0): void
    {
        $pushMessage = $this->getMessageEncoder()->encode($message);
        $redis = $this->getConnection();

        $id = $redis->incr($this->key('message_id'));
        $redis->hset($this->key('messages'), $id, $pushMessage);

        if ($delay > 0) {
            $redis->zadd($this->key('delayed'), [$id => time() + $delay]);
        } else {
            $redis->lpush($this->key('waiting'), [$id]);
        }
    }

    /**
     * Pop a job message from waiting-queue.
     *
     * @return int
     *
     * @throws \Throwable
     */
    public function pop(): int
    {
        $redis = $this->getConnection();

        return (int) $redis->eval(
            LuaScripts::pop(),
            3,
            $this->key('waiting'),
            $this->key('reserved'),
            $this->key('attempts'),
            $this->options['handle_timeout'] ?? 60 * 30
        );
    }

    /**
     * Remove specific finished job from current queue after exec.
     *
     * @param int $id
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function remove(int $id): void
    {
        $redis = $this->getConnection();

        $redis->eval(
            LuaScripts::remove(),
            4,
            $this->key('reserved'),
            $this->key('attempts'),
            $this->key('failed'),
            $this->key('messages'),
            $id
        );
    }

    private function key($name): string
    {
        return "{$this->channelPrefix}{$this->channel}:{$name}";
    }

    /**
     * Release a job which was failed to execute.
     *
     * @param int $id
     * @param int $delay
     *
     * @throws \Throwable
     */
    public function release(int $id, int $delay = 0): void
    {
        $redis = $this->getConnection();

        $redis->eval(
            LuaScripts::release(),
            2,
            $this->key('delayed'),
            $this->key('reserved'),
            $id,
            time() + $delay
        );
    }

    /**
     * Get status of specific job.
     *
     * @param int $id
     *
     * @return int
     *
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function getStatus(int $id): int
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid message ID: $id.");
        }

        $redis = $this->getConnection();

        $status = self::STATUS_DONE;

        if ($redis->hexists($this->key('messages'), $id)) {
            $status = self::STATUS_WAITING;
        }

        if ($redis->zscore($this->key('reserved'), $id)) {
            $status = self::STATUS_RESERVED;
        }

        if ($redis->hexists($this->key('failed'), $id)) {
            $status = self::STATUS_FAILED;
        }

        return $status;
    }

    /**
     * Clear current queue.
     *
     * @throws \Throwable
     */
    public function clear(): void
    {
        $redis = $this->getConnection();

        // delete waiting queue
        while ($redis->llen($this->key('waiting')) > 0) {
            $redis->ltrim($this->key('waiting'), 0, -501);
        }
        // delete reserved queue
        while ($redis->zcard($this->key('reserved')) > 0) {
            $redis->zremrangebyrank($this->key('reserved'), 0, 499);
        }

        // delete delayed queue
        while ($redis->zcard($this->key('delayed')) > 0) {
            $redis->zremrangebyrank($this->key('delayed'), 0, 499);
        }

        // delete failed queue
        $cursor = 0;
        do {
            [$cursor, $data] = $redis->hscan($this->key('failed'), $cursor, ['COUNT' => 200]);
            if (!empty($fields = array_keys($data))) {
                $redis->hdel($this->key('failed'), $fields);
            }
        } while ($cursor != 0);

        // delete attempts queue
        $cursor = 0;
        do {
            [$cursor, $data] = $redis->hscan($this->key('attempts'), $cursor, ['COUNT' => 200]);
            if (!empty($fields = array_keys($data))) {
                $redis->hdel($this->key('attempts'), $fields);
            }
        } while ($cursor != 0);

        // delete messages queue
        $cursor = 0;
        do {
            [$cursor, $data] = $redis->hscan($this->key('messages'), $cursor, ['COUNT' => 200]);
            if (!empty($fields = array_keys($data))) {
                $redis->hdel($this->key('messages'), $fields);
            }
        } while ($cursor != 0);

        // delete others
        $keyIterator = new Keyspace($redis->getConnector(), $this->key("*"), 50);
        !empty($keys = iterator_to_array($keyIterator)) && $redis->del($keys);
    }

    /**
     * Get job message from queue.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function get(int $id): array
    {
        if (!$id) {
            throw new InvalidArgumentException('Invalid id value: '.$id);
        }

        $redis = $this->getConnection();

        $attempts = $redis->hget($this->key('attempts'), $id);
        $payload = $redis->hget($this->key('messages'), $id);

        $job = $this->getMessageEncoder()->decode($payload);
        if (empty($job)) {
            throw new InvalidJobException(sprintf('Broken message payload[%d]: %s', $id, $payload));
        }

        return [$id, (int) $attempts, $job];
    }

    /**
     * Migrate the expired job to waiting queue.
     *
     * @throws \Throwable
     */
    public function migrateExpired(): void
    {
        $redis = $this->getConnection();

        $redis->eval(
            LuaScripts::migrateExpiredJobs(),
            2,
            $this->key('delayed'),
            $this->key('waiting'),
            time()
        );
    }

    /**
     * Get status of current queue.
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function status(): array
    {
        $redis = $this->getConnection();

        $pipe = $redis->pipeline();
        $pipe->get($this->key('message_id'));
        $pipe->zcard($this->key('reserved'));
        $pipe->llen($this->key('waiting'));
        $pipe->zcount($this->key('delayed'), '-inf', '+inf');
        $pipe->hlen($this->key('failed'));
        [$total, $reserved, $waiting, $delayed, $failed] = $pipe->execute();

        $done = ($total ?? 0) - $waiting - $delayed - $reserved - $failed;

        return [$waiting, $reserved, $delayed, $done, $failed, $total ?? 0];
    }

    /**
     * Fail a job.
     *
     * @param int         $id
     * @param string|null $payload
     *
     * @throws \Throwable
     */
    public function failed(int $id, string $payload = null): void
    {
        $redis = $this->getConnection();

        $redis->eval(
            LuaScripts::fail(),
            2,
            $this->key('failed'),
            $this->key('reserved'),
            (int) $id,
            (string) $payload
        );
    }

    /**
     * Retry reserved job (only called when listener restart.).
     *
     * @throws \Throwable
     */
    public function retryReserved(): void
    {
        $redis = $this->getConnection();

        $ids = $redis->zrange($this->key('reserved'), 0, -1);
        foreach ($ids as $id) {
            $this->release((int) $id);
        }
    }

    /**
     * Get all failed jobs.
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function getFailed(): array
    {
        $redis = $this->getConnection();

        $failedJobs = [];
        $cursor = 0;
        do {
            [$cursor, $data] = $redis->hscan($this->key('failed'), $cursor, [
                'COUNT' => 10,
            ]);
            $failedJobs += $data;
        } while ($cursor != 0);

        return $failedJobs;
    }

    /**
     * Clear failed job.
     *
     * @param int $id
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function clearFailed(int $id): void
    {
        $redis = $this->getConnection();

        $redis->hdel($this->key('failed'), [$id]);
    }

    /**
     * Reload failed job.
     *
     * @param int $id
     * @param int $delay
     *
     * @throws \Throwable
     */
    public function reloadFailed(int $id, int $delay = 0): void
    {
        $redis = $this->getConnection();

        $redis->eval(
            LuaScripts::reloadFail(),
            2,
            $this->key('delayed'),
            $this->key('failed'),
            $id,
            time() + $delay
        );
    }
}
