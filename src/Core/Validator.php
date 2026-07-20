<?php

    namespace App\Core;

    class Validator {
        public static function validate(array $data, array $rules): array {
            $errors = [];
            $validated = [];

            foreach ($rules as $field => $ruleString) {
                $fieldRules = explode('|', $ruleString);
                $value = $data[$field] ?? null;
                $nullable = in_array('nullable', $fieldRules, true);

                if (($value === null || $value === '') && $nullable) {
                    $validated[$field] = $value;
                    continue;
                }

                foreach ($fieldRules as $rule) {
                    $params = [];
                    if (str_contains($rule, ':')) {
                        [$rule, $paramStr] = explode(':', $rule, 2);
                        $params = explode(',', $paramStr);
                    }

                    $error = self::applyRule($rule, $field, $value, $params);
                    if ($error) {
                        $errors[$field][] = $error;
                        break; // stop at first failing rule per field
                    }
                }

                if (!isset($errors[$field])) {
                    $validated[$field] = $value;
                }
            }

            if (!empty($errors)) {
                throw new ValidationException($errors);
            }

            return $validated;
        }

        private static function applyRule(string $rule, string $field, $value, array $params): ?string {
            switch ($rule) {
                case 'nullable':
                    return null;

                case 'required':
                    if ($value === null || $value === '') {
                        return "$field is required.";
                    }
                    return null;

                case 'string':
                    if ($value !== null && !is_string($value)) {
                        return "$field must be a string.";
                    }
                    return null;

                case 'numeric':
                    if ($value !== null && !is_numeric($value)) {
                        return "$field must be numeric.";
                    }
                    return null;

                case 'integer':
                    if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                        return "$field must be an integer.";
                    }
                    return null;

                case 'email':
                    if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return "$field must be a valid email address.";
                    }
                    return null;

                case 'date':
                    if ($value !== null && strtotime($value) === false) {
                        return "$field must be a valid date.";
                    }
                    return null;

                case 'in':
                    if ($value !== null && !in_array((string)$value, $params, true)) {
                        return "$field must be one of: " . implode(', ', $params) . '.';
                    }
                    return null;

                case 'max':
                    $max = (int) ($params[0] ?? 0);
                    // A value decoded from JSON as a PHP string (even a numeric-looking one,
                    // e.g. a phone number) is always treated as a string-length check here -
                    // only genuine int/float values fall through to numeric comparison.
                    if (is_string($value)) {
                        if (mb_strlen($value) > $max) {
                            return "$field must not exceed $max characters.";
                        }
                        return null;
                    }
                    if (is_numeric($value) && $value > $max) {
                        return "$field must not be greater than $max.";
                    }
                    return null;

                case 'min':
                    $min = (int) ($params[0] ?? 0);
                    if (is_string($value)) {
                        if (mb_strlen($value) < $min) {
                            return "$field must be at least $min characters.";
                        }
                        return null;
                    }
                    if (is_numeric($value) && $value < $min) {
                        return "$field must be at least $min.";
                    }
                    return null;

                default:
                    return null;
            }
        }
    }
