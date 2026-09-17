# Acta de entrega · Tienda virtual Corporación MegaHogar

| | |
|---|---|
| **Cliente** | CORPORACIÓN MEGA HOGAR S.A.C. · RUC 20616190220 |
| **Dirección** | Jr. Odonovan 174, Cercado, Huancavelica |
| **Proveedor** | Eskala Group · producto BIXO® |
| **Fecha de entrega** | 5 de septiembre de 2026 |
| **Proyecto en plataforma** | Corporación MegaHogar (alta 22 de julio de 2026) |

Todo lo que sigue se comprobó en producción el día de la entrega. Lo que no
se pudo comprobar se indica como tal.

---

## 1. Qué se entrega

Una tienda virtual en línea, con panel de administración propio, sobre la
plataforma BIXO® alojada por Eskala. Comprende:

- **Tienda pública** con dominio propio y certificado de seguridad.
- **Catálogo** completo, con categorías, fotos, buscador con sugerencias y
  filtros.
- **Cotización por WhatsApp** desde cada producto y desde el carrito
  (modalidad "precio a solicitud").
- **Pedidos y pago manual** por Yape y transferencia BCP, con QR de Yape en
  el checkout.
- **Panel de administración** para gestionar productos, categorías, pedidos,
  cotizaciones, clientes, páginas y apariencia de la tienda.
- **Conector de WhatsApp para bot** instalado en el servidor (ver estado en
  el punto 4: requiere dos acciones antes de operar).

## 2. Direcciones de acceso

