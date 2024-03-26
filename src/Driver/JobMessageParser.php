<?php
#MessageMaker.php created by stcer@jz at 2024/3/21
namespace Littlesqx\AintQueue\Driver;

use Littlesqx\AintQueue\Compressable;
use Littlesqx\AintQueue\Exception\InvalidArgumentException;
use Littlesqx\AintQueue\JobInterface;
use Littlesqx\AintQueue\Serializer\Factory;
use function get_class;
use function gettype;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;

class JobMessageParser
{
    /**
     * @var static
     */
    protected static $instance;

    /**
     * @param self $instance
     */
    public static function setInstance($instance): void
    {
        self::$instance = $instance;
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }
        static::$instance = new static();
        return static::$instance;
    }

    public function makePushMessage($message)
    {
        if (is_callable($message)) {
            $serializerType = Factory::SERIALIZER_TYPE_CLOSURE;
        } elseif ($message instanceof JobInterface) {
            if ($message instanceof Compressable) {
                $serializerType = Factory::SERIALIZER_TYPE_COMPRESSING;
            } else {
                $serializerType = Factory::SERIALIZER_TYPE_PHP;
            }
        } elseif (is_string($message) || is_array($message)) {
            $serializerType = Factory::SERIALIZER_TYPE_JSON;
        } else {
            $type = is_object($message) ? get_class($message) : gettype($message);
            throw new InvalidArgumentException($type . ' type message is not allowed.');
        }

        return json_encode([
            't' => $serializerType,
            'm' => Factory::getInstance($serializerType)
                ->serialize($message),
        ]);
    }

    public function parseJobMessage($payload)
    {
        if (empty($payload)
            || empty($message = json_decode($payload, true))
            || !isset($message['t'])
        ) {
            return null;
        }

        $serializer = Factory::getInstance($message['t']);
        return $serializer->unSerialize($message['m']);
    }
}