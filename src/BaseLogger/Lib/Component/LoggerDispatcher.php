<?php

namespace BaseLogger\Lib\Component;

use BaseExceptions\Exception\InvalidArgument\EmptyStringException;
use BaseExceptions\Exception\InvalidArgument\NotStringException;
use BaseLogger\Lib\Util\SessionIdContainer;
use BaseLogger\Lib\Util\SessionIdContainerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerDispatcher

 * @package BaseLogger\Lib\Component
 */
class LoggerDispatcher extends AbstractLogger
{
    /**
     * @var LoggerInterface[]
     */
    private $loggerList = [];
    /**
     * @var SessionIdContainerInterface
     */
    private $sessionIdContainer;

    /**
     * LoggerDispatcher constructor.
     * @param SessionIdContainerInterface $sessionIdContainer
     */
    public function __construct(SessionIdContainerInterface $sessionIdContainer = null)
    {
        if ($sessionIdContainer === null) {
            $sessionIdContainer = new SessionIdContainer();
        }

        $this->sessionIdContainer = $sessionIdContainer;
    }

    /**
     * Register new logger to dispatcher
     *
     * @param string $name
     * @param LoggerInterface $logger
     */
    public function addLogger($name, LoggerInterface $logger)
    {
        if (!is_string($name)) {
            throw new NotStringException("name");
        }
        if (empty($name)) {
            throw new EmptyStringException("name");
        }
        
        $this->loggerList[$name] = $logger;
    }
    
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        $context["sessionId"] = $this->sessionIdContainer->getSessionId();
        
        foreach ($this->loggerList as $logger) {
            $logger->log($level, $message, $context);
        }

        return null;
    }
}
