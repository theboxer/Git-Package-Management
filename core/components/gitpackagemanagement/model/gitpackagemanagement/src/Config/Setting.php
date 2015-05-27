<?php
namespace GPM\Config;

use GPM\Util\Validator;

class Setting
{
    use Validator;
    
    protected $key;
    protected $type = 'textfield';
    protected $area = 'default';
    protected $value = '';
    protected $namespace;
    /** @var Config $config */
    private $config;
    
    protected $section = 'Settings';
    protected $validations = ['key'];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function fromArray($config)
    {
        $this->validate($config);
        
        $this->key = $config['key'];

        if (isset($config['type'])) {
            $this->type = $config['type'];
        }

        if (isset($config['area'])) {
            $this->area = $config['area'];
        }

        if (isset($config['namespace'])) {
            $this->namespace = $config['namespace'];
        } else {
            $this->namespace = $this->config->getLowCaseName();
        }

        if (isset($config['value'])) {
            $this->value = $config['value'];
        }

        return true;
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