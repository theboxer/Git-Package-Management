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

    protected $rules = [
        'key' => 'notEmpty'
    ];

    protected function setDefaults($config)
    {
        if (!isset($config['namespace'])) {
            $this->namespace = $this->config->general->lowCaseName;
        }    
    }

    public function toArray()
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'area' => $this->area,
            'value' => $this->value,
            'namespace' => $this->namespace
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