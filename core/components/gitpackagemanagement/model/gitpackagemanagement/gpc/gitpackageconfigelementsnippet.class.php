<?php

class GitPackageConfigElementSnippet extends GitPackageConfigElement{
    protected $type = 'snippet';
    protected $extension = 'php';
    protected $property_preprocess = '';
    
    public function fromArray($config) {
        if (isset($config['property_preprocess'])) {
            $this->property_preprocess = $config['property_preprocess'];
        }
        return parent::fromArray($config);
    }
    
    public function getPropertyPreprocess() {
        return $this->property_preprocess;
    }
}