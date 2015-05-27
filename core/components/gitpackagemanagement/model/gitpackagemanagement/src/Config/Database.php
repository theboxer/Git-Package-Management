<?php
namespace GPM\Config;

class Database
{
    protected $prefix = 'modx_';
    protected $tables = [];
    protected $simpleObjects = [];

    public function fromArray($config)
    {
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }

        if (isset($config['tables'])) {
            $this->tables = $config['tables'];
        }

        if (isset($config['simpleObjects'])) {
            $this->simpleObjects = $config['simpleObjects'];
        }

        return true;
    }

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