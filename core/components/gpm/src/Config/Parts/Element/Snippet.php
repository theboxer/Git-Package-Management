<?php
namespace GPM\Config\Parts\Element;

use MODX\Revolution\modSnippet;

class Snippet extends Element
{
    /** @var string */
    protected $type = 'snippet';

    /** @var string */
    protected $extension = 'php';

    public function getObject(int $category, bool $debug = false): modSnippet
    {
        return $this->prepareObject($category, true, true, $debug);
    }

    public function getBuildObject(): modSnippet
    {
        return $this->prepareObject(null, false, false, false);
    }
}
