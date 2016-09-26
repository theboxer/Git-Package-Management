<?php namespace GPM\Commands\Package;

use GPM\Commands\GPMCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setName('package:add')
            ->setDescription('Add a new folder.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Component folder'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \modProcessorResponse $response */
        $response = $this->getApplication()->gpm->runProcessor('mgr/gitpackage/create', [
            'folderName' => $input->getArgument('path'),
        ]);

        if (!$response->isError()) {
            $output->writeln('Package built.');
        } else {
            $this->error($output, $response->getMessage());
        }
    }
}
