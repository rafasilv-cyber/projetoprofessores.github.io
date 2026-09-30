<?php
declare(strict_types=1);
namespace App\Core;

final class Validation
{
    public array $errors = [];
    public array $data = [];

    public function text(array $input, string $field, string $label, int $max, bool $required = true): self
    {
        $raw = $input[$field] ?? '';
        $value = is_string($raw) ? preg_replace('/^\s+|\s+$/u', '', $raw) : '';
        $value ??= '';
        $this->data[$field] = $value;
        if ($required && $value === '') {
            $this->errors[$field] = "Preencha o campo {$label}.";
        } elseif (mb_strlen($value, 'UTF-8') > $max) {
            $this->errors[$field] = "{$label} deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    public function email(array $input): self
    {
        $this->text($input, 'email', 'E-mail', 190);
        if (!isset($this->errors['email']) && !filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Informe um e-mail válido.';
        }
        return $this;
    }

    public function choice(array $input, string $field, array $allowed): self
    {
        $value = $input[$field] ?? '';
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            $this->errors[$field] = 'Selecione uma opção válida.';
            $value = '';
        }
        $this->data[$field] = $value;
        return $this;
    }

    public function reference(array $input, string $field, callable $exists, bool $required = true): self
    {
        $value = $input[$field] ?? '';
        if (!$required && $value === '') {
            $this->data[$field] = null;
            return $this;
        }
        $id = is_scalar($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($id === false || !$exists($id)) {
            $this->errors[$field] = 'Selecione um registro existente.';
        }
        $this->data[$field] = $id ?: null;
        return $this;
    }
}
