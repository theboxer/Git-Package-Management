<?php
namespace GPM\Config\Parts;

use Psr\Log\LoggerInterface;

/**
 * Class Build
 *
 * @property-read string $readme
 * @property-read string $license
 * @property-read string $changelog
 * @property-read array $scriptsBefore
 * @property-read array $scriptsAfter
 *
 * @package GPM\Config\Parts
 */
class Build extends Part
{

    /** @var string */
    protected $readme = '';

    /** @var string */
    protected $license = '';

    /** @var string */
    protected $changelog = '';

    /** @var string[] */
    protected $scriptsBefore = [];

    /** @var string[] */
    protected $scriptsAfter = [];

    public function getScriptsPath(): string
    {
        return $this->config->paths->package . '_build' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR;
    }

    protected function generator(): void
    {
        if (empty($this->readme) && file_exists($this->config->paths->package . 'README.md')) {
            $this->readme = 'README.md';
        }

        if (empty($this->changelog) && file_exists($this->config->paths->package . 'CHANGELOG.md')) {
            $this->changelog = 'CHANGELOG.md';
        }

        if (empty($this->license) && file_exists($this->config->paths->package . 'LICENSE.md')) {
            $this->license = 'LICENSE.md';
        }
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

        if (!empty($this->readme)) {
            if (!file_exists($this->config->paths->package . $this->readme)) {
                $logger->error("Build - {$this->readme} doesn't exist");
                $valid = false;
            }
        }

        if (!empty($this->changelog)) {
            if (!file_exists($this->config->paths->package . $this->changelog)) {
                $logger->error("Build - {$this->changelog} doesn't exist");
                $valid = false;
            }
        }

        if (!empty($this->license)) {
            if (!file_exists($this->config->paths->package . $this->license)) {
                $logger->error("Build - {$this->license} doesn't exist");
                $valid = false;
            }
        }

        if ($valid) {
            $logger->debug(' - Build');
        }

        return $valid;
    }
}
