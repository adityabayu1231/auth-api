<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderStatusTransitionException extends Exception
{
    public function __construct(string $message = 'Invalid order status transition')
    {
        parent::__construct($message);
    }
}
