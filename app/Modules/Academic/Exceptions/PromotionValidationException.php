<?php

namespace App\Modules\Academic\Exceptions;

class PromotionValidationException extends \Exception
{
    protected array $validationErrors;

    public function __construct(array $validationErrors, string $message = "Validasi promosi kelas gagal.")
    {
        parent::__construct($message);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
