<?php

namespace pdeans\Miva\Api\Response;

class Error
{
    /**
     * Create a new response error instance.
     *
     * @param string $code Unique error code.
     * @param string $message Human-readable error message.
     * @param string|null $field Field name associated with the error, if available.
     * @param string|null $fieldMessage Field-specific error message, if available.
     * @param bool $validationError Indicates if this is a validation error.
     * @param bool $inputErrors Indicates if input errors are present.
     * @param array<int, array{error_field?: string|null, error_message?: string|null}> $errorFields Field-level errors.
     * @param string|null $functionName Function name that produced the error, if available.
     * @param int|null $index Iteration or operation index associated with the error, if available.
     */
    public function __construct(
        protected readonly string $code,
        protected readonly string $message,
        protected readonly ?string $field = null,
        protected readonly ?string $fieldMessage = null,
        protected readonly bool $validationError = false,
        protected readonly bool $inputErrors = false,
        protected readonly array $errorFields = [],
        protected readonly ?string $functionName = null,
        protected readonly ?int $index = null
    ) {
    }

    /**
     * Get the error code.
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Get the error message.
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * Get the field associated with the error, if provided.
     */
    public function field(): ?string
    {
        return $this->field;
    }

    /**
     * Get the field-specific error message, if provided.
     */
    public function fieldMessage(): ?string
    {
        return $this->fieldMessage;
    }

    /**
     * Determine if this is a validation error.
     */
    public function validationError(): bool
    {
        return $this->validationError;
    }

    /**
     * Determine if this error contains input errors.
     */
    public function inputErrors(): bool
    {
        return $this->inputErrors;
    }

    /**
     * Get the list of field-level errors.
     */
    public function errorFields(): array
    {
        return $this->errorFields;
    }

    /**
     * Get the function name associated with the error, if provided.
     */
    public function functionName(): ?string
    {
        return $this->functionName;
    }

    /**
     * Get the iteration/operation index associated with the error, if provided.
     */
    public function index(): ?int
    {
        return $this->index;
    }
}
