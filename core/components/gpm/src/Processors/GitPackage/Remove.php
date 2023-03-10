<?php
namespace GPM\Processors\GitPackage;

use GPM\Processors\ModelOperation;

class Remove extends ModelOperation
{
    public $operationClass = \GPM\Operations\Remove::class;

    /** @var \GPM\Operations\Remove */
    public $operation;

    public function beforeOutput()
    {
        $this->operation->execute($this->object);

        parent::beforeOutput();
    }
}
