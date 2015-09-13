<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Action extends ConfigObject
{
    public $id;
    public $controller;
    public $hasLayout = 1;
    public $langTopics;
    public $assets = '';
    
    protected $section = 'Actions';
    protected $validations = ['id', 'controller'];

    protected function setDefaults($config)
    {
        if (!isset($config['langTopics'])) {
            $this->langTopics = $this->config->general->lowCaseName . ':default';
        }        
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'controller' => $this->controller,
            'hasLayout' => $this->hasLayout,
            'langTopics' => $this->langTopics,
            'assets' => $this->assets
        ];
    }
}