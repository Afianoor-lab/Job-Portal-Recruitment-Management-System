<?php

namespace App\Exceptions;

class AIServiceException extends ApiException
{
    public function __construct(string $message ,int $code)
    {
        parent::__construct($message,$code);
    }
}
