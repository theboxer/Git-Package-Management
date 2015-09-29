<?php
namespace GPM\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

final class Null implements LoggerInterface
{
    use LoggerTrait;
    
    public function log($level, $message, array $context = array())
    {
    }
}