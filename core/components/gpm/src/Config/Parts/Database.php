<?php
namespace GPM\Config\Parts;

use Psr\Log\LoggerInterface;

/**
 * Class Database
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
}
