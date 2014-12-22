<?php

class GitPackageConfigBuild {
    private $modx;
    /** @var GitPackageConfigBuildResolver $resolver */
    private $resolver;

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        $this->resolver = new GitPackageConfigBuildResolver($this->modx);

        if(isset($config['resolver'])){
            $this->resolver->fromArray($config['resolver']);
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

}