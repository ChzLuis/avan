# Entrega — Reestructuración final de BIXO (2026-08-30)

Ejecución del plan de 17 pasos: Control ordenado primero, luego Configuración
(/bixoadmin) con capacidades clasificadas, luego Operación (/bixosales), sin
duplicar entidades y sin funcionalidades nuevas.

## 1. Árbol real final de `/admin` (BIXO Control — solo Eskala)

```
/admin (middleware superadmin; invitados → admin.login)
├── Inicio                    admin.dashboard
├── EMPRESAS
│   ├── Empresas / tenants    admin.projects (estado, módulos, subdominio,
│   │                         dueño, detalle) + botón "Entrar como" (AUDITADO)
│   └── Demos                 admin.demos.index (cancelar / extender)
├── LICENCIAS
│   └── Licencias y asientos  admin.licenses (sesiones, revocación, tipos)
├── USUARIOS
│   └── Usuarios globales     admin.users (toggle admin, reset password)
├── SOPORTE Y AUDITORÍA
│   └── Auditoría de accesos  admin.audit (NUEVA, solo lectura de AccessEvent:
│                             impersonaciones, perfiles, con filtro por acción)
├── IMPORTS
│   └── Cargas masivas        admin.imports (ADR-008: migrar al Workspace)
└── CONFIGURACIÓN
    └── Configuración global  admin.settings
```

Sin CRUDs paralelos de entidades del tenant: el soporte entra por
impersonación auditada (`/bixoadmin/entrar-como/{id}` → AccessEvent
`impersonate`; salida → `impersonate_end`).

## 2. Árbol real final de `/bixoadmin` (Configuración)

```
/bixoadmin (login propio: "BIXO · Configuración" → aterriza en Mi negocio)
├── MI NEGOCIO            Datos del negocio (settings) · Sedes
├── CATÁLOGO MAESTRO      Productos · Catálogos maestros · Proveedores
├── CANALES               Constructor (tienda) · Canales WhatsApp · Código QR
├── MARKETING             SEO
├── PAGOS E INTEGRACIONES Pagos · Conectores de catálogo
├── CONFIGURACIÓN FISCAL  Certificados SUNAT (series/documentos en settings)
├── EQUIPO                Roles y permisos · Grupos
├── SISTEMA               Módulos
└── → Ir a Ventas / Operación
```

## 3. Árbol real final de `/bixosales` (Operación)

```
/bixosales (login propio: "BIXO · Ventas y operación" → aterriza en Inicio)
├── Inicio
├── COMERCIAL   POS · Venta express · Cotizaciones · Pedidos
│               (+ por módulo: Mesas/Cocina, Pedidos del bot, Reservas, Reparto)
├── CATÁLOGOS   Clientes · Inventario (+ Mis precios si revendedor)
├── FINANZAS    Cobranza · Facturas · Guías de remisión · Caja
├── ANÁLISIS    Reportes
├── (A medida por proyecto: Tickets, Pedidos web, Conversaciones)
└── → Ir a Configuración
```

Ambas caras comparten shell, sesión, identidad, permisos y entitlements
(§11): el breadcrumb marca la cara (chip morado "Configuración" / chip azul
del rubro).

## 4-6. Matrices

**Permisos**: cada entrada del menú lleva los mismos permisos que exige su
ruta (`_sidebar.blade.php`); escrituras de configuración con `settings.*`;
verbos por recurso en catálogo/pedidos (matriz completa:
`docs/auditoria/bixosales-matriz-autorizacion.md`).

**Entitlements** (vocabulario real: orders, catalog, quotes, hr, agenda,
rifas, pos, logistics, invoices, clients, bots): el menú de operación no
ofrece facturas/reservas/reparto sin módulo contratado (`ModulosPortal`
unificado) y las rutas lo exigen con `comercial.module`. El acceso directo
por URL sin módulo devuelve 403 (`ComercialEntitlementTest`).

**Capacidades restringidas**: `docs/architecture/BIXO_CAPACIDADES_MATRIZ.md`
(inventario función por función con ruta, controller, permiso, entitlement,
riesgo y decisión). Mecanismo: `App\Support\Capacidades` + middleware
`capacidad:<clave>` = ENTITLEMENT + PERMISSION + FEATURE FLAG (`cap_<clave>`,
solo Eskala lo enciende; el dueño del negocio NO se salta el flag).

