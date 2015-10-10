<?php
namespace GPM\Config\Loader;

use GPM\Config\Object\General;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;

final class JSON implements iLoader
{
    /** @var Parser */
    private $parser;
    
    /** @var string */
    private $path = '';
    
    /** @var array */
    private $config = [];

    /**
     * @param Parser $parser
     * @param null $path
     * @throws \Exception
     */
    public function __construct(Parser $parser, $path = null)
    {
        $this->parser = $parser;
        
        if ($path === null) {
            $this->path = rtrim($parser->config->packagePath, '/\\') . DIRECTORY_SEPARATOR . '_build' . DIRECTORY_SEPARATOR;
        } else {
            $this->path = $path;
        }
        
        $this->config = $this->loadFile('config.json');
        
        if (isset($this->config['package']) && is_string($this->config['package'])) {
            $this->config['package'] = $this->loadFile($this->config['package']);
        }
        
        if (isset($this->config['package']['elements']) && is_string($this->config['package']['elements'])) {
            $this->config['package']['elements'] = $this->loadFile($this->config['package']['elements']);
        }
    }

    /**
     * Loads all parts
     *
     * @param bool $skip
     * @return mixed
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadAll($skip = true)
    {
        $this->loadGeneral($skip);
        $this->loadActions($skip);
        $this->loadMenus($skip);
        $this->loadCategories($skip);
        $this->loadPlugins($skip);
        $this->loadSnippets($skip);
        $this->loadChunks($skip);
        $this->loadTemplates($skip);
        $this->loadTVs($skip);
        $this->loadResources($skip);
        $this->loadSystemSettings($skip);
        $this->loadDatabase($skip);
        $this->loadExtensionPackage($skip);
        $this->loadBuild($skip);
        $this->loadDependencies($skip);
    }

    /**
     * @param string $fileName
     * @return array
     * @throws ValidatorException
     * @throws \Exception
     */
    private function loadFile($fileName)
    {
        $file = $this->path . $fileName;
        if (!file_exists($file)) throw new \Exception($fileName . ' not found.');
       
        $fileContent = file_get_contents($file);
        $fileContent = json_decode($fileContent, true);

        if ($fileContent === null) throw new \Exception('JSON in file "' . $fileName . '" is invalid');
        
        if (!is_array($fileContent)) return [];

        return $fileContent;
    }

