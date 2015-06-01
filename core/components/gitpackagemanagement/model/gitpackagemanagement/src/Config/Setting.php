<?php
namespace GPM\Config;

class Setting extends ConfigObject
{
    protected $key;
    protected $type = 'textfield';
    protected $area = 'default';
    protected $value = '';
    protected $namespace;
    
    protected $section = 'Settings';
    protected $validations = ['key'];

    protected function setDefaults($config)
    {
        if (!isset($config['namespace'])) {
            $this->namespace = $this->config->getLowCaseName();
        }    
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getArea()
    {
        return $this->area;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getNamespacedKey()
    {
        if ($this->namespace == '') {
            return $this->key;
        }

        return $this->namespace . '.' . $this->key;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

}