<?php

namespace GPM\Operations;

use GPM\Config\Config;
use GPM\Model\GitPackage;
use MODX\Revolution\modDashboardWidget;
use MODX\Revolution\modElementPropertySet;
use MODX\Revolution\modPropertySet;
use MODX\Revolution\Transport\modTransportPackage;
use Psr\Log\LoggerInterface;
use MODX\Revolution\modMenu;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modCategory;
use MODX\Revolution\modX;

class Update extends Operation
{
    /** @var \GPM\Operations\ParseSchema */
    protected $parseSchema;

    /** @var GitPackage */
    protected $package;

    /** @var Config */
    protected $newConfig;

    /** @var Config */
    protected $oldConfig;

    /** @var bool */
    protected $debug = false;

    /** @var modCategory */
    protected $category = null;

    /** @var array */
    protected $categoriesMap = [];

    /** @var bool */
    protected $recreateDatabase = false;

    /** @var bool */
    protected $alterDatabase = false;

    public function __construct(modX $modx, ParseSchema $parseSchema, LoggerInterface $logger)
    {
        $this->parseSchema = $parseSchema;

        parent::__construct($modx, $logger);
    }

    public function execute(GitPackage $package, bool $recreateDatabase = false, bool $alterDatabase = false): void
    {
        $this->package = $package;
        $this->recreateDatabase = $recreateDatabase;
        $this->alterDatabase = $alterDatabase;

        try {
            $packages = $this->modx->getOption('gpm.packages_dir');
            $this->debug = intval($this->modx->getOption('gpm.enable_debug')) === 1;

            $this->oldConfig = Config::wakeMe($package->config, $this->modx);
            $this->newConfig = Config::load($this->modx, $this->logger, $packages . $this->package->dir_name . DIRECTORY_SEPARATOR);

            $this->updateMenus();
            $this->updateSystemSettings();
            $this->updateTables();
            $this->updateTransportListing();
            $this->clearCache();

            $this->updateCategories();
            $this->updateElements('snippet');
            $this->updateElements('chunk');
            $this->updateElements('plugin');
            $this->updateElements('template');
            $this->updateElements('templateVar');
            $this->updatePropertySets();
            $this->updateWidgets();

            $this->updateFred();

            $this->updateGitPackage();
        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

        $this->logger->warning('Package updated');
    }

    protected function updateMenus(): void
    {
        if (empty($this->oldConfig->menus) && empty($this->newConfig->menus)) return;
        $this->logger->notice('Updating Menus');

        $notUsedMenus = [];
        foreach ($this->oldConfig->menus as $oldMenu) {
            $notUsedMenus[$oldMenu->text] = true;
        }

        foreach ($this->newConfig->menus as $menu) {
            if (isset($notUsedMenus[$menu->text])) {
                unset($notUsedMenus[$menu->text]);
            }

            $obj = $menu->getObject();

            $saved = $obj->save();
            if ($saved) {
                $this->logger->info(' - ' . $menu->text);
            } else {
                $this->logger->error('Saving menu ' . $menu->text);
            }
        }

        if (!empty($notUsedMenus)) {
            $this->logger->notice('Removing unused Menus');
        }

        foreach ($notUsedMenus as $notUsedMenu => $v) {
            $obj = $this->modx->getObject(modMenu::class, ['text' => $notUsedMenu]);
            if ($obj) {
                $removed = $obj->remove();

                if ($removed) {
                    $this->logger->info(' - ' . $notUsedMenu);
                } else {
                    $this->logger->error('Removing menu ' . $notUsedMenu);
                }
            }
        }
    }

    protected function updateSystemSettings(): void
    {
        if (empty($this->oldConfig->systemSettings) && empty($this->newConfig->systemSettings)) return;
        $this->logger->notice('Updating System Settings');

        /** @var \GPM\Config\Parts\SystemSetting[] $toDelete */
        $toDelete = [];

        foreach ($this->oldConfig->systemSettings as $oldSetting) {
            $toDelete[$oldSetting->getNamespacedKey()] = $oldSetting;
        }

        foreach ($this->newConfig->systemSettings as $systemSetting) {
            $previousValue = isset($toDelete[$systemSetting->getNamespacedKey()]) ? $toDelete[$systemSetting->getNamespacedKey()]->value : null;
            $setting = $systemSetting->getObject($previousValue);
            $saved = $setting->save();

            if ($saved) {
                $this->logger->info(' - ' . $systemSetting->getNamespacedKey());
            } else {
                $this->logger->error('Saving system setting ' . $systemSetting->getNamespacedKey());
            }

            if (isset($toDelete[$systemSetting->getNamespacedKey()])) {
                unset($toDelete[$systemSetting->getNamespacedKey()]);
            }
        }

        if (!empty($toDelete)) {
            $this->logger->notice('Removing unused System Settings');
        }

        foreach ($toDelete as $key => $value) {
            $settingToDelete = $this->modx->getObject(modSystemSetting::class, ['key' => $key]);
            if ($settingToDelete) {
                $removed = $settingToDelete->remove();

                if ($removed) {
                    $this->logger->info(' - ' . $key);
                } else {
                    $this->logger->error('Removing system setting' . $key);
                }
            }
        }

        $this->modx->reloadConfig();
    }

    protected function updateTables(): void
    {
        if (empty($this->oldConfig->database->tables) && empty($this->newConfig->database->tables)) return;

        if (!empty($this->newConfig->database->tables)) {
            $this->parseSchema->execute($this->package);
            $this->logger->notice('Parsing schema');
        }

        $manager = $this->modx->getManager();

        if ($this->recreateDatabase) {
            if (!empty($this->oldConfig->database->tables)) {
                $this->logger->notice('Deleting Old Tables');
            }

            foreach ($this->oldConfig->database->tables as $oldTable) {
                $manager->removeObjectContainer($oldTable);
                $this->logger->info(' - ' . $oldTable);
            }

            if (!empty($this->newConfig->database->tables)) {
                $this->logger->notice('Creating New Tables');
            }

            foreach ($this->newConfig->database->tables as $newTable) {
                $manager->createObjectContainer($newTable);
                $this->logger->info(' - ' . $newTable);
            }

            return;
        }

        $notUsedTables = [];

        if (!empty($this->oldConfig->database->tables)) {
            $notUsedTables = $this->oldConfig->database->tables;
        }

        $notUsedTables = array_flip($notUsedTables);

        if (!empty($this->newConfig->database->tables)) {
            $logTitle = $this->alterDatabase ? 'Creating new & Altering existing tables' : 'Creating new tables';
            $logTitleDisplayed = false;


            foreach ($this->newConfig->database->tables as $table) {

                if (!isset($notUsedTables[$table])) {
                    if (!$logTitleDisplayed) {
                        $this->logger->notice($logTitle);
                        $logTitleDisplayed = true;
                    }

                    $manager->createObjectContainer($table);
                    $this->logger->info(' - ' . $table);
                    continue;
                }

                if ($this->alterDatabase) {
                    if (!$logTitleDisplayed) {
                        $this->logger->notice($logTitle);
                        $logTitleDisplayed = true;
                    }

                    $this->logger->info(' - ' . $table);
                    $this->alterTable($table);
                }

                unset($notUsedTables[$table]);
            }
        }

        if (!empty($notUsedTables)) {
            $this->logger->notice('Removing unused Tables');
        }

        foreach($notUsedTables as $table => $id){
            $manager->removeObjectContainer($table);
            $this->logger->info(' - ' . $table);
        }
    }

    protected function updateTransportListing(): void
    {
        $name = strtolower($this->newConfig->general->lowCaseName);

        $version = explode('-', $this->newConfig->general->version);
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
            $listing->set('provider', 0);
            $listing->set('disabled', 0);
            $listing->set('package_name', $this->newConfig->general->name); 
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

    protected function alterTable(string $table): void
    {
        $this->updateTableColumns($table);
        $this->updateTableIndexes($table);
    }

    private function updateTableColumns(string $table): void
    {
        $tableName = $this->modx->getTableName($table);
        $tableName = str_replace('`', '', $tableName);
        $dbname = $this->modx->getOption('dbname');
        $c = $this->modx->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = :dbName AND table_name = :tableName");

        $c->bindParam(':dbName', $dbname);
        $c->bindParam(':tableName', $tableName);
        $c->execute();

        $unusedColumns = $c->fetchAll(\PDO::FETCH_COLUMN, 0);
        $unusedColumns = array_flip($unusedColumns);

        $meta = $this->modx->getFieldMeta($table);
        $columns = array_keys($meta);

        $m = $this->modx->getManager();

        foreach ($columns as $column) {
            if (isset($unusedColumns[$column])) {
                $m->alterField($table, $column);
                unset($unusedColumns[$column]);
                continue;
            }

            $m->addField($table, $column);
        }

        foreach ($unusedColumns as $column => $v) {
            $m->removeField($table, $column);
        }
    }

    private function updateTableIndexes(string $table): void
    {
        $m = $this->modx->getManager();

        $tableName = $this->modx->getTableName($table);
        $tableName = str_replace('`', '', $tableName);
        $dbname = $this->modx->getOption('dbname');

        $c = $this->modx->prepare("SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = :dbName AND table_name = :tableName AND INDEX_NAME != 'PRIMARY'");

        $c->bindParam(':dbName', $dbname);
        $c->bindParam(':tableName', $tableName);
        $c->execute();

        $oldIndexes = $c->fetchAll(\PDO::FETCH_COLUMN, 0);

        foreach ($oldIndexes as $oldIndex) {
            $m->removeIndex($table, $oldIndex);
        }

        $meta = $this->modx->getIndexMeta($table);
        $indexes = array_keys($meta);

        foreach ($indexes as $index) {
            if ($index == 'PRIMARY') continue;
            $m->addIndex($table, $index);
        }
    }

    protected function clearCache(): void
    {
        $cacheManager = $this->modx->getCacheManager();
        $cacheManager->deleteTree($this->modx->getCachePath(), ['extensions' => []]);

        $this->modx->setPlaceholder('+' . $this->newConfig->general->lowCaseName . '.core_path', $this->newConfig->paths->core);
        $this->modx->setPlaceholder('+' . $this->newConfig->general->lowCaseName . '.assets_path', $this->newConfig->paths->assets);
        $this->modx->setPlaceholder('+' . $this->newConfig->general->lowCaseName . '.assets_url', $this->newConfig->paths->assetsURL);

        $this->logger->notice('Clearing cache');
    }

    protected function updateCategories(): void
    {
        /** @var modCategory $mainCategory */
        $mainCategory = $this->modx->getObject(modCategory::class, ['category' => $this->newConfig->general->name, 'parent' => 0]);
        if (!$mainCategory) {
            $mainCategory = $this->modx->newObject(modCategory::class);
            $mainCategory->set('category', $this->newConfig->general->name);
            $saved = $mainCategory->save();
            if ($saved) {
                $this->logger->notice('Creating main category: ' . $this->newConfig->general->name);
            } else {
                throw new \Exception('Creating main category: ' . $this->newConfig->general->name);
            }
        }

        $this->category = $mainCategory;

        if (empty($this->oldConfig->categories) && empty($this->newConfig->categories)) {
            return;
        }

        $this->logger->notice('Updating categories');

        foreach ($this->oldConfig->categories as $oldCategory) {
            /** @var modCategory $obj */
            $obj = $this->modx->getObject(modCategory::class, ['category' => $oldCategory->name, 'parent' => $mainCategory->id]);
            if ($obj) {
                $this->categoriesMap[$oldCategory->name] = ['children' => [], 'id' => $obj->get('id'), 'delete' => true];
                $this->getOldChildCategories($oldCategory->children, $obj->get('id'), $this->categoriesMap[$oldCategory->name]['children']);
            }
        }

        $this->syncCategories($this->newConfig->categories, $mainCategory->id, $this->categoriesMap);

        $newMap = [];
        $this->deleteCategories($this->categoriesMap, $newMap);

        $this->categoriesMap = $newMap;
    }

    protected function deleteCategories(array $oldMap, array &$newMap): void
    {
        $headlineSet = false;
        foreach ($oldMap as $key => $item) {
            if ($item['delete'] === true) {
                /** @var modCategory $obj */
                $obj = $this->modx->getObject(modCategory::class, ['id' => $item['id']]);
                if ($obj) {
                    if (!$headlineSet) {
                        $headlineSet = true;
                        $this->logger->notice('Removing unused Categories');
                    }

                    $removed = $obj->remove();
                    if ($removed) {
                        $this->logger->info(' - ' . $obj->category);
                    } else {
                        $this->logger->error('Removing category ' . $obj->category);
                    }
                }
                continue;
            }

            $newMap[$key] = $item;
            $newMap[$key]['children'] = [];
            $this->deleteCategories($item['children'], $newMap[$key]['children']);
        }
    }

    protected function syncCategories(array $childCategories, int $parentId, array &$map): void
    {
        foreach ($childCategories as $category) {
            if (isset($map[$category->name])) {
                $map[$category->name]['delete'] = false;

                $object = $this->modx->getObject(modCategory::class, ['id' => $map[$category->name]['id']]);
                $object->set('rank', $category->rank);
                $object->save();

                $this->logger->info(' - ' . $category->name);
                $this->syncCategories($category->children, $map[$category->name]['id'], $map[$category->name]['children']);

                continue;
            }

            /** @var modCategory $object */
            $object = $this->modx->getObject(modCategory::class, ['category' => $category->name, 'parent' => $parentId]);
            if (!$object) {
                $object = $this->modx->newObject(modCategory::class);
                $object->set('category', $category->name);
                $object->set('rank', $category->rank);
            }
            $object->set('parent', $parentId);
            $saved = $object->save();

            if ($saved) {
                $this->logger->info(' - ' . $category->name);
            } else {
                $this->logger->error('Creating category ' . $category->name);
            }

            $map[$category->name] = ['children' => [], 'id' => $object->get('id'), 'delete' => false];

            $this->syncCategories($category->children, $object->get('id'), $map[$category->name]['children']);
        }
    }

    protected function getOldChildCategories(array $childCategories, int $parentId, array &$map): void
    {
        foreach ($childCategories as $oldCategory) {
            /** @var modCategory $obj */
            $obj = $this->modx->getObject(modCategory::class, ['category' => $oldCategory->name, 'parent' => $parentId]);
            if ($obj) {
                $map[$oldCategory->name] = ['children' => [], 'id' => $obj->get('id'), 'delete' => true];
                $this->getOldChildCategories($oldCategory->children, $obj->get('id'), $map[$oldCategory->name]['children']);
            }
        }
    }

    protected function updateElements(string $type): void
    {
        $cfgType = $type . 's';
        $class = '\\MODX\\Revolution\\mod' . ucfirst($type);

        if (empty($this->oldConfig->{$cfgType}) && empty($this->newConfig->{$cfgType})) {
            return;
        }

        $this->logger->notice('Updating ' . ucfirst($cfgType));

        $notUsedElements = [];
        foreach ($this->oldConfig->{$cfgType} as $oldElement) {
            $notUsedElements[$oldElement->name] = true;
        }

        /** @var \GPM\Config\Parts\Element\Element $element */
        foreach ($this->newConfig->{$cfgType} as $element) {
            if (isset($notUsedElements[$element->name])) {
                unset($notUsedElements[$element->name]);
            }

            $category = $this->getCategory($element->category);
            $obj = $element->getObject($category, $this->debug);
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $element->name);

                $this->modx->removeCollection(modElementPropertySet::class, [
                    'element_class' => 'mod' . ucfirst($type),
                    'element' => $obj->id,
                ]);

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
                $this->logger->error('Saving ' . ucfirst($cfgType) . ' ' . $element->name);
            }
        }

        $pk = 'name';
        if ($type === 'template') {
            $pk = 'templatename';
        }

        if (!empty($notUsedElements)) {
            $this->logger->notice('Removing unused ' . ucfirst($cfgType));
        }

        foreach ($notUsedElements as $notUsedElement => $v) {
            $toDelete = $this->modx->getObject($class, [$pk => $notUsedElement]);
            if ($toDelete) {
                $removed = $toDelete->remove();

                if ($removed) {
                    $this->logger->info(' - ' . $notUsedElement);
                } else {
                    $this->logger->error('Removing ' . ucfirst($cfgType) . ' ' . $notUsedElement);
                }
            }
        }
    }

