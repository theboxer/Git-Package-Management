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
    // FIXED Fatal error: Declaration of ... must be compatible with Psr\Log\AbstractLogger::log($level, Stringable|string $message, array $context = []): void
    public function log($level, $message, array $context = []): void
    {
        $message = str_replace(PHP_EOL, '<br>', $message);
        $message = str_replace('    ', '&nbsp;&nbsp;&nbsp;&nbsp;', $message);

        if (is_string($level)) {
            $level = self::$levelMap[$level];
        }

        $this->modx->log($level, $message);
    }
}
