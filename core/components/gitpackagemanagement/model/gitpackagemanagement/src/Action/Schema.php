<?php
namespace GPM\Action;

use GPM\Config\Config;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

final class Schema
{
    use LoggerAwareTrait;
    
    /** @var Config */
    protected $config;
    
    /** @var \modX */
    protected $modx;
    
    /** @var \GitPackageManagement */
    protected $gpm;
    
    /** @var array */
    protected $resourceMap = [];
    
    /** @var \modCategory */
    protected $category;
    
    /** @var array */
    protected $categoriesMap = [];
    
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->modx =& $config->modx;
        $this->gpm =& $this->modx->gitpackagemanagement;
        $this->logger = $logger;
    }

    public function build()
    {
        $modelPath = $this->config->packagePath . "/core/components/" . $this->config->general->lowCaseName . "/" . 'model/';
        $modelPath = str_replace('\\', '/', $modelPath);

        $manager = $this->modx->getManager();
        $generator = $manager->getGenerator();

        $generator->classTemplate = <<<EOD
<?php
/**
 * [+phpdoc-package+]
 */
class [+class+] extends [+extends+] {}
?>
EOD;
        $generator->platformTemplate = <<<EOD
<?php
/**
 * [+phpdoc-package+]
 */
require_once (strtr(realpath(dirname(dirname(__FILE__))), '\\\\', '/') . '/[+class-lowercase+].class.php');
class [+class+]_[+platform+] extends [+class+] {}
?>
EOD;
        $generator->mapHeader = <<<EOD
<?php
/**
 * [+phpdoc-package+]
 */
EOD;

        $generator->parseSchema($this->config->packagePath. $this->config->build->schemaPath, $modelPath);
    }
}