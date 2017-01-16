<?php

namespace BaseLogger\Module\Logger;

use BaseExceptions\Exception\LogicException\NotImplementedException;
use BaseLogger\Lib\Component\LoggerDispatcher;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerTrait;

/**
 * Class AbstractLogger
 * @package BaseLogger\Module\Logger
 */
class BaseLogger extends AbstractLogger
{
    /**
     * @var string[]
     */
    private $excludeClasses;
    /**
     * @var string[]
     */
    private $excludedFiles;

    /**
     * BaseLogger constructor.
     */
    public function __construct()
    {
        $excludeClasses = [
            get_called_class(),
            BaseLogger::class,
            LoggerDispatcher::class,
            LoggerTrait::class
        ];

        $excludedFiles = [];
        foreach ($excludeClasses as $key => $value) {
            $excludedFiles[] = str_replace("\\", "/", $value);
        }

        $this->excludeClasses = $excludeClasses;
        $this->excludedFiles = $excludedFiles;
    }

    /**
     * Get real backtrace without system logger calls
     *
     * @return array
     */
    public function getTrace()
    {
        $trace = debug_backtrace();
        foreach ($trace as $key => $item) {
            if (in_array($item["class"], $this->excludeClasses)) {
                unset($trace[$key]);
                continue;
            }

            foreach ($this->excludedFiles as $className) {
                if (strpos($item["file"], $className) !== false) {
                    unset($trace[$key]);
                    break;
                }
            }

            break;
        }

        return $trace;
    }
    
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        throw new NotImplementedException();
    }
}
