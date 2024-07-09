<?php
namespace GPM\Config\Parts;

use GPM\Config\Config;
use GPM\Config\FileParser;
use GPM\Config\Parser;
use GPM\Config\Parts\Fred\Blueprint;
use GPM\Config\Parts\Fred\BlueprintCategory;
use GPM\Config\Parts\Fred\Element;
use GPM\Config\Parts\Fred\ElementCategory;
use GPM\Config\Parts\Fred\OptionSet;
use GPM\Config\Parts\Fred\RteConfig;
use GPM\Config\Parts\Fred\Template;
use GPM\Config\Parts\Fred\Theme;
use GPM\Config\Rules;
use MODX\Revolution\modTemplate;

/**
 * Class Database
 *
 * @property-read Theme $theme
 * @property-read ElementCategory[] $elementCategories
 * @property-read BlueprintCategory[] $blueprintCategories
 * @property-read Element[] $elements
 * @property-read Blueprint[] $blueprints
 * @property-read OptionSet[] $optionSets
 * @property-read Template[] $templates
 * @property-read RteConfig[] $rteConfigs
 *
 * @package GPM\Config\Parts
 */
class Fred extends Part
{
    /** @var int */
    private $themeId = 0;

    /** @var array  */
    private $elementCategoriesMap = [];

    /** @var array  */
    private $blueprintCategoriesMap = [];

    /** @var array */
    private $elementsPerCategryMap = [];

    /** @var array */
    private $blueprintsPerCategryMap = [];

    /** @var array */
    private $blueprintsUuidMap = [];

    /** @var array  */
    private $blueprintsMap = [];

    /** @var array  */
    private $optionSetsMap = [];

    /** @var array  */
    private $templatesMap = [];

    /** @var Theme */
    protected $theme;

    /** @var ElementCategory[] */
    protected $elementCategories = [];

    /** @var BlueprintCategory[] */
    protected $blueprintCategories = [];

    /** @var Element[] */
    protected $elements = [];

    /** @var Blueprint[] */
    protected $blueprints = [];

    /** @var OptionSet[] */
    protected $optionSets = [];

    /** @var Template[] */
    protected $templates = [];

    /** @var RteConfig[] */
    protected $rteConfigs = [];

    protected $rules = [
        'elementCategories' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'blueprintCategories' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'elements' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'blueprints' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'optionSets' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'templates' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'rteConfigs' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'theme' => [Rules::configPart]
    ];

    protected function generator(): void
    {
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);

        if ($this->theme) {
            $this->theme->setConfig($config);
        }

        foreach ($this->elementCategories as $elementCategory) {
            $elementCategory->setConfig($config);
        }

        foreach ($this->elements as $element) {
            $element->setConfig($config);
        }

        foreach ($this->blueprints as $blueprint) {
            $blueprint->setConfig($config);
        }

        foreach ($this->optionSets as $optionSEt) {
            $optionSEt->setConfig($config);
        }

        foreach ($this->templates as $template) {
            $template->setConfig($config);
        }

        foreach ($this->rteConfigs as $rteConfig) {
            $rteConfig->setConfig($config);
        }

