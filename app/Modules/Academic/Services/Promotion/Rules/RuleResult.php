<?php

namespace App\Modules\Academic\Services\Promotion\Rules;

class RuleResult
{
    private bool $isValid;
    private bool $isWarning;
    private ?string $message;
    private string $ruleName;

    public function __construct(bool $isValid, string $ruleName, ?string $message = null, bool $isWarning = false)
    {
        $this->isValid = $isValid;
        $this->ruleName = $ruleName;
        $this->message = $message;
        $this->isWarning = $isWarning;
    }

    public static function pass(string $ruleName): self
    {
        return new self(true, $ruleName);
    }

    public static function fail(string $ruleName, string $message): self
    {
        return new self(false, $ruleName, $message, false);
    }

    public static function warning(string $ruleName, string $message): self
    {
        return new self(true, $ruleName, $message, true);
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function isWarning(): bool
    {
        return $this->isWarning;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getRuleName(): string
    {
        return $this->ruleName;
    }
}
