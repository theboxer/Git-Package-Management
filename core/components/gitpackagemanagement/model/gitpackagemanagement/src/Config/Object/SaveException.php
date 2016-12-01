<?php

namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class SaveException extends \Exception
{
    protected $object;

    public function __construct(ConfigObject $object, $message)
    {
        $this->object = $object;

        parent::__construct($message);
    }

    public function getObject()
    {
        return $this->object;
    }
}