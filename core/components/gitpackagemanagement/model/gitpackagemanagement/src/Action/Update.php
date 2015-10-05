<?php
namespace GPM\Action;

use GPM\Config\Config;
use GPM\Config\Object\Action;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

final class Update
{
    use LoggerAwareTrait;
    
    /** @var Config */
    protected $config;
    
    /** @var Config */
    protected $oldConfig;
    
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
    
    /** @var \GitPackage */
    protected $object;
    
    public function __construct(Config $config, \GitPackage $object, LoggerInterface $logger)
    {
        $this->modx =& $config->modx;
        $this->gpm =& $this->modx->gitpackagemanagement;
        $this->logger = $logger;
        $this->config = $config;

        $this->object = $object;

        $this->oldConfig = Config::wakeMe($this->object->config, $this->modx);
    }

    public function update($dbAction = '', $schema = 0)
    {
        if ($this->oldConfig->general->name != $this->config->general->name) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_ccn');
        }

        if ($this->oldConfig->general->lowCaseName != $this->config->general->lowCaseName) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_ccln');
        }

        /** @var \modCategory category */
        $this->category = $this->modx->getObject('modCategory', array('category' => $this->config->general->name));
        if (!$this->category) {
            $this->category = $this->modx->newObject('modCategory');
            $this->category->set('category', $this->config->general->name);
            $this->category->save();
        }

        $this->updateDatabase($dbAction, $schema);
        $this->updateActionsAndMenus();
        $this->updateExtensionPackage();
        $this->updateSystemSettings();

        $notUsedCategories = array();
        $this->updateCategories($notUsedCategories);
        $this->updateElements();
        $this->removeNotUsedCategories($notUsedCategories);

        $this->updateResources();
        $this->clearCache();

        $this->updateObject();
        
        return true;
    }

    public function getOldConfig()
    {
        return $this->oldConfig;
    }
    
    private function updateActionsAndMenus()
    {
        /** @var \modAction[] $actions */
        $actions = $this->modx->getCollection('modAction', array('namespace' => $this->config->general->lowCaseName));
        foreach ($actions as $action) {
            $action->remove();
        }

        foreach ($this->oldConfig->menus as $menu) {
            $menuObject = $this->modx->getObject('modMenu', array('text' => $menu->text));
            $menuObject->remove();
        }

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
                    'permissions' => $men->permissions,
                ), '', true, true);

                if (($men->action instanceof Action) && isset($actions[$men->action->id])) {
                    $menus[$i]->addOne($actions[$men->action->id]);
                } else {
                    $menus[$i]->set('action', $men->action);
                    $menus[$i]->set('namespace', $this->config->general->lowCaseName);
                }

                $menus[$i]->save();
            }
        }
    }

    private function updateExtensionPackage()
    {
        $extPackage = $this->oldConfig->extensionPackage;
        if ($extPackage !== null) {
            $this->modx->removeExtensionPackage($extPackage->name);
            
            if ($this->gpm->not22() === true) {
                $this->modx->removeObject('modExtensionPackage', ['namespace' => $extPackage->namespace, 'name' => $extPackage->name]);
            }
        }

        $extPackage = $this->config->extensionPackage;
        if ($extPackage !== null) {
            if ($this->gpm->not22() === true) {
                $pkg = $this->modx->newObject('modExtensionPackage');
                $pkg->set('namespace', $extPackage->namespace);
                $pkg->set('name', $extPackage->name);
                $pkg->set('path', $extPackage->path);
                $pkg->set('table_prefix', $extPackage->tablePrefix);
                $pkg->set('service_class', $extPackage->serviceClass);
                $pkg->set('service_name', $extPackage->serviceName);
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

    private function updateSystemSettings()
    {
        $oldSettings = $this->oldConfig->systemSettings;
        $notUsedSettings = array_keys($this->oldConfig->systemSettings);
        $notUsedSettings = array_flip($notUsedSettings);

        foreach ($this->config->systemSettings as $key => $setting) {
            /** @var \modSystemSetting $systemSetting */
            $systemSetting = $this->modx->getObject('modSystemSetting', array('key' => $key));
            if (!$systemSetting) {
                $systemSetting = $this->modx->newObject('modSystemSetting');
                $systemSetting->set('key', $key);
                $systemSetting->set('value', $setting->value);
                $systemSetting->set('namespace', $this->config->general->lowCaseName);
                $systemSetting->set('area', $setting->area);
                $systemSetting->set('xtype', $setting->type);
            } else {
                if (!isset($oldSettings[$key]) || $oldSettings[$key]->value != $setting->value) {
                    $systemSetting->set('value', $setting->value);
                }
                $systemSetting->set('area', $setting->area);
                $systemSetting->set('xtype', $setting->type);
            }
            $systemSetting->save();

            if (isset($notUsedSettings[$key])) {
                unset($notUsedSettings[$key]);
            }
        }

        foreach ($notUsedSettings as $key => $value) {
            /** @var \modSystemSetting $setting */
            $setting = $this->modx->getObject('modSystemSetting', array('key' => $key));
            if ($setting) {
                $setting->remove();
            };
        }

        return true;
    }

    private function updateElements()
    {
        $this->updateChunks();
        $this->updateSnippets();
        $this->updateTemplates();
        $this->updatePlugins();
        $this->updateTVs();
    }

    private function updateChunks()
    {
        $notUsedElements = array_keys($this->oldConfig->chunks);
        $notUsedElements = array_flip($notUsedElements);

        foreach ($this->config->chunks as $name => $chunk) {
            /** @var \modChunk $chunkObject */
            $chunkObject = $this->modx->getObject('modChunk', array('name' => $name));
            if (!$chunkObject) {
                $chunkObject = $this->modx->newObject('modChunk');
                $chunkObject->set('name', $chunk->name);
            }

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
            $chunkObject->set('description', $chunk->description);
            $chunkObject->setProperties($chunk->properties);
            $chunkObject->save();

            if (isset($notUsedElements[$name])) {
                unset($notUsedElements[$name]);
            }
        }

        foreach ($notUsedElements as $name => $value) {
            $chunk = $this->modx->getObject('modChunk', array('name' => $name));

            if ($chunk) {
                $chunk->remove();
            }
        }

        return true;
    }
    
    private function updateSnippets()
    {
        $notUsedElements = array_keys($this->oldConfig->snippets);
        $notUsedElements = array_flip($notUsedElements);

        foreach ($this->config->snippets as $name => $snippet) {
            /** @var \modSnippet $snippetObject */
            $snippetObject = $this->modx->getObject('modSnippet', array('name' => $name));
            if (!$snippetObject) {
                $snippetObject = $this->modx->newObject('modSnippet');
                $snippetObject->set('name', $snippet->name);
            }

            if ($this->gpm->getOption('enable_debug')) {
                $snippetObject->set('snippet', 'return include("' . $this->modx->getOption($this->config->general->lowCaseName . '.core_path') . $snippet->filePath . '");');
                $snippetObject->set('static', 0);
                $snippetObject->set('static_file', '');
            } else {
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
            $snippetObject->set('description', $snippet->description);
            $snippetObject->setProperties($snippet->properties);
            $snippetObject->save();

            if (isset($notUsedElements[$name])) {
                unset($notUsedElements[$name]);
            }
        }

        foreach ($notUsedElements as $name => $value) {
            $snippet = $this->modx->getObject('modSnippet', array('name' => $name));

            if ($snippet) {
                $snippet->remove();
            }
        }

        return true;
    }
    
    private function updateTemplates()
    {
        $notUsedElements = array_keys($this->oldConfig->templates);
        $notUsedElements = array_flip($notUsedElements);

        foreach ($this->config->templates as $name => $template) {
            /** @var \modTemplate $templateObject */
            $templateObject = $this->modx->getObject('modTemplate', array('templatename' => $name));
            if (!$templateObject) {
                $templateObject = $this->modx->newObject('modTemplate');
                $templateObject->set('templatename', $template->name);
            }
            $templateObject->set('icon', $template->icon);
            $templateObject->set('static', 1);
            $templateObject->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $template->filePath);

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

            $templateObject->set('category', $category);
            $templateObject->set('description', $template->description);
            $templateObject->setProperties($template->properties);
            $templateObject->save();

            if (isset($notUsedElements[$name])) {
                unset($notUsedElements[$name]);
            }
        }

        foreach ($notUsedElements as $name => $value) {
            $template = $this->modx->getObject('modTemplate', array('templatename' => $name));

            if ($template) {
                $template->remove();
            }
        }

        return true;
    }
    
    private function updatePlugins()
    {
        $notUsedElements = array_keys($this->oldConfig->plugins);
        $notUsedElements = array_flip($notUsedElements);

        foreach ($this->config->plugins as $name => $plugin) {
            /** @var \modPlugin $pluginObject */
            $pluginObject = $this->modx->getObject('modPlugin', array('name' => $name));
            if (!$pluginObject) {
                $pluginObject = $this->modx->newObject('modPlugin');
                $pluginObject->set('name', $plugin->name);
            }

            if ($this->gpm->getOption('enable_debug')) {
                $pluginObject->set('plugincode', 'include("' . $this->modx->getOption($this->config->general->lowCaseName . '.core_path') . $plugin->filePath . '");');
                $pluginObject->set('static', 0);
                $pluginObject->set('static_file', '');
            } else {
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
            $pluginObject->set('description', $plugin->description);

            /** @var \modPluginEvent[] $oldEvents */
            $oldEvents = $pluginObject->getMany('PluginEvents');
            foreach ($oldEvents as $oldEvent) {
                $oldEvent->remove();
            }
            
            /** @var \modPluginEvent[] $events */
            $events = array();
            foreach ($plugin->events as $event) {
                $events[$event] = $this->modx->newObject('modPluginEvent');
                $events[$event]->fromArray(array(
                    'event' => $event,
                    'priority' => 0,
                    'propertyset' => 0,
                ), '', true, true);
            }

            $pluginObject->addMany($events, 'PluginEvents');
            $pluginObject->setProperties($plugin->properties);
            $pluginObject->save();

            if (isset($notUsedElements[$name])) {
                unset($notUsedElements[$name]);
            }
        }

        foreach ($notUsedElements as $name => $value) {
            $plugin = $this->modx->getObject('modPlugin', array('name' => $name));

            if ($plugin) {
                $plugin->remove();
            }
        }

        return true;
    }

    private function updateTVs()
    {
        $notUsedElements = array_keys($this->oldConfig->tvs);
        $notUsedElements = array_flip($notUsedElements);

        foreach ($this->config->tvs as $name => $tv) {
            /** @var \modTemplateVar $tvObject */
            $tvObject = $this->modx->getObject('modTemplateVar', array('name' => $name));

            if (!$tvObject) {
                $tvObject = $this->modx->newObject('modTemplateVar');
                $tvObject->set('name', $tv->name);
            }

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

            /** @var \modTemplateVarTemplate[] $oldTemplates */
            $oldTemplates = $tvObject->getMany('TemplateVarTemplates');

            foreach ($oldTemplates as $oldTemplate) {
                $oldTemplate->remove();
            }

            $tvObject->setProperties($tvObject->getProperties());
            $tvObject->save();

            $templates = $this->modx->getCollection('modTemplate', array('templatename:IN' => $tv->templates));
            foreach ($templates as $template) {
                $templateTVObject = $this->modx->newObject('modTemplateVarTemplate');
                $templateTVObject->set('tmplvarid', $tvObject->id);
                $templateTVObject->set('templateid', $template->id);
                $templateTVObject->save();
            }

            if (isset($notUsedElements[$name])) {
                unset($notUsedElements[$name]);
            }
        }

        foreach ($notUsedElements as $name => $value) {
            /** @var \modTemplateVar $tv */
            $tv = $this->modx->getObject('modTemplateVar', array('name' => $name));

            if ($tv) {
                $tv->remove();
            }
        }

        return true;
    }

    private function updateDatabase($dbAction = '', $schema = 0)
    {
        if (($this->oldConfig->database == null) && ($this->config->database == null)) return;

        if ($this->config->database != null) {
            if ($schema == 1) {
                (new Schema($this->config, $this->logger))->build();
            }
        }

        $modelPath = $this->modx->getOption($this->config->general->lowCaseName . '.core_path', null, $this->modx->getOption('core_path') . 'components/' . $this->config->general->lowCaseName . '/') . 'model/';

        $manager = $this->modx->getManager();

        if ($dbAction == 'recreate') {
            $this->recreateDatabase($modelPath, $manager);
            return;
        }

        if ($this->oldConfig->database != null) {
            $this->modx->addPackage($this->oldConfig->general->lowCaseName, $modelPath, $this->oldConfig->database->prefix);

            foreach ($this->oldConfig->database->simpleObjects as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            $notUsedTables = $this->oldConfig->database->tables;
        } else {
            $notUsedTables = array();
        }

        $notUsedTables = array_flip($notUsedTables);

        if ($this->config->database != null) {
            $this->modx->addPackage($this->config->general->lowCaseName, $modelPath, $this->config->database->prefix);

            foreach ($this->config->database->simpleObjects as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            foreach ($this->config->database->tables as $table) {
                $manager->createObjectContainer($table);

                if (isset($notUsedTables[$table])) {
                    unset($notUsedTables[$table]);

                    if ($dbAction == 'alter') {
                        $this->alterTable($table);
                    }
                }
            }
        }

        foreach ($notUsedTables as $table => $id) {
            $manager->removeObjectContainer($table);
        }
    }

    /**
     * @param string $modelPath
     * @param \xPDOManager $manager
     */
    private function recreateDatabase($modelPath, $manager)
    {
        if ($this->oldConfig->database != null) {
            $this->modx->addPackage($this->oldConfig->general->lowCaseName, $modelPath, $this->oldConfig->database->prefix);

            foreach ($this->oldConfig->database->simpleObjects as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            foreach ($this->oldConfig->database->tables as $table) {
                $manager->removeObjectContainer($table);
            }
        }

        if ($this->config->database != null) {
            $this->modx->addPackage($this->config->general->lowCaseName, $modelPath, $this->config->database->prefix);

            foreach ($this->config->database->simpleObjects as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            foreach ($this->config->database->tables as $table) {
                $manager->createObjectContainer($table);
            }
        }
    }

    private function alterTable($table)
    {
        $this->updateTableColumns($table);
        $this->updateTableIndexes($table);
    }

    private function updateTableColumns($table)
    {
        $tableName = $this->modx->getTableName($table);
        $tableName = str_replace('`', '', $tableName);
        $dbname = $this->modx->getOption('dbname');

        $c = $this->modx->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = :dbName AND table_name = :tableName");

        $c->bindParam(':dbName', $dbname);
        $c->bindParam(':tableName', $tableName);
        $c->execute();

        $unusedColumns = $c->fetchAll(\PDO::FETCH_COLUMN, 0);
        $unusedColumns = array_flip($unusedColumns);

        $meta = $this->modx->getFieldMeta($table);
        $columns = array_keys($meta);

        $m = $this->modx->getManager();

        foreach ($columns as $column) {
            if (isset($unusedColumns[$column])) {
                $m->alterField($table, $column);
                unset($unusedColumns[$column]);
            } else {
                $m->addField($table, $column);
            }
        }

        foreach ($unusedColumns as $column => $v) {
            $m->removeField($table, $column);
        }
    }

    private function clearCache()
    {
        $results = array();
        $partitions = array('menu' => array());
        $this->modx->cacheManager->refresh($partitions, $results);
    }

    private function updateTableIndexes($table)
    {
        $m = $this->modx->getManager();

        $tableName = $this->modx->getTableName($table);
        $tableName = str_replace('`', '', $tableName);
        $dbname = $this->modx->getOption('dbname');

        $c = $this->modx->prepare("SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = :dbName AND table_name = :tableName AND INDEX_NAME != 'PRIMARY'");

        $c->bindParam(':dbName', $dbname);
        $c->bindParam(':tableName', $tableName);
        $c->execute();

        $oldIndexes = $c->fetchAll(\PDO::FETCH_COLUMN, 0);

        foreach ($oldIndexes as $oldIndex) {
            $m->removeIndex($table, $oldIndex);
        }

        $meta = $this->modx->getIndexMeta($table);
        $indexes = array_keys($meta);


        foreach ($indexes as $index) {
            if ($index == 'PRIMARY') continue;
            $m->addIndex($table, $index);
        }
    }

    private function updateResources()
    {
        $resources = $this->config->resources;

        $this->resourceMap = $this->getResourceMap();
        $toRemove = $this->resourceMap;
        $siteStart = $this->modx->getOption('site_start');

        foreach ($resources as $resource) {
            if (isset($this->resourceMap[$resource->pagetitle])) {
                unset($toRemove[$resource->pagetitle]);

                $exists = $this->modx->getObject('modResource', array('id' => $this->resourceMap[$resource->pagetitle]));
                if ($exists) {
                    $resource->setId($exists->id);
                    $this->updateResource($resource);
                } else {
                    $this->createResource($resource);
                }
            } else {
                $this->createResource($resource);
            }
        }

        foreach ($toRemove as $pageTitle => $resource) {
            unset($this->resourceMap[$pageTitle]);

            if ($resource == $siteStart) continue;

            /** @var \modResource $modResource */
            $modResource = $this->modx->getObject('modResource', $resource);
            if ($modResource) {
                $this->modx->updateCollection('modResource', array('parent' => 0), array('parent' => $resource));

                $modResource->remove();
            }
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

    /**
     * @param \GPM\Config\Object\Resource $resource
     */
    private function updateResource($resource)
    {
        $res = $this->modx->runProcessor('resource/update', $resource->toArray());
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

    private function getResourceMap()
    {
        $rmf = $this->config->getAssetsFolder() . 'resourcemap.php';

        if (is_readable($rmf)) {
            $content = include $rmf;
        } else {
            $content = array();
        }

        return $content;
    }

    private function setResourceMap()
    {
        $rmf = $this->config->getAssetsFolder() . 'resourcemap.php';
        file_put_contents($rmf, '<?php return ' . var_export($this->resourceMap, true) . ';');
    }

    private function updateCategories(&$notUsedCategories)
    {
        $notUsedCategories = array_keys($this->oldConfig->categories);
        $notUsedCategories = array_flip($notUsedCategories);

        $categories = $this->config->categories;
        foreach ($categories as $name => $category) {
            $catId = $this->gpm->findCategory($category->getParents(), $this->category->id);

            /** @var \modCategory $categoryObject */
            $categoryObject = $this->modx->getObject('modCategory', $catId);

            if (!$categoryObject) {
                $categoryObject = $this->modx->newObject('modCategory');
                $categoryObject->set('category', $category->name);
            }

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

            $this->categoriesMap[$name] = $categoryObject->id;

            if (isset($notUsedCategories[$name])) {
                unset($notUsedCategories[$name]);
            }
        }

        return true;
    }

    private function removeNotUsedCategories($notUsedCategories)
    {
        foreach ($notUsedCategories as $name => $value) {
            /** @var \modCategory $category */
            $category = $this->modx->getObject('modCategory', array('category' => $name));

            if ($category) {
                $category->remove();
            }
        }
    }
    
    private function updateObject()
    {
        $this->object->set('config', serialize($this->config));
        $this->object->set('description', $this->config->general->description);
        $this->object->set('version', $this->config->general->version);
        $this->object->save();
    }
}