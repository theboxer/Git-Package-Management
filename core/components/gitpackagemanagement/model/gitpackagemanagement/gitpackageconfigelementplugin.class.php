<?php

class GitPackageConfigElementPlugin{
    private $modx;
    private $name;
    private $file;
    private $events = array();

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Elements: plugin - name is not set');
            return false;
        }

        if(isset($config['file'])){
            $this->file = $config['file'];
        }else{
            $this->file = 'plugin.'.$this->name;
        }

        if(isset($config['events'])){
            $this->events = $config['events'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Elements: plugin - events are not set');
            return false;
        }

        return true;
    }

    public function getEvents() {
        return $this->events;
    }

    public function getFile() {
        return $this->file;
    }

    public function getName() {
        return $this->name;
    }
}