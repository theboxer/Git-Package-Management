<?php
namespace GitPackageManagement\Processors\GitPackage;

use GitPackageManagement\Config\Config;
use GitPackageManagement\GitPackageManagement;
use GitPackageManagement\Model\GitPackage;

class Preserve extends \MODX\Revolution\Processors\ModelProcessor {
    /** @var GitPackage $object */
    public $object;
    /** @var Config $config */
    public $config;
    public $packagePath = null;
    /** @var GitPackageManagement */
    private $gpm;

    public function process() {
        $this->gpm = $this->modx->services->get('gitpackagemanagement');

        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        $this->object = $this->modx->getObject(GitPackage::class, ['id' => $id]);
        if (!$this->object) return $this->failure();

        $this->packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';
        if($this->packagePath == null){
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }

        $packagePath = $this->packagePath . $this->object->dir_name;

        $configFile = $packagePath . GitPackageManagement::$configPath;
        if(!file_exists($configFile)){
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $config = file_get_contents($configFile);

        $config = $this->modx->fromJSON($config);

        $this->config = new Config($this->modx, $packagePath);
        if($this->config->parseConfig($config) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $this->preserveAssets();
        $this->preserveCore();

        return $this->success();
    }

    private function preserveAssets() {
        $src = $this->packagePath . $this->object->dir_name . '/assets/components/' . $this->config->getLowCaseName() . '/';
        $dest = rtrim($this->modx->getOption('assets_path') , '/') . '/components/' . $this->config->getLowCaseName() . '/';

        $this->gpm->deleteDir($dest);
        $this->gpm->recurse_copy($src, $dest);
    }

    private function preserveCore() {
        $src = $this->packagePath . $this->object->dir_name . '/core/components/' . $this->config->getLowCaseName() . '/';
        $dest = rtrim($this->modx->getOption('core_path') , '/') . '/components/' . $this->config->getLowCaseName() . '/';

        $this->gpm->deleteDir($dest);
        $this->gpm->recurse_copy($src, $dest);
    }
}
