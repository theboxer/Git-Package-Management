<?php

namespace GPM\Commands\GPM;

use GitPackageManagement\Model\GitPackage;
use GPM\Commands\GPMCommand;
use GPM\MODX\Config;
use MODX\Revolution\modMenu;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modSystemSetting;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Install extends GPMCommand
{

    /** @var \MODX\Revolution\modX $modx */
    protected $modx;

    /** @var Config $config */
    protected $config;

    protected $packageCorePath;

    protected $packageAssetsPath;

    protected $packageAssetsUrl;

    protected function configure()
    {
        $this
            ->setName('gpm:install')
            ->setDescription('Install Git Package Management.')
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Directory name where GPM is located'
            )
            ->addOption(
                'packagesDir',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to directory where packages are located'
            )
            ->addOption(
                'packagesBaseUrl',
                null,
                InputOption::VALUE_REQUIRED,
                'Base URL of packages directory',
                '/'
            )
            ->addOption(
                'corePath',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to MODX core directory'
            )
            ->addOption(
                'configKey',
                null,
                InputOption::VALUE_REQUIRED,
                'MODX config key',
                'config'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentLocation = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $currentLocation = str_replace('\\', '/', $currentLocation);

        $corePath = $input->getOption('corePath');
        if (empty($corePath)) {
            $this->error($output, 'Option corePath is required.');
            return;
        }

        $corePath = str_replace('\\', '/', $corePath);
        $corePath = rtrim($corePath, '/') . '/';

        $configKey = $input->getOption('configKey');

        $dir = $input->getOption('dir');
        if (empty($dir)) {
            $dir = explode('/', $currentLocation);
            $dir = array_pop($dir);
        }

        $packagesDir = $input->getOption('packagesDir');
        if (empty($packagesDir)) {
            $packagesDir = str_replace('\\', '/', dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))) . '/';
        } else {
            $packagesDir = str_replace('\\', '/', $packagesDir);
            $packagesDir = rtrim($packagesDir, '/') . '/';
        }

        $packagesBaseUrl = $input->getOption('packagesBaseUrl');

        $configFileContent = $this->getConfigFileContent($corePath, $configKey);

        $fs = new Filesystem();
        $fs->dumpFile($packagesDir . $dir . '/config.core.php', $configFileContent);

        if ($fs->exists($packagesDir . $dir . '/config.core.php') && $this->getApplication()->gpm === null) {
            if (isset($GLOBALS['modx']) && $GLOBALS['modx'] instanceof \modX) {
                $modx = $GLOBALS['modx'];
            } else {
                require_once $packagesDir . $dir . '/config.core.php';
                /** @var \modX $modx */
                $modx = include 'IncludeMODX.php';
            }

            $gpm = new \GitPackageManagement\GitPackageManagement($modx);

            $this->getApplication()->setMODX($modx);
            $this->getApplication()->setGPM($gpm);
        }

        $this->modx = $this->getApplication()->modx;

        $this->install($packagesDir, $dir, $packagesBaseUrl, $output);

        $output->writeln('GPM installed.');
    }

    protected function getConfigFileContent($corePath, $configKey)
    {
        return "<?php
define('MODX_CORE_PATH', '" . $corePath . "');
define('MODX_CONFIG_KEY', '" . $configKey . "');";
    }

    protected function install($packagesDir, $dir, $packagesBaseUrl, OutputInterface $output)
    {
        $configContent = $this->modx->fromJSON(file_get_contents($packagesDir . $dir . '/_build/config.json'));

        $this->config = new Config($this->modx, $dir);
        $this->config->parseConfig($configContent);

        $this->packageCorePath = $packagesDir . $dir . "/core/components/" . $this->config->getLowCaseName() . "/";
        $this->packageCorePath = str_replace('\\', '/', $this->packageCorePath);

        $this->packageAssetsPath = $packagesDir . $dir . "/assets/components/" . $this->config->getLowCaseName() . "/";
        $this->packageAssetsPath = str_replace('\\', '/', $this->packageAssetsPath);

        $this->packageAssetsUrl = $packagesBaseUrl . $dir . '/assets/components/' . $this->config->getLowCaseName() . '/';

        $this->createNamespace();
        $this->createMenusAndActions();
        $this->createSystemSettings($packagesDir, $packagesBaseUrl);
        $this->createTables();
        $this->clearCache();


        /** @var GitPackage $packageObject */
        $packageObject = $this->modx->newObject(GitPackage::class);
        $packageObject->set('version', $this->config->getVersion());
        $packageObject->set('description', $this->config->getDescription());
        $packageObject->set('author', $this->config->getAuthor());
        $packageObject->set('name', $this->config->getName());
        $packageObject->set('dir_name', $dir);
        $packageObject->set('config', $this->modx->toJSON($configContent));
        $packageObject->save();
    }

    protected function createNamespace()
    {
        /** @var modNamespace $ns */
        $ns = $this->modx->newObject(modNamespace::class);
        $ns->set('name', $this->config->getLowCaseName());
        $ns->set('path', $this->packageCorePath);
        $ns->set('assets_path', $this->packageAssetsPath);
        $ns->save();
    }

    protected function createMenusAndActions()
    {
        $menus = [];

        /**
         * Crete menus if any
         */
        if (count($this->config->getMenus()) > 0) {
            foreach ($this->config->getMenus() as $i => $men) {
                /** @var modMenu[] $menus */
                $menus[$i] = $this->modx->newObject(modMenu::class);
                $menus[$i]->fromArray(
                    [
                        'text'        => $men->getText(),
                        'parent'      => $men->getParent(),
                        'description' => $men->getDescription(),
                        'icon'        => $men->getIcon(),
                        'menuindex'   => $men->getMenuIndex(),
                        'params'      => $men->getParams(),
                        'handler'     => $men->getHandler(),
                        'action'      => $men->getAction(),
                        'namespace'   => $this->config->getLowCaseName(),
                    ],
                    '',
                    true,
                    true
                );

                $menus[$i]->save();
            }
        }
    }

    protected function createSystemSettings($packagesDir, $packagesBaseUrl)
    {
        $this->createSystemSetting($this->config->getLowCaseName() . '.core_path', $this->packageCorePath, 'textfield', 'gitpackagemanagement');
        $this->createSystemSetting($this->config->getLowCaseName() . '.assets_path', $this->packageAssetsPath, 'textfield', 'gitpackagemanagement');
        $this->createSystemSetting($this->config->getLowCaseName() . '.assets_url', $this->packageAssetsUrl, 'textfield', 'gitpackagemanagement');

        $this->createSystemSetting($this->config->getLowCaseName() . '.packages_dir', $packagesDir, 'textfield', 'Paths');
        $this->createSystemSetting($this->config->getLowCaseName() . '.packages_base_url', $packagesBaseUrl, 'textfield', 'Paths');

        foreach ($this->config->getSettings() as $setting) {
            if ($setting->getKey() == 'packages_dir' || $setting->getKey() == 'packages_base_url') {
                continue;
            }

            $this->createSystemSetting($setting->getNamespacedKey(), $setting->getValue(), $setting->getType(), $setting->getArea());
        }
    }

    /**
     * Support method for createSystemSettings(), insert system setting to database
     *
     * @param $key string
     * @param $value string
     * @param  string  $xtype  string
     * @param  string  $area  string
     */
    protected function createSystemSetting($key, $value, $xtype = 'textfield', $area = 'default')
    {
        $ct = $this->modx->getObject(modSystemSetting::class, ['key' => $key]);
        if (!$ct) {
            /** @var modSystemSetting $setting */
            $setting = $this->modx->newObject(modSystemSetting::class);
            $setting->set('key', $key);
            $setting->set('value', $value);
            $setting->set('namespace', $this->config->getLowCaseName());
            $setting->set('area', $area);
            $setting->set('xtype', $xtype);
            $setting->save();
        } else {
            $ct->set('value', $value);
            $ct->set('namespace', $this->config->getLowCaseName());
            $ct->set('area', $area);
            $ct->set('xtype', $xtype);
            $ct->save();
        }
    }

    protected function createTables()
    {
        if ($this->config->getDatabase() != null) {
            $modelPath = $this->packageCorePath . 'model/';
            $this->modx->addPackage($this->config->getLowCaseName(), $modelPath, $this->config->getDatabase()->getPrefix());

            $manager = $this->modx->getManager();

            foreach ($this->config->getDatabase()->getTables() as $table) {
                $manager->createObjectContainer($table);
            }
        }
    }

    protected function clearCache()
    {
        $this->modx->cacheManager->delete('system_settings/config', ['cache_key' => '']);
        $results = [];
        $partitions = ['menu' => []];
        $this->modx->cacheManager->refresh($partitions, $results);

        $this->modx->setPlaceholder('+' . $this->config->getLowCaseName() . '.core_path', $this->packageCorePath);
        $this->modx->setPlaceholder('+' . $this->config->getLowCaseName() . '.assets_path', $this->packageAssetsPath);
        $this->modx->setPlaceholder('+' . $this->config->getLowCaseName() . '.assets_url', $this->packageAssetsUrl);
    }

}
