<?php
#MessageEncoder.php created by stcer@jz at 2024/3/27
namespace App\Job;

use Littlesqx\AintQueue\JobMessageEncoderInterface;
use Littlesqx\AintQueue\Serializer\JsonSerializer;
use ReflectionClass;
use function count;
use function is_array;
use function json_decode;

class MessageEncoder implements JobMessageEncoderInterface
{
    public $classMap = [
        'simpleJob' => SimpleJob::class
    ];

    public function encode($message): string
    {
        return (new JsonSerializer())->serialize($message);
    }

    public function decode(string $payload)
    {
        $object = json_decode($payload, true);
        if (count($object) > 1) {
            [$class, $arguments] = $object;
        } else {
            [$class] = $object;
            $arguments = [];
        }

        if (!is_array($arguments)) {
            $arguments = [];
        }

        $class = $this->classMap[$class] ?? $class;

        $class = new ReflectionClass($class);
        return $class->newInstanceArgs($arguments);
    }
}