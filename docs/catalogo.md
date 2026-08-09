Actúa como un Senior Software Architect, Senior Product Designer UX/UI y desarrollador full-stack especializado en:

- Laravel
- PHP
- Blade
- Alpine.js
- JavaScript
- MySQL
- Arquitectura SaaS multiempresa
- Constructores de ecommerce
- Sistemas de borrador y publicación
- Pruebas Feature, integración, regresión visual y E2E
- Diseño responsive y accesibilidad WCAG AA

Tienes autorización para analizar, diseñar e implementar completamente el nuevo Constructor Guiado de Tiendas Virtuales, desde la fase B0 hasta la fase B8.

Debes trabajar directamente sobre el repositorio actual.

FUENTE PRINCIPAL DE VERDAD

Antes de modificar cualquier archivo, lee completamente:

docs/rediseno-constructor-auditoria.md

También revisa:

- scripts/designer_contract.json
- documentación técnica existente
- README del proyecto
- rutas
- controladores
- servicios
- modelos
- migraciones
- vistas Blade
- scripts Alpine y JavaScript
- pruebas actuales
- configuración de despliegue
- historial reciente de Git relacionado con el diseñador

La auditoría corresponde al commit:

588b304

No asumas que el código sigue exactamente igual. Compara la auditoría con el estado actual del repositorio.

Si encuentras diferencias, actualiza primero:

docs/rediseno-constructor-auditoria.md

y registra las diferencias en:

docs/builder-implementation-status.md

No repitas una auditoría completa si la información sigue vigente. Verifica únicamente los puntos necesarios antes de implementar.

OBJETIVO PRINCIPAL

Transformar los tres diseñadores actuales en una única experiencia llamada:

Constructor

El sistema debe permitir que un operador no técnico pueda:

1. Configurar los datos del negocio.
2. Diseñar la apariencia.
3. Construir la página de inicio.
4. Cargar y revisar el catálogo.
5. Configurar venta, pagos y entregas.
6. Revisar y publicar la tienda.

La meta operativa es completar una tienda profesional en un máximo de 5 horas.

La nueva experiencia debe reemplazar la navegación triple actual, pero debe mantener el diseñador anterior como “Modo clásico” durante la transición.

PRINCIPIOS NO NEGOCIABLES

1. No eliminar funcionalidades actuales.
2. No romper tiendas existentes.
3. No cambiar el render público innecesariamente.
4. No duplicar configuraciones.
5. No crear una segunda fuente de verdad.
6. No escribir directamente en producción durante el autosave.
7. No presentar como “borrador” algo que ya está visible públicamente.
8. No presentar como “Publicar” una acción meramente visual.
9. No utilizar mocks para la vista previa.
10. No reescribir servicios estables sin una justificación técnica.
11. No desplegar cambios sin ejecutar las pruebas correspondientes.
12. No eliminar el modo clásico durante esta implementación.
13. No crear endpoints de escritura nuevos para funcionalidades existentes, salvo que sean indispensables para implementar correctamente borrador, publicación, copia o acciones masivas.
14. Toda escritura debe respetar permisos, proyecto actual y aislamiento multiempresa.
15. Toda interfaz nueva debe utilizar nombres oficiales únicos para sus componentes.

NOMBRES OFICIALES

Utiliza estos nombres en toda la interfaz nueva:

- Hero / Banner principal / Portada Hero → Slider principal
- Promociones / Banners → Anuncios
- Topbar / Announcement → Barra superior
- Oferta del día / Flash Sale → Oferta con contador
- Trust Bar / Compra con confianza → Beneficios
- Diseño / Experiencia / Constructor visual → Constructor
- Portada → Página de inicio

No renombres claves internas existentes si eso rompe compatibilidad. El nombre oficial se aplica a la experiencia nueva y a componentes nuevos.

FORMA DE TRABAJO

Implementa el proyecto completo por fases:

- B0
- B1
- B2
- B3
- B4
- B5
- B6
- B7
- B8

No solicites aprobación entre cada fase, salvo que encuentres:

- una decisión que pueda ocasionar pérdida de datos;
- una migración destructiva;
- una contradicción que no pueda resolverse desde el código;
- ausencia total del procedimiento de despliegue;
- credenciales o variables indispensables que no estén disponibles;
- incompatibilidad grave con la arquitectura actual.

