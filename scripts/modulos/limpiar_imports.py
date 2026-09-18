# -*- coding: utf-8 -*-
# Quita los `use X\Y;` que el generador inserto en archivos movidos cuando la
# clase solo se usa por FQCN (\App\...\Y::) y no por nombre corto.
import io, os, re, sys, json
os.chdir('C:/xampp/htdocs/avan')
B = chr(92)
plan = json.load(io.open(sys.argv[1], encoding='utf-8'))
for p, usos in plan['inserts'].items():
    if not os.path.exists(p): continue
    s = io.open(p, encoding='utf-8', newline='').read()
    for u in usos:
        corto = u[4:-1].split(B)[-1]
        m = re.search(r'^' + re.escape(u) + r'\r?\n', s, re.M)
        if not m: continue
        resto = s[:m.start()] + s[m.end():]
        usos_cortos = len(re.findall(r'(?<![A-Za-z0-9_' + re.escape(B) + r'])' + corto + r'(?![A-Za-z0-9_])', resto))
        if usos_cortos == 0:
            s = resto
            print(f'{p}: quitado use {corto} (solo FQCN)')
    io.open(p, 'w', encoding='utf-8', newline='').write(s)
print('fin')
