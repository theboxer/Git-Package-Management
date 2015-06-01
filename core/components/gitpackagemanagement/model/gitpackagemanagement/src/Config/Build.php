<?php
namespace GPM\Config;

class Build extends ConfigObject
{
    /** @var Build\Resolver $resolver */
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
        $this->resolver = new Build\Resolver();

        parent::__construct($config);
    }

    protected function setDefaults($config)
    {
        if (isset($config['schemaPath'])) {
            $this->schemaPath = '/' . ltrim($config['schemaPath'], '/');
        } else {
            $this->schemaPath = '/core/components/' . $this->config->getLowCaseName() . '/' . 'model/schema/' . $this->config->getLowCaseName() . '.mysql.schema.xml';
        }   
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
     * @return Build\Resolver
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