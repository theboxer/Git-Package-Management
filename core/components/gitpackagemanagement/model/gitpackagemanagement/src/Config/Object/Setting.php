<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Setting extends ConfigObject
{
    public $key;
    public $type = 'textfield';
    public $area = 'default';
    public $value = '';
    public $namespace;

    protected $rules = [
        'key' => 'notEmpty'
    ];

    public function getNamespacedKey()
    {
        if ($this->namespace == '') {
            return $this->key;
        }

        return $this->namespace . '.' . $this->key;
    }

    public function toArray()
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'area' => $this->area,
            'value' => $this->value,
            'namespace' => $this->namespace
        ];
    }

    public function prepareObject()
    {
        /** @var \modSystemSetting $object */
        $object = $this->config->modx->newObject('modSystemSetting');
        $object->set('key', $this->getNamespacedKey());
        $object->set('value', $this->value);
        $object->set('xtype', $this->type);
        $object->set('namespace', $this->config->general->lowCaseName);
        $object->set('area', $this->area);

        return $object;
    }
    
    public function newObject()
    {
        /** @var \modSystemSetting $object */
        $object = $this->config->modx->getObject('modSystemSetting', array('key' => $this->getNamespacedKey()));
        if (!$object) {
            $object = $this->config->modx->newObject('modSystemSetting');
            $object->set('key', $this->getNamespacedKey());
        }

        $object->set('namespace', $this->config->general->lowCaseName);
        $object->set('value', $this->value);
        $object->set('area', $this->area);
        $object->set('xtype', $this->type);

        $saved = $object->save();

        if (!$saved) {
            throw new SaveException($this, "Couldn't save system setting with key: {$this->getNamespacedKey()}");
        }

        return $object;
    }

    public function updateObject($oldValue = null)
    {
        /** @var \modSystemSetting $object */
        $object = $this->config->modx->getObject('modSystemSetting', array('key' => $this->getNamespacedKey()));
        if (!$object) {
            $object = $this->config->modx->newObject('modSystemSetting');
            $object->set('key', $this->getNamespacedKey());
            $object->set('value', $this->value);
            $object->set('namespace', $this->config->general->lowCaseName);
        } else {
            if (($oldValue === null) || $oldValue != $this->value) {
                $object->set('value', $this->value);
            }
        }
        $object->set('area', $this->area);
        $object->set('xtype', $this->type);

        $saved = $object->save();

        if (!$saved) {
            throw new SaveException($this, "Couldn't save system setting with key: {$this->getNamespacedKey()}");
        }

        return $object;
    }

    protected function setDefaults($config)
    {
        if (!isset($config['namespace'])) {
            $this->namespace = $this->config->general->lowCaseName;
        }
    }
}