| Qué | Dirección | Estado verificado |
|---|---|---|
| Tienda (dominio propio) | [megahogar.org](https://megahogar.org) | Responde. Certificado HTTPS válido. |
| Tienda (respaldo en plataforma) | [arindg.com/corporacion-megahogar-ccf4](https://arindg.com/corporacion-megahogar-ccf4) | Responde. |
| Catálogo | [megahogar.org/tienda](https://megahogar.org/tienda) | Responde. |
| Nosotros | [megahogar.org/nosotros](https://megahogar.org/nosotros) | Responde. |
| Contacto | [megahogar.org/contacto](https://megahogar.org/contacto) | Responde. |
| Panel de administración | [arindg.com/bixoadmin](https://arindg.com/bixoadmin) | Responde. |

> **Atención:** `www.megahogar.org` hoy muestra la página de "dominio
> aparcado" de Hostinger, no la tienda. Ver pendiente P1.

## 3. Estado de la tienda el día de la entrega

| Indicador | Valor |
|---|---|
| Productos cargados | 1 282 |
| Productos publicados (visibles) | 1 270 |
| Categorías activas | 42 |
| Clientes registrados | 72 |
| Pedidos recibidos | 1 |
| Cotizaciones recibidas | 1 |
| Borradores sin publicar | ninguno |

**Configuración comercial vigente**

- Modalidad de venta: cotización. Los precios no se muestran; cada producto
  ofrece "Consultar" por WhatsApp.
- WhatsApp de cotizaciones y teléfono de contacto: 951 731 110.
- Horario publicado: lunes a sábado de 9:00 a 19:00; domingos de 9:00 a 13:00.
- Pago manual habilitado: Yape 932 780 200 (Corporación Mega Hogar), con QR
  cargado; BCP Cta. Cte. Soles 3508015381096; CCI 00235000801538109677.
- Envíos: habilitados.
- Diseño: plantilla Computienda, cabecera con mega menú, pie corporativo,
  color principal #123173.

**Módulos habilitados en el panel:** agenda, asistencia, bots, catálogo,
clientes, documentos, grupos, RR. HH., inventario, comprobantes, logística,
fidelización, pedidos, páginas, planilla, proveedores, cotizaciones, sedes y
tienda.

En esta entrega se verificó el funcionamiento de: tienda, catálogo, pedidos,
cotizaciones, clientes, páginas y el conector del bot. Los demás módulos
están habilitados pero no formaron parte de la verificación de esta acta.

## 4. Bot de WhatsApp

Estado comprobado el día de la entrega:

| Componente | Estado |
|---|---|
| Conector instalado en el servidor | Sí, en ejecución. |
| Código QR de emparejamiento | Disponible en el panel (Bots → estado de WhatsApp). |
| Sesión de WhatsApp emparejada | **No.** Falta escanear el QR con el teléfono del negocio. |
| Flujo de conversación configurado | **No.** El proyecto no tiene ningún flujo creado. |

**Consecuencia:** hasta que no se completen las dos acciones del pendiente
P0, el bot no atenderá mensajes. Se comprobó el comportamiento exacto: sin
flujo activo, el motor recibe el mensaje y **calla**, sin aviso al cliente
que escribe. Es preferible no emparejar el número hasta tener el flujo
listo, para que nadie escriba a un WhatsApp que no contesta. Se recomienda crearlo desde la plantilla **Bot Comercial
Informativo** del panel, revisar sus textos con el cliente y recién entonces
emparejar el número.

Observación técnica: el conector registró 8 reinicios desde el 4 de
septiembre a las 02:58. Es lo esperable mientras espera emparejamiento; tras
emparejar conviene vigilar 48 horas que se mantenga estable.

## 5. Accesos entregados

| Persona | Usuario | Rol | Alcance |
|---|---|---|---|
| William Soto Durán | `william.soto` | Gerente | Todo el panel de la tienda |
| Fany N. Rojas | `fany.rojas` | Gerente | Todo el panel de la tienda |

Las contraseñas se entregan **por canal separado** de este documento y deben
cambiarse en el primer ingreso. Eskala conserva un acceso de administración
de plataforma que no forma parte de esta entrega.

> **Importante:** ninguno de los dos usuarios tiene correo electrónico
> cargado. Sin correo no existe recuperación de contraseña. Ver pendiente P1.

## 6. Pendientes conocidos

| Prioridad | Pendiente | Responsable |
|---|---|---|
| **P0** | Crear el flujo del bot (plantilla Bot Comercial) y revisar sus textos. | Eskala con el cliente |
| **P0** | Emparejar WhatsApp: escanear el QR desde el panel con el teléfono del negocio. | Cliente, con apoyo de Eskala |
| **P1** | Cargar un correo a cada usuario del panel para habilitar la recuperación de contraseña. | Eskala |
| **P1** | Apuntar `www.megahogar.org` a la tienda (registro A → 2.24.200.91) y añadir `www` al certificado. Hoy muestra la página de Hostinger. | Cliente (DNS en Hostinger) y Eskala (certificado) |
| **P2** | Definir qué módulos habilitados forman parte del servicio contratado y desactivar los que no. | Eskala con el cliente |
| **P2** | La dirección configurada en el panel está cortada ("Jr. Odonovan 174 Cercado - Huancavel") y así sale en la tienda. Corregirla en Datos del negocio. | Cliente |

## 7. Soporte

| | |
|---|---|
| Canal de soporte | *(completar)* |
| Horario de atención | *(completar)* |
| Plazo de respuesta | *(completar)* |
| Qué cubre | *(completar: incidencias de la plataforma, dudas de uso)* |
| Qué no cubre | *(completar: carga de productos, diseño de campañas, cambios de alcance)* |

## 8. Conformidad

El cliente declara haber recibido los accesos y verificado que la tienda
responde en las direcciones indicadas, y conoce los pendientes del punto 6.

| Por el cliente | Por Eskala |
|---|---|
| Nombre: | Nombre: |
| Cargo: | Cargo: |
| Firma: | Firma: |
| Fecha: | Fecha: |

---

*Documento generado a partir de datos comprobados en producción el
5 de septiembre de 2026. Fuente versionada:
`docs/entregas/megahogar/ACTA-ENTREGA-2026-09-05.md`.*
