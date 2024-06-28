<?php
namespace GPM\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

final class Stealth implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = [])
    {
    }
}