Si aparece uno de estos bloqueos:

1. No improvises.
2. Documenta el bloqueo.
3. Continúa con las partes no bloqueadas.
4. Explica exactamente qué decisión se necesita.

Mantén actualizado durante todo el trabajo:

docs/builder-implementation-status.md

El documento debe contener:

- fase actual;
- tareas terminadas;
- tareas pendientes;
- archivos modificados;
- migraciones;
- endpoints;
- pruebas ejecutadas;
- pruebas pendientes;
- riesgos;
- decisiones técnicas;
- commit correspondiente;
- instrucciones para continuar si la sesión se interrumpe.

COMMITS

Crea un commit separado por fase.

Formato recomendado:

feat(builder-b0): add progress and publication rule foundation
feat(builder-b1): add guided builder shell and live preview
feat(builder-b2): implement business and appearance stages
feat(builder-b3): implement unified homepage block editor
feat(builder-b4): implement catalog metrics and bulk operations
feat(builder-b5): implement sales and operations stage
feat(builder-b6): implement publication checklist and workflow
feat(builder-b7): add expert mode presets and store copy
test(builder-b8): complete QA regression and builder rollout

No mezcles múltiples fases grandes en un único commit.

ARQUITECTURA GENERAL

Crear la nueva ruta:

GET /bixoadmin/settings/builder

Nombre de ruta sugerido:

settings.builder

Debe estar protegida por:

- autenticación;
- proyecto actual;
- permisos correspondientes;
- feature flag por proyecto.

La estructura visual tendrá cuatro zonas:

1. Barra superior fija.
2. Navegación lateral de etapas.
3. Panel central.
4. Vista previa real.

ETAPAS

1. Datos del negocio.
2. Apariencia.
3. Página de inicio.
4. Catálogo.
5. Venta y operación.
6. Revisar y publicar.

Cada etapa debe mostrar:

- número;
- nombre;
- descripción;
- estado;
- porcentaje;
- pendientes;
- tiempo estimado;
- severidad máxima de sus problemas.

ESTADOS DE ETAPA

Utiliza estados consistentes:

- pending
- in_progress
- complete
- warning
- blocked

No determines el progreso solamente por haber visitado la etapa.

El progreso debe depender de reglas reales y datos persistidos.

BARRA SUPERIOR

Debe contener:

- regresar a tiendas;
- nombre de la tienda;
- progreso total;
- estado del autosave;
- último guardado;
- deshacer;
- rehacer;
- selector PC/móvil;
- abrir vista previa completa;
- selector Rápido/Experto;
- botón Publicar.

Publicar debe estar:

- activo cuando no existan críticos;
- bloqueado cuando existan críticos;
- acompañado del número de pendientes;
- conectado al flujo real de publicación.

MODO CLÁSICO

Mantén el diseñador anterior.

Debe existir un acceso secundario:

Abrir modo clásico

Reglas:

- no mostrar el modo clásico como opción principal;
- no duplicar ambos constructores en el menú principal;
- no eliminar rutas antiguas;
- permitir rollback inmediato por proyecto;
- mantener compatibilidad durante uno o dos meses de transición.

FEATURE FLAG

Implementa un estado por proyecto:

- disabled
- beta
- enabled

El feature flag debe permitir:

- activar el constructor en una tienda específica;
- activar beta para tiendas seleccionadas;
- mantener el modo clásico en las demás;
- regresar al modo clásico sin alterar datos;
- controlar la entrada del menú.

No dependas únicamente de un flag global en .env.

MODELO REAL DE BORRADOR Y PUBLICACIÓN

Antes de desarrollar el autosave, define e implementa el modelo completo:

Editar
→ Autoguardar borrador
→ Previsualizar borrador
→ Validar
→ Publicar
→ Hacer visible públicamente

Los cambios autoguardados no deben modificar la tienda pública.

El público debe seguir viendo la última versión publicada.

Analiza qué recursos ya soportan borrador:

- store_sections
- draft_*
- publish_from
- publish_until
- state y publish

Analiza qué recursos no tienen borrador completo:

- project_settings
- navegación
- menú
- páginas
- popup
- configuraciones de venta
- otras configuraciones globales

Selecciona una estrategia consistente.

Opciones válidas:

