<?php
namespace GPM\Processors\GitPackage;

use GPM\Processors\ModelOperation;

class CheckLexicon extends ModelOperation
{
    public $operationClass = \GPM\Operations\CheckLexicon::class;

    /** @var \GPM\Operations\CheckLexicon */
    public $operation;

    public function beforeOutput()
    {
        $this->operation->execute($this->object->dir_name);

        parent::beforeOutput();
    }
}
