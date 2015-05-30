<?php

/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementBuildSchemaProcessor extends modObjectProcessor
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


        $this->buildSchema();

        return $this->success();
    }

    private function buildSchema()
    {
        $modelPath = $this->packagePath . $this->object->dir_name . "/core/components/" . $this->config->getLowCaseName() . "/" . 'model/';
        $modelPath = str_replace('\\', '/', $modelPath);

        $manager = $this->modx->getManager();
        $generator = $manager->getGenerator();

        $generator->classTemplate = <<<EOD
<?php
/**
 * [+phpdoc-package+]
 */
class [+class+] extends [+extends+] {}
?>
EOD;
        $generator->platformTemplate = <<<EOD
<?php
/**
 * [+phpdoc-package+]
 */
require_once (strtr(realpath(dirname(dirname(__FILE__))), '\\\\', '/') . '/[+class-lowercase+].class.php');
class [+class+]_[+platform+] extends [+class+] {}
?>
EOD;
        $generator->mapHeader = <<<EOD
<?php
/**
 * [+phpdoc-package+]
 */
EOD;

        $buildOptions = $this->config->getBuild();
        $generator->parseSchema($this->packagePath . $this->object->dir_name . $buildOptions->getSchemaPath(), $modelPath);
    }
}

return 'GitPackageManagementBuildSchemaProcessor';
