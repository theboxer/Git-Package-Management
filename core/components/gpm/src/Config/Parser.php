<?php

namespace GPM\Config;

class Parser
{

    /** @var array */
    private $config = [];

    private $packageDir;

    private $buildDir;

    /**
     * Parser constructor.
     *
     * @param  string  $packageDir
     *
     * @throws \Exception
     */
    public function __construct(string $packageDir)
    {
        $this->packageDir = $packageDir;
        $this->buildDir = $this->packageDir . '_build' . DIRECTORY_SEPARATOR;

        $this->config = $this->loadConfigFile('gpm');
    }

    /**
     * @param  string  $name
     *
     * @return array
     * @throws \Exception
     */
    protected function loadConfigFile(string $name): array
    {
        $configFileName = $this->getConfigFileName($name);

        try {
            return FileParser::parseFile($this->buildDir . $configFileName);
        } catch (UnsupportedFileException) {
            throw new \Exception("Unsupported file type for config file $configFileName.");
        } catch (InvalidFileException) {
            throw new \Exception("Config file $configFileName is not valid.");
        }
    }

    /**
     * @param  string  $name
     *
     * @return string
     * @throws \Exception
     */
    public function getConfigFileName(string $name): string
    {
        $validTypes = ['json', 'yaml', 'yml'];

        foreach ($validTypes as $validType) {
            if (file_exists("{$this->buildDir}{$name}.{$validType}")) {
                return $name . '.' . $validType;
            }
        }

        $type = $this->getFileType($name);
        if (in_array($type, $validTypes)) {
            if (file_exists($this->buildDir . $name)) {
                return $name;
            }
        } else {
            $name = $name . '.yml';
        }

        throw new \Exception(
            "Config file not found. Please create {$name} (or any other supported type: " . implode(', ', $validTypes) . ") under the _build folder."
        );
    }

