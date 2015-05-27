<?php
namespace GPM\Config\Element;

final class Plugin extends Element
{
    protected $type = 'plugin';
    protected $extension = 'php';
    protected $events = [];

    protected $section = 'Elements: Plugin';

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