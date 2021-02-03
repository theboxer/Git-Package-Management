<?php
namespace GPM\Processors;

use GPM\Model\GitPackage;
use MODX\Revolution\modX;
use MODX\Revolution\Processors\Model\GetProcessor;

abstract class ModelOperation extends GetProcessor {
    /** @var string */
    public $classKey = GitPackage::class;

    /** @var string[] */
    public $languageTopics = ['gpm:default'];

    /** @var GitPackage */
    public $object;

    /** @var string */
    public $objectType = 'gpm.package';

    /** @var string */
    public $operationClass;

    /** @var \GPM\Operations\Operation */
    public $operation;

    public function initialize()
    {
        if (empty($this->operationClass)) {
            return $this->modx->lexicon('gpm.err.operation_ns');
        }

        if (!$this->modx->services->has($this->operationClass)) {
            return $this->modx->lexicon('gpm.err.operation_na');
        }

        $this->operation = $this->modx->services->get($this->operationClass);

        return parent::initialize();
    }

    public function beforeOutput()
    {
        $this->modx->log(modX::LOG_LEVEL_INFO, 'COMPLETED');
        parent::beforeOutput();
    }
}
