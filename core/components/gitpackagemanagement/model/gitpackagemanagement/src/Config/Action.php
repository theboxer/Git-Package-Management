<?php
namespace GPM\Config;

use GPM\Util\Validator;
class Action
{
    use Validator;
    
    /* @var $config Config */
    protected $config;
    protected $id;
    protected $controller;
    protected $hasLayout = 1;
    protected $langTopics;
    protected $assets = '';
    
    protected $section = 'Actions';
    protected $required = ['id', 'controller'];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function fromArray($config)
    {
        $this->validate($config);
        
        $this->id = $config['id'];
        $this->controller = $config['controller'];

        if (isset($config['hasLayout'])) {
            $this->hasLayout = $config['hasLayout'];
        }

        if (isset($config['langTopics'])) {
            $this->langTopics = $config['langTopics'];
        } else {
            $this->langTopics = $this->config->getLowCaseName() . ':default';
        }

        if (isset($config['assets'])) {
            $this->assets = $config['assets'];
        }

        return true;
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