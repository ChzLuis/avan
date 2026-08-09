<?php

namespace App\Catalog\DTOs;

/**
 * Un campo del formulario de configuración de un conector, declarado por el
 * propio proveedor. La UI genérica del panel de integraciones renderiza el
 * formulario a partir de una lista de ConfigField — nunca hay una vista
 * dedicada por proveedor.
 */
final class ConfigField
{
    /**
     * @param  string  $type  text|password|url|number|select|checkbox
     * @param  array<string,string>  $options  Para type=select: [valor => etiqueta].
     * @param  array<string,mixed>  $rules  Reglas de validación Laravel (ej. ['required','max:255']).
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type = 'text',
        public readonly bool $required = false,
        public readonly bool $secret = false,
        public readonly mixed $default = null,
        public readonly array $rules = [],
        public readonly ?string $help = null,
        public readonly array $options = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name, 'label' => $this->label, 'type' => $this->type,
            'required' => $this->required, 'secret' => $this->secret, 'default' => $this->default,
            'help' => $this->help, 'options' => $this->options,
        ];
    }
}
