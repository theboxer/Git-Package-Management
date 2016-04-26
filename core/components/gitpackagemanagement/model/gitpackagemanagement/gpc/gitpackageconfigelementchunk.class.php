<?php

class GitPackageConfigElementChunk extends GitPackageConfigElement{
    protected $type = 'chunk';
    protected $extension = 'tpl';
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