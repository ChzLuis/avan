# BIXO ante SUNAT: revisión técnica y ruta formal (2026-09-05)

Objetivo: que BIXO permita a sus clientes emitir facturas, boletas, notas y
guías electrónicas válidas ante SUNAT, y decidir si conviene acreditarse
como PSE o basta operar como software del contribuyente.

## 1. Cómo emite BIXO hoy (arquitectura real)

BIXO **no genera ni firma XML**. Delega en APIsPERU (`facturacion.apisperu.com`),
que construye el UBL 2.1, lo firma con el **certificado del propio cliente**
(cargado por el cliente en su cuenta de APIsPERU) y lo envía a SUNAT con las
credenciales SOL secundarias del cliente. BIXO envía JSON y recibe la
respuesta de SUNAT.

Jurídicamente, cada cliente emite bajo la modalidad **SEE-Del contribuyente**
con su propio certificado. BIXO es el software del contribuyente; APIsPERU es
la capa técnica. Ninguno de los dos actúa como PSE ni OSE.

Código: `app/Support/ApisPeruService.php` (comprobantes y bajas),
`app/Support/Sunat/GuiaRemisionSender.php` (guías), `app/Support/Sunat/Catalogos.php`.

## 2. Checklist técnico

| Punto | Estado | Detalle |
|---|---|---|
| XML UBL 2.1 | Delegado | Lo arma APIsPERU (Greenter). BIXO manda el payload: tipoOperacion 0101, formaPago (Contado/Credito + cuotas desde hoy), detalle, leyenda 1000. |
| Firma digital | Delegado | Certificado del cliente en APIsPERU. BIXO no custodia certificados ni claves. |
| Series y correlativos | OK | Reserva transaccional con `lockForUpdate`, índice único (proyecto, tipo, serie, correlativo). Series propias para notas (FC01/FD01). El correlativo nunca lo elige el usuario. |
| Envío a SUNAT | OK | Facturas, boletas y notas por `/invoice/send` y `/note/send`. Reintento horario automático (`facturacion:reintentar`). Plazo de 3 días vigilado en portada y formulario. |
| CDR y hash | OK (05/09) | Hash y respuesta completa en `invoices.sunat_cdr`, ya `LONGTEXT`. Al aceptar, `ArchivoComprobantes` escribe el XML firmado y el CDR como ficheros en `storage/app/private/comprobantes/{proyecto}/` con nombre normado; botones XML y CDR en el detalle y en Comprobantes emitidos; `facturacion:archivar` rellenó los existentes. |
| Estados | OK | pending / accepted / rejected / error + baja_estado. El código 1033 (ya informado) se trata como aceptado. |
| Contingencia | **No existe** | Sin modo de contingencia ni resumen de contingencia. Si APIsPERU o SUNAT caen, el comprobante queda en error y reintenta cada hora (válido dentro de los 3 días). |
| Comunicación de baja (facturas) | OK | `/voided/send` con ticket. Solo se marca de baja si SUNAT responde. |
| Baja de boletas (resumen diario) | OK (05/09), pendiente de probar con una boleta real | `ApisPeruService::anularBoleta` envía el resumen diario (`/summary/send`, boleta en estado 3) y guarda el ticket; `facturacion:reintentar` consulta `/summary/status` cada hora y cierra la baja. El botón del detalle ya lo ofrece para boletas. |
| Notas de crédito y débito | OK | Motivos del catálogo 09/10, referencia al comprobante afectado, serie propia. |
| Guías de remisión (GRE) | OK | Remitente, M1/L, transporte público/privado, `/despatch/send` + `/despatch/status`. Ubigeo obligatorio. |
| Representación impresa | OK | Dos plantillas (moderna y clásica), A4, hash, leyenda en letras, cargo de recepción. PDF real con Chrome sin ventana. |
| QR | OK (05/09) | Contenido normado. Se genera en la propia hoja con `public/js/qrcode.min.js` embebido, sin servicio externo; vale para pantalla, impresión y el PDF con Chrome. |
| Conservación XML/CDR | OK (05/09) | Ficheros por negocio en el disco privado más la copia en la base. Falta incluir la descarga en el Portal del Cliente (/c/{token}) para cubrir el acceso del adquirente sin intervención del negocio. |
| Datos del emisor | OK | RUC, razón social, nombre comercial, dirección, ubigeo. Portada avisa si falta algo. |
| Reglas de negocio SUNAT | OK | Factura exige RUC, boleta ≥ S/ 700 exige documento, crédito exige vencimiento y cuota, fecha no futura ni de más de 3 días. |

### Brechas por prioridad

Las tres primeras se cerraron el 2026-09-05 (filas OK de la tabla). Quedan:

