<?php
namespace GPM\Config\Element;

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
}