        foreach ($this->blueprintCategories as $blueprintCategory) {
            $blueprintCategory->setConfig($config);
        }
    }

    public function getThemeId(): int
    {
        if (!empty($this->themeId)) return $this->themeId;

        $where = empty($this->config->fred->theme->uuid) ? ['name' => $this->config->general->name] : ['uuid' => $this->config->fred->theme->uuid];

        /** @var \Fred\Model\FredTheme $obj */
        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredTheme', $where);
        if (!$obj) return $this->themeId;

        $this->themeId = $obj->get('id');

        return $this->themeId;
    }

    public function getElementCategoryId($categoryName): int
    {
        if (!empty($this->elementCategoriesMap[$categoryName])) return $this->elementCategoriesMap[$categoryName];

        /** @var \Fred\Model\FredElementCategory $category */
        $category = $this->config->modx->getObject('\\Fred\\Model\\FredElementCategory', ['name' => $categoryName, 'theme' => $this->getThemeId()]);
        if (!$category) return 0;

        $this->elementCategoriesMap[$categoryName] = $category->get('id');

        return $this->elementCategoriesMap[$categoryName];
    }

    public function getBlueprintCategoryId($categoryName): int
    {
        if (!empty($this->blueprintCategoriesMap[$categoryName])) return $this->blueprintCategoriesMap[$categoryName];

        /** @var \Fred\Model\FredElementCategory $category */
        $category = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprintCategory', ['name' => $categoryName, 'theme' => $this->getThemeId()]);
        if (!$category) return 0;

        $this->blueprintCategoriesMap[$categoryName] = $category->get('id');

        return $this->blueprintCategoriesMap[$categoryName];
    }

    public function getOptionSetId($optionSetName): int
    {
        if (!empty($this->optionSetsMap[$optionSetName])) return $this->optionSetsMap[$optionSetName];

        /** @var \Fred\Model\FredElementOptionSet $optionSet */
        $optionSet = $this->config->modx->getObject('\\Fred\\Model\\FredElementOptionSet', ['name' => $optionSetName, 'theme' => $this->getThemeId()]);
        if (!$optionSet) return 0;

        $this->optionSetsMap[$optionSetName] = $optionSet->get('id');

        return $this->optionSetsMap[$optionSetName];
    }

    public function getTemplateId($templateName): int
    {
        if (!empty($this->templatesMap[$templateName])) return $this->templatesMap[$templateName];

        /** @var \Fred\Model\FredElementOptionSet $template */
        $template = $this->config->modx->getObject(modTemplate::class, ['templatename' => $templateName]);
        if (!$template) return 0;

        $this->templatesMap[$templateName] = $template->get('id');

        return $this->templatesMap[$templateName];
    }

    public function getBlueprintId($name): int
    {
        if (!empty($this->blueprintsMap[$name])) return $this->blueprintsMap[$name];

        /** @var \Fred\Model\FredBlueprint $blueprint */
        $blueprint = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprint', ['name' => $name]);
        if (!$blueprint) return 0;

        $this->blueprintsMap[$name] = $blueprint->get('id');

        return $this->blueprintsMap[$name];
    }

    public function getBlueprintUuid($name): string
    {
        if (empty($this->blueprintsUuidMap)) {
            foreach ($this->config->fred->blueprints as $blueprint) {
                $this->blueprintsUuidMap[$blueprint->name] = $blueprint->uuid;
            }
        }

        if (empty($this->blueprintsUuidMap[$name])) {
            throw new \Exception("Blueprint \"$name\" not found");
        }

        return $this->blueprintsUuidMap[$name];
    }

    protected function setElementCategories(array $elementCategories): void
    {
        foreach ($elementCategories as $elementCategory) {
            $this->elementCategories[] = new ElementCategory($elementCategory, $this->config);
        }
    }

    protected function setElements(array $elements): void
    {
        foreach ($elements as $element) {
            $this->elements[] = new Element($element, $this->config);
        }
    }

    protected function setOptionSets(array $optionSets): void
    {
        foreach ($optionSets as $optionSet) {
            $this->optionSets[] = new OptionSet($optionSet, $this->config);
        }
    }

    protected function setTemplates(array $templates): void
    {
        foreach ($templates as $template) {
            $this->templates[] = new Template($template, $this->config);
        }
    }

    protected function setRteConfigs(array $rteConfigs): void
    {
        foreach ($rteConfigs as $rteConfig) {
            $this->rteConfigs[] = new RteConfig($rteConfig, $this->config);
        }
    }

    protected function setBlueprintCategories(array $blueprintCategoryConfigs): void
    {
        foreach ($blueprintCategoryConfigs as $blueprintCategoryConfig) {
            $this->blueprintCategories[] = new BlueprintCategory($blueprintCategoryConfig, $this->config);
        }
    }

    protected function setBlueprints(array $blueprintConfigs): void
    {
        foreach ($blueprintConfigs as $blueprintConfig) {
            $this->blueprints[] = new Blueprint($blueprintConfig, $this->config);
        }
    }

    protected function setTheme(array $themeConfig): void
    {
        $this->theme = new Theme($themeConfig, $this->config);
    }

    public function syncUuids(): void
    {
        $packageDir = $this->config->paths->package;
        $configDir = $this->config->paths->build;

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


        foreach ($this->elementCategories as $index => $elCategory) {
            $fredConfig['elementCategories'][$index]['uuid'] = $elCategory->uuid;
        }

        foreach ($this->elements as $index => $el) {
            $fredConfig['elements'][$index]['uuid'] = $el->uuid;
        }

        foreach ($this->blueprintCategories as $index => $cat) {
            $fredConfig['blueprintCategories'][$index]['uuid'] = $cat->uuid;
        }

        $fredConfig['theme']['uuid'] = $this->theme->uuid;

        FileParser::writeFile($fredConfigPath, $rawConfig);
    }

    /**
     * @param $name
     * @return Element[]
     */
    public function getElementsForCategory($name)
    {
        if (empty($this->elementsPerCategryMap)) {
            $this->initElementsPerCategoryMap();
        }

        if (!isset($this->elementsPerCategryMap[$name])) return [];

        return $this->elementsPerCategryMap[$name];
    }

    private function initElementsPerCategoryMap(): void
    {
        foreach ($this->elements as $el) {
            if (empty($this->elementsPerCategryMap[$el->category])) {
                $this->elementsPerCategryMap[$el->category] = [];
            }

            $this->elementsPerCategryMap[$el->category][] = $el;
        }
    }

    /**
     * @param $name
     * @return Blueprint[]
     */
    public function getBlueprintsForCategory($name)
    {
        if (empty($this->blueprintsPerCategryMap)) {
            $this->initBlueprintsPerCategoryMap();
        }

        if (!isset($this->blueprintsPerCategryMap[$name])) return [];

        return $this->blueprintsPerCategryMap[$name];
    }

    private function initBlueprintsPerCategoryMap(): void
    {
        foreach ($this->blueprints as $blueprint) {
            if (empty($this->blueprintsPerCategryMap[$blueprint->category])) {
                $this->blueprintsPerCategryMap[$blueprint->category] = [];
            }

            $this->blueprintsPerCategryMap[$blueprint->category][] = $blueprint;
        }
    }
}