1. **Probar la baja de una boleta real** contra SUNAT: el código está, pero GABDE solo ha emitido facturas. La primera boleta anulada confirma el formato del resumen.
2. **Descarga de XML/CDR en el Portal del Cliente**, para que el comprador se sirva solo.
3. **Contingencia**: al menos un modo "emitir y encolar" con aviso claro cuando el envío falla, y un resumen de contingencia si se decide dar de alta series de contingencia.
4. **Modo pruebas SUNAT (beta)** por proyecto: hoy todo va contra producción; un cliente nuevo no puede probar sin emitir de verdad.
5. Dependencia única de APIsPERU: conviene aislar la interfaz (`SunatGateway`) para poder cambiar a Greenter propio u otro proveedor sin tocar controladores.

## 3. Parte formal y comercial

### ¿Qué es BIXO ante SUNAT?

**Software del contribuyente.** Cada cliente es emisor electrónico por el
SEE-Del contribuyente con su certificado. BIXO no firma ni emite en nombre de
nadie. No necesita inscribirse en ningún registro de SUNAT para vender el
sistema.

### ¿Conviene ser PSE?

Requisitos vigentes del Registro PSE (RS 199-2015/SUNAT, modificada por RS
108-2022/SUNAT): capital o activos netos ≥ **150 UIT**, mínimo **5
trabajadores** en planilla, **certificación ISO/IEC 27001** (obligatoria desde
07/2021), certificado digital propio registrado en SOL, régimen general,
declaraciones de los últimos 6 meses con ventas, ser emisor electrónico, sin
condena por delito tributario. Además, homologación de los documentos.

Para Eskala hoy es **desproporcionado**: el coste de la ISO 27001 y el capital
mínimo superan con mucho el beneficio. El PSE además asume la firma con su
propio certificado y responsabilidades operativas.

**OSE** queda descartado: exige 99,96 % de disponibilidad, ISO 27001, garantías
y reenvío de CDR a SUNAT en una hora. Es otro negocio.

### Ruta más barata y realista

1. Seguir como **software del contribuyente**, con APIsPERU (o Greenter propio)
   como motor. Cero trámites ante SUNAT para BIXO.
2. Cerrar las brechas 1 a 3 del checklist: son las que un contador o una
   fiscalización detectan primero.
3. Ofrecer al cliente el alta guiada: certificado digital (SUNAT da el
   Certificado Digital Tributario gratis a MYPE), usuario SOL secundario con el
   permiso de envío por servicio web, y prueba en beta.
4. Reevaluar PSE solo si el volumen lo justifica (cientos de emisores) y como
   proyecto de empresa, no de software.

### Qué se puede decir en la web

- Sí: "Emite facturas, boletas, notas y guías electrónicas válidas ante SUNAT",
  "Integrado con SUNAT mediante servicios web", "Cumple UBL 2.1".
- No: "Autorizado por SUNAT", "PSE", "OSE", "Homologado por SUNAT". Esos
  términos designan registros formales en los que BIXO no está y su uso es
  publicidad engañosa.

## 4. Alta guiada de un cliente (lo que hay que pedirle)

1. **Certificado digital** a nombre de su empresa. SUNAT entrega gratis el Certificado Digital Tributario a MYPE; si no califica, se compra a una entidad certificadora.
2. **Usuario SOL secundario** con el permiso "Envío de documentos electrónicos por servicio web" (Empresas → Comprobantes de pago → SEE - Del contribuyente).
3. **Cuenta en APIsPERU** a nombre del cliente: carga su certificado y sus credenciales SOL y obtiene el token.
4. **En BIXO → Configuración del proyecto**: RUC, razón social, dirección y ubigeo, series (F001, B001, FC01, FD01), token de APIsPERU y plantilla impresa. La portada de Facturación avisa de lo que falte.
5. **Primera factura de prueba** y comprobación del CDR (botón CDR en el detalle). Con eso el cliente está operando.

Ninguno de estos pasos es un trámite de BIXO ante SUNAT: son del emisor.

## 5. Fuentes

- SUNAT, Proveedor de Servicios Electrónicos (PSE): https://cpe.sunat.gob.pe/aliados/pse
- SUNAT, Sistema de Emisión del Contribuyente: https://cpe.sunat.gob.pe/sistema_emision/see_contribuyente
- SUNAT, Operador de Servicios Electrónicos (OSE): https://cpe.sunat.gob.pe/aliados/ose
- SUNAT, Boleta de Venta Electrónica (resumen diario): https://cpe.sunat.gob.pe/tipos_de_comprobantes/boleta
- gob.pe, Registro de PSE: https://www.gob.pe/26366-registro-de-proveedores-de-servicios-electronicos-pse
- Conservación de XML/CDR (plazos del Código Tributario): https://www.nubefact.com/blog/temporal/archivos-clave-de-la-facturacion-electronica-el-pdf-el-xml-y-el-cdr
- APIsPERU, API de facturación: https://apisperu.com/servicios/facturacion
- Greenter (motor UBL/firma): https://github.com/thegreenter/greenter
