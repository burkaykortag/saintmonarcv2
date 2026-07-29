<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Application;
use Core\Contracts\DatabaseInterface;
use Exception;

class Validator {
    private array $errors = [];
    private array $data = [];
    private ?DatabaseInterface $db = null;

    public function __construct(array $data) {
        $this->data = $data;
        $this->db = Application::getInstance()->getContainer()->get(DatabaseInterface::class);
    }

    public static function make(array $data, array $rules, array $messages = []): self {
        $validator = new self($data);
        $validator->validate($rules, $messages);
        return $validator;
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    private function validate(array $rules, array $messages): void {
        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    list($ruleName, $paramStr) = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $method = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));
                if (method_exists($this, $method)) {
                    $isValid = $this->$method($field, $value, $params);
                    if (!$isValid) {
                        $customKey = "{$field}.{$ruleName}";
                        $msg = $messages[$customKey] ?? $messages[$field] ?? $this->getDefaultMessage($field, $ruleName, $params);
                        $this->errors[$field][] = $msg;
                        break; // Stop validating this field if one rule fails
                    }
                }
            }
        }
    }

    private function validateRequired(string $field, $value, array $params): bool {
        if ($value === null) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        if (is_array($value) && empty($value)) {
            return false;
        }
        return true;
    }

    private function validateNumeric(string $field, $value, array $params): bool {
        if ($value === null || $value === '') {
            return true; // Use 'required' to check for empty values
        }
        return is_numeric($value);
    }

    private function validateInteger(string $field, $value, array $params): bool {
        if ($value === null || $value === '') {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateUnique(string $field, $value, array $params): bool {
        if ($value === null || trim((string)$value) === '') {
            return true;
        }
        if (count($params) < 2) {
            throw new Exception("Unique rule requires table and column name.");
        }
        $table = $params[0];
        $column = $params[1];
        $ignoreId = $params[2] ?? null;
        $ignoreColumn = $params[3] ?? 'id';

        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = :val";
        $binds = [':val' => $value];

        if ($ignoreId !== null && $ignoreId !== '' && $ignoreId !== 'NULL') {
            $sql .= " AND `{$ignoreColumn}` != :ignore";
            $binds[':ignore'] = $ignoreId;
        }

        $res = $this->db->query($sql, $binds);
        return ((int)($res[0]['count'] ?? 0)) === 0;
    }

    private function validateMin(string $field, $value, array $params): bool {
        if ($value === null || $value === '') {
            return true;
        }
        $min = (float)$params[0];
        if (is_numeric($value)) {
            return (float)$value >= $min;
        }
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }
        if (is_array($value)) {
            return count($value) >= $min;
        }
        return false;
    }

    private function validateMax(string $field, $value, array $params): bool {
        if ($value === null || $value === '') {
            return true;
        }
        $max = (float)$params[0];
        if (is_numeric($value)) {
            return (float)$value <= $max;
        }
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }
        if (is_array($value)) {
            return count($value) <= $max;
        }
        return false;
    }

    private function validateIn(string $field, $value, array $params): bool {
        if ($value === null || $value === '') {
            return true;
        }
        return in_array((string)$value, $params, true);
    }

    private function getDefaultMessage(string $field, string $rule, array $params): string {
        $labels = [
            'sku' => 'SKU',
            'name' => 'Adı',
            'price' => 'Fiyatı',
            'cost_price' => 'Maliyet Fiyatı',
            'slug' => 'Slug',
            'status' => 'Durum'
        ];
        $label = $labels[$field] ?? ucwords($field);

        switch ($rule) {
            case 'required':
                return "{$label} alanı zorunludur.";
            case 'numeric':
                return "{$label} alanı sayısal olmalıdır.";
            case 'integer':
                return "{$label} alanı tamsayı olmalıdır.";
            case 'unique':
                return "Bu {$label} değeri zaten kullanılmaktadır.";
            case 'min':
                return "{$label} alanı en az {$params[0]} olmalıdır.";
            case 'max':
                return "{$label} alanı en fazla {$params[0]} olmalıdır.";
            case 'in':
                return "Geçersiz {$label} seçimi.";
            default:
                return "{$label} alanı geçersizdir.";
        }
    }
}
