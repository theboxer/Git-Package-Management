<?php
namespace GPM\Config\Object\Element;

final class Plugin extends Element
{
    public $events = [];
    
    protected $elementType = 'plugin';
    protected $extension = 'php';

    protected $section = 'Elements: Plugin';
    protected $validations = ['name', 'category:categoryExists', 'events'];

    public function toArray()
    {
        $array = parent::toArray();

        $array['events'] = $this->events;

        return $array;
    }
}