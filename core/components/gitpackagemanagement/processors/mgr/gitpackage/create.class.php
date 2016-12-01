<?php

/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementCreateProcessor extends modProcessor
{
    public $languageTopics = array('gitpackagemanagement:default');

    public function process()
    {
        $logger = new \GPM\Logger\MODX($this->modx);
        
        try {
            $config = new \GPM\Config\Config($this->modx, $this->getProperty('folderName'));
            $parser = new \GPM\Config\Parser\Parser($config);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $installer = new \GPM\Action\Install($config, $logger);
            $installer->install();
        } catch (\GPM\Config\Validator\ValidatorException $ve) {
            $this->addFieldError('folderName', $this->modx->lexicon('Config file is invalid.'));
            $logger->error('Config file is invalid.' . PHP_EOL);
            $logger->error($ve->getMessage());
            $logger->info('COMPLETED');

            return $this->failure();
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            $logger->info('COMPLETED');

            $this->addFieldError('folderName', $e->getMessage());

            return $this->failure();
        }

        $logger->info('COMPLETED');

        return $this->success();
    }

    public function getLanguageTopics() {
        return $this->languageTopics;
    }
}

return 'GitPackageManagementCreateProcessor';