- revisiones por proyecto;
- snapshot de configuración;
- tabla de borradores;
- payload JSON versionado;
- claves de borrador controladas;
- otra solución equivalente bien documentada.

La solución debe garantizar:

- borrador persistente;
- preview del borrador;
- versión pública estable;
- promoción transaccional;
- rollback;
- aislamiento por proyecto;
- compatibilidad con claves legacy;
- no duplicar permanentemente la lógica de lectura pública.

DOCUMENTA LA DECISIÓN EN:

docs/builder-draft-publication-architecture.md

La publicación debe ser transaccional.

Flujo mínimo:

1. Obtener proyecto y permisos.
2. Bloquear o controlar concurrencia.
3. Ejecutar checklist.
4. Impedir publicación si existen críticos.
5. Iniciar transacción.
6. Crear snapshot de la versión pública anterior.
7. Promover ajustes.
8. Promover secciones.
9. Promover navegación.
10. Promover páginas.
11. Promover popup y configuraciones asociadas.
12. Registrar versión publicada.
13. Confirmar transacción.
14. Invalidar cachés.
15. Actualizar preview y estado.
16. Registrar evento de publicación.

Si una operación falla:

- rollback completo;
- conservar versión pública anterior;
- mostrar error;
- permitir reintentar;
- registrar información técnica sin exponer datos sensibles.

BUILDER RULE REGISTRY

Crea una única fuente de reglas:

app/Storefront/BuilderRuleRegistry.php

No dupliques reglas entre progreso y checklist.

Cada regla debe incluir como mínimo:

- code
- stage
- severity
- weight
- message
- target
- evaluator
- optional quick_fix
- blocks_publish

Ejemplo conceptual:

[
    'code' => 'business.logo_missing',
    'stage' => 'business',
    'severity' => 'critical',
    'weight' => 8,
    'message' => 'Sube el logo de tu negocio.',
    'target' => 'business.logo',
    'blocks_publish' => true,
]

Severidades:

- complete
- recommendation
- warning
- critical

BuilderProgress y PublishChecklist deben consumir este registro.

Crea:

app/Storefront/BuilderProgress.php
app/Storefront/PublishChecklist.php

El progreso no debe ser un promedio simple de seis etapas.

Debe utilizar pesos.

Una configuración obligatoria debe pesar más que una recomendación estética.

Todo resultado debe devolver:

- porcentaje general;
- porcentaje por etapa;
- tareas completas;
- pendientes;
- críticos;
- advertencias;
- recomendaciones;
- destino de corrección.

ENDPOINTS DE LECTURA

Crea endpoints de lectura como:

GET /bixoadmin/settings/builder/progress
GET /bixoadmin/settings/builder/checklist
GET /bixoadmin/settings/builder/catalog/metrics

Los endpoints deben:

- estar protegidos por permisos;
- tomar el proyecto desde el contexto seguro;
- no aceptar arbitrariamente un project_id de otro usuario;
- devolver JSON consistente;
- incluir pruebas de aislamiento.

AUTOSAVE

Implementa adaptadores por recurso.

Arquitectura sugerida:

BuilderSaveManager
├── SettingsSaveAdapter
├── HomeContentSaveAdapter
├── HomeStateSaveAdapter
├── NavigationSaveAdapter
├── PageSaveAdapter
├── PopupSaveAdapter
└── MediaSaveAdapter

Reutiliza los endpoints canónicos existentes cuando sean compatibles con el borrador.

Si el nuevo modelo de borrador requiere un endpoint intermedio, crea un único contrato bien definido y no dupliques toda la lógica de validación.

Estados visibles:

- clean
- dirty
- saving
- saved
- error
- offline

Comportamiento:

- debounce de 500 a 800 ms para texto;
- guardado inmediato para switches importantes;
- cola independiente por recurso;
- número de secuencia;
- ignorar respuestas antiguas;
- no sobrescribir cambios recientes;
- reintento manual;
- reintento controlado;
- advertencia al cerrar con operaciones pendientes;
- persistencia al cambiar de etapa;
- manejo de pérdida de conexión;
- mensajes sencillos para el usuario.

El estado global debe reflejar el peor estado de los recursos.

UNDO Y REDO

Undo y redo no deben ser solamente visuales.

Cada acción debe:

