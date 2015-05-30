<?php
namespace GPM\Config\Element;

final class Plugin extends Element
{
    protected $type = 'plugin';
    protected $extension = 'php';
    protected $events = [];

    protected $section = 'Elements: Plugin';
    protected $validations = ['name', 'category:categoryExists', 'events'];

    public function fromArray($config)
    {
        $this->events = $config['events'];
        
        return parent::fromArray($config);
    }

    public function getEvents()
    {
        return $this->events;
    }
}