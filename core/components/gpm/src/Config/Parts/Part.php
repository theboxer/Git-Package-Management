<?php
namespace GPM\Config\Parts;

use GPM\Config\Config;
use Psr\Log\LoggerInterface;

abstract class Part
{

    /** @var Config */
    protected $config;

    /**
     * Part constructor.
     *
     * @param array $data
     * @param Config|null $config
     */
    public function __construct(array $data, Config $config = null)
    {
        if ($config) {
            $this->setConfig($config);
        }

        $vars = get_object_vars($this);

        foreach ($vars as $name => $var) {
            if ($name === 'config') continue;
            if (in_array($name, self::getSkipProperties())) continue;

            if (isset($data[$name])) {
                $setter = 'set' . ucfirst($name);
                if (method_exists($this, $setter)) {
                    $this->$setter($data[$name]);
                    continue;
                }

                $this->{$name} = $data[$name];
            }
        }

        $this->generator();
    }

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return null;
    }

    public function __set(string $property, $value)
    {
        return $value;
    }

    public function __isset(string $property): bool
    {
        if (in_array($property, $this->getPrivateProperties())) return false;

        return property_exists($this, $property);
    }

    protected function generator(): void
    {

    }

    public function toArray(): array
    {
        $vars = get_object_vars($this);
        unset($vars['config']);

        return $vars;
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    public static function getSkipProperties(): array
    {
        return [];
    }

    public static function getPrivateProperties(): array
    {
        return [];
    }

    abstract public function validate(LoggerInterface $logger): bool;
}
