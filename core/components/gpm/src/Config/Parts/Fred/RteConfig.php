<?php
namespace GPM\Config\Parts\Fred;

use GPM\Config\Config;
use GPM\Config\FileParser;
use GPM\Config\Parts\Part;
use GPM\Config\Rules;

/**
 * Class Category
 *
 * @property-read string $name
 * @property-read string $description
 *
 * @property-read string $file
 * @property-read array | null $content
 * @property-read string $absoluteFilePath
 *
 * @package GPM\Config\Parts\Element
 */
class RteConfig extends Part
{
    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $file = '';

    /** @var string | array */
    protected $content = null;

    /** @var string */
    protected $absoluteFilePath = '';


    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'description' => [Rules::isString],
        'file' => [Rules::isString, Rules::notEmpty, Rules::elementFileExists],
    ];

    protected static $fileExtensions = [
        '.json',
        '.yaml',
        '.yml',
    ];

    protected function generator(): void
    {
        $baseElementsPath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . 'fred' . DIRECTORY_SEPARATOR . 'rteconfigs' . DIRECTORY_SEPARATOR;

        if (!empty($this->name) && empty($this->file)) {
            $this->file = $this->name . '.json';
        }

        if (!empty($this->file) && file_exists($baseElementsPath . $this->file)) {
            $this->absoluteFilePath = $baseElementsPath . $this->file;
        } else {
            foreach (self::$fileExtensions as $fileExtension) {
                if (file_exists($baseElementsPath . $this->name . $fileExtension)) {
                    $this->file = $this->name . $fileExtension;
                    $this->absoluteFilePath = $baseElementsPath . $this->name . $fileExtension;
                    break;
                }
            }
        }
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);
    }

    protected function prepareObject()
    {
        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredElementRTEConfig', ['name' => $this->name, 'theme' => $this->config->fred->getThemeId()]);

        if ($obj === null) {
            $obj = $this->config->modx->newObject('\\Fred\\Model\\FredElementRTEConfig');
            $obj->set('name', $this->name);
            $obj->set('theme', $this->config->fred->getThemeId());
        }

        $obj->set('description', $this->description);

        if ($this->content !== null) {
            $obj->set('content', $this->content);
        } else {
            $obj->set('content', FileParser::parseFile($this->absoluteFilePath));
        }

        return $obj;
    }

    public function deleteObject(): bool {
        $toDelete = $this->config->modx->getObject('\\Fred\\Model\\FredElementRTEConfig', ['name' => $this->name, 'theme' => $this->config->fred->getThemeId()]);
        if ($toDelete) {
            return $toDelete->remove();
        }

        return false;
    }

    public function getObject()
    {
        return $this->prepareObject();
    }
}
