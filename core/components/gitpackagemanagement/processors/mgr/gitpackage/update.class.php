<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gpc/gitpackageconfig.class.php';
/**
 * Update a config file in database
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */

class GitPackageManagementUpdatePackageProcessor extends modObjectUpdateProcessor {
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';
    /** @var GitPackage $object */
    public $object;
    /** @var GitPackageConfig $oldConfig */
    private $oldConfig;
    /** @var GitPackageConfig $oldConfig */
    private $newConfig;
    private $category;
    private $categoriesMap = array();
    private $recreateDatabase = 0;
    private $alterDatabase = 0;
    private $packagePath = null;
    private $resourceMap = array();

    public function beforeSet() {
        $this->packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/');
        if($this->packagePath == null){
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }
        $this->packagePath .=  '/';

        $packagePath = $this->packagePath . $this->object->dir_name;

        $configFile = $packagePath . $this->modx->gitpackagemanagement->configPath;
        if(!file_exists($configFile)){
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $config = file_get_contents($configFile);

        $config = $this->modx->fromJSON($config);
        
        if (is_null($config)) {
            return 'JSON config file is not valid.';
        }

        $this->newConfig = new GitPackageConfig($this->modx, $packagePath);
        $this->newConfig->parseConfig($config);
        if ($this->newConfig->error->hasErrors()) {
            return implode('<br />', $this->newConfig->error->getErrors());
        }

        $dependencies = $this->newConfig->checkDependencies();
        if ($dependencies !== true) {
            $msg = '<strong>Dependencies check failed!</strong><br />';
            foreach ($dependencies as $dependency) {
                $msg .= 'Package ' . $dependency . ' not found!<br />';
            }

            return $msg;
        }

        $this->oldConfig = new GitPackageConfig($this->modx, $packagePath);
        $this->oldConfig->parseConfig($this->modx->fromJSON($this->object->config));

        $this->recreateDatabase = $this->getProperty('recreateDatabase', 0);
        $this->alterDatabase = $this->getProperty('alterDatabase', 0);

        $update = $this->update();
        if($update !== true){
            return $update;
        }

        $this->setProperty('config', $this->modx->toJSON($config));

        $this->object->set('updatedon', time());

        return parent::beforeSet();
    }

    private function update() {
//        $vc = version_compare($this->oldConfig->getVersion(), $this->newConfig->getVersion());
//        if($vc != -1){
//            return $this->modx->lexicon('gitpackagemanagement.package_err_nvil');
//        }

        if($this->oldConfig->getName() != $this->newConfig->getName()){
            return $this->modx->lexicon('gitpackagemanagement.package_err_ccn');
        }

        if($this->oldConfig->getLowCaseName() != $this->newConfig->getLowCaseName()){
            return $this->modx->lexicon('gitpackagemanagement.package_err_ccln');
        }

        $this->object->set('description', $this->newConfig->getDescription());
        $this->object->set('version', $this->newConfig->getVersion());

        /** @var modCategory category */
        $this->category = $this->modx->getObject('modCategory', array('category' => $this->newConfig->getName()));
        if(!$this->category){
            $this->category = $this->modx->newObject('modCategory');
            $this->category->set('category', $this->newConfig->getName());
            $this->category->save();
        }

        $this->updateDatabase();
        $this->updateActionsAndMenus();
        $this->updateExtensionPackage();
        $this->updateSystemSettings();

        $notUsedCategories = array();
        $this->updateCategories($notUsedCategories);
        $this->updateElements();
        $this->removeNotUsedCategories($notUsedCategories);

        $this->updateResources();
        $this->clearCache();

        return true;
    }

    private function updateActionsAndMenus() {
        /** @var modAction[] $actions */
        $actions = $this->modx->getCollection('modAction', array('namespace' => $this->newConfig->getLowCaseName()));
        foreach($actions as $action){
            $action->remove();
        }

        foreach ($this->oldConfig->getMenus() as $menu) {
            $menuObject = $this->modx->getObject('modMenu', array ('text' => $menu->getText()));
            if ($menuObject) $menuObject->remove();
        }

        $actions = array();
        $menus = array();

        /**
         * Create actions if any
         */
        if(count($this->newConfig->getActions()) > 0){
            foreach($this->newConfig->getActions() as $act){
                /** @var modAction[] $actions */
                $actions[$act->getId()] = $this->modx->newObject('modAction');
                $actions[$act->getId()]->fromArray(array(
                    'namespace' => $act->getNamespace(),
                    'controller' => $act->getController(),
                    'haslayout' => $act->getHasLayout(),
                    'lang_topics' => $act->getLangTopics(),
                    'assets' => $act->getAssets(),
                ),'',true,true);
                $actions[$act->getId()]->save();
            }
        }

        /**
         * Crete menus if any
         */
        if(count($this->newConfig->getMenus()) > 0){
            foreach($this->newConfig->getMenus() as $i => $men){
                /** @var modMenu[] $menus */
                $menus[$i] = $this->modx->newObject('modMenu');
                $menus[$i]->fromArray(array(
                    'text' => $men->getText(),
                    'parent' => $men->getParent(),
                    'description' => $men->getDescription(),
                    'icon' => $men->getIcon(),
                    'menuindex' => $men->getMenuIndex(),
                    'params' => $men->getParams(),
                    'handler' => $men->getHandler(),
                    'permissions' => $men->getPermissions(),
                ),'',true,true);

                if (isset($actions[$men->getAction()])) {
                    $menus[$i]->addOne($actions[$men->getAction()]);
                } else {
                    $menus[$i]->set('action', $men->getAction());
                    $menus[$i]->set('namespace', $men->getNamespace());
                }

                $menus[$i]->save();
            }
        }

    }

    private function updateExtensionPackage() {
        $extPackage = $this->oldConfig->getExtensionPackage();
        if($extPackage !== false){
            $this->modx->removeExtensionPackage($this->newConfig->getLowCaseName());
        }

        $extPackage = $this->newConfig->getExtensionPackage();
        if($extPackage !== false){
            $modelPath = $this->packagePath . $this->object->dir_name . "/core/components/" . $this->newConfig->getLowCaseName() . "/" . 'model/';
            $modelPath = str_replace('\\', '/', $modelPath);

            $db = $this->newConfig->getDatabase();
            if ($db) {
                $prefix = $db->getPrefix();
            }

            if (!is_array($extPackage)) $extPackage = array();
            
            if (isset($prefix)) {
                $extPackage['tablePrefix'] = $prefix;
            }

            $this->modx->addExtensionPackage($this->newConfig->getLowCaseName(), $modelPath, $extPackage);
        }
    }

    private function updateSystemSettings() {
        $oldSettings = $this->oldConfig->getSettings();
        $notUsedSettings = array_keys($this->oldConfig->getSettings());
        $notUsedSettings = array_flip($notUsedSettings);

        foreach($this->newConfig->getSettings() as $key => $setting){
            /** @var modSystemSetting $systemSetting */
            $systemSetting = $this->modx->getObject('modSystemSetting', array('key' => $key));
            if (!$systemSetting){
                $systemSetting = $this->modx->newObject('modSystemSetting');
                $systemSetting->set('key', $key);
                $systemSetting->set('value',$setting->getValue());
                $systemSetting->set('namespace', $this->newConfig->getLowCaseName());
                $systemSetting->set('area',$setting->getArea());
                $systemSetting->set('xtype', $setting->getType());
            }else{
                if(!isset($oldSettings[$key]) || $oldSettings[$key]->getValue() != $setting->getValue()){
                    $systemSetting->set('value',$setting->getValue());
                }
                $systemSetting->set('area',$setting->getArea());
                $systemSetting->set('xtype', $setting->getType());
            }
            $systemSetting->save();

            if(isset($notUsedSettings[$key])){
                unset($notUsedSettings[$key]);
            }
        }

        foreach($notUsedSettings as $key => $value){
            /** @var modSystemSetting $setting */
            $setting = $this->modx->getObject('modSystemSetting', array('key' => $key));
            if ($setting) {
                $setting->remove();
            };
        }

        return true;
    }

    private function updateElements() {
        $this->updateElement('Chunk');
        $this->updateElement('Snippet');
        $this->updateElement('Template');
        $this->updateElement('Plugin');
        $this->updateTV();
        $this->updateWidget();
    }

    private function updateElement($type) {
        $configType = strtolower($type). 's';
        $notUsedElements = array_keys($this->oldConfig->getElements($configType));
        $notUsedElements = array_flip($notUsedElements);

        foreach($this->newConfig->getElements($configType) as $name => $element){
            if($type == 'Template'){
                /** @var modElement $elementObject */
                $elementObject = $this->modx->getObject('mod'.$type, array('templatename' => $name));
            }else{
                $elementObject = $this->modx->getObject('mod'.$type, array('name' => $name));
            }
            if (!$elementObject){
                $elementObject = $this->modx->newObject('mod'.$type);
                if($type == 'Template'){
                    $elementObject->set('templatename', $element->getName());
                }else{
                    $elementObject->set('name', $element->getName());
                }
            }
            if($type == 'Template'){
                $elementObject->set('icon', $element->getIcon());
            }
            
            if ($this->modx->gitpackagemanagement->getOption('enable_debug') && ($type == 'Plugin' || $type == 'Snippet')) {
                if($type == 'Plugin') {
                    $elementObject->set('disabled', $element->getDisabled());
                    $elementObject->set('plugincode', 'include("' . $this->modx->getOption($this->newConfig->getLowCaseName() . '.core_path') . $element->getFilePath() . '");');
                } else {
                    $elementObject->set('snippet', 'return include("' . $this->modx->getOption($this->newConfig->getLowCaseName() . '.core_path') . $element->getFilePath() . '");');
                }

                $elementObject->set('static', 0);
                $elementObject->set('static_file', '');
            } else {
                $elementObject->set('static', 1);
                $elementObject->set('static_file', '[[++' . $this->newConfig->getLowCaseName() . '.core_path]]' . $element->getFilePath());
            }

            $category = $element->getCategory();
            if (!empty($category)) {
                if (isset($this->categoriesMap[$category])) {
                    $category = $this->categoriesMap[$category];
                } else {
                    $category = $this->category->id;
                }
            } else {
                $category = $this->category->id;
            }

            $elementObject->set('category', $category);
            $elementObject->set('description', $element->getDescription());
            $elementObject->set('property_preprocess', $element->getPropertyPreProcess());

            if($type == 'Plugin'){
                /** @var modPluginEvent[] $oldEvents */
                $oldEvents = $elementObject->getMany('PluginEvents');
                foreach($oldEvents as $oldEvent){
                    $oldEvent->remove();
                }
                $events = array();

                foreach($element->getEvents() as $event){
                    $events[$event]= $this->modx->newObject('modPluginEvent');
                    $events[$event]->fromArray(array(
                                                    'event' => $event,
                                                    'priority' => 0,
                                                    'propertyset' => 0,
                                               ),'',true,true);
                }

                $elementObject->addMany($events, 'PluginEvents');
            }

            $elementObject->setProperties($element->getProperties());
            $elementObject->save();

            if(isset($notUsedElements[$name])){
                unset($notUsedElements[$name]);
            }
        }

        foreach($notUsedElements as $name => $value){
            if($type == 'Template'){
                $element = $this->modx->getObject('mod'.$type, array('templatename' => $name));
            }else{
                $element = $this->modx->getObject('mod'.$type, array('name' => $name));
            }

            if ($element) {
                $element->remove();
            }
        }

        return true;
    }

    private function updateTV() {
        $notUsedElements = array_keys($this->oldConfig->getElements('tvs'));
        $notUsedElements = array_flip($notUsedElements);

        /** @var GitPackageConfigElementTV $tv */
        foreach($this->newConfig->getElements('tvs') as $name => $tv){
            /** @var modTemplateVar $tvObject */
            $tvObject = $this->modx->getObject('modTemplateVar', array('name' => $name));

            if (!$tvObject){
                $tvObject = $this->modx->newObject('modTemplateVar');
                $tvObject->set('name', $tv->getName());
            }

            $tvObject->set('caption', $tv->getCaption());
            $tvObject->set('description', $tv->getDescription());
            $tvObject->set('property_preprocess', $tv->getPropertyPreProcess());
            $tvObject->set('type', $tv->getInputType());

            $category = $tv->getCategory();
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

            $tvObject->set('elements', $tv->getInputOptionValues());
            $tvObject->set('rank', $tv->getSortOrder());
            $tvObject->set('default_text', $tv->getDefaultValue());
            $tvObject->set('display', $tv->getDisplay());
            
            $inputProperties = $tv->getInputProperties();
            if (!empty($inputProperties)) {
                $tvObject->set('input_properties',$inputProperties);
            }

            $outputProperties = $tv->getOutputProperties();
            if (!empty($outputProperties)) {
                $tvObject->set('output_properties',$outputProperties);
            }

            /** @var modTemplateVarTemplate[] $oldTemplates */
            $oldTemplates = $tvObject->getMany('TemplateVarTemplates');

            foreach($oldTemplates as $oldTemplate){
                $oldTemplate->remove();
            }

            $tvObject->setProperties($tvObject->getProperties());
            $tvObject->save();

            $templates = $tv->getTemplates();
            if (!empty($templates)) {
                $templates = $this->modx->getCollection('modTemplate', array('templatename:IN' => $tv->getTemplates()));
                foreach ($templates as $template) {
                    $templateTVObject = $this->modx->newObject('modTemplateVarTemplate');
                    $templateTVObject->set('tmplvarid', $tvObject->id);
                    $templateTVObject->set('templateid', $template->id);
                    $templateTVObject->save();
                }
            }

            if(isset($notUsedElements[$name])){
                unset($notUsedElements[$name]);
            }
        }

        foreach($notUsedElements as $name => $value){
            /** @var modTemplateVar $tv */
            $tv = $this->modx->getObject('modTemplateVar', array('name' => $name));

            if ($tv) {
                $tv->remove();
            }
        }

        return true;
    }

    private function updateWidget() {
        $notUsedElements = array_keys($this->oldConfig->getElements('widgets'));
        $notUsedElements = array_flip($notUsedElements);

        /** @var GitPackageConfigElementWidget $widget */
        foreach($this->newConfig->getElements('widgets') as $name => $widget){
            /** @var modDashboardWidget $widgetObject */
            $widgetObject = $this->modx->getObject('modDashboardWidget', array('name' => $name));

            if (!$widgetObject){
                $widgetObject = $this->modx->newObject('modDashboardWidget');
                $widgetObject->set('name', $widget->getName());
            }

            $widgetObject->set('description', $widget->getDescription());
            $widgetObject->set('type', $widget->getWidgetType());
            if ($widget->getWidgetType() == 'file') {
                $widgetContent = $widget->getPackagePath() . '/core/components/' . $this->newConfig->getLowCaseName() .'/'. $widget->getFilePath();
            } else {
                $widgetContent = $widget->getFile();
            }
            $widgetObject->set('content', $widgetContent);
            $widgetObject->set('namespace', $this->newConfig->getLowCaseName());
            $widgetObject->set('lexicon', $widget->getLexicon());
            $widgetObject->set('size', $widget->getSize());

            $widgetObject->save();

            if(isset($notUsedElements[$name])){
                unset($notUsedElements[$name]);
            }
        }

        foreach($notUsedElements as $name => $value){
            /** @var modDashboardWidget $widget */
            $widget = $this->modx->getObject('modDashboardWidget', array('name' => $name));

            if ($widget) {
                $widget->remove();
            }
        }

        return true;
    }

    private function updateDatabase() {
        if (($this->oldConfig->getDatabase() == null) && ($this->newConfig->getDatabase() == null)) return;

        if($this->newConfig->getDatabase() != null){
            $buildSchema = $this->getProperty('buildSchema', 0);
            if ($buildSchema) {
                $this->buildSchema();
            }
        }

        $modelPath = $this->modx->getOption($this->newConfig->getLowCaseName().'.core_path',null,$this->modx->getOption('core_path').'components/'.$this->newConfig->getLowCaseName().'/').'model/';

        $manager = $this->modx->getManager();

        if($this->recreateDatabase){
            $this->recreateDatabase($modelPath, $manager);
            return;
        }

        if($this->oldConfig->getDatabase() != null){
            $this->modx->addPackage($this->oldConfig->getLowCaseName(), $modelPath, $this->oldConfig->getDatabase()->getPrefix());

            foreach ($this->oldConfig->getDatabase()->getSimpleObjects() as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            $notUsedTables = $this->oldConfig->getDatabase()->getTables();
        }else{
            $notUsedTables = array();
        }

        $notUsedTables = array_flip($notUsedTables);

        if($this->newConfig->getDatabase() != null){
            $this->modx->addPackage($this->newConfig->getLowCaseName(), $modelPath, $this->newConfig->getDatabase()->getPrefix());

            foreach ($this->newConfig->getDatabase()->getSimpleObjects() as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            foreach($this->newConfig->getDatabase()->getTables() as $table){
                $manager->createObjectContainer($table);

                if(isset($notUsedTables[$table])){
                    unset($notUsedTables[$table]);

                    if ($this->alterDatabase) {
                        $this->alterTable($table);
                    }
                }
            }
        }

        foreach($notUsedTables as $table => $id){
            $manager->removeObjectContainer($table);
        }
    }

    /**
     * @param string $modelPath
     * @param xPDOManager $manager
     */
    private function recreateDatabase($modelPath, $manager){
        if($this->oldConfig->getDatabase() != null){
            $this->modx->addPackage($this->oldConfig->getLowCaseName(), $modelPath, $this->oldConfig->getDatabase()->getPrefix());

            foreach ($this->oldConfig->getDatabase()->getSimpleObjects() as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            foreach($this->oldConfig->getDatabase()->getTables() as $table){
                $manager->removeObjectContainer($table);
            }
        }

        if($this->newConfig->getDatabase() != null){
            $this->modx->addPackage($this->newConfig->getLowCaseName(), $modelPath, $this->newConfig->getDatabase()->getPrefix());

            foreach ($this->newConfig->getDatabase()->getSimpleObjects() as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            foreach($this->newConfig->getDatabase()->getTables() as $table){
                $manager->createObjectContainer($table);
            }
        }
    }

    private function alterTable($table) {
        $this->updateTableColumns($table);
        $this->updateTableIndexes($table);
    }

    private function updateTableColumns($table) {
        $tableName = $this->modx->getTableName($table);
        $tableName = str_replace('`', '', $tableName);
        $dbname = $this->modx->getOption('dbname');

        $c = $this->modx->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = :dbName AND table_name = :tableName");

        $c->bindParam(':dbName', $dbname);
        $c->bindParam(':tableName', $tableName);
        $c->execute();

        $unusedColumns = $c->fetchAll(PDO::FETCH_COLUMN, 0);
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

    private function clearCache() {
        $results = array ();
        $partitions = array ('menu' => array ());
        $this->modx->cacheManager->refresh($partitions, $results);
    }

    private function updateTableIndexes($table) {
        $m = $this->modx->getManager();

        $tableName = $this->modx->getTableName($table);
        $tableName = str_replace('`', '', $tableName);
        $dbname = $this->modx->getOption('dbname');

        $c = $this->modx->prepare("SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = :dbName AND table_name = :tableName AND INDEX_NAME != 'PRIMARY'");

        $c->bindParam(':dbName', $dbname);
        $c->bindParam(':tableName', $tableName);
        $c->execute();

        $oldIndexes = $c->fetchAll(PDO::FETCH_COLUMN, 0);

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

    private function buildSchema() {
        $this->modx->gitpackagemanagement->runProcessor('mgr/gitpackage/buildschema', $this->getProperties());
    }

    private function updateResources() {
        $resources = $this->newConfig->getResources();

        $this->resourceMap = $this->getResourceMap();
        $toRemove = $this->resourceMap;
        $siteStart = $this->modx->getOption('site_start');

        foreach ($resources as $resource) {
            if (isset($this->resourceMap[$resource->getPagetitle()])) {
                unset($toRemove[$resource->getPagetitle()]);

                $exists = $this->modx->getObject('modResource', array('id' => $this->resourceMap[$resource->getPagetitle()]));
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

            /** @var modResource $modResource */
            $modResource = $this->modx->getObject('modResource', $resource);
            if ($modResource) {
                $this->modx->updateCollection('modResource', array('parent' => 0), array('parent' => $resource));

                $modResource->remove();
            }
        }

        $this->setResourceMap();
    }

    /**
     * @param GitPackageConfigResource $resource
     */
    private function createResource($resource) {
        $res = $this->modx->runProcessor('resource/create', $resource->toArray());
        $resObject = $res->getObject();

        if ($resObject && isset($resObject['id'])) {
            /** @var modResource $modResource */
            $modResource = $this->modx->getObject('modResource', array('id' => $resObject['id']));

            if ($modResource) {
                $this->resourceMap[$modResource->pagetitle] = $modResource->id;

                $tvs = $resource->getTvs();
                foreach ($tvs as $tv) {
                    $modResource->setTVValue($tv['name'], $tv['value']);
                }
            }
        }
    }

    /**
     * @param GitPackageConfigResource $resource
     */
    private function updateResource($resource) {
        $res = $this->modx->runProcessor('resource/update', $resource->toArray());
        $resObject = $res->getObject();

        if ($resObject && isset($resObject['id'])) {
            /** @var modResource $modResource */
            $modResource = $this->modx->getObject('modResource', array('id' => $resObject['id']));

            if ($modResource) {
                $this->resourceMap[$modResource->pagetitle] = $modResource->id;

                $tvs = $resource->getTvs();
                foreach ($tvs as $tv) {
                    $modResource->setTVValue($tv['name'], $tv['value']);
                }
            }
        }
    }

    private function getResourceMap() {
        $rmf = $this->newConfig->getAssetsFolder() . 'resourcemap.php';

        if (is_readable($rmf)) {
            $content = include $rmf;
        } else {
            $content = array();
        }

        return $content;
    }

    private function setResourceMap() {
        $rmf = $this->newConfig->getAssetsFolder() . 'resourcemap.php';
        file_put_contents($rmf, '<?php return ' . var_export($this->resourceMap, true) . ';');
    }

    private function updateCategories(&$notUsedCategories)
    {
        $notUsedCategories = array_keys($this->oldConfig->getCategories());
        $notUsedCategories = array_flip($notUsedCategories);

        /** @var GitPackageConfigCategory[] $categories */
        $categories = $this->newConfig->getCategories();
        foreach($categories as $name => $category){
            $catId = $this->modx->gitpackagemanagement->findCategory($category->getParents(), $this->category->id);

            /** @var modCategory $categoryObject */
            $categoryObject = $this->modx->getObject('modCategory', $catId);

            if (!$categoryObject){
                $categoryObject = $this->modx->newObject('modCategory');
                $categoryObject->set('category', $category->getName());
            }

            $parent = $category->getParentObject();
            if (!empty($parent)) {
                $catId = $this->modx->gitpackagemanagement->findCategory($parent->getParents(), $this->category->id);
                /** @var modCategory $parentObject */
                $parentObject = $this->modx->getObject('modCategory', $catId);
                if ($parentObject) {
                    $parent = $parentObject->id;
                } else {
                    $parent = $this->category->id;
                }
            } else {
                $parent = $this->category->id;
            }

            $rank = $category->getRank();
            if (!empty($rank)) {
                $categoryObject->set('rank', $rank);
            }

            $categoryObject->set('parent', $parent);

            $categoryObject->save();

            $this->categoriesMap[$name] = $categoryObject->id;

            if(isset($notUsedCategories[$name])){
                unset($notUsedCategories[$name]);
            }
        }

        return true;
    }

    private function removeNotUsedCategories($notUsedCategories)
    {
        foreach($notUsedCategories as $name => $value){
            /** @var modCategory $category */
            $category = $this->modx->getObject('modCategory', array('category' => $name));

            if ($category) {
                $category->remove();
            }
        }
    }
}
return 'GitPackageManagementUpdatePackageProcessor';
