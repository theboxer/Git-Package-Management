<?php
class GitPackageConfig {
    /** @var modX $modx */
    private $modx;
    /** @var GitPackageManagement $gpm */
    private $gpm;
    /** @var string $packagePath */
    private $packagePath;
    /** @var string $name Package name  */
    private $name = null;
    /** @var string $lowCaseName Package low case name */
    private $lowCaseName = null;
    /** @var string $author Package author */
    private $author = null;
    /** @var string $version Package current version */
    private $version = null;
    /** @var string $description Package description */
    private $description = null;
    /** @var GitPackageConfigAction[] $action Array with package's actions */
    private $actions = array();
    /** @var GitPackageConfigMenu[] $menus Array with package's menus */
    private $menus = array();
    /** @var GitPackageConfigSetting[] $settings Array with package's settings */
    private $settings = array();
    /** @var GitPackageConfigDatabase $database Object with package's database information */
    private $database = null;
    /** @var array $extensionPackage Array with extensionPackage information */
    private $extensionPackage = null;
    /** @var array $elements Array with all elements */
    private $elements = array('plugins' => array(), 'snippets' => array(), 'chunks' => array(), 'templates' => array(), 'tvs' => array());
    /** @var GitPackageConfigResource[] $resources */
    private $resources = array();
    private $dependencies = array();
    /** @var GitPackageConfigBuild $build */
    private $build = null;
    /** @var GitPackageConfigCategory[] $categories */
    private $categories = array();
    /** @var GitPackageError $error */
    public $error;

    /**
     * @param modX $modx
     * @param string $packagePath
     */
    public function __construct(modX &$modx, $packagePath) {
        $this->modx =& $modx;
        $this->gpm =& $this->modx->gitpackagemanagement;
        $this->packagePath = $packagePath;

        $this->modx->loadClass('GitPackageConfigAction', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigMenu', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigSetting', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigDatabase', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigElement', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigElementPlugin', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigElementChunk', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigElementTemplate', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigElementTV', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigElementSnippet', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigResource', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigBuild', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigBuildResolver', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);
        $this->modx->loadClass('GitPackageConfigCategory', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpc/', true, true);

        $this->modx->loadClass('GitPackageError', $this->gpm->getOption('modelPath') . 'gitpackagemanagement/gpe/', true, true);

        $this->error = new GitPackageError($this->modx);
    }

    /**
     * Parse and validate given array into objects
     * @param $config Array
     * @return bool
     */
    public function parseConfig($config) {
        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->error->addError('Name is not set', true);
        }

        if(isset($config['lowCaseName'])){
            $this->lowCaseName = $config['lowCaseName'];
        }else{
            $this->error->addError('LowCaseName is not set', true);
        }

        if(isset($config['author'])){
            $this->author = $config['author'];
        }else{
            $this->error->addError('Author is not set', true);
        }

        if(isset($config['description'])){
            $this->description = $config['description'];
        }else{
            $this->description = '';
        }

        if(isset($config['version'])){
            $this->version = $config['version'];
        }else{
            $this->error->addError('Version is not set', true);
        }

        if ($this->error->hasErrors()) return false;

        if(isset($config['package'])){
            if(isset($config['package']['actions'])){
                $this->setActions($config['package']['actions']);
            }
            if(isset($config['package']['menus'])){
                $this->setMenus($config['package']['menus']);
            }

            if(isset($config['package']['systemSettings'])){
                $this->setSettings($config['package']['systemSettings']);
            }

            if(isset($config['package']['elements'])){
                if(isset($config['package']['elements']['categories'])){
                    $this->setCategories($config['package']['elements']['categories']);
                }

                if(isset($config['package']['elements']['plugins'])){
                    $this->setPluginElements($config['package']['elements']['plugins']);
                }

                if(isset($config['package']['elements']['snippets'])){
                    $this->setSnippetElements($config['package']['elements']['snippets']);
                }

                if(isset($config['package']['elements']['chunks'])){
                    $this->setChunkElements($config['package']['elements']['chunks']);
                }

                if(isset($config['package']['elements']['templates'])){
                    $this->setTemplateElements($config['package']['elements']['templates']);
                }

                if(isset($config['package']['elements']['tvs'])){
                    $this->setTVElements($config['package']['elements']['tvs']);
                }
            }

            if(isset($config['package']['resources'])){
                $this->setResources($config['package']['resources']);
            }
        }

        if(isset($config['database'])){
            $this->setDatabase($config['database']);
        }

        $this->setBuild($config);

        if (isset($config['dependencies'])){
            $this->dependencies = $config['dependencies'];
        }

        if(isset($config['extensionPackage'])){
            if(!isset($config['extensionPackage']['serviceName']) && !isset($config['extensionPackage']['serviceClass'])){
                $this->extensionPackage = true;
            }else if((!isset($config['extensionPackage']['serviceClass']) && isset($config['extensionPackage']['serviceClass'])) || (isset($config['extensionPackage']['serviceClass']) && !isset($config['extensionPackage']['serviceClass']))){
                $this->extensionPackage = false;
            }else{
                $this->extensionPackage['serviceName'] = $config['extensionPackage']['serviceName'];
                $this->extensionPackage['serviceClass'] = $config['extensionPackage']['serviceClass'];
            }
        }else{
            $this->extensionPackage = false;
        }

        return !$this->error->hasErrors();
    }

