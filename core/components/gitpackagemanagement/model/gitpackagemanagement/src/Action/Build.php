<?php
namespace GPM\Action;

use GPM\Builder\Builder;
use GPM\Config\Config;
use GPM\Config\Object\Action;
use Psr\Log\LoggerInterface;

final class Build extends \GPM\Action\Action
{
    /** @var Builder */
    protected $builder;
    
    /** @var array */
    protected $tvMap = [];
    
    /** @var \modSmarty */
    protected $smarty;
    
    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);
        
        $this->loadSmarty();
    }

    public function build()
    {
        $this->checkDependencies();
        
        $this->builder = new Builder($this->modx, $this->smarty, $this->config->packagePath);
        $buildOptions = $this->config->build;

        $objectAttributes = $buildOptions->attributes;
        foreach ($objectAttributes as $element => $attributes) {
            $this->builder->updateCategoryAttribute($element, $attributes);
        }

        $version = explode('-', $this->config->general->version);
        if (count($version) == 1) {
            $version[1] = 'pl';
        }

        $this->builder->getTPBuilder()->directory = $this->config->packagePath . '/_packages/';
        $this->builder->getTPBuilder()->createPackage($this->config->general->lowCaseName, $version[0], $version[1]);

        $this->builder->registerNamespace($this->config->general->lowCaseName, false, true, '{core_path}components/' . $this->config->general->lowCaseName . '/', '{assets_path}components/' . $this->config->general->lowCaseName . '/');

        $vehicle = $this->addCategory();

        $resolver = $buildOptions->resolver;

        $resolversDir = $resolver->resolversDir;
        $resolversDir = trim($resolversDir, '/');
        $resolversDir = $this->config->packagePath . '/_build/' . $resolversDir . '/';

        $before = $resolver->before;
        foreach ($before as $script) {
            $vehicle->addPHPResolver($resolversDir . ltrim($script, '/'));
        }

        if (is_dir($this->config->general->assetsPath)) {
            $vehicle->addAssetsResolver($this->config->general->assetsPath);
        }

        if (is_dir($this->config->general->corePath)) {
            $vehicle->addCoreResolver($this->config->general->corePath);
        }

        $fileResolvers = $resolver->files;
        foreach ($fileResolvers as $fileResolver) {
            $source = $fileResolver['source'];

            $source = str_replace('[[+corePath]]', $this->config->general->corePath, $source);
            $source = str_replace('[[+assetsPath]]', $this->config->general->assetsPath, $source);
            $source = str_replace('[[+packagePath]]', $this->config->packagePath, $source);

            $vehicle->addFileResolver($source, $fileResolver['target']);
        }

        $db = $this->config->database;
        if (($db != null) && (!empty($db->tables))) {
            $vehicle->addTableResolver($this->config->packagePath . '/_build/gpm_resolvers', $db);
        }

        $extensionPackage = $this->config->extensionPackage;
        if ($extensionPackage !== null) {
            $vehicle->addExtensionPackageResolver($this->config->packagePath . '/_build/gpm_resolvers', $extensionPackage);
        }

        if (!empty($this->tvMap)) {
            $vehicle->addTVResolver($this->config->packagePath . '/_build/gpm_resolvers', $this->tvMap);
        }

        $resources = $this->config->resources;
        $resourcesArray = array();

        foreach ($resources as $resource) {
            $resourcesArray[] = $resource->toRawArray();
        }

        if (!empty($resourcesArray)) {
            $vehicle->addResourceResolver($this->config->packagePath . '/_build/gpm_resolvers', $resourcesArray);
        }

        $this->addSystemSettings();

        $after = $resolver->after;
        foreach ($after as $script) {
            $vehicle->addPHPResolver($resolversDir . ltrim($script, '/'));
        }

        $this->builder->putVehicle($vehicle);
        $this->addMenus();

        $packageAttributes = array();

        $license = ltrim($buildOptions->license, '/');
        if (!empty($license) && file_exists($this->config->general->corePath . $license)) {
            $packageAttributes['license'] = file_get_contents($this->config->general->corePath . $license);
        }

        $readme = ltrim($buildOptions->readme, '/');
        if (!empty($readme) && file_exists($this->config->general->corePath . $readme)) {
            $packageAttributes['readme'] = file_get_contents($this->config->general->corePath . $readme);
        }

        $changeLog = ltrim($buildOptions->changelog, '/');
        if (!empty($changeLog) && file_exists($this->config->general->corePath . $changeLog)) {
            $packageAttributes['changelog'] = file_get_contents($this->config->general->corePath . $changeLog);
        }

        $setupOptions = $buildOptions->setupOptions;
        if (!empty($setupOptions) && isset($setupOptions['source']) && !empty($setupOptions['source'])) {
            $file = $this->config->packagePath . '/_build/' . $setupOptions['source'];
            if (file_exists($file)) {
                $setupOptions['source'] = $file;
                $packageAttributes['setup-options'] = $setupOptions;
            }
        }

        $this->builder->setPackageAttributes($packageAttributes);

        $this->builder->pack();
        
        return true;
    }

    private function addCategory()
    {
        /** @var \modCategory $category */
        $category = $this->modx->newObject('modCategory');
        $category->set('category', $this->config->general->name);

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

    private function getCategories($parent = null)
    {
        $cats = $this->getCategoriesForParent($parent);
        $retCategories = array();

        foreach ($cats as $cat) {
            /** @var \modCategory $category */
            $category = $cat->getObject(true);

            $snippets = $this->getSnippets($cat->name);
            if (!empty($snippets)) {
                $category->addMany($snippets, 'Snippets');
            }

            $chunks = $this->getChunks($cat->name);
            if (!empty($chunks)) {
                $category->addMany($chunks, 'Chunks');
            }

            $plugins = $this->getPlugins($cat->name);
            if (!empty($plugins)) {
                $category->addMany($plugins, 'Plugins');
            }

            $templates = $this->getTemplates($cat->name);
            if (!empty($templates)) {
                $category->addMany($templates, 'Templates');
            }

            $templateVariables = $this->getTemplateVariables($cat->name);
            if (!empty($templateVariables)) {
                $category->addMany($templateVariables, 'TemplateVars');
            }

            $categories = $this->getCategories($cat->name);
            if (!empty($categories)) {
                $category->addMany($categories, 'Children');
            }

            $retCategories[] = $category;
        }

        return $retCategories;
    }

    private function getSnippets($category = null)
    {
        $snippets = array();

        $configSnippets = $this->config->snippets;
        if (count($configSnippets) > 0) {
            foreach ($configSnippets as $configSnippet) {
                if ($configSnippet->category != $category) continue;
                if ($configSnippet->build === false) continue;

                $snippetObject = $configSnippet->getObject(true);
                $snippets[] = $snippetObject;
            }
        }

        return $snippets;
    }

    private function getChunks($category = null)
    {
        $chunks = array();

        $configChunks = $this->config->chunks;
        if (count($configChunks) > 0) {
            foreach ($configChunks as $configChunk) {
                if ($configChunk->category != $category) continue;
                if ($configChunk->build === false) continue;

                $chunkObject = $configChunk->getObject(true);
                $chunks[] = $chunkObject;
            }
        }

        return $chunks;
    }

    private function getTemplates($category = null)
    {
        $templates = array();

        $configTemplates = $this->config->templates;
        if (count($configTemplates) > 0) {
            foreach ($configTemplates as $configTemplate) {
                if ($configTemplate->category != $category) continue;
                if ($configTemplate->build === false) continue;

                $templateObject = $configTemplate->getObject(true);
                $templates[] = $templateObject;
            }
        }

        return $templates;
    }

    private function getTemplateVariables($category = null)
    {
        $templateVariables = array();

        $configTVs = $this->config->tvs;
        if (count($configTVs) > 0) {
            foreach ($configTVs as $configTV) {
                if ($configTV->category != $category) continue;
                if ($configTV->build === false) continue;

                $tvObject = $configTV->getObject(true);
                $this->tvMap[$configTV->name] = $configTV->templates;
                $templateVariables[] = $tvObject;
            }
        }

        return $templateVariables;
    }

    private function getPlugins($category = null)
    {
        $plugins = array();

        $configPlugins = $this->config->plugins;
        if (count($configPlugins) > 0) {

            foreach ($configPlugins as $configPlugin) {
                if ($configPlugin->category != $category) continue;
                if ($configPlugin->build === false) continue;

                $pluginObject = $configPlugin->getObject(true);

                $plugins[] = $pluginObject;
            }
        }

        return $plugins;
    }

    private function addMenus()
    {
        $menus = $this->config->menus;

        foreach ($menus as $menu) {
            $menuObject = $menu->getObject(true);

            if ($menu->action instanceof Action) {
                $actionObject = $menu->action->getObject(true);

                $menuObject->addOne($actionObject);
            }

            $vehicle = $this->builder->createVehicle($menuObject, 'menu');
            $this->builder->putVehicle($vehicle);
        }
    }

    private function addSystemSettings()
    {
        $settings = $this->config->systemSettings;

        foreach ($settings as $setting) {
            if ($setting->build === false) continue;
            
            /** @var \modSystemSetting $settingObject */
            $settingObject = $this->modx->newObject('modSystemSetting');
            $settingObject->fromArray(array(
                'key' => $setting->getNamespacedKey(),
                'value' => $setting->value,
                'xtype' => $setting->type,
                'namespace' => $this->config->general->lowCaseName,
                'area' => $setting->area,
            ), '', true, true);

            $vehicle = $this->builder->createVehicle($settingObject, 'setting');
            $this->builder->putVehicle($vehicle);
        }
    }

    private function loadSmarty()
    {
        $this->smarty = $this->modx->getService('smarty', 'smarty.modSmarty');
        $this->smarty->setTemplatePath($this->gpm->getOption('templatesPath') . '/gitpackagebuild/');

        $this->smarty->assign_by_ref('general', $this->config->general);
    }

    /**
     * @param $parent
     * @return \GPM\Config\Object\Category[]
     */
    private function getCategoriesForParent($parent)
    {
        $categories = array();
        $allCategories = $this->config->categories;
        foreach ($allCategories as $category) {
            if ($category->parent == $parent) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

}