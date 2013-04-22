<?php

class GitPackageConfigAction {
    private $modx;
    /* @var $gitPackageConfig GitPackageConfig */
    private $gitPackageConfig;
    private $id;
    private $controller;
    private $hasLayout;
    private $langTopics;
    private $assets;

    public function __construct(modX &$modx, $gitPackageConfig) {
        $this->modx =& $modx;
        $this->gitPackageConfig = $gitPackageConfig;
    }

    public function fromArray($config) {
        if(isset($config['id'])){
            $this->id = $config['id'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Actions - id is not set');
            return false;
        }

        if(isset($config['controller'])){
            $this->controller = $config['controller'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Actions - controller is not set');
            return false;
        }

        if(isset($config['hasLayout'])){
            $this->hasLayout = $config['hasLayout'];
        }else{
            $this->hasLayout = 1;
        }

        if(isset($config['langTopics'])){
            $this->langTopics = $config['langTopics'];
        }else{
            $this->langTopics = $this->gitPackageConfig->getLowCaseName().':default';
        }

        if(isset($config['assets'])){
            $this->assets = $config['assets'];
        }else{
            $this->assets = '';
        }

        return true;
    }

    public function getAssets() {
        return $this->assets;
    }

    public function getController() {
        return $this->controller;
    }

    public function getHasLayout() {
        return $this->hasLayout;
    }

    public function getId() {
        return $this->id;
    }

    public function getLangTopics() {
        return $this->langTopics;
    }


}

class GitPackageConfigMenu {
    private $modx;
    /* @var $gitPackageConfig GitPackageConfig */
    private $gitPackageConfig;
    private $text;
    private $description;
    private $parent;
    private $icon;
    private $menuIndex;
    private $params;
    private $handler;
    private $action;

    public function __construct(modX &$modx, $gitPackageConfig) {
        $this->modx =& $modx;
        $this->gitPackageConfig = $gitPackageConfig;
    }

    public function fromArray($config) {
        if(isset($config['text'])){
            $this->text = $config['text'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Menus - text is not set');
            return false;
        }

        if(isset($config['description'])){
            $this->description = $config['description'];
        }else{
            $this->description = '';
        }

        if(isset($config['parent'])){
            $this->parent = $config['parent'];
        }else{
            $this->parent = 'components';
        }

        if(isset($config['icon'])){
            $this->icon = $config['icon'];
        }else{
            $this->icon = '';
        }

        if(isset($config['menuIndex'])){
            $this->menuIndex = $config['menuIndex'];
        }else{
            $this->menuIndex = 0;
        }

        if(isset($config['params'])){
            $this->params = $config['params'];
        }else{
            $this->params = '';
        }

        if(isset($config['handler'])){
            $this->handler = $config['handler'];
        }else{
            $this->handler = '';
        }

        if(isset($config['action'])){
            /** @var $action GitPackageConfigAction **/
            foreach($this->gitPackageConfig->getActions() as $action){
                if($action->getId() == $config['action']) break;
                $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Menus - action not exist');
                return false;
            }
            $this->action = $config['action'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Menus - action is not set');
            return false;
        }

        return true;
    }

    public function getAction() {
        return $this->action;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getHandler() {
        return $this->handler;
    }

    public function getIcon() {
        return $this->icon;
    }

    public function getMenuIndex() {
        return $this->menuIndex;
    }

    public function getParams() {
        return $this->params;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getText() {
        return $this->text;
    }

}

class GitPackageConfigSetting {
    private $modx;
    private $key;
    private $type;
    private $area;
    private $value;

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['key'])){
            $this->key = $config['key'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Settings - key is not set');
            return false;
        }

        if(isset($config['type'])){
            $this->type = $config['type'];
        }else{
            $this->type = 'textfield';
        }

        if(isset($config['area'])){
            $this->area = $config['area'];
        }else{
            $this->area = 'default';
        }

        if(isset($config['value'])){
            $this->value = $config['value'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Settings - value is not set');
            return false;
        }

        return true;
    }

    public function getArea() {
        return $this->area;
    }

    public function getKey() {
        return $this->key;
    }

    public function getType() {
        return $this->type;
    }

    public function getValue() {
        return $this->value;
    }

}

class GitPackageConfigDatabase {
    private $modx;
    private $prefix;
    private $tables;

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['prefix'])){
            $this->prefix = $config['prefix'];
        }else{
            $this->prefix = '';
        }

        if(isset($config['tables'])){
            $this->tables = $config['tables'];
        }else{
            $this->tables = array();
        }

        return true;
    }

    public function getPrefix() {
        return $this->prefix;
    }

    public function getTables() {
        return $this->tables;
    }

}

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
}