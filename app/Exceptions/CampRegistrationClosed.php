<?php

namespace App\Exceptions;

use RuntimeException;

class CampRegistrationClosed extends RuntimeException
{
    public function __construct(string $message = 'Registration for this camp is not open.')
    {
        parent::__construct($message);
    }
}
