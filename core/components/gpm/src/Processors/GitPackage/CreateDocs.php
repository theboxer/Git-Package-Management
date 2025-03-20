<?php
namespace GPM\Processors\GitPackage;

use GPM\Processors\ModelOperation;

class CreateDocs extends ModelOperation
{
    public $operationClass = \GPM\Operations\CreateDocs::class;

    /** @var \GPM\Operations\CreateDocs */
    public $operation;

    public function beforeOutput()
    {
        $this->operation->execute($this->object->dir_name);

        parent::beforeOutput();
    }
}
