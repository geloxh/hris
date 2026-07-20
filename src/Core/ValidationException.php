<?php

    namespace App\Core;

    class ValidationException extends \RuntimeException {
        public array $errors;

        public function __construct(array $errors, string $message = 'The given data was invalid.') {
            parent::__construct($message);
            $this->errors = $errors;
        }
    }
