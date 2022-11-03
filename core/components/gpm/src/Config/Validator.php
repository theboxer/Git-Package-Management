<?php
namespace GPM\Config;

use GPM\Config\Parts\Part;
use Psr\Log\LoggerInterface;

final class Validator {
    /** @var LoggerInterface */
    public $logger;

    /** @var \GPM\Config\Config */
    public $config;

    /** @var int */
    private $logNesting = 1;

    public function __construct(LoggerInterface $logger, Config $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function validateConfig(): bool
    {
        $this->logger->notice('Validating config');
        $validGeneral = $this->validate($this->config->general);

        if (!$validGeneral) return false;

        $valid = true;

        $valid = $this->validate($this->config->build) && $valid;
        $valid = $this->validate($this->config->paths) && $valid;
        $valid = $this->validate($this->config->database) && $valid;

        foreach ($this->config->systemSettings as $systemSetting) {
            $valid = $this->validate($systemSetting) && $valid;
        }

        foreach ($this->config->menus as $menu) {
            $valid = $this->validate($menu) && $valid;
        }

        foreach ($this->config->categories as $category) {
            $valid = $this->validate($category) && $valid;
        }

        foreach ($this->config->snippets as $snippet) {
            $valid = $this->validate($snippet) && $valid;
        }

        foreach ($this->config->chunks as $chunk) {
            $valid = $this->validate($chunk) && $valid;
        }

        foreach ($this->config->plugins as $plugin) {
            $valid = $this->validate($plugin) && $valid;
        }

        foreach ($this->config->templates as $template) {
            $valid = $this->validate($template) && $valid;
        }

        // FIX TVs
        foreach ($this->config->templateVars as $templateVar) {
            $valid = $this->validate($templateVar) && $valid;
        }

        foreach ($this->config->propertySets as $propertySet) {
            $valid = $this->validate($propertySet) && $valid;
        }

        foreach ($this->config->widgets as $widget) {
            $valid = $this->validate($widget) && $valid;
        }

        return $valid;
    }

    public function validate(Part $part, bool $increaseLogNesting = false): bool
    {
        if ($increaseLogNesting) {
            $this->logNesting++;
        }

        $valid = true;
        $rules = $part->getRules();

        $partName = (explode('\\', get_class($part)));
        $partName = array_pop($partName);
        $this->logger->debug(' ' . str_pad('', $this->logNesting, '-') . " {$partName}" . (!empty($part->keyField) ? (': ' . $part->{$part->keyField}) : ''));

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $fieldRule) {

                if (is_string($fieldRule)) {
                    $fieldRule = ['rule' => $fieldRule];
                }

                if (!isset($fieldRule['params'])) {
                    $fieldRule['params'] = null;
                }

                $valid = Rules::check($fieldRule, $this, $part->$field, $field, $part) && $valid;
            }

        }

        if ($increaseLogNesting) {
            $this->logNesting--;
        }

        return $valid;
    }
}
