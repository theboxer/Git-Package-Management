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
    public $permissions = '';
    /** @var Action|string */
    public $action;

    protected $rules = [
        'text' => 'notEmpty',
        'action' => 'notEmpty'
    ];

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
            'action' => ($this->action instanceof Action)? $this->action->id : $this->action,
            'permissions' => $this->permissions
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

    public function getObject($build = false)
    {
        /** @var \modMenu $object */
        $object = $this->config->modx->newObject('modMenu');
        $object->set('text', $this->text);
        $object->set('parent', $this->parent);
        $object->set('description', $this->description);
        $object->set('icon', $this->icon);
        $object->set('menuindex', $this->menuIndex);
        $object->set('params', $this->params);
        $object->set('handler', $this->handler);
        $object->set('permissions', $this->permissions);

        if (!($this->action instanceof Action)) {
            $object->set('action', $this->action);
            $object->set('namespace', $this->config->general->lowCaseName);
        }
        
        return $object;
    }
}
