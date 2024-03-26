<?php

declare(strict_types=1);

/*
 * This file is part of the littlesqx/aint-queue.
 *
 * (c) littlesqx <littlesqx@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Littlesqx\AintQueue\Serializer;

use Closure;
use Littlesqx\AintQueue\Exception\InvalidArgumentException;
use function call_user_func;

class Factory
{
    /**
     * @const string
     */
    const SERIALIZER_TYPE_PHP = 's';

    const SERIALIZER_TYPE_JSON = 'j';

    /**
     * @const string
     */
    const SERIALIZER_TYPE_CLOSURE = 'cls';

    /**
     * @const string
     */
    const SERIALIZER_TYPE_COMPRESSING = 'cmp';

    /**
     * @var SerializerInterface[]
     */
    public static $instances = [];

    public static $serializerTypes = [
        self::SERIALIZER_TYPE_JSON => self::SERIALIZER_TYPE_JSON,
        self::SERIALIZER_TYPE_PHP => self::SERIALIZER_TYPE_PHP,
        self::SERIALIZER_TYPE_CLOSURE => self::SERIALIZER_TYPE_CLOSURE,
        self::SERIALIZER_TYPE_COMPRESSING => self::SERIALIZER_TYPE_COMPRESSING
    ];

    /**
     * Get a instance for serializer.
     *
     * @param string $type
     *
     * @return SerializerInterface
     *
     * @throws InvalidArgumentException
     */
    public static function getInstance(string $type): SerializerInterface
    {
        if (!isset(static::$serializerTypes[$type])) {
            throw new InvalidArgumentException("The arg type: {$type} is invalid.");
        }

        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = self::make($type);
        }

        return self::$instances[$type];
    }

    /**
     * Make an serializer object.
     *
     * @param string $type
     *
     * @return SerializerInterface|null
     */
    public static function make(string $type): ?SerializerInterface
    {
        if (isset(static::$serializerTypes[$type]) && static::$serializerTypes[$type] instanceof Closure) {
            return call_user_func(static::$serializerTypes[$type]);
        }

        switch ($type) {
            case self::SERIALIZER_TYPE_PHP:
                return new PhpSerializer();
            case self::SERIALIZER_TYPE_CLOSURE:
                return new ClosureSerializer();
            case self::SERIALIZER_TYPE_COMPRESSING:
                return new CompressingSerializer();
            case self::SERIALIZER_TYPE_JSON:
                return new JsonSerializer();
            default:
                return null;
        }
    }
}
