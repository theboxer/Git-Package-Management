<?php
namespace GPM\Processors\GitPackage;

use GPM\Processors\ModelOperation;

class Build extends ModelOperation
{
    public $operationClass = \GPM\Operations\Build::class;

    /** @var \GPM\Operations\Build */
    public $operation;

    public function beforeOutput()
    {
        $this->operation->execute($this->object->dir_name);

        parent::beforeOutput();
    }


}