1. restaurar el valor en Alpine;
2. persistir el cambio;
3. actualizar el borrador;
4. refrescar la vista previa;
5. actualizar progreso y checklist.

Mantén un límite razonable, por ejemplo 50 acciones.

No incluyas archivos o imágenes pesadas completas en el historial. Guarda referencias y metadatos.

PREVIEW REAL

No uses mocks.

Utiliza el storefront real mediante iframe y la ruta existente de previewStorefront o su equivalente actual.

El preview debe:

- cargar con la sesión del administrador;
- mostrar el borrador;
- no afectar el público;
- permitir PC y móvil;
- refrescar después del autosave;
- mantener scroll cuando sea posible;
- mostrar errores de carga;
- permitir abrir en nueva pestaña;
- poder ampliarse a pantalla completa.

COMUNICACIÓN POSTMESSAGE

Define un contrato estable.

Eventos sugeridos:

Desde el storefront:

- storefront:ready
- storefront:section-selected
- storefront:navigation-complete
- storefront:error

Desde el constructor:

- builder:select-section
- builder:highlight-section
- builder:refresh
- builder:set-device
- builder:clear-highlight

Valida:

- origin;
- estructura del mensaje;
- proyecto actual;
- tipos permitidos.

No accedas directamente al DOM interno del iframe desde el constructor.

MARCADORES DEL STOREFRONT

Las secciones editables deben exponer identificadores estables:

data-builder-section
data-builder-instance

No dependas de clases CSS específicas de una plantilla.

El cambio debe ser no destructivo y no modificar visualmente la tienda pública.

B0 — FUNDACIÓN

Implementa:

1. Verificación final de la auditoría.
2. Feature flag por proyecto.
3. Ruta inicial del builder.
4. BuilderRuleRegistry.
5. BuilderProgress.
6. PublishChecklist.
7. Endpoints de lectura.
8. Estrategia real de borrador/publicado.
9. Contrato de autosave.
10. Contrato postMessage.
11. Snapshots base de tiendas existentes.
12. Pruebas de permisos.
13. Pruebas de aislamiento.
14. Documentación técnica.

No modifiques visualmente las tiendas públicas en B0.

B1 — SHELL Y PRUEBA VERTICAL

Implementa el shell completo:

- topbar;
- navegación lateral;
- panel central;
- preview;
- progreso;
- estados de autosave;
- responsive;
- modo clásico;
- feature flag;
- manejo de errores;
- navegación por teclado;
- focus-visible;
- targets de 44 px;
- contraste AA.

Implementa primero una prueba vertical completa:

Color principal:

Editar
→ autoguardar borrador
→ actualizar preview
→ deshacer
→ rehacer
→ checklist
→ publicar
→ verificar público

Implementa una segunda prueba vertical:

Categorías:

Activar o desactivar
→ guardar borrador
→ preview
→ publicar
→ validar storefront público

No avances a B2 hasta que ambas pruebas funcionen.

B2 — DATOS DEL NEGOCIO Y APARIENCIA

ETAPA 1: DATOS DEL NEGOCIO

Campos esenciales:

- nombre comercial;
- rubro;
- logo;
- WhatsApp;
- correo;
- dirección;
- ciudad;
- moneda;
- tipo de venta.

No dupliques campos equivalentes.

Resuelve la fuente canónica del WhatsApp.

Al seleccionar rubro:

- recomendar plantilla;
- recomendar paleta;
- recomendar tipografía;
- recomendar categorías;
- recomendar secciones;
- recomendar método de venta;
- ofrecer contenido demo.

ETAPA 2: APARIENCIA

Unifica:

- plantilla;
- marca;
- identidad;
- logo;
- favicon;
- colores;
- tipografía;
- encabezado;
- barra superior;
- botones;
- tarjetas;
- footer.

Modo rápido:

- tarjetas visuales;
- presets;
- generación de paleta desde logo;
- estilos predefinidos;
- campos esenciales.

Modo experto:

- colores individuales;
- hover;
- sombras;
- radios;
- bordes;
- tamaños;
- espaciados;
- tipografía por componente;
- comportamiento responsive.

No muestres opciones avanzadas de forma predeterminada.

B3 — PÁGINA DE INICIO

Crea una única lista de bloques.

Bloques iniciales:

