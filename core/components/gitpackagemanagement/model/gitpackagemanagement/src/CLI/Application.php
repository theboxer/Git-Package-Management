<?php
namespace GPM\CLI;

use GPM\CLI\Commands\GPMList;
use GPM\Utils;
use Symfony\Component\Console\Command\HelpCommand;

class Application extends \Symfony\Component\Console\Application
{
    /** @var \modX $modx */
    public $modx;

    /** @var \GitPackageManagement|null $gpm */
    public $gpm = null;

    protected static $name = 'GPM CLI';
    protected static $version = '2.0.0';

    public function __construct(){
        parent::__construct(self::$name, self::$version);
    }

    public function loadCommands()
    {
        if ($this->gpm === null) {
            $this->add(new Commands\GPM\Install());
            return;
        }
        
        /** @var \GitPackage $packages */
        $packages = $this->modx->getIterator('GitPackage');
        foreach ($packages as $package) {
            if ($package->name == 'GitPackageManagement') {
                $this->add(new Commands\GPM\Update('gpm:update', $package));
                $this->add(new Commands\GPM\Delete('gpm:delete', $package));
                $this->add(new Commands\GPM\Build('gpm:build', $package));
                continue;
            }
            $this->add(new Commands\Package\CMDList($package->dir_name));    
            $this->add(new Commands\Package\Update($package->dir_name . ':update', $package));    
            $this->add(new Commands\Package\Build($package->dir_name . ':build', $package));    
            $this->add(new Commands\Package\Schema($package->dir_name . ':schema', $package));    
            $this->add(new Commands\Package\Delete($package->dir_name . ':delete', $package));    
            $this->add(new Commands\Package\Key\Get($package->dir_name . ':key:get', $package));    
            $this->add(new Commands\Package\Key\Refresh($package->dir_name . ':key:refresh', $package));    
        }

        $this->add(new Commands\Package\Install());
        $this->add(new Commands\Package\CMDList('package'));
    }

    public function setMODX(\modX $modx)
    {
        $this->modx = $modx;
    }

    public function setGPM($gpm)
    {
        $this->gpm = $gpm;
    }

    protected function getDefaultCommands()
    {
        $commands = [new HelpCommand(), new GPMList()];
        
        if (Utils::isInGlobalMode()) {
            $this->add(new Commands\MODX\Profile());
            $this->add(new Commands\MODX\UseProfile());
            $this->add(new Commands\MODX\ListProfiles());
            $this->add(new Commands\MODX\Delete());
        }
        
        return $commands;
    }

    public function loadGPM()
    {
        $corePath = $this->modx->getOption('gitpackagemanagement.core_path',null,$this->modx->getOption('core_path').'components/gitpackagemanagement/');

        if (!is_dir($corePath)) return false;
        
        $this->gpm = $this->modx->getService(
            'gitpackagemanagement',
            'GitPackageManagement',
            $corePath . 'model/gitpackagemanagement/',
            array(
                'core_path' => $corePath
            )
        );
        
        if (!($this->gpm instanceof \GitPackageManagement)) return false;
        
        return true;
    }
}