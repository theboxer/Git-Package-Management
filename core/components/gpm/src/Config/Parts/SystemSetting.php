<?php

namespace GPM\Config\Parts;

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

    /** @var string */
    protected $key = '';

    /** @var string */
    protected $type = '';

    /** @var string */
    protected $area = '';

    /** @var string */
    protected $value = '';

    public function validate(LoggerInterface $logger): bool
    {
        $valid = true;

        if (empty($this->key)) {
            $logger->error('System Settings - key is required');
            $valid = false;
        }

        if (!in_array($this->type, Types::List)) {
            $logger->error('System Settings - ' . $this->key . ' - type is not valid');
            $valid = false;
        }

        if ($valid) {
            $logger->debug(' - System Setting: ' . $this->key);
        }

        return $valid;
    }

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
