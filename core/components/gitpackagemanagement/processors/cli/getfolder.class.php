<?php

/**
 * Clone git repository and install it
 * 
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementCLIGetFolderProcessor extends modObjectProcessor {
    /** @var GitPackage $object */
    public $object;
    /** @var GitPackageConfig $config */
    public $config;

    public function process() {
        $key = $this->getProperty('2', 0);
        if (empty($key)) {
            return $this->failure();
        }

        $this->object = $this->modx->getObject('GitPackage', array('key' => $key));
        if (!$this->object) {
            return $this->failure();
        }

        return $this->success($this->object->dir_name);
    }

}
return 'GitPackageManagementCLIGetFolderProcessor';
