<?php
namespace GPM\Config\Object\Build;

use GPM\Config\Config;
use GPM\Config\ConfigObject;

class Build extends ConfigObject
{
    /** @var Resolver $resolver */
    public $resolver;
    public $readme = 'docs/readme.txt';
    public $license = 'docs/license.txt';
    public $changelog = 'docs/changelog.txt';
    public $schemaPath = '';
    public $setupOptions = [];
    public $attributes = [];
    
    protected $section = 'Build';
    protected $validators = ['attributes:array'];

    public function __construct(Config $config)
    {
        $this->resolver = new Resolver($config);

        parent::__construct($config);
    }

    protected function setDefaults($config)
    {
        if (isset($config['schemaPath'])) {
            $this->schemaPath = '/' . ltrim($config['schemaPath'], '/');
        } else {
            $this->schemaPath = '/core/components/' . $this->config->general->lowCaseName . '/' . 'model/schema/' . $this->config->general->lowCaseName . '.mysql.schema.xml';
        }   
    }

    public function toArray()
    {
        // @TODO
        return [];
    }

    public function setResolver($resolver)
    {
        $this->resolver->fromArray($resolver);
    }

    public function setAttributes($attributes)
    {
        foreach ($attributes as $key => $attrs) {
            if (is_array($attrs)) {
                $this->attributes[$key] = $attrs;
            }
        }
    }
}