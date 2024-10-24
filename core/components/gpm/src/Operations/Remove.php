<?php

namespace GPM\Operations;

use MODX\Revolution\modX;
use Psr\Log\LoggerInterface;
use GPM\Config\Config;
use GPM\Model\GitPackage;
use MODX\Revolution\modMenu;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modCategory;
use MODX\Revolution\Transport\modTransportPackage;

class Remove extends Operation
{
    /** @var GitPackage */
    protected $package;

    /** @var \GPM\Operations\Scripts\Run */
    protected $runScripts;

    /** @var Config */
    protected $config;

    public function __construct(modX $modx, \GPM\Operations\Scripts\Run $runScripts, LoggerInterface $logger)
    {
        $this->runScripts = $runScripts;

        parent::__construct($modx, $logger);
    }

    public function execute(GitPackage $package, bool $skipScripts = false): void
    {
        $this->package = $package;

        try {
            $this->config = Config::wakeMe($package->config, $this->modx);

            if (!$skipScripts) {
                $this->logger->notice("Running scripts before");
                $this->runScripts->execute($this->package, \GPM\Operations\Scripts\Run::ACTION_UNINSTALL, \GPM\Operations\Scripts\Run::SCOPE_BEFORE);
            }

            $this->removeElements('snippet');
            $this->removeElements('chunk');
            $this->removeElements('plugin');
            $this->removeElements('template');
            $this->removeCategories();

            $this->removeFred();

            $this->removeMenus();

            $this->removeTables();
            $this->removeTransportListing();

            if (!$skipScripts) {
                $this->logger->notice("Running scripts after");
                $this->runScripts->execute($this->package, \GPM\Operations\Scripts\Run::ACTION_UNINSTALL, \GPM\Operations\Scripts\Run::SCOPE_AFTER);
            }

            $this->removeNamespace();
            $this->removeConfigFile();
            $this->clearCache();

            $this->removeGitPackage();
        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

        $this->logger->warning('Package removed');
    }

    protected function removeConfigFile(): void
    {
        $configFile = $this->config->paths->package . 'config.core.php';
        if (file_exists($configFile)) {
            unlink($configFile);
            $this->logger->notice('Removing config.core.php');
        }
    }

    protected function removeMenus(): void
    {
        if (!empty($this->config->menus)) {
            $this->logger->notice('Removing Menus');
        }

        foreach ($this->config->menus as $menu) {
            /** @var modMenu $obj */
            $obj = $this->modx->getObject(modMenu::class, ['text' => $menu->text, 'namespace' => $this->config->general->lowCaseName]);
            if ($obj) {
                $removed = $obj->remove();

                if ($removed) {
                    $this->logger->info(' - ' . $menu->text);
                } else {
                    $this->logger->error('Removing menu ' . $menu->text);
                }

            }
        }
    }

    protected function removeNamespace(): void
    {
        $obj = $this->modx->getObject(modNamespace::class, ['name' => $this->config->general->lowCaseName]);
        if ($obj) {
            $removed = $obj->remove();
            if ($removed) {
                $this->logger->notice('Removing namespace');
            } else {
                $this->logger->error('Removing namespace');
            }
        }
    }

    protected function removeTables(): void
    {
        $manager = $this->modx->getManager();

        if (!empty($this->config->database->tables)) {
            $this->logger->notice('Removing Tables');
        }

        foreach ($this->config->database->tables as $table) {
            $manager->removeObjectContainer($table);
            $this->logger->info(' - ' . $table);
        }
    }

    protected function removeTransportListing(): void
    {
        $this->modx->removeCollection(modTransportPackage::class, ['package_name' => $this->config->general->lowCaseName]);
        $this->logger->notice('Removing Package Installer Listing');
    }

    protected function clearCache(): void
    {
        $cacheManager = $this->modx->getCacheManager();
        $cacheManager->deleteTree($this->modx->getCachePath(), ['extensions' => []]);
        $this->logger->notice('Clearing cache');
    }

    protected function removeCategories(): void
    {
        /** @var modCategory $mainCategory */
        $mainCategory = $this->modx->getObject(modCategory::class, ['category' => $this->config->general->name, 'parent' => 0]);
        if ($mainCategory) {
            $mainCategory->remove();
            $this->logger->notice('Removing main and child categories');
        }
    }

    protected function removeElements(string $type): void
    {
        $cfgType = $type . 's';
        $class = '\\MODX\\Revolution\\mod' . ucfirst($type);

        $pk = 'name';
        if ($type === 'template') {
            $pk = 'templatename';
        }

        if (empty($this->config->{$cfgType})) {
            return;
        }

        $this->logger->notice('Removing ' . ucfirst($cfgType));

        /** @var \GPM\Config\Parts\Element\Element $element */
        foreach ($this->config->{$cfgType} as $element) {
            /** @var \MODX\Revolution\modElement $obj */
            $obj = $this->modx->getObject($class, [$pk => $element->name]);
            if ($obj) {
                $removed = $obj->remove();

                if ($removed) {
                    $this->logger->info(' - ' . $element->name);
                } else {
                    $this->logger->error('Saving ' . $type . ' ' . $element->name);
                }
            }
        }
    }

    protected function removeFred(): void {
        if (!$this->modx->services->has('fred')) return;

        if (empty($this->config->fred)) {
            return;
        }

        $themeId = $this->config->fred->getThemeId();
        $theme = $this->modx->getObject('\\Fred\\Model\\FredTheme', ['id' => $themeId]);
        if ($theme) {
            $theme->remove();
        }

    }

    protected function removeGitPackage(): void
    {
        $this->package->remove();
    }
}
