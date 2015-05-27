<?php
namespace GPM\Config;

use GPM\Util\Validator;

class Menu
{
    use Validator;
    
    /* @var $config Config */
    protected $config;
    protected $text;
    protected $description = '';
    protected $parent = 'components';
    protected $icon = '';
    protected $menuIndex = 0;
    protected $params = '';
    protected $handler = '';
    protected $action;
    /** @var Action $action */
    protected $actionObject = null;
    
    protected $section = 'Menus';
    protected $required = ['text', 'action'];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function fromArray($config)
    {
        $this->validate($config);
        
        $this->text = $config['text'];

        if (isset($config['description'])) {
            $this->description = $config['description'];
        }

        if (isset($config['parent'])) {
            $this->parent = $config['parent'];
        }

        if (isset($config['icon'])) {
            $this->icon = $config['icon'];
        }

        if (isset($config['menuIndex'])) {
            $this->menuIndex = $config['menuIndex'];
        }

        if (isset($config['params'])) {
            $this->params = $config['params'];
        }

        if (isset($config['handler'])) {
            $this->handler = $config['handler'];
        }
        
        $this->setAction($config['action']);

        return true;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getMenuIndex()
    {
        return $this->menuIndex;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setAction($givenAction)
    {

        if (is_string($givenAction)) {
            $this->action = $givenAction;

            return true;
        }

        foreach ($this->config->getActions() as $action) {
            if ($action->getId() != $givenAction) continue;
            $this->action = $givenAction;
            $this->actionObject = $action;

            return true;
        }

        throw new \Exception('Menus - action not exist');
    }

    /**
     * @return Action|null
     */
    public function getActionObject()
    {
        return $this->actionObject;
    }
}
