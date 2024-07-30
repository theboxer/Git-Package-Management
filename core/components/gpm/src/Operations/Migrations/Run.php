<?php
namespace GPM\Operations\Migrations;

use DirectoryIterator;
use GPM\Config\Config;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;

class Run extends Operation {
    /** @var Config */
    protected $config;

    public function execute(GitPackage $package): void
    {
        $packages = $this->modx->getOption('gpm.packages_dir');

        try {
            $this->config = Config::load(
                $this->modx,
                $this->logger,
                $packages . $package->dir_name . DIRECTORY_SEPARATOR
            );

            $lock = $this->config->paths->build . 'migrations' . DIRECTORY_SEPARATOR . 'migrations.lock';

            $latestMigration = null;
            if (file_exists($lock)) {
                $latestMigration = file_get_contents($lock);
            }

            $dir = new DirectoryIterator($this->config->paths->build . 'migrations');

            $migrationsMap = [];

            /** @var \SplFileInfo[] $dir */
            foreach ($dir as $fileInfo) {
                if ($fileInfo->isDot()) continue;

                $fileName = $fileInfo->getFilename();
                $fileName = explode('.', $fileName);

                if (count($fileName) !== 3) continue;
                if (strtolower($fileName[2]) !== 'php') continue;
                if (strtolower($fileName[1]) !== 'migration') continue;

                $migration = include $fileInfo->getRealPath();

                $migrationsMap[$migration::VERSION] = $migration;
            }

            uksort($migrationsMap, 'version_compare');

            foreach ($migrationsMap as $version => $migration) {
                if ($latestMigration !== null && version_compare($latestMigration, $version, '>=')) {
                    continue;
                }

                if (version_compare($version, $package->version, '>')) {
                    $migration($this->modx);
                    file_put_contents($lock, $version);
                }
            }

        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

    }
}
