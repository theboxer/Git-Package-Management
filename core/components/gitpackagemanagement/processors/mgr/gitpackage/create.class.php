<?php

/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementCreateProcessor extends modObjectCreateProcessor
{
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';

    /** @var \GPM\Config\Config $config * */
    private $config = null;
    
    /** @var \GPM\Logger\MODX */
    protected $logger;
    
    /** @var GitPackageManagement */
    protected $gpm;

    public function beforeSave()
    {
        $this->logger = new \GPM\Logger\MODX($this->modx);
        $this->gpm =& $this->modx->gitpackagemanagement;
        
        try {
            $this->config = new \GPM\Config\Config($this->modx, $this->getProperty('folderName'));
            $parser = new \GPM\Config\Parser\Parser($this->modx, $this->config);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $installer = new \GPM\Action\Install($this->config, $this->logger);
            $installer->install();
        } catch (\GPM\Config\Validator\ValidatorException $ve) {
            $this->addFieldError('folderName', $this->modx->lexicon('Config file is invalid.'));
            $this->logger->error('Config file is invalid.<br /><br />');
            $this->logger->error($ve->getMessage());
            $this->logger->info('COMPLETED');

            return false;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->info('COMPLETED');

            $this->addFieldError('folderName', $e->getMessage());

            return false;
        }

//        $dependencies = $this->config->checkDependencies();
//        if ($dependencies !== true) {
//            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_dependencies'));
//            $this->logger->error('Dependencies are not matching!');
//
//            foreach ($dependencies as $dependency) {
//                $this->logger->error('Package ' . $dependency . ' not found!');
//            }
//
//            $this->logger->info('COMPLETED');
//            return false;
//        }

        $this->object->set('config', serialize($this->config));
        $this->object->set('version', $this->config->general->version);
        $this->object->set('description', $this->config->general->description);
        $this->object->set('author', $this->config->general->author);
        $this->object->set('name', $this->config->general->name);
        $this->object->set('dir_name', $this->getProperty('folderName'));

        $this->logger->info('COMPLETED');

        return parent::beforeSave();
    }
}

return 'GitPackageManagementCreateProcessor';
