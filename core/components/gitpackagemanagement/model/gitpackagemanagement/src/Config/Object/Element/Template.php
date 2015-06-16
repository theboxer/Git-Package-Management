<?php
namespace GPM\Config\Object\Element;

final class Template extends Element
{
    protected $elementType = 'template';
    protected $extension = 'tpl';
    protected $icon = '';

    protected $section = 'Elements: Template';

    public function getIcon()
    {
        return $this->icon;
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['icon'] = $this->getIcon();
        
        return $array;
    }
}