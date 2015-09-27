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
    
    public function log($level, $message, array $context = array())
    {
        if (is_string($level)) {
            $level = self::$levelMap[$level];
        }
        
        $this->modx->log($level, $message);
    }
}