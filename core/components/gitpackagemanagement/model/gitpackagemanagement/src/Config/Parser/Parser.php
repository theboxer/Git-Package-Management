<?php
namespace GPM\Config\Parser;

use GPM\Config\Config;
use GPM\Config\Object;
use GPM\Config\Object\General;

final class Parser
{
    /** @var \modX */
    public $modx;
    
    /** @var Config */
    public $config;
    
    public function __construct(Config $config)
    {
        $this->modx =& $config->modx;
        $this->config = $config;
    }

    public function parseGeneral($general, $skip = true)
    {
        $this->config->general = new General($this->config, $general);
        
        return true;
    }

    public function parseAction($action, $skip = true)
    {
        $this->config->actions[] = new Object\Action($this->config, $action);
        
        return true;
    }

    public function parseMenu($menu, $skip = true)
    {
        $this->config->menus[] = new Object\Menu($this->config, $menu);
        
        return true;
    }

    public function parseCategory($category, $skip = true)
    {
        $categoryObject = new Object\Category($this->config, $category);
        if ($skip && isset($this->config->categories[$categoryObject->name])) return true;
        
        $this->config->categories[$categoryObject->name] = $categoryObject;

        return true;
    }

    public function parsePlugin($plugin, $skip = true)
    {
        $pluginObject = new Object\Element\Plugin($this->config, $plugin);
        if ($skip && isset($this->config->plugins[$pluginObject->name])) return true;
        
        $this->config->plugins[$pluginObject->name] = $pluginObject;

        return true;
    }

    public function parseSnippet($snippet, $skip = true)
    {
        $snippetObject = new Object\Element\Snippet($this->config, $snippet);
        if ($skip && isset($this->config->snippets[$snippetObject->name])) return true;
        
        $this->config->snippets[$snippetObject->name] = $snippetObject;

        return true;
    }

    public function parseChunk($chunk, $skip = true)
    {
        $chunkObject = new Object\Element\Chunk($this->config, $chunk);
        if ($skip && isset($this->config->chunks[$chunkObject->name])) return true;
        
        $this->config->chunks[$chunkObject->name] = $chunkObject;
        
        return true;
    }

    public function parseTemplate($template, $skip = true)
    {
        $this->config->templates = new Object\Element\Template($this->config, $template);
    }

    public function parseTV($tv, $skip = true)
    {
        $tvObject = new Object\Element\TV($this->config, $tv);
        if ($skip && isset($this->config->tvs[$tvObject->name])) return true;
        
        $this->config->tvs[$tvObject->name] = $tvObject;

        return true;
    }

    public function parseResource($resource, $skip = true)
    {
        $this->config->resources[] = new Object\Resource($this->config, $resource);
        
        return true;
    }

    public function parseSystemSetting($systemSetting, $skip = true)
    {
        $settingObject = new Object\Setting($this->config, $systemSetting);
        if ($skip && isset($this->config->systemSettings[$settingObject->getNamespacedKey()])) return true;
        
        $this->config->systemSettings[$settingObject->getNamespacedKey()] = $settingObject;

        return true;
    }

    public function parseDatabase($database, $skip = true)
    {
        $this->config->database = new Object\Database($this->config, $database);
    }

    public function parseExtensionPackage($extensionPackage, $skip = true)
    {
        $this->config->extensionPackage = new Object\ExtensionPackage($this->config, $extensionPackage);
        
        return true;
    }

    public function parseBuild($build, $skip = true)
    {
        $this->config->build = new Object\Build\Build($this->config, $build);
        
        return true;
    }

    public function parseDependency($dependency, $skip = true)
    {
        $this->config->dependencies[] = new Object\Dependency($this->config, $dependency);
        
        return true;
    }
}