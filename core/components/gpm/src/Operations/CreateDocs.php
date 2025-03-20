<?php

namespace GPM\Operations;

use GPM\Config\Config;
use MODX\Revolution\modLexicon;

class CreateDocs extends Operation
{
    /** @var string $docsPath */
    public $docsPath = null;

    /** @var string $language */
    private $language = null;

    /** @var Config $config */
    private $config = null;

    public function execute(string $dir): void
    {
        $packages = $this->modx->getOption('gpm.packages_dir');

        $this->config = Config::load($this->modx, $this->logger, $packages . $dir . DIRECTORY_SEPARATOR);
        $this->language = $this->modx->getOption('gitpackagemanagement.default_lexicon', null, 'en');

        /** @var modLexicon $lexicon */
        $lexicon = $this->modx->services->get('lexicon');
        $lexicon->load($this->language . ':core:default');

        $this->docsPath = $this->config->paths->package . '_docs/';

        $doctypes = $this->createDocs();

        $this->logger->notice('Documentation created for ' . implode(', ', $doctypes));
    }

    /**
     * Create docs
     *
     * @return array
     */
    private function createDocs()
    {
        $doctypes = [];
        if ($this->createSettingsDocs()) {
            $doctypes[] = $this->modx->lexicon('gitpackagemanagement.create_docs_settings');
        }
        if ($this->createPropertiesDocs()) {
            $doctypes[] = $this->modx->lexicon('gitpackagemanagement.create_docs_properties');
        }
        return $doctypes;
    }

    private function createSettingsDocs()
    {
        $values = [];
        foreach ($this->config->systemSettings as $setting) {
            $this->modx->lexicon->load($this->language . ':' . $setting->namespace . ':setting');
            switch ($setting->type) {
                case 'textfield':
                default:
                    $default = ($setting->value) ?: '-';
                    break;
                case 'combo-boolean':
                    $default = ($setting->value == '1') ? $this->modx->lexicon('yes', [], $this->language) : $this->modx->lexicon('no', [], $this->language);
                    break;
            }
            $values[] = [
                'key' => $setting->getNamespacedKey(),
                'name' => $this->modx->lexicon('setting_' . $setting->getNamespacedKey(), [], $this->language),
                'description' => $this->convertLinks($this->escapeTable($this->modx->lexicon('setting_' . $setting->getNamespacedKey() . '_desc', [], $this->language))),
                'default' => $default,
            ];
        }
        if ($values) {
            ksort($values, SORT_STRING);
            $result = [
                '| Key | Name | Description | Default |',
                '|-----|------|-------------|---------|'
            ];
            foreach ($values as $value) {
                $result[] = '| ' . $value['key'] . ' | ' . $value['name'] . ' | ' . $value['description'] . ' | ' . $value['default'] . ' |';
            }

            if (!file_exists($this->docsPath)) {
                $this->modx->cacheManager->writeTree($this->docsPath . 'settings/');
            }
            $this->modx->cacheManager->writeFile($this->docsPath . 'settings/' . 'setting.md', implode("\n", $result));
        }
        return true;
    }

    private function createPropertiesDocs()
    {
        foreach ($this->config->snippets as $snippet) {
            $this->modx->lexicon->load($this->language . ':' . $this->config->general->lowCaseName . ':properties');
            $values = [];
            $properties = $snippet->getProperties();
            foreach ($properties as $property) {
                switch ($property['type']) {
                    case 'textfield':
                    default:
                        $default = ($property['value']) ?: '-';
                        break;
                    case 'combo-boolean':
                        $default = ($property['value'] == '1') ? '1 (' . $this->modx->lexicon('yes', [], $this->language) . ')' : '0 (' . $this->modx->lexicon('no', [], $this->language) . ')';
                        break;
                }
                $values[$property['name']] = [
                    'name' => $property['name'],
                    'description' => $this->convertLinks($this->escapeTable($this->modx->lexicon($this->config->general->lowCaseName . '.' . strtolower($snippet->name) . '.' . $property['name'], [], $this->language))),
                    'default' => $default,
                ];
            }

            ksort($values, SORT_STRING);
            $result = [
                '## ' . $snippet->name,
                '',
                '| Property | Description | Default |',
                '|----------|-------------|---------|'
            ];
            foreach ($values as $value) {
                $result[] = '| ' . $value['name'] . ' | ' . $value['description'] . ' | ' . $value['default'] . ' |';
            }

            if (!file_exists($this->docsPath)) {
                $this->modx->cacheManager->writeTree($this->docsPath . 'snippets/');
            }
            $this->modx->cacheManager->writeFile($this->docsPath . 'snippets/' . $snippet->name . '.md', implode("\n", $result));
        }
        return true;
    }

    private function convertLinks($string)
    {
        $search = '#(<a .*?href=")(.*?)(".*?>)(.*?)(</a>)#';
        $replace = '[$4]($2)';
        return preg_replace($search, $replace, $string);
    }

    private function escapeTable($string)
    {
        return str_replace('|', '&#124;', $string);
    }
}

