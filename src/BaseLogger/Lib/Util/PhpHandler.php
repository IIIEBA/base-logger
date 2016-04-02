<?php

namespace BaseLogger\Lib\Util;

use BaseExceptions\Exception\LogicException\NotImplementedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class PhpHandler
 *
 * @package BaseLogger\Lib\Util
 */
class PhpHandler implements PhpHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PhpHandler constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = !is_null($logger) ? $logger : new NullLogger();
    }

    /**
     * Register custom error handler for application
     */
    public function registerErrorHandler()
    {
        set_error_handler([$this, "errorHandler"]);
    }

    /**
     * Register custom exception handler for application
     */
    public function registerExceptionHandler()
    {
        set_exception_handler([$this, "exceptionHandler"]);
    }

    /**
     * Register custom shutdown handler for application
     */
    public function registerShutdownHandler()
    {
        throw new NotImplementedException();
    }

    /**
     * Custom PHP error handler
     *
     * @param int $code
     * @param string $msg
     * @param string $filename
     * @param int $line
     * @return bool
     */
    public function errorHandler($code, $msg, $filename, $line)
    {
        

        return true;
    }

    /**
     * Custom PHP exception handler
     * 
     * @param \Exception $exception
     * @return bool
     */
    public function exceptionHandler(\Exception $exception)
    {
        $this->logger->error($exception->getMessage(), ["exception" => $exception]);
        
        return true;
    }
}
