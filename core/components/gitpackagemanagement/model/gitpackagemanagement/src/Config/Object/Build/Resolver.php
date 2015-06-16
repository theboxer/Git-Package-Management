<?php
namespace GPM\Config\Object\Build;

use GPM\Config\ConfigObject;

final class Resolver extends ConfigObject
{
    protected $resolversDir = 'resolvers';
    protected $before = [];
    protected $after = [];
    protected $files = [];
    
    protected $section = 'Resolved';
    protected $validations = ['before:array', 'after:array', 'files:array'];

    public function toArray()
    {
        // @TODO
        return [];
    }
    
    public function setFiles($files)
    {
        foreach ($files as $file) {
            if (!isset($file['source']) || !isset($file['target'])) continue;

            $this->files[] = [
                'source' => $file['source'],
                'target' => $file['target']
            ];
        }
    }

    /**
     * @return string
     */
    public function getResolversDir()
    {
        return $this->resolversDir;
    }

    public function getFileResolvers()
    {
        return $this->files;
    }

    /**
     * @return array
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @return array
     */
    public function getAfter()
    {
        return $this->after;
    }
}