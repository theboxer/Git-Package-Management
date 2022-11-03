<?php

namespace GPM\Operations;

use GPM\Config\Config;
use GPM\Config\Parts\SystemSetting;
use MODX\Revolution\modElementPropertySet;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modCategory;
use MODX\Revolution\modPropertySet;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\modTemplateVarTemplate;
use MODX\Revolution\modX;
use Psr\Log\LoggerInterface;

class Install extends Operation
{

    /** @var \GPM\Operations\ParseSchema */
    protected $parseSchema;

    /** @var Config */
    protected $config;

    /** @var bool */
    protected $debug = false;

    /** @var modCategory */
    protected $category = null;

    /** @var array */
    protected $categoriesMap = [];

    /** @var \GPM\Model\GitPackage $packageObject */
    protected $package;

    public function __construct(modX $modx, ParseSchema $parseSchema, LoggerInterface $logger)
    {
        $this->parseSchema = $parseSchema;

        parent::__construct($modx, $logger);
    }

    public function execute(string $dir): void
    {
        try {
            $packages = $this->modx->getOption('gpm.packages_dir');
            $packagesBaseUrl = $this->modx->getOption('gpm.packages_base_url');
            $this->debug = intval($this->modx->getOption('gpm.enable_debug')) === 1;

            $this->config = Config::load($this->modx, $this->logger, $packages . $dir . DIRECTORY_SEPARATOR);

            // ADD
            $this->installScripts('before');

            $this->prepareGitPackage($dir);

            $this->createConfigFile();
            $this->createNamespace();
            $this->createMenus();
            $this->createSystemSettings($packagesBaseUrl);
            $this->createTables();
            $this->clearCache();

            $this->createCategories();
            $this->createPropertySets();
            $this->createElements('snippet');
            $this->createElements('chunk');
            $this->createElements('plugin');
            $this->createElements('template');
            // FIX TVs
            $this->createElements('templateVar');

            $this->createWidgets();

            $this->saveGitPackage();

             // ADD
             $this->installScripts('after');

        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

        $this->logger->warning('Package installed.');
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
            $this->parseSchema->execute($this->package);
            $this->logger->notice('Parsing schema');

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

    protected function createCategories(): void
    {
        $mainCategory = $this->modx->getObject(modCategory::class, ['category' => $this->config->general->name]);
        if (!$mainCategory) {
            $this->logger->notice('Creating main category: ' . $this->config->general->name);
            $mainCategory = $this->modx->newObject(modCategory::class);
            $mainCategory->set('category', $this->config->general->name);
            $mainCategory->save();
        }

        $this->category = $mainCategory;

        if (empty($this->config->categories)) {
            return;
        }

        $this->logger->notice('Creating categories');
        foreach ($this->config->categories as $category) {
            /** @var modCategory $object */
            $object = $this->modx->getObject(modCategory::class, ['category' => $category->name]);
            if (!$object) {
                $object = $this->modx->newObject(modCategory::class);
                $object->set('category', $category->name);
                $object->set('rank', $category->rank);
            }

            $object->set('parent', $this->category->get('id'));
            $saved = $object->save();

            if ($saved) {
                $this->logger->info(' - ' . $category->name);
            } else {
                $this->logger->error('Saving category ' . $category->name);
            }

            $this->categoriesMap[$category->name] = [
                'id'       => $object->get('id'),
                'children' => [],
            ];

            if (!empty($category->children)) {
                $this->createCategoryChildren($category->children, $object->get('id'), $this->categoriesMap[$category->name]['children']);
            }
        }
    }

    /**
     * @param  \GPM\Config\Parts\Element\Category[]  $categories
     * @param  int  $parent
     * @param  array  $map
     */
    protected function createCategoryChildren(array $categories, int $parent, array &$map): void
    {
        foreach ($categories as $category) {
            /** @var modCategory $object */
            $object = $this->modx->getObject(modCategory::class, ['category' => $category->name]);
            if (!$object) {
                $object = $this->modx->newObject(modCategory::class);
                $object->set('category', $category->name);
                $object->set('rank', $category->rank);
            }

            $object->set('parent', $parent);
            $saved = $object->save();

            if ($saved) {
                $this->logger->info(' - ' . $category->name);
            } else {
                $this->logger->error('Saving category ' . $category->name);
            }

            $map[$category->name] = [
                'id'       => $object->get('id'),
                'children' => [],
            ];

            if (!empty($category->children)) {
                $this->createCategoryChildren($category->children, $object->get('id'), $map[$category->name]['children']);
            }
        }
    }

    protected function createPropertySets(): void
    {
        if (empty($this->config->propertySets)) {
            return;
        }

        $this->logger->notice("Creating Property Sets");

        foreach ($this->config->propertySets as $propertySet) {
            $category = $this->getCategory($propertySet->category);
            $obj = $propertySet->getObject($category);
            $saved = $obj->save();
            if ($saved) {
                $this->logger->info(' - ' . $propertySet->name);
            } else {
                $this->logger->error("Saving PropertySet {$obj->name}");
            }
        }
    }

    /**
     * @param  string[]  $elCategory
     *
     * @return int
     */
    protected function getCategory(array $elCategory): int
    {
        if (empty($elCategory)) {
            return $this->category->id;
        }

        $catChain = $this->categoriesMap;
        $catId = $this->category->get('id');
        foreach ($elCategory as $cat) {
            if (!isset($catChain[$cat])) {
                throw new \Exception('Category chain is not correct: ' . implode(' - ', $elCategory));
            }

            $catId = $catChain[$cat]['id'];
            $catChain = $catChain[$cat]['children'];
        }

        return $catId;
    }

    /**
     * @param  string  $type
     *
     * @throws \Exception
     */
    protected function createElements(string $type): void
    {
        $cfgType = $type . 's';

        if (empty($this->config->{$cfgType})) {
            return;
        }

        $this->logger->notice("Creating {$cfgType}");

        /** @var \GPM\Config\Parts\Element\Element $element */
        foreach ($this->config->{$cfgType} as $element) {
            $category = $this->getCategory($element->category);
            /** @var \MODX\Revolution\modElement $obj */
            $obj = $element->getObject($category, $this->debug);
            $saved = $obj->save();
            if ($saved) {
                $this->logger->info(' - ' . $element->name . $this->setIdSuffix($obj->id));

                foreach ($element->propertySets as $propertySet) {
                    /** @var modPropertySet $propertySetObject */
                    $propertySetObject = $this->modx->getObject(modPropertySet::class, ['name' => $propertySet]);
                    if ($propertySetObject) {
                        /** @var modElementPropertySet $propertySetLink */
                        $propertySetLink = $this->modx->newObject(modElementPropertySet::class);
                        $propertySetLink->set('property_set', $propertySetObject->id);
                        $propertySetLink->set('element_class', 'MODX\\Revolution\\mod' . ucfirst($type));
                        $propertySetLink->set('element', $obj->id);
                        $propertySetLink->save();

                        $this->logger->info(' -- ' . $propertySet);
                    }
                }

                 // FIX TVs
                 if ($type === 'templateVar' && !empty($element->templates)) {
                    $templates = $this->modx->getCollection(modTemplate::class, ['templatename:IN' => $element->templates]);
                    if ($templates) {
                        foreach ($templates as $template) {
                            $templateTVObject = $this->modx->getObject(modTemplateVarTemplate::class, ['templateid:=' => $template->id]);
                            if (!$templateTVObject) {
                                $templateTVObject = $this->modx->newObject(modTemplateVarTemplate::class);
                            }
                            $templateTVObject->set('tmplvarid', $obj->id);
                            $templateTVObject->set('templateid', $template->id);
                            $templateTVObject->save();
                            $this->logger->info(' -- ' . 'Linked with ' . $template->templatename . $this->setIdSuffix($template->id));
                        }
                    }
                }
            } else {
                $this->logger->error("Saving {$type} {$obj->name}");
            }
        }
    }

    protected function createWidgets(): void
    {
        if (empty($this->config->widgets)) {
            return;
        }

        $this->logger->notice("Creating Widgets");

        foreach ($this->config->widgets as $widget) {
            $obj = $widget->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $widget->name);
            } else {
                $this->logger->error("Saving widget {$widget->name}");
            }
        }
    }

    protected function saveGitPackage(): void
    {
        $this->package->set('config', serialize($this->config));
        $this->package->save();
    }

    // ADD
    protected function installScripts($type): void
    {
        $availableTypes = ['before' => 'scriptsBefore', 'after' => 'scriptsAfter'];
        if (!isset($availableTypes[$type])) return;

        if (empty($this->config->install)) {
            return;
        }

        if (!empty($this->config->install->{$availableTypes[$type]})) {
            $this->logger->notice('Running install scripts ' . $type);
        }

        foreach ($this->config->install->{$availableTypes[$type]} as $script) {
            $scriptPath = $this->config->paths->scripts . $script;
            if (file_exists($scriptPath)) {
                $this->logger->info(' - ' . $script);
                require_once $scriptPath;
            }
        }
    }

}
