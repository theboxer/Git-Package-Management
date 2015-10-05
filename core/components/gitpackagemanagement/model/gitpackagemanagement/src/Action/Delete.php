<?php
namespace GPM\Action;

use GPM\Config\Config;
use Psr\Log\LoggerInterface;

final class Delete extends Action
{
    /** @var \GitPackage */
    protected $object;
    
    public function __construct(Config $config, \GitPackage $object, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);
        
        $this->object = $object;
    }

    public function delete()
    {
        $this->logger->info('Removal process started');
        
        $this->removeElements();
        $this->removeResources();
        $this->removeTables();
        $this->removeExtensionPackage();
        $this->removeMenus();
        $this->removeActions();
        $this->removeNamespace();

        $this->removeObject();
    }

    private function removeNamespace()
    {
        /** @var \modNamespace $ns */
        $ns = $this->modx->getObject('modNamespace', array('name' => $this->config->general->lowCaseName));
        if ($ns) {
            $this->logger->info('Removing namespace');
            $ns->remove();
        }
    }

    private function removeExtensionPackage()
    {
        $extPackage = $this->config->extensionPackage;
        if ($extPackage !== null) {
            $this->logger->info('Removing extension package');
            $this->modx->removeExtensionPackage($extPackage->name);
            
            if ($this->gpm->not22() === true) {
                $this->modx->removeObject('modExtensionPackage', ['namespace' => $extPackage->namespace, 'name' => $extPackage->name]);
            }
        }
    }

    private function removeElements()
    {
        $this->logger->info('Removing elements');
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
                $this->logger->info('Removing table ' . $table);
                $manager->removeObjectContainer($table);
            }
        }
    }

    private function removePlugins()
    {
        $plugins = $this->config->plugins;
        if (count($plugins) > 0) {
            foreach ($plugins as $plugin) {
                /** @var \modPlugin $pluginObject */
                $pluginObject = $this->modx->getObject('modPlugin', array('name' => $plugin->name));
                if ($pluginObject) {
                    $this->logger->info('Removing plugin ' . $plugin->name);
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
                /** @var \modSnippet $snippetObject */
                $snippetObject = $this->modx->getObject('modSnippet', array('name' => $snippet->name));
                if ($snippetObject) {
                    $this->logger->info('Removing snippet ' . $snippet->name);
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
                /** @var \modChunk $chunkObject */
                $chunkObject = $this->modx->getObject('modChunk', array('name' => $chunk->name));
                if ($chunkObject) {
                    $this->logger->info('Removing chunk ' . $chunk->name);
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
                /** @var \modTemplate $templateObject */
                $templateObject = $this->modx->getObject('modTemplate', array('templatename' => $template->name));
                if ($templateObject) {
                    $this->logger->info('Removing template ' . $template->name);
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
                /** @var \modTemplateVar $tvObject */
                $tvObject = $this->modx->getObject('modTemplateVar', array('name' => $tv->name));
                if ($tvObject) {
                    $this->logger->info('Removing tv ' . $tv->name);
                    $tvObject->remove();
                }
            }
        }
    }

    private function removeCategory()
    {
        /** @var \modCategory $cat */
        $cat = $this->modx->getObject('modCategory', array('category' => $this->config->general->lowCaseName));
        if ($cat) {
            $this->logger->info('Removing category ' . $this->config->general->lowCaseName);
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
        /** @var \modAction[] $actions */
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

    private function removeObject()
    {
        $this->object->remove();
    }
}