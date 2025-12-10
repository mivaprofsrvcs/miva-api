<?php

declare(strict_types=1);

namespace pdeans\Miva\Api\Response;

use ArrayIterator;
use IteratorAggregate;

class ErrorBag implements IteratorAggregate
{
    /**
     * Collected response errors.
     *
     * @var array<\pdeans\Miva\Api\Response\Error>
     */
    protected array $errors;

    /**
     * Create a new response error bag instance.
     *
     * @param array<\pdeans\Miva\Api\Response\Error> $errors
     */
    public function __construct(array $errors = [])
    {
        $this->errors = array_values($errors);
    }

    /**
     * Determine if the bag contains any errors.
     */
    public function has(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Get all errors.
     *
     * @return array<\pdeans\Miva\Api\Response\Error>
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Get all error messages.
     */
    public function messages(): array
    {
        return array_map(
            static fn (Error $error) => $error->message(),
            $this->errors
        );
    }

    /**
     * Get errors for a specific field.
     *
     * @return array<\pdeans\Miva\Api\Response\Error>
     */
    public function forField(string $field): array
    {
        $field = strtolower($field);

        return array_values(array_filter(
            $this->errors,
            static function (Error $error) use ($field): bool {
                if ($error->field() !== null && strtolower($error->field()) === $field) {
                    return true;
                }

                foreach ($error->errorFields() as $errorField) {
                    if (
                        isset($errorField['error_field'])
                        && strtolower((string) $errorField['error_field']) === $field
                    ) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    /**
     * Merge another error bag into a new instance.
     */
    public function merge(self $bag): static
    {
        if (! $bag->has()) {
            return $this;
        }

        return new static(array_merge($this->errors, $bag->all()));
    }

    /**
     * Get an iterator for the error collection.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errors);
    }
}
