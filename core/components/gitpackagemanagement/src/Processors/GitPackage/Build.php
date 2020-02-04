<?php
namespace GitPackageManagement\Processors\GitPackage;

use GitPackageManagement\Builder\Builder;
use GitPackageManagement\Builder\Vehicle;
use GitPackageManagement\Config\Category;
use GitPackageManagement\Config\Config;
use GitPackageManagement\Config\ElementChunk;
use GitPackageManagement\Config\ElementPlugin;
use GitPackageManagement\Config\ElementSnippet;
use GitPackageManagement\Config\ElementTemplate;
use GitPackageManagement\Config\ElementTV;
use GitPackageManagement\Config\ElementWidget;
use GitPackageManagement\Config\Menu;
use GitPackageManagement\Config\Setting;
use GitPackageManagement\GitPackageManagement;
use GitPackageManagement\Model\GitPackage;
use MODX\Revolution\modCategory;
use MODX\Revolution\modChunk;
use MODX\Revolution\modDashboardWidget;
use MODX\Revolution\modMenu;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\Processors\ModelProcessor;
use MODX\Revolution\Smarty\modSmarty;
use xPDO\Transport\xPDOTransport;

class Build extends ModelProcessor {
    /** @var GitPackage $object */
    public $object;
    /** @var Config $config */
    public $config;
    public $packagePath = null;
    /** @var Builder $builder */
    public $builder;
    private $corePath;
    private $assetsPath;
    /** @var modSmarty $smarty */
    private $smarty;
    private $tvMap = array();

    public function prepare(){
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        $this->object = $this->modx->getObject(GitPackage::class, array('id' => $id));
        if (!$this->object) return $this->failure();

        $this->packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';
        if ($this->packagePath == null) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }

        $packagePath = $this->packagePath . $this->object->dir_name;

        $configFile = $packagePath . GitPackageManagement::$configPath;
        if (!file_exists($configFile)) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $config = file_get_contents($configFile);

        $config = $this->modx->fromJSON($config);

        $this->config = new Config($this->modx, $packagePath);
        if ($this->config->parseConfig($config) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $this->loadSmarty();

        $buildOptions = $this->config->getBuild()->getBuildOptions();
        $emptyFolders = $this->modx->getOption('empty_folders', $buildOptions, array());
        if (!empty($emptyFolders)) {
            foreach ($emptyFolders as $emptyFolder => $emptyFiles) {
                $emptyFolder = str_replace('{package_path}', $this->config->getPackagePath() . '/', $emptyFolder);
                $this->emptyFolder($emptyFolder, $emptyFiles);
            }
        }

        return true;
    }

