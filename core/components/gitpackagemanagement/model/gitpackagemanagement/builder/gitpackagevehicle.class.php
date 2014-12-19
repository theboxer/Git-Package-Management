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

    public function addTableResolver($packagePath, $tables) {
        if (!is_dir($packagePath . '_build/gpm_resolvers')) {
            mkdir($packagePath . '_build/gpm_resolvers');
        }

        $this->smarty->assign('tables', $tables);

        $resolverContent = $this->smarty->fetch('tables_resolver.tpl');

        file_put_contents($packagePath . '_build/gpm_resolvers/gpm.resolve.tables.php', $resolverContent);

        $this->vehicle->resolve('php',array(
            'source' => $packagePath . '_build/gpm_resolvers/gpm.resolve.tables.php',
        ));

        return $this;
    }

    public function getVehicle() {
        return $this->vehicle;
    }

}