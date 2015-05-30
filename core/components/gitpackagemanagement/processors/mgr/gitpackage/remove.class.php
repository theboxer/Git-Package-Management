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
        $configFile = $packagePath . $this->modx->gitpackagemanagement->configPath;
        if (!file_exists($configFile)) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nfif');
        }

        $this->config = new \GPM\Config\Config($this->modx, $packagePath);

        if ($this->config->parseConfig($this->modx->fromJSON(file_get_contents($configFile))) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
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
        $ns = $this->modx->getObject('modNamespace', array('name' => $this->config->getLowCaseName()));
        if ($ns) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing namespace');
            $ns->remove();
        }
    }

    private function removeExtensionPackage()
    {
        $extPackage = $this->config->getExtensionPackage();
        if ($extPackage !== false) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing extension package');
            $this->modx->removeExtensionPackage($this->config->getLowCaseName());
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
        if ($this->config->getDatabase() != null) {
            $modelPath = $this->modx->getOption($this->config->getLowCaseName() . '.core_path', null, $this->modx->getOption('core_path') . 'components/' . $this->config->getLowCaseName() . '/') . 'model/';
            $this->modx->addPackage($this->config->getLowCaseName(), $modelPath, $this->config->getDatabase()->getPrefix());

            $manager = $this->modx->getManager();

            foreach ($this->config->getDatabase()->getTables() as $table) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing table ' . $table);
                $manager->removeObjectContainer($table);
            }
        }
    }

    private function removePlugins()
    {
        /** @var \GPM\Config\Element\Plugin[] $plugins */
        $plugins = $this->config->getElements('plugins');
        if (count($plugins) > 0) {
            foreach ($plugins as $plugin) {
                /** @var modPlugin $pluginObject */
                $pluginObject = $this->modx->getObject('modPlugin', array('name' => $plugin->getName()));
                if ($pluginObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing plugin ' . $plugin->getName());
                    $pluginObject->remove();
                }
            }
        }
    }

    private function removeSnippets()
    {
        /** @var \GPM\Config\Element\Snippet[] $snippets */
        $snippets = $this->config->getElements('snippets');
        if (count($snippets) > 0) {
            foreach ($snippets as $snippet) {
                /** @var modSnippet $snippetObject */
                $snippetObject = $this->modx->getObject('modSnippet', array('name' => $snippet->getName()));
                if ($snippetObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing snippet ' . $snippet->getName());
                    $snippetObject->remove();
                }
            }
        }
    }

    private function removeChunks()
    {
        /** @var \GPM\Config\Element\Chunk[] $chunks */
        $chunks = $this->config->getElements('chunks');
        if (count($chunks) > 0) {
            foreach ($chunks as $chunk) {
                /** @var modChunk $chunkObject */
                $chunkObject = $this->modx->getObject('modChunk', array('name' => $chunk->getName()));
                if ($chunkObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing chunk ' . $chunk->getName());
                    $chunkObject->remove();
                }
            }
        }
    }

    private function removeTemplates()
    {
        /** @var \GPM\Config\Element\Template[] $templates */
        $templates = $this->config->getElements('templates');
        if (count($templates) > 0) {
            foreach ($templates as $template) {
                /** @var modTemplate $templateObject */
                $templateObject = $this->modx->getObject('modTemplate', array('templatename' => $template->getName()));
                if ($templateObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing template ' . $template->getName());
                    $templateObject->remove();
                }
            }
        }
    }

    private function removeTVs()
    {
        /** @var \GPM\Config\Element\TV[] $tvs */
        $tvs = $this->config->getElements('tvs');
        if (count($tvs) > 0) {
            foreach ($tvs as $tv) {
                /** @var modTemplateVar $tvObject */
                $tvObject = $this->modx->getObject('modTemplateVar', array('name' => $tv->getName()));
                if ($tvObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing tv ' . $tv->getName());
                    $tvObject->remove();
                }
            }
        }
    }

    private function removeCategory()
    {
        /** @var modCategory $cat */
        $cat = $this->modx->getObject('modCategory', array('category' => $this->config->getLowCaseName()));
        if ($cat) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing category ' . $this->config->getLowCaseName());
            $cat->remove();
        }

    }

    private function removeMenus()
    {
        foreach ($this->config->getMenus() as $menu) {
            $menuObject = $this->modx->getObject('modMenu', array('text' => $menu->getText()));
            $menuObject->remove();
        }
    }

    private function removeActions()
    {
        /** @var modAction[] $actions */
        $actions = $this->modx->getCollection('modAction', array('namespace' => $this->config->getLowCaseName()));
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