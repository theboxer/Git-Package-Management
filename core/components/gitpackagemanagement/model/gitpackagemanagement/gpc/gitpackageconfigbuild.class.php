<?php

class GitPackageConfigBuild {
    private $modx;
    /** @var GitPackageConfigBuildResolver $resolver */
    private $resolver;
    private $readme = 'docs/license.txt';
    private $license = 'docs/license.txt';
    private $changeLog = 'docs/license.txt';
    private $setupOptions = array();

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
        $this->resolver = new GitPackageConfigBuildResolver($this->modx);
    }

    public function fromArray($config) {
        if(isset($config['resolver'])){
            $this->resolver->fromArray($config['resolver']);
        }

        if(isset($config['readMe'])){
            $this->readme = $config['readme'];
        }

        if(isset($config['license'])){
            $this->license = $config['license'];
        }

        if(isset($config['changeLog'])){
            $this->changeLog = $config['changelog'];
        }

        if(isset($config['setupOptions'])){
            $this->setupOptions = $config['setupOptions'];
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
        return $this->changeLog;
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
     * @param array $setupOptions
     */
    public function setSetupOptions($setupOptions) {
        $this->setupOptions = $setupOptions;
    }

}