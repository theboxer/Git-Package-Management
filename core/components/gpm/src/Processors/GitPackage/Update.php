<?php
namespace GPM\Processors\GitPackage;

use GPM\Processors\ModelOperation;

class Update extends ModelOperation
{
    public $operationClass = '\\GPM\\Operations\\Update';

    /** @var \GPM\Operations\Update */
    public $operation;

    public function beforeOutput()
    {
        $recreateDatabase = $this->getProperty('recreateDatabase', 0);
        $alterDatabase = $this->getProperty('alterDatabase', 0);
        $this->operation->execute($this->object, $recreateDatabase, $alterDatabase);

        parent::beforeOutput();
    }
}
