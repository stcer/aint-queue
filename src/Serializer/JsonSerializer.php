<?php

declare(strict_types=1);

namespace Littlesqx\AintQueue\Serializer;

use Littlesqx\AintQueue\Exception\InvalidArgumentException;
use ReflectionClass;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

class JsonSerializer implements SerializerInterface
{
    public function serialize($object): string
    {
        if (is_string($object)) {
            $object = [$object];
        }

        if (!is_array($object)) {
            throw new InvalidArgumentException("Argument one need a array");
        }

        return json_encode($object);
    }

    public function unSerialize(string $serialized)
    {
        $object = json_decode($serialized, true);
        if (count($object) > 1) {
            [$class, $arguments] = $object;
        } else {
            [$class] = $object;
            $arguments = [];
        }

        if (!is_array($arguments)) {
            $arguments = [];
        }

        $class = new ReflectionClass($class);
        return $class->newInstanceArgs($arguments);
    }
}