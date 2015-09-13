<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Menu extends ConfigObject
{
    public $text;
    public $description = '';
    public $parent = 'components';
    public $icon = '';
    public $menuIndex = 0;
    public $params = '';
    public $handler = '';
    /** @var Action|string */
    public $action;
    
    protected $section = 'Menus';
    protected $validations = ['text', 'action'];

    public function toArray()
    {
        return [
            'text' => $this->text,
            'description' => $this->description,
            'parent' => $this->parent,
            'icon' => $this->icon,
            'menuIndex' => $this->menuIndex,
            'params' => $this->params,
            'handler' => $this->handler,
            'action' => ($this->action instanceof Action)? $this->action->id : $this->action
        ];
    }

    public function setAction($givenAction)
    {

        if (is_string($givenAction)) {
            $this->action = $givenAction;

            return true;
        }

        foreach ($this->config->actions as $action) {
            if ($action->id != $givenAction) continue;
            $this->action = $action;

            return true;
        }

        throw new \Exception('Menus - action not exist');
    }
}
