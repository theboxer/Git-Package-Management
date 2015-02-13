<?php
require_once dirname(__FILE__) . '/gitpackagevehicle.class.php';

class GitPackageBuilder {
    /** @var modX $modx */
    private $modx;
    /** @var modPackageBuilder $builder */
    private $builder;
    /** @var array $attributes */
    private $attributes = array();
    /** @var modSmarty $smarty */
    private $smarty;
    /** @var string $packagePath */
    private $packagePath;

    /**
     * @param modX $modx
     * @param modSmarty $smarty
     */
    public function __construct(modX &$modx, modSmarty $smarty, $packagePath) {
        $this->modx =& $modx;
        $this->smarty = $smarty;
        $this->packagePath = $packagePath;

        $this->setBuilder();
        $this->setAttributes();
    }

    private function setBuilder() {
        $this->modx->loadClass('transport.modPackageBuilder','',false, true);
        $this->builder = new modPackageBuilder($this->modx);

    }

    private function setAttributes() {
        $this->attributes['category'] = array(
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                'Snippets' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                ),
                'Chunks' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                ),
                'Templates' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'templatename',
                ),
                'TemplateVars' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                ),
                'Plugins' => array(
                    xPDOTransport::UNIQUE_KEY => 'name',
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::RELATED_OBJECTS => true,
                    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                        'PluginEvents' => array(
                            xPDOTransport::PRESERVE_KEYS => true,
                            xPDOTransport::UPDATE_OBJECT => false,
                            xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
                        ),
                    ),
                ),
            )
        );

        $this->attributes['menu'] = array (
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'text',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                'Action' => array (
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => array ('namespace','controller'),
                ),
            ),
        );

        $this->attributes['setting'] = array(
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => false,
        );
    }

    private function getAttributes($type){
        return $this->attributes[$type];
    }

    public function registerNamespace($ns = 'core', $autoIncludes = true, $packageNamespace = true, $path = '', $assetsPath = ''){
        $this->builder->registerNamespace($ns, $autoIncludes, $packageNamespace, $path, $assetsPath);
    }

    public function putVehicle(GitPackageVehicle $vehicle) {
        $this->builder->putVehicle($vehicle->getVehicle());
    }

    public function setPackageAttributes($attributes) {
        $this->builder->setPackageAttributes($attributes);
    }

    public function pack() {
        $this->builder->pack();
        if ($this->modx->gitpackagemanagement->getOption('remove_extracted_package')) {
            $this->modx->gitpackagemanagement->deleteDir($this->builder->directory . $this->builder->signature . '/');
        }
    }


    public function getFileContent($file) {
        $o = file_get_contents($file);

        return $o;
    }

    /**
     * @param $object
     * @param $type
     * @return GitPackageVehicle
     */
    public function createVehicle($object, $type) {
        return new GitPackageVehicle($this->builder, $this->smarty, $object, $this->getAttributes($type));
    }

    public function getTPBuilder() {
        return $this->builder;
    }

}