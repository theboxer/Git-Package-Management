<?php

/**
 * Update a config file in database
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementUpdatePackageProcessor extends modObjectUpdateProcessor
{
    /** @var string */
    public $classKey = 'GitPackage';
    
    /** @var array */
    public $languageTopics = ['gitpackagemanagement:default'];
    
    /** @var string */
    public $objectType = 'gitpackagemanagement.package';
    
    /** @var GitPackage $object */
    public $object;
    
    /** @var \GPM\Logger\MODX */
    protected $logger;
    
    /** @var GitPackageManagement */
    protected $gpm;

    public function beforeSave()
    {
        $this->logger = new \GPM\Logger\MODX($this->modx);
        $this->gpm =& $this->modx->gitpackagemanagement;

        $dbAction = ($this->getProperty('alterDatabase', 0) == 1) ? 'alter' : '';
        $dbAction = ($this->getProperty('recreateDatabase', 0) == 1) ? 'recreate' : $dbAction;
        
        try {
            $newConfig = new \GPM\Config\Config($this->modx, $this->object->dir_name);
            $parser = new \GPM\Config\Parser\Parser($this->modx, $newConfig);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $oldConfig = \GPM\Config\Config::wakeMe($this->object->config, $this->modx);

            $updater = new \GPM\Action\Update($newConfig, $oldConfig, $this->logger);
            $updater->update($dbAction, $this->getProperty('buildSchema', 0));
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
        
//        $dependencies = $this->newConfig->checkDependencies();
//        if ($dependencies !== true) {
//            $msg = '<strong>Dependencies check failed!</strong><br />';
//            foreach ($dependencies as $dependency) {
//                $msg .= 'Package ' . $dependency . ' not found!<br />';
//            }
//
//            return $msg;
//        }

        $this->object->set('config', serialize($newConfig));
        $this->object->set('description', $newConfig->general->description);
        $this->object->set('version', $newConfig->general->version);

        return parent::beforeSave();
    }
}

return 'GitPackageManagementUpdatePackageProcessor';