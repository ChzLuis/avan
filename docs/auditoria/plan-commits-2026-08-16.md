# Plan de commits — 2026-08-16

**332 cambios sin confirmar**; el ultimo commit es `9b2bcc5`.
De ellos **148 son de hoy** y **184 venian de antes**.

Lo anterior NO se tematiza: no lo escribi yo y agruparlo seria inventar una
historia que no ocurrio. Va como punto de partida. Lo de hoy si se separa,
porque se exactamente que se toco y por que.

> **Nada de esto se ha ejecutado.** Es una propuesta para que la revises.

Cada bloque tiene su lista de archivos en `docs/auditoria/commits/`, asi que el
comando es siempre el mismo y no hay rutas que se rompan al copiar.

---

## Orden recomendado

Primero los bloques de hoy (2 a 8) y **al final** el punto de partida: si algo
sale mal, lo que se revisa es lo reciente y no queda mezclado con 182 archivos
heredados.

---

## 2. `01-recuperacion-deriva` — recupera de ARIN codigo que solo vivia en produccion

26 archivos ([lista](commits/01-recuperacion-deriva.txt)).

```bash
git add $(cat docs/auditoria/commits/01-recuperacion-deriva.txt)
git commit -m "recupera de ARIN codigo que solo vivia en produccion"
```

## 3. `02-cuentas-por-cobrar` — libro de cobros, vencimiento real y condiciones por negocio (F2/F2b)

9 archivos ([lista](commits/02-cuentas-por-cobrar.txt)).

```bash
git add $(cat docs/auditoria/commits/02-cuentas-por-cobrar.txt)
git commit -m "libro de cobros, vencimiento real y condiciones por negocio (F2/F2b)"
```

## 4. `03-pagos` — los cobros de panel, bot, extension y POS entran al libro (F3)

8 archivos ([lista](commits/03-pagos.txt)).

```bash
git add $(cat docs/auditoria/commits/03-pagos.txt)
git commit -m "los cobros de panel, bot, extension y POS entran al libro (F3)"
```

## 5. `04-facturacion` — descuento por linea en el comprobante y dinero fiscal exacto (F4a)

5 archivos ([lista](commits/04-facturacion.txt)).

```bash
git add $(cat docs/auditoria/commits/04-facturacion.txt)
git commit -m "descuento por linea en el comprobante y dinero fiscal exacto (F4a)"
```

## 6. `05-seguridad` — cierra el precio manipulable del checkout, permisos abiertos y 500 en produccion

17 archivos ([lista](commits/05-seguridad.txt)).

```bash
git add $(cat docs/auditoria/commits/05-seguridad.txt)
git commit -m "cierra el precio manipulable del checkout, permisos abiertos y 500 en produccion"
```

## 7. `06-herramienta-despliegue` — deploy.py atomico, con puerta de deriva y chequeo de salud real

2 archivos ([lista](commits/06-herramienta-despliegue.txt)).

```bash
git add $(cat docs/auditoria/commits/06-herramienta-despliegue.txt)
git commit -m "deploy.py atomico, con puerta de deriva y chequeo de salud real"
```

## 8. `07-documentacion` — expedientes de auditoria, roadmap y plan de commits

8 archivos ([lista](commits/07-documentacion.txt)).

```bash
git add $(cat docs/auditoria/commits/07-documentacion.txt)
git commit -m "expedientes de auditoria, roadmap y plan de commits"
```

## 9. Resto de hoy, sin clasificar

73 archivos de hoy que no encajan en los bloques anteriores
([lista](commits/08-resto-de-hoy.txt)). **Revisalos antes**: si alguno no deberia
entrar, sacalo de la lista.

```bash
git add $(cat docs/auditoria/commits/08-resto-de-hoy.txt)
git commit -m "trabajo variado del 2026-08-16"
```

## 10. Punto de partida

Los 184 archivos que ya estaban modificados al empezar el dia.

```bash
git add -A
git commit -m "chore: punto de partida de la rama antes de la auditoria del 2026-08-16"
```

---

## Antes de confirmar

- La suite esta en **15 problemas de 522** (empezo el dia en 36 de 510).
- Todo lo desplegado hoy esta verificado en ARIN, con el mismo SHA-256 en local.
- `_local_backup_20260802_122259/` esta **versionado** y duplica archivos reales
  (`PublicController.php`, `routes/web.php`, `Product.php`…). Decide si sale del
  repo antes del punto de partida: hoy hay dos verdades para los mismos archivos.
- La rama es `refactor/store-builder-canonical-context` y **su refactorizacion
  sigue a medias**: el lado publico no usa el contexto canonico. Con estos
  commits hechos, terminarla deja de ser arriesgado.
