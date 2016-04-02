<?php

namespace BaseLogger\Lib\Util;

/**
 * Class PhpHandler
 *
 * @package BaseLogger\Lib\Util
 */
interface PhpHandlerInterface
{
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
