<?php

namespace GitPackageManagement\Processors\GitPackage;

use GitPackageManagement\Config\Config;
use GitPackageManagement\Config\ElementChunk;
use GitPackageManagement\Config\ElementPlugin;
use GitPackageManagement\Config\ElementSnippet;
use GitPackageManagement\Config\ElementTemplate;
use GitPackageManagement\Config\ElementTV;
use GitPackageManagement\GitPackageManagement;
use GitPackageManagement\Model\GitPackage;
use MODX\Revolution\modCategory;
use MODX\Revolution\modChunk;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modResource;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\modX;
use MODX\Revolution\Processors\Model\RemoveProcessor;

class Remove extends RemoveProcessor
{

    public $classKey = GitPackage::class;

    public $languageTopics = ['gitpackagemanagement:default'];

    public $objectType = 'gitpackagemanagement.package';

    /** @var GitPackage $object */
    public $object;

    /** @var Config $config */
    private $config;

    /** @var string $packageFolder */
    private $packageFolder;

    /** @var GitPackageManagement */
    private $gpm;

    public function initialize()
    {
        $this->gpm = $this->modx->services->get('gitpackagemanagement');
        return parent::initialize();
    }


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

    private function setConfig($packagePath)
    {
        $configFile = $packagePath . GitPackageManagement::$configPath;
        if (!file_exists($configFile)) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nfif');
        }

        $this->config = new Config($this->modx, $packagePath);

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
        $this->removeNamespace();
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

    private function removePlugins()
    {
        /** @var ElementPlugin[] $plugins */
        $plugins = $this->config->getElements('plugins');
        if (count($plugins) > 0) {
            foreach ($plugins as $plugin) {
                /** @var modPlugin $pluginObject */
                $pluginObject = $this->modx->getObject(modPlugin::class, ['name' => $plugin->getName()]);
                if ($pluginObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing plugin ' . $plugin->getName());
                    $pluginObject->remove();
                }
            }
        }
    }

    private function removeSnippets()
    {
        /** @var ElementSnippet[] $snippets */
        $snippets = $this->config->getElements('snippets');
        if (count($snippets) > 0) {
            foreach ($snippets as $snippet) {
                /** @var modSnippet $snippetObject */
                $snippetObject = $this->modx->getObject(modSnippet::class, ['name' => $snippet->getName()]);
                if ($snippetObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing snippet ' . $snippet->getName());
                    $snippetObject->remove();
                }
            }
        }
    }

    private function removeChunks()
    {
        /** @var ElementChunk[] $chunks */
        $chunks = $this->config->getElements('chunks');
        if (count($chunks) > 0) {
            foreach ($chunks as $chunk) {
                /** @var modChunk $chunkObject */
                $chunkObject = $this->modx->getObject(modChunk::class, ['name' => $chunk->getName()]);
                if ($chunkObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing chunk ' . $chunk->getName());
                    $chunkObject->remove();
                }
            }
        }
    }

    private function removeTemplates()
    {
        /** @var ElementTemplate[] $templates */
        $templates = $this->config->getElements('templates');
        if (count($templates) > 0) {
            foreach ($templates as $template) {
                /** @var modTemplate $templateObject */
                $templateObject = $this->modx->getObject(modTemplate::class, ['templatename' => $template->getName()]);
                if ($templateObject) {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing template ' . $template->getName());
                    $templateObject->remove();
                }
            }
        }
    }

    private function removeTVs()
    {
        /** @var ElementTV[] $tvs */
        $tvs = $this->config->getElements('tvs');
        if (count($tvs) > 0) {
            foreach ($tvs as $tv) {
                /** @var modTemplateVar $tvObject */
                $tvObject = $this->modx->getObject(modTemplateVar::class, ['name' => $tv->getName()]);
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
        $cat = $this->modx->getObject(modCategory::class, ['category' => $this->config->getLowCaseName()]);
        if ($cat) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing category ' . $this->config->getLowCaseName());
            $cat->remove();
        }
    }

    private function clearCache()
    {
        $results = [];
        $partitions = ['menu' => []];
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
            $resourceMap = [];
        }

        foreach ($resourceMap as $pageTitle => $id) {
            if ($id == $siteStart) {
                continue;
            }

            $this->modx->updateCollection(modResource::class, ['parent' => 0], ['parent' => $id]);
            $this->modx->removeObject(modResource::class, ['id' => $id]);
        }
    }

    private function removeTables()
    {
        if ($this->config->getDatabase() != null) {
            $modelPath = $this->modx->getOption(
                    $this->config->getLowCaseName() . '.core_path',
                    null,
                    $this->modx->getOption('core_path') . 'components/' . $this->config->getLowCaseName() . '/'
                ) . 'src/Model/';
            $this->modx->addPackage($this->config->getLowCaseName(), $modelPath, $this->config->getDatabase()->getPrefix());

            $manager = $this->modx->getManager();

            foreach ($this->config->getDatabase()->getTables() as $table) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing table ' . $table);
                $manager->removeObjectContainer($table);
            }
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

    private function removeMenus()
    {
        foreach ($this->config->getMenus() as $menu) {
            $menuObject = $this->modx->getObject('modMenu', ['text' => $menu->getText()]);
            $menuObject->remove();
        }
    }

    private function removeNamespace()
    {
        /** @var modNamespace $ns */
        $ns = $this->modx->getObject(modNamespace::class, ['name' => $this->config->getLowCaseName()]);
        if ($ns) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing namespace');
            $ns->remove();
        }
    }

    public function afterRemove()
    {
        /** @var int $deleteFolder */
        $deleteFolder = $this->getProperty('deleteFolder');

        if ($deleteFolder == 1) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Removing directory ' . $this->packageFolder);
            $this->gpm->deleteDir($this->packageFolder);
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'COMPLETED');
        return parent::afterRemove();
    }

}
