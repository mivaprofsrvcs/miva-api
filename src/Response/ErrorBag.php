<?php

declare(strict_types=1);

namespace pdeans\Miva\Api\Response;

use ArrayIterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Error>
 */
class ErrorBag implements IteratorAggregate
{
    /**
     * Collected response errors.
     *
     * @var array<Error>
     */
    protected array $errors;

    /**
     * Create a new response error bag instance.
     *
     * @param array<Error> $errors
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
     * @return array<Error>
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Get all error messages.
     *
     * @return array<int, string>
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
     * @return array<Error>
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
    public function merge(self $bag): self
    {
        if (! $bag->has()) {
            return $this;
        }

        return new self(array_merge($this->errors, $bag->all()));
    }

    /**
     * Get an iterator for the error collection.
     *
     * @return ArrayIterator<int, Error>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errors);
    }
}
