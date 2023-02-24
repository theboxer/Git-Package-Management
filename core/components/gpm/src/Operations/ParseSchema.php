<?php
namespace GPM\Operations;

use GPM\Config\Config;
use GPM\Model\GitPackage;

class ParseSchema extends Operation {
    public function execute(GitPackage $package): void
    {
        $packages = $this->modx->getOption('gpm.packages_dir');
        $config = Config::load($this->modx, $this->logger, $packages . $package->dir_name . DIRECTORY_SEPARATOR);

        $manager = $this->modx->getManager();
        $generator = $manager->getGenerator();

        $generator->parseSchema(
            $config->paths->core . 'schema/' . $config->general->lowCaseName . '.mysql.schema.xml',
            $config->paths->core . 'src/',
            [
                'compile'         => null,
                'update'          => 0,
                'regenerate'      => 1,
                'namespacePrefix' => $config->general->namespace
            ]
        );

        $this->logger->warning('Schema parsed');
    }

}
