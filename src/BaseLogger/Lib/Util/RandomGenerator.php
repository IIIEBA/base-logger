<?php

namespace BaseLogger\Lib\Util;

use BaseExceptions\Exception\InvalidArgument\NotIntegerException;
use BaseExceptions\Exception\InvalidArgument\NotPositiveNumericException;

/**
 * Class RandomGenerator
 *
 * @package BaseLogger\Lib\Util
 */
class RandomGenerator
{
    /**
     * Generate random string
     *
     * @param int $bytes
     * @return string
     */
    public function generateString($bytes = 6)
    {
        if (!is_int($bytes)) {
            throw new NotIntegerException("bytes");
        }
        if ($bytes < 1) {
            throw new NotPositiveNumericException("bytes");
        }
        
        return bin2hex(openssl_random_pseudo_bytes($bytes));
    }
}
