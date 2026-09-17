# -*- coding: utf-8 -*-
# Mueve el modulo Inventario (movimientos de stock, ledger, proveedores) a
# app/Modules/Inventario y actualiza las referencias. Mismo esquema que los
# anteriores: conteo exacto (o "al menos una" con None) y parada antes de
# escribir si no cuadra. ImportLog NO viene: lo usa solo el catalogo.
import io, os, subprocess, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
M = 'app/Modules/Inventario'
B = chr(92)

def ns(*partes):
    return B.join(partes)

def git_mv(src, dst):
    os.makedirs(os.path.dirname(os.path.join(L, dst)), exist_ok=True)
    r = subprocess.run(['git', 'mv', src, dst], cwd=L, capture_output=True, text=True)
    if r.returncode != 0:
        print('ERROR git mv', src, '->', dst, r.stderr.strip()); sys.exit(1)
    print('mv ', src, '->', dst)

def reemplazar(path, cambios):
    p = os.path.join(L, path)
    s = io.open(p, encoding='utf-8', newline='').read()
    for viejo, nuevo, n in cambios:
        c = s.count(viejo)
        if (n is None and c < 1) or (n is not None and c != n):
            print(f'ERROR {path}: esperaba {n if n is not None else ">=1"} de {viejo!r}, hay {c}'); sys.exit(1)
        s = s.replace(viejo, nuevo)
    io.open(p, 'w', encoding='utf-8', newline='').write(s)
    print('ok ', path, f'({len(cambios)} cambios)')

NS_CTRL_VIEJO = 'namespace ' + ns('App','Http','Controllers') + ';'
NS_CTRL_NUEVO = 'namespace ' + ns('App','Modules','Inventario','Controllers') + ';' + '\n\nuse ' + ns('App','Http','Controllers','Controller') + ';'
NS_MOD_VIEJO  = 'namespace ' + ns('App','Models') + ';'
NS_MOD_NUEVO  = 'namespace ' + ns('App','Modules','Inventario','Models') + ';'
USE_M         = 'use ' + ns('App','Models')
USE_IM        = 'use ' + ns('App','Modules','Inventario','Models')
USE_MOV_V, USE_MOV_N   = USE_M + ns('','InventoryMovement') + ';', USE_IM + ns('','InventoryMovement') + ';'
USE_PROV_V, USE_PROV_N = USE_M + ns('','Proveedor') + ';',         USE_IM + ns('','Proveedor') + ';'
USE_LED_V, USE_LED_N   = 'use ' + ns('App','Support','InventoryLedger') + ';', 'use ' + ns('App','Modules','Inventario','Support','InventoryLedger') + ';'
FQ_LED_V, FQ_LED_N     = ns('','App','Support','InventoryLedger') + '::', ns('','App','Modules','Inventario','Support','InventoryLedger') + '::'
FQ_MOV_V, FQ_MOV_N     = ns('','App','Models','InventoryMovement') + '::', ns('','App','Modules','Inventario','Models','InventoryMovement') + '::'
FQ_PROV_V, FQ_PROV_N   = ns('','App','Models','Proveedor'),  ns('','App','Modules','Inventario','Models','Proveedor')

# 1) Mover.
git_mv('app/Http/Controllers/InventoryController.php', f'{M}/Controllers/InventoryController.php')
git_mv('app/Http/Controllers/ProveedorController.php', f'{M}/Controllers/ProveedorController.php')
git_mv('app/Models/InventoryMovement.php', f'{M}/Models/InventoryMovement.php')
git_mv('app/Models/Proveedor.php',         f'{M}/Models/Proveedor.php')
git_mv('app/Support/InventoryLedger.php',  f'{M}/Support/InventoryLedger.php')
git_mv('resources/views/inventory',                     f'{M}/Views/inventory')
git_mv('resources/views/company/proveedores.blade.php', f'{M}/Views/company/proveedores.blade.php')

