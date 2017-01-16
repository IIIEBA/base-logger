<?php

declare(strict_types = 1);

namespace BaseLogger\Lib\Util;

use BaseExceptions\Exception\InvalidArgument\EmptyStringException;
use BaseExceptions\Exception\InvalidArgument\NotStringException;

/**
 * Class SessionIdContainer
 * @package BaseLogger\Lib\Util
 */
class SessionIdContainer implements SessionIdContainerInterface
{
    /**
     * @var RandomGenerator
     */
    private $randomGenerator;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * SessionIdContainer constructor.
     */
    public function __construct()
    {
        $this->randomGenerator = new RandomGenerator();
        $this->sessionId = $this->randomGenerator->generateString(12);
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Reset session id in container
     */
    public function resetSessionId()
    {
        $this->sessionId = $this->randomGenerator->generateString(12);
    }

    /**
     * Manually set new session id
     *
     * @param string $sessionId
     */
    public function setSessionId($sessionId)
    {
        if (!is_string($sessionId)) {
            throw new NotStringException("sessionId");
        }
        if ($sessionId === "") {
            throw new EmptyStringException("sessionId");
        }

        $this->sessionId = $sessionId;
    }
}
