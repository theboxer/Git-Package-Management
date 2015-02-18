<?php
namespace GPM\Commands\Package;

use GPM\Commands\GPMCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setName('package:delete')
            ->setDescription('Delete a package.')
            ->addOption(
                'pkg',
                null,
                InputOption::VALUE_REQUIRED,
                'Package or folder name'
            )
            ->addOption(
                'useKey',
                null,
                InputOption::VALUE_NONE,
                'If passed package key will be used instead of package name'
            )
            ->addOption(
                'deleteFolder',
                null,
                InputOption::VALUE_NONE,
                'If passed package folder will be removed'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getOption('pkg');
        if (empty($name)) {
            $this->error($output, 'Option pkg is required.');
            return;
        }

        $pkgMatcher = $input->getOption('useKey') ? 'key' : 'name';
        $c = array($pkgMatcher => $name);
        if ($pkgMatcher == 'name') {
            $c['OR:dir_name:='] = $name;
        }

        $pkg = $this->getApplication()->modx->getObject('GitPackage', $c);

        if (empty($pkg)) {
            $this->error($output, 'Package ' . ($input->getOption('useKey') ? 'with key ' : '') . $name . ' was not found.');
            return;
        }

        $deleteFolder = (int) $input->getOption('deleteFolder');

        $options = array(
            'id' => $pkg->id,
            'deleteFolder' => $deleteFolder
        );

        /** @var \modProcessorResponse $response */
        $response = $this->getApplication()->gpm->runProcessor('mgr/gitpackage/remove', $options);

        if (!$response->isError()) {
            $output->writeln('Package deleted.');
        } else {
            $this->error($output, $response->getMessage());
        }

    }
}
