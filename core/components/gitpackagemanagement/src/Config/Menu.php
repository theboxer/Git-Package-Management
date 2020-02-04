<?php
namespace GitPackageManagement\Config;

use MODX\Revolution\modX;

class Menu {
    private $modx;
    /* @var $gitPackageConfig Config */
    private $gitPackageConfig;
    private $text;
    private $description;
    private $parent;
    private $icon;
    private $menuIndex;
    private $params;
    private $handler;
    private $action;
    private $permissions;
    private $namespace;

    public function __construct(modX &$modx, $gitPackageConfig) {
        $this->modx =& $modx;
        $this->gitPackageConfig = $gitPackageConfig;
    }

    public function fromArray($config) {
        if(isset($config['text'])){
            $this->text = $config['text'];
        }else{
            $this->gitPackageConfig->error->addError('Menus - text is not set', true);
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

        if(isset($config['permissions'])){
            $this->permissions = $config['permissions'];
        }else{
            $this->permissions = '';
        }

        if(!empty($config['namespace'])){
            $this->namespace = $config['namespace'];
        }else{
            $this->namespace = $this->gitPackageConfig->getLowCaseName();
        }

        if(isset($config['action'])){
            $this->action = $config['action'];
        }else{
            $this->gitPackageConfig->error->addError('Menus - action is not set', true);
            return false;
        }

        return true;
    }

    public function getPermissions()
    {
        return $this->permissions;
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

    public function getNamespace()
    {
        return $this->namespace;
    }
}
