# NAVEGACIÓN ORBITAL DE BIXO
## 3 Conceptos Radicales de Navegación como Sistema Operativo

---

## CONTEXTO

BIXO no puede usar:
- ❌ Sidebar tradicional (heredado de Bootstrap/AdminLTE)
- ❌ Tabs estándar (genérico SaaS)
- ❌ Menús anidados (ERP-style)
- ❌ Navegación textual lineal

BIXO necesita:
- ✅ Navegación que sea **parte del negocio**, no separada
- ✅ Experiencia que se sienta como **entrar en habitaciones** del negocio
- ✅ Contexto siempre claro y visual
- ✅ Movimiento con propósito

---

## CONCEPTO A: "EL NEGOCIO ES EL ESCRITORIO"

### Filosofía

El negocio es tu punto de partida y de retorno.

Navegar es como:
- Abrir una carpeta en tu escritorio
- Trabajar dentro
- Cerrar la carpeta
- Volver al escritorio

### Flujo Visual

```
PANTALLA 1: Escritorio
┌────────────────────────────────┐
│ Ferretería GABDE               │
│ 🟢 Activo • Última actividad 2h
├────────────────────────────────┤
│ HOY                            │
│ Ventas: $2,450 | Tickets: 12   │
│ Inventario: OK | Clientes: 3   │
├────────────────────────────────┤
│ Acceso rápido:                 │
│ [Inventario] [Ventas] [Clientes]
│ [Finanzas]   [Reportes]        │
└────────────────────────────────┘

Click en "Inventario"
         ↓ (SLIDE desde derecha, 300ms)

PANTALLA 2: Módulo
┌────────────────────────────────┐
│ ← GABDE / Inventario           │
├────────────────────────────────┤
│ Productos: 234 | Stock bajo: 12│
├────────────────────────────────┤
│ [Tabla de productos]           │
│ [Acciones inline]              │
└────────────────────────────────┘

Click en "←"
         ↓ (SLIDE hacia derecha, 300ms)

Vuelves a PANTALLA 1: Escritorio
```

### Características

✅ **Navegación binaria**: Escritorio ↔ Módulo (no profundidad)
✅ **Contexto visible**: Siempre sabes dónde estás ("GABDE / Inventario")
✅ **Atrás es natural**: ESC o clic en "←" te lleva al escritorio
✅ **Cambiar negocio es consciente**: Haces clic en "Ferretería GABDE"
✅ **Sin sidebar**: El espacio es 100% para contenido

### Desafíos

- Si tienes muchos módulos, ¿cómo los accedes rápido?
- ¿Qué pasa si quieres ir de Inventario a Ventas sin pasar por escritorio?
- ¿Dónde metes las configuraciones globales?

### Para quién funciona

✅ Dueños de negocios que van de una tarea a otra
✅ Flujos de trabajo simples y lineales
❌ Usuarios que necesitan trabajar en múltiples módulos simultáneamente

---

## CONCEPTO B: "NAVEGACIÓN POR CAPAS CONCÉNTRICAS"

### Filosofía

La navegación es **espacial y radial**, no lineal.

Imagina que tu negocio está en el centro, y cada acción te acerca o aleja del centro.

### Flujo Visual

```
CENTRO: Tu Negocio
┌─────────────────┐
│  Ferretería     │
│     GABDE       │
└─────────────────┘
        ↓
    (Click)
        ↓
ANILLO 1: Módulos (aparece alrededor del negocio)
┌─────────────────────────────────┐
│  ← GABDE                         │
├─────────────────────────────────┤
│   📦 Inventario   💰 Ventas     │
│   👥 Clientes    📊 Reportes   │
│   ⚙️ Config      📈 Finanzas    │
└─────────────────────────────────┘
        ↓
    (Click en Inventario)
        ↓
ANILLO 2: Dentro del Módulo
┌─────────────────────────────────┐
│  ← GABDE / Inventario            │
├─────────────────────────────────┤
│   [Productos] [Categorías]      │
│   [Transferencias] [Reportes]   │
│   [Proveedores] [Movimientos]   │
├─────────────────────────────────┤
│   [Lista de productos]          │
│   [Acciones inline]             │
└─────────────────────────────────┘
        ↓
    (Click en producto)
        ↓
ANILLO 3: Detalle
┌─────────────────────────────────┐
│  ← GABDE / Inventario / Tubo PVC│
├─────────────────────────────────┤
│  [Datos completos]              │
│  [Historial]                    │
│  [Acciones específicas]         │
└─────────────────────────────────┘

Presionas ESC o "←"
        ↓
Vuelves a ANILLO 2, luego ANILLO 1, luego CENTRO
```

### Características

