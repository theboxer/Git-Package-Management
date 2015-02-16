<?php
namespace GPM;

class Application extends \Symfony\Component\Console\Application
{
    /** @var \modX $modx */
    public $modx;

    /** @var \GitPackageManagement $gpm */
    public $gpm;

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new Commands\Package\Install();
        $commands[] = new Commands\Package\Update();
        $commands[] = new Commands\Package\Build();
        $commands[] = new Commands\Package\Delete();

        $commands[] = new Commands\Package\Key\Get();
        $commands[] = new Commands\Package\Key\Refresh();

        $commands[] = new Commands\GPM\Install();

        return $commands;
    }

    public function setMODX(\modX $modx)
    {
        $this->modx = $modx;
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