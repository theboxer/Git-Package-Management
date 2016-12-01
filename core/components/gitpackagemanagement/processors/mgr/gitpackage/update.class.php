<?php

/**
 * Update a config file in database
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementUpdatePackageProcessor extends modProcessor
{
    /** @var array */
    public $languageTopics = ['gitpackagemanagement:default'];
    
    public function process()
    {
        /** @var GitPackage $object */
        $object = $this->modx->getObject('GitPackage', intval($this->getProperty('id')));
        if (!$object) {
            return $this->failure('Package not found');
        }
        
        $logger = new \GPM\Logger\MODX($this->modx);

        $dbAction = ($this->getProperty('alterDatabase', 0) == 1) ? 'alter' : '';
        $dbAction = ($this->getProperty('recreateDatabase', 0) == 1) ? 'recreate' : $dbAction;
        
        try {
            $newConfig = new \GPM\Config\Config($this->modx, $object->dir_name);
            $parser = new \GPM\Config\Parser\Parser($newConfig);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $updater = new \GPM\Action\Update($newConfig, $object, $logger);
            $updater->update($dbAction, $this->getProperty('buildSchema', 0));
        } catch (\GPM\Config\Validator\ValidatorException $ve) {
            return $this->failure($ve->getMessage());
        } catch (\Exception $e) {
            return $this->failure($e->getMessage());
        }

        return $this->success();
    }

    public function getLanguageTopics() {
        return $this->languageTopics;
    }
}

return 'GitPackageManagementUpdatePackageProcessor';