<?php

class GitPackageVehicle {
    /** @var modPackageBuilder $builder */
    private $builder;
    /** @var modTransportVehicle $vehicle */
    private $vehicle;
    /** @var modSmarty $smarty */
    private $smarty;

    public function __construct($builder, $smarty, $object, $attributes) {
        $this->builder = $builder;
        $this->smarty = $smarty;

        $this->vehicle = $this->builder->createVehicle($object, $attributes);
    }

    public function addPhpValidator($filePath) {
        $this->vehicle->validate('php', array(
            'source' => $filePath,
        ));

        return $this;
    }

    public function addAssetsResolver($assetsPath){
        return $this->addFileResolver($assetsPath, "return MODX_ASSETS_PATH . 'components/';");
    }

    public function addCoreResolver($corePath) {
        return $this->addFileResolver($corePath, "return MODX_CORE_PATH . 'components/';");
    }

    public function addFileResolver($source, $target)
    {
        $this->vehicle->resolve('file',array(
            'source' => $source,
            'target' => $target,
        ));

        return $this;
    }

    public function addPHPResolver($filePath) {
        $this->vehicle->resolve('php', array(
            'source' => $filePath,
        ));

        return $this;
    }

    public function addTableResolver($packagePath, GitPackageConfigDatabase $db) {
        if (!is_dir($packagePath)) {
            mkdir($packagePath);
        }

        $resolver = $packagePath . '/gpm.resolve.tables.php';
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        $this->smarty->assign('tables', $db->getTables());
        $this->smarty->assign('prefix', $db->getPrefix());
        $this->smarty->assign('simpleobjects', $db->getSimpleObjects());

        $resolverContent = $this->smarty->fetch('tables_resolver.tpl');

        file_put_contents($resolver, $resolverContent);

        return $this->addPHPResolver($resolver);
    }

    public function getVehicle() {
        return $this->vehicle;
    }

    public function addExtensionPackageResolver($packagePath, $options = array()) {
        if (!is_dir($packagePath)) {
            mkdir($packagePath);
        }

        $resolver = $packagePath . '/gpm.resolve.extension_package.php';
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        $this->smarty->assign('extension_package_options', var_export($options, true));

        $resolverContent = $this->smarty->fetch('extension_package_resolver.tpl');

        file_put_contents($resolver, $resolverContent);

        return $this->addPHPResolver($resolver);
    }

    public function addTVResolver($packagePath, $tvMap) {
        if (!is_dir($packagePath)) {
            mkdir($packagePath);
        }

        $resolver = $packagePath . '/gpm.resolve.tv_templates.php';
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        $this->smarty->assign('tvMap', var_export($tvMap, true));

        $resolverContent = $this->smarty->fetch('tv_templates_resolver.tpl');

        file_put_contents($resolver, $resolverContent);

        return $this->addPHPResolver($resolver);
    }

    public function addResourceResolver($packagePath, $resources) {
        if (!is_dir($packagePath)) {
            mkdir($packagePath);
        }

        $resolver = $packagePath . '/gpm.resolve.resources.php';
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        $this->smarty->assign('resources', var_export($resources, true));

        $resolverContent = $this->smarty->fetch('resource_resolver.tpl');

        file_put_contents($resolver, $resolverContent);

        return $this->addPHPResolver($resolver);
    }

    public function addEncryptResolver($packagePath, $config) {
        if (!is_dir($packagePath)) {
            mkdir($packagePath);
        }

        $resolver = $packagePath . '/gpm.resolve.encrypt.php';
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        $this->smarty->assign('lowercasename', $config->getLowCaseName());

        $resolverContent = $this->smarty->fetch('encrypt_resolver.tpl');

        file_put_contents($resolver, $resolverContent);

        return $this->addPHPResolver($resolver);
    }
}
