<?php
namespace GPM\Config\Parts;

use Psr\Log\LoggerInterface;

/**
 * Class Build
 *
 * @property-read array $scriptsBefore
 * @property-read array $scriptsAfter
 *
 * @package GPM\Config\Parts
 */
class Build extends Part
{
    /** @var string[] */
    protected $scriptsBefore = [];

    /** @var string[] */
    protected $scriptsAfter = [];

    public function getScriptsPath(): string
    {
        return $this->config->paths->package . '_build' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR;
    }

    public function validate(LoggerInterface $logger): bool
    {
        $valid = true;

        if (!empty($this->scriptsBefore)) {
            if (!is_array($this->scriptsBefore)) {
                $valid = false;
                $logger->error("Build - scriptsBefore has to be an array");
            } else {
                foreach ($this->scriptsBefore as $script) {
                    $scriptPath = $this->getScriptsPath() . $script;
                    if (!file_exists($scriptPath)) {
                        $valid = false;
                        $logger->error("Build - scriptsBefore - {$script} doesn't exist");
                    }
                }
            }
        }

        if (!empty($this->scriptsAfter)) {
            if (!is_array($this->scriptsAfter)) {
                $valid = false;
                $logger->error("Build - scriptsAfter has to be an array");
            } else {
                foreach ($this->scriptsAfter as $script) {
                    $scriptPath = $this->getScriptsPath() . $script;
                    if (!file_exists($scriptPath)) {
                        $valid = false;
                        $logger->error("Build - scriptsAfter - {$script} doesn't exist");
                    }
                }
            }
        }

        if ($valid) {
            $logger->debug(' - Build');
        }

        return $valid;
    }
}
