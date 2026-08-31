<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'Insufficient wallet balance')
    {
        parent::__construct($message);
    }
}
