<?php
namespace GPM\Config;

use GPM\Error\Error;

class Config
{
    /** @var \modX $modx */
    protected $modx;
    /** @var \GitPackageManagement $gpm */
    protected $gpm;
    /** @var string $packagePath */
    protected $packagePath;
    /** @var string $name Package name */
    protected $name = null;
    /** @var string $lowCaseName Package low case name */
    protected $lowCaseName = null;
    /** @var string $author Package author */
    protected $author = null;
    /** @var string $version Package current version */
    protected $version = null;
    /** @var string $description Package description */
    protected $description = null;
    /** @var Action[] $action Array with package's actions */
    protected $actions = array();
    /** @var Menu[] $menus Array with package's menus */
    protected $menus = array();
    /** @var Setting[] $settings Array with package's settings */
    protected $settings = array();
    /** @var Database $database Object with package's database information */
    protected $database = null;
    /** @var array $extensionPackage Array with extensionPackage information */
    protected $extensionPackage = false;
    /** @var array $elements Array with all elements */
    protected $elements = array('plugins' => array(), 'snippets' => array(), 'chunks' => array(), 'templates' => array(), 'tvs' => array());
    /** @var Resource[] $resources */
    protected $resources = array();
    protected $dependencies = array();
    /** @var Build $build */
    protected $build = null;
    /** @var Category[] $categories */
    protected $categories = array();
    /** @var Error $error */
    public $error;

    /**
     * @param \modX $modx
     * @param string $packagePath
     */
    public function __construct(\modX &$modx, $packagePath)
    {
        $this->modx =& $modx;
        $this->gpm =& $this->modx->gitpackagemanagement;
        $this->packagePath = $packagePath;

        $this->error = new Error($this->modx);
    }

    /**
     * Parse and validate given array into objects
     * @param $config Array
     * @return bool
     */
    public function parseConfig($config)
    {
        $this->setGlobals($config);

        if ($this->error->hasErrors()) return false;

        if (isset($config['package'])) {
            $this->setPackage($config['package']);
        }

        if (isset($config['database'])) {
            $this->setDatabase($config['database']);
        }

        if (!isset($config['build'])) {
            $config['build'] = array();
        }

        $this->setBuild($config['build']);

        if (isset($config['dependencies'])) {
            $this->dependencies = $config['dependencies'];
        }

        if (isset($config['extensionPackage'])) {
            $this->setExtensionPackage($config['extensionPackage']);
        }

        return !$this->error->hasErrors();
    }

