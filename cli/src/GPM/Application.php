<?php
namespace GPM;

class Application extends \Symfony\Component\Console\Application
{
    /** @var \modX $modx */
    public $modx;

    /** @var \GitPackageManagement\GitPackageManagement $gpm */
    public $gpm;

    protected static $name = 'GPM CLI';
    protected static $version = '1.0.0';

    public function __construct(){
        parent::__construct(self::$name, self::$version);
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new Commands\Package\Install();
        $commands[] = new Commands\Package\Update();
        $commands[] = new Commands\Package\Build();
        $commands[] = new Commands\Package\Delete();
        $commands[] = new Commands\Package\GetList();
        $commands[] = new Commands\Package\Schema();

        $commands[] = new Commands\Package\Key\Get();
        $commands[] = new Commands\Package\Key\Refresh();

        $commands[] = new Commands\GPM\Install();

        return $commands;
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
        try {
            $this->gpm = $this->modx->services->get('gitpackagemanagement');
        } catch (\Exception $e) {
            $this->gpm = null;
        }
    }
}
