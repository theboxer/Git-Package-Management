<?php
namespace GPM\Config\Parts;

use GPM\Config\Config;

trait HasProperties
{
    /** @var Config */
    protected $config;

    /** @var Property[] */
    protected $properties = [];

    protected function setProperties(array $properties): void
    {
        foreach ($properties as $property) {
            $this->properties[] = new Property($property, $this->config);
        }
    }

    public function getProperties(): array
    {
        $properties = [];

        foreach ($this->properties as $property) {
            $properties[] = $property->toArray();
        }

        return $properties;
    }
}
