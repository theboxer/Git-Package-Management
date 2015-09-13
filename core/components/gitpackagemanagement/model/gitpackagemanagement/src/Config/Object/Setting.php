<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Setting extends ConfigObject
{
    public $key;
    public $type = 'textfield';
    public $area = 'default';
    public $value = '';
    public $namespace;
    
    protected $section = 'Settings';
    protected $validations = ['key'];

    protected function setDefaults($config)
    {
        if (!isset($config['namespace'])) {
            $this->namespace = $this->config->getLowCaseName();
        }    
    }

    public function toArray()
    {
        return [
            'key' => $this->getKey(),
            'type' => $this->getType(),
            'area' => $this->getArea(),
            'value' => $this->getValue(),
            'namespace' => $this->getNamespace()
        ];
    }

    public function getNamespacedKey()
    {
        if ($this->namespace == '') {
            return $this->key;
        }

        return $this->namespace . '.' . $this->key;
    }
}