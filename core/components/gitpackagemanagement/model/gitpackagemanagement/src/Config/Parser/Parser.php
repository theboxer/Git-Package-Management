<?php
namespace GPM\Config\Parser;

use GPM\Config\Config;
use GPM\Config\Object;
use GPM\Config\Object\General;

class Parser
{
    /** @var \modX */
    protected $modx;
    /** @var Config */
    protected $config;
    
    public function __construct(\modX &$modx, Config $config)
    {
        $this->modx =& $modx;
        $this->config = $config;
    }

    public function parseGeneral(&$target, $general, $skip = true)
    {
        $target = new General($this->config, $general);
    }

    public function parseActions(&$target, $actions, $skip = true)
    {
        foreach ($actions as $action) {
            $target[] = new Object\Action($this->config, $action);
        }
    }

    public function parseMenus(&$target, $menus, $skip = true)
    {
        foreach ($menus as $menu) {
            $target[] = new Object\Menu($this->config, $menu);
        }
    }

    public function parseCategories(&$target, $categories, $skip = true)
    {
        foreach ($categories as $category) {
            $categoryObject = new Object\Category($this->config, $category);
            if ($skip && isset($target[$categoryObject->getName()])) continue;
            
            $target[$categoryObject->getName()] = $categoryObject;
        }
    }

    public function parsePlugins(&$target, $plugins, $skip = true)
    {
        foreach ($plugins as $plugin) {
            $pluginObject = new Object\Element\Plugin($this->config, $plugin);
            if ($skip && isset($target[$pluginObject->getName()])) continue;
            
            $target[$pluginObject->getName()] = $pluginObject;
        }
    }

    public function parseSnippets(&$target, $snippets, $skip = true)
    {
        foreach ($snippets as $snippet) {
            $snippetObject = new Object\Element\Snippet($this->config, $snippet);
            if ($skip && isset($target[$snippetObject->getName()])) continue;
            
            $target[$snippetObject->getName()] = $snippetObject;
        }
    }

    public function parseChunks(&$target, $chunks, $skip = true)
    {
        foreach ($chunks as $chunk) {
            $chunkObject = new Object\Element\Chunk($this->config, $chunk);
            if ($skip && isset($target[$chunkObject->getName()])) continue;
            
            $target[$chunkObject->getName()] = $chunkObject;
        }
    }

    public function parseTemplates(&$target, $templates, $skip = true)
    {
        foreach ($templates as $template) {
            $target[] = new Object\Element\Template($this->config, $template);
        }
    }

    public function parseTvs(&$target, $tvs, $skip = true)
    {
        foreach ($tvs as $tv) {
            $tvObject = new Object\Element\TV($this->config, $tv);
            if ($skip && isset($target[$tvObject->getName()])) continue;
            
            $target[$tvObject->getName()] = $tvObject;
        }
    }

    public function parseResources(&$target, $resources, $skip = true)
    {
        foreach ($resources as $resource) {
            $target[] = new Object\Resource($this->config, $resource, $this->modx);
        }
    }

    public function parseSystemSettings(&$target, $systemSettings, $skip = true)
    {
        foreach ($systemSettings as $systemSetting) {
            $settingObject = new Object\Setting($this->config, $systemSetting);
            if ($skip && isset($target[$settingObject->getNamespacedKey()])) continue;
            
            $target[$settingObject->getNamespacedKey()] = $settingObject;
        }
    }

    public function parseDatabase(&$target, $database, $skip = true)
    {
        $target = new Object\Database($this->config, $database);
    }

    public function parseExtensionPackage(&$target, $extensionPackage, $skip = true)
    {
        $target = new Object\ExtensionPackage($this->config, $extensionPackage);
    }

    public function parseBuild(&$target, $build, $skip = true)
    {
        $target = new Object\Build\Build($this->config, $build);
    }

    public function parseDependencies(&$target, $dependencies, $skip = true)
    {
        foreach ($dependencies as $dependency) {
            $target[] = new Object\Dependency($this->config, $dependency);
        }
    }
}