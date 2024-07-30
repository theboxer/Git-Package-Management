<?php
/**
 *
 * THIS SCRIPT IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$general.lowCaseName}}
 * @subpackage build
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

use MODX\Revolution\Transport\modTransportPackage;

{foreach from=$migrationClasses item=migrationClass}
{{$migrationClass}}

{/foreach}

class Migrator
{
    private $modx;
    private $name = '{{$lowCaseName}}';
    private $latestVersion = '';
{literal}
    public function __construct(&$modx)
    {
        $this->modx =& $modx;
        $this->getLatestVersion();
    }

    public function migrate($migrations)
    {
        if (empty($this->latestVersion)) return;

        $migrationsMap = [];

        foreach ($migrations as $migration) {
            $migrationsMap[$migration::VERSION] = new $migration();
        }

        uksort($migrationsMap, 'version_compare');

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
        $this->latestVersion = $oldPackage->getComparableVersion();
    }
}
{/literal}
(new Migrator($transport->xpdo))->migrate([
{foreach from=$versions item=version}
    "{{$version}}",
{/foreach}
]);