# 2) Lo movido.
reemplazar(f'{M}/Controllers/InventoryController.php', [
    (NS_CTRL_VIEJO, NS_CTRL_NUEVO, 1), (USE_MOV_V, USE_MOV_N, 1), (USE_LED_V, USE_LED_N, 1),
    ("view('inventory.", "view('inventario::inventory.", None),
])
reemplazar(f'{M}/Controllers/ProveedorController.php', [
    (NS_CTRL_VIEJO, NS_CTRL_NUEVO, 1), (USE_PROV_V, USE_PROV_N, 1),
    ("view('company.proveedores'", "view('inventario::company.proveedores'", None),
])
reemplazar(f'{M}/Models/InventoryMovement.php', [
    (NS_MOD_VIEJO, NS_MOD_NUEVO + '\n\n' + USE_M + ns('','Product') + ';\n' + USE_M + ns('','Project') + ';\n' + USE_M + ns('','User') + ';', 1),
])
reemplazar(f'{M}/Models/Proveedor.php', [
    (NS_MOD_VIEJO, NS_MOD_NUEVO + '\n\n' + USE_M + ns('','Project') + ';', 1),
])
reemplazar(f'{M}/Support/InventoryLedger.php', [
    ('namespace ' + ns('App','Support') + ';', 'namespace ' + ns('App','Modules','Inventario','Support') + ';', 1),
    (USE_MOV_V, USE_MOV_N, 1),
])
reemplazar(f'{M}/Views/inventory/index.blade.php', [(FQ_LED_V, FQ_LED_N, None)])

# 3) Quien los usa desde fuera.
reemplazar('app/Catalog/Sync/CatalogSyncManager.php',        [(FQ_LED_V, FQ_LED_N, 1)])
reemplazar('app/Http/Controllers/Catalog/ProductController.php', [(FQ_LED_V, FQ_LED_N, 4), (FQ_MOV_V, FQ_MOV_N, 1)])
reemplazar('app/Http/Controllers/OrderController.php',       [(FQ_LED_V, FQ_LED_N, 1), (FQ_MOV_V, FQ_MOV_N, 2)])
reemplazar('app/Http/Controllers/PosController.php',         [(FQ_LED_V, FQ_LED_N, 1)])
reemplazar('app/Http/Controllers/PublicController.php',      [(FQ_LED_V, FQ_LED_N, 1)])
reemplazar('app/Modules/Control/Controllers/AdminImportController.php', [(FQ_LED_V, FQ_LED_N, 1)])
reemplazar('app/Support/BusquedaGlobal.php',                 [(FQ_PROV_V + '::', FQ_PROV_N + '::', 1)])
reemplazar('app/Models/Product.php', [(NS_MOD_VIEJO, NS_MOD_VIEJO + '\n\n' + USE_MOV_N, 1)])
reemplazar('app/Models/Project.php', [(NS_MOD_VIEJO, NS_MOD_VIEJO + '\n\n' + USE_PROV_N, 1)])
reemplazar('routes/web.php', [
    ('use ' + ns('App','Http','Controllers','ProveedorController') + ';', 'use ' + ns('App','Modules','Inventario','Controllers','ProveedorController') + ';', 1),
    (ns('','App','Http','Controllers','InventoryController') + '::class', ns('','App','Modules','Inventario','Controllers','InventoryController') + '::class', 3),
])
reemplazar('tests/Feature/InventoryLedgerTest.php',      [(USE_MOV_V, USE_MOV_N, 1), (USE_LED_V, USE_LED_N, 1)])
reemplazar('tests/Feature/TenantOwnedModelScopeTest.php', [(USE_MOV_V, USE_MOV_N, 1), (USE_PROV_V, USE_PROV_N, 1)])
reemplazar('tests/Feature/ProjectIsolationTest.php',     [(USE_PROV_V, USE_PROV_N, 1)])
reemplazar('tests/Feature/PanelAuthorizationTest.php',   [(FQ_PROV_V, FQ_PROV_N, 2)])
reemplazar('tests/Feature/AislamientoTenantTest.php',    [(FQ_MOV_V, FQ_MOV_N, 1), (FQ_PROV_V, FQ_PROV_N, 1)])
print('LISTO')
