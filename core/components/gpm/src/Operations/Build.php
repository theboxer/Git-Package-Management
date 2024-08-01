<?php
namespace GPM\Operations;

use GPM\Config\Config;
use GPM\Config\Parts\Fred\NoUuidException;
use GPM\Utils\Build\Attributes;
use MODX\Revolution\modCategory;
use MODX\Revolution\Transport\modPackageBuilder;
use xPDO\Transport\xPDOScriptVehicle;
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

            $this->packInstallValidator();

            $this->packNamespace();
            $this->packScripts('before');

            $this->packSystemSettings();
            $this->packMenu();
            $this->packDB();
            $this->packMainCategory();
            $this->packWidgets();

            $this->packFred();

            $this->packMigrations();

            $this->packScripts('after');

            $this->packUnInstallValidator();

            $this->setPackageAttributes();

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

    protected function packInstallValidator(): void
    {
        if (empty($this->config->build->installValidator)) return;

        $this->package->put(
            [
                'source' => $this->config->paths->scripts . $this->config->build->installValidator
            ],
            [
                'vehicle_class' => xPDOScriptVehicle::class,
                xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true,
            ]
        );
    }

    protected function packUnInstallValidator(): void
    {
        if (empty($this->config->build->unInstallValidator)) return;

        $this->package->put(
            [
                'source' => $this->config->paths->scripts . $this->config->build->unInstallValidator
            ],
            [
                'vehicle_class' => xPDOScriptVehicle::class,
                xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true,
            ]
        );
    }

    protected function packNamespace(): void
    {
        $this->logger->notice('Packing namespace');
        $namespace = $this->modx->newObject(\MODX\Revolution\modNamespace::class);
        $namespace->set('name', $this->config->general->lowCaseName);
        $namespace->set('path', '{core_path}components/' . $this->config->general->lowCaseName . '/');
        $namespace->set('assets_path', '{assets_path}components/' . $this->config->general->lowCaseName . '/');

        $namespaceResolvers = [
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
        ];

        if (!empty($this->config->build->readme)) {
            $readmePath = ltrim($this->config->build->readme, '\\/');
            if (substr($readmePath, 0, 4) !== 'core' && substr($readmePath, 0, 6) !== 'assets') {
                $namespaceResolvers[] = [
                    'type' => 'file',
                    'source' => $this->config->paths->package . $this->config->build->readme,
                    'target' => "return MODX_CORE_PATH . 'components/{$this->config->general->lowCaseName}/docs/';",
                ];
            }
        }

        if (!empty($this->config->build->license)) {
            $licensePath = ltrim($this->config->build->license, '\\/');
            if (substr($licensePath, 0, 4) !== 'core' && substr($licensePath, 0, 6) !== 'assets') {
                $namespaceResolvers[] = [
                    'type' => 'file',
                    'source' => $this->config->paths->package . $this->config->build->license,
                    'target' => "return MODX_CORE_PATH . 'components/{$this->config->general->lowCaseName}/docs/';",
                ];
            }
        }

        if (!empty($this->config->build->changelog)) {
            $changelogPath = ltrim($this->config->build->changelog, '\\/');
            if (substr($changelogPath, 0, 4) !== 'core' && substr($changelogPath, 0, 6) !== 'assets') {
                $namespaceResolvers[] = [
                    'type' => 'file',
                    'source' => $this->config->paths->package . $this->config->build->changelog,
                    'target' => "return MODX_CORE_PATH . 'components/{$this->config->general->lowCaseName}/docs/';",
                ];
            }
        }

        $this->package->put($namespace, [
            xPDOTransport::UNIQUE_KEY    => 'name',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            'resolve' => $namespaceResolvers
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

        $this->package->put([
            'type' => 'php',
            "source" => $this->getScript('reload_system_settings'),
        ], [
            "vehicle_class" => xPDOScriptVehicle::class
        ]);
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
                    'vehicle_class' => xPDOScriptVehicle::class
                ]
            );

            $this->package->put(
                [
                    'source' => $this->getScript('sync_tables', ['tables' => $this->config->database->tables])
                ],
                [
                    'vehicle_class' => xPDOScriptVehicle::class
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

    protected function packWidgets(): void
    {
        if (!empty($this->config->widgets)) {
            $this->logger->notice('Packing widgets');
        }

        foreach ($this->config->widgets as $widget) {
            $this->logger->info(' - ' . $widget->name);
            $this->package->put($widget->getBuildObject(), Attributes::$widget);
        }
    }

    protected function packMainCategory(): void
    {
        $this->logger->notice('Packing main category');

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

        $templateVars = $this->getElements('templateVar');
        if (!empty($templateVars)) {
            $category->addMany($templateVars, 'TemplateVars');
        }

        $propertySets = $this->getPropertySets();
        if (!empty($propertySets)) {
            $category->addMany($propertySets, 'PropertySets');
        }

        $categories = $this->getCategories($this->config->categories);
        if (!empty($categories)) {
            $category->addMany($categories, 'Children');
        }

        $phpResolvers = [
            [
                'type' => 'php',
                'snippets' => $this->getElementPropertySets('snippets'),
                'chunks' => $this->getElementPropertySets('chunks'),
                'plugins' => $this->getElementPropertySets('plugins'),
                'templates' => $this->getElementPropertySets('templates'),
                'source' => $this->getResolver('element_property_set'),
            ]
        ];

        if (!empty($templateVars)) {
            $phpResolvers[] = [
                'type' => 'php',
                'templateVars' => $this->getTemplateVariableTemplates(),
                'source' => $this->getResolver('template_var_templates'),
            ];
        }

        $resolvers = [
            'resolve' => $phpResolvers
        ];

        $this->package->put($category, array_merge(Attributes::$category, $resolvers));
    }

    protected function getElementPropertySets(string $type)
    {
        $elements = [];

        foreach ($this->config->{$type} as $element) {
            $elements[$element->name] = $element->propertySets;
        }

        return $elements;
    }

    protected function getTemplateVariableTemplates()
    {
        $templateVars = [];

        foreach ($this->config->templateVars as $templateVar) {
            $templateVars[$templateVar->name] = $templateVar->templates;
        }

        return $templateVars;
    }

    protected function packScripts($type): void
    {
        $availableTypes = ['before' => 'scriptsBefore', 'after' => 'scriptsAfter'];
        if (!isset($availableTypes[$type])) return;

        if (!empty($this->config->build->{$availableTypes[$type]})) {
            $this->logger->notice('Packing scripts ' . $type);
        }

        foreach ($this->config->build->{$availableTypes[$type]} as $script) {
            $scriptPath = $this->config->paths->scripts . $script;
            if (file_exists($scriptPath)) {
                $this->logger->info(' - ' . $script);

                $isGPMScript = substr($script, -8, 8) === '.gpm.php';

                $this->package->put(
                    [
                        'source' => $isGPMScript ? $this->getScript('gpm_script', [
                            'lowCaseName' => $this->config->general->lowCaseName,
                            'script' => $this->getGPMScriptContent($scriptPath),
                        ], str_replace('.gpm.php', '', $script)) : $scriptPath
                    ],
                    [
                        'vehicle_class' => xPDOScriptVehicle::class
                    ]
                );
            }
        }
    }

    protected function getGPMScriptContent($scriptPath): array
    {
        $content = file_get_contents($scriptPath);
        $content = trim($content);

        if (strncmp($content, '<?', 2) == 0) {
            $content = substr($content, 2);
            if (strncmp($content, 'php', 3) == 0) {
                $content = substr($content, 3);
            }
        }

        if (substr($content, -2, 2) == '?>') {
            $content = substr($content, 0, -2);
        }

        $content = trim($content, " \n\r\0\x0B");

        $imports = [];

        $content = preg_replace_callback('/^[[:blank:]]*use ([^;]+);[[:blank:]]*$/m', function ($matches) use (&$imports) {
            $imports[trim($matches[1])] = true;
            return '';
        }, $content);

        $imports = array_keys($imports);

        $content = preg_replace('/^\n{2,}$/m', '', $content);

        return [
            'content' => $content,
            'imports' => $imports,
        ];
    }

    protected function packMigrations(): void
    {
        if (!is_dir($this->config->paths->build . 'migrations')) {
            $migratorFile = $this->config->paths->package . '_build' . DIRECTORY_SEPARATOR . 'gpm_scripts' . DIRECTORY_SEPARATOR . 'gpm.script.migrator.php';
            if (file_exists($migratorFile)) {
                unlink($migratorFile);
            }
            return;
        }

        $dir = new \DirectoryIterator($this->config->paths->build . 'migrations');

        $migrations = [];

        $logged = false;

        $imports = ['MODX\Revolution\Transport\modTransportPackage' => true];

        /** @var \SplFileInfo[] $dir */
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDot()) continue;

            $fileName = $fileInfo->getFilename();
            $fileName = explode('.', $fileName);

            if (count($fileName) !== 3) continue;

            if (strtolower($fileName[2]) !== 'php') continue;
            if (strtolower($fileName[1]) !== 'migration') continue;

            if (!$logged) {
                $this->logger->notice('Packing migrations');
                $logged = true;
            }

            $script = $this->getGPMScriptContent($fileInfo->getRealPath());

            foreach ($script['imports'] as $import) {
                $imports[$import] = true;
            }

            $migrations[] = $script['content'];

            $this->logger->notice(' - ' . implode('.', $fileName));
        }

        // Already used in the migrator template
        unset($imports['MODX\Revolution\Transport\modTransportPackage']);

        $this->package->put([
            'type' => 'php',
            "source" => $this->getScript('migrator', [
                'lowCaseName' => $this->config->general->lowCaseName,
                'migrations' => $migrations,
                'imports' => array_keys($imports),
            ]),
        ], [
            "vehicle_class" => xPDOScriptVehicle::class,
            xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true
        ]);
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
            $category->set('rank', $cat->rank);

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

            $propertySets = $this->getPropertySets($catPath);
            if (!empty($propertySets)) {
                $category->addMany($propertySets, 'PropertySets');
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

    protected function getPropertySets(array $category = []): array
    {
        $propertySets = [];
        if (empty($this->config->propertySets)) return $propertySets;

        foreach ($this->config->propertySets as $propertySet) {
            if ($propertySet->category !== $category) continue;
            $this->logger->notice(' ' . str_pad('', count($category) + 2, '-') . ' PropertySet: ' . $propertySet->name);
            $propertySets[] = $propertySet->getBuildObject();
        }

        return $propertySets;
    }

    protected function setPackageAttributes(): void
    {
        if (!empty($this->config->build->readme)) {
            $this->package->setAttribute('readme', file_get_contents($this->config->paths->package . $this->config->build->readme));
        }

        if (!empty($this->config->build->license)) {
            $this->package->setAttribute('license', file_get_contents($this->config->paths->package . $this->config->build->license));
        }

        if (!empty($this->config->build->changelog)) {
            $this->package->setAttribute('changelog', file_get_contents($this->config->paths->package . $this->config->build->changelog));
        }

        $requires = array_filter($this->config->build->requires, function($key) {
            return strtolower($key) !== 'gpm';
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($requires)) {
            $this->package->setAttribute('requires', $requires);
        }

        if (!empty($this->config->build->setupOptions)) {
            $this->package->setAttribute('setup-options', ['source' => $this->config->paths->build . $this->config->build->setupOptions]);
        }
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

    protected function getScript(string $name, array $props = [], string $targetName = ''): string
    {
        $gpmScripts = $this->config->paths->package . '_build' . DIRECTORY_SEPARATOR . 'gpm_scripts' . DIRECTORY_SEPARATOR;
        if (!is_dir($gpmScripts)) {
            mkdir($gpmScripts);
        }

        if (empty($targetName)) {
            $targetName = $name;
        }

        $script = "{$gpmScripts}gpm.script.{$targetName}.php";
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

    protected function packFred(): void
    {
        if (!$this->modx->services->has('fred')) return;
        if (empty($this->config->fred->theme->uuid)) return;


        try {
            $this->package->put([
                'type' => 'php',
                "source" => $this->getScript('fred_get_service'),
            ], [
                "vehicle_class" => xPDOScriptVehicle::class,
                xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true
            ]);

            $theme = $this->config->fred->theme->getBuildObject();

            $accessMap = [
                'elementCategories' => [],
                'blueprintCategories' => [],
                'elements' => [],
                'blueprints' => [],
            ];

            $rteConfigs = [];
            foreach ($this->config->fred->rteConfigs as $rteConfig) {
                $rteConfigs[] = $rteConfig->getBuildObject();
            }

            $theme->addMany($rteConfigs, 'RTEConfigs');

            $optionSets = [];

            foreach ($this->config->fred->optionSets as $optionSet) {
                $optionSets[] = $optionSet->getBuildObject();
            }

            $theme->addMany($optionSets, 'OptionSets');

            $elementCategories = [];
            foreach ($this->config->fred->elementCategories as $cat) {
                $elementCategories[] = $cat->getBuildObject();

                if (!empty($cat->templates)) {
                    $accessMap['elementCategories'][$cat->uuid] = $cat->templates;
                }
            }
            $theme->addMany($elementCategories, 'ElementCategories');

            $blueprintCategories = [];
            foreach ($this->config->fred->blueprintCategories as $bpCat) {
                if ($bpCat->public === false) continue;

                $blueprintCategories[] = $bpCat->getBuildObject();

                if (!empty($bpCat->templates)) {
                    $accessMap['blueprintCategories'][$bpCat->uuid] = $bpCat->templates;
                }
            }

            $elementOptionSetMap = [];

            foreach ($this->config->fred->elements as $element) {
                if (!empty($element->templates)) {
                    $accessMap['elements'][$element->uuid] = $element->templates;
                }

                if (empty($element->option_set)) continue;

                $elementOptionSetMap[$element->uuid] = $element->option_set;
            }

            foreach ($this->config->fred->blueprints as $bp) {
                if ($bp->public === false) continue;

                if (!empty($bp->templates)) {
                    $accessMap['blueprints'][$bp->uuid] = $bp->templates;
                }
            }

            $theme->addMany($blueprintCategories, 'BlueprintCategories');

            $this->package->put($theme, Attributes::$fredTheme);

            $this->package->put([
                'type' => 'php',
                "source" => $this->getScript('fred_link_element_option_set'),
                "map" => $elementOptionSetMap
            ], [
                "vehicle_class" => xPDOScriptVehicle::class
            ]);

            $themedTemplates = [];

            foreach ($this->config->fred->templates as $template) {
                $themedTemplates[] = [
                    'name' => $template->name,
                    'blueprint' => !empty($template->defaultBlueprint) ? $this->config->fred->getBlueprintUuid($template->defaultBlueprint) : '',
                ];
            }

            if (!empty($themedTemplates)) {
                $this->package->put([
                    'type' => 'php',
                    "source" => $this->getScript('fred_link_templates'),
                    "templates" => $themedTemplates,
                    "theme" => $this->config->fred->theme->uuid,
                    "access" => $accessMap,
                ], [
                    "vehicle_class" => xPDOScriptVehicle::class
                ]);
            }

        } catch (NoUuidException $err) {
            $this->logger->critical('No UUID on ' . $err->getMessage());
        }
    }

}
