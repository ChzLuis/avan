# -*- coding: utf-8 -*-
# Mueve el modulo Control (superadmin, licencias, demos, auditoria) a
# app/Modules/Control y actualiza las referencias. Mismo esquema que
# mover_personas.py: cada reemplazo exige un numero exacto de apariciones
# (o "al menos una" si n es None) y se detiene ANTES de escribir si no cuadra.
import io, os, subprocess, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
M = 'app/Modules/Control'
B = chr(92)  # barra invertida

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

NS_ADMIN_VIEJO = 'namespace ' + ns('App','Http','Controllers','Admin') + ';'
NS_CTRL_VIEJO  = 'namespace ' + ns('App','Http','Controllers') + ';'
NS_CTRL_NUEVO  = 'namespace ' + ns('App','Modules','Control','Controllers') + ';'
NS_MOD_VIEJO   = 'namespace ' + ns('App','Models') + ';'
NS_MOD_NUEVO   = 'namespace ' + ns('App','Modules','Control','Models') + ';'
USE_MODELS     = 'use ' + ns('App','Models')
FQ_ACCESS_V    = ns('','App','Models','AccessEvent') + '::'
FQ_ACCESS_N    = ns('','App','Modules','Control','Models','AccessEvent') + '::'
FQ_LIC_V       = ns('','App','Support','LicenseManager')
FQ_LIC_N       = ns('','App','Modules','Control','Support','LicenseManager')
USE_DEMO_V     = USE_MODELS + ns('','DemoRequest') + ';'
USE_DEMO_N     = 'use ' + ns('App','Modules','Control','Models','DemoRequest') + ';'
USE_ACC_V      = USE_MODELS + ns('','AccessEvent') + ';'
USE_ACC_N      = 'use ' + ns('App','Modules','Control','Models','AccessEvent') + ';'

ADMIN = ['AdminAuthController', 'AdminDashboardController', 'AdminImportController',
         'AdminLicenseController', 'AdminProjectController', 'AdminSettingsController',
         'AdminTurnosController', 'AdminUserController']

# 1) Mover conservando historial.
for c in ADMIN:
    git_mv(f'app/Http/Controllers/Admin/{c}.php', f'{M}/Controllers/{c}.php')
git_mv('app/Http/Controllers/DemoController.php', f'{M}/Controllers/DemoController.php')
git_mv('app/Models/AccessEvent.php',  f'{M}/Models/AccessEvent.php')
git_mv('app/Models/DemoRequest.php',  f'{M}/Models/DemoRequest.php')
git_mv('app/Support/LicenseManager.php', f'{M}/Support/LicenseManager.php')
git_mv('resources/views/admin', f'{M}/Views/admin')
git_mv('resources/views/demo',  f'{M}/Views/demo')
git_mv('resources/views/components/admin-layout.blade.php', f'{M}/Views/components/admin-layout.blade.php')

# 2) Controladores: namespace + vistas bajo control::
for c in ADMIN:
    cambios = [(NS_ADMIN_VIEJO, NS_CTRL_NUEVO, 1), ("view('admin.", "view('control::admin.", None)]
    if c == 'AdminLicenseController':
        cambios.append(('use ' + ns('App','Support','LicenseManager') + ';', 'use ' + ns('App','Modules','Control','Support','LicenseManager') + ';', 1))
    reemplazar(f'{M}/Controllers/{c}.php', cambios)
reemplazar(f'{M}/Controllers/DemoController.php', [
    (NS_CTRL_VIEJO, NS_CTRL_NUEVO + '\n\nuse ' + ns('App','Http','Controllers','Controller') + ';', 1),
    (USE_DEMO_V, USE_DEMO_N, 1),
    ("view('admin.", "view('control::admin.", None),
    ("view('demo.", "view('control::demo.", None),
])

# 3) Modelos y soporte.
reemplazar(f'{M}/Models/AccessEvent.php', [
    (NS_MOD_VIEJO, NS_MOD_NUEVO + '\n\n' + USE_MODELS + ns('','User') + ';', 1),
])
reemplazar(f'{M}/Models/DemoRequest.php', [
    (NS_MOD_VIEJO, NS_MOD_NUEVO + '\n\n' + USE_MODELS + ns('','Project') + ';\n' + USE_MODELS + ns('','User') + ';', 1),
])
reemplazar(f'{M}/Support/LicenseManager.php', [
    ('namespace ' + ns('App','Support') + ';', 'namespace ' + ns('App','Modules','Control','Support') + ';', 1),
])
# En la vista todas las apariciones deben migrar: no hay numero "correcto".
reemplazar(f'{M}/Views/admin/licenses/index.blade.php', [(FQ_LIC_V, FQ_LIC_N, None)])

# 4) Quien los usa desde fuera.
reemplazar('routes/admin.php', [
    ('use ' + ns('App','Http','Controllers','Admin') + B, 'use ' + ns('App','Modules','Control','Controllers') + B, 8),
    ('use ' + ns('App','Http','Controllers','DemoController') + ';', 'use ' + ns('App','Modules','Control','Controllers','DemoController') + ';', 1),
    (FQ_ACCESS_V, FQ_ACCESS_N, 1),
    ("view('admin.audit.index'", "view('control::admin.audit.index'", 1),
])
reemplazar('routes/web.php', [
    ('use ' + ns('App','Http','Controllers','DemoController') + ';', 'use ' + ns('App','Modules','Control','Controllers','DemoController') + ';', 1),
    (FQ_ACCESS_V, FQ_ACCESS_N, 2),
])
reemplazar('app/Modules/Personas/Controllers/HRController.php',             [(FQ_ACCESS_V, FQ_ACCESS_N, 2)])
reemplazar('app/Modules/Personas/Controllers/RolePermissionController.php', [(FQ_ACCESS_V, FQ_ACCESS_N, 4)])
reemplazar('app/Support/ProjectOwnership.php',       [(USE_ACC_V, USE_ACC_N, 1)])
reemplazar('app/Http/Requests/Auth/LoginRequest.php', [(FQ_LIC_V + '::', FQ_LIC_N + '::', 1)])
reemplazar('app/Jobs/ExpireDemos.php', [(USE_DEMO_V, USE_DEMO_N, 1)])
reemplazar('app/Mail/DemoCreada.php',  [(USE_DEMO_V, USE_DEMO_N, 1)])
for t in ['AccessAuditTest', 'ControlNavegacionTest', 'FusionPortalesTest', 'ProjectOwnershipTest']:
    reemplazar(f'tests/Feature/{t}.php', [(USE_ACC_V, USE_ACC_N, 1)])
print('LISTO')
