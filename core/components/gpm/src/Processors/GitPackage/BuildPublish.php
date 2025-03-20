<?php
namespace GPM\Processors\GitPackage;

use GPM\Processors\ModelOperation;

class BuildPublish extends ModelOperation
{
    public $operationClass = \GPM\Operations\BuildPublish::class;

    /** @var \GPM\Operations\BuildPublish */
    public $operation;

    public function beforeOutput()
    {
        $this->operation->execute($this->object->dir_name);

        parent::beforeOutput();
    }
}
