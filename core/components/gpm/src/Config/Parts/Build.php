<?php
namespace GPM\Config\Parts;

use GPM\Config\Rules;
use Psr\Log\LoggerInterface;

/**
 * Class Build
 *
 * @property-read string $readme
 * @property-read string $license
 * @property-read string $changelog
 * @property-read array $scriptsBefore
 * @property-read array $scriptsAfter
 * @property-read array $requires
 *
 * @package GPM\Config\Parts
 */
class Build extends Part
{

    /** @var string */
    protected $readme = '';

    /** @var string */
    protected $license = '';

    /** @var string */
    protected $changelog = '';

    /** @var string[] */
    protected $scriptsBefore = [];

    /** @var string[] */
    protected $scriptsAfter = [];

    /** @var string[] */
    protected $requires = [];

    protected $rules = [
        'readme' => [Rules::isString, Rules::packageFileExists],
        'license' => [Rules::isString, Rules::packageFileExists],
        'changelog' => [Rules::isString, Rules::packageFileExists],
        'scriptsBefore' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::isString, Rules::scriptExists]]]
        ],
        'requires' => [Rules::isArray, Rules::packageDependencies],
    ];

    protected function generator(): void
    {
        if (empty($this->readme) && file_exists($this->config->paths->package . 'README.md')) {
            $this->readme = 'README.md';
        }

        if (empty($this->changelog) && file_exists($this->config->paths->package . 'CHANGELOG.md')) {
            $this->changelog = 'CHANGELOG.md';
        }

        if (empty($this->license) && file_exists($this->config->paths->package . 'LICENSE.md')) {
            $this->license = 'LICENSE.md';
        }
    }
}
