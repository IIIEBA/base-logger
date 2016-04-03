<?php

namespace BaseLogger\Lib\Exception\ErrorHandler;

use Exception;

/**
 * Class ErrorException
 *
 * @package BaseLogger\Lib\Exception
 */
class ErrorException extends \Exception
{
    /**
     * ErrorException constructor.
     *
     * @param string $message
     * @param int $code
     * @param string $filename
     * @param int $line
     */
    public function __construct($message, $code, $filename, $line)
    {
        parent::__construct(
            sprintf("PHP error: [%s] with code [%s]", $message, $code),
            $code
        );
        
        $this->file = $filename;
        $this->line = $line;
    }
}
