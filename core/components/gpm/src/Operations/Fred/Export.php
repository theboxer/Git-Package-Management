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

class Export extends Operation
{
    /** @var GitPackage */
    protected $package;

    protected $packagesDir;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $configDir;

    /** @var int */
    protected $themeId;

    public function __construct(modX $modx, LoggerInterface $logger)
    {
        parent::__construct($modx, $logger);
    }

    public function execute(GitPackage $package, array $parts): void
    {
        $this->package = $package;

        $this->packagesDir = $this->modx->getOption('gpm.packages_dir');
        $this->config = Config::load(
            $this->modx,
            $this->logger,
            $this->packagesDir . $this->package->dir_name . DIRECTORY_SEPARATOR
        );

        $themeUuid = $this->config->fred->theme->uuid;
        if (empty($themeUuid)) {
            $this->logger->warning("Theme's UUID is not set, run update first.");
            return;
        }

        $this->themeId = $this->config->fred->getThemeId();
        if (empty($this->themeId)) {
            $this->logger->warning("Theme not found.");
            return;
        }

        $this->logger->notice("Going to export: " . implode(', ', $parts));

        $config = [];
        $parts = array_flip($parts);

        if (isset($parts['blueprints'])) {
            $config['blueprints'] = $this->loadBlueprints();
        }

        if (isset($parts['elements'])) {
            $config['elements'] = $this->loadElements();
        }

        if (isset($parts['optionSets'])) {
            $config['optionSets'] = $this->loadOptionSets();
        }

        if (isset($parts['rteConfigs'])) {
            $config['rteConfigs'] = $this->loadRteConfigs();
        }

        if (isset($parts['elementCategories'])) {
            $config['elementCategories'] = $this->loadElementCategories();
        }

        if (isset($parts['blueprintCategories'])) {
            $config['blueprintCategories'] = $this->loadBlueprintCategories();
        }

        if (isset($parts['themedTemplates'])) {
            $config['themedTemplates'] = $this->loadThemedTemplates();
        }

        if (empty($config)) return;

        $this->saveConfig($config);
    }

    private function loadBlueprints()
    {
        $this->logger->notice("Exporting blueprints");

        $c = $this->modx->newQuery('\\Fred\\Model\\FredBlueprint');
        $c->leftJoin('\\Fred\\Model\\FredBlueprintCategory', 'Category');

        $c->where([
            'Category.theme' => $this->themeId
        ]);

        $c->select($this->modx->getSelectColumns('\\Fred\\Model\\FredBlueprint', 'FredBlueprint'));
        $c->select(
            $this->modx->getSelectColumns('\\Fred\\Model\\FredBlueprintCategory', 'Category', 'category_', ['name'])
        );

        $blueprints = $this->modx->getIterator('\\Fred\\Model\\FredBlueprint', $c);


        $blueprintsConfig = [];
        foreach ($blueprints as $blueprint) {
            $c = $this->modx->newQuery('\\Fred\\Model\\FredBlueprintTemplateAccess');
            $c->where([
                'blueprint' => $blueprint->get('id'),
            ]);
            $c->leftJoin(modTemplate::class, 'Template', 'FredBlueprintTemplateAccess.template = template.id');
            $c->select($this->modx->getSelectColumns(modTemplate::class, 'Template', '', ['templatename']));
            $c->prepare();
            $c->stmt->execute();

            $templateNames = $c->stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

            $cfg = [
                'uuid' => $blueprint->get('uuid'),
                'name' => $blueprint->get('name'),
                'public' => $blueprint->get('public'),
                'category' => $blueprint->get('category_name'),
                'description' => $blueprint->get('description'),
                'image' => $blueprint->get('image'),
                'rank' => $blueprint->get('rank'),
                'data' => $blueprint->get('data'),
            ];

            if (!empty($templateNames)) {
                $cfg['templates'] = $templateNames;
            }

            $blueprintsConfig[] = $cfg;

            $this->logger->info(" - {$blueprint->get('name')}");
        }

        return $blueprintsConfig;
    }