    /**
     * Parse and validate plugins array
     * @param $plugins Array
     * @return bool
     */
    private function setPluginElements($plugins){
        foreach ($plugins as $plugin){
            $p = new GitPackageConfigElementPlugin($this->modx, $this);
            if($p->fromArray($plugin) == false) return false;
            $this->elements['plugins'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate categories array
     * @param $categories Array
     * @return bool
     */
    private function setCategories($categories){
        foreach ($categories as $category){
            $c = new GitPackageConfigCategory($this->modx, $this);
            if($c->fromArray($category) == false) return false;
            $this->categories[$c->getName()] = $c;
        }

        return true;
    }

    /**
     * Parse and validate snippets array
     * @param $snippets Array
     * @return bool
     */
    private function setSnippetElements($snippets){
        foreach ($snippets as $snippet){
            $p = new GitPackageConfigElementSnippet($this->modx, $this);
            if($p->fromArray($snippet) == false) return false;
            $this->elements['snippets'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate chunks array
     * @param $chunks Array
     * @return bool
     */
    private function setChunkElements($chunks){
        foreach ($chunks as $chunk){
            $p = new GitPackageConfigElementChunk($this->modx, $this);
            if($p->fromArray($chunk) == false) return false;
            $this->elements['chunks'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate templates array
     * @param $templates Array
     * @return bool
     */
    private function setTemplateElements($templates){
        foreach ($templates as $template){
            $p = new GitPackageConfigElementTemplate($this->modx, $this);
            if($p->fromArray($template) == false) return false;
            $this->elements['templates'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate TVs array
     * @param $tvs Array
     * @return bool
     */
    private function setTVElements($tvs){
        foreach ($tvs as $tv){
            $p = new GitPackageConfigElementTV($this->modx, $this);
            if($p->fromArray($tv) == false) return false;
            $this->elements['tvs'][$p->getName()] = $p;
        }

        return true;
    }

    /**
     * Parse and validate actions
     * @param $actions Array
     * @return bool
     */
    private function setActions($actions) {
        foreach ($actions as $action){
            $a = new GitPackageConfigAction($this->modx, $this);
            if($a->fromArray($action) == false) return false;
            $this->actions[] = $a;
        }

        return true;
    }

    /**
     * Parse and validate menus
     * @param $menus Array
     * @return bool
     */
    private function setMenus($menus) {
        foreach ($menus as $menu){
            $m = new GitPackageConfigMenu($this->modx, $this);
            if($m->fromArray($menu) == false) return false;
            $this->menus[] = $m;
        }

        return true;
    }

    /**
     * Parse and validate settings
     * @param $settings
     * @return bool
     */
    private function setSettings($settings) {
        foreach ($settings as $setting){
            $s = new GitPackageConfigSetting($this->modx, $this);
            if($s->fromArray($setting) == false) return false;
            $this->settings[$s->getNamespacedKey()] = $s;
        }

        return true;
    }

    /**
     * Parse and validate database information
     * @param $database
     * @return bool
     */
    private function setDatabase($database) {
        $this->database = new GitPackageConfigDatabase($this->modx);
        if($this->database->fromArray($database) == false) return false;

        return true;
    }

    /**
     * Set build options
     * @return bool
     */
    private function setBuild($config) {
        $build = isset($config['build']) ? $config['build'] : array();
        $this->build = new GitPackageConfigBuild($this->modx);
        if($this->build->fromArray($build) == false) return false;

        return true;
    }

    /**
     * Parse and validate resources array
     * @param $resources Array
     * @return bool
     */
    private function setResources($resources){
        foreach ($resources as $resource){
            $p = new GitPackageConfigResource($this->modx, $this);
            if($p->fromArray($resource) == false) return false;
            $this->resources[] = $p;
        }

        return true;
    }

    /**
     * Returns array of GitPackageConfigAction objects
     * @return GitPackageConfigAction[]
     */
    public function getActions() {
        return $this->actions;
    }

    /**
     * Returns author of package
     * @return string
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * Returns low case name of package
     * @return string
     */
    public function getLowCaseName() {
        return $this->lowCaseName;
    }

    /**
     * Returns array of GitPackageConfigMenu objects
     * @return GitPackageConfigMenu[]
     */
    public function getMenus() {
        return $this->menus;
    }

    /**
     * Returns name of package
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns array of GitPackageConfigSetting objects
     * @return GitPackageConfigSetting[]
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * Returns version of package
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Returns description of package
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Returns information about database
     * @return GitPackageConfigDatabase
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * Returns information about extension package
     * @return array|null
     */
    public function getExtensionPackage() {
        return $this->extensionPackage;
    }

    /**
     * Return array with all elements, or only with elements of type $type
     * @param string $type (default is null)
     * @return GitPackageConfigElement[]
     */
    public function getElements($type = null) {
        if($type){
            return $this->elements[$type];
        }

        return $this->elements;
    }

    /**
     * @return string
     */
    public function getPackagePath() {
        return $this->packagePath;
    }

    /**
     * Return array with all resources
     *
     * @return GitPackageConfigResource[]
     */
    public function getResources() {
        return $this->resources;
    }

    public function getAssetsFolder() {
        $assetsFolder = $this->modx->gitpackagemanagement->getOption('assetsPath');

        if (!is_dir($assetsFolder . 'packages')) {
            mkdir($assetsFolder . 'packages');
        }

        $assetsFolder .= 'packages/';

        if (!is_dir($assetsFolder . $this->getLowCaseName())) {
            mkdir($assetsFolder . $this->getLowCaseName());
        }

        return $assetsFolder . $this->getLowCaseName() . '/';
    }

    public function checkDependencies() {
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
     * @return GitPackageConfigBuild
     */
    public function getBuild() {
        return $this->build;
    }

    /**
     * @return GitPackageConfigCategory[]
     */
    public function getCategories()
    {
        return $this->categories;
    }
}