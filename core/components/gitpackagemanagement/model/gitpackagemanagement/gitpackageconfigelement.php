<?php

abstract class GitPackageConfigElement{
    /** @var modX $modx */
    protected $modx;
    /** @var GitPackageConfig $config */
    protected $config;
    /** @var string $name */
    protected $name;
    /** @var string $file */
    protected $file;
    /** @var string $type */
    protected $type;
    /** @var string $extension */
    protected $extension;

    public function __construct(modX &$modx, GitPackageConfig $config) {
        $this->modx =& $modx;
        $this->config = $config;
    }

    public function fromArray($config) {
        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Elements: '.$this->type.' - name is not set');
            return false;
        }

        if(isset($config['file'])){
            $this->file = $config['file'];
        }else{
            $this->file = strtolower($this->name).'.'.$this->type . '.' . $this->extension;
        }

        if ($this->checkFile() == false) {
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Elements: '.$this->file.' - file does not exists');
            return false;
        }

        return true;
    }

    protected function checkFile() {
        $file = $this->config->getPackagePath();
        $file .= '/core/components/'.$this->config->getLowCaseName().'/elements/' . $this->type . 's/' . $this->file;

        if(!file_exists($file)){
            return false;
        }

        return true;
    }

    public function getFile() {
        return $this->file;
    }

    public function getName() {
        return $this->name;
    }
}