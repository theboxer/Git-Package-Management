<?php
namespace GPM\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

final class MODX implements LoggerInterface
{
    use LoggerTrait;

    /** @var \modX */
    protected $modx;

    public static $levelMap = [
        'emergency' => \modX::LOG_LEVEL_FATAL,
        'alert' => \modX::LOG_LEVEL_ERROR,
        'critical' => \modX::LOG_LEVEL_FATAL,
        'error' => \modX::LOG_LEVEL_ERROR,
        'warning' => \modX::LOG_LEVEL_WARN,
        'notice' => \modX::LOG_LEVEL_WARN,
        'info' => \modX::LOG_LEVEL_INFO,
        'debug' => \modX::LOG_LEVEL_DEBUG
    ];

    public function __construct(\modX &$modx)
    {
        $this->modx =& $modx;
    }

    /**
     * @param  string|int  $level
     * @param  string  $message
     * @param  array  $context
     */
    public function log($level, $message, array $context = [])
    {
        $message = str_replace(PHP_EOL, '<br>', $message);
        $message = str_replace('    ', '&nbsp;&nbsp;&nbsp;&nbsp;', $message);

        if (is_string($level)) {
            $level = self::$levelMap[$level];
        }

        $target = (!$this->getOption('enable_file_log')) ? '' : array(
            'target' => 'FILE',
            'options' => array(
                'filename' => 'gpm.log'
            )
        );

        $this->modx->log($level, $message, $target);
    }
}
