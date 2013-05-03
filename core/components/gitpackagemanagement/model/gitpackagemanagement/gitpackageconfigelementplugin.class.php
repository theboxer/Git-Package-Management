<?php

class GitPackageConfigElementPlugin extends GitPackageConfigElement{
    protected $type = 'plugin';
    protected $events = array();

    public function fromArray($config) {
        if(isset($config['events'])){
            $this->events = $config['events'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Elements: plugin - events are not set');
            return false;
        }

        return parent::fromArray($config);
    }

    public function getEvents() {
        return $this->events;
    }
}