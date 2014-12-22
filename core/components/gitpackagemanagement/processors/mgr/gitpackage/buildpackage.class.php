<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gpc/gitpackageconfig.class.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/builder/gitpackagebuilder.class.php';
/**
 * Clone git repository and install it
 * 
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementBuildPackageProcessor extends modObjectProcessor {
    /** @var GitPackage $object */
    public $object;
    /** @var GitPackageConfig $config */
    public $config;
    public $packagePath = null;
    /** @var GitPackageBuilder $builder */
    public $builder;
    private $corePath;
    private $assetsPath;
    /** @var modSmarty $smarty */
    private $smarty;

    public function prepare(){
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        $this->object = $this->modx->getObject('GitPackage', array('id' => $id));
        if (!$this->object) return $this->failure();

        $this->packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';
        if($this->packagePath == null){
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }

        $packagePath = $this->packagePath . $this->object->dir_name;

        $configFile = $packagePath . $this->modx->gitpackagemanagement->configPath;
        if(!file_exists($configFile)){
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $config = file_get_contents($configFile);

        $config = $this->modx->fromJSON($config);

        $this->config = new GitPackageConfig($this->modx, $packagePath);
        if($this->config->parseConfig($config) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $this->loadSmarty();

        return true;
    }

    public function process() {
        $prepare = $this->prepare();
        if ($prepare !== true) {
            return $prepare;
        }

        $this->setPaths();

        $this->builder = new GitPackageBuilder($this->modx, $this->smarty);

        $version = explode('-', $this->config->getVersion());
        if (count($version) == 1) {
            $version[1] = 'pl';
        }

        $this->builder->getTPBuilder()->directory = $this->config->getPackagePath() . '/_packages/';
        $this->builder->getTPBuilder()->createPackage($this->config->getLowCaseName(), $version[0], $version[1]);

        $this->builder->registerNamespace($this->config->getLowCaseName(), false, true, '{core_path}components/' . $this->config->getLowCaseName() . '/','{assets_path}components/' . $this->config->getLowCaseName() . '/');

        $vehicle = $this->addCategory();

        $resolver = $this->config->getBuild()->getResolver();

        $resolversDir = $resolver->getResolversDir();
        $resolversDir = trim($resolversDir, '/');
        $resolversDir = $this->packagePath . '_build/' . $resolversDir . '/';

        $before = $resolver->getBefore();
        foreach($before as $script) {
            $vehicle->addPHPResolver($resolversDir . ltrim($script, '/'));
        }

        if (is_dir($this->assetsPath)) {
            $vehicle->addAssetsResolver($this->assetsPath);
        }

        if (is_dir($this->corePath)) {
            $vehicle->addCoreResolver($this->corePath);
        }

        $db = $this->config->getDatabase();
        if ($db != null) {
            $tables = $db->getTables();
            if (!empty($tables)) {
                $vehicle->addTableResolver($this->packagePath . '_build/gpm_resolvers', $tables);
            }
        }

        $extensionPackage = $this->config->getExtensionPackage();
        if ($extensionPackage !== false) {
            if ($extensionPackage === true) {
                $vehicle->addExtensionPackageResolver($this->packagePath . '_build/gpm_resolvers');
            } else {
                $vehicle->addExtensionPackageResolver($this->packagePath . '_build/gpm_resolvers', $extensionPackage['serviceName'], $extensionPackage['serviceClass']);
            }
        }

        $after = $resolver->getAfter();
        foreach($after as $script) {
            $vehicle->addPHPResolver($resolversDir . ltrim($script, '/'));
        }

        $this->builder->putVehicle($vehicle);
        $this->addMenus();
        $this->addSystemSettings();

        $this->builder->setPackageAttributes(array(
            'license' => file_get_contents($this->corePath . 'docs/license.txt'),
            'readme' => file_get_contents($this->corePath . 'docs/readme.txt'),
            'changelog' => file_get_contents($this->corePath . 'docs/changelog.txt'),
//            'setup-options' => array(
//                'source' => $sources['build'].'setup.options.php',
//            ),
        ));

        $this->builder->pack();

        return $this->success();
    }

    private function setPaths() {
        $packagesPath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';

        $this->corePath = $packagesPath . $this->object->dir_name . "/core/components/" . $this->config->getLowCaseName() . "/";
        $this->corePath = str_replace('\\', '/', $this->corePath);

        $this->assetsPath = $packagesPath . $this->object->dir_name . "/assets/components/" . $this->config->getLowCaseName() . "/";
        $this->assetsPath = str_replace('\\', '/', $this->assetsPath);

        $this->packagePath = $packagesPath . $this->object->dir_name . "/";
        $this->packagePath = str_replace('\\', '/', $this->packagePath);
    }

    private function addCategory() {
        /** @var modCategory $category */
        $category = $this->modx->newObject('modCategory');
        $category->set('category', $this->config->getName());

        return $this->builder->addCategory($category, $this->getSnippets(), $this->getChunks(), $this->getPlugins());
    }

    private function getSnippets() {
        $snippets = array();

        /** @var GitPackageConfigElementSnippet[] $configSnippets */
        $configSnippets = $this->config->getElements('snippets');
        if(count($configSnippets) > 0){
            $path = $this->corePath . 'elements/snippets/';

            foreach($configSnippets as $configSnippet){
                $snippetObject = $this->modx->newObject('modSnippet');
                $snippetObject->set('name', $configSnippet->getName());
                $snippetObject->set('snippet', $this->builder->getFileContent($path . $configSnippet->getFile()));

                $snippets[] = $snippetObject;
            }
        }

        return $snippets;
    }

    private function getChunks() {
        $chunks = array();

        /** @var GitPackageConfigElementChunk[] $configChunks */
        $configChunks = $this->config->getElements('chunks');
        if(count($configChunks) > 0){
            $path = $this->corePath . 'elements/chunks/';

            foreach($configChunks as $configChunk){
                $chunkObject = $this->modx->newObject('modSnippet');
                $chunkObject->set('name', $configChunk->getName());
                $chunkObject->set('snippet', $this->builder->getFileContent($path . $configChunk->getFile()));

                $chunks[] = $chunkObject;
            }
        }

        return $chunks;
    }

    private function getPlugins() {
        $plugins = array();

        /** @var GitPackageConfigElementPlugin[] $configPlugins */
        $configPlugins = $this->config->getElements('plugins');
        if(count($configPlugins) > 0){
            $path = $this->corePath . 'elements/plugins/';

            foreach($configPlugins as $configPlugin){
                $pluginObject = $this->modx->newObject('modPlugin');
                $pluginObject->set('name', $configPlugin->getName());
                $pluginObject->set('plugincode', $this->builder->getFileContent($path . $configPlugin->getFile()));

                $events = $configPlugin->getEvents();
                if (count($events) > 0) {
                    $eventObjects = array();
                    foreach ($events as $event) {
                        $eventObjects[$event] = $this->modx->newObject('modPluginEvent');
                        $eventObjects[$event]->fromArray(array(
                            'event' => $event,
                            'priority' => 0,
                            'propertyset' => 0
                        ), '', true, true);
                    }

                    $pluginObject->addMany($eventObjects);
                }

                $plugins[] = $pluginObject;
            }
        }

        return $plugins;
    }

    private function addMenus() {
        /** @var GitPackageConfigMenu[] $menus */
        $menus = $this->config->getMenus();

        foreach ($menus as $menu) {
            $menuObject = $this->modx->newObject('modMenu');
            $menuObject->fromArray(array(
                'text' => $menu->getText(),
                'parent' => $menu->getParent(),
                'description' => $menu->getDescription(),
                'icon' => $menu->getIcon(),
                'menuindex' => $menu->getMenuIndex(),
                'params' => $menu->getParams(),
                'handler' => $menu->getHandler(),
            ),'',true,true);

            $configAction = $menu->getActionObject();
            if ($configAction !== null) {
                $actionObject = $this->modx->newObject('modAction');
                $actionObject->fromArray(array(
                    'id' => 1,
                    'namespace' => $this->config->getLowCaseName(),
                    'parent' => 0,
                    'controller' => $configAction->getController(),
                    'haslayout' => $configAction->getHasLayout(),
                    'lang_topics' => $configAction->getLangTopics(),
                    'assets' => $configAction->getAssets(),
                ),'',true,true);

                $menuObject->addOne($actionObject);
            }

            $vehicle = $this->builder->createVehicle($menuObject, 'menu');
            $this->builder->putVehicle($vehicle);
        }
    }

    private function addSystemSettings() {
        /** @var GitPackageConfigSetting[] $settings */
        $settings = $this->config->getSettings();

        foreach ($settings as $setting) {
            /** @var modSystemSetting $settingObject */
            $settingObject = $this->modx->newObject('modSystemSetting');
            $settingObject->fromArray(array(
                'key' => $setting->getNamespacedKey(),
                'value' => $setting->getValue(),
                'xtype' => $setting->getType(),
                'namespace' => $setting->getNamespace(),
                'area' => $setting->getArea(),
            ), '', true, true);

            $vehicle = $this->builder->createVehicle($settingObject, 'setting');
            $this->builder->putVehicle($vehicle);
        }
    }

    private function loadSmarty() {
        $this->smarty = $this->modx->getService('smarty','smarty.modSmarty');
        $this->smarty->setTemplatePath($this->modx->gitpackagemanagement->getOption('templatesPath') . '/gitpackagebuild/');

        $this->smarty->assign('lowercasename', $this->config->getLowCaseName());
    }
}
return 'GitPackageManagementBuildPackageProcessor';
