<?php

namespace App\Exceptions;

use Exception;

class TemplateComplianceException extends Exception
{
    protected array $details;

    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;

        \Log::error('Template Compliance Failed', [
            'message' => $message,
            'details' => $details,
        ]);
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
