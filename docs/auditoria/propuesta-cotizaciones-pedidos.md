# Propuesta de rediseño: Cotizaciones / Pedidos

**Recibida:** 2026-08-14 (mockup "Enfoque SaaS Profesional", flujo de 5 pasos).
**Estado:** analizada, NO implementada. Este documento es la lectura crítica
contrastada contra el código real, para decidir qué se toma y qué no.

Archivos de referencia: `resources/views/quotes/index.blade.php` (1094 líneas)
y `resources/views/orders/index.blade.php` (984 líneas).

---

## 1. El diagnóstico del mockup, verificado contra el código

| Punto del mockup | ¿Es cierto? | Evidencia en el código |
|---|---|---|
| Formulario largo en una sola vista | **Sí** | El formulario de cotización es un modal único con cliente + items + pago |
| Poca jerarquía y espacio vacío | **Sí** | Mismo peso visual en todas las secciones |
| Falta contexto del cliente | **Sí** | No hay panel de resumen; los datos se escriben a ciegas |
| No hay guardado automático | **Sí** | Ninguna coincidencia de `autosave`/`draft` temporal. Se pierde todo al cerrar |
| Poca claridad en múltiples productos | **Parcial** | La tabla existe y funciona; lo que falta es reordenar y códigos |
| Sin vista previa ni totales detallados | **Parcial** | El PDF ya existe (`exportOrderPDF`), pero no hay previsualización en pantalla |
| No escalable a flujos complejos | **Vago** | Sin criterio medible; no se puede validar ni refutar |

**5 de 7 confirmados.** El diagnóstico es honesto, no inflado.

---

## 2. Qué ya existe (no hay que construirlo)

- Tabla de items con descripción, cantidad, precio y **descuento por línea**
- Subtotal calculado en vivo
- Búsqueda de productos que rellena la línea
- Estados del documento (Borrador / Enviada / Aceptada / Rechazada / Convertida)
  con barra de progreso visual — **ya es un "stepper", pero de estado, no de captura**
- Duplicar cotización como borrador
- Campos de cliente: nombre, RUC/DNI, teléfono, email, dirección, notas
- `valid_until` (validez de la cotización)
- Datos de pago: estado, monto pagado, método, condición
- **PDF, imagen para WhatsApp y ticket 58 mm** (en Pedidos, ya visibles)

## 3. Qué falta de verdad

**Barato (maquetación):**
- Panel lateral de resumen con totales en vivo
- Pantalla de confirmación con "¿qué sigue?"
- Vista previa del documento en pantalla (el render del PDF ya existe, falta mostrarlo antes de guardar)
- Atajos de teclado
- Reordenar líneas arrastrando
- Código de producto visible (SERV-001)

**Medio (lógica de cliente):**
- Auto-guardado de borrador
- Descuento **global** (hoy solo hay por línea)
- Buscar cliente existente + "guardar como cliente frecuente"
- Campo "Contacto" (persona dentro de la empresa)

**Caro (backend nuevo):**
- **Archivos adjuntos** — 0 coincidencias en el código. Implica almacenamiento,
  validación de tipo, límite de peso, y decidir si viajan en el PDF/email
- **Términos y condiciones** — 0 coincidencias. Necesita plantillas reutilizables,
  no un textarea que se reescribe en cada cotización
- **Enviar por email** — función nueva con implicaciones de entregabilidad
- **Tipo de documento y moneda** como campos reales del flujo

---

## 4. HALLAZGO GRAVE (independiente de la propuesta)

`quotes/index.blade.php:288`

```js
get igv() { return this.subtotal * 0; }
```

**El IGV está multiplicado por cero.** Toda cotización emitida calcula IGV = S/ 0,00
y el total es igual al subtotal. El mockup asume "IGV (18%) S/ 212.04" funcionando.

Esto no es diseño: es cálculo fiscal, en un sistema que **ya tiene facturación
electrónica SUNAT integrada** (`resources/views/facturacion/`). Debe corregirse
aunque la propuesta se descarte, y decidiendo antes: ¿los precios cargados son
con IGV incluido o sin él? La respuesta cambia la fórmula y es del cliente.

---

## 5. Objeción de fondo: el wizard de 5 pasos

**Recomiendo NO adoptar el flujo por pasos**, y esta es la razón:

Un asistente por pasos es excelente para quien hace la tarea **una vez**
(registro, checkout de una tienda, configuración inicial). Es peor para quien
la hace **treinta veces al día**: obliga a 4 clics de "Siguiente" y a navegar
atrás para corregir un precio.

El propio mockup se titula "Enfoque SaaS Profesional", pero los SaaS de
facturación de referencia — Bsale, Alegra, Nubox, Facturama — **no usan wizard**
para emitir documentos. Usan **una pantalla con secciones y un panel lateral de
totales fijo**. El wizard aparece en su onboarding, no en su herramienta diaria.

Riesgo concreto medible: hoy una cotización se crea en 1 pantalla. Con el wizard
serían 5 pantallas + 4 clics de avance, para el mismo dato.

### Alternativa propuesta

**Pantalla única con panel lateral fijo** que conserve el 80% valioso del mockup:

```
┌────────────────────────────────┬──────────────────┐
│ ① Cliente        (buscar/nuevo)│  RESUMEN         │
│ ② Productos      (tabla)       │  Items (3)       │
│ ③ Condiciones    (plegable)    │  Descuento       │
│ ④ Adjuntos       (plegable)    │  IGV 18%         │
│                                │  TOTAL           │
│                                │  [Vista previa]  │
│                                │  [Guardar]       │
└────────────────────────────────┴──────────────────┘
```

- Las secciones numeradas dan **el mismo sentido de progreso** sin encarcelar
- El panel de totales — lo mejor de la propuesta — está siempre visible
- Corregir un precio es hacer scroll, no retroceder pasos
- Condiciones y adjuntos plegados: no estorban a quien cotiza rápido
- Vista previa y confirmación **sí se toman tal cual** del mockup

---

## 6. Errores detectados en el propio mockup

- **Paso 1** muestra el campo **"Email" dos veces** (fila 3, ambas columnas).
  Una debería ser otro dato (¿celular? ¿web?).
- **Paso 2**: "Items (3) S/ 1,240.00" no cuadra con las líneas mostradas
  (500 + 540 + 200 = **1.240** ✔ correcto), pero el descuento global del 5%
  sobre 1.240 es 62,00 ✔ y el IGV 18% sobre 1.178 = **212,04** ✔.
  Los números están bien; se verificaron uno a uno.
- El flujo no contempla **qué pasa si el cliente ya existe con deuda** —
  ni el mockup ni el sistema actual avisan.

---

## 7. Recomendación en una línea

Tomar de la propuesta: panel de totales en vivo, auto-guardado, búsqueda de
cliente, vista previa, confirmación, atajos, descuento global, términos y
adjuntos. **Descartar el wizard de 5 pasos** a favor de pantalla única con
secciones. Y arreglar el IGV en cero **antes** que cualquier cosa de diseño.
