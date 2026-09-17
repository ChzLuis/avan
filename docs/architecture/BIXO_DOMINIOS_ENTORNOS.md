# BIXO — Dominios y entornos

Inventario verificado el **2026-08-30** contra el VPS ARIN (`2.24.200.91`) y
DNS público (resolver 8.8.8.8). Base para separar **pruebas** de **producción**.

> Método: `nginx sites-enabled` + tabla `projects` + resolución DNS externa +
> `curl` real + vencimiento de certificados. Nada aquí sale de memoria.

---

## 1. Cómo está montado hoy

**Una sola instalación Laravel** sirve TODO:

- Ruta: `/home/arindg/htdocs/arindg.com` (2.1 GB) · storage 481 MB
- Base de datos: `dbarin` · PHP-FPM pool `127.0.0.1:20002`
- Panel de hosting: **CloudPanel** (`clpctl`) — crear un sitio nuevo genera
  nginx + pool PHP + SSL automáticamente
- **Sin git en el servidor**: el despliegue es archivo por archivo con
  `deploy.py` (`BASE` fijo a arindg). Por eso existe la puerta de deriva.

**14 `server_name` distintos apuntan al mismo `root`.** Cambiar de entorno =
repuntar esos vhosts, no mover datos.

Fuera de BIXO (no tocar): `admin.mercadosmayoristas.com.pe`,
`bot.pruebatusuerte.com.pe`, `srv1588646.hstgr.cloud`, `n8n.arindg.com` (proxy
a `:5678`) y `eskalagroup.com` (sitio **estático**, no Laravel).

---

## 2. Proyectos con dominio — estado real

| Proyecto | id | Dominio (BD) | DNS | HTTP | Estado |
|---|---|---|---|---|---|
| Corporación MegaHogar | 19 | megahogar.org | ✅ 2.24.200.91 | 200 | **OK** |
| Electro Jara | 21 | jaraluzcorporation.com | ✅ 2.24.200.91 | 200 | **OK** |
| Tecsist Solution | 18 | tienda.tecsist.net | ✅ 2.24.200.91 | 200 | **OK** |
| Market Huacho Express | 7 | markethuachoexpress.arindg.com | ✅ 2.24.200.91 | 200 | **OK** — depende de arindg |
| Baby Toncito | 20 | babytoncito.com | ❌ **no existe** | — | **CAÍDA** |
| IMPORT MUSUHUAY | 22 | importmusuhuaysac.arindg.com | ❌ **no existe** | — | **CAÍDA** |
| Eskala (interno) | 16 | — | — | — | sin dominio |
| Distribuidores GABDE | 10 | — | — | — | sin dominio |

### 2.1 Las 2 tiendas caídas (hallazgo 2026-08-30)

No es un fallo de código: **el registro DNS nunca se creó**. Ambas tienen su
vhost en nginx y su proyecto activo en la BD, pero el dominio no resuelve.

- **`babytoncito.com` → NXDOMAIN.** Además su alias `babytoncito.arindg.com`
  sí resuelve pero **muestra el login del panel**, no su tienda: el
  `custom_domain` en BD es `babytoncito.com`, así que `DetectCustomDomain` no
  reconoce el alias. Resultado: el cliente **no tiene ninguna URL que funcione**.
- **`importmusuhuaysac.arindg.com` → NXDOMAIN.** Falta el registro A. Ojo: el
  wildcard de arindg es solo de **certificado**, no de DNS — cada subdominio
  necesita su propio registro (`markethuachoexpress` sí lo tiene).

**Arreglo (independiente de la migración):** crear el registro A → `2.24.200.91`
en el DNS de cada dominio. Para Baby Toncito, además decidir si su dominio
definitivo es `babytoncito.com` (y registrarlo/apuntarlo) o el subdominio — y
dejar el `custom_domain` de la BD coherente con esa decisión.

### 2.2 Alias heredados (se pueden retirar)

`tecsistsolution.arindg.com` y `babytoncito.arindg.com` resuelven pero caen al
login del panel. No sirven a nadie hoy. Convertir en redirect 301 al dominio
real del cliente, o eliminar.

`prueba.arindg.com` y `prueba2.arindg.com`: sin DNS, y su vhost apunta al
certificado `arindg.com` **vencido el 2026-08-27**. Inofensivo hoy (nadie los
alcanza) pero hay que limpiarlos o rehacerlos al montar el entorno de pruebas.

