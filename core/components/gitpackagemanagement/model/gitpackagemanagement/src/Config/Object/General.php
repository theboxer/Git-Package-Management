<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class General extends ConfigObject
{
    public $name;
    public $lowCaseName;
    public $description = '';
    public $author;
    public $version;
    public $corePath;
    public $assetsPath;
    public $assetsURL;

    protected $rules = [
        'name' => 'notEmpty',
        'author' => 'notEmpty',
        'version' => 'notEmpty'
    ];

    protected function setDefaults($config)
    {
        if (empty($config['lowCaseName'])) {
            $this->lowCaseName = str_replace(' ', '_', strtolower($this->name));
        }

        $corePath = $this->config->packagePath . "/core/components/" . $this->lowCaseName . "/";
        $this->corePath = str_replace('\\', '/', $corePath);

        $assetsPath = $this->config->packagePath . "/assets/components/" . $this->lowCaseName . "/";
        $this->assetsPath = str_replace('\\', '/', $assetsPath);

        $packagesUrl = $this->config->modx->getOption('gitpackagemanagement.packages_base_url', null, null);
        $this->assetsURL = $packagesUrl . $this->config->folderName . '/assets/components/' . $this->lowCaseName . '/';
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'lowCaseName' => $this->lowCaseName,
            'description' => $this->description,
            'author' => $this->author,
            'version' => $this->version
        ];
    }
}