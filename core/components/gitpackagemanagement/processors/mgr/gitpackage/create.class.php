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

    public function beforeSave()
    {
        $this->logger = new \GPM\Logger\MODX($this->modx);
        
        $folderName = $this->getProperty('folderName');

        /**
         * Check if is set packages dir in MODx system settings
         */
        $packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/');
        if ($packagePath == null) {
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            $this->logger->error($this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            $this->logger->info('COMPLETED');
            
            return false;
        }
        $packagePath .= '/';

        /**
         * Check if is filled folder name
         */
        if (empty($folderName)) {
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_ns_folder_name'));
            $this->logger->error($this->modx->lexicon('gitpackagemanagement.package_err_ns_folder_name'));
        }

        /**
         * Check if core config is writable
         */
        if (!$this->checkConfig($packagePath . $folderName . '/config.core.php')) {
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_cc_nw', array('package' => $packagePath . $folderName)));
            $this->logger->error($this->modx->lexicon('gitpackagemanagement.package_err_cc_nw', array('package' => $packagePath . $folderName)));
        }

        /**
         * If no error was added in block above, cloning and installation part begins
         */
        if (!$this->hasErrors()) {
            /**
             * Parse config file to objects
             */
            if ($this->setConfig($packagePath, $folderName) == false) {
                return false;
            }

            $installer = new \GPM\Action\Install($this->config, $this->logger);
            $installer->install();

            /**
             * Create database record for cloned repository
             */
            $this->object->set('version', $this->config->general->version);
            $this->object->set('description', $this->config->general->description);
            $this->object->set('author', $this->config->general->author);
            $this->object->set('name', $this->config->general->name);
            $this->object->set('dir_name', $folderName);
        }

        $this->logger->info('COMPLETED');

        return parent::beforeSave();
    }

    /**
     * Parse config file to objects
     * @param $packagePath
     * @param $folderName
     * @return bool
     */
    private function setConfig($packagePath, $folderName)
    {
        $package = $packagePath . $folderName;
        $configFile = $package . $this->modx->gitpackagemanagement->configPath;
        if (!file_exists($configFile)) {
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf'));

            return false;
        }

        try {
            $this->config = new \GPM\Config\Config($this->modx, $package, $folderName);
            $parser = new \GPM\Config\Parser\Parser($this->modx, $this->config);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();
        } catch (\GPM\Config\Validator\ValidatorException $ve) {
            $this->addFieldError('folderName', $this->modx->lexicon('Config file is invalid.'));
            $this->logger->error('Config file is invalid.<br /><br />');
            $this->logger->error($ve->getMessage());
            $this->logger->info('COMPLETED');
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->info('COMPLETED');
            
            return false;
        }

        $dependencies = $this->config->checkDependencies();
        if ($dependencies !== true) {
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_dependencies'));
            $this->logger->error('Dependencies are not matching!');

            foreach ($dependencies as $dependency) {
                $this->logger->error('Package ' . $dependency . ' not found!');
            }

            $this->logger->info('COMPLETED');
            return false;
        }

        $this->object->set('config', serialize($this->config));
        $this->object->save();
        $this->logger->info('Config file is valid.');

        return true;
    }

    /**
     * Check if given config file is writable or can be created
     *
     * @param $config
     * @return bool
     */
    private function checkConfig($config)
    {
        if (!file_exists($config)) {
            /* make an attempt to create the file */
            @ $hnd = fopen($config, 'w');
            @ fwrite($hnd, '<?php');
            @ fclose($hnd);
        }
        $isWritable = @is_writable($config);
        if (!$isWritable) {
            return false;
        } else {
            return true;
        }
    }
}

return 'GitPackageManagementCreateProcessor';
