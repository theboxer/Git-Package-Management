<?php

namespace GPM\Config\Parts;

use GPM\Config\Rules;
use GPM\Utils\Types;
use MODX\Revolution\modSystemSetting;
use Psr\Log\LoggerInterface;

/**
 * Class SystemSetting
 *
 * @property-read string $key
 * @property-read string $type
 * @property-read string $area
 * @property-read string $value
 *
 * @package GPM\Config\Parts
 */
class SystemSetting extends Part
{
    protected $keyField = 'key';

    /** @var string */
    protected $key = '';

    /** @var string */
    protected $type = '';

    /** @var string */
    protected $area = '';

    /** @var string */
    protected $value = '';

    protected $rules = [
        'key' => [Rules::isString, Rules::notEmpty],
        'type' => [Rules::isString, Rules::validXType],
        'value' => [Rules::isScalar]
    ];

    public function getObject($previousValue = null): modSystemSetting
    {
        return $this->prepareObject(true, $previousValue);
    }

    protected function prepareObject(bool $update = false, $previousValue = null): modSystemSetting
    {
        /** @var modSystemSetting $obj */
        $obj = null;

        if ($update) {
            $obj = $this->config->modx->getObject(modSystemSetting::class, ['key' => $this->getNamespacedKey()]);
        }

        if ($obj === null) {
            $obj = $this->config->modx->newObject(modSystemSetting::class);
            $obj->set('key', $this->getNamespacedKey());
        }

        if (($previousValue === null) || $previousValue !== $this->value) {
            $obj->set('value', $this->value);
        }

        $obj->set('namespace', $this->config->general->lowCaseName);
        $obj->set('area', $this->area);
        $obj->set('xtype', $this->type);

        return $obj;
    }

    public function getNamespacedKey(): string
    {
        return $this->config->general->lowCaseName . '.' . $this->key;
    }

    public function getBuildObject(): modSystemSetting
    {
        return $this->prepareObject();
    }

    protected function generator(): void
    {
        if (empty($this->type)) {
            $this->type = 'textfield';
        }

        if (empty($this->area)) {
            $this->area = 'default';
        }
    }

}
