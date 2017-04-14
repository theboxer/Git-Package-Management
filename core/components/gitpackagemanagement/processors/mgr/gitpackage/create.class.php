<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gpc/gitpackageconfig.class.php';
/**
 * Clone git repository and install it
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementCreateProcessor extends modObjectCreateProcessor {
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';

    /** @var GitPackageConfig $config **/
    private $config = null;

    /** @var string $packageCorePath */
    private $packageCorePath = null;

    /** @var string $packageAssetsPath */
    private $packageAssetsPath = null;

    /** @var string $packageAssetsUrl */
    private $packageAssetsUrl = null;

    /** @var modCategory $category */
    private $category = null;

    private $categoriesMap = array();

   /** @var bool $installFromDirectory */
    private $installFromDirectory = false;

    private $resourceMap = array();

    public function beforeSave() {
//        $url = $this->getProperty('url');
        $folderName = $this->getProperty('folderName');

        /**
         * Check if is set packages dir in MODx system settings
         */
        $packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/');
        if($packagePath == null){
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            $this->modx->log(modX::LOG_LEVEL_INFO,'COMPLETED');
            return false;
        }
        $packagePath .=  '/';

        /**
         * Check if is filled folder name
         */
        if (empty($folderName)) {
            $this->addFieldError('folderName',$this->modx->lexicon('gitpackagemanagement.package_err_ns_folder_name'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ns_folder_name'));
        }

        /**
         * Check if build config and core config are writable
         */
        if (!$this->checkConfig($packagePath . $folderName . '/config.core.php')) {
            $this->addFieldError('folderName',$this->modx->lexicon('gitpackagemanagement.package_err_cc_nw', array('package' => $packagePath . $folderName)));
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_cc_nw', array('package' => $packagePath . $folderName)));
        }

        /**
         * If no error was added in block above, cloning and installation part begins
         */
        if(!$this->hasErrors()){
            /**
             * Parse config file to objects
             */
            if($this->setConfig($packagePath . $folderName) == false) {
                return false;
            }

            /** @var string packageCorePath Path to core of cloned repository*/
            $this->packageCorePath = $packagePath . $folderName . "/core/components/" . $this->config->getLowCaseName() . "/";
            $this->packageCorePath = str_replace('\\', '/', $this->packageCorePath);

            /** @var string packageAssetsPath Path to assets of cloned repository */
            $this->packageAssetsPath = $packagePath . $folderName . "/assets/components/" . $this->config->getLowCaseName() . "/";
            $this->packageAssetsPath = str_replace('\\', '/', $this->packageAssetsPath);

            /** @var string $packagesUrl Base url for packages directory */
            $packagesUrl = $this->modx->getOption('gitpackagemanagement.packages_base_url',null,null);

            /** @var string packageAssetsUrl URL of assets of cloned repository */
            $this->packageAssetsUrl = $packagesUrl . $folderName . '/assets/components/' . $this->config->getLowCaseName() . '/';

            $this->modx->log(modX::LOG_LEVEL_INFO, '<br /><strong>INSTALLATION START</strong>');
            /**
             * Start installation process
             */
            $this->installPackage($packagePath . $folderName);

            /**
             * Create database record for cloned repository
             */
            $this->object->set('version', $this->config->getVersion());
            $this->object->set('description', $this->config->getDescription());
            $this->object->set('author', $this->config->getAuthor());
            $this->object->set('name', $this->config->getName());
            $this->object->set('dir_name', $folderName);
        }

        $this->modx->log(modX::LOG_LEVEL_INFO,'COMPLETED');

        return parent::beforeSave();
    }

    /**
     * Parse config file to objects
     * @param $package string Path to folder of cloned repository
     * @return bool
     */
    private function setConfig($package){
        $configFile = $package . $this->modx->gitpackagemanagement->configPath;
        if(!file_exists($configFile)){
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf'));

            return false;
        }

        $configContent = $this->modx->fromJSON(file_get_contents($configFile));
        $this->config = new GitPackageConfig($this->modx, $package);
        if($this->config->parseConfig($configContent) == false) {
            $this->addFieldError('folderName', $this->modx->lexicon('Config file is invalid.'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Config file is invalid.');
            $this->modx->log(modX::LOG_LEVEL_INFO,'COMPLETED');

            return false;
        }

        $dependencies = $this->config->checkDependencies();
        if ($dependencies !== true) {
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_dependencies'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Dependencies are not matching!');

            foreach ($dependencies as $dependency) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Package ' . $dependency . ' not found!');
            }

            $this->modx->log(modX::LOG_LEVEL_INFO,'COMPLETED');
            return false;
        }

        $this->object->set('config', $this->modx->toJSON($configContent));
        $this->object->save();
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Config file is valid.');
        return true;

    }

    /**
     * Create config.core.php and build.config.php
     * @param $package string Path to folder of cloned repository
     */
    private function createConfigFiles($package){
        $coreConfigContent = "<?php\n" .
                             "define('MODX_CORE_PATH', '".str_replace('\\', '\\\\', MODX_CORE_PATH)."');\n" .
                             "define('MODX_CONFIG_KEY', '".MODX_CONFIG_KEY."');";
        file_put_contents($package . '/config.core.php', $coreConfigContent);

        $this->modx->log(modX::LOG_LEVEL_INFO, 'config.core.php file created.');
    }

    /**
     * Install package from $package folder
     * @param $package string Path to folder of cloned repository
     */
    private function installPackage($package){
        $this->createConfigFiles($package);
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
     * Add extension package if extension package block is in config.json
     */
    private function addExtensionPackage(){
        $extPackage = $this->config->getExtensionPackage();

        if($extPackage !== false){
            $modelPath = $this->packageCorePath . 'model/';
            $modelPath = str_replace('\\', '/', $modelPath);

            $db = $this->config->getDatabase();
            $prefix = $db->getPrefix();

            if (!is_array($extPackage)) $extPackage = array();
            
            if (isset($prefix)) {
                $extPackage['tablePrefix'] = $prefix;
            }

            $this->modx->addExtensionPackage($this->config->getLowCaseName(), $modelPath, $extPackage);
        }
    }

    /**
     * Create namespace with lowCaseName (from config)
     */
    private function createNamespace(){
        $ns = $this->modx->newObject('modNamespace');
        $ns->set('name', $this->config->getLowCaseName());
        $ns->set('path', $this->packageCorePath);
        $ns->set('assets_path', $this->packageAssetsPath);
        $ns->save();

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Namespace '. $this->config->getLowCaseName() . ' created');
    }

    /**
     * Create actions, if actions block is in config
     * Create menus, if menus block is in config and action used in menu has been already created
     */
    private function createMenusAndActions(){
        $actions = array();
        $menus = array();

        /**
         * Create actions if any
         */
        if(count($this->config->getActions()) > 0){
            foreach($this->config->getActions() as $act){
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

            $this->modx->log(modX::LOG_LEVEL_INFO, 'Actions created.');
        }

        /**
         * Crete menus if any
         */
        if(count($this->config->getMenus()) > 0){
            foreach($this->config->getMenus() as $i => $men){
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
                          'permissions' => $men->getPermissions()
                     ),'',true,true);

                if (isset($actions[$men->getAction()])) {
                    $menus[$i]->addOne($actions[$men->getAction()]);
                } else {
                    $menus[$i]->set('action', $men->getAction());
                    $menus[$i]->set('namespace', $men->getNamespace());
                }

                $menus[$i]->save();
            }

            $this->modx->log(modX::LOG_LEVEL_INFO, 'Menus created.');
        }
    }

    /**
     * Create system settings, core_path and assets_url are created automatically
     */
    private function createSystemSettings() {
        $this->createSystemSetting($this->config->getLowCaseName() . '.core_path', $this->packageCorePath, 'textfield', 'Git Package Management Settings');
        $this->createSystemSetting($this->config->getLowCaseName() . '.assets_path', $this->packageAssetsPath, 'textfield', 'Git Package Management Settings');
        $this->createSystemSetting($this->config->getLowCaseName() . '.assets_url', $this->packageAssetsUrl, 'textfield', 'Git Package Management Settings');

        /** @var $setting GitPackageConfigSetting */
        foreach($this->config->getSettings() as $setting){
            $this->createSystemSetting($setting->getNamespacedKey(), $setting->getValue(), $setting->getType(), $setting->getArea());
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'System settings created.');

    }

    /**
     * Support method for createSystemSettings(), insert system setting to database
     * @param $key string
     * @param $value string
     * @param string $xtype string
     * @param string $area string
     */
    private function createSystemSetting($key, $value, $xtype = 'textfield', $area = 'default'){
        $ct = $this->modx->getObject('modSystemSetting',array('key' => $key));
        if (!$ct){
            /** @var modSystemSetting $setting */
            $setting = $this->modx->newObject('modSystemSetting');
            $setting->set('key', $key);
            $setting->set('value', $value);
            $setting->set('namespace', $this->config->getLowCaseName());
            $setting->set('area', $area);
            $setting->set('xtype', $xtype);
            $setting->save();
        }else{
            $ct->set('value', $value);
            $ct->set('namespace', $this->config->getLowCaseName());
            $ct->set('area', $area);
            $ct->set('xtype', $xtype);
            $ct->save();
        }
    }

    /**
     * Create tables in database, if database block is in config
     */
    private function createTables() {
        if($this->config->getDatabase() != null){
            $modelPath = $this->packageCorePath.'model/';
            $this->modx->addPackage($this->config->getLowCaseName(), $modelPath, $this->config->getDatabase()->getPrefix());

            foreach ($this->config->getDatabase()->getSimpleObjects() as $simpleObject) {
                $this->modx->loadClass($simpleObject);
            }

            $manager = $this->modx->getManager();
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating tables:');

            foreach($this->config->getDatabase()->getTables() as $table){
                $manager->createObjectContainer($table);
            }
        }
    }

    /**
     * Create category. Create plugins, chunks and snippets and insert all of them to created category.
     */
    private function createElements(){
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating elements started');
        $this->createCategories();
        $this->createPlugins();
        $this->createChunks();
        $this->createSnippets();
        $this->createTemplates();
        $this->createTVs();
        $this->createWidgets();
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating elements finished');
    }

    /**
     * Create categories for elements
     */
    private function createCategories() {
        $category = $this->modx->getObject('modCategory', array('category' => $this->config->getName()));
        if(!$category){
            $category = $this->modx->newObject('modCategory');
            $category->set('category', $this->config->getName());
            $category->save();
        }

        $this->category = $category;

        /** @var GitPackageConfigCategory[] $categories */
        $categories = $this->config->getCategories();
        foreach ($categories as $category) {
            $categoryObject = $this->modx->newObject('modCategory');
            $categoryObject->set('category', $category->getName());

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

            $categoryObject->set('parent', $parent);
            $categoryObject->save();
            $this->categoriesMap[$category->getName()] = $categoryObject->id;
        }
    }

    /**
     * Create plugins if any
     */
    private function createPlugins(){
        /** @var GitPackageConfigElementPlugin[] $plugins */
        $plugins = $this->config->getElements('plugins');
        if(count($plugins) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating plugins:');
            foreach($plugins as $plugin){
                $pluginObject = $this->modx->newObject('modPlugin');
                $pluginObject->set('name', $plugin->getName());
                $pluginObject->set('description', $plugin->getDescription());
                $pluginObject->set('property_preprocess', $plugin->getPropertyPreProcess());
                if ($this->modx->gitpackagemanagement->getOption('enable_debug')) {
                    $pluginObject->set('plugincode', 'include("' . $this->packageCorePath . $plugin->getFilePath() . '");');
                    $pluginObject->set('static', 0);
                    $pluginObject->set('static_file', '');
                } else {
                    $pluginObject->set('snippet', '');
                    $pluginObject->set('static', 1);
                    $pluginObject->set('static_file', '[[++' . $this->config->getLowCaseName() . '.core_path]]' . $plugin->getFilePath());
                }

                $category = $plugin->getCategory();
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

                $pluginObject->setProperties($plugin->getProperties());
                $pluginObject->save();

                $events = array();

                foreach($plugin->getEvents() as $event){
                    $events[$event]= $this->modx->newObject('modPluginEvent');
                    $events[$event]->fromArray(array(
                          'event' => $event,
                          'priority' => 0,
                          'propertyset' => 0,
                     ),'',true,true);
                }

                $pluginObject->addMany($events);
                $pluginObject->save();
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Plugin ' . $plugin->getName() . ' created.');
            }

        }
    }

    /**
     * Create snippets if any
     */
    private function createSnippets(){
        /** @var GitPackageConfigElementSnippet[] $snippets */
        $snippets = $this->config->getElements('snippets');
        if(count($snippets) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating snippets:');
            foreach($snippets as $snippet){
                $snippetObject = $this->modx->newObject('modSnippet');
                $snippetObject->set('name', $snippet->getName());
                $snippetObject->set('description', $snippet->getDescription());
                $snippetObject->set('property_preprocess', $snippet->getPropertyPreProcess());
                if ($this->modx->gitpackagemanagement->getOption('enable_debug')) {
                    $snippetObject->set('snippet', 'return include("' . $this->packageCorePath . $snippet->getFilePath() . '");');
                    $snippetObject->set('static', 0);
                    $snippetObject->set('static_file', '');
                } else {
                    $snippetObject->set('snippet', '');
                    $snippetObject->set('static', 1);
                    $snippetObject->set('static_file', '[[++' . $this->config->getLowCaseName() . '.core_path]]' . $snippet->getFilePath());
                }

                $category = $snippet->getCategory();
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

                $snippetObject->setProperties($snippet->getProperties());
                $snippetObject->save();

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Snippet ' . $snippet->getName() . ' created.');
            }

        }
    }

    /**
     * Create chunks if any
     */
    private function createChunks(){
        /** @var GitPackageConfigElementChunk[] $chunks */
        $chunks = $this->config->getElements('chunks');
        if(count($chunks) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating chunks:');
            foreach($chunks as $chunk){
                $chunkObject = $this->modx->newObject('modChunk');
                $chunkObject->set('name', $chunk->getName());
                $chunkObject->set('description', $chunk->getDescription());
                $chunkObject->set('property_preprocess', $chunk->getPropertyPreProcess());
                $chunkObject->set('static', 1);
                $chunkObject->set('static_file', '[[++' . $this->config->getLowCaseName() . '.core_path]]' . $chunk->getFilePath());

                $category = $chunk->getCategory();
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

                $chunkObject->setProperties($chunk->getProperties());
                $chunkObject->save();

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Chunk ' . $chunk->getName() . ' created.');
            }

        }
    }

    /**
     * Create templates if any
     */
    private function createTemplates(){
        /** @var GitPackageConfigElementTemplate[] $templates */
        $templates = $this->config->getElements('templates');
        if(count($templates) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating templates:');
            foreach($templates as $template){
                $templatesObject = $this->modx->newObject('modTemplate');
                $templatesObject->set('templatename', $template->getName());
                $templatesObject->set('description', $template->getDescription());
                $templatesObject->set('property_preprocess', $template->getPropertyPreProcess());
                $templatesObject->set('static', 1);
                $templatesObject->set('icon', $template->getIcon());
                $templatesObject->set('static_file', '[[++' . $this->config->getLowCaseName() . '.core_path]]' . $template->getFilePath());

                $category = $template->getCategory();
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

                $templatesObject->setProperties($template->getProperties());
                $templatesObject->save();

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Template ' . $template->getName() . ' created.');
            }

        }
    }

    /**
     * Create tvs if any
     */
    private function createTVs(){
        /** @var GitPackageConfigElementTV[] $tvs */
        $tvs = $this->config->getElements('tvs');
        if(count($tvs) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating TVs:');
            foreach($tvs as $tv){
                /** @var modTemplateVar $tvObject */
                $tvObject = $this->modx->newObject('modTemplateVar');
                $tvObject->set('name', $tv->getName());
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

                $tvObject->setProperties($tv->getProperties());
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

                $this->modx->log(modX::LOG_LEVEL_INFO, 'TV ' . $tv->getName() . ' created.');
            }

        }
    }

    /**
     * Create widgets if any
     */
    private function createWidgets(){
        /** @var GitPackageConfigElementWidget[] $widgets */
        $widgets = $this->config->getElements('widgets');
        if(count($widgets) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating widgets:');
            foreach($widgets as $widget){
                $widgetObject = $this->modx->newObject('modDashboardWidget');
                $widgetObject->set('name', $widget->getName());
                $widgetObject->set('description', $widget->getDescription());
                $widgetObject->set('type', $widget->getWidgetType());
                if ($widget->getWidgetType() == 'file') {
                    $widgetContent = $widget->getPackagePath() . '/core/components/' . $this->config->getLowCaseName() .'/'. $widget->getFilePath();
                } else {
                    $widgetContent = $widget->getFile();
                }
                $widgetObject->set('content', $widgetContent);
                $widgetObject->set('namespace', $this->config->getLowCaseName());
                $widgetObject->set('lexicon', $widget->getLexicon());
                $widgetObject->set('size', $widget->getSize());

                $widgetObject->save();

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Widget ' . $widget->getName() . ' created.');
            }

        }
    }

    /**
     * Clears MODX cache and sets placeholders
     */
    private function clearCache() {
        $this->modx->cacheManager->delete('system_settings/config', array('cache_key' => ''));
        $results = array();
        $partitions = array ('menu' => array ());
        $this->modx->cacheManager->refresh($partitions, $results);

        $this->modx->setPlaceholder('+' . $this->config->getLowCaseName() . '.core_path', $this->packageCorePath);
        $this->modx->setPlaceholder('+' . $this->config->getLowCaseName() . '.assets_path', $this->packageAssetsPath);
        $this->modx->setPlaceholder('+' . $this->config->getLowCaseName() . '.assets_url', $this->packageAssetsUrl);
    }

    /**
     * Check if given config file is writable or can be created
     *
     * @param $config
     * @return bool
     */
    private function checkConfig($config) {
        if (!file_exists($config)) {
            /* make an attempt to create the file */
            @ $hnd = fopen($config, 'w');
            @ fwrite($hnd, '<?php');
            @ fclose($hnd);
        }
        $isWritable = @is_writable($config);
        if (!$isWritable) {
            return false;
        } else {
            return true;
        }
    }

    private function createResources() {
        $resources = $this->config->getResources();

        $this->resourceMap = $this->getResourceMap();

        foreach ($resources as $resource) {
            $this->createResource($resource);
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

    private function setResourceMap() {
        $rmf = $this->config->getAssetsFolder() . 'resourcemap.php';
        file_put_contents($rmf, '<?php return ' . var_export($this->resourceMap, true) . ';');
    }

    private function getResourceMap() {
        $rmf = $this->config->getAssetsFolder() . 'resourcemap.php';

        if (is_readable($rmf)) {
            $content = include $rmf;
        } else {
            $content = array();
        }

        return $content;
    }
}
return 'GitPackageManagementCreateProcessor';
