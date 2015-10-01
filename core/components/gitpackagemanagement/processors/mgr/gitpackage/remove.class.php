<?php

/**
 * Remove and uninstall package
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementRemoveProcessor extends modProcessor
{
    public $languageTopics = array('gitpackagemanagement:default');
    
    public function process()
    {
        /** @var GitPackage $object */
        $object = $this->modx->getObject('GitPackage', intval($this->getProperty('id')));
        if (!$object) {
            return $this->failure('Package not found');
        }
        
        $logger = new \GPM\Logger\MODX($this->modx);

        try {
            $config = new \GPM\Config\Config($this->modx, $object->dir_name);
            $parser = new \GPM\Config\Parser\Parser($this->modx, $config);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $deleter = new \GPM\Action\Delete($config, $object, $logger);
            $deleter->delete();
            
            $logger->info('COMPLETED');
        } catch (\GPM\Config\Validator\ValidatorException $ve) {
            $logger->error('Config file is invalid.' . PHP_EOL);
            $logger->error($ve->getMessage());
            $logger->info('COMPLETED');

            return $this->failure();
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            $logger->info('COMPLETED');

            return $this->failure();
        }

        return $this->success();
    }

    public function getLanguageTopics() {
        return $this->languageTopics;
    }
}

return 'GitPackageManagementRemoveProcessor';