---

## 3. Certificados SSL

| Certificado | Vence | Cubre |
|---|---|---|
| `arindg-wildcard` | 2026-11-04 | `*.arindg.com`, `arindg.com`, `megahogar.org`, `jaraluzcorporation.com` |
| `megahogar.org` | 2026-10-22 | megahogar.org |
| `jaraluzcorporation.com` | 2026-11-04 | jaraluzcorporation.com |
| `tienda.tecsist.net` | 2026-11-09 | tienda.tecsist.net |
| `tecsist.net` | 2026-10-19 | tecsist.net |
| `markethuachoexpress.arindg.com` | 2026-11-09 | markethuachoexpress.arindg.com |
| `babytoncito.arindg.com` | 2026-11-09 | babytoncito.arindg.com |
| `arindg.com` (suelto) | ⚠️ **2026-08-27 — VENCIDO** | usado solo por `prueba*.arindg.com` |

`babytoncito.com` **no tiene certificado propio**: su vhost usa el wildcard de
arindg, que no lo cubre. Si se le crea el DNS sin emitir certificado, dará
error de SSL. Emitir el cert **antes** de publicar el DNS.

---

## 4. Restricciones que condicionan la separación

1. **Un número de WhatsApp = una sesión.** Los conectores PM2 (`bixo-baileys`,
   `bixo-megahogar`) mandan su webhook a **un solo host**. El mismo número no
   puede atender pruebas y producción: el entorno de pruebas necesita **línea
   de WhatsApp propia** o no podrá probar el bot, que es lo que más se rompe.
2. **La app es dueña de la raíz.** Rutas comodín `/{slug}`, `/{slug}/contacto`,
   `/{slug}/blog`, más `/build`, `/storage` y `/api/bot/inbound`. Por eso
   montarla en una subcarpeta de un sitio estático (`eskalagroup.com/bixo…`)
   está descartado: obliga a mapear cada ruta a mano en nginx.
3. **`/bixosales` y `/bixoadmin` ya son rutas de la app**
   (`routes/web.php:1030` y `:101`). Se conservan tal cual con solo cambiar de
   host — cero cambios de código.
4. **Sin git en el servidor.** Hoy ya divergen local y ARIN. Con dos
   instalaciones serían **tres copias divergiendo**. Un entorno de pruebas
   infiel da falsa confianza: poner git **antes** de duplicar.

---

## 5. Esquema de URLs propuesto

Principio: **una URL de cliente nunca lleva el nombre del entorno.** Hoy Market
Huacho (producción) vive en `arindg.com`, que se quiere volver de pruebas.

|  | Producción | Pruebas |
|---|---|---|
| Panel admin | `app.bixo.pe/bixoadmin` | `arindg.com/bixoadmin` |
| Ventas | `app.bixo.pe/bixosales` | `arindg.com/bixosales` |
| Cliente sin dominio | `markethuacho.bixo.pe` | `markethuacho.arindg.com` |
| Cliente con dominio | `megahogar.org` *(no cambia)* | `megahogar.arindg.com` |

Alternativa sin costo: `bixo.eskalagroup.com/…` y
`<cliente>.bixo.eskalagroup.com` — funciona, pero son URLs de 4 niveles y el
wildcard exige validación DNS-01 en cada renovación.

**Los 4 clientes con dominio propio no cambian de URL jamás**: solo cambia a qué
carpeta apunta su nginx. Para ellos es invisible.

---

## 6. Orden de trabajo

1. **Arreglar las 2 tiendas caídas** — no depende de nada más. *(§2.1)*
2. **Selector de destino en `deploy.py`** + código del servidor bajo git. Sin
   esto, un despliegue distraído entra al sitio equivocado.
3. Crear el sitio de producción con `clpctl` (nginx + pool + SSL automáticos),
   apuntando a la **BD actual `dbarin`**: los datos de los clientes no se mueven,
   riesgo casi cero.
4. Migrar clientes **uno a uno, del más pequeño al más grande**. Nunca MegaHogar
   primero.
5. arindg queda de pruebas: copia refrescable de la BD + línea WhatsApp propia.

**Decisión pendiente del usuario:** dominio de producción (`bixo.pe` propio vs
`bixo.eskalagroup.com`) y si habrá chip de WhatsApp para pruebas.
