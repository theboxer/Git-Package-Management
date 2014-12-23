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

    public function addAssetsResolver($assetsPath){
        $this->vehicle->resolve('file',array(
            'source' => $assetsPath,
            'target' => "return MODX_ASSETS_PATH . 'components/';",
        ));

        return $this;
    }

    public function addCoreResolver($corePath) {
        $this->vehicle->resolve('file',array(
            'source' => $corePath,
            'target' => "return MODX_CORE_PATH . 'components/';",
        ));

        return $this;
    }

    public function addPHPResolver($filePath) {
        $this->vehicle->resolve('php', array(
            'source' => $filePath,
        ));

        return $this;
    }

    public function addTableResolver($packagePath, $tables) {
        if (!is_dir($packagePath)) {
            mkdir($packagePath);
        }

        $resolver = $packagePath . '/gpm.resolve.tables.php';
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        $this->smarty->assign('tables', $tables);

        $resolverContent = $this->smarty->fetch('tables_resolver.tpl');

        file_put_contents($resolver, $resolverContent);

        return $this->addPHPResolver($resolver);
    }

    public function getVehicle() {
        return $this->vehicle;
    }

    public function addExtensionPackageResolver($packagePath, $serviceName = null, $serviceClass = null) {
        if (!is_dir($packagePath)) {
            mkdir($packagePath);
        }

        $resolver = $packagePath . '/gpm.resolve.extension_package.php';
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        $this->smarty->assign('serviceName', $serviceName);
        $this->smarty->assign('serviceClass', $serviceClass);

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
}