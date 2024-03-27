<?php

declare(strict_types=1);

/*
 * This file is part of the littlesqx/aint-queue.
 *
 * (c) littlesqx <littlesqx@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Littlesqx\AintQueue;

use Littlesqx\AintQueue\Serializer\JobMessageEncoder;

abstract class AbstractQueue implements QueueInterface
{
    /**
     * @var string
     */
    protected $channelPrefix = 'aint-queue:';

    /**
     * @var string
     */
    protected $channel = 'default';

    /**
     * @see AbstractQueue::isWaiting()
     */
    const STATUS_WAITING = 1;
    /**
     * @see AbstractQueue::isReserved()
     */
    const STATUS_RESERVED = 2;
    /**
     * @see AbstractQueue::isDone()
     */
    const STATUS_DONE = 3;

    /**
     * @see AbstractQueue::isFailed()
     */
    const STATUS_FAILED = 4;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var JobMessageEncoder
     */
    protected $messageEncoder;

    public function __construct(string $channel, array $options = [])
    {
        $this->channel = $channel;
        $this->options = $options;
    }

    public function setMessageEncoder(JobMessageEncoderInterface $messageEncoder): void
    {
        $this->messageEncoder = $messageEncoder;
    }

    public function getMessageEncoder(): JobMessageEncoderInterface
    {
        if (!isset($this->messageEncoder)) {
            $this->messageEncoder = JobMessageEncoder::getInstance();
        }

        return $this->messageEncoder;
    }

    /**
     * Get name of the channel.
     *
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Moved the expired job to waiting queue.
     */
    abstract public function migrateExpired(): void;

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isWaiting(int $id): bool
    {
        return self::STATUS_WAITING === $this->getStatus($id);
    }

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isReserved(int $id): bool
    {
        return self::STATUS_RESERVED === $this->getStatus($id);
    }

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isDone(int $id): bool
    {
        return self::STATUS_DONE === $this->getStatus($id);
    }

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isFailed(int $id): bool
    {
        return self::STATUS_FAILED === $this->getStatus($id);
    }
}
