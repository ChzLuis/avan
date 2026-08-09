# BIXO Design System
## ADN Visual del Producto

---

## FILOSOFÍA CENTRAL

**BIXO no es una aplicación web.**

**BIXO es un Sistema Operativo donde el negocio es el sistema.**

Todo lo que ves, haces y sientes debe comunicar una verdad:

> "Esto es tu negocio. Aquí vives. Aquí crece."

No: "Esto es un software para gestionar tu negocio."

---

## 01. EL NEGOCIO ES EL PROTAGONISTA

### Principio Fundamental

Cuando abres BIXO, no ves:
- Un dashboard
- Un menú
- Un "Bienvenido"
- Módulos a elegir

Ves: **Tu Negocio**

```
BIXO abre y ves:
┌────────────────────────────────────┐
│  Ferretería GABDE                  │
│  🔴 Activo • Última actividad hace 2h
├────────────────────────────────────┤
│  HOY                               │
│  Ventas: $2,450 | Tickets: 12      │
│  Clientes nuevos: 3 | Inventario OK
├────────────────────────────────────┤
│  Rápido acceso:                    │
│  📦 Inventario | 💰 Ventas | 👥 Cli
└────────────────────────────────────┘
```

El negocio está VIVO en la pantalla. No es una categoría de menú.

### Implicaciones de Diseño

1. **El negocio ocupa espacio visual importante**
   - No es un selector pequeño en la esquina
   - Es una entidad reconocible
   - Tiene presencia física en la interfaz

2. **El contexto es siempre evidente**
   - Dónde estás (qué negocio)
   - Cuándo estás (fecha/hora)
   - Qué estás haciendo (módulo activo)

3. **El camino de vuelta es siempre claro**
   - Atrás = vuelvo al negocio (escritorio)
   - No es un menú, es una acción física

4. **Cambiar de negocio es un acto consciente**
   - No es un dropdown discreto
   - Es un cambio de contexto completo
   - La pantalla completa se actualiza

---

## 02. ¿CÓMO SE MUEVE BIXO?

### Principio: Movimiento como Navegación Espacial

BIXO NO se mueve como un formulario web. **Se mueve como un SO.**

#### Tipos de Movimiento

**1. ENTRA (Enter a módulo)**
```
Acción: Click en "Inventario" desde escritorio
Movimiento: Slide horizontal desde derecha (300ms)
Sensación: Entras en una habitación
Easing: cubic-bezier(0.34, 1.56, 0.64, 1)
```

**2. SALE (Back al negocio)**
```
Acción: Click "Atrás" o ESC
Movimiento: Slide horizontal hacia derecha (300ms)
Sensación: Sales de la habitación, vuelves a casa
Easing: cubic-bezier(0.34, 1.56, 0.64, 1)
```

**3. APARECE (Acción contextual)**
```
Acción: Hover en producto, click derecho, etc
Movimiento: Fade suave desde el elemento (150ms)
Sensación: Las opciones emergen del contexto
Easing: ease-in-out
```

**4. CAMBIA (Cambio de negocio)**
```
Acción: Click en selector de negocio
Movimiento: Fade completo (200ms) + repaint
Sensación: Cambias de escritorio, todo es nuevo
Easing: ease-in-out
```

#### Comportamiento Característico

- **Sin retraso**: Las acciones responden instantáneamente
- **Sin micro-animaciones innecesarias**: Cada movimiento significa algo
- **Espacial, no decorativo**: Los movimientos crean sensación de profundidad
- **Predecible**: El usuario sabe dónde va cada elemento

---

## 03. ¿CÓMO RESPIRA BIXO?

### Principio: Espaciado como Estructura, No como Decoración

BIXO respira diferente a otros SaaS porque **respeta el contenido sobre la forma**.

#### Escala de Espaciados (PROPIA, NO 4px)

```
8px    → Micro (entre elementos muy cercanos)
12px   → Pequeño (entre elementos relacionados)
20px   → Estándar (dentro de componentes)
32px   → Separación (entre secciones)
52px   → Aire (entre bloques principales)
84px   → Respiración (espacio genuino)
```

**¿Por qué esta escala?**
- No es múltiplo de 4 (eso es Material Design)
- Tiene progresión más orgánica
- Genera ritmo visual único
- Crea "pausa" natural entre secciones

#### Densidad Visual según Contexto

**Escritorio del Negocio** (GABDE abierto):
- Padding: 20px
- Gap entre widgets: 20px
- Aire genuino (no saturado)

**Dentro de Módulo** (Inventario):
- Padding: 20px (consistente)
- Espacio entre filas: 12px
- Compacto sin ser asfixiante

**Detalle de Item**:
- Padding: 32px (más respiro)
- Separación visual clara
- Menos información visible, más espacio

#### Ritmo Vertical > Horizontal

BIXO no es simétrico:
- Espaciado vertical: 1.5x más que horizontal
- Crea sensación de "caídas" y "subidas" naturales
- El ojo baja naturalmente

---

## 04. ¿CÓMO ORGANIZA LA INFORMACIÓN BIXO?

### Principio: Contexto > Jerarquía

BIXO no fuerza contenido en grillas. **El contenido define su forma según el contexto.**

#### Estructura de Información

```
NIVEL 1: Negocio (Escritorio)
  ├─ Status (Activo/Inactivo)
  ├─ KPI rápida (Hoy)
  └─ Acceso rápido (5 opciones máximo)

NIVEL 2: Módulo (Dentro de Inventario)
  ├─ Contexto (¿Dónde estoy?)
  ├─ Datos principales (Tabla, Kanban, etc)
  └─ Acciones (sobre los datos)

NIVEL 3: Detalle (Un producto específico)
  ├─ Datos completos
  ├─ Historial/contexto
  └─ Acciones disponibles
```

