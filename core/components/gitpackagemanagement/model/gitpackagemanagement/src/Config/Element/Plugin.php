<?php
namespace GPM\Config\Element;

class Plugin extends Element{
    protected $type = 'plugin';
    protected $extension = 'php';
    protected $events = array();

    public function fromArray($config) {
        if(isset($config['events'])){
            $this->events = $config['events'];
        }else{
            $this->config->error->addError('Elements: plugin - events are not set', true);
            return false;
        }

        return parent::fromArray($config);
    }

    public function getEvents() {
        return $this->events;
    }
}