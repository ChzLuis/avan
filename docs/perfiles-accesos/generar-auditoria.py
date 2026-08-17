# -*- coding: utf-8 -*-
"""
Genera la auditoría de permisos y accesos (FASE 0 del plan de Perfiles y Accesos).

La gracia de que esto sea un script y no un documento escrito a mano es poder
repetirlo tras cada fase y comparar ANTES vs DESPUÉS con un diff.

Uso:
    # 1) volcar las tres consultas de producción a un directorio
    python deploy.py --sql "..."  > <datos>/permisos.tsv
    python deploy.py --sql "..."  > <datos>/roles.tsv
    python deploy.py --sql "..."  > <datos>/proyectos.tsv
    # 2) generar el mapa de rutas
    php artisan route:list --json > <datos>/rutas.json
    # 3) generar el informe
    python docs/perfiles-accesos/generar-auditoria.py <datos> > docs/perfiles-accesos/01-AUDITORIA-ANTES.md

Las consultas exactas están al final de este archivo, en CONSULTAS.
"""

import io
import json
import os
import re
import subprocess
import sys

# La consola de Windows usa cp1252 y destrozaba los acentos del informe.
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", errors="replace")

RAIZ = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
MUTADORES = {"POST", "PUT", "PATCH", "DELETE"}

# Áreas del modelo objetivo (fase 4). Cada permiso actual se propone a un área.
AREAS = [
    "Catálogo", "Inventario", "Pedidos", "Cotizaciones", "Clientes",
    "Punto de venta", "Caja", "Facturación", "Cobros", "Agenda",
    "Personal", "Configuración",
]

# Prefijo de permiso -> área objetivo. Lo que no encaje se marca SIN DESTINO
# para que nadie lo dé por migrado sin decidirlo (regla del plan: ningún permiso
# antiguo puede quedar sin decisión documentada).
AREA_DE = {
    "catalog": "Catálogo", "catalog-integrations": "Catálogo",
    "create-products": "Catálogo", "edit-products": "Catálogo",
    "delete-products": "Catálogo", "view-catalog": "Catálogo",
    "inventory": "Inventario", "proveedores": "Inventario",
    "orders": "Pedidos", "view-orders": "Pedidos", "manage-orders": "Pedidos",
    "manage-logistics": "Pedidos", "view-logistics": "Pedidos",
    "quotes": "Cotizaciones", "view-quotes": "Cotizaciones", "manage-quotes": "Cotizaciones",
    "clients": "Clientes", "view-clients": "Clientes", "manage-clients": "Clientes",
    "pos": "Punto de venta",
    "caja": "Caja",
    "invoices": "Facturación",
    "payments": "Cobros",
    "agenda": "Agenda", "view-agenda": "Agenda", "manage-agenda": "Agenda",
    "hr": "Personal", "attendance": "Personal", "view-hr": "Personal", "manage-hr": "Personal",
    "settings": "Configuración", "manage-settings": "Configuración",
    "manage-modules": "Configuración", "manage-members": "Configuración",
    "roles": "Configuración",
}

