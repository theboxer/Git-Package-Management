<?php

class GitPackageConfigSetting {
    private $modx;
    private $key;
    private $type;
    private $area;
    private $value;

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['key'])){
            $this->key = $config['key'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Settings - key is not set');
            return false;
        }

        if(isset($config['type'])){
            $this->type = $config['type'];
        }else{
            $this->type = 'textfield';
        }

        if(isset($config['area'])){
            $this->area = $config['area'];
        }else{
            $this->area = 'default';
        }

        if(isset($config['value'])){
            $this->value = $config['value'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Settings - value is not set');
            return false;
        }

        return true;
    }

    public function getArea() {
        return $this->area;
    }

    public function getKey() {
        return $this->key;
    }

    public function getType() {
        return $this->type;
    }

    public function getValue() {
        return $this->value;
    }

}