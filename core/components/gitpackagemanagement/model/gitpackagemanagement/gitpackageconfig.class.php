<?php

require_once 'gitpackageconfigaction.class.php';
require_once 'gitpackageconfigmenu.class.php';
require_once 'gitpackageconfigsetting.class.php';
require_once 'gitpackageconfigdatabase.class.php';
require_once 'gitpackageconfigelementplugin.class.php';


class GitPackageConfig {
    private $modx;

    private $name = null;
    private $lowCaseName = null;
    private $author = null;
    private $version = null;
    private $description = null;
    private $actions = array();
    private $menus = array();
    private $settings = array();
    private $database = null;
    private $extensionPackage = null;
    private $elements = array('plugins' => array());

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

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
            }
        }

        if(isset($config['database'])){
            if($this->setDatabase($config['database']) == false){
                return false;
            }
        }

        if(isset($config['extensionPackage'])){
            if(!isset($config['extensionPackage']['serviceName'])){
                $this->extensionPackage = false;
            }else if(!isset($config['extensionPackage']['serviceClass'])){
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

    private function setPluginElements($plugins){
        foreach ($plugins as $plugin){
            $p = new GitPackageConfigElementPlugin($this->modx, $this);
            if($p->fromArray($plugin) == false) return false;
            $this->elements['plugins'][] = $p;
        }

        return true;
    }

    private function setActions($config) {
        foreach ($config as $action){
            $a = new GitPackageConfigAction($this->modx, $this);
            if($a->fromArray($action) == false) return false;
            $this->actions[] = $a;
        }

        return true;
    }

    private function setMenus($config) {
        foreach ($config as $menu){
            $m = new GitPackageConfigMenu($this->modx, $this);
            if($m->fromArray($menu) == false) return false;
            $this->menus[] = $m;
        }

        return true;
    }

    private function setSettings($config) {
        foreach ($config as $setting){
            $s = new GitPackageConfigSetting($this->modx);
            if($s->fromArray($setting) == false) return false;
            $this->settings[] = $s;
        }

        return true;
    }

    private function setDatabase($config) {
        $this->database = new GitPackageConfigDatabase($this->modx);
        if($this->database->fromArray($config) == false) return false;

        return true;
    }

    public function getActions() {
        return $this->actions;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function getLowCaseName() {
        return $this->lowCaseName;
    }

    public function getMenus() {
        return $this->menus;
    }

    public function getName() {
        return $this->name;
    }

    public function getSettings() {
        return $this->settings;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getDescription() {
        return $this->description;
    }

    /**
     * @return GitPackageConfigDatabase
     */
    public function getDatabase() {
        return $this->database;
    }

    public function getExtensionPackage() {
        return $this->extensionPackage;
    }

    public function getElements($type = null) {
        if($type){
            return $this->elements[$type];
        }

        return $this->elements;
    }
}