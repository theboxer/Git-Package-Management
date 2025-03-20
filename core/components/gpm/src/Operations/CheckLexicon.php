<?php

namespace GPM\Operations;

use DirectoryIterator;
use Exception;
use FilesystemIterator;
use GPM\Config\Config;
use MODX\Revolution\modLexicon;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CheckLexicon extends Operation
{
    /** @var string $docsPath */
    public $docsPath = null;

    /** @var string $language */
    private $language = null;

    /** @var Config $config */
    private $config = null;

    private $languageKeys = [];
    private $missingKeys = [];
    private $superfluousKeys = [];
    private $variableKeys = [];

    private $invalidLexicons = [];

    public function execute(string $dir): void
    {
        $packages = $this->modx->getOption('gpm.packages_dir');

        $this->config = Config::load($this->modx, $this->logger, $packages . $dir . DIRECTORY_SEPARATOR);
        $this->language = $this->modx->getOption('gitpackagemanagement.default_lexicon', null, 'en');

        /** @var modLexicon $lexicon */
        $lexicon = $this->modx->services->get('lexicon');
        $lexicon->clearCache();

        $this->lexiconPath = $this->config->paths->package . 'core/components/' . $this->config->general->lowCaseName . '/lexicon/';

        $this->addKeys();

        $lexiconEntries = $this->loadLexicons();
        if ($lexiconEntries === false) {
            $this->logger->error('The language folder "' . $this->lexiconPath . $this->language . '/' . '" does not exist!');
            return;
        }

        $this->missingKeys = array_diff($this->languageKeys, array_keys($lexiconEntries));
        $usedKeys = array_intersect($this->languageKeys, array_keys($lexiconEntries));
        $this->superfluousKeys = array_diff(array_keys($lexiconEntries), $usedKeys);

        $msg = [];
        $result = $this->writeKeys('missing');
        if ($result) {
            $msg[] = $result;
        }
        $result = $this->writeKeys('superfluous');
        if ($result) {
            $msg[] = $result;
        }
        $result = $this->writeKeys('variable');
        if ($result) {
            $msg[] = $result;
        }
        if ($this->invalidLexicons) {
            $this->logger->error('The following lexicon files are invalid: ' . implode(', ', $this->invalidLexicons));
        }
        if (empty($msg)) {
            $this->logger->notice('Every lexicon entry is available and no variable keys are used!');
        } else {
            $this->logger->error(implode("<br><br>", $msg));
        }
    }

    /**
     * Load package lexicons
     *
     * @return bool|array
     */
    private function loadLexicons()
    {
        if (file_exists($this->lexiconPath . $this->language . '/')) {
            $_lang = [];
            $iterator = new DirectoryIterator($this->lexiconPath . $this->language . '/');
            foreach ($iterator as $path => $current) {
                if (strpos($current->getFilename(), 'inc.php') !== false) {
                    try {
                        include $current->getRealPath();
                    } catch (Exception $e) {
                        $this->invalidLexicons[] = $current->getFilename();
                    }
                }
            }
            return $_lang;
        } else {
            return false;
        }
    }

    /**
     * Add used lexicon keys
     */
    private function addKeys()
    {
        $directory = new RecursiveDirectoryIterator($this->config->paths->package, FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($directory, function ($current) {
            /** @var RecursiveDirectoryIterator $current */
            if ($current->getFilename()[0] === '.') {
                return false;
            }
            if ($current->isDir()) {
                return ($current->getFilename() !== '_packages' && $current->getFilename() !== 'node_modules' && $current->getFilename() !== 'vendor' && $current->getFilename() !== 'site');
            } else {
                $pathinfo = pathinfo($current->getFilename());
                return $current->isFile() && (
                        $pathinfo['extension'] == 'php' ||
                        $pathinfo['extension'] == 'js' ||
                        $pathinfo['extension'] == 'html' ||
                        $pathinfo['extension'] == 'tpl' ||
                        $pathinfo['basename'] == 'config.json'
                    ) && strpos($pathinfo['basename'], 'min.js') === false;
            }
        });
        $iterator = new RecursiveIteratorIterator($filter);

        foreach ($iterator as $path => $current) {
            $this->addPhpKeys($path);
            $this->addJsKeys($path);
            $this->addChunkKeys($path);
            $this->addSmartyKeys($path);
        }
        $this->addSettingKeys();
        $this->addMenuKeys();
        $this->addSnippetKeys();
        $this->addWidgetKeys();

        $this->languageKeys = array_unique($this->languageKeys);
        sort($this->languageKeys);
    }

    /**
     * Add lexicon calls in php files: modx->lexicon('packageprefix.whatever'
     *
     * @param string $filename
     */
    private function addPhpKeys(string $filename)
    {
        $fileContent = file_get_contents($filename);
        $results = [];
        preg_match_all('/(modx|xpdo)->lexicon\((["\'])((perm.)?' . $this->config->general->lowCaseName . '.*?)\2\s*[,)]/m', $fileContent, $results);
        if (is_array($results[3])) {
            foreach ($results[3] as $result) {
                // Don't add lexicon keys that ends with a dot or an underscore or that contain a variable
                if (substr($result, -1) !== '.' &&
                    substr($result, -1) !== '_'
                ) {
                    if (strpos($result, '$') === false
                    ) {
                        $this->languageKeys[] = $result;
                    } else {
                        $this->variableKeys[] = $result;
                    }
                }
            }
        }
    }

    /**
     * Add lexicon calls in javascript files: _('packageprefix.whatever'
     *
     * @param string $filename
     */
    private function addJsKeys(string $filename)
    {
        $fileContent = file_get_contents($filename);
        $results = [];
        preg_match_all('/_\(([\'"])(' . $this->config->general->lowCaseName . '.*?)\s*[,)]/m', $fileContent, $results);
        if (is_array($results[2])) {
            foreach ($results[2] as $result) {
                // Don't add lexicon keys that ends with a dot or an underscore or that key is concatenated
                if (substr($result, -1) !== '.' &&
                    substr($result, -1) !== '_'
                ) {
                    if (strpos($result, '+') === false
                    ) {
                        $this->languageKeys[] = $result;
                    } else {
                        $this->variableKeys[] = $result;
                    }
                }
            }
        }
    }

    /**
     * Add lexicon calls in chunk files: [[%packageprefix.whatever
     *
     * @param string $filename
     */
    private function addChunkKeys(string $filename)
    {
        $fileContent = file_get_contents($filename);
        $results = [];
        preg_match_all('/\[\[%(' . $this->config->general->lowCaseName . '.*?)[?\]]/m', $fileContent, $results);
        if (is_array($results[1])) {
            foreach ($results[1] as $result) {
                // Don't add lexicon keys that ends with a dot or an underscore or that key contains a setting tag
                if (substr($result, -1) !== '.' &&
                    substr($result, -1) !== '_'
                ) {
                    if (strpos($result, '[[+') === false
                    ) {
                        $this->languageKeys[] = $result;
                    } else {
                        $this->variableKeys[] = $result;
                    }
                }
            }
        }
    }

    /**
     * Add _lang calls in smarty template files: {$_lang.whatever}
     *
     * @param string $filename
     */
    private function addSmartyKeys(string $filename)
    {
        $fileContent = file_get_contents($filename);
        $results = [];
        preg_match_all('/\$_lang\.(.*?)}/m', $fileContent, $results);
        if (is_array($results[1])) {
            foreach ($results[1] as $result) {
                // Don't add lexicon keys that ends with a dot or an underscore or that key contains a setting tag
                if (substr($result, -1) !== '.' &&
                    substr($result, -1) !== '_'
                ) {
                    $this->languageKeys[] = $this->config->general->lowCaseName . '.' . $result;
                }
            }
        }
    }

    /**
     * Add setting language keys
     */
    private function addSettingKeys()
    {
        foreach ($this->config->systemSettings as $setting) {
            $this->languageKeys[] = 'setting_' . $setting->getNamespacedKey();
            $this->languageKeys[] = 'setting_' . $setting->getNamespacedKey() . '_desc';
            if (!in_array($setting->area, [
                'authentication', 'caching', 'file', 'furls', 'gateway',
                'language', 'manager', 'session', 'site', 'system'
            ])) {
                $this->languageKeys[] = 'area_' . $setting->area;
            }
        }
    }

    /**
     * Add menu language keys
     */
    private function addMenuKeys()
    {
        foreach ($this->config->menus as $menu) {
            $this->languageKeys[] = $menu->text;
            $this->languageKeys[] = $menu->description;
        }
    }

    /**
     * Add snippet property language keys
     */
    private function addSnippetKeys()
    {
        foreach ($this->config->snippets as $snippet) {
            $properties = $snippet->getProperties();
            foreach ($properties as $property) {
                $this->languageKeys[] = $this->config->general->lowCaseName . '.' . strtolower($snippet->name) . '.' . $property['name'];
                if (is_array($property['options']) && !empty($property['options'])) {
                    foreach ($property['options'] as $option) {
                        if (strpos($option['text'], $this->config->general->lowCaseName . '.') === 0) {
                            $this->languageKeys[] = $option['text'];
                        }
                    }
                }
            }
        }
    }

    /**
     * Add widget language keys
     */
    private function addWidgetKeys()
    {
        foreach ($this->config->widgets as $widget) {
            $this->languageKeys[] = $widget->name;
            $this->languageKeys[] = $widget->description;
        }
    }

    /**
     * Write missing/superfluous keys to the file _missing.php/_superfluous.php in the language folder
     *
     * @param string $type
     * @return bool|string
     */
    private function writeKeys(string $type)
    {
        switch ($type) {
            case 'superfluous':
                $keys = &$this->superfluousKeys;
                $keysFile = '_superfluous.php';
                break;
            case 'variable':
                $keys = &$this->variableKeys;
                $keysFile = '_variable.php';
                break;
            default:
                $type = 'missing';
                $keys = &$this->missingKeys;
                $keysFile = '_missing.php';
                break;
        }
        if (!empty($keys)) {
            $handle = fopen($this->lexiconPath . $this->language . '/' . $keysFile, 'w');
            if ($handle) {
                fwrite($handle, "<?php\n");
                foreach ($keys as $key) {
                    fwrite($handle, "\$_lang['$key'] = '';\n");
                }
                fclose($handle);
            } else {
                return 'Cannot write to file:  ' . $keysFile;
            }

            return '<strong>The ' . $type . ' keys:</strong> ' . implode(', ', array_values($keys)) . '<br><br>' .
                'They could be found in the file <strong>' . $keysFile . '</strong> in the <strong>' . $this->language . '</strong> lexicon.';
        } else {
            if (file_exists($this->lexiconPath . $this->language . '/' . $keysFile)) {
                unlink($this->lexiconPath . $this->language . '/' . $keysFile);
            }
            return false;
        }
    }
}

