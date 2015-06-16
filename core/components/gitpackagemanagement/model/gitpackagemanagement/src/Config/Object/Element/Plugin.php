<?php
namespace GPM\Config\Object\Element;

final class Plugin extends Element
{
    protected $elementType = 'plugin';
    protected $extension = 'php';
    protected $events = [];

    protected $section = 'Elements: Plugin';
    protected $validations = ['name', 'category:categoryExists', 'events'];

    public function getEvents()
    {
        return $this->events;
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['events'] = $this->getEvents();

        return $array;
    }
}