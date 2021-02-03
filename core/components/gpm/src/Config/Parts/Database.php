<?php
namespace GPM\Config\Parts;

use Psr\Log\LoggerInterface;

/**
 * Class General
 *
 * @property-read string[] $tables
 *
 * @package GPM\Config\Parts
 */
class Database extends Part
{
    /** @var string[] */
    protected $tables = [];

    protected function generator(): void
    {
    }

    public function validate(LoggerInterface $logger): bool
    {
        $valid = true;

        if (!is_array($this->tables)) {
            $valid = false;
            $logger->error('Database - tables has to be an array');
        }

        if ($valid) {
            $logger->debug(' - Database');
        }

        return true;
    }

}
