<?php
namespace GPM\CLI\Commands;

use GPM\CLI\Commands\Command;
use GPM\CLI\Descriptors\Text;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GPMList extends ListCommand
{

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('xml')) {
            @trigger_error('The --xml option was deprecated in version 2.7 and will be removed in version 3.0. Use the --format option instead.', E_USER_DEPRECATED);

            $input->setOption('format', 'xml');
        }

        $helper = new DescriptorHelper();
        $helper->register('txt', new Text());
        $helper->describe($output, $this->getApplication(), array(
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'namespace' => $input->getArgument('namespace'),
        ));
    }
}
