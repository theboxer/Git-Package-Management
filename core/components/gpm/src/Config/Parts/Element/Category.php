<?php
namespace GPM\Config\Parts\Element;

use GPM\Config\Config;
use GPM\Config\Parts\Part;
use Psr\Log\LoggerInterface;

/**
 * Class General
 *
 * @property-read string $name
 * @property-read integer $rank
 * @property-read Category[] $children
 *
 * @package GPM\Config\Parts
 */
class Category extends Part
{
    /** @var string */
    protected $name = '';

    /** @var integer */
    protected $rank = 0;

    /** @var Category[] */
    protected $children = [];

    protected function generator(): void
    {
    }

    protected function setChildren(array $children): void
    {
        foreach ($children as $category) {
            $this->children[] = new Category($category, $this->config);
        }
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);

        foreach ($this->children as $category) {
            $category->setConfig($config);
        }
    }

    public function validate(LoggerInterface $logger): bool
    {
        $valid = true;
        if (empty($this->name)) {
            $valid = false;
            $logger->error("Categories - name is required");
        }

        if (!empty($this->children)) {
            foreach ($this->children as $childCategory) {
                $valid = $childCategory->validate($logger) && $valid;
            }
        }

        if (!is_int($this->rank)) {
            $valid = false;
            $logger->error("Categories - {$this->name} - rank has to be an integer");
        }

        if ($valid) {
            $logger->debug(' - Category: ' . $this->name);
        }

        return $valid;
    }
}
