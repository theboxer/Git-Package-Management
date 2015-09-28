<?php
namespace GPM\Action;

use GPM\Config\Config;
use Psr\Log\LoggerInterface;

final class Install
{
    /** @var Config */
    protected $config;
    
    /** @var LoggerInterface */
    protected $logger;
    
    /** @var \modX */
    protected $modx;
    
    /** @var \GitPackageManagement */
    protected $gpm;
    
    /** @var array */
    protected $resourceMap = [];
    
    /** @var \modCategory */
    protected $category;
    
    /** @var array */
    protected $categoriesMap = [];
    
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->modx =& $config->modx;
        $this->gpm =& $this->modx->gitpackagemanagement;
    }

    public function install()
    {
        $this->logger->info('INSTALLATION START');

        $this->createConfigFile();
        $this->createNamespace();
        $this->createMenusAndActions();
        $this->createSystemSettings();
        $this->createTables();
        $this->addExtensionPackage();
        $this->clearCache();
        $this->createElements();
        $this->createResources();
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
        $ns->set('path', $this->config->packagePath);
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
                $actions[$act->id] = $this->modx->newObject('modAction');
                $actions[$act->id]->fromArray(array(
                    'namespace' => $this->config->general->lowCaseName,
                    'controller' => $act->controller,
                    'haslayout' => $act->hasLayout,
                    'lang_topics' => $act->langTopics,
                    'assets' => $act->assets,
                ), '', true, true);
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
                $menus[$i] = $this->modx->newObject('modMenu');
                $menus[$i]->fromArray(array(
                    'text' => $men->text,
                    'parent' => $men->parent,
                    'description' => $men->description,
                    'icon' => $men->icon,
                    'menuindex' => $men->menuIndex,
                    'params' => $men->params,
                    'handler' => $men->handler,
                ), '', true, true);

                if (isset($actions[$men->action])) {
                    $menus[$i]->addOne($actions[$men->action]);
                } else {
                    $menus[$i]->set('action', $men->action);
                    $menus[$i]->set('namespace', $this->config->general->lowCaseName);
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
        $this->createSystemSetting($this->config->general->lowCaseName . '.core_path', $this->config->general->corePath, 'textfield', 'Git Package Management Settings');
        $this->createSystemSetting($this->config->general->lowCaseName . '.assets_path', $this->config->general->assetsPath, 'textfield', 'Git Package Management Settings');
        $this->createSystemSetting($this->config->general->lowCaseName . '.assets_url', $this->config->general->assetsURL, 'textfield', 'Git Package Management Settings');

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
        // @TODO
        $extPackage = $this->config->extensionPackage;
        if ($extPackage !== false) {
            $modelPath = $this->config->general->corePath . 'model/';
            $modelPath = str_replace('\\', '/', $modelPath);
            if ($extPackage === true) {
                $this->modx->addExtensionPackage($this->config->general->lowCaseName, $modelPath);
            } else {
                $this->modx->addExtensionPackage($this->config->general->lowCaseName, $modelPath, array(
                    'serviceName' => $extPackage['serviceName'],
                    'serviceClass' => $extPackage['serviceClass']
                ));
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
            $categoryObject = $this->modx->newObject('modCategory');
            $categoryObject->set('category', $category->name);

            $parent = $category->getParentObject();
            if (!empty($parent)) {
                $catId = $this->gpm->findCategory($parent->getParents(), $this->category->id);
                /** @var \modCategory $parentObject */
                $parentObject = $this->modx->getObject('modCategory', $catId);
                if ($parentObject) {
                    $parent = $parentObject->id;
                } else {
                    $parent = $this->category->id;
                }
            } else {
                $parent = $this->category->id;
            }

            $categoryObject->set('parent', $parent);
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
                $pluginObject = $this->modx->newObject('modPlugin');
                $pluginObject->set('name', $plugin->name);
                $pluginObject->set('description', $plugin->description);
                if ($this->gpm->getOption('enable_debug')) {
                    $pluginObject->set('plugincode', 'include("' . $this->modx->getOption($this->config->general->lowCaseName . '.core_path') . $plugin->filePath . '");');
                    $pluginObject->set('static', 0);
                    $pluginObject->set('static_file', '');
                } else {
                    $pluginObject->set('snippet', '');
                    $pluginObject->set('static', 1);
                    $pluginObject->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $plugin->filePath);
                }

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

                $pluginObject->setProperties($plugin->properties);
                $pluginObject->save();

                /** @var \modPluginEvent[] $events */
                $events = [];

                foreach ($plugin->events as $event) {
                    $events[$event] = $this->modx->newObject('modPluginEvent');
                    $events[$event]->fromArray(array(
                        'event' => $event,
                        'priority' => 0,
                        'propertyset' => 0,
                    ), '', true, true);
                }

                $pluginObject->addMany($events);
                $pluginObject->save();
                $this->logger->info('Plugin ' . $plugin->name . ' created.');
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
                $snippetObject = $this->modx->newObject('modSnippet');
                $snippetObject->set('name', $snippet->name);
                $snippetObject->set('description', $snippet->description);
                if ($this->gpm->getOption('enable_debug')) {
                    $snippetObject->set('snippet', 'return include("' . $this->modx->getOption($this->config->general->lowCaseName . '.core_path') . $snippet->filePath . '");');
                    $snippetObject->set('static', 0);
                    $snippetObject->set('static_file', '');
                } else {
                    $snippetObject->set('snippet', '');
                    $snippetObject->set('static', 1);
                    $snippetObject->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $snippet->filePath);
                }

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

                $snippetObject->setProperties($snippet->properties);
                $snippetObject->save();

                $this->logger->info('Snippet ' . $snippet->name . ' created.');
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
                $chunkObject = $this->modx->newObject('modChunk');
                $chunkObject->set('name', $chunk->name);
                $chunkObject->set('description', $chunk->description);
                $chunkObject->set('static', 1);
                $chunkObject->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $chunk->filePath);

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

                $chunkObject->setProperties($chunk->properties);
                $chunkObject->save();

                $this->logger->info('Chunk ' . $chunk->name . ' created.');
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
                $templatesObject = $this->modx->newObject('modTemplate');
                $templatesObject->set('templatename', $template->name);
                $templatesObject->set('description', $template->description);
                $templatesObject->set('static', 1);
                $templatesObject->set('icon', $template->icon);
                $templatesObject->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $template->filePath);

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

                $templatesObject->setProperties($template->properties);
                $templatesObject->save();

                $this->logger->info('Template ' . $template->name . ' created.');
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
                /** @var \modTemplateVar $tvObject */
                $tvObject = $this->modx->newObject('modTemplateVar');
                $tvObject->set('name', $tv->name);
                $tvObject->set('caption', $tv->caption);
                $tvObject->set('description', $tv->description);
                $tvObject->set('type', $tv->type);

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

                $tvObject->set('elements', $tv->inputOptionValues);
                $tvObject->set('rank', $tv->sortOrder);
                $tvObject->set('default_text', $tv->defaultValue);

                $inputProperties = $tv->inputProperties;
                if (!empty($inputProperties)) {
                    $tvObject->set('input_properties', $inputProperties);
                }

                $outputProperties = $tv->outputProperties;
                if (!empty($outputProperties)) {
                    $tvObject->set('output_properties', $outputProperties[0]);
                }

                $tvObject->setProperties($tv->properties);
                $tvObject->save();

                $templates = $this->modx->getCollection('modTemplate', array('templatename:IN' => $tv->templates));
                foreach ($templates as $template) {
                    $templateTVObject = $this->modx->newObject('modTemplateVarTemplate');
                    $templateTVObject->set('tmplvarid', $tvObject->id);
                    $templateTVObject->set('templateid', $template->id);
                    $templateTVObject->save();
                }

                $this->logger->info('TV ' . $tv->name . ' created.');
            }

        }
    }
}