    protected function updateWidgets(): void
    {
        if (empty($this->oldConfig->widgets) && empty($this->newConfig->widgets)) {
            return;
        }

        $this->logger->notice('Updating Widgets');

        $notUsedWidgets = [];
        foreach ($this->oldConfig->widgets as $oldWidget) {
            $notUsedWidgets[$oldWidget->name] = true;
        }

        foreach ($this->newConfig->widgets as $widget) {
            if (isset($notUsedWidgets[$widget->name])) {
                unset($notUsedWidgets[$widget->name]);
            }

            $obj = $widget->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $widget->name);
            } else {
                $this->logger->error('Saving Widget  ' . $widget->name);
            }
        }

        if (!empty($notUsedWidgets)) {
            $this->logger->notice('Removing unused Widgets');
        }

        foreach ($notUsedWidgets as $notUsedWidget => $v) {
            $toDelete = $this->modx->getObject(modDashboardWidget::class, ['name' => $notUsedWidget, 'namespace' => $this->newConfig->general->lowCaseName]);
            if ($toDelete) {
                $removed = $toDelete->remove();

                if ($removed) {
                    $this->logger->info(' - ' . $notUsedWidget);
                } else {
                    $this->logger->error('Removing Widget ' . $notUsedWidget);
                }
            }
        }
    }

    protected function updatePropertySets(): void
    {
        if (empty($this->oldConfig->propertySets) && empty($this->newConfig->propertySets)) {
            return;
        }

        $this->logger->notice('Updating Property Sets');

        $notUsedPropertySets = [];
        foreach ($this->oldConfig->propertySets as $oldPropertySet) {
            $notUsedPropertySets[$oldPropertySet->name] = $oldPropertySet;
        }

        foreach ($this->newConfig->propertySets as $propertySet) {
            $previousProperties = null;
            if (isset($notUsedPropertySets[$propertySet->name])) {
                $previousProperties = $notUsedPropertySets[$propertySet->name]->getProperties();
                unset($notUsedPropertySets[$propertySet->name]);
            }

            $category = $this->getCategory($propertySet->category);
            $obj = $propertySet->getObject($category, $previousProperties);
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $propertySet->name);
            } else {
                $this->logger->error('Saving PropertySet ' . $propertySet->name);
            }
        }

        if (!empty($notUsedPropertySets)) {
            $this->logger->notice('Removing unused PropertySets');
        }

        foreach ($notUsedPropertySets as $notUsedPropertySet => $v) {
            $toDelete = $this->modx->getObject(modPropertySet::class, ['name' => $notUsedPropertySet]);
            if ($toDelete) {
                $removed = $toDelete->remove();

                if ($removed) {
                    $this->logger->info(' - ' . $notUsedPropertySet);
                } else {
                    $this->logger->error('Removing PropertySet ' . $notUsedPropertySet);
                }
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

    protected function updateFred(): void {
        if (!$this->modx->services->has('fred')) return;

        if (empty($this->oldConfig->fred) && empty($this->newConfig->fred)) {
            return;
        }

        $this->logger->notice('Updating Fred Theme');

        $obj = $this->newConfig->fred->theme->getObject();
        $saved = $obj->save();
        $this->newConfig->fred->theme->setUuid($obj->get('uuid'));

        if ($saved) {
            $this->logger->info(' - ' . $this->newConfig->general->name);
        } else {
            $this->logger->error('Saving Fred Theme  ' . $this->newConfig->general->name);
        }

        $this->updateFredOptionSets();
        $this->updateFredRteConfigs();

        $this->updateFredElementCategories();
        $this->updateFredBlueprintCategories();

        $this->updateFredElements();
        $this->updateFredBlueprints();

        $this->updateFredTemplates();

        $this->newConfig->fred->syncUuids();

    }

    protected function updateFredElementCategories(): void {
        if (empty($this->oldConfig->fred->elementCategories) && empty($this->newConfig->fred->elementCategories)) {
            return;
        }

        $this->logger->notice('Updating Fred Element Categories');

        $notUsedCategories = [];
        foreach ($this->oldConfig->fred->elementCategories as $oldCategories) {
            if (empty($oldCategories->uuid)) continue;
            $notUsedCategories[$oldCategories->uuid] = $oldCategories;
        }

        foreach ($this->newConfig->fred->elementCategories as $category) {
            if (isset($notUsedCategories[$category->uuid])) {
                unset($notUsedCategories[$category->uuid]);
            }

            $obj = $category->getObject();
            $saved = $obj->save();

            $category->setUuid($obj->get('uuid'));


            if ($saved) {
                $this->logger->info(' - ' . $category->name);
            } else {
                $this->logger->error('Saving Fred Element Category  ' . $category->name);
            }
        }

        if (!empty($notUsedCategories)) {
            $this->logger->notice('Removing unused Fred Element Categories');
        }

        foreach ($notUsedCategories as $catToDelete) {
            $removed = $catToDelete->deleteObject();
            if ($removed) {
                $this->logger->info(' - ' . $catToDelete->name);
            } else {
                $this->logger->error('Removing Fred Element Category ' . $catToDelete->name);
            }
        }
    }

    protected function updateFredBlueprintCategories(): void {
        if (empty($this->oldConfig->fred->blueprintCategories) && empty($this->newConfig->fred->blueprintCategories)) {
            return;
        }

        $this->logger->notice('Updating Fred Blueprint Categories');

        $notUsedCategories = [];
        foreach ($this->oldConfig->fred->blueprintCategories as $oldCategories) {
            if (empty($oldCategories->uuid)) continue;
            $notUsedCategories[$oldCategories->uuid] = $oldCategories;
        }

        foreach ($this->newConfig->fred->blueprintCategories as $category) {
            if (isset($notUsedCategories[$category->uuid])) {
                unset($notUsedCategories[$category->uuid]);
            }

            $obj = $category->getObject();
            $saved = $obj->save();

            $category->setUuid($obj->get('uuid'));

            if ($saved) {
                $this->logger->info(' - ' . $category->name);
            } else {
                $this->logger->error('Saving Fred Blueprint Category  ' . $category->name);
            }
        }

        if (!empty($notUsedCategories)) {
            $this->logger->notice('Removing unused Fred Blueprint Categories');
        }

        foreach ($notUsedCategories as $catToDelete) {
            $removed = $catToDelete->deleteObject();
            if ($removed) {
                $this->logger->info(' - ' . $catToDelete->name);
            } else {
                $this->logger->error('Removing Fred Blueprint Category ' . $catToDelete->name);
            }
        }
    }

    protected function updateFredElements(): void {
        if (empty($this->oldConfig->fred->elements) && empty($this->newConfig->fred->elements)) {
            return;
        }

        $this->logger->notice('Updating Fred Elements');

        $notUsedElements = [];
        foreach ($this->oldConfig->fred->elements as $oldElements) {
            if (empty($oldElements->uuid)) continue;
            $notUsedElements[$oldElements->uuid] = $oldElements;
        }

        foreach ($this->newConfig->fred->elements as $element) {
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

        if (!empty($notUsedElements)) {
            $this->logger->notice('Removing unused Fred Element');
        }

        foreach ($notUsedElements as $elToDelete) {
            $removed = $elToDelete->deleteObject();
            if ($removed) {
                $this->logger->info(' - ' . $elToDelete->name);
            } else {
                $this->logger->error('Removing Fred Element ' . $elToDelete->name);
            }
        }
    }

    protected function updateFredBlueprints(): void {
        if (empty($this->oldConfig->fred->blueprints) && empty($this->newConfig->fred->blueprints)) {
            return;
        }

        $this->logger->notice('Updating Fred Blueprints');

        $notUsedBlueprints = [];
        foreach ($this->oldConfig->fred->blueprints as $oldBlueprint) {
            if (empty($oldBlueprint->uuid)) continue;

            $notUsedBlueprints[$oldBlueprint->uuid] = $oldBlueprint;
        }

        foreach ($this->newConfig->fred->blueprints as $blueprint) {
            if (isset($notUsedBlueprints[$blueprint->uuid])) {
                unset($notUsedBlueprints[$blueprint->uuid]);
            }

            $obj = $blueprint->getObject();
            $saved = $obj->save();

            $blueprint->setUuid($obj->get('uuid'));

            if ($saved) {
                $this->logger->info(' - ' . $blueprint->name);
            } else {
                $this->logger->error('Saving Fred Blueprint  ' . $blueprint->name);
            }
        }

        if (!empty($notUsedBlueprints)) {
            $this->logger->notice('Removing unused Fred Blueprint');
        }

        foreach ($notUsedBlueprints as $blueprintToDelete) {
            $removed = $blueprintToDelete->deleteObject();
            if ($removed) {
                $this->logger->info(' - ' . $blueprintToDelete->name);
            } else {
                $this->logger->error('Removing Fred Blueprint ' . $blueprintToDelete->name);
            }
        }
    }

    protected function updateFredOptionSets(): void {
        if (empty($this->oldConfig->fred->optionSets) && empty($this->newConfig->fred->optionSets)) {
            return;
        }

        $this->logger->notice('Updating Fred Option Sets');

        $notUsedOptionSets = [];
        foreach ($this->oldConfig->fred->optionSets as $oldOptionSet) {
            $notUsedOptionSets[$oldOptionSet->name] = $oldOptionSet;
        }

        foreach ($this->newConfig->fred->optionSets as $optionSet) {
            if (isset($notUsedOptionSets[$optionSet->name])) {
                unset($notUsedOptionSets[$optionSet->name]);
            }

            $obj = $optionSet->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $optionSet->name);
            } else {
                $this->logger->error('Saving Fred Option Set  ' . $optionSet->name);
            }
        }

        if (!empty($notUsedOptionSets)) {
            $this->logger->notice('Removing unused Fred Option Set');
        }

        foreach ($notUsedOptionSets as $toDelete) {
            $removed = $toDelete->deleteObject();
            if ($removed) {
                $this->logger->info(' - ' . $toDelete->name);
            } else {
                $this->logger->error('Removing Fred Option Set ' . $toDelete->name);
            }
        }
    }

    protected function updateFredRteConfigs(): void {
        if (empty($this->oldConfig->fred->rteConfigs) && empty($this->newConfig->fred->rteConfigs)) {
            return;
        }

        $this->logger->notice('Updating Fred RTE Configs');

        $notUsedRteConfigs = [];
        foreach ($this->oldConfig->fred->rteConfigs as $oldRteConfig) {
            $notUsedRteConfigs[$oldRteConfig->name] = $oldRteConfig;
        }

        foreach ($this->newConfig->fred->rteConfigs as $rteConfig) {
            if (isset($notUsedRteConfigs[$rteConfig->name])) {
                unset($notUsedRteConfigs[$rteConfig->name]);
            }

            $obj = $rteConfig->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $rteConfig->name);
            } else {
                $this->logger->error('Saving Fred RTE Config  ' . $rteConfig->name);
            }
        }

        if (!empty($notUsedRteConfigs)) {
            $this->logger->notice('Removing unused Fred RTE Config');
        }

        foreach ($notUsedRteConfigs as $toDelete) {
            $removed = $toDelete->deleteObject();
            if ($removed) {
                $this->logger->info(' - ' . $toDelete->name);
            } else {
                $this->logger->error('Removing Fred RTE Config ' . $toDelete->name);
            }
        }
    }

    protected function updateFredTemplates(): void {
        if (empty($this->oldConfig->fred->templates) && empty($this->newConfig->fred->templates)) {
            return;
        }

        $this->logger->notice('Updating Fred Templates');

        $notUsedTemplates = [];
        foreach ($this->oldConfig->fred->templates as $oldTemplate) {
            $notUsedTemplates[$oldTemplate->name] = $oldTemplate;
        }

        foreach ($this->newConfig->fred->templates as $template) {
            if (isset($notUsedTemplates[$template->name])) {
                unset($notUsedTemplates[$template->name]);
            }

            $obj = $template->getObject();
            $saved = $obj->save();

            if ($saved) {
                $this->logger->info(' - ' . $template->name);
            } else {
                $this->logger->error('Saving Fred Template  ' . $template->name);
            }
        }

        if (!empty($notUsedTemplates)) {
            $this->logger->notice('Removing unused Fred Template');
        }

        foreach ($notUsedTemplates as $toDelete) {
            $removed = $toDelete->deleteObject();
            if ($removed) {
                $this->logger->info(' - ' . $toDelete->name);
            } else {
                $this->logger->error('Removing Fred Template ' . $toDelete->name);
            }
        }
    }

    protected function updateGitPackage(): void
    {
        $this->package->set('description', $this->newConfig->general->description);
        $this->package->set('version', $this->newConfig->general->version);
        $this->package->set('config', serialize($this->newConfig));
        $this->package->set('updatedon', time());
        $this->package->save();
    }

}
