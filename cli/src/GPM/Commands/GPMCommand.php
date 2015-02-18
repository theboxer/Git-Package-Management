<?php
namespace GPM\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class GPMCommand extends Command
{
    /**
     * @return \GPM\Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }

    protected function error(OutputInterface &$output, $msg)
    {
        $formatter = $output->getFormatter();

        $messages = array(
            '',
            $formatter->format(sprintf('<error>%s</error>', str_repeat(' ', strlen($msg) + 8))),
            $formatter->format('<error>    ' . $msg . '    </error>'),
            $formatter->format(sprintf('<error>%s</error>', str_repeat(' ', strlen($msg) + 8))),
            ''
        );

        $output->writeln($messages, OutputInterface::OUTPUT_RAW);
    }
}