- Barra superior
- Encabezado
- Slider principal
- Beneficios
- Anuncios
- Categorías
- Oferta con contador
- Productos con descuento
- Productos destacados
- Blog
- Footer

Un bloque debe contener en un solo editor:

- contenido;
- diseño;
- variante;
- visibilidad;
- estado;
- programación;
- configuración PC;
- configuración móvil.

No mantengas una pantalla para ordenar y otra para diseñar el mismo bloque.

Cada tarjeta debe permitir:

- editar;
- activar/desactivar;
- reordenar;
- duplicar cuando sea compatible;
- eliminar cuando corresponda;
- PC;
- móvil;
- estado;
- pendientes;
- miniatura;
- volver a lista.

Utiliza los endpoints existentes:

- settings.experience.home
- settings.experience.home.state
- settings.design.update
- settings.upload-logo

Respeta claves canónicas y aliases legacy.

Mantén las variantes ya implementadas:

- categorías “círculos con banda”;
- contador “banda compacta”;
- variantes existentes por sección;
- orden instantáneo actual;
- inspector conectado a claves reales.

La vista previa debe resaltar el bloque activo.

Incluye:

- Agregar sección
- Aplicar estructura recomendada
- Copiar estructura de otra tienda

B4 — CATÁLOGO

Crea métricas:

- total;
- publicados;
- inactivos;
- incompletos;
- sin imagen;
- sin precio;
- sin categoría;
- SKU duplicados;
- categorías vacías;
- stock agotado cuando corresponda.

Acciones iniciales:

- Importar Excel
- Crear producto
- Copiar productos
- Usar catálogo demo

Implementa acciones masivas seguras:

- cambiar categoría;
- cambiar estado;
- incrementar o disminuir precio;
- establecer precio;
- asignar precio mayorista;
- eliminar precio mayorista;
- publicar;
- despublicar;
- seleccionar todos los resultados filtrados.

No realices eliminación masiva física sin confirmación reforzada.

Para operaciones masivas:

- validar permisos;
- utilizar transacciones;
- devolver cantidad afectada;
- devolver errores parciales;
- registrar actividad;
- proteger contra selección de productos de otro proyecto.

Accesos rápidos:

- Corregir productos sin imagen
- Corregir productos sin precio
- Resolver SKU duplicados
- Revisar productos incompletos

Mantén el CRUD de productos existente como fuente principal.

No construyas un segundo CRUD completo si no es necesario.

B5 — VENTA Y OPERACIÓN

Organiza la etapa en cuatro grupos:

1. Cómo vender.
2. Cómo cobrar.
3. Cómo entregar.
4. Información y confianza.

Cómo vender:

- compra directa;
- cotización;
- pedido por WhatsApp;
- minorista;
- mayorista;
- ambos.

Cómo cobrar:

- Yape;
- Plin;
- transferencia;
- pago contra entrega;
- pasarela existente.

Cómo entregar:

- recojo;
- envío local;
- envío nacional;
- costo fijo;
- gratis desde;
- tarifas por zona si existen;
- coordinación por WhatsApp.

Información y confianza:

- Nosotros;
- Contacto;
- Preguntas frecuentes;
- Términos;
- Privacidad;
- Libro de reclamaciones.

Utiliza revelado progresivo.

No muestres configuraciones de opciones desactivadas.

Reutiliza:

- settings.design.update
- settings.experience.page
- settings.experience.popup
- StoreNavigationController
- endpoints canónicos actuales

B6 — REVISAR Y PUBLICAR

Construye el checklist usando exclusivamente BuilderRuleRegistry.

Debe validar como mínimo:

Datos:

- nombre;
- logo;
- WhatsApp;
- correo cuando sea requerido;
- dirección cuando corresponda.

Diseño:

- plantilla;
- color principal;
- vista móvil;
- slider;
- bloques esenciales.

Catálogo:

- productos;
- imágenes;
- precios;
- SKU;
- categorías.

Venta:

- modo de venta;
- pago;
- entrega;
- WhatsApp;
- checkout.

Confianza:

- Nosotros;
- Contacto;
- Términos;
- Privacidad;
- Libro de reclamaciones cuando corresponda.

Clasificación:

- Crítico
- Advertencia
- Recomendación
- Completo

Los críticos bloquean.

