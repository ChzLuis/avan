<?php

namespace App\Catalog\DTOs;

/** Colección de ConfigField que un proveedor declara para su formulario de credenciales. */
final class ConfigSchema
{
    /** @param  ConfigField[]  $fields */
    public function __construct(public readonly array $fields)
    {
    }

    /** Reglas de validación Laravel derivadas del esquema, listas para $request->validate(). */
    public function validationRules(): array
    {
        $rules = [];
        foreach ($this->fields as $field) {
            $r = $field->rules;
            if ($field->required && !in_array('required', $r, true)) {
                array_unshift($r, 'required');
            } elseif (!$field->required && !in_array('nullable', $r, true)) {
                array_unshift($r, 'nullable');
            }
            $rules["credentials.{$field->name}"] = $r;
        }

        return $rules;
    }

    /** Nombres de los campos marcados como secretos (nunca se devuelven al frontend tras guardarlos). */
    public function secretFieldNames(): array
    {
        return array_values(array_map(fn (ConfigField $f) => $f->name, array_filter($this->fields, fn (ConfigField $f) => $f->secret)));
    }

    public function toArray(): array
    {
        return array_map(fn (ConfigField $f) => $f->toArray(), $this->fields);
    }
}
