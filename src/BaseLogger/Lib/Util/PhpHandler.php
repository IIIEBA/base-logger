<?php

namespace BaseLogger\Lib\Util;

use BaseLogger\Lib\Exception\ErrorHandler\DeprecatedErrorException;
use BaseLogger\Lib\Exception\ErrorHandler\ErrorException;
use BaseLogger\Lib\Exception\ErrorHandler\NoticeErrorException;
use BaseLogger\Lib\Exception\ErrorHandler\ParseErrorException;
use BaseLogger\Lib\Exception\ErrorHandler\StrictErrorException;
use BaseLogger\Lib\Exception\ErrorHandler\WarningErrorException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        register_shutdown_function([$this, "shutdownHandler"]);
    }

    /**
     * Custom PHP error handler
     *
     * @param int $code
     * @param string $msg
     * @param string $file
     * @param int $line
     * @return bool
     *
     * @throws ErrorException
     * @throws NoticeErrorException
     * @throws ParseErrorException
     * @throws StrictErrorException
     * @throws WarningErrorException
     */
    public function errorHandler($code, $msg, $file, $line)
    {
        switch ($code) {
            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                throw new ErrorException($msg, $code, $file, $line);
                break;
            
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                throw new WarningErrorException($msg, $code, $file, $line);
                break;
            
            case E_NOTICE:
            case E_USER_NOTICE:
                throw new NoticeErrorException($msg, $code, $file, $line);
                break;
            
            case E_PARSE:
                throw new ParseErrorException($msg, $code, $file, $line);
                break;
                
            case E_STRICT:
                throw new StrictErrorException($msg, $code, $file, $line);
                break;

            case E_USER_DEPRECATED:
            case E_DEPRECATED:
                $this->logger->notice($msg, [
                    "object" => $this,
                    "exception" => new DeprecatedErrorException($msg, $code, $file, $line),
                ]);
                break;
            
            default:
                throw new ErrorException($msg, $code, $file, $line);
        }

        return true;
    }

    /**
     * Custom PHP exception handler
     *
     * @param \Exception $exception
     * @throws \Exception
     */
    public function exceptionHandler(\Exception $exception)
    {
        // Hot fix for support php7 strict mode exception
        // TODO: add switch case for throwable errors
        if ($exception instanceof \Error) {
            $exception = new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->logger->error($exception->getMessage(), [
            "exception" => $exception,
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
        ]);

        throw $exception;
    }

    /**
     * Custom PHP shutdown handler
     */
    public function shutdownHandler()
    {
        $error = error_get_last();
        if (!empty($error) && is_array($error) && $error["type"] === E_ERROR) {
            $msg = "PHP FATAL:" . (!empty($error["message"]) ? $error["message"] : "<no text>");
            if (strpos($msg, "Uncaught exception") === 0) {
                return;
            }

            $file = !empty($error["file"]) ? $error["file"] : "<no file>";
            $line = !empty($error["line"]) ? $error["line"] : 0;

            $this->logger->log(
                LogLevel::EMERGENCY,
                $msg,
                [
                    "file" => $file,
                    "line" => $line,
                    "exception" => new ErrorException($msg, 0, $file, $line)
                ]
            );
        }
    }
}