    /**
     * Load general info about package
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadGeneral($skip = true)
    {
        $general = [];
        
        if (isset($this->config['name'])) $general['name'] = $this->config['name']; 
        if (isset($this->config['lowCaseName'])) $general['lowCaseName'] = $this->config['lowCaseName']; 
        if (isset($this->config['description'])) $general['description'] = $this->config['description']; 
        if (isset($this->config['author'])) $general['author'] = $this->config['author']; 
        if (isset($this->config['version'])) $general['version'] = $this->config['version'];
        
        return $this->parser->parseGeneral($general);
    }

    /**
     * Load Actions
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadActions($skip = true)
    {
        if (empty($this->config['package']['actions'])) return true;

        $actions = $this->config['package']['actions'];
        
        if (is_string($actions)) {
            $actions = $this->loadFile($actions);
        }
        
        if (!is_array($actions)) {
            throw new \Exception('Actions are supposed to be an array.');
        }
        
        foreach ($actions as $action) {
            $this->parser->parseAction($action, $skip);
        }
        
        return true;
    }

    /**
     * Load Menus
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadMenus($skip = true)
    {
        if (empty($this->config['package']['menus'])) return true;

        $menus = $this->config['package']['menus'];
        
        if (is_string($menus)) {
            $menus = $this->loadFile($menus);
        }

        if (!is_array($menus)) {
            throw new \Exception('Menus are supposed to be an array.');
        }

        foreach ($menus as $menu) {
            $this->parser->parseMenu($menu, $skip);
        }
        
        return $this->config['package']['menus'];
    }

    /**
     * Load Categories
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadCategories($skip = true)
    {
        if (empty($this->config['package']['elements']['categories'])) return true;

        $categories = $this->config['package']['elements']['categories'];
        
        if (is_string($categories)) {
            $categories = $this->loadFile($categories);
        }

        if (!is_array($categories)) {
            throw new \Exception('Categories are supposed to be an array.');
        }

        foreach ($categories as $category) {
            $this->parser->parseCategory($category, $skip);
        }

        return true;
    }

    /**
     * Load Plugins
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadPlugins($skip = true)
    {
        if (empty($this->config['package']['elements']['plugins'])) return true;

        $plugins = $this->config['package']['elements']['plugins'];
        
        if (is_string($plugins)) {
            $plugins = $this->loadFile($plugins);
        }

        if (!is_array($plugins)) {
            throw new \Exception('Plugins are supposed to be an array.');
        }

        foreach ($plugins as $plugin) {
            $this->parser->parsePlugin($plugin, $skip);
        }
        
        return true;
    }

    /**
     * Load Snippets
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadSnippets($skip = true)
    {
        if (empty($this->config['package']['elements']['snippets'])) return true;

        $snippets = $this->config['package']['elements']['snippets'];
        
        if (is_string($snippets)) {
            $snippets = $this->loadFile($snippets);
        }

        if (!is_array($snippets)) {
            throw new \Exception('Snippets are supposed to be an array.');
        }

        foreach ($snippets as $snippet) {
            $this->parser->parseSnippet($snippet, $skip);
        }

        return true;
    }

    /**
     * Load Chunks
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadChunks($skip = true)
    {
        if (empty($this->config['package']['elements']['chunks'])) return true;

        $chunks = $this->config['package']['elements']['chunks'];
        
        if (is_string($chunks)) {
            $chunks = $this->loadFile($chunks);
        }

        if (!is_array($chunks)) {
            throw new \Exception('Chunks are supposed to be an array.');
        }

        foreach ($chunks as $chunk) {
            $this->parser->parseChunk($chunk, $skip);
        }

        return true;
    }

    /**
     * Load Templates
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadTemplates($skip = true)
    {
        if (empty($this->config['package']['elements']['templates'])) return true;

        $templates = $this->config['package']['elements']['templates'];
        
        if (is_string($templates)) {
            $templates = $this->loadFile($templates);
        }

        if (!is_array($templates)) {
            throw new \Exception('Templates are supposed to be an array.');
        }

        foreach ($templates as $template) {
            $this->parser->parseTemplate($template, $skip);
        }

        return true;
    }

    /**
     * Load TVs
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadTVs($skip = true)
    {
        if (empty($this->config['package']['elements']['tvs'])) return true;

        $tvs = $this->config['package']['elements']['tvs'];
        
        if (is_string($tvs)) {
            $tvs = $this->loadFile($tvs);
        }

        if (!is_array($tvs)) {
            throw new \Exception('TVs are supposed to be an array.');
        }

        foreach ($tvs as $tv) {
            $this->parser->parseTV($tv, $skip);
        }

        return true;
    }

    /**
     * Load Resources
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadResources($skip = true)
    {
        if (empty($this->config['package']['resources'])) return true;

        $resources = $this->config['package']['resources'];
        
        if (is_string($resources)) {
            $resources = $this->loadFile($resources);
        }

        if (!is_array($resources)) {
            throw new \Exception('Resources are supposed to be an array.');
        }

        foreach ($resources as $resource) {
            $this->parser->parseResource($resource, $skip);
        }

        return true;
    }

    /**
     * Load System settings
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadSystemSettings($skip = true)
    {
        $this->parser->parseSystemSetting([
            'key' => 'core_path',
            'value' => $this->parser->config->general->corePath,
            'area' => 'Git Package Management Settings',
            'build' => false
        ], true);

        $this->parser->parseSystemSetting([
            'key' => 'assets_path',
            'value' => $this->parser->config->general->assetsPath,
            'area' => 'Git Package Management Settings',
            'build' => false
        ], true);

        $this->parser->parseSystemSetting([
            'key' => 'assets_url',
            'value' => $this->parser->config->general->assetsURL,
            'area' => 'Git Package Management Settings',
            'build' => false
        ], true);
        
        if (empty($this->config['package']['systemSettings'])) return true;

        $systemSettings = $this->config['package']['systemSettings'];
        
        if (is_string($systemSettings)) {
            $systemSettings = $this->loadFile($systemSettings);
        }

        if (!is_array($systemSettings)) {
            throw new \Exception('System settings are supposed to be an array.');
        }

        foreach ($systemSettings as $systemSetting) {
            $this->parser->parseSystemSetting($systemSetting, $skip);
        }
        return true;
    }

    /**
     * Load Database
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadDatabase($skip = true)
    {
        if (empty($this->config['database'])) return true;

        $database = $this->config['database'];
        
        if (is_string($database)) {
            $database = $this->loadFile($database);
        }
        
        $this->parser->parseDatabase($database, $skip);

        return true;
    }

    /**
     * Load Extension packages
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadExtensionPackage($skip = true)
    {
        if (!isset($this->config['extensionPackage'])) return true;

        $extensionPackage = $this->config['extensionPackage'];
        
        if (is_string($extensionPackage)) {
            $extensionPackage = $this->loadFile($extensionPackage);
        }
        
        $this->parser->parseExtensionPackage($extensionPackage, $skip);

        return true;
    }

    /**
     * Load Build
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadBuild($skip = true)
    {
        $build = [];
        if (!empty($this->config['build'])) {
            $build = $this->config['build'];
        }

        if (is_string($build)) {
            $build = $this->loadFile($build);
        }

        $this->parser->parseBuild($build, $skip);

        return true;
    }

    /**
     * Load Dependencies
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadDependencies($skip = true)
    {
        if (empty($this->config['dependencies'])) return true;

        $dependencies = $this->config['dependencies'];
        
        if (is_string($dependencies)) {
            $dependencies = $this->loadFile($dependencies);
        }

        if (!is_array($dependencies)) {
            throw new \Exception('Dependencies are supposed to be an array.');
        }

        foreach ($dependencies as $dependency) {
            $this->parser->parseDependency($dependency, $skip);
        }

        return true;
    }
}