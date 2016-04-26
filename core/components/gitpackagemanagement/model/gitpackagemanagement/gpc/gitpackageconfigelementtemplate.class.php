<?php

class GitPackageConfigElementTemplate extends GitPackageConfigElement{
    protected $type = 'template';
    protected $extension = 'tpl';
    protected $icon = '';
    protected $property_preprocess = '';

    public function fromArray($config) {
        if (isset($config['icon'])) {
            $this->icon = $config['icon'];
        }
        if (isset($config['property_preprocess'])) {
            $this->property_preprocess = $config['property_preprocess'];
        }
        return parent::fromArray($config);
    }

    public function getIcon() {
        return $this->icon;
    }
    public function getPropertyPreprocess() {
        return $this->property_preprocess;
    }
}