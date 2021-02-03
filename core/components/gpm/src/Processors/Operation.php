<?php
namespace GPM\Processors;

use MODX\Revolution\modX;
use MODX\Revolution\Processors\Processor;

abstract class Operation extends Processor {
    /** @var string[] */
    public $languageTopics = ['gpm:default'];

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

    public function process()
    {
        $this->modx->log(modX::LOG_LEVEL_INFO, 'COMPLETED');

        return $this->success();
    }

    public function getLanguageTopics(): array
    {
        return $this->languageTopics;
    }
}
