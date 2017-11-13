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
    private $tvMap = array();

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

        $this->builder = new GitPackageBuilder($this->modx, $this->smarty, $this->packagePath);
        $buildOptions = $this->config->getBuild();

        $objectAttributes = $buildOptions->getAttributes();
        foreach ($objectAttributes as $element => $attributes) {
            $this->builder->updateCategoryAttribute($element, $attributes);
        }

        $version = explode('-', $this->config->getVersion());
        if (count($version) == 1) {
            $version[1] = 'pl';
        }

        $this->builder->getTPBuilder()->directory = $this->config->getPackagePath() . $this->modx->getOption('gitpackagemanagement.build_path', null, '/_packages/');
        if (!is_dir($this->builder->getTPBuilder()->directory)) {
            mkdir($this->builder->getTPBuilder()->directory);
        }
        $this->builder->getTPBuilder()->createPackage($this->config->getLowCaseName(), $version[0], $version[1]);

        $this->builder->registerNamespace($this->config->getLowCaseName(), false, true, '{core_path}components/' . $this->config->getLowCaseName() . '/','{assets_path}components/' . $this->config->getLowCaseName() . '/');

        $this->prependVehicles();

        $vehicle = $this->addCategory();

        $resolver = $buildOptions->getResolver();

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

        $fileResolvers = $resolver->getFileResolvers();
        foreach ($fileResolvers as $fileResolver) {
            $source = $fileResolver['source'];

            $source = str_replace('[[+corePath]]', $this->corePath, $source);
            $source = str_replace('[[+assetsPath]]', $this->assetsPath, $source);
            $source = str_replace('[[+packagePath]]', $this->packagePath, $source);

            $vehicle->addFileResolver($source, $fileResolver['target']);
        }

        $db = $this->config->getDatabase();
        if ($db != null) {
            $tables = $db->getTables();
            if (!empty($tables)) {
                $vehicle->addTableResolver($this->packagePath . '_build/gpm_resolvers', $db);
            }
        }

        $extensionPackage = $this->config->getExtensionPackage();
        if ($extensionPackage !== false) {
            $prefix = $db->getPrefix();

            if (!is_array($extensionPackage)) $extensionPackage = array();
            
            if (isset($prefix)) {
                $extensionPackage['tablePrefix'] = $prefix;
            }

            $vehicle->addExtensionPackageResolver($this->packagePath . '_build/gpm_resolvers', $extensionPackage);
        }

        if (!empty($this->tvMap)) {
            $vehicle->addTVResolver($this->packagePath . '_build/gpm_resolvers', $this->tvMap);
        }

        $resources = $this->config->getResources();
        $resourcesArray = array();

        foreach ($resources as $resource) {
            $resourcesArray[] = $resource->toRawArray();
        }

        if (!empty($resourcesArray)) {
            $vehicle->addResourceResolver($this->packagePath . '_build/gpm_resolvers', $resourcesArray);
        }

        $this->addWidgets();
        $this->addSystemSettings();

        $after = $resolver->getAfter();
        foreach($after as $script) {
            $vehicle->addPHPResolver($resolversDir . ltrim($script, '/'));
        }

        $this->builder->putVehicle($vehicle);
        $this->addMenus();

        $this->appendVehicles();

        $packageAttributes = array();

        $license = ltrim($buildOptions->getLicense(), '/');
        if (!empty($license) && file_exists($this->corePath . $license)) {
            $packageAttributes['license'] = file_get_contents($this->corePath . $license);
        }

        $readme = ltrim($buildOptions->getReadme(), '/');
        if (!empty($readme) && file_exists($this->corePath . $readme)) {
            $packageAttributes['readme'] = file_get_contents($this->corePath . $readme);
        }

        $changeLog = ltrim($buildOptions->getChangeLog(), '/');
        if (!empty($changeLog) && file_exists($this->corePath . $changeLog)) {
            $packageAttributes['changelog'] = file_get_contents($this->corePath . $changeLog);
        }

        $setupOptions = $buildOptions->getSetupOptions();
        if (!empty($setupOptions) && isset($setupOptions['source']) && !empty($setupOptions['source'])) {
            $file = $this->packagePath . '_build/' . $setupOptions['source'];
            if (file_exists($file)) {
                $setupOptions['source'] = $file;
                $packageAttributes['setup-options'] = $setupOptions;
            }
        }

        $dependencies = $this->config->getDependencies();
        if (!empty($dependencies)) {
            $packageAttributes['requires'] = array();
            foreach ($dependencies as $dependency) {
                $packageAttributes['requires'][$dependency['name']] = $dependency['version'];
            }
        }

        $this->builder->setPackageAttributes($packageAttributes);

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

    protected function addCategory() {
        /** @var modCategory $category */
        $category = $this->modx->newObject('modCategory');
        $category->set('category', $this->config->getName());

        $snippets = $this->getSnippets();
        if (!empty($snippets)) {
            $category->addMany($snippets, 'Snippets');
        }

        $chunks = $this->getChunks();
        if (!empty($chunks)) {
            $category->addMany($chunks, 'Chunks');
        }

        $plugins = $this->getPlugins();
        if (!empty($plugins)) {
            $category->addMany($plugins, 'Plugins');
        }

        $templates = $this->getTemplates();
        if (!empty($templates)) {
            $category->addMany($templates, 'Templates');
        }

        $templateVariables = $this->getTemplateVariables();
        if (!empty($templateVariables)) {
            $category->addMany($templateVariables, 'TemplateVars');
        }

        $categories = $this->getCategories();
        if (!empty($categories)) {
            $category->addMany($categories, 'Children');
        }

        return $this->builder->createVehicle($category, 'category');
    }

    private function getCategories($parent = null) {
        $cats = $this->getCategoriesForParent($parent);
        $retCategories = array();

        foreach ($cats as $cat) {
            /** @var modCategory $category */
            $category = $this->modx->newObject('modCategory');
            $category->set('category', $cat->getName());

            $snippets = $this->getSnippets($cat->getName());
            if (!empty($snippets)) {
                $category->addMany($snippets, 'Snippets');
            }

            $chunks = $this->getChunks($cat->getName());
            if (!empty($chunks)) {
                $category->addMany($chunks, 'Chunks');
            }

            $plugins = $this->getPlugins($cat->getName());
            if (!empty($plugins)) {
                $category->addMany($plugins, 'Plugins');
            }

            $templates = $this->getTemplates($cat->getName());
            if (!empty($templates)) {
                $category->addMany($templates, 'Templates');
            }

            $templateVariables = $this->getTemplateVariables($cat->getName());
            if (!empty($templateVariables)) {
                $category->addMany($templateVariables, 'TemplateVars');
            }

            $categories = $this->getCategories($cat->getName());
            if (!empty($categories)) {
                $category->addMany($categories, 'Children');
            }

            $retCategories[] = $category;
        }

        return $retCategories;
    }

    private function getSnippets($category = null) {
        $snippets = array();

        /** @var GitPackageConfigElementSnippet[] $configSnippets */
        $configSnippets = $this->config->getElements('snippets');
        if(count($configSnippets) > 0){
            foreach($configSnippets as $configSnippet){
                if ($configSnippet->getCategory() != $category) continue;

                $snippetObject = $this->modx->newObject('modSnippet');
                $snippetObject->set('name', $configSnippet->getName());
                $snippetObject->set('description', $configSnippet->getDescription());
                $snippetObject->set('property_preprocess', $configSnippet->getPropertyPreProcess());
                $snippetObject->set('content', $this->builder->getFileContent($this->corePath . $configSnippet->getFilePath()));

                $snippetObject->setProperties($configSnippet->getProperties());
                $snippets[] = $snippetObject;
            }
        }

        return $snippets;
    }

    private function getChunks($category = null) {
        $chunks = array();

        /** @var GitPackageConfigElementChunk[] $configChunks */
        $configChunks = $this->config->getElements('chunks');
        if(count($configChunks) > 0){
            foreach($configChunks as $configChunk){
                if ($configChunk->getCategory() != $category) continue;

                $chunkObject = $this->modx->newObject('modChunk');
                $chunkObject->set('name', $configChunk->getName());
                $chunkObject->set('description', $configChunk->getDescription());
                $chunkObject->set('property_preprocess', $configChunk->getPropertyPreProcess());
                $chunkObject->set('content', $this->builder->getFileContent($this->corePath . $configChunk->getFilePath()));

                $chunkObject->setProperties($configChunk->getProperties());
                $chunks[] = $chunkObject;
            }
        }

        return $chunks;
    }

    private function getTemplates($category = null) {
        $templates = array();

        /** @var GitPackageConfigElementTemplate[] $configTemplates */
        $configTemplates = $this->config->getElements('templates');
        if(count($configTemplates) > 0){
            foreach($configTemplates as $configTemplate){
                if ($configTemplate->getCategory() != $category) continue;

                $templateObject = $this->modx->newObject('modTemplate');
                $templateObject->set('templatename', $configTemplate->getName());
                $templateObject->set('description', $configTemplate->getDescription());
                $templateObject->set('property_preprocess', $configTemplate->getPropertyPreProcess());
                $templateObject->set('icon', $configTemplate->getIcon());
                $templateObject->set('content', $this->builder->getFileContent($this->corePath . $configTemplate->getFilePath()));

                $templateObject->setProperties($configTemplate->getProperties());
                $templates[] = $templateObject;
            }
        }

        return $templates;
    }

    private function getTemplateVariables($category = null) {
        $templateVariables = array();

        /** @var GitPackageConfigElementTV[] $configTVs */
        $configTVs = $this->config->getElements('tvs');
        if(count($configTVs) > 0){
            foreach($configTVs as $configTV){
                if ($configTV->getCategory() != $category) continue;

                $tvObject = $this->modx->newObject('modTemplateVar');
                $tvObject->set('name', $configTV->getName());
                $tvObject->set('caption', $configTV->getCaption());
                $tvObject->set('description', $configTV->getDescription());
                $tvObject->set('property_preprocess', $configTV->getPropertyPreProcess());
                $tvObject->set('type', $configTV->getInputType());
                $tvObject->set('elements', $configTV->getInputOptionValues());
                $tvObject->set('rank', $configTV->getSortOrder());
                $tvObject->set('default_text', $configTV->getDefaultValue());
                $tvObject->set('display', $configTV->getDisplay());

                $inputProperties = $configTV->getInputProperties();
                if (!empty($inputProperties)) {
                    $tvObject->set('input_properties',$inputProperties);
                }

                $outputProperties = $configTV->getOutputProperties();
                if (!empty($outputProperties)) {
                    $tvObject->set('output_properties',$outputProperties);
                }

                $tvObject->setProperties($configTV->getProperties());
                $this->tvMap[$configTV->getName()] = $configTV->getTemplates();
                $templateVariables[] = $tvObject;
            }
        }

        return $templateVariables;
    }

    private function getPlugins($category = null) {
        $plugins = array();

        /** @var GitPackageConfigElementPlugin[] $configPlugins */
        $configPlugins = $this->config->getElements('plugins');
        if(count($configPlugins) > 0){

            foreach($configPlugins as $configPlugin){
                if ($configPlugin->getCategory() != $category) continue;

                $pluginObject = $this->modx->newObject('modPlugin');
                $pluginObject->set('name', $configPlugin->getName());
                $pluginObject->set('description', $configPlugin->getDescription());
                $pluginObject->set('disabled', $configPlugin->getDisabled());
                $pluginObject->set('property_preprocess', $configPlugin->getPropertyPreProcess());
                $pluginObject->set('content', $this->builder->getFileContent($this->corePath . $configPlugin->getFilePath()));

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

                $pluginObject->setProperties($configPlugin->getProperties());
                $plugins[] = $pluginObject;
            }
        }

        return $plugins;
    }

    protected function prependVehicles() {}

    protected function appendVehicles() {}

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
                'permissions' => $menu->getPermissions(),
            ),'',true,true);

            $configAction = $menu->getActionObject();
            if ($configAction !== null) {
                $actionObject = $this->modx->newObject('modAction');
                $actionObject->fromArray(array(
                    'id' => 1,
                    'namespace' => $configAction->getNamespace(),
                    'parent' => 0,
                    'controller' => $configAction->getController(),
                    'haslayout' => $configAction->getHasLayout(),
                    'lang_topics' => $configAction->getLangTopics(),
                    'assets' => $configAction->getAssets(),
                ),'',true,true);

                $menuObject->addOne($actionObject);
            } else {
                $menuObject->set('action', $menu->getAction());
                $menuObject->set('namespace', $menu->getNamespace());
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
                'namespace' => $this->config->getLowCaseName(),
                'area' => $setting->getArea(),
            ), '', true, true);

            $vehicle = $this->builder->createVehicle($settingObject, 'setting');
            $this->builder->putVehicle($vehicle);
        }
    }

    private function addWidgets() {
        /** @var GitPackageConfigElementWidget[] $widgets */
        $widgets = $this->config->getElements('widgets');

        foreach ($widgets as $widget) {
            /** @var modSystemSetting $widgetObject */
            $widgetObject = $this->modx->newObject('modDashboardWidget');
            $widgetObject->fromArray(array(
                'name' => $widget->getName(),
                'description' => $widget->getDescription(),
                'type' => $widget->getWidgetType(),
                'content' => ($widget->getWidgetType() == 'file') ?
                    '[[++core_path]]' . 'components/' . $this->config->getLowCaseName() . '/' . $widget->getFilePath() :
                    $widget->getFile(),
                'namespace' => $this->config->getLowCaseName(),
                'lexicon' => $widget->getLexicon(),
                'size' => $widget->getSize(),
            ), '', true, true);

            $vehicle = $this->builder->createVehicle($widgetObject, 'widget');
            $this->builder->putVehicle($vehicle);
        }
    }

    private function loadSmarty() {
        $this->smarty = $this->modx->getService('smarty','smarty.modSmarty');
        $this->smarty->setTemplatePath($this->modx->gitpackagemanagement->getOption('templatesPath') . '/gitpackagebuild/');

        $this->smarty->assign('lowercasename', $this->config->getLowCaseName());
    }

    /**
     * @param $parent
     * @return GitPackageConfigCategory[]
     */
    private function getCategoriesForParent($parent)
    {
        $categories = array();
        $allCategories = $this->config->getCategories();
        foreach ($allCategories as $category) {
            if ($category->getParent() == $parent) {
                $categories[] = $category;
            }
        }

        return $categories;
    }
}
return 'GitPackageManagementBuildPackageProcessor';
