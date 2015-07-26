<?php

class GitPackageConfigElementWidget extends GitPackageConfigElement{
    protected $type = 'widget';
    protected $extension = 'php';
    protected $widgettype = '';
    protected $lexicon = '';
    protected $size = '';

    public function fromArray($config) {
        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->config->error->addError('Elements: ' . $this->type . ' - name is not set', true);
            return false;
        }

        if (isset($config['description'])) {
            $this->description = $config['description'];
        }

        if(isset($config['type'])){
            $this->widgettype = $config['type'];
        }else{
            $this->widgettype = 'file';
        }

        if ($this->widgettype == 'file') {
            if (isset($config['content'])) {
                $this->file = $config['content'];
            } else {
                $this->file = strtolower($this->name) . '.' . $this->type . '.' . $this->extension;
            }
            if ($this->checkFile() == false) {
                return false;
            }
        } else {
            $this->file = $config['content'];
        }

        if(isset($config['namespace'])){
            $this->namespace = $config['namespace'];
        }else{
            $this->namespace = $this->config->getLowCaseName();
        }

        if(isset($config['lexicon'])){
            $this->lexicon = $config['lexicon'];
        } else {
            $this->lexicon = $this->config->getLowCaseName() . ':default';
        }

        if(isset($config['size'])){
            $this->size = $config['size'];
        } else {
            $this->size = 'half';
        }

        return true;
    }

    /**
     * @return string
     */
    public function getWidgetType() {
        return $this->widgettype;
    }

    /**
     * @return string
     */
    public function getLexicon() {
        return $this->lexicon;
    }

    /**
     * @return string
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getPackagePath() {
        return $this->config->getPackagePath();
    }

}