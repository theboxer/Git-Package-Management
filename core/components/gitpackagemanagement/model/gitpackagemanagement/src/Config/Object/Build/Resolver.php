<?php
namespace GPM\Config\Object\Build;

use GPM\Config\ConfigObject;

final class Resolver extends ConfigObject
{
    public $resolversDir = 'resolvers';
    public $before = [];
    public $after = [];
    public $files = [];

    protected $rules = [
        'before' => 'type:array',
        'after' => 'type:array',
        'files' => 'type:array',
    ];

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