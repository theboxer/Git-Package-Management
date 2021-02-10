<?php
namespace GPM\Config\Parts;

use GPM\Config\Config;
use GPM\Config\Rules;


/**
 * Class Part
 *
 * @property-read string $keyField
 *
 * @package GPM\Config\Parts
 */
abstract class Part
{
    /** @var string  */
    protected $keyField = '';

    /** @var Config */
    protected $config;

    /** @var array */
    protected $rules = [];

    /** @var array */
    private $defaultRules = [];

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

        $allowedTypes = [
            'string' => Rules::isString,
            'boolean' => Rules::isBool,
            'integer' => Rules::isInt,
            'double' => Rules::isFloat,
            'array' => Rules::isArray,
        ];
        $vars = get_object_vars($this);

        foreach ($vars as $name => $var) {
            if ($name === 'config') continue;
            if ($name === 'rules') continue;
            if ($name === 'defaultRules') continue;
            if ($name === 'keyField') continue;
            if (in_array($name, self::getSkipProperties())) continue;

            $type = gettype($this->{$name});
            if (isset($allowedTypes[$type])) {
                $this->defaultRules[$name] = [$allowedTypes[$type]];
            }

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
        unset($vars['rules']);
        unset($vars['defaultRules']);
        unset($vars['keyField']);

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

    public function getRules(): array
    {
        return array_merge($this->defaultRules, $this->rules);
    }
}
