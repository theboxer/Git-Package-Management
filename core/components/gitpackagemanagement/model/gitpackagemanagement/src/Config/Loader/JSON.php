<?php
namespace GPM\Config\Loader;

final class JSON implements iLoader
{
    /** @var string */
    private $path = '';
    
    /** @var array */
    private $config = [];
    
    /**
     * @param string $path
     * @throws \Exception
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '_build' . DIRECTORY_SEPARATOR;
        $this->config = $this->loadFile('config.json');
        
        if (isset($this->config['package']) && is_string($this->config['package'])) {
            $this->config['package'] = $this->loadFile($this->config['package']);
        }
        
        if (isset($this->config['package']['elements']) && is_string($this->config['package']['elements'])) {
            $this->config['package']['elements'] = $this->loadFile($this->config['package']['elements']);
        }
    }

    /**
     * @param string $fileName
     * @return array
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
     * Load general info about packge
     *
     * @return array
     */
    public function loadGeneral()
    {
        $general = [];
        
        if (isset($this->config['name'])) $general['name'] = $this->config['name']; 
        if (isset($this->config['lowCaseName'])) $general['lowCaseName'] = $this->config['lowCaseName']; 
        if (isset($this->config['description'])) $general['description'] = $this->config['description']; 
        if (isset($this->config['author'])) $general['author'] = $this->config['author']; 
        if (isset($this->config['version'])) $general['version'] = $this->config['version'];

        return $general;
    }

    /**
     * Load Actions
     *
     * @return array
     */
    public function loadActions()
    {
        if (!isset($this->config['package']['actions'])) return [];

        if (is_string($this->config['package']['actions'])) {
            return $this->loadFile($this->config['package']['actions']);
        }
        
        return $this->config['package']['actions'];
    }

    /**
     * Load Menus
     *
     * @return array
     */
    public function loadMenus()
    {
        if (!isset($this->config['package']['menus'])) return [];

        if (is_string($this->config['package']['menus'])) {
            return $this->loadFile($this->config['package']['menus']);
        }

        return $this->config['package']['menus'];
    }

    /**
     * Load Categories
     *
     * @return array
     */
    public function loadCategories()
    {
        if (!isset($this->config['package']['elements']['categories'])) return [];

        if (is_string($this->config['package']['elements']['categories'])) {
            return $this->loadFile($this->config['package']['elements']['categories']);
        }

        return $this->config['package']['elements']['categories'];
    }
    
    /**
     * Load Plugins
     *
     * @return array
     */
    public function loadPlugins()
    {
        if (!isset($this->config['package']['elements']['plugins'])) return [];

        if (is_string($this->config['package']['elements']['plugins'])) {
            return $this->loadFile($this->config['package']['elements']['plugins']);
        }
        
        return $this->config['package']['elements']['plugins'];
    }

    /**
     * Load Snippets
     *
     * @return array
     */
    public function loadSnippets()
    {
        if (!isset($this->config['package']['elements']['snippets'])) return [];

        if (is_string($this->config['package']['elements']['snippets'])) {
            return $this->loadFile($this->config['package']['elements']['snippets']);
        }

        return $this->config['package']['elements']['snippets'];
    }

    /**
     * Load Chunks
     *
     * @return array
     */
    public function loadChunks()
    {
        if (!isset($this->config['package']['elements']['chunks'])) return [];

        if (is_string($this->config['package']['elements']['chunks'])) {
            return $this->loadFile($this->config['package']['elements']['chunks']);
        }

        return $this->config['package']['elements']['chunks'];
    }

    /**
     * Load Templates
     *
     * @return array
     */
    public function loadTemplates()
    {
        if (!isset($this->config['package']['elements']['templates'])) return [];

        if (is_string($this->config['package']['elements']['templates'])) {
            return $this->loadFile($this->config['package']['elements']['templates']);
        }

        return $this->config['package']['elements']['templates'];
    }

    /**
     * Load TVs
     *
     * @return array
     */
    public function loadTVs()
    {
        if (!isset($this->config['package']['elements']['tvs'])) return [];

        if (is_string($this->config['package']['elements']['tvs'])) {
            return $this->loadFile($this->config['package']['elements']['tvs']);
        }

        return $this->config['package']['elements']['tvs'];
    }

    /**
     * Load Resources
     *
     * @return array
     */
    public function loadResources()
    {
        if (!isset($this->config['package']['resources'])) return [];

        if (is_string($this->config['package']['resources'])) {
            return $this->loadFile($this->config['package']['resources']);
        }

        return $this->config['package']['resources'];
    }

    /**
     * Load System settings
     *
     * @return array
     */
    public function loadSystemSettings()
    {
        if (!isset($this->config['package']['systemSettings'])) return [];

        if (is_string($this->config['package']['systemSettings'])) {
            return $this->loadFile($this->config['package']['systemSettings']);
        }

        return $this->config['package']['systemSettings'];
    }

    /**
     * Load Database
     *
     * @return array
     */
    public function loadDatabase()
    {
        if (!isset($this->config['database'])) return [];

        if (is_string($this->config['database'])) {
            return $this->loadFile($this->config['database']);
        }

        return $this->config['database'];
    }

    /**
     * Load Extension packages
     *
     * @return array
     */
    public function loadExtensionPackage()
    {
        if (!isset($this->config['extensionPackage'])) return [];

        if (is_string($this->config['extensionPackage'])) {
            return $this->loadFile($this->config['extensionPackage']);
        }

        return $this->config['extensionPackage'];
    }

    /**
     * Load Build
     *
     * @return array
     */
    public function loadBuild()
    {
        if (!isset($this->config['build'])) return [];

        if (is_string($this->config['build'])) {
            return $this->loadFile($this->config['build']);
        }

        return $this->config['build'];
    }
    
    /**
     * Load Dependencies
     *
     * @return array
     */
    public function loadDependencies()
    {
        if (!isset($this->config['dependencies'])) return [];

        if (is_string($this->config['dependencies'])) {
            return $this->loadFile($this->config['dependencies']);
        }

        return $this->config['dependencies'];
    }
}