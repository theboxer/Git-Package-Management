<?php
namespace GPM\CLI\Commands;

use Symfony\Component\Console\Command\Command;

class GPMCommand extends Command
{
    /** @var \GitPackage */
    protected $package;
    
    public function __construct($name, \GitPackage $package)
    {
        $this->package = $package;
        
        parent::__construct($name);
    }
    
    /**
     * @return \GPM\CLI\Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}
