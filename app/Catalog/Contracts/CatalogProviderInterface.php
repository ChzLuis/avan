<?php

namespace App\Catalog\Contracts;

use App\Catalog\DTOs\ConfigSchema;
use App\Catalog\DTOs\ConnectionTestResult;
use App\Catalog\DTOs\ProviderCapabilities;
use App\Models\CatalogIntegration;

/**
 * Contrato mínimo que todo conector de catálogo debe implementar. NO expone
 * ningún formato propio del proveedor externo — todo lo que devuelve son
 * DTOs normalizados internos (App\Catalog\DTOs\*). El núcleo del sistema
 * (CatalogSyncManager, el comando catalog:sync, los controladores del panel)
 * solo conoce esta interfaz; jamás el nombre de un proveedor concreto.
 *
 * Capacidades de listado (fetchProducts/fetchServices/fetchCategories) NO son
 * parte de este contrato mínimo: viven en interfaces opcionales
 * (ProvidesProducts, ProvidesServices, ProvidesCategories) que el proveedor
 * implementa solo si aplica. Un proveedor que no vende servicios simplemente
 * no implementa ProvidesServices — el motor lo detecta con instanceof.
 */
interface CatalogProviderInterface
{
    /** Identificador único y estable del proveedor (ej. "siskote"). Usado como clave del Registry. */
    public function key(): string;

    /** Nombre legible para mostrar en la UI (ej. "SISKOTE"). */
    public function name(): string;

    /** Qué puede hacer este proveedor — determina qué botones/pasos mostrar y ejecutar. */
    public function capabilities(): ProviderCapabilities;

    /** Campos de configuración que este proveedor necesita (URL, token, etc.), para generar el formulario dinámico. */
    public function configSchema(): ConfigSchema;

    /** Prueba que las credenciales guardadas en la integración sean válidas, sin traer datos. */
    public function testConnection(CatalogIntegration $integration): ConnectionTestResult;
}
