<?php
namespace GPM\Config\Parts\Element;

use MODX\Revolution\modChunk;

class Chunk extends Element
{
    /** @var string */
    protected $_type = 'chunk';

    /** @var string */
    protected $extension = 'tpl';

    public function getObject(int $category, bool $debug = false): modChunk
    {
        return $this->prepareObject($category, true, true);
    }

    public function getBuildObject(): modChunk
    {
        return $this->prepareObject(null, false, false);
    }

}
