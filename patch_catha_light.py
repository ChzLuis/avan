filepath = '/home/user_mercado/htdocs/admin.mercadosmayoristas.com.pe/resources/views/public/templates/catha.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Cambiar variables CSS de colores oscuros a claros
old_vars = """:root {
  --gold: {{ $primaryColor }};
  --gold2: {{ $secondaryColor }};
  --navy: #0d1117;
  --navy2: #161b22;
  --navy3: #1c2430;
  --font-serif: 'Playfair Display', Georgia, serif;
  --font-sans: 'Inter', sans-serif;
}
*, body { font-family: var(--font-sans); }
.serif { font-family: var(--font-serif); }
.btn-gold { background: var(--gold); color: #0d1117; font-weight: 700; }
.btn-gold:hover { background: var(--gold2); }
.btn-outline-gold { border: 1.5px solid var(--gold); color: var(--gold); }
.btn-outline-gold:hover { background: var(--gold); color: #0d1117; }
.text-gold { color: var(--gold); }
.border-gold { border-color: var(--gold); }
.bg-gold { background: var(--gold); }
[x-cloak] { display:none!important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:40;backdrop-filter:blur(4px); }
.drawer { position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.3); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* Cards */
.lic-card { transition: transform .3s ease, box-shadow .3s ease; }
.lic-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.5); }
.lic-card .lic-overlay { opacity: 0; transition: opacity .3s; }
.lic-card:hover .lic-overlay { opacity: 1; }
.lic-card .lic-img img { transition: transform .5s ease; }
.lic-card:hover .lic-img img { transform: scale(1.06); }

/* Scrollbar oculta */
.no-scroll::-webkit-scrollbar { display: none; }
.no-scroll { scrollbar-width: none; }

/* Gradiente dorado */
.gold-gradient { background: linear-gradient(135deg, var(--gold) 0%, var(--gold2) 100%); }

/* Badge oferta rojo */
.badge-oferta { background: #dc2626; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 7px; letter-spacing: .05em; }

/* Input search dark */
.search-dark { background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12); color: #fff; }
.search-dark::placeholder { color: rgba(255,255,255,.4); }
.search-dark:focus { background: rgba(255,255,255,.1); border-color: var(--gold); outline: none; }"""

new_vars = """:root {
  --gold: {{ $primaryColor }};
  --gold2: {{ $secondaryColor }};
  --navy: #f9f6f0;
  --navy2: #ffffff;
  --navy3: #f2ede4;
  --font-serif: 'Playfair Display', Georgia, serif;
  --font-sans: 'Inter', sans-serif;
}
*, body { font-family: var(--font-sans); background: var(--navy); color: #1a1209; }
.serif { font-family: var(--font-serif); }
.btn-gold { background: var(--gold); color: #fff; font-weight: 700; }
.btn-gold:hover { background: var(--gold2); }
.btn-outline-gold { border: 1.5px solid var(--gold); color: var(--gold); }
.btn-outline-gold:hover { background: var(--gold); color: #fff; }
.text-gold { color: var(--gold); }
.border-gold { border-color: var(--gold); }
.bg-gold { background: var(--gold); }
[x-cloak] { display:none!important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:40;backdrop-filter:blur(4px); }
.drawer { position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.15); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* Cards */
.lic-card { transition: transform .3s ease, box-shadow .3s ease; background:#fff; }
.lic-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.12); }
.lic-card .lic-overlay { opacity: 0; transition: opacity .3s; }
.lic-card:hover .lic-overlay { opacity: 1; }
.lic-card .lic-img img { transition: transform .5s ease; }
.lic-card:hover .lic-img img { transform: scale(1.06); }

/* Scrollbar oculta */
.no-scroll::-webkit-scrollbar { display: none; }
.no-scroll { scrollbar-width: none; }

/* Gradiente dorado */
.gold-gradient { background: linear-gradient(135deg, var(--gold) 0%, var(--gold2) 100%); }

/* Badge oferta rojo */
.badge-oferta { background: #dc2626; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 7px; letter-spacing: .05em; }

/* Input search claro */
.search-dark { background: #f5f0e8; border: 1px solid #e0d5c0; color: #1a1209; }
.search-dark::placeholder { color: #a89070; }
.search-dark:focus { background: #fff; border-color: var(--gold); outline: none; }"""

# 2. Cambiar body dark a body light
old_body = """<body class="text-gray-100" style="background:var(--navy);" x-data="store()" x-cloak>"""
new_body = """<body class="text-gray-800" style="background:var(--navy);" x-data="store()" x-cloak>"""

# 3. Cambiar topbar oscuro a claro
old_topbar = """<div style="background:#080c10;border-bottom:1px solid rgba(201,168,76,.1);" class="text-xs py-2">"""
new_topbar = """<div style="background:#1a1209;border-bottom:1px solid rgba(201,168,76,.2);" class="text-xs py-2">"""

# 4. Cambiar header oscuro a claro
old_header_bg = """<header style="background:var(--navy2);border-bottom:1px solid rgba(201,168,76,.15);" class="sticky top-0 z-30">"""
new_header_bg = """<header style="background:#ffffff;border-bottom:1px solid rgba(201,168,76,.25);box-shadow:0 2px 20px rgba(0,0,0,.06);" class="sticky top-0 z-30">"""

