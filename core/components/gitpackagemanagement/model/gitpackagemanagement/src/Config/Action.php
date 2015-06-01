<?php
namespace GPM\Config;

class Action extends ConfigObject
{
    protected $id;
    protected $controller;
    protected $hasLayout = 1;
    protected $langTopics;
    protected $assets = '';
    
    protected $section = 'Actions';
    protected $validations = ['id', 'controller'];

    protected function setDefaults($config)
    {
        if (!isset($config['langTopics'])) {
            $this->langTopics = $this->config->getLowCaseName() . ':default';
        }        
    }

    public function getAssets()
    {
        return $this->assets;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getHasLayout()
    {
        return $this->hasLayout;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLangTopics()
    {
        return $this->langTopics;
    }


}