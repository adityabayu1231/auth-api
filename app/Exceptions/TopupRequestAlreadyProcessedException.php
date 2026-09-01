<?php

namespace App\Exceptions;

use Exception;

class TopupRequestAlreadyProcessedException extends Exception
{
    public function __construct(string $message = 'Topup request has already been processed')
    {
        parent::__construct($message);
    }
}
