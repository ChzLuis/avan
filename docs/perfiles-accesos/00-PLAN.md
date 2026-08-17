# PLAN DE TRABAJO — PERFILES Y ACCESOS BIXO

> Documento fuente. Redactado por el propietario del producto el 2026-08-16.
> No modificar el contenido de las fases; el avance se registra en `03-AVANCE.md`.

## 1. Objetivo

Reestructurar completamente el sistema actual de roles y permisos de BIXO para convertirlo en un modelo:

* comprensible para un dueño de negocio;
* seguro en backend;
* aislado por proyecto/negocio;
* auditable;
* escalable;
* consistente entre todos los módulos;
* compatible con los accesos existentes durante la migración.

El resultado final debe abandonar la lógica visible de decenas de permisos técnicos y trabajar con:

**Perfil → Área → Nivel de acceso**

Los niveles serán siempre:

1. **Sin acceso**
2. **Ver**
3. **Trabajar**
4. **Administrar**

No implementar excepciones conceptuales por módulo.

---

## 2. Estado actual que debes considerar

La auditoría del 16 de agosto de 2026 encontró:

* 90 permisos definidos.
* 23 permisos que actualmente no restringen nada.
* 13 roles.
* Solo 2 roles con usuarios asignados.
* 4 roles construidos sobre permisos legacy en inglés.
* 110 de 219 acciones de modificación sin comprobación de permisos.
* Configuración concentra 57 acciones abiertas.
* Bots de WhatsApp concentra 14 acciones abiertas.
* Los roles actualmente son globales y no pertenecen a un proyecto.
* La descripción de los roles se valida pero no se persiste.
* Ya se corrigió la posibilidad de que un miembro modificara su propio rol para escalar privilegios.

No asumir que la nomenclatura de los permisos refleja su funcionamiento real.

Validar siempre contra: rutas; middleware; Policies; Gates; controladores; componentes Livewire si existen; Blade; llamadas AJAX/API; acciones POST/PUT/PATCH/DELETE; base de datos real.

---

## 3. Principios obligatorios de arquitectura

### 3.1 Denegar por defecto

Toda acción que modifique información debe tener autorización explícita en backend.

> Si una acción no tiene una regla de autorización definida, debe considerarse no autorizada.

No utilizar únicamente botones ocultos como mecanismo de seguridad.

### 3.2 Separar autorización de aislamiento

Para ejecutar una acción deben cumplirse dos condiciones independientes:

**A. Permiso** — ¿El usuario puede realizar esta acción?

**B. Proyecto** — ¿El recurso sobre el cual quiere actuar pertenece al proyecto activo del usuario?

Tener permiso no debe permitir nunca operar sobre recursos de otro negocio. Crear pruebas específicas de aislamiento entre proyectos.

### 3.3 No dar permisos individuales como solución habitual

La arquitectura debe ser **Plantilla BIXO → Perfil del proyecto → Usuario**, no
**Plantilla → Usuario + decenas de excepciones individuales**.

### 3.4 Separar BIXO de los negocios

No confundir el **Administrador de plataforma BIXO** (cuenta interna de Eskala/BIXO) con el **Dueño** (administrador total únicamente de su negocio/proyecto).

El Dueño de Mega Hogar no obtiene capacidades sobre Tecsist, Baby Toncito ni ningún otro proyecto.

---

## 4. Modelo objetivo — plantillas

BIXO tendrá seis plantillas estándar.

**Dueño.** Control total del negocio. Todas las áreas en Administrar. Puede administrar perfiles y accesos del negocio. Debe existir siempre al menos un Dueño activo por proyecto.

**Encargado.** Gestiona la operación diaria. Acceso elevado a catálogo, pedidos, clientes, caja y operación. No administra seguridad ni configuraciones críticas por defecto.

**Vendedor.** Opera ventas y atención: punto de venta, pedidos, cotizaciones, clientes y comprobantes asociados a la venta. No modifica configuraciones críticas ni estructura general del negocio.

**Almacén.** Administra mercadería: catálogo e inventario. No debe visualizar ni administrar información financiera salvo personalización posterior del perfil del proyecto.

**Contador.** Gestiona operación financiera: caja, facturación y cobros. No modifica catálogo ni diseño de la tienda por defecto.

**Solo lectura.** Acceso de consulta. Puede visualizar áreas autorizadas pero ninguna petición de modificación debe ser aceptada.

---

## 5. Áreas y niveles

Implementar 12 áreas. Cada una debe soportar exactamente **Sin acceso / Ver / Trabajar / Administrar**.