Cada nivel tiene su propio lenguaje. No hereda del anterior.

#### Organización Visual

- **Sin tarjetas flotantes** (Bootstrap-style)
- **Sin pestañas de esquina** (ERP-style)
- **Sin menús anidados** (Web-style)

En lugar de eso:
- **Bloques de contenido** que respetan espacio
- **Contexto integrado** (no separado en tabs)
- **Navegación espacial** (no textual)

#### Sistema de Capas

```
CAPA 1: Negocio (fondo, siempre presente)
CAPA 2: Módulo actual (si está abierto)
CAPA 3: Datos/Contenido (tabla, lista, detalle)
CAPA 4: Acciones contextuales (menú, botones)
CAPA 5: Overlays (modales, notificaciones)
CAPA 6: Sistema (alerts críticos)
```

Cada capa tiene:
- Profundidad visual clara (sombra, z-index)
- Espaciado diferenciado
- Tipografía específica

---

## 05. ¿CÓMO SE SIENTE INTERACTUAR CON BIXO?

### Principio: Intención Visible, Fricción Invisible

Cada interacción debe sentirse natural, como usar un SO.

#### Estados Interactivos

```
REPOSO: 
  - Sin decoración
  - Invisible casi
  - "Aquí hay algo, pero no es urgente"

HOVER:
  - Fondo sutil (+5% más claro)
  - Ningún scale
  - Sombra muy sutil
  - Cursor: pointer si aplica

FOCUS (teclado):
  - Ring: 2px, color primario
  - Offset: 2px
  - Visible pero no invasivo

ACTIVE:
  - Opacidad 90%
  - Sombra más pronunciada
  - Scale: 0.98 (hundimiento mínimo)

LOADING:
  - Shimmer suave (no skeleton screens)
  - Movimiento controlado (no "busy")
  - Usuario sabe que está pasando algo

ERROR:
  - Borde sutil (rojo)
  - Ícono + mensaje directo
  - Sin drama, sin "oh no!"
  - Solución visible

SUCCESS:
  - Checkmark animation (200ms)
  - Fade automático (no persiste)
  - Tranquilo, no celebratorio
```

#### Densidad Interactiva

- **Área mínima clickeable**: 40x40px
- **Espaciado mínimo entre elementos**: 8px
- **Touch-friendly por defecto**: 44x44px
- **Feedback máximo**: 100ms desde acción

---

## 06. TIPOGRAFÍA: JERARQUÍA SIN GRITERÍA

### Escala Propia

```
H1 (Nombre negocio): 28px, weight 700, line-height 1.2
H2 (Módulo):         22px, weight 600, line-height 1.3
H3 (Sección):        16px, weight 600, line-height 1.4
Body (Contenido):    14px, weight 400, line-height 1.5
Caption (Helper):    12px, weight 500, line-height 1.4
Micro (Labels):      11px, weight 600, line-height 1.3
```

### Características

- **Sin variaciones extremas** (no 48px a 10px)
- **Pesos limitados**: 400, 500, 600, 700 solamente
- **Altura de línea generosa**: respeta lecturabilidad
- **Contraste de importancia, no tamaño**

---

## 07. BORDES Y RADIOS: MINIMALISMO INTENCIONAL

```
Componentes pequeños (botones, inputs): 4px
Componentes medianos (cards):           6px
Paneles (módulos):                      8px
Máximo absoluto:                        8px
```

### Bordes

- **Líneas visibles**: 1px solamente
- **Líneas divisoras**: 0.5px muy baja opacidad
- **Sin bordes múltiples**: máximo uno por elemento

---

## 08. SOMBRAS: PROFUNDIDAD REAL

```
SUBTLE:   0 1px 2px rgba(0, 0, 0, 0.04)
LIGHT:    0 2px 4px rgba(0, 0, 0, 0.06)
MEDIUM:   0 4px 8px rgba(0, 0, 0, 0.08)
STRONG:   0 8px 16px rgba(0, 0, 0, 0.10)
DEPTH:    0 12px 24px rgba(0, 0, 0, 0.12)
```

**Regla de oro**: Una sombra por elemento. Nunca aglomerar.

---

## 09. PALETA DE COLORES (A DEFINIR EN NAVEGACIÓN)

- **Primario**: Energía y acción
- **Secundario**: Contexto y soporte
- **Neutrales**: Arquitectura (grises cuidados)
- **Estados**: Error, warning, success, info

**Criterio**: Los colores comunican intención, no decoración.

---

## VALIDACIÓN: ¿SE VE COMO BIXO?

Antes de diseñar cualquier pantalla, preguntarse:

✅ ¿El negocio es el protagonista?
✅ ¿Se siente como un SO, no como un formulario web?
✅ ¿Cada movimiento tiene propósito?
✅ ¿El espaciado respira naturalmente?
✅ ¿Alguien lo reconocería como BIXO sin el logo?

Si respondes "No" a cualquiera: rediseña. No compromises.

---

## PRÓXIMAS FASES

1. **3 Conceptos de Navegación Orbital** → Elegir cuál define BIXO
2. **Paleta de colores** → Basada en la filosofía elegida
3. **Componentes Core** → Botones, inputs, cards con lenguaje BIXO
4. **Pantalla de Validación** → Una pantalla completa como prueba
5. **¿Se ve como BIXO?** → Validación final sin logo
