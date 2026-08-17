# RBAC de produccion (ARIN) — fuente de verdad

**Fecha:** 2026-08-14
**Regla adoptada:** toda auditoria de autorizacion se hace **contra ARIN**.
Local sirve solo para ejecutar pruebas.

---

## 1. Divergencias local vs produccion

| | Local | ARIN |
|---|---:|---:|
| Roles | 13 | **12** |
| Permisos | ~80 | **90** |
| Usuarios no superadmin | 1 | **20+** |
| `admin` (permisos) | 21 | **67** |
| `gerente` (permisos) | 49 | **65** |
| `solo_lectura` | 11 | **15** |
| `contador` | 9 | **13** |
| `vendedor` | 17 | **20** |

**`superadmin` no existe en ARIN.** En local es un rol con 57 permisos. En
produccion el acceso total lo da `users.is_superadmin = 1` (usuario `admin`,
id 4) a traves del atajo de `Gate::before`, no un rol.

**`orders.descuento` SI existe en ARIN** y lo tienen `gerente` y `vendedor`. El
riesgo de `PermissionDoesNotExist` en `PosController` **no aplica a produccion**;
es exclusivo de local, donde el permiso no se sembro.

**Universo A (legacy) en ARIN:** `owner` (21), `comercial` (10), `logistica` (4),
`operaciones` (6) siguen sin ningun permiso `dominio.accion`. `admin` (67) tiene
ambos universos.

### Consecuencia

Las conclusiones deducidas de local sobre radio de impacto son **inservibles**.
Pendiente para una fase aparte (NO en este hotfix): sincronizar
`PermissionsSeeder` con ARIN para que local deje de mentir.

---

## 2. Propiedad del pedido: el sistema NO puede distinguirla

Pregunta a resolver: revendedor edita **cualquier** pedido del proyecto (A) o
**solo los suyos** (B).

| Comprobacion | Resultado |
|---|---|
| `orders.created_by` | **existe**, `bigint unsigned`, indexada |
| Quien la rellena | **solo** `PosController@store` (`:138`) |
| `OrderController` referencias a `created_by` / `auth()->id()` | **0** |
| Scope de propiedad en `index` / `show` / `update` | **ninguno** |
| Pedidos en ARIN con autor | **3 de 21** |
| `updated_by` o equivalente | **no existe** |

`OrderController@store` —la via del panel y del portal— **no escribe
`created_by`**. Solo el POS lo hace. Por eso 18 de 21 pedidos no tienen autor.

**No se puede implementar B hoy.** Autorizar por propiedad exigiria:

1. Que `OrderController@store` rellene `created_by`.
2. Una politica (`OrderPolicy`) que compare `created_by` con el usuario.
3. Decidir que ocurre con los 18 pedidos historicos sin autor: hoy nadie seria
   su propietario y quedarian ineditables para un rol restringido a lo propio.

Se reporta antes de tocar permisos, como se pidio. **Nada modificado.**

---

## 3. Riesgo de negocio: NO hubo regresion funcional

Evidencia empirica en ARIN, no deduccion por nombre de rol:

```
pedidos creados por cada uno de los 10 revendedores  ->  0
```

| Autor de eventos (`order_events`) | Rol | Eventos | Ultimo |
|---|---|---:|---|
| (sistema / bot) | — | 13 | 2026-08-13 |
| `admin` (id 4) | `is_superadmin=1` | 4 | 2026-08-14 |
| `pool.espinoza` (id 21) | `gerente` | 2 | 2026-08-13 |

**Cero eventos** de `revendedor`, `vendedor`, `almacen`, `contador` o
`solo_lectura`. Los unicos operadores humanos son un superadmin (exento por
`Gate::before`) y un `gerente` (tiene los 10 permisos de pedidos y cotizaciones).

Los 10 revendedores corresponden al proyecto de prueba ECCOMERCE POC.

**Conclusion: el hotfix desplegado no rompio ninguna operacion en uso.** La
afirmacion original de "radio de impacto cero" era correcta en el resultado pero
se alcanzo por un razonamiento invalido (datos de local). Ahora esta verificada
contra uso real de produccion.

---

## 4. Matriz de impacto por rol — Pedidos y Cotizaciones (ARIN)

`SI(bug)` = podia solo por la vulnerabilidad · `SI` = permiso real ·
`NO` = bloqueado tras el hotfix

### Pedidos

| Rol | ver | crear | editar | eliminar | pagar | emitir compr. | WhatsApp | etiqueta |
|---|---|---|---|---|---|---|---|---|
| `admin` (superadmin) | SI | SI | SI | SI | SI | SI | SI | SI |
| `gerente` | SI | SI | SI | SI | SI | SI | SI | SI |
| `vendedor` | SI | SI | SI | ~~SI(bug)~~ **NO** | SI | SI | SI | SI |
| `revendedor` | SI | SI | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | NO | NO | NO | SI |
| `almacen` | SI | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | NO | NO | NO | SI |
| `contador` | SI | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | NO | NO | NO | SI |
| `solo_lectura` | SI | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | NO | NO | NO | SI |

### Cotizaciones

| Rol | ver | crear | editar | eliminar | duplicar | enviar | convertir | marcar vista |
|---|---|---|---|---|---|---|---|---|
| `admin` (superadmin) | SI | SI | SI | SI | SI | SI | SI | SI |
| `gerente` | SI | SI | SI | SI | SI | SI | SI | SI |
| `vendedor` | SI | SI | SI | ~~SI(bug)~~ **NO** | SI | SI | SI | SI |
| `revendedor` | SI | SI | SI | ~~SI(bug)~~ **NO** | SI | SI | SI | SI |
| `contador` | SI | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | NO | NO | NO | SI |
| `solo_lectura` | SI | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | ~~SI(bug)~~ **NO** | NO | NO | NO | SI |
| `almacen` | NO | NO | NO | NO | NO | NO | NO | NO |

`almacen` no tiene `quotes.ver`: no accede a cotizaciones, ni antes ni ahora.

### Contraste con lo que ofrece la UI

**La interfaz no condiciona ningun boton por permiso.** Cero directivas `@can` o
`@canany` en `orders/index.blade.php`, `quotes/index.blade.php` y el layout
comercial.

Consecuencia: un `contador` o un `solo_lectura` **sigue viendo** los botones de
crear, editar y eliminar; al pulsarlos recibe un 403 crudo. Funcionalmente es
seguro —el servidor bloquea— pero la experiencia es mala y puede leerse como
error del sistema.

Corregirlo es trabajo de UI y queda **fuera** de esta fase, anotado para el
rediseño de BixoSales.

---

## 5. Preguntas abiertas para decidir

1. **`revendedor` y edicion de pedidos.** No hay evidencia de uso (0 pedidos,
   0 eventos). Si funcionalmente corresponde el modelo B (solo lo propio), no se
   resuelve con `orders.editar` global: requiere el trabajo de §2.
2. **`vendedor` y borrado.** Perdio `orders.eliminar` y `quotes.eliminar`. Ningun
   vendedor real ha generado eventos. ¿Debe un vendedor borrar pedidos, o basta
   con `orders.cancelar`, que ya existe y nadie usa en ninguna ruta?
3. **`almacen`.** Solo tiene `orders.ver`. Para un rol de almacen, actualizar el
   estado de un pedido parece esperable; hoy no puede. La vulnerabilidad se lo
   permitia.

Ninguna se resuelve aqui.
