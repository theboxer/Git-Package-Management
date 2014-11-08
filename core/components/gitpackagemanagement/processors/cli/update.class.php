<?php

/**
 * Clone git repository and install it
 * 
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementCLIUpdateProcessor extends modObjectProcessor {
    /** @var GitPackage $object */
    public $object;
    /** @var GitPackageConfig $config */
    public $config;

    public function process() {
        $key = $this->getProperty('2', 0);
        if (empty($key)) {
            return $this->failure('no key');
        }

        $this->object = $this->modx->getObject('GitPackage', array('key' => $key));
        if (!$this->object) {
            return $this->failure('no object');
        }

        $corePath = $this->modx->getOption('gitpackagemanagement.core_path',null,$this->modx->getOption('core_path').'components/gitpackagemanagement/');
        $path = $this->modx->getOption('processorsPath', $this->modx->gitpackagemanagement->config, $corePath . 'processors/');
        $this->modx->runProcessor('mgr/gitpackage/update', array(
            'id' => $this->object->id,
            'recreateDatabase' => 0,
            'alterDatabase' => 1,
            'buildSchema' => 0,
        ), array(
            'processors_path' => $path,
            'location' => '',
        ));

        return $this->success();
    }

}
return 'GitPackageManagementCLIUpdateProcessor';
