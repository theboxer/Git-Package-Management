<?php
namespace GPM\Operations\Scripts;

use DirectoryIterator;
use GPM\Config\Config;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;

class Run extends Operation {
    public const ACTION_INSTALL = 'install';
    public const ACTION_UPGRADE = 'upgrade';
    public const ACTION_UNINSTALL = 'uninstall';

    public const SCOPE_ALL = 'all';
    public const SCOPE_BEFORE = 'before';
    public const SCOPE_AFTER = 'after';
    public const SCOPE_NAMES = 'names';

    public static $allActions = self::ACTION_INSTALL . ', ' . self::ACTION_UPGRADE . ' or ' . self::ACTION_UNINSTALL;
    public static $allScopes = self::SCOPE_ALL . ', ' . self::SCOPE_BEFORE . ', ' . self::SCOPE_AFTER . ' or ' . self::SCOPE_NAMES;

    /** @var Config */
    protected $config;

    public function execute(GitPackage $package, $action = self::ACTION_INSTALL, $scope = self::SCOPE_ALL, $names = []): void
    {
        $packages = $this->modx->getOption('gpm.packages_dir');

        try {
            $this->config = Config::load(
                $this->modx,
                $this->logger,
                $packages . $package->dir_name . DIRECTORY_SEPARATOR
            );

            $this->logger->warning("Running Scripts as $action");

            switch ($action) {
                case self::ACTION_UPGRADE:
                    $realAction = 1;
                    break;
                case self::ACTION_UNINSTALL:
                    $realAction = 2;
                    break;
                default:
                    $realAction = 0;
            }

            if (!is_dir($this->config->paths->build . 'scripts')) {
                $this->logger->warning("- There are no scripts");
                return;
            }

            $names = $scope === self::SCOPE_NAMES ? array_flip($this->formatNames($names)) : [];

            if ($scope === self::SCOPE_ALL || $scope === self::SCOPE_BEFORE || $scope == self::SCOPE_NAMES) {
                foreach ($this->config->build->scriptsBefore as $scriptName) {
                    if ($scope === self::SCOPE_NAMES && !isset($names[$scriptName])) {
                        continue;
                    }

                    if (substr($scriptName, -8, 8) !== '.gpm.php') {
                        $this->logger->info("- Skipping $scriptName (not gpm script)");
                        continue;
                    }

                    $this->logger->info("- Executing $scriptName");
                    $scriptFile = $this->config->paths->build . 'scripts' . DIRECTORY_SEPARATOR . $scriptName;
                    if (!file_exists($scriptFile)) {
                        $this->logger->error("- Script $scriptName not found in $scriptFile");
                        return;
                    }

                    $script = include $scriptFile;
                    $script($this->modx, $realAction);
                }
            }

            if ($scope === self::SCOPE_ALL || $scope === self::SCOPE_AFTER || $scope == self::SCOPE_NAMES) {
                foreach ($this->config->build->scriptsAfter as $scriptName) {
                    if ($scope === self::SCOPE_NAMES && !isset($names[$scriptName])) {
                        continue;
                    }

                    if (substr($scriptName, -8, 8) !== '.gpm.php') {
                        $this->logger->info("- Skipping $scriptName (not gpm script)");
                        continue;
                    }

                    $this->logger->info("- Executing $scriptName");
                    $scriptFile = $this->config->paths->build . 'scripts' . DIRECTORY_SEPARATOR . $scriptName;
                    if (!file_exists($scriptFile)) {
                        $this->logger->error("- Script $scriptName not found in $scriptFile");
                        return;
                    }

                    $script = include $scriptFile;
                    $script($this->modx, $realAction);
                }
            }
        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

    }

    private function formatNames($names)
    {
        $formatted = [];

        foreach ($names as $name) {
            if (substr($name, -8, 8) !== '.gpm.php') {
                $formatted[] = $name . '.gpm.php';
                continue;
            }

            $formatted[] = $name;
        }

        return $formatted;
    }
}
