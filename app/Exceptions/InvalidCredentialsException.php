<?php

namespace App\Exceptions;

class InvalidCredentialsException extends ApiException
{
    public function __construct(string $message = 'Invalid email or password . Plz try Again.')
    {
        parent::__construct($message, 401);
    }
}