    private function loadElements()
    {
        $this->logger->notice("Exporting elements");

        $c = $this->modx->newQuery('\\Fred\\Model\\FredElement');
        $c->leftJoin('\\Fred\\Model\\FredElementCategory', 'Category');

        $c->where([
            'Category.theme' => $this->themeId
        ]);

        $c->select($this->modx->getSelectColumns('\\Fred\\Model\\FredElement', 'FredElement'));
        $c->select(
            $this->modx->getSelectColumns('\\Fred\\Model\\FredElementCategory', 'Category', 'category_', ['name'])
        );

        $elements = $this->modx->getIterator('\\Fred\\Model\\FredElement', $c);

        $optionSetsMap = [];


        $elementsConfig = [];
        foreach ($elements as $element) {
            $c = $this->modx->newQuery('\\Fred\\Model\\FredElementTemplateAccess');
            $c->where([
                'element' => $element->get('id'),
            ]);
            $c->leftJoin(modTemplate::class, 'Template', 'FredElementTemplateAccess.template = template.id');
            $c->select($this->modx->getSelectColumns(modTemplate::class, 'Template', '', ['templatename']));
            $c->prepare();
            $c->stmt->execute();

            $templateNames = $c->stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

            $optionSetId = $element->get('option_set');

            $optionSet = null;

            if (!empty($optionSetId)) {
                if (isset($optionSetsMap[$optionSetId])) {
                    $optionSet = $optionSetsMap[$optionSetId];
                } else {
                    $optionSetObj = $this->modx->getObject('\\Fred\\Model\\FredElementOptionSet', ['id' => $optionSetId]
                    );
                    if (!$optionSetObj) {
                        continue;
                    }

                    $optionSetsMap[$optionSetId] = $optionSetObj->get('name');

                    $optionSet = $optionSetsMap[$optionSetId];
                }
            }

            $elConfig = [
                'uuid' => $element->get('uuid'),
                'name' => $element->get('name'),
                'category' => $element->get('category_name'),
                'description' => $element->get('description'),
                'image' => $element->get('image'),
                'rank' => $element->get('rank'),
                'content' => $element->get('content'),
            ];

            if (!empty($optionSet)) {
                $elConfig['option_set'] = $optionSet;
            }

            if (!empty($element->get('options_override'))) {
                $elConfig['options_override'] = $element->get('options_override');
            }

            if (!empty($templateNames)) {
                $elConfig['templates'] = $templateNames;
            }

            $elementsConfig[] = $elConfig;

            $this->logger->info(" - {$element->get('name')}");
        }

        return $elementsConfig;
    }

    private function loadOptionSets()
    {
        $this->logger->notice("Exporting option sets");

        $optionSets = $this->modx->getIterator('\\Fred\\Model\\FredElementOptionSet', ['theme' => $this->themeId]);

        $optionSetsConfig = [];
        foreach ($optionSets as $optionSet) {
            $optionSetsConfig[] = [
                'name' => $optionSet->get('name'),
                'description' => $optionSet->get('description'),
                'complete' => $optionSet->get('complete'),
                'data' => $optionSet->get('data'),
            ];

            $this->logger->info(" - {$optionSet->get('name')}");
        }

        return $optionSetsConfig;
    }

    private function loadRteConfigs()
    {
        $this->logger->notice("Exporting RTE Configs");

        $rteConfigs = $this->modx->getIterator('\\Fred\\Model\\FredElementRTEConfig', ['theme' => $this->themeId]);

        $rteConfigConfigs = [];
        foreach ($rteConfigs as $rteConfig) {
            $rteConfigConfigs[] = [
                'name' => $rteConfig->get('name'),
                'description' => $rteConfig->get('description'),
                'data' => $rteConfig->get('data'),
            ];

            $this->logger->info(" - {$rteConfig->get('name')}");
        }

        return $rteConfigConfigs;
    }