✅ **Navegación visual**: Ves dónde estás (cuántos anillos de profundidad)
✅ **Saltos entre anillos**: De Inventario a Ventas sin pasar por centro (opción)
✅ **Contexto acumulativo**: Ves el path completo ("GABDE / Inventario / Tubo PVC")
✅ **Simetría**: Profundidad visual clara
✅ **Rápido**: No vuelves a centros innecesariamente

### Desafíos

- ¿Cómo se ve en pantalla pequeña?
- ¿Qué pasa con modales y overlays?
- ¿Es intuitivo para usuarios nuevos?

### Para quién funciona

✅ Usuarios que buscan información profunda
✅ Flujos donde necesitas ver relaciones
✅ Interfaces donde el path es importante

---

## CONCEPTO C: "CONTEXTO VIVO"

### Filosofía

La navegación desaparece y reaparece según **lo que estás haciendo**.

No hay un "menú siempre visible". Hay un contexto que cambia.

### Flujo Visual

```
ESTADO 1: Viendo Negocio
┌────────────────────────────────┐
│ Ferretería GABDE               │
│ 🟢 Activo                      │
├────────────────────────────────┤
│ HOY: $2,450 ventas, 12 tickets │
├────────────────────────────────┤
│ [Acceso rápido: 5 botones]     │
└────────────────────────────────┘
Navegación: Selector negocio (arriba), acceso rápido (abajo)

ESTADO 2: Entrando a Inventario
┌────────────────────────────────┐
│ Inventario                     │ ← Título cambia
│ 234 productos | Stock bajo: 12 │
├────────────────────────────────┤
│ [Tabla de productos]           │
│ [Filtros contextuales]         │ ← Navegación cambia
│ [Acciones sobre tabla]         │
└────────────────────────────────┘
Navegación: Filtros específicos de inventario, acciones globales

ESTADO 3: Seleccionas un producto
┌────────────────────────────────┐
│ Tubo PVC 3"                    │ ← Título cambia
│ Stock: 45 | Precio: $5.50      │
├────────────────────────────────┤
│ [Datos del producto]           │
│ [Historial]                    │
│ [Acciones del producto]        │ ← Navegación específica
└────────────────────────────────┘
Navegación: Acciones sobre ESTE producto solamente

ESTADO 4: Cierras detalle
┌────────────────────────────────┐
│ Inventario                     │ ← Vuelves a tabla
│ 234 productos | Stock bajo: 12 │
├────────────────────────────────┤
│ [Tabla de productos]           │
│ [Filtros contextuales]         │
│ [Acciones sobre tabla]         │
└────────────────────────────────┘
Navegación: Vuelve a contexto anterior automáticamente
```

### Características

✅ **Sin menú fijo**: La navegación es invisible cuando no la necesitas
✅ **Contextual**: Cambia con tus acciones
✅ **Limpio**: Máximo espacio para contenido
✅ **Natural**: Como un SO, no como un website
✅ **Eficiente**: No clics innecesarios para navegar

### Desafíos

- ¿Cómo descubre el usuario qué opciones hay?
- ¿Necesita breadcrumb/path visible?
- ¿Qué pasa si quiere ir de Inventario a Ventas?

### Para quién funciona

✅ Usuarios expertos que saben qué buscan
✅ Flujos donde el espacio es crítico
✅ Interfaces donde contexto = navegación

---

## COMPARATIVA: ¿CUÁL ES BIXO?

| Aspecto | Concepto A | Concepto B | Concepto C |
|---------|-----------|-----------|-----------|
| **Sensación** | Casa con habitaciones | Mapeo concéntrico | Fluidez contextual |
| **Profundidad** | Binaria (in/out) | Anillos (1,2,3...) | Dinámica |
| **Navegación** | Escritorio ← → Módulo | Path visible | Contextual |
| **Espacio** | 100% contenido | 80% contenido | 100% contenido |
| **Curva aprendizaje** | Muy fácil | Media | Media-Alta |
| **Para negocios simples** | ✅ Excelente | ⚠️ Overkill | ⚠️ Arriesgado |
| **Para negocios complejos** | ❌ Limitado | ✅ Excelente | ✅ Bueno |
| **Se siente como SO** | Parcialmente | Sí | Sí |
| **Reconocible sin logo** | Sí | Sí | Sí |

---

## RECOMENDACIÓN

**Concepto A + Concepto B híbrido**:

- Centro: Negocio (escritorio)
- Primer nivel: Módulos (anillo 1, acceso directo)
- Dentro: Submódulos/datos (anillo 2)
- Detalle: Producto/cliente (anillo 3)

Combina la **claridad de A** con la **eficiencia de B**.

---

## PRÓXIMO PASO

¿Cuál de estos 3 conceptos (o híbrido) debería definir la navegación de BIXO?

Una vez elegimos, diseñamos:
1. Prototipo visual del concepto
2. Transiciones exactas
3. Componentes de navegación con lenguaje BIXO
