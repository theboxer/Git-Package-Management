<?php
namespace GPM\Commands\Package;

use GPM\Commands\GPMCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setName('package:update')
            ->setDescription('Update package')
            ->addOption(
                'pkg',
                null,
                InputOption::VALUE_REQUIRED,
                'Package name'
            )
            ->addOption(
                'updateDB',
                null,
                InputOption::VALUE_OPTIONAL,
                'If passed database will be updated. Possible options: alter, recreate or empty value',
                ''
            )
            ->addOption(
                'schema',
                null,
                InputOption::VALUE_NONE,
                'If passed XML schema will be build'
            )
            ->addOption(
                'useKey',
                null,
                InputOption::VALUE_NONE,
                'If passed package key will be used instead of package name'
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

        $db = $input->getOption('updateDB');

        $options = array(
            'id' => $pkg->id,
            'recreateDatabase' => (int) ($db == 'recreate'),
            'alterDatabase' => (int) ($db == 'alter'),
            'buildSchema' => (int) $input->getOption('schema'),
        );

        /** @var \modProcessorResponse $response */
        $response = $this->getApplication()->gpm->runProcessor('mgr/gitpackage/update', $options);

        if (!$response->isError()) {
            $output->writeln('Package updated.');
        } else {
            $this->error($output, $response->getMessage());
        }

    }
}
