<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', (string) $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $this->applyRule($field, $value, (string) $rule, $data);
            }
        }

        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule, array $data): void
    {
        $name = $rule;
        $param = null;

        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
        }

        $label = ucwords(str_replace('_', ' ', $field));

        switch ($name) {
            case 'required':
                if ($value === null || $value === '' || $value === []) {
                    $this->add($field, "{$label} is required.");
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->add($field, "{$label} must be numeric.");
                }
                break;
            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->add($field, "{$label} must be an integer.");
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $this->add($field, "{$label} must be a valid email address.");
                }
                break;
            case 'min':
                if (is_numeric($value) && (float) $value < (float) $param) {
                    $this->add($field, "{$label} must be at least {$param}.");
                } elseif (is_string($value) && !is_numeric($value) && mb_strlen($value) < (int) $param) {
                    $this->add($field, "{$label} must be at least {$param} characters.");
                }
                break;
            case 'max':
                if ($param !== null && str_contains($param, ',')) {
                    if ($value !== null && $value !== '' && !preg_match('/^-?\d+(\.\d+)?$/', (string) $value)) {
                        $this->add($field, "{$label} must be a valid decimal.");
                    }
                } elseif (is_numeric($value) && (float) $value > (float) $param) {
                    $this->add($field, "{$label} may not be greater than {$param}.");
                } elseif (is_string($value) && !is_numeric($value) && mb_strlen($value) > (int) $param) {
                    $this->add($field, "{$label} may not be greater than {$param} characters.");
                }
                break;
            case 'in':
                $allowed = explode(',', (string) $param);
                if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
                    $this->add($field, "{$label} is invalid.");
                }
                break;
            case 'confirmed':
                $other = $data[$field . '_confirmation'] ?? null;
                if ((string) $value !== (string) $other) {
                    $this->add($field, "{$label} confirmation does not match.");
                }
                break;
            case 'date':
                if ($value !== null && $value !== '' && strtotime((string) $value) === false) {
                    $this->add($field, "{$label} must be a valid date.");
                }
                break;
        }
    }

    private function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
