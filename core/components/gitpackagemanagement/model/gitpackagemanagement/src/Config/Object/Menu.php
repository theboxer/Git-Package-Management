<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Menu extends ConfigObject
{
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
    protected $validations = ['text', 'action'];

    public function toArray()
    {
        return [
            'text' => $this->getText(),
            'description' => $this->getDescription(),
            'parent' => $this->getParent(),
            'icon' => $this->getIcon(),
            'menuIndex' => $this->getMenuIndex(),
            'params' => $this->getParams(),
            'handler' => $this->getHandler(),
            'action' => $this->getAction()
        ];
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
