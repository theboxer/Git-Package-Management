<?php

class GitPackageConfigDatabase {
    private $modx;
    private $prefix;
    private $tables;

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['prefix'])){
            $this->prefix = $config['prefix'];
        }else{
            $this->prefix = 'modx_';
        }

        if(isset($config['tables'])){
            $this->tables = $config['tables'];
        }else{
            $this->tables = array();
        }

        return true;
    }

    public function getPrefix() {
        return $this->prefix;
    }

    public function getTables() {
        return $this->tables;
    }

}