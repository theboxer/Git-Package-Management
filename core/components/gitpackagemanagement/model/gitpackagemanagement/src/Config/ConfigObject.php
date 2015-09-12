<?php
namespace GPM\Config;

abstract class ConfigObject
{
    /* @var Config */
    protected $config;

    protected $validations = [];
    protected $section = [];

    public function __construct(Config $config, $data = null)
    {
        $this->config = $config;

        if ($data !== null) {
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
                    $this->{'set' . ucfirst($key)}($key);
                    continue;
                }

                $this->{$key} = $value;
            }
        }

        $this->setDefaults($data);
        $this->validate($data);

        return true;
    }

    abstract public function toArray();

    protected function setDefaults($config)
    {
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

    protected function presentValidator($config, $field)
    {
        if (!isset($config[$field])) {
            throw new \Exception($this->generateMsg($field, 'is not set'));
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
        if (empty($config[$field])) return;

        $currentCategories = array_keys($this->config->getCategories());
        if (!in_array($config[$field], $currentCategories)) {
            throw new \Exception($this->generateMsg('category', $config[$field] . ' does not exist'));
        }
    }
}