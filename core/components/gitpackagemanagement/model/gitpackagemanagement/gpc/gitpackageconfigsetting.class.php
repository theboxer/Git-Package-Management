<?php

class GitPackageConfigSetting {
    private $modx;
    private $key;
    private $type;
    private $area;
    private $value = '';
    private $namespace;
    /** @var GitPackageConfig $gitPackageConfig */
    private $gitPackageConfig;

    public function __construct(modX &$modx, $gitPackageConfig) {
        $this->modx =& $modx;
        $this->gitPackageConfig = $gitPackageConfig;
    }

    public function fromArray($config) {
        if(isset($config['key'])){
            $this->key = $config['key'];
        }else{
            $this->gitPackageConfig->error->addError('Settings - key is not set', true);
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

        if(isset($config['namespace'])){
            $this->namespace = $config['namespace'];
        }else{
            $this->namespace = $this->gitPackageConfig->getLowCaseName();
        }

        if(isset($config['value'])){
            $this->value = $config['value'];
        }

        return true;
    }

    public function getNamespace() {
        return $this->namespace;
    }

    public function getArea() {
        return $this->area;
    }

    public function getKey() {
        return $this->key;
    }

    public function getNamespacedKey() {
        if ($this->namespace == '') {
            return $this->key;
        }

        return $this->namespace . '.' . $this->key;
    }

    public function getType() {
        return $this->type;
    }

    public function getValue() {
        return $this->value;
    }

}