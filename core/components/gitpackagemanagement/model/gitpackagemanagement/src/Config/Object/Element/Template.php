<?php
namespace GPM\Config\Object\Element;

final class Template extends Element
{
    public $icon = '';
    
    protected $elementType = 'template';
    protected $extension = 'tpl';

    public function toArray()
    {
        $array = parent::toArray();

        $array['icon'] = $this->icon;
        
        return $array;
    }
}