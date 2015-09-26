<?php
namespace GPM\Config\Validator;

use GPM\Config\Config;

final class Rules 
{
    public static function required($value, $field, array $args, array $data, Config $config) {
        if ($value === null) {
            throw new RuleException($field . 'This field must be present.');
        }
    }
    
    public static function notEmpty($value, $field, array $args, array $data, Config $config) {
        if ($value === null) {
            throw new RuleException($field, 'This field can\'t be empty.');
        }
    }

    public static function type($value, $field, array $args, array $data, Config $config)
    {
        $type = strtolower($args[0]);
        if ($type === 'float') {
            $type = 'double';
        }
        
        $typeOf = gettype($value);
        $allowNull = isset($args[1]) && $args[1] === 'null';
        
        if ($typeOf !== $type) {
            if (!(null === $value && true === $allowNull)) {
                throw new RuleException($field, sprintf('%1s must have a value of type %2s', $field, $type));
            }
        }
    }

    public static function categoryExists($value, $field, array $args, array $data, Config $config)
    {
        if (empty($value)) return;

        $currentCategories = array_keys($config->categories);
        if (!in_array($value, $currentCategories)) {
            throw new RuleException($field, sprintf('Category %1s does not exist', $value));
        }
    }
}
