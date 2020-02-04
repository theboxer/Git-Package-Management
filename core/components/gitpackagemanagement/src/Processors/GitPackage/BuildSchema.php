<?php
namespace GitPackageManagement\Processors\GitPackage;

use GitPackageManagement\Config\Config;
use GitPackageManagement\GitPackageManagement;
use GitPackageManagement\Model\GitPackage;
use MODX\Revolution\Processors\ModelProcessor;

class BuildSchema extends ModelProcessor {
    /** @var GitPackage $object */
    public $object;
    /** @var Config $config */
    public $config;
    public $packagePath = null;

    public function process() {
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        $this->object = $this->modx->getObject(GitPackage::class, array('id' => $id));
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


        $this->buildSchema();

        return $this->success();
    }

    private function buildSchema() {
        $corePath = $this->packagePath . $this->object->dir_name . "/core/components/" . $this->config->getLowCaseName() . "/";
        $corePath = str_replace('\\', '/', $corePath);

        $manager = $this->modx->getManager();
        $generator = $manager->getGenerator();

        $buildOptions = $this->config->getBuild();
        $generator->parseSchema(
            $this->packagePath . $this->object->dir_name . $buildOptions->getSchemaPath(),
            $corePath . '/src/',
            [
                'compile' => null,
                'update' => 0,
                'regenerate' => 1,
                'namespacePrefix' => $this->config->getName()
            ]
        );
    }
}