    public function process() {
        $prepare = $this->prepare();
        if ($prepare !== true) {
            return $prepare;
        }

        $this->setPaths();

        $this->builder = new Builder($this->modx, $this->smarty, $this->packagePath);
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

        $this->builder->registerNamespace($this->config->getLowCaseName(), false, true, '{core_path}components/' . $this->config->getLowCaseName() . '/', '{assets_path}components/' . $this->config->getLowCaseName() . '/');

        $this->prependVehicles();

        /** @var Vehicle $vehicle */
        $vehicle = $this->addCategory();

        $validator = $buildOptions->getValidator();

        $validatorsDir = $validator->getValidatorsDir();
        $validatorsDir = trim($validatorsDir, '/');
        $validatorsDir = $this->packagePath . '_build/' . $validatorsDir . '/';

        $validators = $validator->getValidators();
        foreach ($validators as $script) {
            $vehicle->addPhpValidator($validatorsDir . ltrim($script, '/'));
        }

        $resolver = $buildOptions->getResolver();

        $resolversDir = $resolver->getResolversDir();
        $resolversDir = trim($resolversDir, '/');
        $resolversDir = $this->packagePath . '_build/' . $resolversDir . '/';

        $before = $resolver->getBefore();
        foreach ($before as $script) {
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
        foreach ($after as $script) {
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
        $category = $this->modx->newObject(modCategory::class);
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

        $category = $this->builder->createVehicle($category, 'category');

        $buildOptions = $this->config->getBuild()->getBuildOptions();

        if ($this->modx->getOption('abort_install_on_vehicle_fail', $buildOptions, false)) {
            $categoryVehicle = $category->getVehicle();
            $categoryVehicle->attributes[xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL] = true;
        }

        return $category;
    }

    private function getCategories($parent = null) {
        $cats = $this->getCategoriesForParent($parent);
        $retCategories = array();

        foreach ($cats as $cat) {
            /** @var modCategory $category */
            $category = $this->modx->newObject(modCategory::class);
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

        /** @var ElementSnippet[] $configSnippets */
        $configSnippets = $this->config->getElements('snippets');
        if (count($configSnippets) > 0) {
            foreach ($configSnippets as $configSnippet) {
                if ($configSnippet->getCategory() != $category) continue;

                $snippetObject = $this->modx->newObject(modSnippet::class);
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

        /** @var ElementChunk[] $configChunks */
        $configChunks = $this->config->getElements('chunks');
        if (count($configChunks) > 0) {
            foreach ($configChunks as $configChunk) {
                if ($configChunk->getCategory() != $category) continue;

                $chunkObject = $this->modx->newObject(modChunk::class);
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

        /** @var ElementTemplate[] $configTemplates */
        $configTemplates = $this->config->getElements('templates');
        if (count($configTemplates) > 0) {
            foreach ($configTemplates as $configTemplate) {
                if ($configTemplate->getCategory() != $category) continue;

                $templateObject = $this->modx->newObject(modTemplate::class);
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

        /** @var ElementTV[] $configTVs */
        $configTVs = $this->config->getElements('tvs');
        if (count($configTVs) > 0) {
            foreach ($configTVs as $configTV) {
                if ($configTV->getCategory() != $category) continue;

                $tvObject = $this->modx->newObject(modTemplateVar::class);
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
                    $tvObject->set('input_properties', $inputProperties);
                }

                $outputProperties = $configTV->getOutputProperties();
                if (!empty($outputProperties)) {
                    $tvObject->set('output_properties', $outputProperties);
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

        /** @var ElementPlugin[] $configPlugins */
        $configPlugins = $this->config->getElements('plugins');
        if (count($configPlugins) > 0) {

            foreach ($configPlugins as $configPlugin) {
                if ($configPlugin->getCategory() != $category) continue;

                $pluginObject = $this->modx->newObject(modPlugin::class);
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

    protected function emptyFolder($path, $filemask = '*')
    {
        $inverse = false;
        if (strpos($filemask, '!') === 0) {
            $filemask = substr($filemask, 1);
            $inverse = true;
        }
        $files = glob($path . '/' . $filemask, GLOB_BRACE);
        if ($inverse) {
            $allFiles = glob($path . '/*');
            $files = array_diff($allFiles, $files);
        }
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->emptyFolder($file, '*');
                rmdir($file);
            } else {
                unlink($file);
            }
        }
        return;
    }

    protected function prependVehicles() {}

    protected function appendVehicles() {}

    private function addMenus() {
        /** @var Menu[] $menus */
        $menus = $this->config->getMenus();

        foreach ($menus as $menu) {
            $menuObject = $this->modx->newObject(modMenu::class);
            $menuObject->fromArray(array(
                'text' => $menu->getText(),
                'parent' => $menu->getParent(),
                'description' => $menu->getDescription(),
                'icon' => $menu->getIcon(),
                'menuindex' => $menu->getMenuIndex(),
                'params' => $menu->getParams(),
                'handler' => $menu->getHandler(),
                'permissions' => $menu->getPermissions(),
                'action' => $menu->getAction(),
                'namespace' => $menu->getNamespace(),
            ), '', true, true);

            $vehicle = $this->builder->createVehicle($menuObject, 'menu');
            $this->builder->putVehicle($vehicle);
        }
    }

    private function addSystemSettings() {
        /** @var Setting[] $settings */
        $settings = $this->config->getSettings();

        foreach ($settings as $setting) {
            /** @var modSystemSetting $settingObject */
            $settingObject = $this->modx->newObject(modSystemSetting::class);
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
        /** @var ElementWidget[] $widgets */
        $widgets = $this->config->getElements('widgets');

        foreach ($widgets as $widget) {
            /** @var modDashboardWidget $widgetObject */
            $widgetObject = $this->modx->newObject(modDashboardWidget::class);
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
        /** @var GitPackageManagement $gpm */
        $gpm = $this->modx->services->get('gitpackagemanagement');
        $this->smarty = $this->modx->services->get('smarty');
        $this->smarty->setTemplatePath($gpm->getOption('templatesPath') . '/gitpackagebuild/');

        $this->smarty->assign('lowercasename', $this->config->getLowCaseName());
    }

    /**
     * @param $parent
     * @return Category[]
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
