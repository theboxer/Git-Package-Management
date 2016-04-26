<?php

class GitPackageConfigElementPlugin extends GitPackageConfigElement{
    protected $type = 'plugin';
    protected $extension = 'php';
    protected $events = array();
    protected $property_preprocess = '';

    public function fromArray($config) {
        if(isset($config['events'])){
            $this->events = $config['events'];
        }else{
            $this->config->error->addError('Elements: plugin - events are not set', true);
            return false;
        }
        if (isset($config['property_preprocess'])) {
            $this->property_preprocess = $config['property_preprocess'];
        }
        return parent::fromArray($config);
    }

    public function getEvents() {
        return $this->events;
    }
    public function getPropertyPreprocess() {
        return $this->property_preprocess;
    }
}