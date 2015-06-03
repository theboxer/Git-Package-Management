<?php
namespace GPM\Config;

class Database extends ConfigObject
{
    protected $prefix = 'modx_';
    protected $tables = [];
    protected $simpleObjects = [];
    
    protected $section ='Database';
    protected $validations = ['tables:array', 'simpleObjects:array'];

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getSimpleObjects()
    {
        return $this->simpleObjects;
    }

}