<?php

namespace App\Support;

final class StorefrontTheme
{
    /**
     * Resolve the visual identity of the shared storefront without coupling
     * navigation, content or project settings to a Blade template.
     */
    public static function resolve(array $settings): array
    {
        $key = (string) ($settings['catalog_template'] ?? 'default');
        $definition = CatalogTemplates::get($key);

        if (!$definition) {
            $key = 'default';
            $definition = CatalogTemplates::get($key) ?? [];
        }

        $templateSettings = (array) ($definition['settings'] ?? []);
        $cardStyle = (string) ($templateSettings['card_style'] ?? 'minimal');

        return [
            'key' => $key,
            // `name` y `description` NO estaban, y el selector de plantillas
            // las exige: su guard hace
            // `if (!json.theme.name || !json.theme.description) throw`, asi que
            // aplicar una plantilla mostraba SIEMPRE "La respuesta de la
            // plantilla esta incompleta" aunque el servidor la hubiera guardado
            // bien. El usuario veia un error en una operacion que si funciono.
            'name' => (string) ($definition['name'] ?? $definition['label'] ?? ucfirst($key)),
            'description' => (string) ($definition['short_description'] ?? $definition['description'] ?? ''),
            'label' => (string) ($definition['label'] ?? ucfirst($key)),
            'category' => (string) ($definition['category'] ?? 'General'),
            'family' => self::family($key, $cardStyle),
            'card_style' => preg_replace('/[^a-z0-9_-]/i', '', $cardStyle) ?: 'minimal',
        ];
    }

    private static function family(string $key, string $cardStyle): string
    {
        return match (true) {
            $key === 'direct' => 'direct',
            in_array($key, ['ella', 'editorial', 'boutique', 'joyeria', 'nordic'], true) => 'editorial',
            in_array($key, ['urban', 'luxe', 'licoreria'], true) || $cardStyle === 'dark' => 'dark',
            in_array($key, ['computienda', 'tecnologia'], true) || $cardStyle === 'tech' => 'technology',
            in_array($key, ['bistro', 'restaurante', 'supermercado', 'fresh', 'farmacia', 'farma', 'belleza', 'mascotas', 'lavanderia'], true) => 'organic',
            in_array($key, ['servicios', 'libreria'], true) || $cardStyle === 'service' => 'professional',
            default => 'commerce',
        };
    }
}
