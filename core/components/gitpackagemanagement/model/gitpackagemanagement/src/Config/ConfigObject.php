<?php
namespace GPM\Config;

abstract class ConfigObject
{
    /* @var $config Config */
    protected $config;

    protected $validations = [];
    protected $section = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function fromArray($config)
    {
        foreach ($config as $key => $value) {
            if (in_array($key, ['config', 'section', 'validations'])) continue;

            if (property_exists($this, $key)) {
                if (method_exists($this, 'set' . ucfirst($key))) {
                    $this->{'set' . ucfirst($key)}($key);
                    continue;
                }

                $this->{$key} = $value;
            }
        }

        $this->setDefaults($config);
        $this->validate($config);

        return true;
    }

    protected function validate($config)
    {
        foreach ($this->validations as $validation) {
            $validation = explode(':', $validation);

            $field = array_shift($validation);

            if (empty($validation)) {
                $validation[] = 'required';
            }

            foreach ($validation as $rule) {
                if (method_exists($this, $rule . 'Validator')) {
                    $this->{$rule . 'Validator'}($config, $field);
                }
            }
        }
    }

    protected function setDefaults($config)
    {
    }

    protected function presentValidator($config, $field)
    {
        if (!isset($config[$field])) {
            throw new \Exception($this->generateMsg($field, 'is not set'));
        }
    }

    protected function requiredValidator($config, $field)
    {
        if (empty($config[$field])) {
            throw new \Exception($this->generateMsg($field, 'are empty'));
        }
    }

    protected function arrayValidator($config, $field)
    {
        if (isset($config[$field]) && !is_array($config[$field])) {
            throw new \Exception($this->generateMsg($field, 'are not an array'));
        }
    }

    protected function categoryExistsValidator($config, $field)
    {
        if (!isset($config[$field])) return;

        $currentCategories = array_keys($this->config->getCategories());
        if (!in_array($config[$field], $currentCategories)) {
            throw new \Exception($this->generateMsg('category', $config[$field] . ' does not exist'));
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