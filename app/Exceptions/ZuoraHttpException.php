<?php

declare(strict_types=1);

namespace App\Exceptions;

class ZuoraHttpException extends ZuoraException
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message)
    {
        $this->statusCode = $statusCode;
        parent::__construct("HTTP {$statusCode}: {$message}");
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
