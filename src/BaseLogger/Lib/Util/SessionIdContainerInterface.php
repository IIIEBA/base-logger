<?php

declare(strict_types = 1);

namespace BaseLogger\Lib\Util;

/**
 * Class SessionIdContainer
 * @package BaseLogger\Lib\Util
 */
interface SessionIdContainerInterface
{
    /**
     * @return string
     */
    public function getSessionId();

    /**
     * Reset session id in container
     */
    public function resetSessionId();

    /**
     * Manually set new session id
     *
     * @param string $sessionId
     */
    public function setSessionId($sessionId);
}
