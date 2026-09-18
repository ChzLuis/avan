# -*- coding: utf-8 -*-
# Monta cada captura movil en una imagen cuadrada 1080x1080: fondo de color, marco de celular y rotulo.
import os, sys
from PIL import Image, ImageDraw, ImageFont, ImageFilter

SP = sys.argv[1]
OUT = 'C:/xampp/htdocs/avan/public/uploads/eskala'
os.makedirs(OUT, exist_ok=True)
TIENDAS = {
    'ferreteria': ('Ferretería', (16, 30, 66), (255, 122, 0)),
    'ropa':       ('Ropa y bebés', (0, 121, 118), (255, 240, 230)),
    'tecnologia': ('Tecnología', (8, 20, 120), (69, 200, 255)),
    'hogar':      ('Hogar y muebles', (20, 40, 90), (201, 168, 84)),
    'electrico':  ('Eléctrico', (18, 92, 103), (230, 250, 250)),
}
def fuente(tam, negrita=True):
    for f in (['C:/Windows/Fonts/segoeuib.ttf', 'C:/Windows/Fonts/arialbd.ttf'] if negrita else ['C:/Windows/Fonts/segoeui.ttf', 'C:/Windows/Fonts/arial.ttf']):
        if os.path.exists(f):
            return ImageFont.truetype(f, tam)
    return ImageFont.load_default()

W = 1080
for clave, (rotulo, fondo, acento) in TIENDAS.items():
    src = f'{SP}/capturas/{clave}.png'
    if not os.path.exists(src):
        continue
    shot = Image.open(src).convert('RGB')
    # Fondo con degradado suave
    img = Image.new('RGB', (W, W), fondo)
    deg = Image.new('RGB', (W, W), fondo)
    d = ImageDraw.Draw(deg)
    for y in range(W):
        t = y / W
        c = tuple(int(fondo[i] * (1 - 0.25 * t)) for i in range(3))
        d.line([(0, y), (W, y)], fill=c)
    img = deg
    # Celular: pantalla 400 x 866 (proporcion 390x844), marco negro redondeado
    ph_w, ph_h = 390, 844
    pantalla = shot.resize((ph_w, ph_h), Image.LANCZOS)
    borde = 14
    marco = Image.new('RGBA', (ph_w + 2 * borde, ph_h + 2 * borde), (0, 0, 0, 0))
    md = ImageDraw.Draw(marco)
    md.rounded_rectangle([0, 0, marco.width - 1, marco.height - 1], radius=54, fill=(20, 20, 24))
    mascara = Image.new('L', (ph_w, ph_h), 0)
    ImageDraw.Draw(mascara).rounded_rectangle([0, 0, ph_w - 1, ph_h - 1], radius=40, fill=255)
    marco.paste(pantalla, (borde, borde), mascara)
    # sombra
    sombra = Image.new('RGBA', (marco.width + 80, marco.height + 80), (0, 0, 0, 0))
    ImageDraw.Draw(sombra).rounded_rectangle([40, 50, sombra.width - 40, sombra.height - 30], radius=60, fill=(0, 0, 0, 120))
    sombra = sombra.filter(ImageFilter.GaussianBlur(24))
    x = (W - marco.width) // 2
    y = 60
    img.paste(sombra, (x - 40, y - 40), sombra)
    img.paste(marco, (x, y), marco)
    # Rotulo inferior
    d = ImageDraw.Draw(img)
    f1 = fuente(44); f2 = fuente(30, False)
    t1 = f'Tienda de {rotulo.lower()}'
    t2 = 'hecha con ESKALA · pedidos por WhatsApp'
    w1 = d.textlength(t1, font=f1); w2 = d.textlength(t2, font=f2)
    d.text(((W - w1) / 2, 960), t1, font=f1, fill=(255, 255, 255))
    d.text(((W - w2) / 2, 1014), t2, font=f2, fill=acento)
    img.save(f'{OUT}/tienda-{clave}.jpg', 'JPEG', quality=86, optimize=True)
    print(clave, os.path.getsize(f'{OUT}/tienda-{clave}.jpg'))
