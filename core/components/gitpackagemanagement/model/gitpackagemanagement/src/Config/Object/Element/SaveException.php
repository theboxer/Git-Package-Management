<?php

namespace GPM\Config\Object\Element;

class SaveException extends \GPM\Config\Object\SaveException
{
    public function __construct(Element $object)
    {
        $this->object = $object;

        $message = "[{$this->object->getElementType()}] {$this->object->name} save failed.";
        
        parent::__construct($object, $message);
    }
}