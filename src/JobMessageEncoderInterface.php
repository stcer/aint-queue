<?php
# JobMessageEncoder.php
namespace Littlesqx\AintQueue;

use Closure;

interface JobMessageEncoderInterface
{
    public function encode($message): string;

    /**
     * @param string $payload
     * @return Closure|JobInterface
     */
    public function decode(string $payload);
}