<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gitpackageconfig.class.php';
/**
 * Update a config file in database
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */

class GitPackageManagementUpdateConfigUpdateProcessor extends modObjectUpdateProcessor {
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';
    /** @var GitPackage $object */
    public $object;

    public function beforeSet() {

        $packagePath = $this->modx->getOption('gitpackagemanagement.packages_dir',null,null);
        if($packagePath == null){
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }

        $packagePath .= $this->object->dir_name;

        $configFile = $packagePath . $this->modx->gitpackagemanagement->configPath;
        if(!file_exists($configFile)){
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $config = file_get_contents($configFile);

        $config = $this->modx->fromJSON($config);

        $configObject = new GitPackageConfig($this->modx, $packagePath);
        if($configObject->parseConfig($config) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $this->setProperty('config', $this->modx->toJSON($config));

        return parent::beforeSet();
    }

}
return 'GitPackageManagementUpdateConfigUpdateProcessor';