Las advertencias permiten publicar después de confirmación.

Cada problema debe incluir:

- explicación;
- etapa;
- campo;
- botón Corregir;
- deep-link al componente exacto;
- quick fix cuando sea seguro.

FLUJO DE PUBLICACIÓN

1. Ejecutar checklist.
2. Mostrar resumen.
3. Confirmar advertencias.
4. Publicar transaccionalmente.
5. Invalidar cachés.
6. Verificar versión pública.
7. Mostrar éxito.

Pantalla de éxito:

- URL;
- abrir tienda;
- copiar enlace;
- QR;
- compartir por WhatsApp;
- fecha de publicación;
- versión.

B7 — EXPERTO, PRESETS Y COPIAR TIENDA

MODO EXPERTO

Agregar panel global:

Ajustes avanzados

Debe incluir opciones poco frecuentes:

- SEO técnico;
- dominio;
- scripts;
- integraciones;
- código personalizado si ya existe;
- correos;
- roles;
- permisos;
- configuración avanzada del checkout;
- galería de plantillas clásicas.

No expongas configuraciones que el backend no soporte.

PRESETS

Crea arquitectura para diez rubros:

- Tecnología
- Ferretería
- Muebles
- Ropa
- Ropa de bebé
- Restaurante
- Veterinaria
- Óptica
- Servicios
- Mayorista

Cada preset puede incluir:

- plantilla;
- paleta;
- tipografía;
- secciones;
- orden;
- categorías;
- textos iniciales;
- métodos de venta;
- datos demo opcionales.

Los presets deben estar en una fuente mantenible:

- config;
- clases dedicadas;
- archivos estructurados.

No hardcodees grandes payloads directamente en una vista.

Aplicar preset debe:

- mostrar qué modificará;
- permitir cancelar;
- preservar productos existentes salvo confirmación;
- guardar como borrador;
- permitir deshacer.

COPIAR TIENDA

Crea:

app/Storefront/StoreCopyService.php

Permite copiar selectivamente:

- apariencia;
- encabezado;
- barra superior;
- footer;
- página de inicio;
- menú;
- categorías;
- páginas;
- venta;
- pagos;
- entrega.

Condiciones:

- solo tiendas autorizadas;
- solo owner o superadministrador;
- operación transaccional;
- confirmación;
- resumen previo;
- no copiar productos por defecto;
- no copiar dominios;
- no copiar credenciales;
- no copiar IDs sin mapear;
- duplicar correctamente imágenes o referencias;
- mantener aislamiento de proyecto;
- guardar en borrador;
- registrar auditoría.

B8 — QA, REGRESIÓN Y ACTIVACIÓN

Ejecuta QA completo.

PRUEBAS UNITARIAS Y FEATURE

Crea o completa:

- BuilderProgressTest
- PublishChecklistTest
- BuilderRuleRegistryTest
- BuilderPermissionsTest
- BuilderProjectIsolationTest
- BuilderDraftTest
- BuilderPublishTest
- BuilderRollbackTest
- BuilderStageBusinessTest
- BuilderStageAppearanceTest
- BuilderStageHomeTest
- BuilderStageCatalogTest
- BuilderStageSalesTest
- BuilderCopyStoreTest
- BuilderCatalogBulkTest
- BuilderFeatureFlagTest
- BuilderPreviewTest

PRUEBAS E2E

Casos mínimos:

1. Crear tienda desde preset.
2. Subir logo.
3. Cambiar paleta.
4. Configurar slider.
5. Reordenar secciones.
6. Importar productos.
7. Corregir producto sin precio.
8. Configurar WhatsApp.
9. Configurar pago.
10. Configurar entrega.
11. Resolver críticos.
12. Publicar.
13. Abrir tienda pública.
14. Validar móvil.
15. Regresar a modo clásico.
16. Volver al constructor sin pérdida de datos.

REGRESIÓN DE TIENDAS EXISTENTES

No utilices comparación byte a byte sin normalización.

Para las siete tiendas actuales valida:

- misma plantilla;
- mismos datos visibles;
- mismo orden de secciones;
- mismas configuraciones;
- mismas rutas;
- misma visibilidad;
- comportamiento responsive equivalente;
- DOM normalizado equivalente;
- capturas visuales sin diferencias relevantes;
- navegación;
- carrito;
- producto;
- páginas;
- contacto;
- checkout;
- reclamos cuando corresponda.