    /**
     * Parse and validate plugins array
     * @param $plugins Array
     * @return bool
     */
    private function setPluginElements($plugins)
    {
        foreach ($plugins as $plugin) {
            $p = new Element\Plugin($this->modx, $this);

            try {
                $p->fromArray($plugin);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }

            $this->elements['plugins'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate categories array
     * @param $categories Array
     * @return bool
     */
    private function setCategories($categories)
    {
        foreach ($categories as $category) {
            $c = new Category($this->modx, $this);

            try {
                $c->fromArray($category);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }

            $this->categories[$c->getName()] = $c;
        }

        return true;
    }

    /**
     * Parse and validate snippets array
     * @param $snippets Array
     * @return bool
     */
    private function setSnippetElements($snippets)
    {
        foreach ($snippets as $snippet) {
            $p = new Element\Snippet($this->modx, $this);

            try {
                $p->fromArray($snippet);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }

            $this->elements['snippets'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate chunks array
     * @param $chunks Array
     * @return bool
     */
    private function setChunkElements($chunks)
    {
        foreach ($chunks as $chunk) {
            $p = new Element\Chunk($this->modx, $this);

            try {
                $p->fromArray($chunk);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }

            $this->elements['chunks'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate templates array
     * @param $templates Array
     * @return bool
     */
    private function setTemplateElements($templates)
    {
        foreach ($templates as $template) {
            $p = new Element\Template($this->modx, $this);

            try {
                $p->fromArray($template);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }

            $this->elements['templates'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate TVs array
     * @param $tvs Array
     * @return bool
     */
    private function setTVElements($tvs)
    {
        foreach ($tvs as $tv) {
            $p = new Element\TV($this->modx, $this);

            try {
                $p->fromArray($tv);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }

            $this->elements['tvs'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate actions
     * @param $actions Array
     * @return bool
     */
    private function setActions($actions)
    {
        foreach ($actions as $action) {
            $a = new Action($this->modx, $this);

            try {
                $a->fromArray($action);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }

            $this->actions[] = $a;
        }

        return true;
    }

    /**
     * Parse and validate menus
     * @param $menus Array
     * @return bool
     */
    private function setMenus($menus)
    {
        foreach ($menus as $menu) {
            $m = new Menu($this->modx, $this);
            
            try {
                $m->fromArray($menu);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }
            
            $this->menus[] = $m;
        }

        return true;
    }

    /**
     * Parse and validate settings
     * @param $settings
     * @return bool
     */
    private function setSettings($settings)
    {
        foreach ($settings as $setting) {
            $s = new Setting($this->modx, $this);
            
            try {
                $s->fromArray($setting);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }
            
            $this->settings[$s->getNamespacedKey()] = $s;
        }

        return true;
    }

    /**
     * Parse and validate database information
     * @param $database
     * @return bool
     */
    private function setDatabase($database)
    {
        $this->database = new Database($this->modx);
        
        try {
            $this->database->fromArray($database);
        } catch (\Exception $e) {
            $this->error->addError($e->getMessage(), true);
        }

        return true;
    }

    /**
     * Set build options
     * @param $build
     * @return bool
     */
    private function setBuild($build)
    {
        $this->build = new Build($this->modx, $this);
        
        try {
            $this->build->fromArray($build);
        } catch (\Exception $e) {
            $this->error->addError($e->getMessage(), true);
        }

        return true;
    }

    /**
     * Parse and validate resources array
     * @param $resources Array
     * @return bool
     */
    private function setResources($resources)
    {
        foreach ($resources as $resource) {
            $p = new Resource($this->modx, $this);
            
            try {
                $p->fromArray($resource);
            } catch (\Exception $e) {
                $this->error->addError($e->getMessage(), true);
            }
            
            $this->resources[] = $p;
        }

        return true;
    }

    /**
     * Returns array of GitPackageConfigAction objects
     * @return Action[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Returns author of package
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Returns low case name of package
     * @return string
     */
    public function getLowCaseName()
    {
        return $this->lowCaseName;
    }

    /**
     * Returns array of GitPackageConfigMenu objects
     * @return Menu[]
     */
    public function getMenus()
    {
        return $this->menus;
    }

    /**
     * Returns name of package
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns array of GitPackageConfigSetting objects
     * @return Setting[]
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Returns version of package
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns description of package
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns information about Database
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Returns information about extension package
     * @return array|null
     */
    public function getExtensionPackage()
    {
        return $this->extensionPackage;
    }

    /**
     * Return array with all elements, or only with elements of type $type
     * @param string $type (default is null)
     * @return Element\Element[]
     */
    public function getElements($type = null)
    {
        if ($type) {
            return $this->elements[$type];
        }

        return $this->elements;
    }

    /**
     * @return string
     */
    public function getPackagePath()
    {
        return $this->packagePath;
    }

    /**
     * Return array with all resources
     *
     * @return Resource[]
     */
    public function getResources()
    {
        return $this->resources;
    }

    public function getAssetsFolder()
    {
        $assetsFolder = $this->gpm->getOption('assetsPath');

        if (!is_dir($assetsFolder . 'packages')) {
            mkdir($assetsFolder . 'packages');
        }

        $assetsFolder .= 'packages/';

        if (!is_dir($assetsFolder . $this->getLowCaseName())) {
            mkdir($assetsFolder . $this->getLowCaseName());
        }

        return $assetsFolder . $this->getLowCaseName() . '/';
    }

    public function checkDependencies()
    {
        $failed = array();

        foreach ($this->dependencies as $dependency) {
            $found = $this->modx->getCount('transport.modTransportPackage', array('package_name' => $dependency));
            $foundInGPM = $this->modx->getCount('GitPackage', array('name' => $dependency, 'OR:dir_name:=' => $dependency));

            if ($found == 0 && $foundInGPM == 0) {
                $failed[] = $dependency;
            }
        }

        if (count($failed) == 0) {
            return true;
        }

        return $failed;
    }

    /**
     * @return Build
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * @return Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param $elements
     * @throws \Exception
     */
    protected function setElements($elements)
    {
        if (isset($elements['categories'])) {
            $this->setCategories($elements['categories']);
        }

        if (isset($elements['plugins'])) {
            $this->setPluginElements($elements['plugins']);
        }

        if (isset($elements['snippets'])) {
            $this->setSnippetElements($elements['snippets']);
        }

        if (isset($elements['chunks'])) {
            $this->setChunkElements($elements['chunks']);
        }

        if (isset($elements['templates'])) {
            $this->setTemplateElements($elements['templates']);
        }

        if (isset($elements['tvs'])) {
            $this->setTVElements($elements['tvs']);
        }
    }

    protected function setPackage($package)
    {
        if (isset($package['actions'])) {
            $this->setActions($package['actions']);
        }
        if (isset($package['menus'])) {
            $this->setMenus($package['menus']);
        }

        if (isset($package['systemSettings'])) {
            $this->setSettings($package['systemSettings']);
        }

        if (isset($package['elements'])) {
            $this->setElements($package['elements']);
        }

        if (isset($package['resources'])) {
            $this->setResources($package['resources']);
        }
    }

    protected function setExtensionPackage($extensionPackage)
    {
        if (!isset($extensionPackage['serviceName']) && !isset($extensionPackage['serviceClass'])) {
            $this->extensionPackage = true;
        } else if ((!isset($extensionPackage['serviceClass']) && isset($extensionPackage['serviceClass'])) || (isset($extensionPackage['serviceClass']) && !isset($extensionPackage['serviceClass']))) {
            $this->extensionPackage = false;
        } else {
            $this->extensionPackage['serviceName'] = $extensionPackage['serviceName'];
            $this->extensionPackage['serviceClass'] = $extensionPackage['serviceClass'];
        }
    }

    protected function setGlobals($config)
    {
        if (isset($config['name'])) {
            $this->name = $config['name'];
        } else {
            $this->error->addError('Name is not set', true);
        }

        if (isset($config['lowCaseName'])) {
            $this->lowCaseName = $config['lowCaseName'];
        } else {
            $this->error->addError('LowCaseName is not set', true);
        }

        if (isset($config['author'])) {
            $this->author = $config['author'];
        } else {
            $this->error->addError('Author is not set', true);
        }

        if (isset($config['description'])) {
            $this->description = $config['description'];
        } else {
            $this->description = '';
        }

        if (isset($config['version'])) {
            $this->version = $config['version'];
        } else {
            $this->error->addError('Version is not set', true);
        }
    }
}