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
    public function __construct($message, $code, $filename, $line)
    {
        parent::__construct($message, $code);
        
        $this->file = $filename;
        $this->line = $line;
    }
}
