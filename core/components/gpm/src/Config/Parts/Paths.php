<?php
namespace GPM\Config\Parts;

use GPM\Config\Config;
use Psr\Log\LoggerInterface;

/**
 * Class Paths
 *
 * @property-read string $package
 * @property-read string $core
 * @property-read string $assets
 * @property-read string $assetsURL
 * @property-read string $scripts
 * @property-read string $build
 *
 * @package GPM\Config\Parts
 */
class Paths extends Part
{
    /** @var string */
    protected $package = '';

    /** @var string  */
    protected $core = '';

    /** @var string  */
    protected $assets = '';

    /** @var string  */
    protected $assetsURL = '';

    /** @var string  */
    protected $scripts = '';

    /** @var string  */
    protected $build = '';

    /**
     * Paths constructor.
     *
     * @param array $data
     * @param Config $config
     */
    public function __construct(array $data, Config $config)
    {
        parent::__construct($data, $config);

        $package = explode(DIRECTORY_SEPARATOR, trim($this->package, DIRECTORY_SEPARATOR));
        $package = array_pop($package);

        $this->core = $this->package . 'core' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $config->general->lowCaseName . DIRECTORY_SEPARATOR;
        $this->assets = $this->package . 'assets' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $config->general->lowCaseName . DIRECTORY_SEPARATOR;
        $this->assetsURL = $package . '/assets' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $config->general->lowCaseName . DIRECTORY_SEPARATOR;

        $this->scripts = $this->package . '_build' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR;
        $this->build = $this->package . '_build' . DIRECTORY_SEPARATOR;
    }
}
