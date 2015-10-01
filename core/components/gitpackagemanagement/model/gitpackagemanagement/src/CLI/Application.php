<?php
namespace GPM\CLI;

use GPM\CLI\Commands\Package\MyListCommand;

class Application extends \Symfony\Component\Console\Application
{
    /** @var \modX $modx */
    public $modx;

    /** @var \GitPackageManagement $gpm */
    public $gpm;

    protected static $name = 'GPM CLI';
    protected static $version = '1.0.0';

    public function __construct(){
        parent::__construct(self::$name, self::$version);
    }

    public function loadCommands()
    {
        /** @var \GitPackage $packages */
        $packages = $this->modx->getIterator('GitPackage');
        foreach ($packages as $package) {
            $this->add(new Commands\Package\Update($package->dir_name . ':update', $package));    
            $this->add(new Commands\Package\Build($package->dir_name . ':build', $package));    
            $this->add(new Commands\Package\Schema($package->dir_name . ':schema', $package));    
            $this->add(new Commands\Package\Delete($package->dir_name . ':delete', $package));    
            $this->add(new Commands\Package\Key\Get($package->dir_name . ':key:get', $package));    
            $this->add(new Commands\Package\Key\Refresh($package->dir_name . ':key:refresh', $package));    
        }
        
//        $this->add(new Commands\GPM\Install());
    }

    public function setMODX(\modX $modx)
    {
        $this->modx = $modx;
    }

    public function setGPM($gpm)
    {
        $this->gpm = $gpm;
    }

    public function loadGPM()
    {
        $corePath = $this->modx->getOption('gitpackagemanagement.core_path',null,$this->modx->getOption('core_path').'components/gitpackagemanagement/');

        $this->gpm = $this->modx->getService(
            'gitpackagemanagement',
            'GitPackageManagement',
            $corePath . 'model/gitpackagemanagement/',
            array(
                'core_path' => $corePath
            )
        );
    }

}