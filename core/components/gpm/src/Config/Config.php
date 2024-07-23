<?php
namespace GPM\Config;

use MODX\Revolution\modX;
use Psr\Log\LoggerInterface;

/**
 * Class Config
 *
 * @property-read modX $modx;
 * @property-read Parts\Paths $paths;
 * @property-read Parts\General $general;
 * @property-read Parts\SystemSetting[] $systemSettings;
 * @property-read Parts\Menu[] $menus;
 * @property-read Parts\Database $database;
 * @property-read Parts\Build $build;
 * @property-read Parts\Element\Snippet[] $snippets;
 * @property-read Parts\Element\Chunk[] $chunks;
 * @property-read Parts\Element\Plugin[] $plugins;
 * @property-read Parts\Element\Template[] $templates;
 * @property-read Parts\Element\TemplateVar[] $templateVars;
 * @property-read Parts\Element\Category[] $categories;
 * @property-read Parts\PropertySet[] $propertySets;
 * @property-read Parts\Widget[] $widgets;
 * @property-read Parts\Fred $fred;
 *
 * @package GPM\Config
 */
class Config
{
    /** @var Parts\Paths */
    private $paths;

    /** @var Parts\General */
    private $general;

    /** @var Parts\SystemSetting[] */
    private $systemSettings = [];

    /** @var Parts\Menu[] */
    private $menus = [];

    /** @var Parts\Database */
    private $database;

    /** @var Parts\Element\Snippet[] */
    private $snippets = [];

    /** @var Parts\Element\Plugin[] */
    private $plugins = [];

    /** @var Parts\Element\Chunk[] */
    private $chunks = [];

    /** @var Parts\Element\Template[] */
    private $templates = [];

    /** @var Parts\Element\TemplateVar[] */
    private $templateVars = [];

    /** @var Parts\Element\Category[] */
    private $categories = [];

    /** @var Parts\PropertySet[] */
    private $propertySets = [];

    /** @var Parts\Widget[] */
    private $widgets = [];

    /** @var Parts\Build */
    private $build;

    /** @var Parts\Fred */
    private $fred;

    /** @var modX */
    private $modx;

    /**
     * @param  string  $packageDir
     *
     * @return array
     * @throws \Exception
     */
    public static function parseConfig(string $packageDir): array
    {
        $packageDir  = rtrim($packageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($packageDir)) {
            throw new \Exception('Package directory doesn\'t exist.');
        }

        $parser = new Parser($packageDir);
        return $parser->parse();
    }

    /**
     * @param  \MODX\Revolution\modX  $modx
     * @param  \Psr\Log\LoggerInterface  $logger
     * @param $cfg
     *
     * @return \GPM\Config\Config
     * @throws \Exception
     */
    public static function load(modX $modx, LoggerInterface $logger, $cfg): Config
    {
        if (is_string($cfg)) {
            $cfg = self::parseConfig($cfg);
        }

        if (!is_array($cfg)) {
            throw new \Exception('Config is not valid');
        }

        $config = new self($modx);

        $config->general = new Parts\General($cfg['general'], $config);
        $config->paths = new Parts\Paths($cfg['paths'], $config);
        $config->build = new Parts\Build($cfg['build'], $config);
        $config->database = new Parts\Database($cfg['database'], $config);

        foreach ($cfg['systemSettings'] as $systemSetting) {
            $config->systemSettings[] = new Parts\SystemSetting($systemSetting, $config);
        }

        foreach ($cfg['menus'] as $menu) {
            $config->menus[] = new Parts\Menu($menu, $config);
        }

        foreach ($cfg['categories'] as $category) {
            $config->categories[] = new Parts\Element\Category($category, $config);
        }

        foreach ($cfg['snippets'] as $snippet) {
            $config->snippets[] = new Parts\Element\Snippet($snippet, $config);
        }

        foreach ($cfg['chunks'] as $chunk) {
            $config->chunks[] = new Parts\Element\Chunk($chunk, $config);
        }

        foreach ($cfg['plugins'] as $plugin) {
            $config->plugins[] = new Parts\Element\Plugin($plugin, $config);
        }

        foreach ($cfg['templates'] as $template) {
            $config->templates[] = new Parts\Element\Template($template, $config);
        }

        foreach ($cfg['templateVars'] as $templateVar) {
            $config->templateVars[] = new Parts\Element\TemplateVar($templateVar, $config);
        }

        foreach ($cfg['propertySets'] as $propertySet) {
            $config->propertySets[] = new Parts\PropertySet($propertySet, $config);
        }

        foreach ($cfg['widgets'] as $widget) {
            $config->widgets[] = new Parts\Widget($widget, $config);
        }

        $config->fred = new Parts\Fred($cfg['fred'], $config);

        $valid = $config->validate($logger);
        if (!$valid) {
            throw new \Exception('Config is not valid');
        }

        return $config;
    }

    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return null;
    }

    public function __set(string $property, $value)
    {
        return $value;
    }

    public function __isset(string $property): bool
    {
        return property_exists($this, $property);
    }

    public function setModx(modX $modx): void
    {
        $this->modx = $modx;
    }

    private function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    private function validate(LoggerInterface $logger): bool
    {
        $validator = new Validator($logger, $this);
        return $validator->validateConfig();
    }

    public function __sleep(): array
    {
        return [
            'paths',
            'general',
            'systemSettings',
            'menus',
            'database',
            'snippets',
            'chunks',
            'plugins',
            'templates',
            'templateVars',
            'categories',
            'propertySets',
            'widgets',
            'fred',
        ];
    }

    public static function wakeMe(string $data, modX $modx): Config
    {
        /** @var Config $config */
        $config = unserialize($data);
        $config->setModx($modx);

        $config->paths->setConfig($config);
        $config->general->setConfig($config);
        $config->database->setConfig($config);

        foreach ($config->menus as $menu) {
            $menu->setConfig($config);
        }

        foreach ($config->systemSettings as $systemSetting) {
            $systemSetting->setConfig($config);
        }

        foreach ($config->snippets as $snippet) {
            $snippet->setConfig($config);
        }

        foreach ($config->plugins as $plugin) {
            $plugin->setConfig($config);
        }

        foreach ($config->chunks as $chunk) {
            $chunk->setConfig($config);
        }

        foreach ($config->templates as $template) {
            $template->setConfig($config);
        }

        foreach ($config->templateVars as $templateVar) {
            $templateVar->setConfig($config);
        }

        foreach ($config->categories as $category) {
            $category->setConfig($config);
        }

        foreach ($config->propertySets as $propertySet) {
            $propertySet->setConfig($config);
        }

        foreach ($config->widgets as $widget) {
            $widget->setConfig($config);
        }

        if ($config->fred) {
            $config->fred->setConfig($config);
        }

        return $config;
    }

    public function toArray(): array
    {
        $vars = get_object_vars($this);
        unset($vars['modx']);

        return $vars;
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
