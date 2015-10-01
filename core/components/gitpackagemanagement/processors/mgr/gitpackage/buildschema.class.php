<?php

/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementBuildSchemaProcessor extends modProcessor
{
    /** @var \GPM\Config\Config $config */
    public $config;
    
    public function process()
    {
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        /** @var GitPackage $object */
        $object = $this->modx->getObject('GitPackage', array('id' => $id));
        if (!$object) return $this->failure('GitPackage not found.');

        try {
            $this->config = new \GPM\Config\Config($this->modx, $object->dir_name);
            $parser = new \GPM\Config\Parser\Parser($this->modx, $this->config);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $schema = new \GPM\Action\Schema($this->config, new \GPM\Logger\Null());
            $schema->build();
        } catch (\GPM\Config\Validator\ValidatorException $ve) {
            $message = 'Config file is invalid.<br /><br />';
            $message .= $ve->getMessage();
            
            return $this->failure($message);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage());
        }

        return $this->success();
    }
}

return 'GitPackageManagementBuildSchemaProcessor';