    private function loadThemedTemplates()
    {
        $this->logger->notice("Exporting Themed Templates");

        $c = $this->modx->newQuery('\\Fred\\Model\\FredThemedTemplate');
        $c->leftJoin('\\Fred\\Model\\FredBlueprint', 'FredBlueprint', 'FredThemedTemplate.default_blueprint = FredBlueprint.id');
        $c->leftJoin('\\MODX\\Revolution\\modTemplate', 'Template');

        $c->select($this->modx->getSelectColumns('\\Fred\\Model\\FredThemedTemplate', 'FredThemedTemplate'));
        $c->select($this->modx->getSelectColumns('\\Fred\\Model\\FredBlueprint', 'FredBlueprint', 'bp_', ['name']));
        $c->select($this->modx->getSelectColumns('\\MODX\\Revolution\\modTemplate', 'Template', 'template_', ['templatename']));

        $c->where(['theme' => $this->themeId]);

        $themedTemplates = $this->modx->getIterator('\\Fred\\Model\\FredThemedTemplate', $c);

        $themedTemplateConfigs = [];
        foreach ($themedTemplates as $themedTemplate) {
            $themedTemplateConfigs[] = [
                'name' => $themedTemplate->get('template_templatename'),
                'defaultBlueprint' => $themedTemplate->get('bp_name'),
            ];

            $this->logger->info(" - {$themedTemplate->get('template_templatename')}");
        }

        return $themedTemplateConfigs;
    }

    private function loadElementCategories()
    {
        $this->logger->notice("Exporting Element Categories");

        $categories = $this->modx->getIterator('\\Fred\\Model\\FredElementCategory', ['theme' => $this->themeId]);

        $elementCategoriesConfig = [];
        foreach ($categories as $category) {
            $c = $this->modx->newQuery('\\Fred\\Model\\FredElementCategoryTemplateAccess');
            $c->where([
                'category' => $category->get('id'),
            ]);
            $c->leftJoin(modTemplate::class, 'Template', 'FredElementTemplateAccess.template = template.id');
            $c->select($this->modx->getSelectColumns(modTemplate::class, 'Template', '', ['templatename']));
            $c->prepare();
            $c->stmt->execute();

            $templateNames = $c->stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

            $elConfig = [
                'uuid' => $category->get('uuid'),
                'name' => $category->get('name'),
                'rank' => $category->get('rank'),
            ];

            if (!empty($templateNames)) {
                $elConfig['templates'] = $templateNames;
            }

            $elementCategoriesConfig[] = $elConfig;

            $this->logger->info(" - {$category->get('name')}");
        }

        return $elementCategoriesConfig;
    }

    private function loadBlueprintCategories()
    {
        $this->logger->notice("Exporting Blueprint Categories");

        $categories = $this->modx->getIterator('\\Fred\\Model\\FredBlueprintCategory', ['theme' => $this->themeId]);

        $elementCategoriesConfig = [];
        foreach ($categories as $category) {
            $c = $this->modx->newQuery('\\Fred\\Model\\FredBlueprintCategoryTemplateAccess');
            $c->where([
                'category' => $category->get('id'),
            ]);
            $c->leftJoin(modTemplate::class, 'Template', 'FredBlueprintCategoryTemplateAccess.template = template.id');
            $c->select($this->modx->getSelectColumns(modTemplate::class, 'Template', '', ['templatename']));
            $c->prepare();
            $c->stmt->execute();

            $templateNames = $c->stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

            $elConfig = [
                'uuid' => $category->get('uuid'),
                'name' => $category->get('name'),
                'rank' => $category->get('rank'),
                'public' => $category->get('public'),
            ];

            if (!empty($templateNames)) {
                $elConfig['templates'] = $templateNames;
            }

            $elementCategoriesConfig[] = $elConfig;

            $this->logger->info(" - {$category->get('name')}");
        }

        return $elementCategoriesConfig;
    }


