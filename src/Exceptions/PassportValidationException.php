<?php

namespace Nitron\Passport\Exceptions;

class PassportValidationException extends PassportException
{
    private array $errors;

    public function __construct(string $message, int $code = 422, array $errors = [])
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
