<?php
namespace GPM\Util;

/**
 * Class Validator
 * 
 * @property \GPM\Config\Config $config
 * @property array $validations
 * @property string $section
 * 
 * @package GPM\Util
 */
trait Validator
{
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
    
    protected function presentValidator($config, $field) {
        if (!isset($config[$field])) {
            throw new \Exception($this->generateMsg($field, 'is not set'));
        }
    }
    
    protected function requiredValidator($config, $field) {
        if (empty($config[$field])) {
            throw new \Exception($this->generateMsg($field, 'is empty'));
        }
    }
    
    protected function arrayValidator($config, $field) {
        if (isset($config[$field]) && !is_array($config[$field])) {
            throw new \Exception($this->generateMsg($field, 'is not an array'));
        }
    }

    protected function categoryExistsValidator($config, $field) {
        if (!isset($config[$field])) return;

        $currentCategories = array_keys($this->config->getCategories());
        if (!in_array($config[$field], $currentCategories)) {
            throw new \Exception($this->generateMsg('category', $config[$field] . ' does not exist'));
        }
    }
    
    protected function generateMsg($field, $msg) {
        $output = '';
        if (!empty ($this->section)) {
            $output .= $this->section . ' - ';
        }

        $output .= $field . ' ' . $msg;

        return $output;
    }

}