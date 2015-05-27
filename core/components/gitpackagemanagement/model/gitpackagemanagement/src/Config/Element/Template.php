<?php
namespace GPM\Config\Element;

final class Template extends Element
{
    protected $type = 'template';
    protected $extension = 'tpl';
    protected $icon = '';

    protected $section = 'Elements: Template';

    public function fromArray($config)
    {
        if (isset($config['icon'])) {
            $this->icon = $config['icon'];
        }

        return parent::fromArray($config);
    }

    public function getIcon()
    {
        return $this->icon;
    }
}