# ── FASE 2: decisiones sobre los permisos que no encajan por prefijo ──────────
#
# Se tomaron con datos de producción (2026-08-16): las tablas de rifas, mapas
# operativos y solicitudes internas/externas están VACÍAS, y `tickets` ni
# siquiera existe como tabla. No se fuerzan dentro de las 12 áreas: inventar un
# hueco para un módulo sin uso ensucia el modelo canónico.
#
#   permiso -> (destino, estado, justificación)
DECISIONES = {
    'settings.negocio':   ('Configuración / Trabajar',   'SUSTITUIR', 'Datos del negocio, SEO y flujo: configuración operativa del día a día.'),
    'settings.diseno':    ('Configuración / Trabajar',   'SUSTITUIR', 'La tienda se retoca a diario; equivocarse no destruye datos.'),
    'settings.catalogos': ('Configuración / Trabajar',   'SUSTITUIR', 'Listas maestras y perfiles de catálogo.'),
    'settings.qr':        ('Configuración / Trabajar',   'SUSTITUIR', 'El QR se regenera sin consecuencias.'),
    'settings.pagos':     ('Configuración / Administrar', 'SUSTITUIR', 'Toca dinero: cambiar una cuenta de cobro desvía los pagos del negocio.'),
    'settings.editar':    ('Configuración / Administrar', 'ELIMINAR AL FINAL', 'Ningún usuario lo tiene y ninguna ruta lo usa. Se retira en Fase 13.'),
    'settings.ver':       ('Configuración / Ver',        'SUSTITUIR', 'Consultar la configuración sin cambiarla.'),

    'catalog.resenas':                  ('Catálogo / Trabajar', 'SUSTITUIR', 'Moderar reseñas es tarea diaria. Borrarlas quedará en Catálogo / Administrar.'),
    'catalog-integrations.view-history': ('Catálogo / Ver',      'SUSTITUIR', 'Historial de sincronización: solo lectura.'),

    'reports.ver':      ('(transversal)', 'SUSTITUIR', 'Un reporte no es un área: se ve lo que ya puedes ver. Pasa a resolverse por el área de cada dato.'),
    'reports.exportar': ('(transversal)', 'SUSTITUIR', 'Exportar exige Trabajar en el área correspondiente.'),

    'mapa.ver':    ('Pedidos / Ver',      'PENDIENTE DE CONECTAR', 'Reparto. `operational_maps` está vacía: ninguna ruta lo aplica todavía.'),
    'mapa.editar': ('Pedidos / Trabajar', 'PENDIENTE DE CONECTAR', 'Ídem.'),

    'rifas.ver':      ('(módulo sin uso)', 'PENDIENTE DE CONECTAR', 'Tablas `rifas` y `rifa_ventas` vacías. Decidir si el módulo se mantiene antes de darle área.'),
    'rifas.validar':  ('(módulo sin uso)', 'PENDIENTE DE CONECTAR', 'Ídem.'),
    'rifas.cancelar': ('(módulo sin uso)', 'PENDIENTE DE CONECTAR', 'Ídem.'),

    'tickets.ver':      ('(no existe)', 'HUÉRFANO', 'No hay tabla de tickets en la base. Eliminar en Fase 13.'),
    'tickets.eliminar': ('(no existe)', 'HUÉRFANO', 'Ídem.'),

    'view-requests':   ('(módulo sin uso)', 'LEGACY', 'Solicitudes internas y externas: ambas tablas vacías, y además nomenclatura inglesa.'),
    'manage-requests': ('(módulo sin uso)', 'LEGACY', 'Ídem.'),
}

# Sufijo de acción -> nivel objetivo.
NIVEL_DE = {
    "ver": "Ver", "view": "Ver",
    "crear": "Trabajar", "editar": "Trabajar", "importar": "Trabajar",
    "fichar": "Trabajar", "usar": "Trabajar", "abrir": "Trabajar",
    "cerrar": "Trabajar", "movimiento": "Trabajar", "sync": "Trabajar",
    "eliminar": "Administrar", "cancelar": "Administrar", "anular": "Administrar",
    "descuento": "Administrar", "aprobar": "Administrar", "rechazar": "Administrar",
    "gestionar": "Administrar", "manage": "Administrar", "validar": "Administrar",
}


def leer_tsv(ruta):
    if not os.path.exists(ruta):
        return []
    filas = []
    with open(ruta, encoding="utf-8", errors="replace") as fh:
        lineas = [l.rstrip("\n") for l in fh if l.strip()]
    if not lineas:
        return []
    cab = lineas[0].split("\t")
    for l in lineas[1:]:
        partes = l.split("\t")
        if len(partes) < len(cab):
            partes += [""] * (len(cab) - len(partes))
        filas.append(dict(zip(cab, partes)))
    return filas


def texto(*rutas_rel):
    out = ""
    for r in rutas_rel:
        p = os.path.join(RAIZ, r)
        if os.path.isfile(p):
            with open(p, encoding="utf-8", errors="replace") as fh:
                out += fh.read()
    return out


