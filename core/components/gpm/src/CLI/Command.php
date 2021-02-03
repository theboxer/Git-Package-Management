<?php
namespace GPM\CLI;

use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends \Symfony\Component\Console\Command\Command {

    const SUCCESS = 0;
    const FAILURE = 1;

    protected function error(OutputInterface &$output, string $msg): void
    {
        $formatter = $output->getFormatter();

        $messages = array(
            '',
            $formatter->format(sprintf('<error>%s</error>', str_repeat(' ', strlen($msg) + 3))),
            $formatter->format('<error> ' . $msg . '  </error>'),
            $formatter->format(sprintf('<error>%s</error>', str_repeat(' ', strlen($msg) + 3))),
            ''
        );

        $output->writeln($messages, OutputInterface::OUTPUT_RAW);
    }
}
