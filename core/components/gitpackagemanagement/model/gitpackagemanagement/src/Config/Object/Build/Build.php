<?php
namespace GPM\Config\Object\Build;

use GPM\Config\Config;
use GPM\Config\ConfigObject;

class Build extends ConfigObject
{
    /** @var Resolver $resolver */
    private $resolver;
    private $readme = 'docs/readme.txt';
    private $license = 'docs/license.txt';
    private $changelog = 'docs/changelog.txt';
    private $schemaPath = '';
    private $setupOptions = [];
    private $attributes = [];
    
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
            $this->schemaPath = '/core/components/' . $this->config->general->getLowCaseName() . '/' . 'model/schema/' . $this->config->general->getLowCaseName() . '.mysql.schema.xml';
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

    /**
     * @return Resolver
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * @return string
     */
    public function getReadme()
    {
        return $this->readme;
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * @return string
     */
    public function getChangeLog()
    {
        return $this->changelog;
    }

    /**
     * @return array
     */
    public function getSetupOptions()
    {
        return $this->setupOptions;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getSchemaPath()
    {
        return $this->schemaPath;
    }

}