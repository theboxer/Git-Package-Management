<?php
namespace GPM\Config\Parts;

use GPM\Config\Rules;
use MODX\Revolution\modDashboardWidget;

/**
 * Class PropertySet
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string $type
 * @property-read string $content
 * @property-read string $lexicon
 * @property-read string $size
 * @property-read string $permission
 * @property-read string $filePath
 * @property-read string $absoluteFilePath
 * @property-read array $properties
 *
 * @package GPM\Config\Parts
 */
class Widget extends Part
{
    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $type = '';

    /** @var string */
    protected $content = '';

    /** @var string */
    protected $lexicon = '';

    /** @var string */
    protected $size = '';

    /** @var string */
    protected $permission = '';

    /** @var string */
    protected $filePath = '';

    /** @var string */
    protected $absoluteFilePath = '';

    /** @var array */
    protected $properties = [];

    protected $rules = [
        'name' => [Rules::notEmpty, Rules::isString],
        'content' => [Rules::widgetContent],
        'type' => [Rules::isString, ['rule' => Rules::isEnum, 'params' => ['html', 'file', 'snippet', 'php']]],
        'size' => [Rules::isString, Rules::notEmpty, ['rule' => Rules::isEnum, 'params' => ['quarter', 'one-third', 'half', 'two-third', 'three-quarters', 'full', 'double']]]
    ];

    public static function getSkipProperties(): array
    {
        return array_merge(parent::getSkipProperties(), ['filePath', 'absoluteFilePath']);
    }

    protected function generator(): void
    {
        parent::generator();

        if (empty($this->size)) {
            $this->size = 'half';
        }

        if (empty($this->type)) {
            $this->type = 'file';
        }

        if (empty($this->content) && ($this->type === 'file')) {
            $this->content = $this->name . '.php';
        }

        if (($this->type === 'file') && (!empty($this->content))) {
            $this->filePath = 'elements' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR . $this->content;
            $this->absoluteFilePath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR . $this->content;
        }

        if (empty($this->lexicon)) {
            $this->lexicon = $this->config->general->lowCaseName . ':default';
        }
    }

    protected function prepareObject(bool $build = false): modDashboardWidget
    {
        /** @var \MODX\Revolution\modDashboardWidget $obj */
        $obj = null;

        if (!$build) {
            $obj = $this->config->modx->getObject(modDashboardWidget::class, ['name' => $this->name, 'namespace' => $this->config->general->lowCaseName]);
        }

        if ($obj === null) {
            $obj = $this->config->modx->newObject(modDashboardWidget::class);
            $obj->set('name', $this->name);
            $obj->set('namespace', $this->config->general->lowCaseName);
        }

        $obj->set('description', $this->description);
        $obj->set('type', $this->type);
        if ($this->type === 'file') {
            if (!$build) {
                $obj->set('content', $this->absoluteFilePath);
            } else {
                $obj->set('content', '[[++core_path]]components' . DIRECTORY_SEPARATOR . $this->config->general->lowCaseName . DIRECTORY_SEPARATOR . $this->filePath);
            }
        } else {
            $obj->set('content', $this->content);
        }

        $obj->set('properties', $this->properties);
        $obj->set('lexicon', $this->lexicon);
        $obj->set('size', $this->size);
        $obj->set('permission', $this->permission);

        return $obj;
    }

    public function getObject(): modDashboardWidget
    {
        return $this->prepareObject(false);
    }

    public function getBuildObject(): modDashboardWidget
    {
        return $this->prepareObject(true);
    }
}
