<?php

declare(strict_types = 1);

namespace BaseLogger\Lib\Enum;

use Enum\Lib\Enum;

/**
 * Class LogLevel
 * @package Kernel\Logger\Enum
 */
class LogLevel extends Enum
{
    const EMERGENCY = "emergency";
    const ALERT = "alert";
    const CRITICAL = "critical";
    const ERROR = "error";
    const WARNING = "warning";
    const NOTICE = "notice";
    const INFO = "info";
    const DEBUG = "debug";
}