Genera snapshots antes y después.

Ignora correctamente:

- CSRF;
- timestamps;
- identificadores dinámicos;
- valores de sesión;
- tokens;
- atributos no deterministas.

ACCESIBILIDAD

Validar:

- contraste AA;
- teclado;
- focus-visible;
- labels;
- aria;
- botones de 44 px;
- navegación del drawer;
- cierre de modales;
- lectores de pantalla;
- prefers-reduced-motion.

RESPONSIVE

Escritorio:

- cuatro zonas;
- preview fijo;
- panel central flexible.

Tablet:

- lateral compacta;
- preview en drawer.

Móvil:

- indicador de etapas;
- formulario de una columna;
- preview en pantalla completa;
- barra inferior Atrás / Continuar;
- botones accesibles.

INSTRUMENTACIÓN

Registra eventos internos:

- builder_opened
- builder_classic_opened
- builder_stage_started
- builder_stage_completed
- builder_field_error
- builder_autosave_failed
- builder_preview_opened
- builder_preset_applied
- builder_bulk_action
- builder_publish_blocked
- builder_publish_started
- builder_published
- builder_publish_failed

No envíes información sensible.

Métricas necesarias:

- tiempo total hasta publicación;
- tiempo por etapa;
- etapas abandonadas;
- errores frecuentes;
- uso del modo clásico;
- uso de presets;
- uso de acciones masivas;
- cantidad de bloqueos;
- tiempo de respuesta del autosave;
- errores de preview.

DESIGN SYSTEM

Reutiliza los tokens actuales --dz-* cuando sean adecuados.

Mantén:

- acento índigo;
- tipografía system-ui;
- radios consistentes;
- sombras suaves;
- iconografía DesignerIcons;
- transiciones discretas;
- contraste AA.

Componentes:

- botones;
- campos;
- tarjetas;
- switches;
- segmentos;
- badges;
- progreso;
- toast;
- modal;
- drawer;
- skeleton;
- empty state;
- alertas;
- checklist;
- block card;
- preview frame;
- stage nav.

No agregues una librería pesada de componentes sin necesidad.

SEGURIDAD

Revisa:

- autorización por proyecto;
- mass assignment;
- CSRF;
- XSS en textos configurables;
- sanitización;
- carga de archivos;
- MIME;
- tamaño;
- nombres de archivo;
- rutas;
- IDs manipulados;
- copy store;
- bulk actions;
- postMessage origin;
- permisos owner/superadmin;
- separación entre tenants.

RENDIMIENTO

Evita:

- recargar todo el builder en cada cambio;
- consultas N+1;
- cargar todos los productos para métricas;
- refrescar continuamente el iframe;
- enviar formularios completos por cada tecla;
- guardar campos que no cambiaron.

Utiliza:

- debounce;
- índices;
- agregaciones SQL;
- caché controlada;
- invalidación después de publicar;
- payloads parciales;
- lazy loading;
- refresco del preview agrupado.

MIGRACIONES

Toda migración debe:

- ser reversible;
- tener down();
- no perder datos;
- funcionar con datos existentes;
- incluir índices;
- usar valores por defecto seguros;
- probarse en una copia o entorno local;
- quedar documentada.

No ejecutes una migración destructiva en producción.

DESPLIEGUE

Busca primero el procedimiento existente del proyecto.

Revisa:

- scripts;
- documentación;
- GitHub Actions;
- comandos de deploy;
- configuración VPS;
- FPM;
- OPcache;
- caches de Laravel;
- migraciones.

No inventes credenciales ni comandos particulares del servidor.

Si existe un procedimiento documentado:

1. Ejecuta pruebas locales.
2. Crea respaldo o verifica respaldo.
3. Despliega según el procedimiento.
4. Ejecuta migraciones seguras.
5. Limpia caches necesarias.
6. Reinicia o recarga PHP-FPM si corresponde.
7. Verifica health check.
8. Verifica builder.
9. Verifica tiendas públicas.
10. Documenta resultado.

Si no existe un procedimiento verificable, completa el desarrollo y deja:

docs/builder-deployment-plan.md

con los comandos propuestos y los datos exactos que faltan.