def grep(patron, ruta_rel):
    """
    Devuelve las líneas de un árbol que ejecutan comprobaciones de permiso.

    La ruta va con barras normales a propósito: con las barras invertidas de
    Windows, grep no resolvía el directorio, devolvía vacío en silencio y los
    permisos que solo se comprueban dentro de un controlador —como
    orders.cancelar— salían marcados como muertos.
    """
    destino_grep = (RAIZ + "/" + ruta_rel).replace("\\", "/")
    r = subprocess.run(
        # -e es obligatorio: el patrón empieza por '-' y grep lo tomaba por una opción.
        ["grep", "-rhE", "-e", patron, destino_grep, "--include=*.php"],
        capture_output=True, text=True, errors="replace",
    )
    if r.returncode > 1:
        raise RuntimeError(f"grep falló sobre {destino_grep}: {r.stderr.strip()}")
    return r.stdout


def destino(permiso):
    if permiso in DECISIONES:
        return DECISIONES[permiso][0]
    base = permiso.split(".")[0] if "." in permiso else permiso
    accion = permiso.split(".")[-1] if "." in permiso else permiso
    area = AREA_DE.get(base) or AREA_DE.get(permiso)
    if permiso.startswith("view-"):
        nivel = "Ver"
    elif permiso.startswith(("manage-", "create-", "edit-", "delete-")):
        nivel = "Administrar" if permiso.startswith(("manage-", "delete-")) else "Trabajar"
    else:
        nivel = NIVEL_DE.get(accion)
    if not area or not nivel:
        return None
    return f"{area} / {nivel}"


