<?php
namespace GPM\Config;

use GPM\Config\Validator\Validator;
use GPM\Config\Validator\ValidatorException;
use GPM\Utils;

abstract class ConfigObject
{
    /* @var Config */
    protected $config;

    protected $rules = [];

    public function __construct(Config $config, $data = null)
    {
        $this->config = $config;

        if ($data !== null) {
            if (!is_array($data)) {
                throw new \Exception('Data have to be an array');    
            }
            
            $this->fromArray($data);
        }
    }

    public function __sleep()
    {
        $serialize = array_flip(array_keys(get_object_vars($this)));
        unset($serialize['config']);

        return array_flip($serialize);
    }

    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    public function fromArray($data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, ['config', 'section', 'validations'])) continue;

            if (property_exists($this, $key)) {
                if (method_exists($this, 'set' . ucfirst($key))) {
                    $this->{'set' . ucfirst($key)}($value);
                    continue;
                }

                $this->{$key} = $value;
            }
        }

        $this->setDefaults($data);
        $this->validate();

        return true;
    }

    abstract public function toArray();

    protected function setDefaults($config)
    {
    }

    protected function validate()
    {
        $validator = new Validator($this->rules);
        $validator->validate(Utils::getPublicVars($this), $this->config);

        if ($validator->hasErrors()) {
            $reflection = new \ReflectionClass($this);
            
            throw new ValidatorException($reflection->getShortName(), $validator->getErrors());
        }
    }

    protected function generateMsg($field, $msg)
    {
        $output = '';
        if (!empty ($this->section)) {
            $output .= $this->section . ' - ';
        }

        $output .= $field . ' ' . $msg;

        return $output;
    }
}