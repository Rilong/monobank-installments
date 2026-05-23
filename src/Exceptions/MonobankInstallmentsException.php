<?php

namespace Rilong\MonobankInstallments\Exceptions;

class MonobankInstallmentsException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
