# Plan de refactorización del constructor de tienda

Este plan no está implementado. Requiere aprobación antes de modificar código.

## Objetivo técnico

Llegar a un flujo único y predecible:

```text
Formulario tipado
  → Request/DTO validado
  → servicio de configuración por proyecto
  → estado draft o published
  → StorefrontViewModel
  → componentes Blade compartidos
  → capa visual de la plantilla (solo estilos/variantes)
```

La plantilla debe decidir apariencia, no dónde se buscan los datos ni qué configuración gana.

## Fase 0 — Respaldo, inventario de datos y feature flag

1. Exportar conteos y duplicados de `store_sections`, `project_templates`, `store_menus` y settings relevantes por proyecto.
2. Detectar llaves privadas de plantilla realmente usadas.
3. Crear una bandera nueva por proyecto, por ejemplo `store_builder_contract_v3`, sin activar públicamente.
4. Mantener el render actual como fallback durante la transición.

**Criterio de salida:** informe de datos, rollback documentado y ninguna tienda cambiada.

## Fase 1 — Contrato canónico de configuración

1. Crear un registro PHP de campos con tipo, default, validación, alcance y capability.
2. Definir namespaces:
   - `theme.*`: colores, tipografías, radios.
   - `header.*`, `footer.*`, `catalog.*`, `checkout.*`, `seo.*`.
   - `home.{component}.*` dentro de `store_sections`.
3. Definir precedencia única: proyecto publicado > default de plantilla; draft solo en preview.
4. Crear adaptadores de lectura para llaves heredadas, sin borrarlas aún.
5. Reemplazar consultas repetidas `Project::setting()` por una colección cargada una vez.

**Criterio de salida:** tests unitarios de contrato y snapshots iguales en modo legacy.

## Fase 2 — Completar borrador/publicación

1. Migrar flags draft de dispositivo y fechas.
2. Añadir restricción de componente único tras limpiar duplicados.
3. Encapsular `saveDraft`, `publishOne`, `publishAll`, `resetDraft` en un servicio transaccional.
4. Hacer que “Guardar borrador” no altere ninguna columna publicada.
5. Mostrar estados explícitos: Borrador, Publicado, Programado, Expirado, Oculto, Sin contenido.

**Criterio de salida:** comparación automatizada de la tienda pública antes/después de guardar borrador.

## Fase 3 — Reorganizar el Diseñador

1. Mantener dos áreas principales claras:
   - **Plantillas**.
   - **Constructor visual**.
2. Dentro del constructor, usar navegación interna para Inicio, Encabezado/menú, Catálogo, Checkout, Footer/Contacto, SEO; sin duplicar formularios.
3. Incluir y corregir `store-navigation-builder` e `institutional-pages`.
4. Mover ajustes duplicados de Marca/Portada al componente propietario.
5. Filtrar controles por capabilities reales de cada plantilla, mostrando advertencias cuando una opción no sea compatible.
6. Añadir vista previa por PC/tablet/móvil sin publicar.

**Criterio de salida:** todos los campos de la matriz tienen una única ubicación visible.

## Fase 4 — Categorías destacadas y Solo por hoy

### Categorías destacadas

Ampliar el JSON canónico de `store_sections.featured_categories` con:

- título, subtítulo y “Ver todo”;
- selección y orden de categorías;
- ocultar vacías y mostrar conteo;
- visual `auto/image/icon/initial`;
- icono por categoría y upload/fit;
- layout `image-top/overlay/minimal/horizontal`;
- forma, radio, columnas desktop/tablet/mobile y carrusel móvil;
- colores con validación de contraste.

Crear un adaptador temporal desde `featured_categories_*` hacia el JSON. Después de verificar datos, deprecar las llaves privadas.

### Solo por hoy

- Unificar `countdown_*`, `show_flash_sale` y `daily_offer`.
- Definir timezone de la tienda.
- Alinear exactamente `hide/message` en todas las plantillas.
- Mostrar estado expirado en el administrador.
- Validar imagen, CTA y URL.

