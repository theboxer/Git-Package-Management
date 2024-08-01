<?php
/**
 *
 * THIS SCRIPT IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$lowCaseName}}
 * @subpackage build
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

use MODX\Revolution\Transport\modTransportPackage;
{foreach from=$imports item=import}
use {{$import}};
{/foreach}

class Migrator
{
    private $modx;
    private $name = '{{$lowCaseName}}';
    private $latestVersion = '';
    public function __construct(&$modx)
    {
        $this->modx =& $modx;
        $this->getLatestVersion();
    }

    private function getMigrationsMap()
    {
        $migrations = [
{foreach from=$migrations item=migration}
            (function () {
                {{$migration}}
            })(),
{/foreach}
        ];

        $migrationsMap = [];

        foreach ($migrations as $migration) {
            $migrationsMap[$migration::VERSION] = $migration;
        }

        uksort($migrationsMap, 'version_compare');

        return $migrationsMap;
    }
{literal}
    public function migrate()
    {
        if (empty($this->latestVersion)) return;

        $migrationsMap = $this->getMigrationsMap();

        foreach ($migrationsMap as $version => $migration) {
            if (version_compare($version, $this->latestVersion, '>')) {
                $this->modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, 'Running migration: ' . $version);
                $migration($this->modx);
            }
        }

    }

    private function getLatestVersion()
    {
        $c = $this->modx->newQuery(modTransportPackage::class);
        $c->where([
            'workspace' => 1,
            "(SELECT
                    `signature`
                  FROM {$this->modx->getTableName(modTransportPackage::class)} AS `latestPackage`
                  WHERE `latestPackage`.`package_name` = `modTransportPackage`.`package_name`
                  ORDER BY
                     `latestPackage`.`version_major` DESC,
                     `latestPackage`.`version_minor` DESC,
                     `latestPackage`.`version_patch` DESC,
                     IF(`release` = '' OR `release` = 'ga' OR `release` = 'pl','z',`release`) DESC,
                     `latestPackage`.`release_index` DESC
                  LIMIT 1,1) = `modTransportPackage`.`signature`",
        ]);
        $c->where([
            'modTransportPackage.package_name' => $this->name,
            'installed:IS NOT' => null
        ]);

        /** @var modTransportPackage $oldPackage */
        $oldPackage = $this->modx->getObject(modTransportPackage::class, $c);
        if ($oldPackage) {
            $this->latestVersion = $oldPackage->getComparableVersion();
        }
    }
}

(new Migrator($transport->xpdo))->migrate();
{/literal}