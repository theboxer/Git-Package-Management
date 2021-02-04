<?php

namespace GPM\Operations;

use GPM\Config\Config;
use GPM\Config\Parts\SystemSetting;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modCategory;
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
            $this->prepareGitPackage($dir);

            $this->createConfigFile();
            $this->createNamespace();
            $this->createMenus();
            $this->createSystemSettings($packagesBaseUrl);
            $this->createTables();
            $this->clearCache();

            $this->createCategories();
            $this->createElements('snippet');
            $this->createElements('chunk');
            $this->createElements('plugin');
            $this->createElements('template');

            $this->saveGitPackage();
        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

        $this->logger->warning('Package installed.');
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
        if (empty($this->config->menus)) return;
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
                'id' => $object->get('id'),
                'children' => [],
            ];

            if (!empty($category->children)) {
                $this->createCategoryChildren($category->children, $object->get('id'), $this->categoriesMap[$category->name]['children']);
            }
        }
    }

    /**
     * @param \GPM\Config\Parts\Element\Category[] $categories
     * @param int $parent
     * @param array $map
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
                'id' => $object->get('id'),
                'children' => [],
            ];

            if (!empty($category->children)) {
                $this->createCategoryChildren($category->children, $object->get('id'), $map[$category->name]['children']);
            }
        }
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

        $this->logger->notice("Creating {$cfgType}:");

        /** @var \GPM\Config\Parts\Element\Element $element */
        foreach ($this->config->{$cfgType} as $element) {
            $category = $this->getCategory($element->category);
            $obj = $element->getObject($category, $this->debug);
            $saved = $obj->save();
            if ($saved) {
                $this->logger->info(' - ' . $element->name);
            } else {
                $this->logger->error("Saving {$type} {$obj->name}");
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
        if (empty($elCategory)) return $this->category->id;

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

    protected function prepareGitPackage(string $dir): void
    {
        $this->package = $this->modx->newObject(\GPM\Model\GitPackage::class);
        $this->package->set('version', $this->config->general->version);
        $this->package->set('description', $this->config->general->description);
        $this->package->set('author', $this->config->general->author);
        $this->package->set('name', $this->config->general->name);
        $this->package->set('dir_name', $dir);
    }

    protected function saveGitPackage(): void
    {
        $this->package->set('config', serialize($this->config));
        $this->package->save();
    }

}
