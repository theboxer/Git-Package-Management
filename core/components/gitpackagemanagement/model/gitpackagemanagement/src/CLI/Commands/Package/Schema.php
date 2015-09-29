<?php
namespace GPM\CLI\Commands\Package;

use GPM\CLI\Commands\GPMCommand;
use GPM\Config\Config;
use GPM\Config\Loader\JSON;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Schema extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setName('package:schema')
            ->setDescription('Build class from a XML schema.')
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);
        
        $name = $input->getOption('pkg');
        if (empty($name)) {
            $logger->error('Option pkg is required.');
            return null;
        }

        $pkgMatcher = $input->getOption('useKey') ? 'key' : 'name';
        $c = array($pkgMatcher => $name);
        if ($pkgMatcher == 'name') {
            $c['OR:dir_name:='] = $name;
        }

        $pkg = $this->getApplication()->modx->getObject('GitPackage', $c);

        if (empty($pkg)) {
            $logger->error('Package ' . ($input->getOption('useKey') ? 'with key ' : '') . $name . ' was not found.');
            return null;
        }

        try {
            $config = new Config($this->getApplication()->modx, $pkg->dir_name);
            $parser = new Parser($this->getApplication()->modx, $config);
            $loader = new JSON($parser);
            $loader->loadAll();

            $schema = new \GPM\Action\Schema($config, $logger);
            $schema->build();
        } catch (ValidatorException $ve) {
            $logger->error('Config file is invalid.');
            $logger->error($ve->getMessage());

            
            return null;
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            
            return null;
        }

        $output->writeln('Classes from XML schema built.');
    }
}
