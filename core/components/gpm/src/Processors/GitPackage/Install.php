<?php
namespace GPM\Processors\GitPackage;

use GPM\Processors\Operation;
use MODX\Revolution\modX;

class Install extends Operation
{
    public $operationClass = '\\GPM\\Operations\\Install';

    /** @var \GPM\Operations\Install */
    public $operation;

    public function process()
    {
        $dir = $this->getProperty('dir');
        if (empty($dir)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gpm.err.dir_ns'));
            $this->modx->log(modX::LOG_LEVEL_INFO, 'COMPLETED');
            $this->addFieldError('dir', $this->modx->lexicon('gpm.err.dir_ns'));
            return $this->failure();
        }

        $this->operation->execute($dir);
        return parent::process();
    }

}
