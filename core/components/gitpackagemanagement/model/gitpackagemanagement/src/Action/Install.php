<?php
namespace GPM\Action;

use GPM\Config\Object\Action;

final class Install extends \GPM\Action\Action
{
    /** @var array */
    protected $resourceMap = [];
    
    /** @var \modCategory */
    protected $category;
    
    /** @var array */
    protected $categoriesMap = [];
    public function install()
    {
        $this->logger->info('INSTALLATION START');

        $this->checkDependencies();
        
        $this->createConfigFile();
        $this->createNamespace();
        $this->createMenusAndActions();
        $this->createSystemSettings();
        $this->createTables();
        $this->addExtensionPackage();
        $this->clearCache();
        $this->createElements();
        $this->createResources();
        
        $this->createObject();
    }

    /**
     * Create config.core.php
     */
    private function createConfigFile()
    {
        $this->checkConfig($this->config->packagePath . '/config.core.php');
        
        $coreConfigContent = "<?php\n" .
            "define('MODX_CORE_PATH', '" . str_replace('\\', '\\\\', MODX_CORE_PATH) . "');\n" .
            "define('MODX_CONFIG_KEY', '" . MODX_CONFIG_KEY . "');";
        file_put_contents($this->config->packagePath . '/config.core.php', $coreConfigContent);

        $this->logger->info('config.core.php file created.');
    }

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
            throw new \Exception($this->modx->lexicon('gitpackagemanagement.package_err_cc_nw', array('package' => $this->config->packagePath)));
        }
    }

    /**
     * Create namespace with lowCaseName (from config)
     */
    private function createNamespace()
    {
        $ns = $this->modx->getObject('modNamespace', $this->config->general->lowCaseName);
        if ($ns) {
            return;
        }
        
        $ns = $this->modx->newObject('modNamespace');
        $ns->set('name', $this->config->general->lowCaseName);
        $ns->set('path', $this->config->general->corePath);
        $ns->set('assets_path', $this->config->general->assetsPath);
        $ns->save();

        $this->logger->info('Namespace ' . $this->config->general->lowCaseName . ' created');
    }

    /**
     * Create actions, if actions block is in config
     * Create menus, if menus block is in config and action used in menu has been already created
     */
    private function createMenusAndActions()
    {
        $actions = array();
        $menus = array();

        /**
         * Create actions if any
         */
        if (count($this->config->actions) > 0) {
            foreach ($this->config->actions as $act) {
                /** @var \modAction[] $actions */
                $actions[$act->id] = $act->getObject();
                $actions[$act->id]->save();
            }

            $this->logger->info('Actions created.');
        }

        /**
         * Crete menus if any
         */
        if (count($this->config->menus) > 0) {
            foreach ($this->config->menus as $i => $men) {
                /** @var \modMenu[] $menus */
                $menus[$i] = $men->getObject();

                if (($men->action instanceof Action) && isset($actions[$men->action->id])) {
                    $menus[$i]->addOne($actions[$men->action->id]);
                }

                $menus[$i]->save();
            }

            $this->logger->info('Menus created.');
        }
    }

    /**
     * Create system settings, core_path and assets_url are created automatically
     */
    private function createSystemSettings()
    {
        foreach ($this->config->systemSettings as $setting) {
            $this->createSystemSetting($setting->getNamespacedKey(), $setting->value, $setting->type, $setting->area);
        }

        $this->logger->info('System settings created.');

    }

    /**
     * Support method for createSystemSettings(), insert system setting to database
     * @param $key string
     * @param $value string
     * @param string $xtype string
     * @param string $area string
     */
    private function createSystemSetting($key, $value, $xtype = 'textfield', $area = 'default')
    {
        /** @var \modSystemSetting $setting */
        $ct = $this->modx->getObject('modSystemSetting', array('key' => $key));
        if (!$ct) {
            $ct = $this->modx->newObject('modSystemSetting');
            $ct->set('key', $key);
        }
        
        $ct->set('value', $value);
        $ct->set('namespace', $this->config->general->lowCaseName);
        $ct->set('area', $area);
        $ct->set('xtype', $xtype);
        
        $ct->save();
    }

    /**
     * Create tables in database, if database block is in config
     */
    private function createTables()
    {
        if ($this->config->database === null) return;
        
        $modelPath = $this->config->general->corePath . 'model/';
        $this->modx->addPackage($this->config->general->lowCaseName, $modelPath, $this->config->database->prefix);

        foreach ($this->config->database->simpleObjects as $simpleObject) {
            $this->modx->loadClass($simpleObject);
        }

        $manager = $this->modx->getManager();
        $this->logger->info('Creating tables:');

        foreach ($this->config->database->tables as $table) {
            $manager->createObjectContainer($table);
        }
    }

    /**
     * Add extension package if extension package block is in config.json
     */
    private function addExtensionPackage()
    {
        $extPackage = $this->config->extensionPackage;
        
        if ($extPackage !== null) {
            if ($this->gpm->not22() === true) {
                $pkg = $extPackage->getObject();
                $pkg->save();
            } else {
                $options = [
                    'tablePrefix' => $extPackage->tablePrefix
                ];

                if ($extPackage->serviceClass != '') {
                    $options['serviceName'] = $extPackage->serviceName;
                    $options['serviceClass'] = $extPackage->serviceClass;
                }

                $this->modx->addExtensionPackage($extPackage->name, $extPackage->path, $options);
            }
        }
    }

    /**
     * Clears MODX cache and sets placeholders
     */
    private function clearCache()
    {
        $this->modx->cacheManager->delete('system_settings/config', array('cache_key' => ''));
        $results = array();
        $partitions = array('menu' => array());
        $this->modx->cacheManager->refresh($partitions, $results);

        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.core_path', $this->config->general->corePath);
        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.assets_path', $this->config->general->assetsPath);
        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.assets_url', $this->config->general->assetsURL);
    }

    /**
     * Create category. Create plugins, chunks and snippets and insert all of them to created category.
     */
    private function createElements()
    {
        $this->logger->info('Creating elements started');
        $this->createCategories();
        $this->createPlugins();
        $this->createChunks();
        $this->createSnippets();
        $this->createTemplates();
        $this->createTVs();
        $this->logger->info('Creating elements finished');
    }

    private function createResources()
    {
        $resources = $this->config->resources;

        $this->resourceMap = [];

        foreach ($resources as $resource) {
            $this->createResource($resource);
        }

        $this->setResourceMap();
    }

    /**
     * @param \GPM\Config\Object\Resource $resource
     */
    private function createResource($resource)
    {
        $res = $this->modx->runProcessor('resource/create', $resource->toArray());
        $resObject = $res->getObject();

        if ($resObject && isset($resObject['id'])) {
            /** @var \modResource $modResource */
            $modResource = $this->modx->getObject('modResource', array('id' => $resObject['id']));

            if ($modResource) {
                $this->resourceMap[$modResource->pagetitle] = $modResource->id;

                $tvs = $resource->tvs;
                foreach ($tvs as $tv) {
                    $modResource->setTVValue($tv['name'], $tv['value']);
                }
            }
        }
    }

    private function setResourceMap()
    {
        $rmf = $this->config->getAssetsFolder() . 'resourcemap.php';
        file_put_contents($rmf, '<?php return ' . var_export($this->resourceMap, true) . ';');
    }

    /**
     * Create categories for elements
     */
    private function createCategories()
    {
        $category = $this->modx->getObject('modCategory', array('category' => $this->config->general->name));
        if (!$category) {
            $category = $this->modx->newObject('modCategory');
            $category->set('category', $this->config->general->name);
            $category->save();
        }

        $this->category = $category;

        $categories = $this->config->categories;
        foreach ($categories as $category) {
            $categoryObject = $category->getObject();

            $categoryObject->save();
            $this->categoriesMap[$category->name] = $categoryObject->id;
        }
    }

    /**
     * Create plugins if any
     */
    private function createPlugins()
    {
        $plugins = $this->config->plugins;
        if (count($plugins) > 0) {
            $this->logger->info('Creating plugins:');
            foreach ($plugins as $plugin) {
                $pluginObject = $plugin->getObject();

                $category = $plugin->category;
                if (!empty($category)) {
                    if (isset($this->categoriesMap[$category])) {
                        $category = $this->categoriesMap[$category];
                    } else {
                        $category = $this->category->id;
                    }
                } else {
                    $category = $this->category->id;
                }
                $pluginObject->set('category', $category);

                $pluginObject->save();

                
                if ($pluginObject->save()) {
                    $this->logger->info(' - ' . $plugin->name);
                } else {
                    $this->logger->error(' - ' . $plugin->name . ' failed');
                }
            }
        }
    }

    /**
     * Create snippets if any
     */
    private function createSnippets()
    {
        $snippets = $this->config->snippets;
        if (count($snippets) > 0) {
            $this->logger->info('Creating snippets:');
            foreach ($snippets as $snippet) {
                $snippetObject = $snippet->getObject();

                $category = $snippet->category;
                if (!empty($category)) {
                    if (isset($this->categoriesMap[$category])) {
                        $category = $this->categoriesMap[$category];
                    } else {
                        $category = $this->category->id;
                    }
                } else {
                    $category = $this->category->id;
                }
                $snippetObject->set('category', $category);

                if ($snippetObject->save()) {
                    $this->logger->info(' - ' . $snippet->name);
                } else {
                    $this->logger->error(' - ' . $snippet->name . ' failed');
                }

            }
        }
    }

    /**
     * Create chunks if any
     */
    private function createChunks()
    {
        $chunks = $this->config->chunks;
        if (count($chunks) > 0) {
            $this->logger->info('Creating chunks:');
            foreach ($chunks as $chunk) {
                $chunkObject = $chunk->getObject();

                $category = $chunk->category;
                if (!empty($category)) {
                    if (isset($this->categoriesMap[$category])) {
                        $category = $this->categoriesMap[$category];
                    } else {
                        $category = $this->category->id;
                    }
                } else {
                    $category = $this->category->id;
                }
                $chunkObject->set('category', $category);

                if ($chunkObject->save()){
                    $this->logger->info(' - ' . $chunk->name);
                } else {
                    $this->logger->error(' - ' . $chunk->name . ' failed');
                }

            }

        }
    }

    /**
     * Create templates if any
     */
    private function createTemplates()
    {
        $templates = $this->config->templates;
        if (count($templates) > 0) {
            $this->logger->info('Creating templates:');
            foreach ($templates as $template) {
                $templatesObject = $template->getObject();

                $category = $template->category;
                if (!empty($category)) {
                    if (isset($this->categoriesMap[$category])) {
                        $category = $this->categoriesMap[$category];
                    } else {
                        $category = $this->category->id;
                    }
                } else {
                    $category = $this->category->id;
                }
                $templatesObject->set('category', $category);

                if ($templatesObject->save()) {
                    $this->logger->info(' - ' . $template->name);
                } else {
                    $this->logger->error(' - ' . $template->name . ' failed');
                }
            }
        }
    }

    /**
     * Create tvs if any
     */
    private function createTVs()
    {
        $tvs = $this->config->tvs;
        if (count($tvs) > 0) {
            $this->logger->info('Creating TVs:');
            foreach ($tvs as $tv) {
                $tvObject = $tv->getObject();

                $category = $tv->category;
                if (!empty($category)) {
                    if (isset($this->categoriesMap[$category])) {
                        $category = $this->categoriesMap[$category];
                    } else {
                        $category = $this->category->id;
                    }
                } else {
                    $category = $this->category->id;
                }
                $tvObject->set('category', $category);

                if ($tvObject->save()) {
                    $this->logger->info(' - ' . $tv->name);
                } else {
                    $this->logger->error(' - ' . $tv->name . ' failed');
                }

                /** @var \modTemplate[] $templates */
                $templates = $this->modx->getCollection('modTemplate', ['templatename:IN' => $tv->templates]);
                foreach ($templates as $template) {
                    $templateTVObject = $this->modx->newObject('modTemplateVarTemplate');
                    $templateTVObject->set('tmplvarid', $tvObject->id);
                    $templateTVObject->set('templateid', $template->id);
                    $templateTVObject->save();
                }
            }
        }
    }

    private function createObject()
    {
        /** @var \GitPackage $object */
        $object = $this->modx->newObject('GitPackage');
        $object->set('config', serialize($this->config));
        $object->set('version', $this->config->general->version);
        $object->set('description', $this->config->general->description);
        $object->set('author', $this->config->general->author);
        $object->set('name', $this->config->general->name);
        $object->set('dir_name', $this->config->folderName);
        $object->save();
    }
}