<?php
namespace GitPackageManagement\Config;

class ElementTemplate extends Element{
    protected $type = 'template';
    protected $extension = 'tpl';
    protected $icon = '';

    public function fromArray($config) {
        if (isset($config['icon'])) {
            $this->icon = $config['icon'];
        }

        return parent::fromArray($config);
    }

    public function getIcon() {
        return $this->icon;
    }
}