No despliegues de forma improvisada.

CRITERIOS DE ACEPTACIÓN

El proyecto se considera terminado cuando:

1. Existe una sola navegación principal.
2. No existen configuraciones duplicadas en la UI nueva.
3. Cada componente tiene un nombre oficial único.
4. El progreso se calcula con datos reales.
5. El checklist y el progreso usan las mismas reglas.
6. El autosave guarda borrador, no producción.
7. El preview muestra el borrador real.
8. Publicar promueve los cambios transaccionalmente.
9. Undo y redo persisten.
10. El preview PC/móvil funciona.
11. El modo rápido funciona.
12. El modo experto funciona.
13. Los presets funcionan.
14. Copiar tienda funciona con permisos.
15. Las acciones masivas del catálogo funcionan.
16. Los críticos bloquean publicación.
17. Las advertencias requieren confirmación.
18. El modo clásico permanece disponible.
19. Las siete tiendas existentes no presentan regresiones relevantes.
20. Las pruebas unitarias, Feature y E2E están en verde.
21. La interfaz cumple accesibilidad AA.
22. El constructor funciona en escritorio, tablet y móvil.
23. Se registran métricas de uso.
24. Existe documentación técnica.
25. Existe documentación de despliegue.
26. Una tienda demo puede completarse en un máximo de 5 horas.

ARCHIVOS ESPERADOS

La arquitectura propuesta contempla, como mínimo:

app/Storefront/BuilderRuleRegistry.php
app/Storefront/BuilderProgress.php
app/Storefront/PublishChecklist.php
app/Storefront/StoreCopyService.php
app/Storefront/StagePresets.php
app/Http/Controllers/StoreBuilderController.php

resources/views/settings/builder/index.blade.php
resources/views/settings/builder/topbar.blade.php
resources/views/settings/builder/stage-nav.blade.php
resources/views/settings/builder/preview.blade.php
resources/views/settings/builder/styles.blade.php
resources/views/settings/builder/script.blade.php
resources/views/settings/builder/advanced.blade.php

resources/views/settings/builder/stages/business.blade.php
resources/views/settings/builder/stages/appearance.blade.php
resources/views/settings/builder/stages/home.blade.php
resources/views/settings/builder/stages/catalog.blade.php
resources/views/settings/builder/stages/sales.blade.php
resources/views/settings/builder/stages/publish.blade.php

resources/views/settings/builder/blocks/

tests/Feature/Builder/

docs/builder-implementation-status.md
docs/builder-draft-publication-architecture.md
docs/builder-deployment-plan.md
docs/builder-final-report.md

Los nombres pueden ajustarse si la arquitectura real del proyecto lo exige. Documenta cualquier cambio.

ENTREGA FINAL

Al terminar, crea:

docs/builder-final-report.md

Debe incluir:

1. Resumen ejecutivo.
2. Arquitectura implementada.
3. Modelo de borrador/publicación.
4. Rutas creadas.
5. Endpoints creados.
6. Servicios creados.
7. Archivos modificados.
8. Migraciones.
9. Feature flags.
10. Reglas del checklist.
11. Flujo de publicación.
12. Seguridad.
13. Pruebas ejecutadas.
14. Resultado de regresión.
15. Resultado de accesibilidad.
16. Resultado responsive.
17. Métricas instrumentadas.
18. Commits.
19. Procedimiento de despliegue.
20. Rollback.
21. Riesgos conocidos.
22. Trabajo futuro no crítico.
23. Evidencia de los criterios de aceptación.

RESPUESTA FINAL DEL AGENTE

Cuando termines, responde con:

- estado general;
- fases completadas;
- commit de cada fase;
- pruebas ejecutadas;
- migraciones;
- estado del despliegue;
- resultado de regresión;
- riesgos pendientes;
- enlace o ruta a builder-final-report.md.

No respondas únicamente “terminado”.

Muestra evidencia verificable.

COMIENZA AHORA

1. Lee la auditoría.
2. Revisa el estado actual de Git.
3. Verifica que el árbol de trabajo esté limpio.
4. Crea o confirma la rama de trabajo.
5. Crea builder-implementation-status.md.
6. Ejecuta pruebas base.
7. Captura snapshots base.
8. Implementa B0.
9. Continúa ordenadamente hasta B8.