<?php
namespace GPM\Action;

use GPM\Config\Config;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

abstract class Action
{
    use LoggerAwareTrait;
    
    /** @var Config */
    protected $config;
    
    /** @var \modX */
    protected $modx;
    
    /** @var \GitPackageManagement */
    protected $gpm;
    
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->modx =& $config->modx;
        $this->gpm =& $this->modx->gitpackagemanagement;
        $this->logger = $logger;
    }

    public function checkDependencies()
    {
        $unsatisfied = [];

        foreach ($this->config->dependencies as $dependency) {
            $found = $this->modx->getCount('transport.modTransportPackage', ['package_name' => $dependency->name, 'installed:!=' => null]);
            $foundInGPM = $this->modx->getCount('GitPackage', ['name' => $dependency->name, 'OR:dir_name:=' => $dependency->name]);

            if ($found == 0 && $foundInGPM == 0) {
                $unsatisfied[$dependency->name] = $dependency->version;
            }
        }
        $this->modx->loadClass('transport.xPDOTransport', XPDO_CORE_PATH, true, true);
        
        $unsatisfied = \xPDOTransport::checkPlatformDependencies($unsatisfied);

        if (count($unsatisfied) > 0) {
            throw new \Exception('Dependencies unsatisfied: ' . implode(', ', array_keys($unsatisfied)));
        }
    }
}