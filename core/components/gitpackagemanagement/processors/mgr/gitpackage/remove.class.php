<?php

/**
 * Remove and uninstall package
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementRemoveProcessor extends modObjectRemoveProcessor
{
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';
    /** @var GitPackage $object */
    public $object;
    /** @var \GPM\Config\Config $config */
    private $config;
    /** @var string $packageFolder */
    private $packageFolder;

    /** @var \GPM\Logger\MODX */
    protected $logger;
    
    public function beforeRemove()
    {
        /**
         * Check if is set packages dir in MODx system settings
         */
        $packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';
        if ($packagePath == null) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            $this->modx->log(modX::LOG_LEVEL_INFO, 'COMPLETED');
            return false;

        }

        $this->packageFolder = $packagePath . $this->object->dir_name;
        $configRet = $this->setConfig($this->packageFolder);
        if ($configRet !== true) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $configRet);
            $this->modx->log(modX::LOG_LEVEL_INFO, 'COMPLETED');
            return false;
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Uninstallation process begun');
        $this->uninstallPackage();

        return parent::beforeRemove();
    }

    public function afterRemove()
    {
        /** @var int $deleteFolder */
        $deleteFolder = $this->getProperty('deleteFolder');

        if ($deleteFolder == 1) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing directory ' . $this->packageFolder);
            $this->modx->gitpackagemanagement->deleteDirectory($this->packageFolder);
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'COMPLETED');
        return parent::afterRemove();
    }

    private function setConfig($packagePath)
    {
        $this->logger = new \GPM\Logger\MODX($this->modx);
        
        $configFile = $packagePath . '/_build/config.json';
        if (!file_exists($configFile)) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nfif');
        }

        try {
            $this->config = new \GPM\Config\Config($this->modx, $this->object->dir_name);
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

            $this->addFieldError('folderName', $e->getMessage());

            return false;
        }
        
        return true;
    }

    private function uninstallPackage()
    {
        $this->removeElements();
        $this->removeResources();
        $this->removeTables();
        $this->removeExtensionPackage();
        $this->removeMenus();
        $this->removeActions();
        $this->removeNamespace();

    }

    private function removeNamespace()
    {
        /** @var modNamespace $ns */
        $ns = $this->modx->getObject('modNamespace', array('name' => $this->config->general->lowCaseName));
        if ($ns) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing namespace');
            $ns->remove();
        }
    }

    private function removeExtensionPackage()
    {
        $extPackage = $this->config->extensionPackage;
        if ($extPackage !== false) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing extension package');
            $this->modx->removeExtensionPackage($this->config->general->lowCaseName);
        }
    }

    private function removeElements()
    {
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing elements');
        $this->removePlugins();
        $this->removeSnippets();
        $this->removeChunks();
        $this->removeTemplates();
        $this->removeTVs();
        $this->removeCategory();
        $this->clearCache();
    }

    private function removeTables()
    {
        if ($this->config->database != null) {
            $modelPath = $this->modx->getOption($this->config->general->lowCaseName . '.core_path', null, $this->modx->getOption('core_path') . 'components/' . $this->config->general->lowCaseName . '/') . 'model/';
            $this->modx->addPackage($this->config->general->lowCaseName, $modelPath, $this->config->database->prefix);

            $manager = $this->modx->getManager();

            foreach ($this->config->database->tables as $table) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing table ' . $table);
                $manager->removeObjectContainer($table);
            }
        }
    }

    private function removePlugins()
    {
        $plugins = $this->config->plugins;
        if (count($plugins) > 0) {
            foreach ($plugins as $plugin) {
                /** @var modPlugin $pluginObject */
                $pluginObject = $this->modx->getObject('modPlugin', array('name' => $plugin->name));
                if ($pluginObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing plugin ' . $plugin->name);
                    $pluginObject->remove();
                }
            }
        }
    }

    private function removeSnippets()
    {
        $snippets = $this->config->snippets;
        if (count($snippets) > 0) {
            foreach ($snippets as $snippet) {
                /** @var modSnippet $snippetObject */
                $snippetObject = $this->modx->getObject('modSnippet', array('name' => $snippet->name));
                if ($snippetObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing snippet ' . $snippet->name);
                    $snippetObject->remove();
                }
            }
        }
    }

    private function removeChunks()
    {
        $chunks = $this->config->chunks;
        if (count($chunks) > 0) {
            foreach ($chunks as $chunk) {
                /** @var modChunk $chunkObject */
                $chunkObject = $this->modx->getObject('modChunk', array('name' => $chunk->name));
                if ($chunkObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing chunk ' . $chunk->name);
                    $chunkObject->remove();
                }
            }
        }
    }

    private function removeTemplates()
    {
        $templates = $this->config->templates;
        if (count($templates) > 0) {
            foreach ($templates as $template) {
                /** @var modTemplate $templateObject */
                $templateObject = $this->modx->getObject('modTemplate', array('templatename' => $template->name));
                if ($templateObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing template ' . $template->name);
                    $templateObject->remove();
                }
            }
        }
    }

    private function removeTVs()
    {
        $tvs = $this->config->tvs;
        if (count($tvs) > 0) {
            foreach ($tvs as $tv) {
                /** @var modTemplateVar $tvObject */
                $tvObject = $this->modx->getObject('modTemplateVar', array('name' => $tv->name));
                if ($tvObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing tv ' . $tv->name);
                    $tvObject->remove();
                }
            }
        }
    }

    private function removeCategory()
    {
        /** @var modCategory $cat */
        $cat = $this->modx->getObject('modCategory', array('category' => $this->config->general->lowCaseName));
        if ($cat) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing category ' . $this->config->general->lowCaseName);
            $cat->remove();
        }

    }

    private function removeMenus()
    {
        foreach ($this->config->menus as $menu) {
            $menuObject = $this->modx->getObject('modMenu', array('text' => $menu->text));
            $menuObject->remove();
        }
    }

    private function removeActions()
    {
        /** @var modAction[] $actions */
        $actions = $this->modx->getCollection('modAction', array('namespace' => $this->config->general->lowCaseName));
        foreach ($actions as $action) {
            $action->remove();
        }
    }

    private function clearCache()
    {
        $results = array();
        $partitions = array('menu' => array());
        $this->modx->cacheManager->refresh($partitions, $results);
    }

    private function removeResources()
    {
        $assetsFolder = $this->config->getAssetsFolder();
        $rmf = $assetsFolder . 'resourcemap.php';
        $siteStart = $this->modx->getOption('site_start');

        if (is_readable($rmf)) {
            $resourceMap = include $rmf;
            unlink($rmf);
            rmdir($assetsFolder);
        } else {
            $resourceMap = array();
        }

        foreach ($resourceMap as $pageTitle => $id) {
            if ($id == $siteStart) continue;

            $this->modx->updateCollection('modResource', array('parent' => 0), array('parent' => $id));
            $this->modx->removeObject('modResource', array('id' => $id));
        }
    }
}

return 'GitPackageManagementRemoveProcessor';