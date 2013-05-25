<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gitpackageconfig.class.php';
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

    /** @var $config GitPackageConfig **/
    private $config = null;

    private $packageCorePath = null;
    private $packageAssetsPath = null;
    private $packageAssetsUrl = null;

    private $category = null;

    private $installFromDirectory = false;

    public function beforeSave() {
        $url = $this->getProperty('url');
        $folderName = $this->getProperty('folderName');

        /**
         * Check if is set packages dir in MODx system settings
         */
        $packagePath = $this->modx->getOption('gitpackagemanagement.packages_dir',null,null);
        if($packagePath == null){
            $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            $this->modx->log(modX::LOG_LEVEL_INFO,'COMPLETED');
            return false;
        }

        /**
         * Check if is filled folder name
         */
        if (empty($folderName)) {
            $this->addFieldError('folderName',$this->modx->lexicon('gitpackagemanagement.package_err_ns_folder_name'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ns_folder_name'));
        }

        $configFile = $packagePath . $folderName . $this->modx->gitpackagemanagement->configPath;
        if(file_exists($configFile)){
            $this->installFromDirectory = true;
        }

        /**
         * If URL is empty and folder with folderName exists, installation process will start from that folder.
         * If URL is empty and folder with folderName NOT exist, error is showed.
         */
        if (empty($url)) {
            if(!$this->installFromDirectory){
                $this->addFieldError('url',$this->modx->lexicon('gitpackagemanagement.package_err_ns_url'));
                $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ns_url'));
            }
        } else if ($this->doesAlreadyExist(array('url' => $url))) {
            $this->addFieldError('url',$this->modx->lexicon('gitpackagemanagement.package_err_ae_url'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ae_url'));
        }

        /**
         * If no error was added in block above, cloning and installation part begins
         */
        if(!$this->hasErrors()){

            /**
             * If installation from folder has to be done, cloning of git repository is skipped
             */
            if(!$this->installFromDirectory){

                /**
                 * If folder with folderName already exist render error
                 */
                if(is_dir($packagePath . $folderName)){
                    $this->addFieldError('folderName', $this->modx->lexicon('gitpackagemanagement.package_err_ae_folder_name'));
                    $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('gitpackagemanagement.package_err_ae_folder_name'));
                    $this->modx->log(modX::LOG_LEVEL_INFO,'COMPLETED');
                    return false;
                }

                /**
                 * Create folder for repository
                 */
                mkdir($packagePath . $folderName);
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Folder for package created.');

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Cloning of repository started.');
                /**
                 * Clone repository
                 */
                $this->modx->gitpackagemanagement->createRepo($packagePath . $folderName, $url);
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Git repository cloned.');
            }

            /**
             * Parse config file to objects
             */
            if($this->setConfig($packagePath . $folderName) == false) {
                return false;
            }

            /** @var string packageCorePath Path to core of cloned repository*/
            $this->packageCorePath = $packagePath . $folderName . "/core/components/" . $this->config->getLowCaseName() . "/";
            /** @var string packageAssetsPath Path to assets of cloned repository */
            $this->packageAssetsPath = $packagePath . $folderName . "/assets/components/" . $this->config->getLowCaseName() . "/";

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
            $this->object->Set('dir_name', $folderName);
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
            $this->addFieldError('url', $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf'));
            if(!$this->installFromDirectory){
                $this->modx->gitpackagemanagement->deleteDirectory($package);
            }
            return false;
        }

        $configContent = $this->modx->fromJSON(file_get_contents($configFile));
        $this->config = new GitPackageConfig($this->modx, $package);
        if($this->config->parseConfig($configContent) == false) {
            $this->addFieldError('url', $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf'));
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Config file is invalid.');
            if(!$this->installFromDirectory){
                $this->modx->gitpackagemanagement->deleteDirectory($package);
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

        $buildConfigContent = "<?php\n" .
                              "/**\n" .
                              "* Define the MODX path constants necessary for installation\n" .
                              "*\n" .
                              "* @package monitoring\n" .
                              "* @subpackage build\n" .
                              "*/\n" .
                              "define('MODX_BASE_PATH', '".MODX_BASE_PATH."');\n" .
                              "define('MODX_CORE_PATH', MODX_BASE_PATH . 'core/');\n" .
                              "define('MODX_MANAGER_PATH', MODX_BASE_PATH . 'manager/');\n" .
                              "define('MODX_CONNECTORS_PATH', MODX_BASE_PATH . 'connectors/');\n" .
                              "define('MODX_ASSETS_PATH', MODX_BASE_PATH . 'assets/');\n\n" .
                              "define('MODX_BASE_URL','".MODX_BASE_URL."');\n" .
                              "define('MODX_CORE_URL', MODX_BASE_URL . 'core/');\n" .
                              "define('MODX_MANAGER_URL', MODX_BASE_URL . 'manager/');\n" .
                              "define('MODX_CONNECTORS_URL', MODX_BASE_URL . 'connectors/');\n" .
                              "define('MODX_ASSETS_URL', MODX_BASE_URL . 'assets/');";
        file_put_contents($package . '/_build/build.config.php', $buildConfigContent);

        $this->modx->log(modX::LOG_LEVEL_INFO, '_build/build.config.php file created.');

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
        $this->createElements();
    }

    /**
     * Add extension package if extension package block is in config.json
     */
    private function addExtensionPackage(){
        $extPackage = $this->config->getExtensionPackage();
        if($extPackage !== false){
            $modelPath = $this->packageCorePath . 'model/';
            $modelPath = str_replace('\\', '/', $modelPath);
            if($extPackage === true){
                $this->modx->addExtensionPackage($this->config->getLowCaseName(),$modelPath);
            }else{
                $this->modx->addExtensionPackage($this->config->getLowCaseName(),$modelPath, array(
                      'serviceName' => $extPackage['serviceName'],
                      'serviceClass' => $extPackage['serviceClass']
                 ));
            }

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
            /** @var $act GitPackageConfigAction */
            foreach($this->config->getActions() as $act){
                $actions[$act->getId()] = $this->modx->newObject('modAction');
                $actions[$act->getId()]->fromArray(array(
                        'namespace' => $this->config->getLowCaseName(),
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
            /** @var $men GitPackageConfigMenu */
            foreach($this->config->getMenus() as $i => $men){
                $menus[$i] = $this->modx->newObject('modMenu');
                $menus[$i]->fromArray(array(
                          'text' => $men->getText(),
                          'parent' => $men->getParent(),
                          'description' => $men->getDescription(),
                          'icon' => $men->getIcon(),
                          'menuindex' => $men->getMenuIndex(),
                          'params' => $men->getParams(),
                          'handler' => $men->getHandler(),
                     ),'',true,true);
                $menus[$i]->addOne($actions[$men->getAction()]);
                $menus[$i]->save();
            }

            $this->modx->log(modX::LOG_LEVEL_INFO, 'Menus created.');
        }
    }

    /**
     * Create system settings, core_path and assets_url are created automatically
     */
    private function createSystemSettings() {
        $this->createSystemSetting('core_path', $this->packageCorePath);
        $this->createSystemSetting('assets_url', $this->packageAssetsUrl);

        /** @var $setting GitPackageConfigSetting */
        foreach($this->config->getSettings() as $setting){
            $this->createSystemSetting($setting->getKey(), $setting->getValue(), $setting->getType(), $setting->getArea());
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
        $ct = $this->modx->getObject('modSystemSetting',array('key' => $this->config->getLowCaseName().".".$key));
        if (!$ct){
            /** @var modSystemSetting $setting */
            $setting = $this->modx->newObject('modSystemSetting');
            $setting->set('key', $this->config->getLowCaseName().".".$key);
            $setting->set('value',$value);
            $setting->set('namespace', $this->config->getLowCaseName());
            $setting->set('area',$area);
            $setting->set('xtype', $xtype);
            $setting->save();
        }else{
            $ct->set('value',$value);
            $ct->set('namespace', $this->config->getLowCaseName());
            $ct->set('area',$area);
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
        $this->createCategory();
        $this->createPlugins();
        $this->createChunks();
        $this->createSnippets();
        $this->createTemplates();
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating elements finished');
    }

    /**
     * Create category for elements
     */
    private function createCategory() {
        $category = $this->modx->getObject('modCategory', array('category' => $this->config->getName()));
        if(!$category){
            $category = $this->modx->newObject('modCategory');
            $category->set('category', $this->config->getName());
            $category->save();
        }

        $this->category = $category;
    }

    /**
     * Create plugins if any
     */
    private function createPlugins(){
        $plugins = $this->config->getElements('plugins');
        if(count($plugins) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating plugins:');
            /** @var GitPackageConfigElementPlugin $plugin */
            foreach($plugins as $plugin){
                $pluginObject = $this->modx->newObject('modPlugin');
                $pluginObject->set('name', $plugin->getName());
                $pluginObject->set('static', 1);
                $pluginObject->set('static_file', $this->packageCorePath.'elements/plugins/' . $plugin->getFile());
                $pluginObject->set('category', $this->category->id);
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
        $snippets = $this->config->getElements('snippets');
        if(count($snippets) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating snippets:');
            /** @var GitPackageConfigElementSnippet $snippet */
            foreach($snippets as $snippet){
                $snippetObject = $this->modx->newObject('modSnippet');
                $snippetObject->set('name', $snippet->getName());
                $snippetObject->set('static', 1);
                $snippetObject->set('static_file', $this->packageCorePath.'elements/snippets/' . $snippet->getFile());
                $snippetObject->set('category', $this->category->id);
                $snippetObject->save();

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Snippet ' . $snippet->getName() . ' created.');
            }

        }
    }

    /**
     * Create chunks if any
     */
    private function createChunks(){
        $chunks = $this->config->getElements('chunks');
        if(count($chunks) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating chunks:');
            /** @var GitPackageConfigElementChunk $chunk */
            foreach($chunks as $chunk){
                $chunkObject = $this->modx->newObject('modChunk');
                $chunkObject->set('name', $chunk->getName());
                $chunkObject->set('static', 1);
                $chunkObject->set('static_file', $this->packageCorePath.'elements/chunks/' . $chunk->getFile());
                $chunkObject->set('category', $this->category->id);
                $chunkObject->save();

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Chunk ' . $chunk->getName() . ' created.');
            }

        }
    }

    /**
     * Create templates if any
     */
    private function createTemplates(){
        $templates = $this->config->getElements('templates');
        if(count($templates) > 0){
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Creating templates:');
            /** @var GitPackageConfigElementTemplate $template */
            foreach($templates as $template){
                $templatesObject = $this->modx->newObject('modTemplate');
                $templatesObject->set('templatename', $template->getName());
                $templatesObject->set('static', 1);
                $templatesObject->set('static_file', $this->packageCorePath.'elements/templates/' . $template->getFile());
                $templatesObject->set('category', $this->category->id);
                $templatesObject->save();

                $this->modx->log(modX::LOG_LEVEL_INFO, 'Template ' . $template->getName() . ' created.');
            }

        }
    }
}
return 'GitPackageManagementCreateProcessor';
