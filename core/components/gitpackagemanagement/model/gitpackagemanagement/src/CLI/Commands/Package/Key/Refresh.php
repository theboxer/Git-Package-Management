<?php
namespace GPM\CLI\Commands\Package\Key;

use GPM\CLI\Commands\GPMCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Refresh extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Generate random package\'s key.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->package->set('key', '');
        $this->package->save();

        $output->writeln($this->package->key);
    }
}
