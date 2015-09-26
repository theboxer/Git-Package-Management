<?php
namespace GPM\Config\Validator;

final class Rules 
{
    public static function required($value, $field, $args = [], $data, $config) {
        if ($value === null) {
            throw new RuleException($field . 'This field must be present.');
        }
    }
    
    public static function notEmpty($value, $field, $args = [], $data, $config) {
        if ($value === null) {
            throw new RuleException($field, 'This field can\'t be empty.');
        }
    }

    public function __callStatic($value, $field, $args = [], $data, $config)
    {
        
    }
}
