<?php

class GitPackageConfigBuild {
    private $modx;
    /** @var GitPackageConfig $config */
    private $config;
    /** @var GitPackageConfigBuildResolver $resolver */
    private $resolver;
    private $readme = 'docs/readme.txt';
    private $license = 'docs/license.txt';
    private $changelog = 'docs/changelog.txt';
    private $schemaPath = '';
    private $setupOptions = array();
    private $buildOptions = array();
    private $attributes = array();

    public function __construct(modX &$modx, GitPackageConfig $config) {
        $this->modx =& $modx;
        $this->resolver = new GitPackageConfigBuildResolver($this->modx);
        $this->config = $config;
    }

    public function fromArray($config) {
        if(isset($config['resolver'])){
            $this->resolver->fromArray($config['resolver']);
        }

        if(isset($config['readme'])){
            $this->readme = $config['readme'];
        }

        if(isset($config['license'])){
            $this->license = $config['license'];
        }

        if(isset($config['changelog'])){
            $this->changelog = $config['changelog'];
        }

        if(isset($config['setupOptions'])){
            $this->setupOptions = $config['setupOptions'];
        }

        if(isset($config['options'])){
            $this->buildOptions = $config['options'];
        }

        if(isset($config['schemaPath'])){
            $this->schemaPath = '/' . ltrim($config['schemaPath'], '/');
        } else {
            $this->schemaPath = '/core/components/' . $this->config->getLowCaseName() . '/' . 'model/schema/' . $this->config->getLowCaseName() . '.mysql.schema.xml';
        }

        if(isset($config['attributes']) && is_array($config['attributes'])){
            foreach ($config['attributes'] as $key => $attributes) {
                if (is_array($attributes)) {
                    $this->attributes[$key] = $attributes;
                }
            }
        }

        return true;
    }

    /**
     * @return GitPackageConfigBuildResolver
     */
    public function getResolver() {
        return $this->resolver;
    }

    /**
     * @param GitPackageConfigBuildResolver $resolver
     */
    public function setResolver($resolver) {
        $this->resolver = $resolver;
    }

    /**
     * @return string
     */
    public function getReadme() {
        return $this->readme;
    }

    /**
     * @param string $readme
     */
    public function setReadme($readme) {
        $this->readme = $readme;
    }

    /**
     * @return string
     */
    public function getLicense() {
        return $this->license;
    }

    /**
     * @param string $license
     */
    public function setLicense($license) {
        $this->license = $license;
    }

    /**
     * @return string
     */
    public function getChangeLog() {
        return $this->changelog;
    }

    /**
     * @param string $changeLog
     */
    public function setChangeLog($changeLog) {
        $this->changeLog = $changeLog;
    }

    /**
     * @return array
     */
    public function getSetupOptions() {
        return $this->setupOptions;
    }

    /**
     * @return array
     */
    public function getBuildOptions() {
        return $this->buildOptions;
    }

    /**
     * @param array $setupOptions
     */
    public function setSetupOptions($setupOptions) {
        $this->setupOptions = $setupOptions;
    }

    public function getAttributes(){
        return $this->attributes;
    }

    public function getSchemaPath()
    {
        return $this->schemaPath;
    }

}