    private function saveConfig(array $config)
    {
        $packageDir = $this->packagesDir . $this->package->dir_name . DIRECTORY_SEPARATOR;
        $packageDir = rtrim($packageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $parser = new Parser($packageDir);
        $configName = $parser->getConfigFileName('gpm');


        $fredConfigPath = $this->config->paths->build . $configName;
        $rawConfig = FileParser::parseFile($fredConfigPath);
        $configInSeparateFile = false;

        if (is_string($rawConfig['fred'])) {
            $fredConfigPath = $this->configDir . $rawConfig['fred'];
            $rawConfig = FileParser::parseFile($fredConfigPath);
            $configInSeparateFile = true;
        }

        if ($configInSeparateFile) {
            $fredConfig = &$rawConfig;
        } else {
            $fredConfig = &$rawConfig['fred'];
        }

        if (!empty($config['themedTemplates'])) {
            $this->saveThemedTemplates($fredConfig, $config['themedTemplates']);
        }

        if (!empty($config['blueprints'])) {
            $this->saveBlueprints($fredConfig, $config['blueprints']);
        }

        if (!empty($config['elements'])) {
            $this->saveElements($fredConfig, $config['elements']);
        }

        if (!empty($config['optionSets'])) {
            $this->saveOptionSets($fredConfig, $config['optionSets']);
        }

        if (!empty($config['rteConfigs'])) {
            $this->saveRteConfigs($fredConfig, $config['rteConfigs']);
        }

        if (!empty($config['elementCategories'])) {
            $this->saveElementCategories($fredConfig, $config['elementCategories']);
        }

        if (!empty($config['blueprintCategories'])) {
            $this->saveBlueprintCategories($fredConfig, $config['blueprintCategories']);
        }

        FileParser::writeFile($fredConfigPath, $rawConfig);
    }

    private function saveBlueprints(&$fredConfig, array $blueprints)
    {
        $blueprintsPath = $this->config->paths->build . 'fred' . DIRECTORY_SEPARATOR . 'blueprints' . DIRECTORY_SEPARATOR;
        if (!is_dir($blueprintsPath)) {
            mkdir($blueprintsPath, 0777, true);
        }


        $files = glob($blueprintsPath . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $fredConfig['blueprints'] = [];
        foreach ($blueprints as $blueprint) {
            $data = $blueprint['data'];
            unset($blueprint['data']);

            file_put_contents($blueprintsPath . $blueprint['uuid'] . '.json', json_encode($data, JSON_PRETTY_PRINT));

            $fredConfig['blueprints'][] = $blueprint;
        }
    }

    private function saveElements(&$fredConfig, array $elements)
    {
        $baseElementsPath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . 'fred' . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR;

        $fredConfig['elements'] = [];
        foreach ($elements as $element) {
            $content = $element['content'];
            unset($element['content']);

            $contentPath = $baseElementsPath . $element['category'] . DIRECTORY_SEPARATOR;
            if (!is_dir($contentPath)) {
                mkdir($contentPath, 0777, true);
            }

            file_put_contents($contentPath . $element['name'] . '.html', $content);

            $fredConfig['elements'][] = $element;
        }
    }

    private function saveOptionSets(&$fredConfig, array $optionSets)
    {
        $basePath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . 'fred' . DIRECTORY_SEPARATOR . 'optionsets' . DIRECTORY_SEPARATOR;
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        $fredConfig['optionSets'] = [];
        foreach ($optionSets as $element) {
            $data = $element['data'];
            unset($element['data']);

            file_put_contents($basePath . $element['name'] . '.json', json_encode($data, JSON_PRETTY_PRINT));

            $fredConfig['optionSets'][] = $element;
        }
    }

    private function saveRteConfigs(&$fredConfig, array $rteConfigs)
    {
        $basePath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . 'fred' . DIRECTORY_SEPARATOR . 'rteconfigs' . DIRECTORY_SEPARATOR;
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        $fredConfig['rteConfigs'] = [];
        foreach ($rteConfigs as $rteConfig) {
            $data = $rteConfig['data'];
            unset($rteConfig['data']);

            file_put_contents($basePath . $rteConfig['name'] . '.json', json_encode($data, JSON_PRETTY_PRINT));

            $fredConfig['rteConfigs'][] = $rteConfig;
        }
    }

    private function saveElementCategories(&$fredConfig, array $elementCategories)
    {
        $fredConfig['elementCategories'] = [];
        foreach ($elementCategories as $elementCategory) {
            $fredConfig['elementCategories'][] = $elementCategory;
        }
    }

    private function saveBlueprintCategories(&$fredConfig, array $blueprintCategories)
    {
        $fredConfig['blueprintCategories'] = [];
        foreach ($blueprintCategories as $blueprintCategory) {
            $fredConfig['blueprintCategories'][] = $blueprintCategory;
        }
    }

    private function saveThemedTemplates(&$fredConfig, array $themedTemplates)
    {
        $fredConfig['templates'] = [];
        foreach ($themedTemplates as $themedTemplate) {
            $fredConfig['templates'][] = $themedTemplate;
        }
    }

}
