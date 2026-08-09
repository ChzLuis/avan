filepath = '/home/user_mercado/htdocs/admin.mercadosmayoristas.com.pe/resources/views/public/templates/catha.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Textos claros -> oscuros (fuera del hero/toast/drawer que ya tienen fondo oscuro)
# Hacemos reemplazos globales de clases Tailwind de texto claro a texto oscuro

replacements = [
    # Textos principales
    ('text-gray-100', 'text-gray-800'),
    ('text-gray-200', 'text-gray-700'),
    # gray-300 solo en iconos y secundarios -> gris medio
    ('text-gray-300', 'text-gray-500'),
    # gray-400 -> gris legible
    # ('text-gray-400', 'text-gray-500'),  # ya es legible

    # Bordes blancos -> grises
    ('border-white/5', 'border-gray-100'),
    ('border-white/10', 'border-gray-200'),

    # Hover bg blancos -> grises claros
    ('hover:bg-white/10', 'hover:bg-gray-100'),
    ('hover:bg-white/5', 'hover:bg-amber-50'),
    ('bg-white/5', 'bg-gray-50'),
    ('bg-white/10', 'bg-gray-100'),

    # Section de catalogos - fondo de seccion de categorias y filtros
    ('style="background:var(--navy3)"', 'style="background:#f2ede4"'),
    ('style="background: var(--navy3)"', 'style="background:#f2ede4"'),

    # Announcement bar (si existe fondo oscuro)
    # Floating bar bottom
    ('style="background:var(--navy2);border-top:1px solid rgba(184,151,58,.2);"',
     'style="background:#ffffff;border-top:1px solid rgba(201,168,76,.25);box-shadow:0 -2px 20px rgba(0,0,0,.08);"'),

    # Toast - dejar dorado (ya se ve bien)

    # Dropdown busqueda fondo
    ('style="background:var(--navy2);border:1px solid rgba(184,151,58,.2);"',
     'style="background:#ffffff;border:1px solid rgba(201,168,76,.3);box-shadow:0 8px 30px rgba(0,0,0,.12);"'),
]

count = 0
for old, new in replacements:
    n = content.count(old)
    if n > 0:
        content = content.replace(old, new)
        print(f'  {n}x: {old[:50]}')
        count += n

# Restaurar los textos claros DENTRO del hero (tiene fondo oscuro)
# El hero empieza con {{-- ═══ HERO ═══ --}} y termina con </section>
hero_start = content.find('{{-- ═══ HERO ═══ --}}')
hero_end = content.find('</section>', hero_start) + len('</section>')

if hero_start != -1:
    hero_section = content[hero_start:hero_end]
    # Revertir los cambios de texto dentro del hero
    hero_section = hero_section.replace('text-gray-800', 'text-white')
    hero_section = hero_section.replace('text-gray-700', 'text-gray-100')
    hero_section = hero_section.replace('text-gray-500', 'text-gray-300')
    content = content[:hero_start] + hero_section + content[hero_end:]
    print('  Hero revertido a colores claros OK')

# Restaurar textos claros en el TOAST (fondo dorado)
toast_start = content.find('{{-- TOAST --}}')
toast_end = content.find('</div>', toast_start) + len('</div>')
# El toast ya tiene clase text-gray-900 asi que no hay problema

# Restaurar textos claros en el FOOTER (fondo oscuro #1a1209)
footer_start = content.find('{{-- FOOTER CATHA --}}')
footer_end = content.find('</footer>', footer_start) + len('</footer>')
if footer_start != -1:
    footer_section = content[footer_start:footer_end]
    footer_section = footer_section.replace('class="text-gray-800', 'class="text-gray-100')
    footer_section = footer_section.replace('class="text-gray-700', 'class="text-gray-300')
    footer_section = footer_section.replace('class="text-gray-500', 'class="text-gray-400')
    footer_section = footer_section.replace('"text-gray-800 ', '"text-white ')
    footer_section = footer_section.replace('"text-gray-700 ', '"text-gray-200 ')
    # Bordes en footer
    footer_section = footer_section.replace('border-gray-100', 'border-white/10')
    footer_section = footer_section.replace('border-gray-200', 'border-white/10')
    footer_section = footer_section.replace('hover:bg-gray-100', 'hover:bg-white/10')
    footer_section = footer_section.replace('bg-gray-50', 'bg-white/5')
    content = content[:footer_start] + footer_section + content[footer_end:]
    print('  Footer restaurado a colores claros OK')

# Topbar (fondo oscuro #1a1209) - restaurar
topbar_start = content.find('{{-- TOP BAR CATHA --}}')
topbar_end = content.find('</div>', topbar_start) + len('</div>')
if topbar_start != -1:
    topbar = content[topbar_start:topbar_end]
    topbar = topbar.replace('text-gray-800', 'text-gray-100')
    topbar = topbar.replace('text-gray-700', 'text-gray-300')
    topbar = topbar.replace('class="text-gray-500 ', 'class="text-gray-400 ')
    content = content[:topbar_start] + topbar + content[topbar_end:]
    print('  Topbar restaurado OK')

# DRAWER (fondo blanco - textos oscuros estan bien, no tocar)

# Floating bar bottom -> fondo blanco, textos oscuros ya ok

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print(f'\nTotal {count} reemplazos + ajustes de secciones OK')
