<?php
namespace GPM\Config\Parts\Element;

use MODX\Revolution\modTemplateVar;

class Tv extends Element
{
    /** @var string */
    protected $type = 'templateVar';

    /** @var string */
    protected $extension = '';

    public function getObject(int $category, bool $debug = false): modTemplateVar
    {
        // echo 'TEST';
        // print_r($this->config);
        return $this->prepareObject($category, true, true);
    }

    public function getBuildObject(): modTemplateVar
    {
        return $this->prepareObject(null, false, false);
    }

}
