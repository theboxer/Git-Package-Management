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
    
    protected $rules = [
        'id' => 'notEmpty',
        'controller' => 'notEmpty'
    ];

    protected function setDefaults($config)
    {
        if (empty($config['langTopics'])) {
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

    public function getObject($build = false)
    {
        /** @var \modAction $object */
        $object = $this->config->modx->newObject('modAction');
        $object->set('namespace', $this->config->general->lowCaseName);
        $object->set('controller', $this->controller);
        $object->set('haslayout', $this->hasLayout);
        $object->set('lang_topics', $this->langTopics);
        $object->set('assets', $this->assets);
        
        return $object;
    }
}