**Criterio de salida:** el mismo contenido/estado aparece al cambiar entre CompuTienda, Ecommerce y Direct; solo cambia el estilo.

## Fase 5 — View-model público y componentes compartidos

1. Crear `StorefrontViewModel` o servicio equivalente que entregue:
   - settings resueltos;
   - header/menu/footer;
   - secciones publicables y motivo de exclusión;
   - catálogo paginado/filtros;
   - páginas y pop-up.
2. Crear componentes Blade compartidos para header, menú, footer, popup, tarjetas y secciones.
3. Hacer que cada plantilla aporte clases, tokens y variantes, no consultas ni reglas de negocio.
4. Renderizar orden y visibilidad desde Laravel.
5. Retirar por etapas los selectores heurísticos de `public-store-runtime`.

**Criterio de salida:** ninguna sección esencial se mueve mediante JavaScript; no hay salto visual.

## Fase 6 — Separación real de páginas

1. `/{slug}`: Inicio únicamente.
2. `/{slug}/tienda`: catálogo completo con búsqueda, filtros y paginación.
3. Nosotros, Contacto, Blog y páginas personalizadas dentro del layout compartido.
4. Ocultar una opción del menú no altera `StorePage.is_enabled` ni borra contenido.
5. Desacoplar Blog de `storefront_structure_v2` cuando use una plantilla productiva compatible.
6. Asegurar dominios personalizados y rutas con slug.

**Criterio de salida:** pruebas de rutas y contenido para todas las plantillas soportadas.

## Fase 7 — Normalizar plantillas

Prioridad de adaptación:

1. CompuTienda.
2. Ecommerce.
3. Direct.
4. Default.
5. Resto de `PRODUCTION_TEMPLATE_VIEWS` por familia visual.

Para cada una:

- declarar capabilities reales;
- usar header/menu/footer compartidos;
- implementar `home`, `shop`, `about`, `contact`, `blog`, `page`;
- respetar settings canónicos;
- mantener tokens visuales y layout propios.

**Criterio de salida:** suite de contrato verde por plantilla.

## Fase 8 — Seguridad, rendimiento y accesibilidad

1. Request classes para cada formulario.
2. Validador único de URL interna/externa; `http/https` para externos.
3. Evitar HTML arbitrario en mapa/contenido; almacenar URL o datos estructurados.
4. Eager loading y eliminación de consultas desde Blade.
5. Validar contraste, focus, teclado, reduced motion y labels.
6. Sanitizar/normalizar redes, teléfono y WhatsApp.
7. Limitar y optimizar imágenes.

## Fase 9 — Rollout reversible

1. Vista previa V3 por proyecto.
2. Comparación visual legacy/V3.
3. Activación piloto en una tienda de prueba.
4. Activación gradual por plantilla/proyecto.
5. Métricas de 404, errores JS, pedidos y contacto.
6. Fallback inmediato al renderer legacy mientras dure la transición.

## Orden recomendado de archivos

1. Migraciones draft/índices.
2. Servicios/DTO/registro de settings.
3. Controladores.
4. View-model público.
5. Componentes compartidos.
6. Diseñador.
7. CompuTienda, Ecommerce y Direct.
8. Resto de plantillas.
9. Retirada del runtime heredado.

## Decisiones que requieren aprobación

1. Si los ajustes de una plantilla son defaults o deben reemplazar la identidad visual al seleccionarla.
2. Si Blog seguirá en JSON para la primera versión o se migra de inmediato a tabla.
3. Si páginas deshabilitadas deben devolver 404 o seguir accesibles fuera del menú.
4. Qué valor gana hoy entre `logo_url` y `header_logo_url` durante la migración.
5. Si se conservarán las siete pestañas o se adoptará definitivamente Plantillas + Constructor visual.
6. Qué plantillas se consideran oficialmente soportadas en la primera entrega.
