<?php

namespace GPM\Commands\Package;

use GPM\Commands\GPMCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends GPMCommand
{

    protected function configure()
    {
        $this
            ->setName('package:install')
            ->setDescription('Install a new package')
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the folder with package'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folder = $input->getOption('dir');
        if (empty($folder)) {
            $this->error($output, 'Option dir is required.');
            return;
        }

        $options = [
            'folderName' => $folder,
        ];

        /** @var \MODX\Revolution\Processors\ProcessorResponse $response */
        $response = $this->getApplication()->gpm->runProcessor('mgr/gitpackage/create', $options);

        if (!$response->isError()) {
            $output->writeln('Package installed.');
        } else {
            $this->error($output, $response->getMessage());
        }
    }

}
