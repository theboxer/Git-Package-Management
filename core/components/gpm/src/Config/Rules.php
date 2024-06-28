<?php
namespace GPM\Config;

use GPM\Config\Parts\Element\Element;
use GPM\Config\Parts\Element\Plugin;
use GPM\Config\Parts\Part;
use GPM\Config\Parts\Widget;
use GPM\Model\GitPackage;
use MODX\Revolution\Transport\modTransportPackage;
use Psr\Log\LoggerInterface;
use xPDO\Transport\xPDOTransport;

class Rules {
    const isString = 'isString';
    const notEmpty = 'notEmpty';
    const isArray = 'isArray';
    const isObject = 'isObject';
    const isScalar = 'isScalar';
    const isInt = 'isInt';
    const isFloat = 'isFloat';
    const isBool = 'isBool';
    const isEnum = 'isEnum';

    const packageFileExists = 'packageFileExists';
    const scriptExists = 'scriptExists';
    const buildFileExists = 'buildFileExists';
    const validXType = 'validXType';
    const categoryExists = 'categoryExists';
    const templateExists = 'templateExists';
    const configPart = 'configPart';
    const elementFileExists = 'elementFileExists';
    const containsEventPropertySets = 'containsEventPropertySets';
    const propertySetExists = 'propertySetExists';
    const widgetContent = 'widgetContent';
    const packageDependencies = 'packageDependencies';

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

    private static function isObject(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        $isArray = is_array($value);
        if (!$isArray) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "has to be object, " . gettype($value) . " given.");
            return false;
        }

        $keys = array_keys($value);
        foreach ($keys as $key) {
            $valid = self::check(['rule' => Rules::isString], $validator, $key, "{$fieldName} - {$key}", $part);
            if (!$valid) return false;
        }

        return true;
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

                $arrayValueLabel = '';
                if (is_scalar($arrayValue)) {
                    $arrayValueLabel = $arrayValue;
                } else if (is_object($arrayValue) && (property_exists($arrayValue, 'keyField'))) {
                    $arrayValueLabel = $arrayValue->{$arrayValue->keyField};
                }

                $valid = self::check($itemRule, $validator, $arrayValue, "{$fieldName} - {$arrayValueLabel}", $part);
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

    private static function buildFileExists(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        if (empty($value)) return true;

        $valid = file_exists($validator->config->paths->build . $value);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "doesn't exist.");
        }

        return $valid;
    }

    private static function scriptExists(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        if (empty($value)) return true;

        $valid = file_exists($validator->config->paths->scripts . $value);

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

    private static function templateExists(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        if (empty($value)) return true;
        if (!is_array($value)) return false;

        $configTemplates = $validator->config->templates;
        foreach ($value as $template) {
            $found = false;
            foreach ($configTemplates as $configTemplate) {
                if ($configTemplate->name === $template) {
                    $found = true;
                    break;
                }
            }

            if ($found === false) {
                $validator->logger->error(self::getLogID($part, $fieldName) . implode('/', $value) . " template doesn't exist");
                break;
            }
        }

        return true;
    }

    private static function configPart(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        return $validator->validate($value, true);
    }

    private static function elementFileExists(Validator $validator, $value, string $fieldName, $part, $params = null): bool
    {
        if ($part->content !== null) return true;

        $valid = file_exists($part->absoluteFilePath);

        if (!$valid) {
            $validator->logger->error(self::getLogID($part, $fieldName) . "\"{$part->filePath}\" doesn't exist");
        }

        return $valid;
    }

    private static function containsEventPropertySets(Validator $validator, $value, string $fieldName, Plugin $part, $params = null): bool
    {
        $valid = true;
        $events = $part->events;
        foreach ($events as $event) {
            if (empty($event->propertySet)) continue;

            if (!in_array($event->propertySet, $value)) {
                $validator->logger->error(self::getLogID($part, $fieldName) . "don't contain {$event->propertySet} property set from {$event->name} event.");
                $valid = false;
            }
        }

        return $valid;
    }

    private static function propertySetExists(Validator $validator, $value, string $fieldName, Element $part, $params = null): bool
    {
        foreach ($validator->config->propertySets as $propertySet) {
            if ($propertySet->name === $value) {
                return true;
            }
        }

        $validator->logger->error(self::getLogID($part, $fieldName) . "is not defined under package's property sets.");
        return false;
    }

    private static function isEnum(Validator $validator, $value, string $fieldName, Part $part, array $params = []): bool
    {
        $valid = in_array($value, $params);
        if ($valid) return true;

        $validator->logger->error(self::getLogID($part, $fieldName) . "has to be one of these values " . implode(', ', $params) . ". Value {$value} was used.");
        return false;
    }

    private static function widgetContent(Validator $validator, $value, string $fieldName, Widget $part, $params = null): bool
    {
        $type = $part->type;

        if ($type === 'file') {
            $valid = file_exists($part->absoluteFilePath);

            if (!$valid) {
                $validator->logger->error(self::getLogID($part, $fieldName) . "\"{$part->filePath}\" doesn't exist");
            }

            return $valid;
        }

        return true;
    }

    private static function packageDependencies(Validator $validator, $value, string $fieldName, Part $part, $params = null): bool
    {
        /** @var modTransportPackage $transportPackage */
        $transportPackage = $validator->config->modx->newObject(modTransportPackage::class);
        $transportPackage->set('signature', $validator->config->general->lowCaseName . '-' . $validator->config->general->version);
        $transportPackage->parseSignature();

        $unsatisfied = $transportPackage->checkDependencies($value);
        if (empty($unsatisfied)) {
            return true;
        }

        $valid = true;

        foreach ($unsatisfied as $packageName => $constraint) {
            if (strtolower($packageName) === 'php') {
                $valid = false;
                $validator->logger->error(self::getLogID($part, $fieldName) . "PHP {$constraint} but your version is " . PHP_VERSION);
                continue;
            }

            if (strtolower($packageName) === 'modx') {
                $valid = false;
                $validator->logger->error(self::getLogID($part, $fieldName) . "MODX Revolution {$constraint} but your version is {$validator->config->modx->version['full_version']}");
                continue;
            }

            /** @var GitPackage $package */
            $package = $validator->config->modx->getObject(GitPackage::class, ['name' => $packageName]);
            if (!$package) {
                $valid = false;
                $validator->logger->error(self::getLogID($part, $fieldName) . "{$packageName} but it is not installed.");
                continue;
            }

            $satisfies = xPDOTransport::satisfies($package->version, $constraint);
            if (!$satisfies) {
                $valid = false;
                $validator->logger->error(self::getLogID($part, $fieldName) . "{$packageName} {$constraint} but your version is {$package->version}");
            }
        }

        return $valid;
    }
}
