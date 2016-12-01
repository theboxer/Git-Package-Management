<?php
namespace GPM\Config\Loader;

use GPM\Config\Object\General;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;
use Symfony\Component\Finder\Finder;

final class File implements iLoader
{
    /** @var Parser */
    private $parser;
    
    /** @var string */
    private $path = '';
    
    /** @var string */
    private $name = '';

    /**
     * @param Parser $parser
     * @param null $path
     * @throws \Exception
     */
    public function __construct(Parser $parser, $path = null)
    {
        if ($parser->config->general === null) throw new \Exception('Load General part first');

        $this->parser = $parser;
        if ($path === null) {
            $this->path = rtrim($parser->config->packagePath, '/\\') . DIRECTORY_SEPARATOR;
        } else {
            $this->path = $path;
        }
        $this->name = $parser->config->general->lowCaseName;
    }

    /**
     * Load Snippets
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadSnippets($skip = true)
    {
        $scannedDir = $this->path . '/core/components/' . $this->name . '/elements/snippets/';

        $finder = new Finder();

        /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
        $files = $finder->files()->in($scannedDir)->name('*.php');

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

            $this->parser->parseSnippet([
                'name' => $snippetName,
                'file' => $fileName,
                'category' => $category,
            ], $skip);
        }

        return true;
    }

    /**
     * Load general info about package
     *
     * @param bool $skip
     * @return bool
     */
    public function loadGeneral($skip = true)
    {
        return true;
    }

    /**
     * Load Actions
     *
     * @param bool $skip
     * @return bool
     */
    public function loadActions($skip = true)
    {
        return true;
    }

    /**
     * Load Menus
     *
     * @param bool $skip
     * @return bool
     */
    public function loadMenus($skip = true)
    {
        return true;
    }

    /**
     * Load Categories
     *
     * @param bool $skip
     * @return bool
     */
    public function loadCategories($skip = true)
    {
        return true;
    }

    /**
     * Load Plugins
     *
     * @param bool $skip
     * @return bool
     */
    public function loadPlugins($skip = true)
    {
        return true;
    }

    /**
     * Load Chunks
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadChunks($skip = true)
    {
        $scannedDir = $this->path . '/core/components/' . $this->name . '/elements/chunks/';

        $finder = new Finder();

        /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
        $files = $finder->files()->in($scannedDir)->name('*.tpl');

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $chunkName = explode('.', $fileName);
            array_pop($chunkName);
            if (count($chunkName) > 1) {
                if ($chunkName[count($chunkName) - 1] == 'chunk') {
                    array_pop($chunkName);
                }
            }
            $chunkName = implode('.', $chunkName);
            $category = '';
            $path = $file->getRelativePath();
            if (!empty($path)) {
                $path = explode(DIRECTORY_SEPARATOR, $path);
                $category = array_pop($path);
            }

            $this->parser->parseChunk([
                'name' => $chunkName,
                'file' => $fileName,
                'category' => $category,
            ], $skip);
        }

        return true;
    }

    /**
     * Load Templates
     *
     * @param bool $skip
     * @return bool
     */
    public function loadTemplates($skip = true)
    {
        return true;
    }

    /**
     * Load TVs
     *
     * @param bool $skip
     * @return bool
     */
    public function loadTVs($skip = true)
    {
        return true;
    }

    /**
     * Load Resources
     *
     * @param bool $skip
     * @return bool
     */
    public function loadResources($skip = true)
    {
        return true;
    }

    /**
     * Load System settings
     *
     * @param bool $skip
     * @return bool
     */
    public function loadSystemSettings($skip = true)
    {
        return true;
    }

    /**
     * Load Database
     *
     * @param bool $skip
     * @return bool
     */
    public function loadDatabase($skip = true)
    {
        return true;
    }

    /**
     * Load Extension package
     *
     * @param bool $skip
     * @return bool
     */
    public function loadExtensionPackage($skip = true)
    {
        return true;
    }

    /**
     * Load Build
     *
     * @param bool $skip
     * @return bool
     */
    public function loadBuild($skip = true)
    {
        return true;
    }

    /**
     * Load Dependencies
     *
     * @param bool $skip
     * @return bool
     */
    public function loadDependencies($skip = true)
    {
        return true;
    }
}