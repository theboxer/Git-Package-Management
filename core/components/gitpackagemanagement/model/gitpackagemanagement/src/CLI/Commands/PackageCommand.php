<?php
namespace GPM\CLI\Commands;

class PackageCommand extends Command
{
    /** @var \GitPackage */
    protected $package;
    
    public function __construct($name, \GitPackage $package)
    {
        $this->package = $package;
        
        parent::__construct($name);
    }
}