    protected function getFileType(string $fileName): string
    {
        $type = explode('.', $fileName);
        return strtolower(array_pop($type) ?? '');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function parse(): array
    {
        if (empty($this->config)) {
            return [];
        }

        return [
            'paths'          => $this->getPaths(),
            'general'        => $this->getGeneral(),
            'systemSettings' => $this->getSystemSettings(),
            'menus'          => $this->getMenus(),
            'database'       => $this->getDatabase(),
            'snippets'       => $this->getSnippets(),
            'chunks'         => $this->getChunks(),
            'plugins'        => $this->getPlugins(),
            'templates'      => $this->getTemplates(),
            'templateVars'   => $this->getTemplateVars(),
            'categories'     => $this->getCategories(),
            'propertySets'   => $this->getPropertySets(),
            'widgets'        => $this->getWidgets(),
            'build'          => $this->getBuild(),
            'fred'           => $this->getFred(),
        ];
    }

    private function getPaths(): array
    {
        return [
            'package' => $this->packageDir,
        ];
    }

    private function getGeneral(): array
    {
        return [
            'name'        => $this->config['name'] ?? '',
            'lowCaseName' => $this->config['lowCaseName'] ?? '',
            'description' => $this->config['description'] ?? '',
            'author'      => $this->config['author'] ?? '',
            'version'     => $this->config['version'] ?? '',
            'namespace'   => $this->config['namespace'] ?? '',
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getSystemSettings(): array
    {
        $output = [];

        if (!isset($this->config['systemSettings'])) {
            return $output;
        }

        $systemSettings = $this->config['systemSettings'];
        if (is_string($systemSettings)) {
            $systemSettings = $this->loadConfigFile($systemSettings);
        }

        if (!is_array($systemSettings)) {
            return $output;
        }

        foreach ($systemSettings as $systemSetting) {
            if (is_string($systemSetting)) {
                $systemSetting = $this->loadConfigFile($systemSetting);
            }

            if (is_array($systemSetting)) {
                $output[] = $systemSetting;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getMenus(): array
    {
        $output = [];

        if (!isset($this->config['menus'])) {
            return $output;
        }

        $menus = $this->config['menus'];
        if (is_string($menus)) {
            $menus = $this->loadConfigFile($menus);
        }

        if (!is_array($menus)) {
            return $output;
        }

        foreach ($menus as $menu) {
            if (is_string($menu)) {
                $menu = $this->loadConfigFile($menu);
            }

            if (is_array($menu)) {
                $output[] = $menu;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getDatabase(): array
    {
        if (!isset($this->config['database'])) {
            return [];
        }

        $database = $this->config['database'];
        if (is_string($database)) {
            $database = $this->loadConfigFile($database);
        }

        if (is_array($database)) {
            return $database;
        }

        return [];
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getFred()
    {
        $defaults = [
            'theme' => [],
            'elements' => [],
            'templates' => [],
            'elementCategories' => [],
            'optionSets' => [],
            'rteConfigs' => [],
            'blueprintCategories' => [],
        ];

        if (!isset($this->config['fred'])) {
            return null;
        }

        $fred = $this->config['fred'];
        if (is_string($fred)) {
            $fred = $this->loadConfigFile($fred);
        }

        if (!is_array($fred)) {
            return $defaults;
        }

        return array_merge($defaults, $fred);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getSnippets(): array
    {
        $output = [];

        if (!isset($this->config['snippets'])) {
            return $output;
        }

        $snippets = $this->config['snippets'];
        if (is_string($snippets)) {
            $snippets = $this->loadConfigFile($snippets);
        }

        if (!is_array($snippets)) {
            return $output;
        }

        foreach ($snippets as $snippet) {
            if (is_string($snippet)) {
                $snippet = $this->loadConfigFile($snippet);
            }

            if (is_array($snippet)) {
                $snippet['properties'] = $this->getProperties($snippet);

                if (is_string($snippet['category'])) {
                    $snippet['category'] = [$snippet['category']];
                }

                $output[] = $snippet;
            }
        }

        return $output;
    }

    /**
     * @param  array  $element
     *
     * @return array
     * @throws \Exception
     */
    private function getProperties(array $element): array
    {
        if (empty($element['properties'])) {
            return [];
        }
        $properties = $element['properties'];

        if (is_string($properties)) {
            $properties = $this->loadConfigFile($properties);
        }

        if (!is_array($properties)) {
            return [];
        }

        $output = [];

        foreach ($element['properties'] as $property) {
            if (is_string($property)) {
                $property = $this->loadConfigFile($property);
            }

            if (is_array($property)) {
                $output[] = $property;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getChunks(): array
    {
        $output = [];

        if (!isset($this->config['chunks'])) {
            return $output;
        }

        $chunks = $this->config['chunks'];
        if (is_string($chunks)) {
            $chunks = $this->loadConfigFile($chunks);
        }

        if (!is_array($chunks)) {
            return $output;
        }

        foreach ($chunks as $chunk) {
            if (is_string($chunk)) {
                $chunk = $this->loadConfigFile($chunk);
            }

            if (is_array($chunk)) {
                $chunk['properties'] = $this->getProperties($chunk);
                if (is_string($chunk['category'])) {
                    $chunk['category'] = [$chunk['category']];
                }

                $output[] = $chunk;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getPlugins(): array
    {
        $output = [];

        if (!isset($this->config['plugins'])) {
            return $output;
        }

        $plugins = $this->config['plugins'];
        if (is_string($plugins)) {
            $plugins = $this->loadConfigFile($plugins);
        }

        if (!is_array($plugins)) {
            return $output;
        }

        foreach ($plugins as $plugin) {
            if (is_string($plugin)) {
                $plugin = $this->loadConfigFile($plugin);
            }

            if (is_array($plugin)) {
                $plugin['properties'] = $this->getProperties($plugin);
                if (isset($plugin['category']) && is_string($plugin['category'])) {
                    $plugin['category'] = [$plugin['category']];
                }

                if (isset($plugin['events']) && is_array($plugin['events'])) {
                    $events = [];
                    foreach ($plugin['events'] as $event) {
                        if (is_string($event)) {
                            $event = ['name' => $event];
                        }

                        $events[] = $event;
                    }

                    $plugin['events'] = $events;
                }

                $output[] = $plugin;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getTemplates(): array
    {
        $output = [];

        if (!isset($this->config['templates'])) {
            return $output;
        }

        $templates = $this->config['templates'];
        if (is_string($templates)) {
            $templates = $this->loadConfigFile($templates);
        }

        if (!is_array($templates)) {
            return $output;
        }

        foreach ($templates as $template) {
            if (is_string($template)) {
                $template = $this->loadConfigFile($template);
            }

            if (is_array($template)) {
                $template['properties'] = $this->getProperties($template);
                if (is_string($template['category'])) {
                    $template['category'] = [$template['category']];
                }

                $output[] = $template;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getTemplateVars(): array
    {
        $output = [];

        if (!isset($this->config['tvs'])) {
            return $output;
        }

        $templateVars = $this->config['tvs'];
        if (is_string($templateVars)) {
            $templateVars = $this->loadConfigFile($templateVars);
        }

        if (!is_array($templateVars)) {
            return $output;
        }

        foreach ($templateVars as $templateVar) {
            if (is_string($templateVar)) {
                $templateVar = $this->loadConfigFile($templateVar);
            }

            if (is_array($templateVar)) {
                $templateVar['properties'] = $this->getProperties($templateVar);
                if (is_string($templateVar['category'])) {
                    $templateVar['category'] = [$templateVar['category']];
                }

                $output[] = $templateVar;
            }
        }

        return $output;
    }

    /**
     * @param  string|array|null  $children
     *
     * @return array
     * @throws \Exception
     */
    private function getCategories($children = null): array
    {
        $output = [];

        if ($children === null) {
            if (!isset($this->config['categories'])) {
                return $output;
            }

            $categories = $this->config['categories'];
        } else {
            $categories = $children;
        }

        if (is_string($categories)) {
            $categories = $this->loadConfigFile($categories);
        }

        if (!is_array($categories)) {
            return $output;
        }

        foreach ($categories as $category) {
            if (is_string($category)) {
                $category = $this->loadConfigFile($category);
            }

            if (is_array($category)) {
                if (!empty($category['children'])) {
                    $category['children'] = $this->getCategories($category['children']);
                }

                $output[] = $category;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getPropertySets(): array
    {
        $output = [];

        if (!isset($this->config['propertySets'])) {
            return $output;
        }

        $propertySets = $this->config['propertySets'];
        if (is_string($propertySets)) {
            $propertySets = $this->loadConfigFile($propertySets);
        }

        if (!is_array($propertySets)) {
            return $output;
        }

        foreach ($propertySets as $propertySet) {
            if (is_string($propertySet)) {
                $propertySet = $this->loadConfigFile($propertySet);
            }

            if (is_array($propertySet)) {
                $propertySet['properties'] = $this->getProperties($propertySet);
                if (is_string($propertySet['category'])) {
                    $propertySet['category'] = [$propertySet['category']];
                }

                $output[] = $propertySet;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getWidgets(): array
    {
        $output = [];

        if (!isset($this->config['widgets'])) {
            return $output;
        }

        $widgets = $this->config['widgets'];
        if (is_string($widgets)) {
            $widgets = $this->loadConfigFile($widgets);
        }

        if (!is_array($widgets)) {
            return $output;
        }

        foreach ($widgets as $widget) {
            if (is_string($widget)) {
                $widget = $this->loadConfigFile($widget);
            }

            if (is_array($widget)) {
                $widget['properties'] = $this->getProperties($widget);
                $output[] = $widget;
            }
        }

        return $output;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getBuild(): array
    {
        if (!isset($this->config['build'])) {
            return [];
        }

        $build = $this->config['build'];
        if (is_string($build)) {
            $build = $this->loadConfigFile($build);
        }

        if (is_array($build)) {
            return $build;
        }

        return [];
    }

}
