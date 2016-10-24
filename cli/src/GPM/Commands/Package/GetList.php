<?php namespace GPM\Commands\Package;

use GPM\Commands\GPMCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetList extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setName('package:list')
            ->setDescription('List registered packages.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modx = $this->getApplication()->modx;

        $table = new Table($output);
        $table->setHeaders([
            'name', 'description', 'version', 'dir_name', 'key'
        ]);

        /** @var \GitPackage $package */
        foreach($modx->getCollection('GitPackage') as $package) {
            $table->addRow([
                $package->name, $package->description, $package->version, $package->dir_name, $package->key
            ]);
        }

        $table->render();
    }
}
