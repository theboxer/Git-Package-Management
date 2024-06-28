<?php
namespace GPM\Config\Parts\Fred;

/**
 * @property-read string $uuid
 */
trait Uuid {
    /** @var string */
    protected $uuid = '';

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }
}