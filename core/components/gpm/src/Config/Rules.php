<?php
namespace GPM\Config;

use GPM\Config\Parts\Element\Element;
use GPM\Config\Parts\Part;
use Psr\Log\LoggerInterface;

class Rules {
    const isString = 'isString';
    const notEmpty = 'notEmpty';
    const isArray = 'isArray';
    const isScalar = 'isScalar';
    const isInt = 'isInt';
    const isFloat = 'isFloat';
    const isBool = 'isBool';

    const packageFileExists = 'packageFileExists';
    const scriptExists = 'scriptExists';
    const validXType = 'validXType';
    const categoryExists = 'categoryExists';
    const configPart = 'configPart';
    const elementFileExists = 'elementFileExists';

    private static function getLogID(Part $part, $fieldName): string
    {
        $partName = (explode('\\', get_class($part)));
        $partName = array_pop($partName);

        return $partName . (!empty($part->keyField) ? (': ' . $part->{$part->keyField}) : '' ) . ' - ' . $fieldName . ' ';
    }

    public static function check($rule, Validator $validator, $value, string $fieldName, Part $part): bool
    {
        if (method_exists(self::class, $rule['rule'])) {
            return self::{$rule['rule']}($validator, $value, $fieldName, $part, $rule['params']);
        }

        $validator->logger->error(self::getLogID($part, $fieldName) . "has unknown rule {$rule['rule']}.");
        return false;
    }

    private static function isString(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $valid = is_string($value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "has to be string, " . gettype($value) . " given.");
        }

        return $valid;
    }

    private static function isBool(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $valid = is_bool($value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "has to be bool, " . gettype($value) . " given.");
        }

        return $valid;
    }

    private static function isInt(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $valid = is_int($value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "has to be int, " . gettype($value) . " given.");
        }

        return $valid;
    }

    private static function isFloat(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $valid = is_float($value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "has to be float, " . gettype($value) . " given.");
        }

        return $valid;
    }

    private static function isScalar(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $valid = is_scalar($value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "has to be scalar, " . gettype($value) . " given.");
        }

        return $valid;
    }

    private static function notEmpty(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $valid = !empty($value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "is empty.");
        }

        return $valid;
    }

    private static function isArray(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        if ($params === null) {
            $params = [];
        }

        if (empty($params['itemRules'])) {
            $params['itemRules'] = [Rules::isString];
        }

        $isArray = is_array($value);
        if (!$isArray) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "has to be array, " . gettype($value) . " given.");
            return false;
        }

        foreach ($value as $arrayValue) {
            foreach ($params['itemRules'] as $itemRule) {
                if (is_string($itemRule)) {
                    $itemRule = ['rule' => $itemRule];
                }

                if (!isset($itemRule['params'])) {
                    $itemRule['params'] = null;
                }

                $valid = self::check($itemRule, $validator, $arrayValue, "{$fieldName} - {$arrayValue}", $part);
                if (!$valid) return false;
            }
        }

        return true;
    }

    private static function packageFileExists(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        if (empty($value)) return true;

        $valid = file_exists($validator->config->paths->package . $value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "doesn't exist.");
        }

        return $valid;
    }

    private static function scriptExists(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        if (empty($value)) return true;

        $valid = file_exists($validator->config->paths->scriptsPath . $value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "doesn't exist.");
        }

        return $valid;
    }

    private static function validXType(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $xTypes = [
            'textfield',
            'textarea',
            'numberfield',
            'combo-boolean',
            'text-password',
            'modx-combo-category',
            'modx-combo-charset',
            'modx-combo-country',
            'modx-combo-context',
            'modx-combo-namespace',
            'modx-combo-template',
            'modx-combo-user',
            'modx-combo-usergroup',
            'modx-combo-language',
            'modx-combo-source',
            'modx-combo-source-type',
            'modx-combo-manager-theme',
            'modx-grid-json',
        ];

        $valid = in_array($value, $xTypes);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "is not valid.");
        }

        return $valid;
    }

    private static function categoryExists(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        if (empty($value)) return true;
        if (!is_array($value)) return false;

        $configCategories = $validator->config->categories;
        foreach ($value as $category) {
            $found = false;
            foreach ($configCategories as $configCategory) {
                if ($configCategory->name === $category) {
                    $configCategories = $configCategory->children;
                    $found = true;
                    break;
                }
            }

            if ($found === false) {
                $validator->logger->error(self::getLogID($part, $fieldName) . implode('/', $value) . " category doesn't exist");
                break;
            }
        }

        return true;
    }

    private static function configPart(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        return $validator->validate($value, true);
    }

    private static function elementFileExists(Validator $validator, $value, string $fieldName, Element $part, $params = null): bool
    {
        $valid = file_exists($part->absoluteFilePath);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "\"{$part->file}\" doesn't exist");
        }

        return $valid;
    }
}
