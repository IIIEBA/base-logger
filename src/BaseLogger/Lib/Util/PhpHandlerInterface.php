<?php

namespace BaseLogger\Lib\Util;

use Psr\Log\LoggerInterface;

/**
 * Class PhpHandler
 *
 * @package BaseLogger\Lib\Util
 */
interface PhpHandlerInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * Register custom error handler for application
     */
    public function registerErrorHandler();

    /**
     * Register custom exception handler for application
     */
    public function registerExceptionHandler();

    /**
     * Register custom shutdown handler for application
     */
    public function registerShutdownHandler();
}
