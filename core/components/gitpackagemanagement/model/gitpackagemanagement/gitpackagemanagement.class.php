<?php
/**
 * The base class for GitPackageManagement.
 *
 * @package gitpackagemanagement
 */
class GitPackageManagement {
    /** @var \modX $modx */
    public $modx;
    public $namespace = 'gitpackagemanagement';
    /** @var array $config */
    public $options = array();
    /** @var array $chunks */
    public $chunks = array();
    /** @var string $configPath */
    public $configPath = '/_build/config.json';

    function __construct(modX &$modx,array $options = array()) {
        $this->modx =& $modx;
        $this->namespace = $this->getOption('namespace', $options, 'gitpackagemanagement');

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/gitpackagemanagement/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/gitpackagemanagement/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/gitpackagemanagement/');
        $connectorUrl = $assetsUrl.'connector.php';

        $this->options = array_merge(array(
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'widgetsPath' => $corePath . 'elements/widgets/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
        ),$options);

        $this->modx->addPackage('gitpackagemanagement', $this->getOption('modelPath'));
        $this->modx->lexicon->load('gitpackagemanagement:default');

    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = array(), $default = null) {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

    public function runProcessor($action = '', $scriptProperties = array(), $location = '') {
        $path = $this->getOption('processorsPath');

        return $this->modx->runProcessor($action, $scriptProperties, array(
            'processors_path' => $path,
            'location' => $location,
        ));
    }

    public function stream_copy($src, $dest) {
        $fsrc = fopen($src,'r');
        $fdest = fopen($dest,'w+');

        $len = stream_copy_to_stream($fsrc,$fdest);

        fclose($fsrc);
        fclose($fdest);

        return $len;
    }

    public function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    $this->stream_copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function deleteDir($dirPath) {
        if (is_dir($dirPath)) {
            $files = scandir($dirPath);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($dirPath . '/' . $file))
                        $this->deleteDir($dirPath . '/' . $file);
                    else
                        unlink($dirPath . '/' . $file);
                }
            }
            rmdir($dirPath);
        }
    }

    public function jsonFormat($json) {
        if (!is_string($json)) {
            if (phpversion() && phpversion() >= 5.4) {
                return json_encode($json, JSON_PRETTY_PRINT);
            }
            $json = json_encode($json);
        }
        $result      = '';
        $pos         = 0;               // indentation level
        $strLen      = strlen($json);
        $indentStr   = "\t";
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;
        for ($i = 0; $i < $strLen; $i++) {
            // Speedup: copy blocks of input which don't matter re string detection and formatting.
            $copyLen = strcspn($json, $outOfQuotes ? " \t\r\n\",:[{}]" : "\\\"", $i);
            if ($copyLen >= 1) {
                $copyStr = substr($json, $i, $copyLen);
                // Also reset the tracker for escapes: we won't be hitting any right now
                // and the next round is the first time an 'escape' character can be seen again at the input.
                $prevChar = '';
                $result .= $copyStr;
                $i += $copyLen - 1;      // correct for the for(;;) loop
                continue;
            }

            // Grab the next character in the string
            $char = substr($json, $i, 1);

            // Are we inside a quoted string encountering an escape sequence?
            if (!$outOfQuotes && $prevChar === '\\') {
                // Add the escaped character to the result string and ignore it for the string enter/exit detection:
                $result .= $char;
                $prevChar = '';
                continue;
            }
            // Are we entering/exiting a quoted string?
            if ($char === '"' && $prevChar !== '\\') {
                $outOfQuotes = !$outOfQuotes;
            }
            // If this character is the end of an element,
            // output a new line and indent the next line
            else if ($outOfQuotes && ($char === '}' || $char === ']')) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            // eat all non-essential whitespace in the input as we do our own here and it would only mess up our process
            else if ($outOfQuotes && false !== strpos(" \t\r\n", $char)) {
                continue;
            }
            // Add the character to the result string
            $result .= $char;
            // always add a space after a field colon:
            if ($outOfQuotes && $char === ':') {
                $result .= ' ';
            }
            // If the last character was the beginning of an element,
            // output a new line and indent the next line
            else if ($outOfQuotes && ($char === ',' || $char === '{' || $char === '[')) {
                $result .= $newLine;
                if ($char === '{' || $char === '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            $prevChar = $char;
        }
        return $result;
    }

    public function findCategory(array $path, $root)
    {
        $currentParent = $root;
        $category = null;
        foreach ($path as $name) {
            $category = $this->modx->getObject('modCategory', array('parent' => $currentParent, 'category' => $name));
            $currentParent = $category->id;
        }

        return $category->id;
    }
}