<?php

require_once 'gitpackageconfigelement.php';
require_once 'gitpackageconfigaction.class.php';
require_once 'gitpackageconfigmenu.class.php';
require_once 'gitpackageconfigsetting.class.php';
require_once 'gitpackageconfigdatabase.class.php';
require_once 'gitpackageconfigelementplugin.class.php';
require_once 'gitpackageconfigelementchunk.class.php';
require_once 'gitpackageconfigelementsnippet.class.php';
require_once 'gitpackageconfigelementtemplate.class.php';
require_once 'gitpackageconfigelementtv.class.php';


class GitPackageConfig {
    /** @var modX $modx */
    private $modx;
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

    /**
     * @param modX $modx
     * @param string $packagePath
     */
    public function __construct(modX &$modx, $packagePath) {
        $this->modx =& $modx;
        $this->packagePath = $packagePath;
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
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Name is not set');
            return false;
        }

        if(isset($config['lowCaseName'])){
            $this->lowCaseName = $config['lowCaseName'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] LowCaseName is not set');
            return false;
        }

        if(isset($config['author'])){
            $this->author = $config['author'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Author is not set');
            return false;
        }

        if(isset($config['description'])){
            $this->description = $config['description'];
        }else{
            $this->description = '';
        }

        if(isset($config['version'])){
            $this->version = $config['version'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Version is not set');
            return false;
        }

        if(isset($config['package'])){
            if(isset($config['package']['actions'])){
                if($this->setActions($config['package']['actions']) == false){
                    return false;
                }
            }
            if(isset($config['package']['menus'])){
                if($this->setMenus($config['package']['menus']) == false){
                    return false;
                }
            }

            if(isset($config['package']['systemSettings'])){
                if($this->setSettings($config['package']['systemSettings']) == false){
                    return false;
                }
            }

            if(isset($config['package']['elements'])){
                if(isset($config['package']['elements']['plugins'])){
                    if($this->setPluginElements($config['package']['elements']['plugins']) == false){
                        return false;
                    }
                }

                if(isset($config['package']['elements']['snippets'])){
                    if($this->setSnippetElements($config['package']['elements']['snippets']) == false){
                        return false;
                    }
                }

                if(isset($config['package']['elements']['chunks'])){
                    if($this->setChunkElements($config['package']['elements']['chunks']) == false){
                        return false;
                    }
                }

                if(isset($config['package']['elements']['templates'])){
                    if($this->setTemplateElements($config['package']['elements']['templates']) == false){
                        return false;
                    }
                }

                if(isset($config['package']['elements']['tvs'])){
                    if($this->setTVElements($config['package']['elements']['tvs']) == false){
                        return false;
                    }
                }
            }
        }

        if(isset($config['database'])){
            if($this->setDatabase($config['database']) == false){
                return false;
            }
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

        return true;
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
}