# Propuesta: Panel Comercial BIXO — funcionalidades, mejoras y diferenciadores

Fecha: 2026-08-05. Base: auditoría completa del código real.

## 1. Lo que YA existe (inventario honesto)

| Área | Estado real |
|---|---|
| Pedidos | Centro de Pedidos nuevo (KPIs, pestañas, pago separado, WhatsApp 5 plantillas, auditoría, export) ✅ hoy |
| Cotizaciones | Tabla quotes con vencimiento/token/convertir a pedido ✅ hoy |
| CRM | Client tiene: `etapa`, `lead_temp`, `lead_score`, `lead_source`, `monto_estimado`, `proximo_seguimiento`, `responsable`, `producto_interes`, `objeciones` — ¡un CRM completo ya modelado! |
| Pagos | payment_status separado, comprobante adjunto, aprobación Yape/Plin del bot |
| Facturación | Boleta/Factura SUNAT (APIsPERU) operativa |
| Bot WhatsApp | Baileys + flujos + ventas reales (rifas/eccomerce) |
| BIXO Score | Ya existe en el portal (87/100) con semáforo por área |
| Tiendas | Constructor + checkout + Yape + comprobante del cliente |

**Conclusión clave: el pipeline/embudo del mockup NO hay que inventarlo — los campos ya están en `clients`. Solo falta la vista y los procesos que los alimenten.**

## 2. Panel Comercial propuesto (el mockup, con datos reales)

Estructura de la vista Resumen (portal /bixosales, categoría comercio):

1. **Fila KPI con sparklines**: Ventas del mes (▲% vs anterior) · Meta comercial % con barra (setting `sales_goal_month`, editable ahí mismo) · Ticket promedio (▲%) · Clientes nuevos (▲%).
2. **Rendimiento comercial**: gráfico acumulado del mes vs línea de meta (SVG propio), selector Ventas/Pedidos, caja lateral "Ventas acumuladas / Meta / %".
3. **Embudo de conversión**: Leads → Contactados → Cotizados → Ganados con % de conversión (fuente: `clients.etapa` + quotes + orders).
4. **Pipeline de oportunidades**: barra por etapa con **cantidad y S/** (usa `monto_estimado` de clients + total de quotes).
5. **Oportunidades prioritarias** (tabla accionable): cliente, etapa, S/, próxima acción y fecha límite — ordenada por urgencia real (cotización por vencer > pago atrasado > seguimiento vencido `proximo_seguimiento` > mayor valor). Cada fila abre el registro.
6. **Ranking comercial**: por `responsable`/`created_by` con barra y % de aporte a la meta.
7. **Alertas e insights**: accionables, cada una con enlace (cotiz vencen hoy, S/ por cobrar, pedidos +48h, stock crítico, ritmo bajo meta, clientes sin seguimiento).

## 3. Funcionalidades NUEVAS de valor (priorizadas)

| # | Funcionalidad | Valor para el negocio | Esfuerzo |
|---|---|---|---|
| 1 | **Cobranza por WhatsApp en 1 clic** desde alertas: "S/ 850 por cobrar" → lista → botón que abre wa.me con solicitud de pago + datos Yape del proyecto | Convierte el panel en caja: el dueño cobra en segundos | Bajo (base ya hecha hoy) |
| 2 | **Seguimientos del día**: lista de `proximo_seguimiento` vencidos/hoy con botón WhatsApp y "reprogramar" | Nadie se olvida de un lead; el CRM se usa de verdad | Bajo |
| 3 | **Meta por vendedor** (setting `sales_goal_{user}`) + ranking con % cumplimiento real | Gestión de equipo, como el mockup | Bajo |
| 4 | **Insights automáticos** ("Los martes vendes 30% más", "El 60% de cotizaciones vencen sin respuesta", "Tu ticket bajó S/12") calculados de los datos | Sensación de "asistente que piensa" — diferencial | Medio |
| 5 | **Bot → CRM automático**: cada conversación del bot crea/actualiza el lead (etapa, temperatura, producto_interes) | El embudo se llena SOLO; nadie digita | Medio (bot ya escribe clients) |
| 6 | **Enlace de cobro** (página pública /pagar/{token} con Yape QR + comprobante) reutilizando el checkout | Cobranza remota sin tienda | Medio |
| 7 | **Resumen diario por WhatsApp al dueño** (ventas, cobros, alertas del día, 8pm) | El panel "va" al dueño; retención brutal | Medio (usa Baileys) |

## 4. Mejoras de vistas (rápidas)

- Alertas del portal: arreglar rama SLA que deja alertas vacías en comercios (bug detectado) y enriquecer con cobros/cotizaciones/seguimientos.
- KPI strip del portal: + Por cobrar (S/) y Meta con barra.
- "Activos ahora": mostrar estado de pago y botón WhatsApp directo en cada tarjeta.
- Búsqueda global del topbar: que busque también pedidos y cotizaciones (hoy solo clientes/mesas).
- Periodo seleccionable (Hoy/Semana/Mes) en el panel.

## 5. El diferenciador (por qué BIXO ≠ los demás)

Los ERP/CRM del mercado (Odoo, Bsale, Wally, etc.) **muestran** datos; BIXO **actúa por WhatsApp**, que es donde vende la microempresa peruana:

> **"El único sistema donde cada número tiene un botón: lo pendiente se cobra, se persigue y se avisa por WhatsApp en un clic — y el bot llena el CRM solo."**

Ese es el hilo conductor: panel → alerta → acción WhatsApp → auditoría. Ya tenemos el 70% construido (bot, plantillas, pagos, score); la propuesta 1-2-3 cierra el círculo con esfuerzo bajo.

## 6. Orden sugerido de construcción

1. Panel Resumen nuevo (sección 2) + fixes de vistas (sección 4) — 1 tanda.
2. Cobranza 1-clic + Seguimientos del día (funcs 1-2) — 1 tanda.
3. Meta por vendedor + Insights (3-4).
4. Bot→CRM, Enlace de cobro, Resumen diario (5-7) — por separado, tocan bot/rutas públicas.