# 5. Cambiar nav border oscuro a claro
old_nav_border = """<nav style="border-top:1px solid rgba(201,168,76,.08);" class="hidden lg:block">"""
new_nav_border = """<nav style="border-top:1px solid rgba(201,168,76,.2);background:#faf7f0;" class="hidden lg:block">"""

old_nav_border2 = """<div class="lg:hidden flex gap-1 px-4 pb-2 pt-1 no-scroll overflow-x-auto" style="border-top:1px solid rgba(201,168,76,.08);">"""
new_nav_border2 = """<div class="lg:hidden flex gap-1 px-4 pb-2 pt-1 no-scroll overflow-x-auto" style="border-top:1px solid rgba(201,168,76,.2);background:#faf7f0;">"""

# 6. Botones nav con texto oscuro en claro
old_nav_btn = """              :class="filterCat==='' ? 'text-gold border-b-2 border-gold' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent'"
              class="px-5 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap transition">Todo</button>"""
new_nav_btn = """              :class="filterCat==='' ? 'text-gold border-b-2 border-gold' : 'text-gray-500 hover:text-gray-800 border-b-2 border-transparent'"
              class="px-5 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap transition">Todo</button>"""

old_nav_cat = """              :class="filterCat==='{{ $cat->id }}' ? 'text-gold border-b-2 border-gold' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent'"
              class="px-5 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap transition">{{ $cat->name }}</button>"""
new_nav_cat = """              :class="filterCat==='{{ $cat->id }}' ? 'text-gold border-b-2 border-gold' : 'text-gray-500 hover:text-gray-800 border-b-2 border-transparent'"
              class="px-5 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap transition">{{ $cat->name }}</button>"""

# 7. Logo tagline color
old_tagline = """<span class="text-[9px] tracking-[.35em] uppercase text-gray-500">{{ $settings['footer_tagline'] ?? 'Calidad y Tradicion' }}</span>"""
new_tagline = """<span class="text-[9px] tracking-[.35em] uppercase text-gray-400">{{ $settings['footer_tagline'] ?? 'Calidad y Tradicion' }}</span>"""

# 8. Search suggestions dropdown en claro
old_dropdown = """             style="background:var(--navy2);border:1px solid rgba(201,168,76,.2);"
             class="absolute right-0 top-full mt-1 rounded-xl shadow-2xl z-[200] overflow-hidden min-w-[280px]">"""
new_dropdown = """             style="background:#ffffff;border:1px solid rgba(201,168,76,.3);box-shadow:0 8px 30px rgba(0,0,0,.12);"
             class="absolute right-0 top-full mt-1 rounded-xl shadow-2xl z-[200] overflow-hidden min-w-[280px]">"""

old_sug_btn = """                    class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-white/5 transition text-left border-b border-white/5 last:border-0">"""
new_sug_btn = """                    class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-amber-50 transition text-left border-b border-gray-100 last:border-0">"""

old_sug_name = """                <p class="text-sm font-semibold text-gray-200 truncate" x-html="_highlight(p.name)"></p>"""
new_sug_name = """                <p class="text-sm font-semibold text-gray-800 truncate" x-html="_highlight(p.name)"></p>"""

old_sug_cat = """                <p class="text-xs text-gray-500" x-text="p.cat"></p>"""
new_sug_cat = """                <p class="text-xs text-gray-400" x-text="p.cat"></p>"""

# 9. Carrito icono color
old_cart_icon = """        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">"""
new_cart_icon = """        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">"""

# 10. Footer claro
old_footer = """<footer style="background:#080c10;border-top:1px solid rgba(201,168,76,.15);">"""
new_footer = """<footer style="background:#1a1209;border-top:1px solid rgba(201,168,76,.2);">"""

# 11. Hero: cambiar fondo oscuro a crema oscuro con overlay mas suave
old_hero = """  <div class="absolute inset-0" style="background:linear-gradient(135deg,#080c10 0%,#1a1000 50%,#080c10 100%);"></div>"""
new_hero = """  <div class="absolute inset-0" style="background:linear-gradient(135deg,#1a1209 0%,#2d1f00 50%,#1a1209 100%);"></div>"""

replacements = [
    (old_vars, new_vars),
    (old_body, new_body),
    (old_topbar, new_topbar),
    (old_header_bg, new_header_bg),
    (old_nav_border, new_nav_border),
    (old_nav_border2, new_nav_border2),
    (old_nav_btn, new_nav_btn),
    (old_nav_cat, new_nav_cat),
    (old_dropdown, new_dropdown),
    (old_sug_btn, new_sug_btn),
    (old_sug_name, new_sug_name),
    (old_sug_cat, new_sug_cat),
    (old_cart_icon, new_cart_icon),
    (old_footer, new_footer),
    (old_hero, new_hero),
]

count = 0
for old, new in replacements:
    if old in content:
        content = content.replace(old, new, 1)
        count += 1
    else:
        print(f'NO ENCONTRADO: {old[:60]}...')

# Cambiar fondo del body de navy (ahora crema) y texto gris oscuro
# Cambiar todas las clases text-gray-100/200/300/400 en el hero a mas claras (las del hero se quedan)
# Cambiar el catalog section background
content = content.replace(
    'id="catalogo" style="background:var(--navy)"',
    'id="catalogo" style="background:#f9f6f0"'
)
content = content.replace(
    'id="catalogo" style="background: var(--navy)"',
    'id="catalogo" style="background:#f9f6f0"'
)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print(f'OK - {count} reemplazos realizados')
