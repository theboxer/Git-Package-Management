<?php
namespace GPM\CLI\Commands\GPM;

use GPM\CLI\Commands\PackageCommand;
use GPM\Config\Config;
use GPM\Config\Loader\JSON;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;
use GPM\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Update extends PackageCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Update GPM')
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
                'noUI',
                null,
                InputOption::VALUE_NONE,
                'Update GPM without UI'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);

        $noUI = $input->getOption('noUI');

        try {
            $config = new Config($this->getApplication()->modx, $this->package->dir_name, dirname(Utils::getGPMDir()) . '/');
            $parser = new Parser($this->getApplication()->modx, $config);
            $loader = new JSON($parser);
            $loader->loadAll();

            unset($config->systemSettings['gitpackagemanagement.assets_path']);
            unset($config->systemSettings['gitpackagemanagement.assets_url']);

            if ($noUI) {
                $config->actions = [];
                $config->menus = [];
            }
            
            $schema = new \GPM\Action\Update($config, $this->package, $logger);
            $schema->update($input->getOption('updateDB'), intval($input->getOption('schema')));

            $fs = new Filesystem();
            if ($noUI) {
                $fs->remove([$this->getApplication()->modx->getOption('assets_path') . 'components/' . $config->general->lowCaseName]);
                $logger->info('UI removed.');
            } else {
                $fs->mirror(Utils::getGPMDir() . '/assets/components/' . $config->general->lowCaseName, $this->getApplication()->modx->getOption('assets_path') . 'components/' . $config->general->lowCaseName);
                $logger->info('UI updated.');
            }
            
        } catch (ValidatorException $ve) {
            $logger->error('Config file is invalid.');
            $logger->error($ve->getMessage());

            return null;
        } catch (\Exception $e) {
            $logger->error($e->getMessage());

            return null;
        }
    }
}
