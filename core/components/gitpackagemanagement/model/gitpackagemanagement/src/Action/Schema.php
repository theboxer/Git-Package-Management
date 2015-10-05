<?php
namespace GPM\Action;

final class Schema extends Action
{
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