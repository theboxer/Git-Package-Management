<?php
namespace GPM\Operations;

use GPM\Config\Config;
use GPM\Model\GitPackage;
use GPM\Utils\Build\Attributes;
use MODX\Revolution\modCategory;
use MODX\Revolution\Transport\modPackageBuilder;
use xPDO\Transport\xPDOTransport;

class Build extends Operation {
    /** @var \Smarty */
    protected $smarty;

    /** @var Config */
    protected $config;

    /** @var modPackageBuilder */
    protected $builder;

    /** @var xPDOTransport */
    protected $package;

    public function execute(string $dir): void
    {
        $packages = $this->modx->getOption('gpm.packages_dir');

        try {
            $this->config = Config::load($this->modx, $this->logger, $packages . $dir . DIRECTORY_SEPARATOR);
            $this->builder = new modPackageBuilder($this->modx);

            $this->loadSmarty();
            $this->package = $this->createPackage();

            $this->packNamespace();
            $this->packScripts('before');

            $this->packSystemSettings();
            $this->packMenu();
            $this->packDB();
            $this->packElements();

            $this->packScripts('after');

            $this->package->pack();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $this->logger->warning('Package built.');
    }

    /**
     * @return \xPDO\Transport\xPDOTransport
     */
    public function createPackage(): xPDOTransport
    {
        $name = strtolower($this->config->general->lowCaseName);

        $version = explode('-', $this->config->general->version);
        if (count($version) == 1) {
            $version[1] = 'pl';
        }

        $release = $version[1];
        $version = $version[0];

        if (empty($version)) {
            throw new \Exception('Version is not specified');
        }

        if (empty($release)) {
            throw new \Exception('release is not specified');
        }

        $signature = $name . '-' . $version . '-' . $release;
        $filename = $signature . '.transport.zip';

        $directory = $this->config->paths->package . trim($this->modx->getOption('gpm.build_path', null, '_packages'), '/') . DIRECTORY_SEPARATOR;

        if (file_exists($directory . $filename)) {
            unlink($directory . $filename);
        }

        if (file_exists($directory . $signature) && is_dir($directory . $signature)) {
            $cacheManager = $this->modx->getCacheManager();
            if ($cacheManager) {
                $cacheManager->deleteTree($directory . $signature, true, false, []);
            }
        }

        $package = new xPDOTransport($this->modx, $signature, $directory);

        return $package;
    }

    protected function packNamespace(): void
    {
        $this->logger->notice('Packing namespace');
        $namespace = $this->modx->newObject(\MODX\Revolution\modNamespace::class);
        $namespace->set('name', $this->config->general->lowCaseName);
        $namespace->set('path', '{core_path}components/' . $this->config->general->lowCaseName . '/');
        $namespace->set('assets_path', '{assets_path}components/' . $this->config->general->lowCaseName . '/');

        $this->package->put($namespace, [
            xPDOTransport::UNIQUE_KEY    => 'name',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            'resolve' => [
                [
                    'type' => 'file',
                    'source' => $this->config->paths->core,
                    'target' => "return MODX_CORE_PATH . 'components/';",
                ],
                [
                    'type' => 'file',
                    'source' => $this->config->paths->assets,
                    'target' => "return MODX_ASSETS_PATH . 'components/';",
                ],
                [
                    'type' => 'php',
                    'source' => $this->getResolver('bootstrap'),
                ]
            ]
        ]);
    }

    protected function packSystemSettings(): void
    {
        if (!empty($this->config->systemSettings)) {
            $this->logger->notice('Packing system settings');
        }

        foreach ($this->config->systemSettings as $systemSetting) {
            $this->logger->info(' - ' . $systemSetting->getNamespacedKey());
            $this->package->put($systemSetting->getBuildObject(), Attributes::$setting);
        }
    }

    protected function packDB(): void
    {
        if (!empty($this->config->database->tables)) {
            $this->logger->notice('Packing tables');
            $this->package->put(
                [
                    'source' => $this->getScript('tables', ['tables' => $this->config->database->tables])
                ],
                [
                    'vehicle_class' => 'xPDO\\Transport\\xPDOScriptVehicle'
                ]
            );
        }
    }

    protected function packMenu(): void
    {
        if (!empty($this->config->menus)) {
            $this->logger->notice('Packing menus');
        }

        foreach ($this->config->menus as $menu) {
            $this->logger->info(' - ' . $menu->text);
            $this->package->put($menu->getBuildObject(), Attributes::$menu);
        }
    }

    protected function packElements(): void
    {
        $this->logger->notice('Packing elements');

        $category = $this->modx->newObject(modCategory::class);
        $category->set('category', $this->config->general->name);

        $this->logger->notice(' - Category: ' . $this->config->general->name);

        $snippets = $this->getElements('snippet');
        if (!empty($snippets)) {
            $category->addMany($snippets, 'Snippets');
        }

        $chunks = $this->getElements('chunk');
        if (!empty($chunks)) {
            $category->addMany($chunks, 'Chunks');
        }

        $plugins = $this->getElements('plugin');
        if (!empty($plugins)) {
            $category->addMany($plugins, 'Plugins');
        }

        $templates = $this->getElements('template');
        if (!empty($templates)) {
            $category->addMany($templates, 'Templates');
        }

        $categories = $this->getCategories($this->config->categories);
        if (!empty($categories)) {
            $category->addMany($categories, 'Children');
        }

        $this->package->put($category, Attributes::$category);
    }

    protected function packScripts($type): void
    {
        $availableTypes = ['before' => 'scriptsBefore', 'after' => 'scriptsAfter'];
        if (!isset($availableTypes[$type])) return;

        if (!empty($this->config->build->{$availableTypes[$type]})) {
            $this->logger->notice('Packing scripts ' . $type);
        }

        foreach ($this->config->build->{$availableTypes[$type]} as $script) {
            $scriptPath = $this->config->build->getScriptsPath() . $script;
            if (file_exists($scriptPath)) {
                $this->logger->info(' - ' . $script);
                $this->package->put(
                    [
                        'source' => $scriptPath
                    ],
                    [
                        'vehicle_class' => 'xPDO\\Transport\\xPDOScriptVehicle'
                    ]
                );
            }
        }
    }

    /**
     * @param \GPM\Config\Parts\Element\Category[] $categories
     * @param  array  $path
     *
     * @return array
     */
    protected function getCategories(array $categories, array $path = []): array
    {
        $childCategories = [];
        if (empty($categories)) return $childCategories;
        foreach ($categories as $cat) {
            $catPath = array_merge($path, [$cat->name]);
            $this->logger->notice(' ' . str_pad('', count($catPath) + 1, '-') . ' Category: ' . $cat->name);

            /** @var modCategory $category */
            $category = $this->modx->newObject(modCategory::class);
            $category->set('category', $cat->name);

            $snippets = $this->getElements('snippet', $catPath);
            if (!empty($snippets)) {
                $category->addMany($snippets, 'Snippets');
            }

            $chunks = $this->getElements('chunk', $catPath);
            if (!empty($chunks)) {
                $category->addMany($chunks, 'Chunks');
            }

            $plugins = $this->getElements('plugin', $catPath);
            if (!empty($plugins)) {
                $category->addMany($plugins, 'Plugins');
            }

            $templates = $this->getElements('template', $catPath);
            if (!empty($templates)) {
                $category->addMany($templates, 'Templates');
            }

            $children = $this->getCategories($cat->children, $catPath);
            if (!empty($children)) {
                $category->addMany($children, 'Children');
            }

            $childCategories[] = $category;
        }

        return $childCategories;
    }

    protected function getElements(string $type, array $category = []): array
    {
        $elements = [];

        $cfgType = $type . 's';
        $configElements = $this->config->{$cfgType};
        if (empty($configElements)) return $elements;

        /** @var \GPM\Config\Parts\Element\Element $configElement */
        foreach ($configElements as $configElement) {
            if ($configElement->category !== $category) continue;
            $this->logger->notice(' ' . str_pad('', count($category) + 2, '-') . ' ' . ucfirst($type) . ': ' . $configElement->name);
            $elements[] = $configElement->getBuildObject();
        }

        return $elements;
    }

    protected function getResolver(string $name, array $props = []): string
    {
        $gpmResolvers = $this->config->paths->package . '_build' . DIRECTORY_SEPARATOR . 'gpm_resolvers' . DIRECTORY_SEPARATOR;
        if (!is_dir($gpmResolvers)) {
            mkdir($gpmResolvers);
        }

        $resolver = "{$gpmResolvers}gpm.resolve.{$name}.php";
        if (file_exists($resolver)) {
            unlink($resolver);
        }

        if (!empty($props)) {
            $this->smarty->assign($props);
        }

        $resolverContent = $this->smarty->fetch("resolvers/{$name}.tpl");

        if (!empty($props)) {
            foreach ($props as $key => $val) {
                $this->smarty->clearAssign($key);
            }
        }

        file_put_contents($resolver, $resolverContent);

        return $resolver;
    }

    protected function getScript(string $name, array $props = []): string
    {
        $gpmScripts = $this->config->paths->package . '_build' . DIRECTORY_SEPARATOR . 'gpm_scripts' . DIRECTORY_SEPARATOR;
        if (!is_dir($gpmScripts)) {
            mkdir($gpmScripts);
        }

        $script = "{$gpmScripts}gpm.script.{$name}.php";
        if (file_exists($script)) {
            unlink($script);
        }

        if (!empty($props)) {
            $this->smarty->assign($props);
        }

        $scriptContent = $this->smarty->fetch("scripts/{$name}.tpl");

        if (!empty($props)) {
            foreach ($props as $key => $val) {
                $this->smarty->clearAssign($key);
            }
        }

        file_put_contents($script, $scriptContent);

        return $script;
    }

    protected function loadSmarty(): void
    {
        /** @var \GPM\GPM $gpm */
        $gpm = $this->modx->services->get('gpm');
        $this->smarty = new \Smarty();
        $this->smarty->setCaching(\Smarty::CACHING_OFF);
        $this->smarty->setCompileDir(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/cache/compiled_templates/');
        $this->smarty->setTemplateDir($gpm->getOption('templatesPath') . '/build/');

        $this->smarty->assign('general', $this->config->general->toArray());
    }

}
