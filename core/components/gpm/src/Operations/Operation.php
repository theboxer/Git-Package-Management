<?php
namespace GPM\Operations;

use MODX\Revolution\modX;
use Psr\Log\LoggerInterface;

abstract class Operation
{
    protected $modx;
    protected $logger;

    public function __construct(modX $modx, LoggerInterface $logger)
    {
        $this->modx = $modx;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