def main():
    datos = sys.argv[1] if len(sys.argv) > 1 else "."

    permisos = leer_tsv(os.path.join(datos, "permisos.tsv"))
    roles = leer_tsv(os.path.join(datos, "roles.tsv"))
    proyectos = leer_tsv(os.path.join(datos, "proyectos.tsv"))
    with open(os.path.join(datos, "rutas.json"), encoding="utf-8") as fh:
        rutas = json.load(fh)

    # ── Dónde se aplica cada permiso ──────────────────────────────────────
    en_rutas = texto("routes/web.php", "routes/admin.php", "routes/bixo.php", "routes/api.php")
    # [(] en vez de \( : este grep interpreta \( como apertura de grupo y aborta.
    en_codigo = grep(r"->can[(]|hasPermissionTo[(]|checkPermission|can:", "app")
    en_vistas = grep(r"@can|->can[(]", "resources/views")

    for p in permisos:
        n = p["name"]
        p["ruta"] = n in en_rutas
        p["codigo"] = n in en_codigo
        p["vista"] = n in en_vistas
        p["aplicado"] = p["ruta"] or p["codigo"]
        p["legacy"] = n.startswith(("view-", "manage-", "create-", "edit-", "delete-"))
        p["destino"] = destino(n)
        p["n_roles"] = len([x for x in p.get("roles", "").split(",") if x])
        # La decisión de la Fase 2 manda sobre la deducción automática.
        if n in DECISIONES:
            p["estado"] = DECISIONES[n][1]
            p["justificacion"] = DECISIONES[n][2]
        elif p["aplicado"]:
            p["estado"] = "ACTIVO"
            p["justificacion"] = "Se aplica y tiene destino directo en el modelo canónico."
        elif p["legacy"]:
            p["estado"] = "LEGACY"
            p["justificacion"] = "Nomenclatura inglesa. Solo se acepta en Clientes, Pedidos y Cotizaciones."
        elif p["n_roles"] == 0:
            p["estado"] = "HUÉRFANO"
            p["justificacion"] = "Ni se aplica ni lo tiene ningún rol."
        else:
            p["estado"] = "PENDIENTE DE CONECTAR"
            p["justificacion"] = "Existe y hay roles que lo tienen, pero ninguna ruta lo comprueba."

    # ── Rutas ─────────────────────────────────────────────────────────────
    panel, abiertas, protegidas = [], [], []
    for r in rutas:
        uri = r.get("uri", "")
        if not uri.startswith(("bixoadmin", "bixosales", "portal", "admin")):
            continue
        # login/logout no pueden exigir permiso: son la puerta de entrada.
        if re.search(r"(^|/)(login|logout)$", uri):
            continue
        verbos = set(r.get("method", "").split("|")) & MUTADORES
        mw = " ".join(r.get("middleware") or [])
        perms = re.findall(r"(?:project\.)?can:([\w.|-]+)", mw)
        fila = {
            "uri": uri,
            "verbos": "/".join(sorted(verbos)) if verbos else "GET",
            "muta": bool(verbos),
            "accion": r.get("action", "").split("\\")[-1],
            "nombre": r.get("name") or "",
            "permisos": ", ".join(perms) if perms else "",
            "superadmin": "superadmin" in mw,
            "area": uri.split("/")[1] if "/" in uri else uri,
        }
        panel.append(fila)
        if fila["muta"]:
            (protegidas if (perms or fila["superadmin"]) else abiertas).append(fila)

    por_area = {}
    for f in abiertas:
        por_area.setdefault(f["area"], []).append(f)

    # ── Informe ───────────────────────────────────────────────────────────
    P = print
    P("# AUDITORÍA — ANTES DE LA MIGRACIÓN")
    P()
    P("> FASE 0 del plan de Perfiles y Accesos. Fotografía del sistema **antes** de")
    P("> tocar ninguna autorización. Regenerable con `generar-auditoria.py` para")
    P("> comparar el antes y el después de cada fase.")
    P()
    P("## Resumen")
    P()
    P("| Métrica | Valor |")
    P("|---|---:|")
    P(f"| Permisos definidos | {len(permisos)} |")
    P(f"| Permisos aplicados en ruta o controlador | {len([p for p in permisos if p['aplicado']])} |")
    P(f"| Permisos que no restringen nada | {len([p for p in permisos if not p['aplicado']])} |")
    P(f"| Permisos de nomenclatura legacy (inglesa) | {len([p for p in permisos if p['legacy']])} |")
    P(f"| Roles definidos | {len(roles)} |")
    P(f"| Roles con usuarios asignados | {len([r for r in roles if int(r.get('n_usuarios') or 0) > 0])} |")
    P(f"| Roles sin ningún usuario | {len([r for r in roles if int(r.get('n_usuarios') or 0) == 0])} |")
    P(f"| Acciones del panel que modifican datos | {len(protegidas) + len(abiertas)} |")
    P(f"| ...con autorización | {len(protegidas)} |")
    P(f"| ...**sin autorización** | {len(abiertas)} |")
    P(f"| Proyectos activos | {len(proyectos)} |")
    P()

    P("## 1. Mapa de permisos")
    P()
    P("`Ruta`/`Código` indican dónde se comprueba de verdad. Un permiso sin ninguna")
    P("de las dos marcas aparece en la pantalla de Roles pero no restringe nada.")
    P()
    P("| Permiso | Estado | Ruta | Código | Roles | Destino propuesto |")
    P("|---|---|:--:|:--:|--:|---|")
    for p in sorted(permisos, key=lambda x: (x["estado"] != "ACTIVO", x["name"])):
        d = p["destino"] or "**SIN DESTINO — decidir**"
        P(f"| `{p['name']}` | {p['estado']} | {'x' if p['ruta'] else ''} | "
          f"{'x' if p['codigo'] else ''} | {p['n_roles']} | {d} |")
    P()

    P("## 2. Mapa de roles")
    P()
    P("| Rol | Permisos | Usuarios | Observación |")
    P("|---|--:|--:|---|")
    for r in roles:
        nom = r.get("name", "")
        np, nu = int(r.get("n_permisos") or 0), int(r.get("n_usuarios") or 0)
        suyos = [p for p in permisos if nom in p.get("roles", "").split(",")]
        legacy = len([p for p in suyos if p["legacy"]])
        obs = []
        if nu == 0:
            obs.append("sin usuarios")
        if suyos and legacy == len(suyos):
            obs.append("**construido solo con permisos legacy: no funciona fuera de pedidos/cotizaciones/clientes**")
        elif legacy:
            obs.append(f"{legacy} legacy")
        muertos = len([p for p in suyos if not p["aplicado"]])
        if muertos:
            obs.append(f"{muertos} sin efecto")
        P(f"| `{nom}` | {np} | {nu} | {'; '.join(obs) or 'ok'} |")
    P()

    P("## 3. Alcance por proyecto")
    P()
    P("Los roles **no pertenecen a ningún proyecto**: la tabla `roles` no tiene")
    P("`project_id`. El alcance por negocio se simula en `SetActiveProject`, que en")
    P("cada petición hace `syncRoles()` con el rol de la ficha de empleado del")
    P("proyecto activo — y `syncRoles([])` si no hay ficha, borrando los roles")
    P("globales del usuario. Ver la sección de riesgos.")
    P()
    P("| Proyecto | Empleados activos | Miembros | Roles en uso |")
    P("|---|--:|--:|---|")
    for pr in proyectos:
        P(f"| {pr.get('proyecto','')} | {pr.get('empleados','0')} | "
          f"{pr.get('miembros','0')} | {pr.get('roles_en_uso','-')} |")
    P()

    P("## 4. Acciones que modifican datos SIN autorización")
    P()
    P(f"Total: **{len(abiertas)}**. Basta con ser miembro del proyecto.")
    P()
    P("| Área | Acciones abiertas |")
    P("|---|--:|")
    for a, fs in sorted(por_area.items(), key=lambda kv: -len(kv[1])):
        P(f"| {a} | {len(fs)} |")
    P()
    P("<details><summary>Listado completo</summary>")
    P()
    P("| Verbo | Ruta | Controlador |")
    P("|---|---|---|")
    for f in sorted(abiertas, key=lambda x: (x["area"], x["uri"])):
        P(f"| {f['verbos']} | `{f['uri']}` | {f['accion']} |")
    P()
    P("</details>")
    P()

    P("## 5. Acciones que modifican datos CON autorización")
    P()
    P("| Verbo | Ruta | Permiso exigido |")
    P("|---|---|---|")
    for f in sorted(protegidas, key=lambda x: x["uri"]):
        P(f"| {f['verbos']} | `{f['uri']}` | "
          f"{('`' + f['permisos'] + '`') if f['permisos'] else 'solo superadmin'} |")
    P()

    P("## 6. Clasificación y tabla de migración (FASE 2)")
    P()
    P("Cada permiso con su estado, su destino en el modelo canónico y por qué.")
    P("Las decisiones sobre los que no encajaban por prefijo están tomadas con")
    P("datos de producción y viven en `DECISIONES`, dentro de este generador.")
    P()
    conteo = {}
    for p in permisos:
        conteo[p["estado"]] = conteo.get(p["estado"], 0) + 1
    P("| Estado | Permisos | Significado |")
    P("|---|--:|---|")
    SIGNIFICADO = {
        "ACTIVO": "Se aplica hoy y tiene destino directo.",
        "SUSTITUIR": "Se aplica, pero cambia de nombre o de nivel en el modelo canónico.",
        "LEGACY": "Nomenclatura inglesa. Se retira en la Fase 13.",
        "HUÉRFANO": "No lo usa nadie ni existe lo que protege. Se elimina.",
        "PENDIENTE DE CONECTAR": "Existe y hay roles que lo tienen, pero ninguna ruta lo comprueba.",
        "ELIMINAR AL FINAL": "Se conserva mientras haga falta y se retira en la Fase 13.",
    }
    for estado, n in sorted(conteo.items(), key=lambda kv: -kv[1]):
        P(f"| {estado} | {n} | {SIGNIFICADO.get(estado, '')} |")
    P()
    P("| Permiso | Estado | Roles | Destino | Justificación |")
    P("|---|---|--:|---|---|")
    orden = {"ACTIVO": 0, "SUSTITUIR": 1, "PENDIENTE DE CONECTAR": 2,
             "LEGACY": 3, "ELIMINAR AL FINAL": 4, "HUÉRFANO": 5}
    for p in sorted(permisos, key=lambda x: (orden.get(x["estado"], 9), x["name"])):
        d = p["destino"] or "**SIN DESTINO — decidir**"
        P(f"| `{p['name']}` | {p['estado']} | {p['n_roles']} | {d} | {p.get('justificacion','')} |")
    P()

    sin_destino = [p for p in permisos if not p["destino"]]
    if sin_destino:
        P(f"**Permisos todavía sin decisión: {len(sin_destino)}** — "
          + ", ".join(f"`{p['name']}`" for p in sin_destino))
    else:
        P("**Todos los permisos tienen decisión documentada.** Requisito de entrada")
        P("a la Fase 5 cumplido.")
    P()


if __name__ == "__main__":
    main()
