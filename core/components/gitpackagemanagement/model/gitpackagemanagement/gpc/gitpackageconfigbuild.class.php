<?php

class GitPackageConfigBuild {
    private $modx;
    private $resolvers = array(
        'resolveAssets' => true,
        'resolveCore' => true,
        'resolversDir' => 'resolvers',
        'before' => array(),
        'after' => array()
    );

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['resolvers']['resolveAssets'])){
            $this->resolvers['resolveAssets'] = $config['resolvers']['resolveAssets'];
        }

        if(isset($config['resolvers']['resolveCore'])){
            $this->resolvers['resolveCore'] = $config['resolvers']['resolveCore'];
        }

        if(isset($config['resolvers']['resolversDir'])){
            $this->resolvers['resolversDir'] = $config['resolvers']['resolversDir'];
        }

        if(isset($config['resolvers']['before'])){
            $this->resolvers['before'] = $config['resolvers']['before'];
        }

        if(isset($config['resolvers']['after'])){
            $this->resolvers['after'] = $config['resolvers']['after'];
        }

        return true;
    }

    /**
     * @return array
     */
    public function getResolvers() {
        return $this->resolvers;
    }

    /**
     * @param array $resolvers
     */
    public function setResolvers($resolvers) {
        $this->resolvers = $resolvers;
    }


}