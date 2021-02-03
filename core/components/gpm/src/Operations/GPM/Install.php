<?php
namespace GPM\Operations\GPM;

use GPM\Config\Config;

class Install extends \GPM\Operations\Install
{
    public function execute(string $dir, string $packagesDir = null, string $packagesBaseUrl = null): void
    {
        try {
            $parsedConfig = Config::parseConfig($packagesDir . $dir);
            if (empty($parsedConfig)) {
                $this->logger->error('Config file not found.');
                return;
            }

            $parsedConfig['systemSettings'][] = [
                'key'   => 'packages_dir',
                'area'  => 'Paths',
                'value' => $packagesDir,
            ];

            $parsedConfig['systemSettings'][] = [
                'key'   => 'packages_base_url',
                'area'  => 'Paths',
                'value' => $packagesBaseUrl,
            ];

            $this->config = Config::load($this->modx, $this->logger, $parsedConfig);

            $this->createConfigFile();
            $this->createNamespace();
            $this->createMenus();
            $this->createSystemSettings($packagesBaseUrl);
            $this->createTables();
            $this->clearCache();

            $this->createGitPackage($dir);
        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

        $this->logger->warning('GPM installed.');
    }
}
