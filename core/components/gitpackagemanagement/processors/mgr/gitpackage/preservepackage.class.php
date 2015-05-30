<?php

/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementPreservePackageProcessor extends modObjectProcessor
{
    /** @var GitPackage $object */
    public $object;
    /** @var \GPM\Config\Config $config */
    public $config;
    public $packagePath = null;

    public function process()
    {
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        $this->object = $this->modx->getObject('GitPackage', array('id' => $id));
        if (!$this->object) return $this->failure();

        $this->packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';
        if ($this->packagePath == null) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }

        $packagePath = $this->packagePath . $this->object->dir_name;

        $configFile = $packagePath . $this->modx->gitpackagemanagement->configPath;
        if (!file_exists($configFile)) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $config = file_get_contents($configFile);

        $config = $this->modx->fromJSON($config);

        $this->config = new \GPM\Config\Config($this->modx, $packagePath);
        if ($this->config->parseConfig($config) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $this->preserveAssets();
        $this->preserveCore();

        return $this->success();
    }

    private function preserveAssets()
    {
        $src = $this->packagePath . $this->object->dir_name . '/assets/components/' . $this->config->getLowCaseName() . '/';
        $dest = rtrim($this->modx->getOption('assets_path'), '/') . '/components/' . $this->config->getLowCaseName() . '/';

        $this->modx->gitpackagemanagement->deleteDir($dest);
        $this->modx->gitpackagemanagement->recurse_copy($src, $dest);
    }

    private function preserveCore()
    {
        $src = $this->packagePath . $this->object->dir_name . '/core/components/' . $this->config->getLowCaseName() . '/';
        $dest = rtrim($this->modx->getOption('core_path'), '/') . '/components/' . $this->config->getLowCaseName() . '/';

        $this->modx->gitpackagemanagement->deleteDir($dest);
        $this->modx->gitpackagemanagement->recurse_copy($src, $dest);
    }
}

return 'GitPackageManagementPreservePackageProcessor';
