<?php

namespace GPM\Operations;

use GPM\Config\Config;
use GPM\Config\Parts\SystemSetting;
use MODX\Revolution\modElementPropertySet;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modCategory;
use MODX\Revolution\modPropertySet;
use MODX\Revolution\modX;
use MODX\Revolution\Transport\modTransportPackage;
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
            $this->createTransportListing();
            $this->clearCache();

            $this->createCategories();
            $this->createPropertySets();
            $this->createElements('snippet');
            $this->createElements('chunk');
            $this->createElements('plugin');
            $this->createElements('template');
            $this->createElements('templateVar');
            $this->createWidgets();

            $this->createFred();

            $this->saveGitPackage();
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

        $this->modx->reloadConfig();
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

    protected function createTransportListing(): void
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
        $listing = $this->modx->getObject(modTransportPackage::class, ['signature' => $signature]);
        if ($listing) {
            $listing->set('installed', date('Y-m-d H:i:s'));
        } else {
            $listing = $this->modx->newObject(modTransportPackage::class);
            $listing->set('source', $filename);
            $listing->set('signature', $signature);
            $listing->set('installed', date('Y-m-d H:i:s'));
            $listing->set('workspace', 1);
            $listing->set('state', 1);
            $listing->set('provider', 0);
            $listing->set('disabled', 0);
            $listing->set('package_name', $this->config->general->name); 
            $listing->set('release', $release);
            $version = explode('.', $version);
            $listing->set('version_major', $version[0]);
            $listing->set('version_minor', $version[1]);
            $listing->set('version_patch', $version[2]);
            $listing->set('metadata', [
                'id' => "fred-{$this->newConfig->general->lowCaseName}",
                'package' => "fred-package-{$this->newConfig->general->lowCaseName}",
                'display_name' => $signature,
                'name' => $this->newConfig->general->name,
                'version' => $version,
                'version_major' => $version[0],
                'version_minor' => $version[1],
                'version_patch' => $version[2],
                'release' => '',
                'vrelease' => '',
                'vrelease_index' => '',
                'author' => $this->newConfig->general->author,
                'description' => '',
                'instructions' => '',
                'changelog' => '',
            ]);
        }
        $listing->save();
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
                $this->logger->info(' - ' . $element->name);

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

    protected function createFred(): void {
        if (!$this->modx->services->has('fred')) return;

        if (empty($this->config->fred)) {
            return;
        }

        $this->logger->notice('Creating Fred Theme');

        $obj = $this->config->fred->theme->getObject();
        $saved = $obj->save();
        $this->config->fred->theme->setUuid($obj->get('uuid'));

        if ($saved) {
            $this->logger->info(' - ' . $this->config->general->name);
        } else {
            $this->logger->error('Saving Fred Theme  ' . $this->config->general->name);
        }

        $this->createFredOptionSets();
        $this->createFredRteConfigs();

        $this->createFredElementCategories();
        $this->createFredBlueprintCategories();

        $this->createFredElements();
        $this->createFredBlueprints();

        $this->createFredTemplates();

        $this->config->fred->syncUuids();

    }

    protected function createFredElementCategories(): void {
        if (empty($this->config->fred->elementCategories)) {
            return;
        }

        $this->logger->notice('Creating Fred Element Categories');

        foreach ($this->config->fred->elementCategories as $category) {
            $obj = $category->getObject();
            $saved = $obj->save();

            $category->setUuid($obj->get('uuid'));


            if ($saved) {
                $this->logger->info(' - ' . $category->name);
            } else {
                $this->logger->error('Saving Fred Element Category  ' . $category->name);
            }
        }
    }

    protected function createFredBlueprintCategories(): void {
        if (empty($this->config->fred->blueprintCategories)) {
            return;
        }

        $this->logger->notice('Creating Fred Blueprint Categories');

        foreach ($this->config->fred->blueprintCategories as $category) {
            $obj = $category->getObject();
            $saved = $obj->save();

            $category->setUuid($obj->get('uuid'));

            if ($saved) {
                $this->logger->info(' - ' . $category->name);
            } else {
                $this->logger->error('Saving Fred Blueprint Category  ' . $category->name);
            }
        }
    }

    protected function createFredElements(): void {
        if (empty($this->config->fred->elements)) {
            return;
        }

        $this->logger->notice('Creating Fred Elements');

        foreach ($this->config->fred->elements as $element) {
            if (isset($notUsedElements[$element->uuid])) {
                unset($notUsedElements[$element->uuid]);
            }

            $obj = $element->getObject();
            $saved = $obj->save();

            $element->setUuid($obj->get('uuid'));

            if ($saved) {
                $this->logger->info(' - ' . $element->name);
            } else {
                $this->logger->error('Saving Fred Element  ' . $element->name);
            }
        }
    }

    protected function createFredBlueprints(): void {
        if (empty($this->config->fred->blueprints)) {
            return;
        }

        $this->logger->notice('Creating Fred Blueprints');

        foreach ($this->config->fred->blueprints as $blueprint) {
            $obj = $blueprint->getObject();
            $saved = $obj->save();

            $blueprint->setUuid($obj->get('uuid'));

            if ($saved) {
                $this->logger->info(' - ' . $blueprint->name);
            } else {
                $this->logger->error('Saving Fred Blueprint  ' . $blueprint->name);
            }
        }
    }

    protected function createFredOptionSets(): void {
        if (empty($this->config->fred->optionSets)) {
            return;
        }

        $this->logger->notice('Creating Fred Option Sets');

        foreach ($this->config->fred->optionSets as $optionSet) {
            $obj = $optionSet->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $optionSet->name);
            } else {
                $this->logger->error('Saving Fred Option Set  ' . $optionSet->name);
            }
        }
    }

    protected function createFredRteConfigs(): void {
        if (empty($this->config->fred->rteConfigs)) {
            return;
        }

        $this->logger->notice('Creating Fred RTE Configs');

        foreach ($this->config->fred->rteConfigs as $rteConfig) {
            $obj = $rteConfig->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $rteConfig->name);
            } else {
                $this->logger->error('Saving Fred RTE Config  ' . $rteConfig->name);
            }
        }
    }

    protected function createFredTemplates(): void {
        if (empty($this->config->fred->templates)) {
            return;
        }

        $this->logger->notice('Creating Fred Templates');

        foreach ($this->config->fred->templates as $template) {
            $obj = $template->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $template->name);
            } else {
                $this->logger->error('Saving Fred Template  ' . $template->name);
            }
        }
    }

    protected function saveGitPackage(): void
    {
        $this->package->set('config', serialize($this->config));
        $this->package->save();
    }

}
