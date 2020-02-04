<?php
namespace GitPackageManagement\Config;

class ElementPlugin extends Element{
    protected $type = 'plugin';
    protected $extension = 'php';
    protected $events = array();
    protected $disabled = 0;

    public function fromArray($config) {
        if(isset($config['events'])){
            $this->events = $config['events'];
        }else{
            $this->config->error->addError('Elements: plugin - events are not set', true);
            return false;
        }

        if (isset($config['disabled'])) {
            $this->disabled = intval($config['disabled']);
        }

        return parent::fromArray($config);
    }

    public function getEvents() {
        return $this->events;
    }

    public function getDisabled()
    {
        return $this->disabled;
    }
}
