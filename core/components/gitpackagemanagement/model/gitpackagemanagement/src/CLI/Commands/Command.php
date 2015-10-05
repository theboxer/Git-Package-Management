<?php
namespace GPM\CLI\Commands;

use Symfony\Component\Console\Command\Command as SCommand;

class Command extends SCommand
{
    /**
     * @return \GPM\CLI\Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}
