<?php
namespace GPM\Operations\GPM;

use GPM\Config\Config;
use GPM\Config\Parts\SystemSetting;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;
use GPM\Operations\ParseSchema;
use MODX\Revolution\modMenu;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modX;
use Psr\Log\LoggerInterface;

class Update extends Operation
{
    /** @var GitPackage */
    protected $package;

    /** @var Config */
    protected $newConfig;

    /** @var Config */
    protected $oldConfig;

    public function __construct(modX $modx, ParseSchema $parseSchema, LoggerInterface $logger)
    {
        $this->parseSchema = $parseSchema;

        parent::__construct($modx, $logger);
    }

    public function execute(GitPackage $package): void
    {
        $this->package = $package;

        try {
            $packages = $this->modx->getOption('gpm.packages_dir');
            $this->debug = intval($this->modx->getOption('gpm.enable_debug')) === 1;

            $this->oldConfig = Config::wakeMe($package->config, $this->modx);
            $this->newConfig = Config::load($this->modx, $this->logger, $packages . $this->package->dir_name . DIRECTORY_SEPARATOR);

            $this->updateMenus();
            $this->updateSystemSettings();
            $this->updateTables();

            $this->updateGitPackage();
            $this->clearCache();
        } catch (\Exception $err) {
            $this->logger->error($err->getMessage());
            return;
        }

        $this->logger->warning('GPM installed.');
    }

    protected function updateGitPackage(): void
    {
        $this->package->set('description', $this->newConfig->general->description);
        $this->package->set('version', $this->newConfig->general->version);
        $this->package->set('config', serialize($this->newConfig));
        $this->package->set('updatedon', time());
        $this->package->save();
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
            $key = $oldSetting->getNamespacedKey();
            if ($key === 'gpm.packages_dir') continue;
            if ($key === 'gpm.packages_base_url') continue;
            $toDelete[$key] = $oldSetting;
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
    }

    protected function updateTables(): void
    {
        if (empty($this->oldConfig->database->tables) && empty($this->newConfig->database->tables)) return;

        if (!empty($this->newConfig->database->tables)) {
            $this->parseSchema->execute($this->package);
            $this->logger->notice('Parsing schema');
        }

        $manager = $this->modx->getManager();

        $notUsedTables = [];

        if (!empty($this->oldConfig->database->tables)) {
            $notUsedTables = $this->oldConfig->database->tables;
        }

        $notUsedTables = array_flip($notUsedTables);

        if (!empty($this->newConfig->database->tables)) {
            $logTitle = 'Creating new & Altering existing tables';
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

                if (!$logTitleDisplayed) {
                    $this->logger->notice($logTitle);
                    $logTitleDisplayed = true;
                }

                $this->logger->info(' - ' . $table);
                $this->alterTable($table);

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

        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.core_path', $this->config->paths->core);
        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.assets_path', $this->config->paths->assets);
        $this->modx->setPlaceholder('+' . $this->config->general->lowCaseName . '.assets_url', $this->config->paths->assetsURL);

        $this->logger->notice('Clearing cache');
    }
}
