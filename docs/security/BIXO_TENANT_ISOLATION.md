# Matriz de aislamiento multiempresa

Ultima actualizacion: 2026-08-27 · medido sobre commit `2a64c15`

## Como se aisla hoy

Existen **dos mecanismos** conviviendo:

1. **Scope global** — el trait `App\Models\Traits\HasProjectScope` añade un
   `where(project_id)` automatico. Lo usan **13 de 83 modelos**.
2. **Comprobacion manual** en el controlador — `abort_unless($x->project_id === $project->id, 403)`
   o un helper equivalente (`$this->autorizarConversacion(...)`, `$this->authorizeObject(...)`).

### Defecto conocido del scope global (TD-001)

```php
$projectId = session('active_project_id') ?? session('comercial_project_id');
if ($projectId) { $builder->where(...); }
```

Si no hay sesion de proyecto, **no filtra nada**. Es un comportamiento
*fail-open*: ante ausencia de contexto abre en lugar de cerrar. Afecta a
comandos de consola, jobs en cola y a cualquier flujo cuyo contexto viva en
otra clave de sesion — por ejemplo el portal `/f/{slug}`, que autentica con
`facturacion_auth.{slug}`.

## Entidades con scope global (13)

`Caja` · `CatalogIntegration` · `Client` · `Combo` · `GuiaRemision` ·
`InventoryMovement` · `Invoice` · `Order` · `Payment` · `Product` ·
`Promotion` · `Proveedor` · `Quote`

Son las entidades comerciales nucleares. La eleccion es correcta; el problema
es la cobertura del resto, no estas.

## Resultado de la auditoria por metodo

Se analizaron los **174 metodos** de controlador que reciben un modelo por
route-model-binding (es decir, donde el ID viaja en la URL y puede manipularse).

| Situacion | Metodos | Lectura |
|---|---:|---|
| Protegido por scope global | 77 | El modelo filtra solo |
| Protegido por comprobacion manual | 78 | El controlador valida |
| **Sin proteccion visible** | **19** | Requiere revision |

### Desglose de los 19 sin proteccion

| Modelo | Metodos | Veredicto |
|---|---:|---|
| `Project` | 8 | **Aceptable** — `AdminProjectController`, plano de control de Eskala |
| `RifaVenta` | 5 | **RIESGO REAL** — ver abajo |
| `User` | 3 | **Aceptable** — `AdminUserController` / `AdminLicenseController` |
| `DemoRequest` | 2 | **Aceptable** — administracion de demos |
| Otros | 1 | Revisar caso a caso |

### RISK-001 — `RifaVenta`

`RifaController` recibe `RifaVenta $venta` por la URL y opera sin comprobar a
que proyecto pertenece. Ejemplo textual:

```php
public function cancelar(RifaVenta $venta)
{
    $venta->update(['status' => 'cancelado']);
```

Un usuario autenticado de cualquier negocio puede cancelar, previsualizar o
modificar ventas de rifa de **otro** negocio cambiando el ID en la URL.

Metodos afectados: `cancelar`, `ticketPreview`, `botPaymentProof`,
`botUpdateData` y uno mas (detalle en `storage/app/auditoria_tenant.json`).

**Correccion recomendada:** añadir `HasProjectScope` a `RifaVenta` — es una
entidad tenant-owned y encaja con el patron de las otras 13 — y añadir el test
de aislamiento correspondiente.

## Como reproducir esta auditoria

El script vive en el scratchpad de la sesion y puede reejecutarse; recorre
`app/Http/Controllers`, detecta los metodos con binding de modelo y busca en su
cuerpo comprobaciones de tenant (`abort_unless`, `abort_if`, comparacion de
`project_id`, helpers `$this->autoriz*`/`$this->authoriz*`, `forProject`).

Limitacion conocida: el detector reconoce patrones, no ejecuta el codigo. Un
metodo puede estar protegido por middleware de ruta y aparecer aqui como no
protegido. Por eso cada hallazgo se verifico leyendo el metodo antes de
clasificarlo.

## Pendiente (deuda TD-002)

No existe todavia ni un solo test automatizado de aislamiento por entidad. La
proteccion depende hoy de la disciplina en cada controlador nuevo. Ese test es
el primer entregable de la Fase 1.
