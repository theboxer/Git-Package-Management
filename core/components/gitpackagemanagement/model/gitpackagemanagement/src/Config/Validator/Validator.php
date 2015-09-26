<?php
namespace GPM\Config\Validator;

use GPM\Config\Config;

final class Validator
{
    protected $rules = [];
    protected $errors = [];
    
    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    public function validate(array $fields, Config $config)
    {
        foreach ($this->rules as $field => $rules) {
            $rules = explode(',', $rules);
            
            foreach ($rules as $rule) {
                $args = explode(':', $rule);
                $ruleName = array_shift($args);
                
                try {
                    Rules::$ruleName($fields[$field], $field, $args, $config);
                } catch (RuleException $e) {
                    if (!isset($this->errors[$e->getField()])) $this->errors[$e->getField()] = [];

                    $this->errors[$e->getField()] = $e->getMessage();
                }
            }
        }    
    }

    public function hasErrors()
    {
        return !empty($this->rules);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}