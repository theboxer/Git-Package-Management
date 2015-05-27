<?php
namespace GPM\Config;

class Database
{
    private $modx;
    private $prefix;
    private $tables;
    private $simpleObjects;

    public function __construct(\modX &$modx)
    {
        $this->modx =& $modx;
    }

    public function fromArray($config)
    {
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        } else {
            $this->prefix = 'modx_';
        }

        if (isset($config['tables'])) {
            $this->tables = $config['tables'];
        } else {
            $this->tables = array();
        }

        if (isset($config['simpleObjects'])) {
            $this->simpleObjects = $config['simpleObjects'];
        } else {
            $this->simpleObjects = array();
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