# -*- coding: utf-8 -*-
# Mueve el modulo Personas a app/Modules/Personas y actualiza las referencias.
# Cada reemplazo exige un numero exacto de apariciones: si no coincide, se
# detiene ANTES de escribir ese archivo, para no dejar el arbol a medias.
import io, os, subprocess, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
M = 'app/Modules/Personas'
B = chr(92)  # barra invertida, para no escribirla literal en este archivo

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
        if c != n:
            print(f'ERROR {path}: esperaba {n} de {viejo!r}, hay {c}'); sys.exit(1)
        s = s.replace(viejo, nuevo)
    io.open(p, 'w', encoding='utf-8', newline='').write(s)
    print('ok ', path, f'({len(cambios)} cambios)')

NS_CTRL_VIEJO = 'namespace ' + ns('App','Http','Controllers') + ';'
NS_CTRL_NUEVO = ('namespace ' + ns('App','Modules','Personas','Controllers') + ';'
                 + '\n\nuse ' + ns('App','Http','Controllers','Controller') + ';')
NS_MOD_VIEJO  = 'namespace ' + ns('App','Models') + ';'
NS_MOD_NUEVO  = 'namespace ' + ns('App','Modules','Personas','Models') + ';'
USE_MODELS    = 'use ' + ns('App','Models')
USE_PMODELS   = 'use ' + ns('App','Modules','Personas','Models')

# 1) Mover archivos conservando historial.
for c in ['HRController', 'AttendanceController', 'UserGroupController', 'RolePermissionController']:
    git_mv(f'app/Http/Controllers/{c}.php', f'{M}/Controllers/{c}.php')
for m in ['Attendance', 'WorkSchedule', 'UserGroup']:
    git_mv(f'app/Models/{m}.php', f'{M}/Models/{m}.php')
git_mv('resources/views/hr',    f'{M}/Views/hr')
git_mv('resources/views/roles', f'{M}/Views/roles')

# 2) Controladores: namespace + base Controller + modelos movidos + vistas.
reemplazar(f'{M}/Controllers/HRController.php', [
    (NS_CTRL_VIEJO, NS_CTRL_NUEVO, 1),
    ("view('hr.employees'", "view('personas::hr.employees'", 1),
])
reemplazar(f'{M}/Controllers/AttendanceController.php', [
    (NS_CTRL_VIEJO, NS_CTRL_NUEVO, 1),
    (USE_MODELS + ns('','Attendance') + ';', USE_PMODELS + ns('','Attendance') + ';', 1),
    ("view('hr.attendance'", "view('personas::hr.attendance'", 1),
    ("view('hr.comisiones'", "view('personas::hr.comisiones'", 1),
])
reemplazar(f'{M}/Controllers/UserGroupController.php', [
    (NS_CTRL_VIEJO, NS_CTRL_NUEVO, 1),
    (USE_MODELS + ns('','UserGroup') + ';', USE_PMODELS + ns('','UserGroup') + ';', 1),
])
reemplazar(f'{M}/Controllers/RolePermissionController.php', [
    (NS_CTRL_VIEJO, NS_CTRL_NUEVO, 1),
    ("view('roles.index'", "view('personas::roles.index'", 1),
])

# 3) Modelos: namespace + imports de los modelos que se quedan en Core.
reemplazar(f'{M}/Models/Attendance.php', [
    (NS_MOD_VIEJO, NS_MOD_NUEVO + '\n\n' + USE_MODELS + ns('','Employee') + ';\n' + USE_MODELS + ns('','Project') + ';', 1),
])
reemplazar(f'{M}/Models/WorkSchedule.php', [
    (NS_MOD_VIEJO, NS_MOD_NUEVO + '\n\n' + USE_MODELS + ns('','Employee') + ';', 1),
])
reemplazar(f'{M}/Models/UserGroup.php', [
    (NS_MOD_VIEJO, NS_MOD_NUEVO + '\n' + USE_MODELS + ns('','Project') + ';', 1),
])

# 4) Quien los usa desde fuera.
reemplazar('routes/web.php', [
    ('use ' + ns('App','Http','Controllers','RolePermissionController') + ';', 'use ' + ns('App','Modules','Personas','Controllers','RolePermissionController') + ';', 1),
    ('use ' + ns('App','Http','Controllers','HRController') + ';',             'use ' + ns('App','Modules','Personas','Controllers','HRController') + ';', 1),
    ('use ' + ns('App','Http','Controllers','UserGroupController') + ';',      'use ' + ns('App','Modules','Personas','Controllers','UserGroupController') + ';', 1),
    (ns('','App','Http','Controllers','AttendanceController') + '::class',     ns('','App','Modules','Personas','Controllers','AttendanceController') + '::class', 5),
])
reemplazar('app/Http/Controllers/Admin/AdminTurnosController.php', [
    (USE_MODELS + ns('','WorkSchedule') + ';', USE_PMODELS + ns('','WorkSchedule') + ';', 1),
])
reemplazar('app/Models/Employee.php', [
    (NS_MOD_VIEJO, NS_MOD_VIEJO + '\n\n' + USE_PMODELS + ns('','Attendance') + ';\n' + USE_PMODELS + ns('','WorkSchedule') + ';', 1),
])
reemplazar('app/Models/Project.php', [
    (NS_MOD_VIEJO, NS_MOD_VIEJO + '\n\n' + USE_PMODELS + ns('','UserGroup') + ';', 1),
])

# 5) Provider de modulos.
reemplazar('bootstrap/providers.php', [
    ('    CatalogServiceProvider::class,\n];', '    CatalogServiceProvider::class,\n    ' + ns('App','Modules','ModulosServiceProvider') + '::class,\n];', 1),
])
print('LISTO')
