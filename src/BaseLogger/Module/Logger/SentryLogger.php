<?php

namespace BaseLogger\Module\Logger;

use BaseExceptions\Exception\InvalidArgument\EmptyStringException;
use BaseExceptions\Exception\InvalidArgument\NotStringException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Raven_Stacktrace;

/**
 * Class SentryLogger
 *
 * @package BaseLogger\Module\Component
 */
class SentryLogger extends AbstractLogger
{
    /**
     * @var array
     */
    private $levelList;
    /**
     * @var \Raven_Client
     */
    private $client;

    /**
     * SentryLogger constructor.
     *
     * @param string $dns
     * @param array $levelList
     */
    public function __construct(
        $dns,
        array $levelList = []
    ) {
        if (!is_string($dns)) {
            throw new NotStringException("dns");
        }
        if (empty($dns)) {
            throw new EmptyStringException("dns");
        }
        
        $allowedLevels = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::WARNING,
            LogLevel::ERROR,
        ];
        
        $this->levelList = !empty($levelList) ? array_intersect($allowedLevels, $levelList) : $allowedLevels;
        $this->client = new \Raven_Client($dns);
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
        if (!is_scalar($message)) {
            return;
        }

        // Check is given level supported for current logger
        if (!in_array($level, $this->levelList)) {
            return;
        }

        // Build trace
        $trace = debug_backtrace();
        array_shift($trace);

        // Build request
        $request = $this->buildDataPacket($level, $message, $context);

        // If we have sql in log, save one more just for query
        if (array_key_exists("sql", $context)) {
            $sqlRequest = array_merge($request, $this->buildSqlPacket($context));
            $this->client->capture($sqlRequest, $trace);
        }

        $request = array_merge($request, $this->buildMessagePacket(strval($message)));
        $request = array_merge($request, $this->buildExceptionPacket($context));

        // Log message
        $this->client->capture($request, $trace);
    }


    /**
     * Prepare context for sentry
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return array
     */
    public function buildDataPacket ($level, $message, array $context)
    {
        $result = [
            "logger" => "php",
            "level" => $level,
            "extra" => [],
            "tags" => [],
            "message" => $message,
        ];

        // Left selected key in first lvl of params list
        $specialKeys = [
            "fingerprint",
            "level",
            "logger",
            "tags",
        ];

        // List of keys which need to skip
        $excludeKeys = [
            "exception",
            "sql",
        ];

        // Parse context
        foreach ($context as $key => $value) {
            if (in_array($key, $excludeKeys)) {
                continue;
            }

            if (in_array($key, $specialKeys)) {
                $result[$key] = $value;
                continue;
            }

            if ($key === "extra" && is_array($value)) {
                $result["extra"] = array_merge($result["extra"], $value);
                continue;
            }

            $result["extra"][$key] = $value;
        }

        // Parse tags
        $tags = [];
        foreach ($result["tags"] as $key => $value) {
            if (is_int($key)) {
                $tags[$value] = 1;
            } else {
                $tags[$key] = $value;
            }
        }
        $result["tags"] = $tags;

        // Add session id to tags if exist
        if (array_key_exists("sessionId", $context)) {
            $result["tags"]["sessionId"] = $context["sessionId"];
        }

        // Remove empty elms
        if (empty($result["extra"])) {
            unset($result["extra"]);
        }
        if (empty($result["tags"])) {
            unset($result["tags"]);
        }

        return $result;
    }

    /**
     * Build SQL interface packet from context
     *
     * @param array $context
     * @return array
     */
    public function buildSqlPacket(array $context)
    {
        if (!array_key_exists("sql", $context)) {
            return [];
        }

        return [
            "sentry.interfaces.Query" => [
                "query" => $context["sql"],
                "engine" => "mysql",
            ],
            "message" => $context["sql"],
        ];
    }

    /**
     * Build Exception interface packet from context
     *
     * @param array $context
     * @return array
     */
    public function buildExceptionPacket(array $context)
    {
        if (!array_key_exists("exception", $context)) {
            return [];
        }

        /**
         * @var \Exception $exception
         */
        $exception = $context["exception"];
        do {
            $exceptionData = [
                'value' => $exception->getMessage(),
                'type' => get_class($exception),
                'module' => $exception->getFile() .':'. $exception->getLine(),
            ];

            $trace = $exception->getTrace();
            $frame_where_exception_thrown = array(
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            );

            array_unshift($trace, $frame_where_exception_thrown);

            $exceptionData['stacktrace'] = array(
                'frames' => Raven_Stacktrace::get_stack_info($trace),
            );

            $exceptions[] = $exceptionData;
        } while ($exception = $exception->getPrevious());

        return [
            "exception" => [
                'values' => array_reverse($exceptions),
            ],
        ];
    }

    /**
     * Build message interface from message
     *
     * @param string $message
     * @return array
     */
    public function buildMessagePacket($message)
    {
        return [
            "sentry.interfaces.Message" => [
                "message" => $message,
            ],
        ];

    }
}
