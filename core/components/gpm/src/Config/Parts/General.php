<?php
namespace GPM\Config\Parts;

use Psr\Log\LoggerInterface;

/**
 * Class General
 *
 * @property-read string $name
 * @property-read string $lowCaseName
 * @property-read string $description
 * @property-read string $namespace
 * @property-read string $author
 * @property-read string $version
 *
 * @package GPM\Config\Parts
 */
class General extends Part
{
    /** @var string */
    protected $name = '';

    /** @var string */
    protected $lowCaseName = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $namespace = '';

    /** @var string */
    protected $author = '';

    /** @var string */
    protected $version = '';

    protected function generator(): void
    {
        if (empty($this->lowCaseName)) {
            $this->lowCaseName = strtolower(str_replace(' ', '', $this->name));
        }

        if (empty($this->namespace)) {
            $this->namespace = ucfirst($this->lowCaseName);
        }
    }

    public function validate(LoggerInterface $logger): bool
    {
        $valid = true;
        if (empty($this->name)) {
            $logger->error('General - name is required');
            $valid = false;
        }

        if (empty($this->version)) {
            $logger->error('General - version is required');
            $valid = false;
        }

        if ($valid) {
            $logger->debug(' - General');
        }

        return $valid;
    }
}
