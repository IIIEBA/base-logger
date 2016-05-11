<?php

namespace BaseLogger\Module\Logger;

use BaseExceptions\Exception\InvalidArgument\EmptyStringException;
use BaseExceptions\Exception\InvalidArgument\NotIntegerException;
use BaseExceptions\Exception\InvalidArgument\NotPositiveNumericException;
use BaseExceptions\Exception\InvalidArgument\NotStringException;
use BaseLogger\Lib\Component\LoggerDispatcher;
use Psr\Log\AbstractLogger;

/**
 * Class EosLogger
 * @package BaseLogger\Module\Logger
 */
class EosLogger extends AbstractLogger
{
    const UDP_MAX_SIZE = 64000;

    /**
     * @var resource
     */
    private $socket;
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var string
     */
    private $uname;
    /**
     * @var string
     */
    private $realm;
    /**
     * @var string
     */
    private $secret;
    /**
     * @var int|null
     */
    private $ipAddressUpdateTime;
    /**
     * @var string|null
     */
    private $ipAddress;
    /**
     * @var array|\string[]
     */
    private $levelList;

    /**
     * EosLogger constructor.
     * @param string $host
     * @param string $realm
     * @param string $secret
     * @param int|null $port
     * @param string[] $levelList
     */
    public function __construct(
        $host,
        $realm,
        $secret,
        $port = null,
        array $levelList = []
    ) {
        if (!is_string($host)) {
            throw new NotStringException("host");
        }
        if (empty($host)) {
            $host = "127.0.0.1";
        }

        if (!is_string($realm)) {
            throw new NotStringException("realm");
        }
        if (empty($realm)) {
            throw new EmptyStringException("realm");
        }

        if (!is_string($secret)) {
            throw new NotStringException("secret");
        }
        if (empty($secret)) {
            throw new EmptyStringException("secret");
        }

        if (!is_null($port)) {
            if (!is_int($port)) {
                throw new NotIntegerException("port");
            }
            if ($port < 1) {
                throw new NotPositiveNumericException("port");
            }
        } else {
            $port = 8087;
        }

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $this->host   = $host;
        $this->port   = $port;
        $this->uname  = gethostname();
        $this->realm  = $realm;
        $this->secret = $secret;
        $this->levelList = $levelList;
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
        if (!empty($this->levelList) && !in_array($level, $this->levelList)) {
            return;
        }

        list($ms, $ts) = explode(' ', microtime());
        $tags = [$this->uname, $level];
        $data = [
            "event-time" => date('Y-m-d\TH:i:s.', $ts) . sprintf("%06d", $ms * 1000000) . date('P'),
            "message" => $message,
        ];

        // Parse context
        foreach ($context as $key => $value) {
            switch ($key) {
                case "tags":
                    if (is_array($value)) {
                        foreach ($value as $tagKey => $tagValue) {
                            $tags[] = (is_numeric($tagKey) ? "" : $tagKey . "-") . $tagValue;
                        }
                    } else {
                        $tags[] = $value;
                    }

                    break;

                case "sessionId":
                    $tags[] = $context["sessionId"];
                    $data["eos-id"] = $context["sessionId"];

                    break;

                case "exception":
                    if (is_object($value) && $value instanceof \Exception) {
                        $tags[] = 'error';
                        $data['exception'] = [
                            'message' => $value->getMessage(),
                            'code'    => $value->getCode(),
                            'trace'   => $value->getTrace(),
                        ];
                    }

                    break;

                case "object":
                    if (is_object($value)) {
                        $object = get_class($value);
                        $data[$key] = $object;
                        $tags[] = $object;
                    }

                    break;

                default:
                    $data[$key] = $value;
            }
        }

        if (array_key_exists("file", $data) && array_key_exists("line", $data)) {
            $trace = debug_backtrace();
            array_shift($trace);

            if ($trace[0]["class"] === LoggerDispatcher::class) {
                array_shift($trace);
            }

            $data["file"] = isset($trace[0]["file"]) ? $trace[0]["file"] : "<file>";
            $data["line"] = isset($trace[0]["line"]) ? $trace[0]["line"] : "<line>";
        }

        $this->send($tags, $data);
    }

    /**
     * Utility method to send data into EOS
     *
     * @param string[] $tags
     * @param mixed    $data
     */
    private function send($tags, $data)
    {
        // Serializing objects
        foreach ($data as $key => $value) {
            if ($key === 'exception') {
                continue;
            }
            $data[$key] = $this->safeSerialize($value);
        }

        $data = json_encode($data);

        if (strlen($data) > self::UDP_MAX_SIZE) {
            $data = substr($data, 0, self::UDP_MAX_SIZE);
        }

        // Creating packet and signature
        $nonce  = microtime(true) . mt_rand();
        $hash   = hash("sha256", $nonce . $data . $this->secret);
        $packet = $nonce . "\n" . $this->realm . "+" . $hash . "\nlog://" . implode(':', $tags) . "\n" . $data;

        // Sending
        socket_sendto(
            $this->socket,
            $packet,
            strlen($packet),
            0,
            $this->getTargetIp(),
            $this->port
        );
    }

    /**
     * Serializes incoming data
     *
     * @param mixed $data
     * @return string
     */
    public function safeSerialize($data)
    {
        if (is_array($data) || is_object($data)) {
            $s = json_encode($data);
            if ($s === false) {
                // Possible binary data
                $s = serialize($s);
            }

            return $s;
        } else {
            return $data;
        }
    }

    /**
     * Resolves hostname once in 60 seconds
     *
     * @return string
     */
    private function getTargetIp()
    {
        if ($this->ipAddressUpdateTime === null || time() - 60 > $this->ipAddressUpdateTime) {
            // Need resolve hostname
            $this->ipAddress = gethostbyname($this->host);
            $this->ipAddressUpdateTime = time();
        }

        return $this->ipAddress;
    }
}
