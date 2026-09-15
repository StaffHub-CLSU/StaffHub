<?php
/**
 * Validator
 *
 * A small, reusable, chainable-ish validation helper used by every form
 * handler in the app (Employee, User, Attendance, Payroll). Accumulates
 * error messages keyed by field name so pages/AJAX endpoints can return
 * them directly as JSON.
 */
class Validator
{
    private array $errors = [];

    public function required($value, string $field, string $label): static
    {
        if ($value === null || trim((string) $value) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email($value, string $field, string $label = 'Email'): static
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function numeric($value, string $field, string $label): static
    {
        if ($value !== '' && $value !== null && !is_numeric($value)) {
            $this->errors[$field] = "$label must be a number.";
        }
        return $this;
    }

    public function min($value, float $min, string $field, string $label): static
    {
        if (is_numeric($value) && $value < $min) {
            $this->errors[$field] = "$label must be at least $min.";
        }
        return $this;
    }

    public function maxLength($value, int $max, string $field, string $label): static
    {
        if (!empty($value) && mb_strlen((string) $value) > $max) {
            $this->errors[$field] = "$label must not exceed $max characters.";
        }
        return $this;
    }

    public function date($value, string $field, string $label): static
    {
        if (!empty($value)) {
            $d = DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->errors[$field] = "$label must be a valid date (YYYY-MM-DD).";
            }
        }
        return $this;
    }

    /**
     * Validates password strength: min 8 chars, at least one letter and one number.
     */
    public function passwordStrength($value, string $field, string $label = 'Password'): static
    {
        if (!empty($value)) {
            if (strlen($value) < 8) {
                $this->errors[$field] = "$label must be at least 8 characters long.";
            } elseif (!preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value)) {
                $this->errors[$field] = "$label must contain at least one letter and one number.";
            }
        }
        return $this;
    }

    public function addError(string $field, string $message): static
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? array_values($this->errors)[0] : null;
    }
}