| # | Área | Ver | Trabajar | Administrar |
|---|------|-----|----------|-------------|
| 5.1 | Catálogo | consultar productos, servicios, categorías; exportar si corresponde | crear, editar, precios, imágenes, información comercial | eliminar, importación masiva, operaciones destructivas, configuraciones estructurales |
| 5.2 | Inventario | consultar stock, Kardex, movimientos | entradas, salidas, transferencias ordinarias | ajustes, regularizaciones, importación, correcciones críticas |
| 5.3 | Pedidos | consultar | crear y procesar | anular, eliminar, operaciones críticas |
| 5.4 | Cotizaciones | consultar | crear, editar, enviar, convertir | eliminar, operaciones administrativas |
| 5.5 | Clientes | consultar | crear y editar | eliminar, fusionar, importar, operaciones críticas |
| 5.6 | Punto de venta | consultar operaciones | vender y cobrar | descuentos especiales, anulaciones, configuración relacionada |
| 5.7 | Caja | consultar | abrir, cerrar, registrar movimientos | correcciones, anulaciones, administración |
| 5.8 | Facturación | consultar | emitir | anular, operaciones críticas |
| 5.9 | Cobros | consultar | registrar y gestionar | aprobar, rechazar, revertir |
| 5.10 | Agenda | consultar | crear, editar, reprogramar | configurar, operaciones administrativas |
| 5.11 | Personal | consultar | gestionar empleados, asistencia y turnos según alcance | eliminar, administrar configuración relacionada |
| 5.12 | Configuración | consultar configuración | modificar configuración operativa de bajo o medio impacto | dominios, medios de pago, bots, integraciones, estructura del negocio, configuraciones sensibles |

Determinar durante la auditoría exactamente qué operación pertenece a cada nivel.

---

## 6. Regla de herencia

Los niveles son acumulativos: **Administrar incluye Trabajar y Ver. Trabajar incluye Ver.**

No deben existir combinaciones como editar sin ver, administrar sin trabajar, o trabajar sin acceso de lectura.

Internamente puede resolverse mediante permisos independientes o lógica jerárquica, pero la experiencia de usuario y las reglas deben respetar esta jerarquía.

---

## 7. Fases de implementación

No saltar fases. No empezar por la interfaz.

### FASE 0 — Congelar el estado actual

Crear una fotografía reproducible del sistema antes de modificar permisos.