## 7-10. Clasificación (resumen)

**ESKALA_ONLY** (sin flag, solo superadmin):
- Gestión de plantillas de diseño completa (`design-templates.*`: crear,
  importar, exportar, versionar, restaurar, aplicar) — capacidad `plantillas`.
- Copiar configuración desde una tienda AJENA (`builder.copy` con fuente de
  otro dueño — guardia en código).
- Todo `/admin`.

**RESTRICTED_TENANT** (flag `cap_*` + permiso):
- `cap_plantillas` — abre plantillas a un tenant concreto.
- `cap_builder_avanzado` — cambio de motor/plantilla (definido; aplicación
  fina dentro de la etapa Apariencia queda en deuda TD-025).
- `cap_seo_avanzado` — analytics/píxeles/schema (definido; separación visual
  de la pantalla SEO en deuda TD-025).

**PUBLIC_TENANT** (permiso normal): constructor nivel cliente (logo, colores,
banners, textos, secciones, imágenes, orden), QR actuales, SEO básico, pagos,
fiscal, equipo, catálogo, canales WhatsApp — detalle en la matriz.

**DEPRECATED_CANDIDATE**: `settings.design` y `settings.designer` (Diseño
legacy) — fuera de todo menú, endurecidos con `project.can:settings.diseno`.

## 11-12. Archivos y rutas modificadas (commits propios)

- `8034c9b` Control: `routes/admin.php` (+`admin.audit`),
  `components/admin-layout.blade.php` (menú agrupado),
  `admin/audit/index.blade.php` (nueva), `admin/projects/index.blade.php`
  (botón Entrar como), **eliminado** `layouts/admin.blade.php` (copia muerta).
- `55cef3a` Capacidades y caras: `BIXO_CAPACIDADES_MATRIZ.md`,
  `app/Support/Capacidades.php`, `app/Http/Middleware/VerificaCapacidad.php`,
  `bootstrap/app.php` (alias), `routes/web.php` (grupo capacidad:plantillas,
  hardening design/designer), `comercial/layouts/_sidebar.blade.php` (menú por
  cara), `comercial/layouts/app.blade.php` (chip por cara).
- `5211f7e` Logins: `layouts/guest.blade.php`, `comercial/login.blade.php`.
- Previos hoy: `a476929` (salida de impersonación auditada, fix SUNAT,
  ModulosPortal+entitlements), aterrizaje admin→Mi negocio.

## 13-16. Tests y validaciones

- Nuevos: `ControlNavegacionTest` (3), `CapacidadesRestringidasTest` (5).
- Actualizados al contrato de dos caras: `FusionPortalesTest`,
  `WorkspaceShellUnificadoTest`, `SettingsAuthorizationTest` (plantillas ya
  no se abre con solo el permiso).
- Acceso directo por URL: cubierto (capacidades 403, entitlements 403,
  Control redirige a su login sin filtrar contenido).
- Cross-tenant: `copyStore` exige dueño de la fuente o superadmin;
  `HasProjectScope`/`ComercialEntitlementTest`/`WaBotIsolationTest` vigentes.
- Suite completa: **1020 pass, 2 failed (3956 aserciones, 293s)** — los 2
  rojos son de la feature de variantes en vuelo (plantillas 3→2 motores en
  `supported-template-selector`), ajenos a esta reestructuración.

## 18. Deuda pendiente

- **TD-024**: bloques del sidebar objetivo del Control SIN backend (Productos
  y planes, Add-ons, Feature flags como sistema, Plataforma/health, Webhooks,
  Integraciones globales, Historial de soporte): no se inventaron CRUDs.
- **TD-025**: aplicación fina de `cap_builder_avanzado` (bloquear el cambio
  de motor DENTRO de la etapa Apariencia) y `cap_seo_avanzado` (separar la
  pantalla SEO en básico/avanzado). Las capacidades y el gate ya existen.
- **TD-023** (previa): re-parentar Productos/Constructor al shell unificado
  cuando aterrice la feature de variantes.
- Imports/Turnos siguen en el Control (ADR-008: migrar al Workspace).
- Validación visual en ARIN pendiente de deploy.
