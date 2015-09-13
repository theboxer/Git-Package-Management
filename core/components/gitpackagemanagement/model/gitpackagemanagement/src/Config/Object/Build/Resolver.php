<?php
namespace GPM\Config\Object\Build;

use GPM\Config\ConfigObject;

final class Resolver extends ConfigObject
{
    public $resolversDir = 'resolvers';
    public $before = [];
    public $after = [];
    public $files = [];
    
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
}