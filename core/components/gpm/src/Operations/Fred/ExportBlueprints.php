<?php

namespace GPM\Operations\Fred;

use GPM\Config\Config;
use GPM\Config\FileParser;
use GPM\Config\Parser;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modX;
use Psr\Log\LoggerInterface;

class ExportBlueprints extends Operation
{
    /** @var GitPackage */
    protected $package;

    protected $packagesDir;

    /** @var Config */
    protected $config;

    public function __construct(modX $modx, LoggerInterface $logger)
    {
        parent::__construct($modx, $logger);
    }

    public function execute(GitPackage $package): void
    {
        $this->package = $package;

        $this->packagesDir = $this->modx->getOption('gpm.packages_dir');
        $this->config = Config::load(
            $this->modx,
            $this->logger,
            $this->packagesDir . $this->package->dir_name . DIRECTORY_SEPARATOR
        );

        // If there are no fred elements, there also can't be any blueprints
        if (count($this->config->fred->elements) === 0) {
            $this->logger->warning('There are no Fred Elements.');
            return;
        }

        $themeUuid = $this->config->fred->theme->uuid;
        if (empty($themeUuid)) {
            $this->logger->warning("Theme's UUID is not set, run update first.");
            return;
        }

        $themeId = $this->config->fred->getThemeId();
        if (empty($themeId)) {
            $this->logger->warning("Theme not found.");
            return;
        }

        $this->logger->notice("Exporting blueprints");

        $c = $this->modx->newQuery('\\Fred\\Model\\FredBlueprint');
        $c->leftJoin('\\Fred\\Model\\FredBlueprintCategory', 'Category');

        $c->where([
            'Category.theme' => $themeId
        ]);

        $c->select($this->modx->getSelectColumns('\\Fred\\Model\\FredBlueprint', 'FredBlueprint'));
        $c->select(
            $this->modx->getSelectColumns('\\Fred\\Model\\FredBlueprintCategory', 'Category', 'category_', ['name'])
        );

        $blueprints = $this->modx->getIterator('\\Fred\\Model\\FredBlueprint', $c);


        $blueprintsConfig = [];
        foreach ($blueprints as $blueprint) {
            $c = $this->modx->newQuery('\\Fred\\Model\\FredBlueprintTemplateAccess');
            $c->leftJoin(modTemplate::class, 'Template', 'FredBlueprintTemplateAccess.template = template.id');
            $c->select($this->modx->getSelectColumns(modTemplate::class, 'Template', '', ['templatename']));
            $c->prepare();
            $c->stmt->execute();

            $templateNames = $c->stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

            $blueprintsConfig[] = [
                'uuid' => $blueprint->get('uuid'),
                'name' => $blueprint->get('name'),
                'public' => $blueprint->get('public'),
                'category' => $blueprint->get('category_name'),
                'description' => $blueprint->get('description'),
                'image' => $blueprint->get('image'),
                'rank' => $blueprint->get('rank'),
                'data' => $blueprint->get('data'),
                'templates' => $templateNames,
            ];

            $this->logger->info(" - {$blueprint->get('name')}");
        }

        $this->saveBlueprints($blueprintsConfig);

        $total = count($blueprintsConfig);
        $name = 'blueprint';

        if ($total > 1) {
            $name .= 's';
        }

        $this->logger->warning("Exported $total $name!");
    }

    private function saveBlueprints(array $blueprints)
    {
        $packageDir = $this->packagesDir . $this->package->dir_name . DIRECTORY_SEPARATOR;
        $packageDir = rtrim($packageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $configDir = $packageDir . '_build' . DIRECTORY_SEPARATOR;

        if (!is_dir($configDir . 'fred')) {
            mkdir($configDir . 'fred');
        }

        $blueprintsPath = $configDir . 'fred' . DIRECTORY_SEPARATOR . 'blueprints' . DIRECTORY_SEPARATOR;
        if (!is_dir($blueprintsPath)) {
            mkdir($blueprintsPath);
        }


        $files = glob($blueprintsPath . '*');
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (count($blueprints) === 0) return;

        $parser = new Parser($packageDir);
        $configName = $parser->getConfigFileName('gpm');


        $fredConfigPath = $configDir . $configName;
        $rawConfig = FileParser::parseFile($fredConfigPath);
        $configInSeparateFile = false;

        if (is_string($rawConfig['fred'])) {
            $fredConfigPath = $configDir . $rawConfig['fred'];
            $rawConfig = FileParser::parseFile($fredConfigPath);
            $configInSeparateFile = true;
        }

        if ($configInSeparateFile) {
            $fredConfig = &$rawConfig;
        } else {
            $fredConfig = &$rawConfig['fred'];
        }

        $fredConfig['blueprints'] = [];
        foreach ($blueprints as $blueprint) {
            $data = $blueprint['data'];
            unset($blueprint['data']);

            file_put_contents($blueprintsPath . $blueprint['uuid'] . '.json', json_encode($data, JSON_PRETTY_PRINT));

            $fredConfig['blueprints'][] = $blueprint;
        }



        FileParser::writeFile($fredConfigPath, $rawConfig);
    }

}
