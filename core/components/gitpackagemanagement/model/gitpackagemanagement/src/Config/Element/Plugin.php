<?php
namespace GPM\Config\Element;

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
}