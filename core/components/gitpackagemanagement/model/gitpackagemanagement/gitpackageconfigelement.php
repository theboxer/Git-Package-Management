<?php

abstract class GitPackageConfigElement{
    protected $modx;
    protected $name;
    protected $file;
    protected $type;

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Elements: '.$this->type.' - name is not set');
            return false;
        }

        if(isset($config['file'])){
            $this->file = $config['file'];
        }else{
            $this->file = strtolower($this->name).'.'.$this->type;
        }

        return true;
    }

    public function getFile() {
        return $this->file;
    }

    public function getName() {
        return $this->name;
    }
}