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
    
    /** @var \GPM\Logger\MODX */
    protected $logger;

    /** @var GitPackageManagement */
    protected $gpm;

    public function process()
    {
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        $this->object = $this->modx->getObject('GitPackage', array('id' => $id));
        if (!$this->object) return $this->failure();

        $this->logger = new \GPM\Logger\MODX($this->modx);
        $this->gpm =& $this->modx->gitpackagemanagement;

        try {
            $this->config = new \GPM\Config\Config($this->modx, $this->object->dir_name);
            $parser = new \GPM\Config\Parser\Parser($this->modx, $this->config);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $installer = new \GPM\Action\Schema($this->config, $this->logger);
            $installer->build();
        } catch (\GPM\Config\Validator\ValidatorException $ve) {
            $this->logger->error('Config file is invalid.<br /><br />');
            $this->logger->error($ve->getMessage());

            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $this->success();
    }
}

return 'GitPackageManagementBuildSchemaProcessor';
