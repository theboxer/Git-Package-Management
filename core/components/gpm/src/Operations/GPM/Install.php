<?php
namespace GPM\Operations\GPM;

use GPM\Config\Config;
use GPM\Config\Parts\SystemSetting;
use GPM\Operations\Operation;
use MODX\Revolution\modNamespace;

class Install extends Operation
{
    /** @var Config */
    protected $config;

    /** @var bool */
    protected $debug = false;

    /** @var \GPM\Model\GitPackage $packageObject */
    protected $package;

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
            $this->prepareGitPackage($dir);

            $this->createConfigFile();
            $this->createNamespace();
            $this->createMenus();
            $this->createSystemSettings($packagesBaseUrl);
            $this->createTables();
            $this->clearCache();

            $this->saveGitPackage();
        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

        $this->logger->warning('GPM installed.');
    }

    protected function prepareGitPackage(string $dir): void
    {
        $this->package = $this->modx->newObject(\GPM\Model\GitPackage::class);
        $this->package->set('version', $this->config->general->version);
        $this->package->set('description', $this->config->general->description);
        $this->package->set('author', $this->config->general->author);
        $this->package->set('name', $this->config->general->name);
        $this->package->set('dir_name', $dir);
    }

    protected function createConfigFile(): void
    {
        if (!file_exists($this->config->paths->package . 'config.core.php')) {
            @$hnd = fopen($this->config->paths->package . 'config.core.php', 'w');
            @fwrite($hnd, '<?php');
            @fclose($hnd);
        }

        $isWritable = @is_writable($this->config->paths->package . 'config.core.php');
        if (!$isWritable) {
            throw new \Exception($this->modx->lexicon('gitpackagemanagement.package_err_cc_nw', ['package' => $this->config->paths->package]));
        }

        $coreConfigContent = "<?php\n" .
            "define('MODX_CORE_PATH', '" . str_replace('\\', '\\\\', MODX_CORE_PATH) . "');\n" .
            "define('MODX_CONFIG_KEY', '" . MODX_CONFIG_KEY . "');";
        file_put_contents($this->config->paths->package . 'config.core.php', $coreConfigContent);

        $this->logger->notice('Creating config.core.php');
    }

    protected function createNamespace(): void
    {
        /** @var modNamespace $ns */
        $ns = $this->modx->newObject(modNamespace::class);
        $ns->set('name', $this->config->general->lowCaseName);
        $ns->set('path', $this->config->paths->core);
        $ns->set('assets_path', $this->config->paths->assets);
        $saved = $ns->save();

        if ($saved) {
            $this->logger->notice('Creating namespace ' . $this->config->general->lowCaseName);
            $namespace = $ns->toArray();

            if (is_readable($namespace['path'] . 'bootstrap.php')) {
                $modx =& $this->modx;
                require_once $namespace['path'] . 'bootstrap.php';
            }

            return;
        }

        throw new \Exception('Creating namespace failed');
    }

    protected function createMenus(): void
    {
        if (empty($this->config->menus)) {
            return;
        }
        $this->logger->notice('Creating Menus');

        foreach ($this->config->menus as $menu) {
            $obj = $menu->getObject();

            $saved = $obj->save();
            if ($saved) {
                $this->logger->info(' - ' . $menu->text);
            } else {
                $this->logger->error('Saving menu' . $menu->text);
            }
        }
    }

    protected function createSystemSettings(string $packagesBaseUrl): void
    {
        $this->logger->notice('Creating System Settings');

        $gpmSettings = [
            new SystemSetting(['key' => 'core_path', 'area' => 'GPM Settings', 'value' => $this->config->paths->core], $this->config),
            new SystemSetting(['key' => 'assets_path', 'area' => 'GPM Settings', 'value' => $this->config->paths->assets], $this->config),
            new SystemSetting(['key' => 'assets_url', 'area' => 'GPM Settings', 'value' => $packagesBaseUrl . $this->config->paths->assetsURL], $this->config),
        ];

        $allSetting = array_merge($gpmSettings, $this->config->systemSettings);

        foreach ($allSetting as $systemSetting) {
            $setting = $systemSetting->getObject();
            $saved = $setting->save();

            if ($saved) {
                $this->logger->info(' - ' . $systemSetting->getNamespacedKey());
            } else {
                $this->logger->error('Saving system setting ' . $systemSetting->getNamespacedKey());
            }
        }
    }

    protected function createTables(): void
    {
        if (!empty($this->config->database->tables)) {
            $this->logger->notice('Creating tables');
            $manager = $this->modx->getManager();

            foreach ($this->config->database->tables as $table) {
                $tableCreated = $manager->createObjectContainer($table);
                if ($tableCreated) {
                    $this->logger->info(' - ' . $table);
                } else {
                    $this->logger->error('Creating table ' . $table);
                }
            }
        }
    }

    protected function clearCache(): void
    {
        $cacheManager = $this->modx->getCacheManager();
        $cacheManager->deleteTree($this->modx->getCachePath(), ['extensions' => []]);

        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.core_path', $this->config->paths->core);
        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.assets_path', $this->config->paths->assets);
        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.assets_url', $this->config->paths->assetsURL);

        $this->logger->notice('Clearing cache');
    }

    protected function saveGitPackage(): void
    {
        $this->package->set('config', serialize($this->config));
        $this->package->save();
    }
}