Revisar: `permissions`, `roles`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`, empleados/usuarios, proyectos, membresías, rutas, middleware, Policies, Gates, controladores, acciones mutables, vistas que muestran/ocultan opciones por permiso.

Crear una matriz con, por cada acción: módulo; ruta; método HTTP; controlador; operación; permiso actual; rol que lo posee; proyecto; si modifica información; si está actualmente abierta.

**Entregable:** archivo de auditoría **ANTES DE LA MIGRACIÓN** que permita comparar comportamiento antes/después.

**No modificar todavía ninguna autorización existente durante esta fase.**

### FASE 1 — Corregir descripción de roles

Agregar la columna necesaria para almacenar la descripción. Verificar creación, edición, validación, persistencia, lectura y visualización. No cambiar permisos.

**Criterio de cierre:** crear un rol con descripción, recargar la pantalla y verificar que la descripción persiste.

### FASE 2 — Clasificar los 90 permisos

No eliminar todavía. Cada permiso debe quedar clasificado como: ACTIVO; LEGACY; HUÉRFANO; PENDIENTE DE CONECTAR; SUSTITUIR; ELIMINAR AL FINAL.

Matriz: `| Permiso actual | Dónde se usa | Rol | Sustitución futura | Estado |`

Investigar especialmente: permisos en inglés; `settings.*`; `inventory.*`; `attendance.*`; `reports.*`.

**Importante:** no eliminar `settings.negocio`, `settings.diseno`, `settings.pagos` u otros permisos que puedan servir temporalmente para cerrar acciones actualmente abiertas.

### FASE 3 — Cerrar las acciones sin autorización

Prioridad de seguridad. ~110 acciones mutables sin protección. Trabajar módulo por módulo, en este orden:

1. Configuración. 2. Bots de WhatsApp. 3. Empresa/sedes/grupos. 4. Listas maestras. 5. Combos/promociones. 6. Cupones. 7. Certificados. 8. Propuestas. 9. Resto.

No usar temporalmente un permiso que deje fuera a los usuarios actualmente autorizados sin analizar el impacto. Antes de cada cambio, tabla: `| Acción | Quién puede hoy | Quién debería poder | Permiso provisional |`

**Criterio obligatorio:** 0 acciones POST/PUT/PATCH/DELETE sensibles sin autorización explícita.

### FASE 4 — Crear el modelo canónico

Crear los 36 permisos correspondientes a 12 áreas × 3 niveles. Nomenclatura interna consistente (`catalogo.ver`, `catalogo.trabajar`, `catalogo.administrar` o equivalente). Elegir una sola convención; no mezclar español/inglés ni formatos.

Crear una fuente de verdad central que defina área, nivel, nombre, descripción y jerarquía. Evitar repetir manualmente la definición de permisos por todo el código.

### FASE 5 — Crear el mapeo legacy → nuevo

No migrar a ciegas. Tabla completa: `| Permiso antiguo | Permiso nuevo | Justificación |`

Cada permiso antiguo debe tener destino o justificación explícita de eliminación. No debe quedar ninguno sin decisión documentada.

### FASE 6 — Migrar los roles existentes

Transformar los permisos actuales usando el mapping. Objetivo: mantener capacidades equivalentes durante la migración. No dar más privilegios de los necesarios ni quitar silenciosamente accesos válidos.

Revisar especialmente `gerente`, `qa_lectura`, `owner`, `comercial`, `operaciones`, `logistica`. Los roles vacíos pueden reestructurarse con menor riesgo, pero igualmente deben documentarse.

**Comparación obligatoria:** matriz ANTES vs DESPUÉS por rol y módulo.

### FASE 7 — Aislamiento por proyecto

Los perfiles no pueden continuar siendo globales. Evaluar el sistema de Teams de Spatie Permission o la estrategia técnicamente más adecuada según la versión instalada. **No asumir**: revisar versión real y documentación antes de implementar.

Cada perfil debe pertenecer a un proyecto. Modificar el Vendedor de Mega Hogar no debe cambiar el Vendedor de Tecsist.

### FASE 8 — Pruebas de aislamiento

* Administrador de Mega Hogar intenta editar producto de Tecsist → 403.
* Usuario con Caja Administrar de Mega Hogar intenta operar caja de Baby Toncito → denegado.
* Dueño de un proyecto intenta consultar administración de otro manipulando URL/ID → denegado.

Probar tanto acceso visual como petición directa al backend.

### FASE 9 — Crear las seis plantillas BIXO

Dueño, Encargado, Vendedor, Almacén, Contador, Solo lectura. Sirven como punto inicial: al crear/configurar un proyecto, BIXO genera perfiles propios para ese negocio basados en estas plantillas. Las modificaciones posteriores pertenecen al proyecto. No modificar automáticamente los perfiles existentes cuando BIXO cambie una plantilla futura.

### FASE 10 — Personalización de perfiles

Permitir modificar un perfil del negocio área por área. **Encargado** puede convertirse en **Encargado · Personalizado**. Mostrar visualmente que el perfil se desvió de la plantilla. Evitar como comportamiento principal asignar permisos individuales a empleados.

### FASE 11 — Proteger el perfil Dueño

Debe existir al menos un Dueño activo por proyecto; impedir eliminar/desactivar al último Dueño; impedir que el último Dueño se quite a sí mismo dicho acceso; los cambios de Dueño deben quedar auditados. No confundir esta protección con Super Admin global.

### FASE 12 — Auditoría de seguridad

Registrar como mínimo: proyecto; usuario que realizó el cambio; usuario afectado; perfil; nivel anterior; nivel nuevo; fecha/hora; IP si ya se registra; origen de la operación.

> Luis cambió Caja de Ver a Administrar en el perfil Encargado.
> María asignó el perfil Vendedor a Pedro.
> Carlos retiró acceso a Configuración del perfil Encargado.

La auditoría no debe depender únicamente de logs de servidor: debe ser información estructurada consultable.

### FASE 13 — Retirar el sistema legacy

Solo después de comprobar que ninguna ruta, Policy, Gate, Blade, controlador, rol ni proceso en background usa permisos antiguos. Entonces retirar permisos legacy, huérfanos, nomenclatura inglesa vieja y código temporal de compatibilidad.

**Resultado:** solo queda una generación canónica de permisos.

### FASE 14 — Rediseñar la interfaz

No mostrar una pantalla técnica llamada **Roles**. Renombrar funcionalmente a **Perfiles y accesos**, con subtítulo: *Define qué puede ver y hacer cada persona dentro de tu negocio.*

Vista principal con tarjetas: Dueño (control total del negocio); Encargado (gestiona la operación diaria); Vendedor (vende, cotiza y atiende clientes); Almacén (gestiona productos e inventario); Contador (gestiona caja, cobros y facturación); Solo lectura (consulta información sin realizar modificaciones).

### FASE 15 — Vista de edición de perfil

No utilizar 90 checkboxes. Tabla/listado por área, con un único selector por área: Sin acceso / Ver / Trabajar / Administrar.

### FASE 16 — Ayuda contextual

**Ver:** puede consultar información pero no modificarla.
**Trabajar:** puede realizar las tareas habituales del área, como crear y editar.
**Administrar:** puede realizar también operaciones sensibles como eliminar, anular, configurar o ejecutar acciones masivas.

Cuando sea útil, explicar acciones específicas: *Aplicar descuentos especiales requiere Punto de venta → Administrar.*

### FASE 17 — Responsive y UX

Debe funcionar en escritorio, laptop, tablet y móvil. No diseñarla únicamente como tabla desktop: en móvil usar tarjetas, acordeones, selectores grandes y áreas claramente separadas, con tamaños táctiles adecuados. No recargar la pantalla de información técnica.

### FASE 18 — Pruebas automatizadas

Niveles (sin acceso no entra; ver no modifica; trabajar crea/edita; administrar ejecuta acciones críticas); jerarquía (administrar incluye trabajar, trabajar incluye ver); proyectos (usuario de A no actúa sobre B); Dueño (el último no puede eliminarse); solo lectura (ninguna acción mutable); perfiles (modificación en Mega Hogar no afecta Tecsist).

No limitar los tests a Blade: probar endpoints directamente.

### FASE 19 — Auditoría final de rutas

Reinspeccionar todas las rutas y clasificar por método. Cada operación mutable debe tener: autenticación; pertenencia al proyecto; autorización; validación. Generar reporte final de cobertura.

---

## 8. Criterios de aceptación finales

El trabajo NO está terminado hasta conseguir:

* 36 permisos canónicos · 12 áreas · 3 niveles efectivos por área.
* 0 permisos decorativos · 0 permisos legacy activos.
* 0 acciones mutables sensibles sin autorización.
* 100 % de perfiles aislados por proyecto; 0 modificación de un proyecto afectando a otro.
* 6 plantillas BIXO.
* Auditoría de cambios de acceso.
* Último Dueño protegido.
* Backend protegido aunque se manipule la interfaz.
* Pruebas automatizadas de acceso y pruebas cruzadas multi-proyecto.
* Pantalla de Perfiles y accesos responsive.
* Ningún checkbox técnico de permisos legacy visible al cliente.

---

## 9. Reglas de ejecución

1. No realizar una reescritura masiva sin analizar primero el código real.
2. No asumir que un permiso está activo únicamente porque existe en la base de datos.
3. No eliminar permisos antes de comprobar todas sus referencias.
4. No utilizar ocultamiento visual como seguridad.
5. No introducir accesos globales para solucionar rápidamente problemas multi-proyecto.
6. No asignar permisos directos a usuarios como solución estándar.
7. No modificar datos comerciales del negocio durante las pruebas.
8. No tocar producción de forma irreversible sin una migración validada.
9. Las migraciones deben ser reversibles cuando técnicamente sea razonable.
10. Antes de cada fase de alto riesgo, ejecutar tests.
11. Después de cada fase de alto riesgo, ejecutar nuevamente tests.
12. No pasar a la siguiente fase si la actual deja errores de autorización.
13. No acumular cambios gigantes en un único commit.
14. Crear commits por fase lógica.
15. Mantener compatibilidad temporal cuando sea necesaria para evitar cortes.

---

## 10. Forma de reportar avance

Sin informes largos de teoría. Después de cada fase: **Fase terminada** (qué se hizo) · **Archivos modificados** (listado concreto) · **Base de datos** (migraciones o datos afectados) · **Pruebas** (qué se ejecutó y resultado) · **Antes / Después** (qué comportamiento cambió) · **Pendiente** (qué sigue abierto) · **Riesgos encontrados** (solo riesgos reales).

No marcar una fase como terminada si existen errores pendientes relacionados con ella.

---

## 11. Primera orden

Empezar únicamente por la **FASE 0 — CONGELAR Y AUDITAR EL ESTADO ACTUAL**. No modificar todavía la arquitectura de permisos.

Entregar: mapa completo de permisos; mapa completo de roles; usuarios asignados; rutas protegidas; rutas no protegidas; permisos realmente utilizados; permisos muertos; alcance actual por proyecto; acciones de escritura sin autorización; tabla de migración preliminar.

Después presentar el resultado y definir con precisión qué archivos y migraciones serán necesarios para las FASES 1 a 3.

**No empezar el rediseño visual hasta que la seguridad, el modelo canónico y el aislamiento por proyecto estén resueltos.**
