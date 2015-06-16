<?php
namespace GPM\Config\Loader;

use Symfony\Component\Finder\Finder;

final class File implements iLoader
{
    /** @var string */
    private $path = '';
    /** @var string */
    private $name = '';

    /**
     * @param string $path
     * @param string $name
     */
    public function __construct($path, $name)
    {
        $this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        $this->name = $name;
    }

    /**
     * Load general info about packge
     *
     * @return array
     */
    public function loadGeneral()
    {
        return [];
    }

    /**
     * Load Actions
     *
     * @return array
     */
    public function loadActions()
    {
        return [];
    }

    /**
     * Load Menus
     *
     * @return array
     */
    public function loadMenus()
    {
        return [];
    }

    /**
     * Load Categories
     *
     * @return array
     */
    public function loadCategories()
    {
        return [];
    }
    
    /**
     * Load Plugins
     *
     * @return array
     */
    public function loadPlugins()
    {
        return [];
    }

    public function loadSnippets()
    {
        $scannedDir = $this->path . '/core/components/' . $this->name . '/elements/snippets/';

        $finder = new Finder();

        /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
        $files = $finder->files()->in($scannedDir)->name('*.php');

        $snippets = [];

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $snippetName = explode('.', $fileName);
            array_pop($snippetName);
            if (count($snippetName) > 1) {
                if ($snippetName[count($snippetName) - 1] == 'snippet') {
                    array_pop($snippetName);
                }
            }
            $snippetName = implode('.', $snippetName);
            $category = '';
            $path = $file->getRelativePath();
            if (!empty($path)) {
                $path = explode(DIRECTORY_SEPARATOR, $path);
                $category = array_pop($path);
            }

            $snippetArray = [
                'name' => $snippetName,
                'file' => $fileName,
                'category' => $category,
            ];

            $snippets[] = $snippetArray;
        }

        return $snippets;
    }

    /**
     * Load Chunks
     *
     * @return array
     */
    public function loadChunks()
    {
        return [];
    }

    /**
     * Load Templates
     *
     * @return array
     */
    public function loadTemplates()
    {
        return [];
    }

    /**
     * Load TVs
     *
     * @return array
     */
    public function loadTVs()
    {
        return [];
    }

    /**
     * Load Resources
     *
     * @return array
     */
    public function loadResources()
    {
        return [];
    }

    /**
     * Load System settings
     *
     * @return array
     */
    public function loadSystemSettings()
    {
        return [];
    }

    /**
     * Load Database
     *
     * @return array
     */
    public function loadDatabase()
    {
        return [];
    }

    /**
     * Load Extension packages
     *
     * @return array
     */
    public function loadExtensionPackage()
    {
        return [];
    }

    /**
     * Load Build
     *
     * @return array
     */
    public function loadBuild()
    {
        return [];
    }
    
    /**
     * Load Dependencies
     *
     * @return array
     */
    public function loadDependencies()
    {
        return [];
    }
}