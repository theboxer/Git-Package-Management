<?php
namespace GPM\Config\Parts\Element;

use GPM\Config\Parts\Part;
use GPM\Utils\Types;
use Psr\Log\LoggerInterface;

/**
 * Class General
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string $type
 * @property-read string $value
 * @property-read string $lexicon
 * @property-read string $area
 *
 * @package GPM\Config\Parts
 */
class Property extends Part
{
    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $type = '';

    /** @var string */
    protected $value = '';

    /** @var string */
    protected $lexicon = '';

    /** @var string */
    protected $area = '';

    protected function generator(): void
    {
        if (empty($this->type)) {
            $this->type = 'textfield';
        }

        if (empty($this->lexicon)) {
            $this->lexicon = $this->config->general->lowCaseName . ':properties';
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'desc' => $this->description,
            'type' => $this->type,
            'value' => $this->value,
            'lexicon' => $this->lexicon,
            'area' => $this->area,
        ];
    }


    public function validate(LoggerInterface $logger, $name = ''): bool
    {
        $valid = true;
        if (empty($this->name)) {
            $logger->error("Property - {$name} - name is required");
            $valid = false;
        }

        if (!in_array($this->type, Types::List)) {
            $logger->error("Property - {$name} - " . $this->name . ' - type is not valid');
            $valid = false;
        }

        if ($valid) {
            $logger->debug(' -- Property: ' . $this->name);
        }

        return $valid;
    }
}
