<?php

/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementBuildPackageProcessor extends modProcessor
{
    public $languageTopics = array('gitpackagemanagement:default');

    public function process()
    {
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        /** @var GitPackage $object */
        $object = $this->modx->getObject('GitPackage', array('id' => $id));
        if (!$object) return $this->failure('GitPackage not found.');
        
        $logger = new \GPM\Logger\MODX($this->modx);

        try {
            $config = new \GPM\Config\Config($this->modx, $object->dir_name);
            $parser = new \GPM\Config\Parser\Parser($config);
            $loader = new \GPM\Config\Loader\JSON($parser);
            $loader->loadAll();

            $builder = new \GPM\Action\Build($config, $logger);
            $builder->build();
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

return 'GitPackageManagementBuildPackageProcessor';
