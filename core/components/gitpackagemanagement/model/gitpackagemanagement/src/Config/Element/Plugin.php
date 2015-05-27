<?php
namespace GPM\Config\Element;

class Plugin extends Element
{
    protected $type = 'plugin';
    protected $extension = 'php';
    protected $events = array();

    public function fromArray($config)
    {
        if (isset($config['events'])) {
            $this->events = $config['events'];
        } else {
            throw new \Exception('Elements: plugin - events are not set');
        }

        return parent::fromArray($config);
    }

    public function getEvents()
    {
        return $this->events;
    }
}