<script>
/* Firma al pie del material. Va en una constante porque aparece en las
   cinco composiciones y debe decir lo mismo en todas. */
const FIRMA = 'Tecnología de Eskala Group';

/* ============================================================================
   Composiciones del material promocional.

   Cada plantilla es una COMPOSICION distinta, no un cambio de color: cambia
   donde va el logo, la jerarquia del titulo, si la tarjeta del codigo monta
   sobre la banda, como se ordenan los beneficios y como cierra la pieza.

   Todas reciben el mismo ayudante de dibujo (g) y los mismos datos (d), asi
   que anadir una plantilla nueva es escribir una funcion mas aqui.
   ========================================================================== */

function QXLienzo(ctx, W, H) {
  return {
    ctx, W, H,
    fuente(peso, px, fam, italic) {
      ctx.font = `${italic ? 'italic ' : ''}${peso} ${Math.round(px)}px ${fam || '"Inter", system-ui, -apple-system, sans-serif'}`;
    },
    relleno(c) { ctx.fillStyle = c; },
    alfa(v) { ctx.globalAlpha = v; },

    caja(x, y, w, h, r, color, sombra) {
      ctx.save(); ctx.fillStyle = color;
      if (sombra) { ctx.shadowColor = 'rgba(15,23,42,.16)'; ctx.shadowBlur = sombra; ctx.shadowOffsetY = sombra*.3; }
      ctx.beginPath();
      if (ctx.roundRect) ctx.roundRect(x, y, w, h, r); else ctx.rect(x, y, w, h);
      ctx.fill(); ctx.restore();
    },
    degradado(x0,y0,x1,y1,c1,c2){ const g=ctx.createLinearGradient(x0,y0,x1,y1); g.addColorStop(0,c1); g.addColorStop(1,c2); return g; },

    texto(t, cx, y, maxW, alto, maxLineas) {
      const pal = String(t||'').split(/\s+/); let l='', filas=[];
      pal.forEach(p => { const pr = l ? l+' '+p : p;
        if (ctx.measureText(pr).width > maxW && l) { filas.push(l); l = p; } else l = pr; });
      if (l) filas.push(l);
      filas = filas.slice(0, maxLineas || 3);
      filas.forEach((f,i) => ctx.fillText(f, cx, y + i*alto));
      return y + filas.length*alto;
    },

    /* Cuantas lineas ocupara un texto con la fuente activa. Permite calcular
       la altura de una banda ANTES de pintarla. */
    medir(t, maxW, maxLineas) {
      const pal = String(t||'').split(/\s+/); let l='', filas=[];
      pal.forEach(p => { const pr = l ? l+' '+p : p;
        if (ctx.measureText(pr).width > maxW && l) { filas.push(l); l = p; } else l = pr; });
      if (l) filas.push(l);
      return Math.min(filas.length, maxLineas || 3);
    },

    logo(img, cx, y, aMax, hMax, pastilla) {
      if (!img) return y;
      const r = Math.min(aMax/img.width, hMax/img.height);
      const w = img.width*r, h = img.height*r, x = cx - w/2;
      let alto = h;
      if (pastilla) { const p = h*.20; this.caja(x-p, y-p, w+p*2, h+p*2, h*.26, '#ffffff'); alto += p; }
      ctx.drawImage(img, x, y, w, h);
      return y + alto;
    },

    /* Logo en disco blanco con sombra: asi va en la referencia, montado sobre
       la banda y con aro del color de la marca. */
    logoDisco(img, cx, cy, radio, aro) {
      ctx.save();
      ctx.shadowColor = 'rgba(15,23,42,.20)'; ctx.shadowBlur = radio*.5; ctx.shadowOffsetY = radio*.14;
      ctx.fillStyle = '#ffffff'; ctx.beginPath(); ctx.arc(cx, cy, radio, 0, Math.PI*2); ctx.fill();
      ctx.restore();
      if (aro) { ctx.save(); ctx.strokeStyle = aro; ctx.lineWidth = radio*.045; ctx.globalAlpha = .55;
        ctx.beginPath(); ctx.arc(cx, cy, radio*.94, 0, Math.PI*2); ctx.stroke(); ctx.restore(); }
      if (img) {
        ctx.save(); ctx.beginPath(); ctx.arc(cx, cy, radio*.86, 0, Math.PI*2); ctx.clip();
        const r = Math.min(radio*1.62/img.width, radio*1.62/img.height);
        ctx.drawImage(img, cx - img.width*r/2, cy - img.height*r/2, img.width*r, img.height*r);
        ctx.restore();
      } else if (this._iniciales) {
        ctx.save(); ctx.fillStyle = aro || '#334155'; ctx.textAlign = 'center';
        ctx.font = `800 ${Math.round(radio*.9)}px "Inter", system-ui, sans-serif`;
        ctx.fillText(this._iniciales, cx, cy + radio*.32); ctx.restore();
      }
      return cy + radio;
    },

    /* Un logo cuadrado luce en disco; uno apaisado (los que llevan el nombre
       al lado del simbolo) se estrangula dentro del circulo, asi que va en
       pastilla. La forma se elige por la proporcion real de la imagen. */
    logoAuto(img, cx, y, radio, aro) {
      if (img && (img.width / img.height) >= 1.45) {
        return this.logo(img, cx, y + radio*.42, radio*2.7, radio*1.16, true) + radio*.42;
      }
      this.logoDisco(img, cx, y + radio, radio, aro);
      return y + radio*2;
    },

    codigo(img, cx, y, lado, fondo, radio, sombra, margen) {
      const x = cx - lado/2, p = margen != null ? margen : lado*.075;
      this.caja(x-p, y-p, lado+p*2, lado+p*2, radio!=null?radio:lado*.07, fondo||'#ffffff', sombra);
      ctx.drawImage(img, x, y, lado, lado);
      return y + lado + p;
    },

    /* Escuadras en las esquinas del codigo: detalle de la referencia, va por
       fuera del area util para no tapar ningun modulo. */
    escuadras(cx, y, lado, color, grosor) {
      const x = cx - lado/2, m = lado*.055, L = lado*.13, gr = grosor || lado*.022;
      ctx.save(); ctx.strokeStyle = color; ctx.lineWidth = gr; ctx.lineCap = 'round'; ctx.globalAlpha = .75;
      const esq = [[x-m, y-m, 1, 1], [x+lado+m, y-m, -1, 1], [x-m, y+lado+m, 1, -1], [x+lado+m, y+lado+m, -1, -1]];
      esq.forEach(([ex, ey, sx, sy]) => {
        ctx.beginPath(); ctx.moveTo(ex, ey + sy*L); ctx.lineTo(ex, ey + sy*gr*.5);
        ctx.arcTo(ex, ey, ex + sx*gr*.5, ey, gr); ctx.lineTo(ex + sx*L, ey); ctx.stroke();
      });
      ctx.restore();
    },

    /* Marca dentro del codigo, en disco con aro. ecc=H lo tolera. */
    logoEnCodigo(img, cx, cy, radio, aro, fondo) {
      ctx.save();
      ctx.fillStyle = fondo || '#ffffff'; ctx.beginPath(); ctx.arc(cx, cy, radio, 0, Math.PI*2); ctx.fill();
      ctx.strokeStyle = aro; ctx.lineWidth = radio*.10; ctx.beginPath(); ctx.arc(cx, cy, radio*.93, 0, Math.PI*2); ctx.stroke();
      ctx.restore();
      if (img) {
        ctx.save(); ctx.beginPath(); ctx.arc(cx, cy, radio*.78, 0, Math.PI*2); ctx.clip();
        const r = Math.min(radio*1.5/img.width, radio*1.5/img.height);
        ctx.drawImage(img, cx - img.width*r/2, cy - img.height*r/2, img.width*r, img.height*r);
        ctx.restore();
      }
    },

    /* Icono de beneficio: disco tenue + simbolo simple dibujado a mano */
    iconoBeneficio(tipo, cx, cy, r, color, fondo) {
      ctx.save();
      ctx.fillStyle = fondo; ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI*2); ctx.fill();
      ctx.strokeStyle = color; ctx.lineWidth = r*.13; ctx.lineCap='round'; ctx.lineJoin='round'; ctx.fillStyle = color;
      const s = r*.52;
      if (tipo === 'bolsa') {
        ctx.beginPath(); ctx.moveTo(cx-s*.75, cy-s*.35); ctx.lineTo(cx-s*.6, cy+s*.85);
        ctx.lineTo(cx+s*.6, cy+s*.85); ctx.lineTo(cx+s*.75, cy-s*.35); ctx.closePath(); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx, cy-s*.35, s*.42, Math.PI, 0); ctx.stroke();
      } else if (tipo === 'corazon') {
        ctx.beginPath();
        ctx.moveTo(cx, cy+s*.7);
        ctx.bezierCurveTo(cx-s*1.25, cy-s*.15, cx-s*.5, cy-s*.95, cx, cy-s*.3);
        ctx.bezierCurveTo(cx+s*.5, cy-s*.95, cx+s*1.25, cy-s*.15, cx, cy+s*.7);
        ctx.stroke();
      } else if (tipo === 'oferta') {
        ctx.beginPath(); ctx.arc(cx-s*.36, cy-s*.36, s*.24, 0, Math.PI*2); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx+s*.36, cy+s*.36, s*.24, 0, Math.PI*2); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(cx+s*.72, cy-s*.72); ctx.lineTo(cx-s*.72, cy+s*.72); ctx.stroke();
      } else if (tipo === 'envio') {
        ctx.beginPath(); ctx.rect(cx-s*.9, cy-s*.5, s*1.1, s*.85); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(cx+s*.2, cy-s*.15); ctx.lineTo(cx+s*.62, cy-s*.15);
        ctx.lineTo(cx+s*.9, cy+s*.12); ctx.lineTo(cx+s*.9, cy+s*.35); ctx.lineTo(cx+s*.2, cy+s*.35); ctx.closePath(); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx-s*.45, cy+s*.5, s*.19, 0, Math.PI*2); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx+s*.55, cy+s*.5, s*.19, 0, Math.PI*2); ctx.stroke();
      } else { // marca de verificacion
        ctx.beginPath(); ctx.moveTo(cx-s*.55, cy); ctx.lineTo(cx-s*.12, cy+s*.45); ctx.lineTo(cx+s*.6, cy-s*.45); ctx.stroke();
      }
      ctx.restore();
    },

    /* Tira de marcas que trabaja el negocio: pastillas perfiladas, centradas
       y repartidas en hasta dos filas segun quepan. Devuelve el alto usado. */
    marcasAlto(marcas, maxW, alto) {
      return this.marcasFilas(marcas, maxW, alto).length * (alto * 1.42);
    },
    marcasFilas(marcas, maxW, alto) {
      this.fuente(700, alto*.46);
      const filas = []; let fila = [], ancho = 0;
      marcas.forEach(m => {
        const w = ctx.measureText(m.toUpperCase()).width + alto*1.15;
        if (fila.length && ancho + w + alto*.34 > maxW) { filas.push(fila); fila = []; ancho = 0; }
        fila.push({ t: m.toUpperCase(), w }); ancho += w + alto*.34;
      });
      if (fila.length) filas.push(fila);
      // Dos filas se reparten a partes iguales: llenando de corrido salian
      // repartos feos como 4 + 1.
      if (filas.length === 2) {
        const todas = filas.flat(), corte = Math.ceil(todas.length / 2);
        const a = todas.slice(0, corte), b = todas.slice(corte);
        const cabe = f => f.reduce((t, p) => t + p.w, 0) + alto*.34*(f.length-1) <= maxW;
        if (cabe(a) && cabe(b)) return [a, b];
      }
      return filas.slice(0, 2);
    },
    marcas(lista, cx, y, maxW, alto, color, texto) {
      const filas = this.marcasFilas(lista, maxW, alto);
      ctx.save(); ctx.textAlign = 'center';
      filas.forEach((fila, i) => {
        const total = fila.reduce((a, p) => a + p.w, 0) + alto*.34*(fila.length-1);
        let x = cx - total/2;
        const fy = y + i*(alto*1.42);
        fila.forEach(p => {
          ctx.globalAlpha = .30; ctx.strokeStyle = color; ctx.lineWidth = Math.max(1, alto*.045);
          ctx.beginPath();
          if (ctx.roundRect) ctx.roundRect(x, fy, p.w, alto, alto/2); else ctx.rect(x, fy, p.w, alto);
          ctx.stroke();
          ctx.globalAlpha = .78; ctx.fillStyle = texto || color;
          this.fuente(700, alto*.46);
          ctx.fillText(p.t, x + p.w/2, fy + alto*.63);
          x += p.w + alto*.34;
        });
      });
      ctx.restore();
      return y + filas.length*(alto*1.42);
    },

    /* Pastilla con la direccion, como en la referencia */
    pastillaUrl(texto, cx, y, alto, color, textoColor, W) {
      ctx.save();
      this.fuente(700, alto*.42);
      const anchoTexto = ctx.measureText(texto).width;
      const w = Math.min(W*.86, anchoTexto + alto*2.4);
      this.caja(cx - w/2, y, w, alto, alto/2, color);
      // globo
      const gx = cx - w/2 + alto*.72, gy = y + alto/2, r = alto*.26;
      ctx.strokeStyle = textoColor; ctx.lineWidth = alto*.055; ctx.globalAlpha = .95;
      ctx.beginPath(); ctx.arc(gx, gy, r, 0, Math.PI*2); ctx.stroke();
      ctx.beginPath(); ctx.ellipse(gx, gy, r*.45, r, 0, 0, Math.PI*2); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(gx-r, gy); ctx.lineTo(gx+r, gy); ctx.stroke();
      ctx.globalAlpha = 1;
      ctx.fillStyle = textoColor; ctx.textAlign = 'center';
      ctx.fillText(texto, cx + alto*.36, y + alto*.63);
      ctx.restore();
      return y + alto;
    },

    /* Trama muy tenue para que la banda no sea un rectangulo plano */
    trama(x, y, w, h, color, simbolos) {
      ctx.save(); ctx.globalAlpha = .13; ctx.strokeStyle = color; ctx.fillStyle = color;
      const lista = (simbolos && simbolos.length) ? simbolos : ['check'];
      const cols = 5, filas = 3;
      for (let i = 0; i < cols*filas; i++) {
        const cx = x + (i % cols)*(w/cols) + (w/cols)*.5 + ((i%2) ? w*.03 : -w*.02);
        const cy = y + Math.floor(i/cols)*(h/filas) + (h/filas)*.5;
        const r = (w/cols)*(.20 + ((i*7)%3)*.05);
        ctx.save(); ctx.translate(cx, cy); ctx.rotate(((i*37)%24 - 12) * Math.PI/180); ctx.translate(-cx, -cy);
        this.simbolo(lista[i % lista.length], cx, cy, r, color);
        ctx.restore();
      }
      ctx.restore();
    },

    /* Dibuja solo el trazo del simbolo, sin disco: sirve para la trama */
    simbolo(tipo, cx, cy, r, color) {
      const s = r*.9;
      ctx.strokeStyle = color; ctx.lineWidth = r*.16; ctx.lineCap='round'; ctx.lineJoin='round';
      if (tipo === 'bolsa') {
        ctx.beginPath(); ctx.moveTo(cx-s*.6, cy-s*.28); ctx.lineTo(cx-s*.48, cy+s*.68);
        ctx.lineTo(cx+s*.48, cy+s*.68); ctx.lineTo(cx+s*.6, cy-s*.28); ctx.closePath(); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx, cy-s*.28, s*.34, Math.PI, 0); ctx.stroke();
      } else if (tipo === 'corazon') {
        ctx.beginPath(); ctx.moveTo(cx, cy+s*.55);
        ctx.bezierCurveTo(cx-s*1.0, cy-s*.12, cx-s*.4, cy-s*.76, cx, cy-s*.24);
        ctx.bezierCurveTo(cx+s*.4, cy-s*.76, cx+s*1.0, cy-s*.12, cx, cy+s*.55); ctx.stroke();
      } else if (tipo === 'oferta') {
        ctx.beginPath(); ctx.arc(cx-s*.3, cy-s*.3, s*.2, 0, Math.PI*2); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx+s*.3, cy+s*.3, s*.2, 0, Math.PI*2); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(cx+s*.6, cy-s*.6); ctx.lineTo(cx-s*.6, cy+s*.6); ctx.stroke();
      } else if (tipo === 'envio') {
        ctx.beginPath(); ctx.rect(cx-s*.72, cy-s*.4, s*.88, s*.68); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(cx+s*.16, cy-s*.12); ctx.lineTo(cx+s*.5, cy-s*.12);
        ctx.lineTo(cx+s*.72, cy+s*.1); ctx.lineTo(cx+s*.72, cy+s*.28); ctx.lineTo(cx+s*.16, cy+s*.28); ctx.closePath(); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx-s*.36, cy+s*.4, s*.15, 0, Math.PI*2); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx+s*.44, cy+s*.4, s*.15, 0, Math.PI*2); ctx.stroke();
      } else if (tipo === 'estrella') {
        ctx.beginPath();
        for (let k = 0; k < 5; k++) {
          const a1 = -Math.PI/2 + k*2*Math.PI/5, a2 = a1 + Math.PI/5;
          ctx[k?'lineTo':'moveTo'](cx + Math.cos(a1)*s*.7, cy + Math.sin(a1)*s*.7);
          ctx.lineTo(cx + Math.cos(a2)*s*.3, cy + Math.sin(a2)*s*.3);
        }
        ctx.closePath(); ctx.stroke();
      } else {
        ctx.beginPath(); ctx.moveTo(cx-s*.45, cy); ctx.lineTo(cx-s*.1, cy+s*.36); ctx.lineTo(cx+s*.5, cy-s*.36); ctx.stroke();
      }
    },

    /* Separador punteado entre beneficios */
    punteado(x, y0, y1, color) {
      ctx.save(); ctx.globalAlpha = .35; ctx.strokeStyle = color;
      ctx.lineWidth = Math.max(1, (y1-y0)*.012); ctx.setLineDash([(y1-y0)*.05, (y1-y0)*.07]);
      ctx.beginPath(); ctx.moveTo(x, y0); ctx.lineTo(x, y1); ctx.stroke();
      ctx.setLineDash([]); ctx.restore();
    },

    vineta(x, y, r, color) {
      ctx.save(); ctx.fillStyle = color; ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI*2); ctx.fill();
      ctx.strokeStyle='#fff'; ctx.lineWidth=r*.34; ctx.lineCap='round'; ctx.lineJoin='round';
      ctx.beginPath(); ctx.moveTo(x-r*.42,y); ctx.lineTo(x-r*.1,y+r*.34); ctx.lineTo(x+r*.45,y-r*.36); ctx.stroke();
      ctx.restore();
    },
  };
}

/* ========================= LAS COMPOSICIONES ========================= */
const QX_FLYERS = {

  /* 1 · MARCA — la pieza de referencia. Banda de marca con trama tenue, logo
        en disco montado, titular a dos voces (recta + cursiva), tarjeta del
        codigo invadiendo la banda, beneficios con icono y pastilla de URL.
        Debe verse terminada sin tocar nada. */
  marca: {
    label: 'Marca', formato: 'flyer',
    mini: [ ['banda',34], ['disco'], ['qr',44,'monta'], ['iconos'] ],
    dibujar(g, d) {
      const { W, H, ctx } = g;
      const M = W*.045;                       // margen exterior de la pieza
      const rPieza = W*.045;

      // Fondo de la hoja y pieza recortada con esquinas suaves
      g.relleno('#f4f7fa'); ctx.fillRect(0,0,W,H);
      ctx.save();
      ctx.beginPath();
      if (ctx.roundRect) ctx.roundRect(M, M, W-M*2, H-M*2, rPieza); else ctx.rect(M, M, W-M*2, H-M*2);
      ctx.clip();

      const px = M, py = M, pw = W-M*2, ph = H-M*2;
      g.relleno(d.fondo); ctx.fillRect(px, py, pw, ph);

      // Banda de marca (tinte claro del color, como en la referencia)
      const bandaH = ph*.345;
      ctx.fillStyle = d.degradado ? g.degradado(px,py,px+pw,py+bandaH, d.headerSuave, d.header) : d.headerSuave;
      ctx.fillRect(px, py, pw, bandaH);
      if (d.conTrama !== false) g.trama(px, py, pw, bandaH, d.tramaColor, (d.iconos||[]).concat(['estrella','corazon']));

      ctx.textAlign = 'center';

      // Logo en disco, montado sobre la banda
      const rDisco = pw*.118*d.escalaLogo;
      const cyDisco = py + ph*.042 + rDisco;
      g.logoAuto(d.logo, W/2, cyDisco - rDisco, rDisco, d.header);
      let y = cyDisco + rDisco + ph*.036;

      // Titular a dos voces: la segunda linea en cursiva y en color de marca
      g.relleno(d.tituloColor); g.fuente(800, pw*.070, '"Nunito", "Inter", sans-serif');
      ctx.fillText(g.recorta ? g.recorta(d.titulo) : d.titulo, W/2, y);
      y += pw*.074;
      if (d.subtitulo) {
        g.relleno(d.tituloAcento); g.fuente(900, pw*.086, '"Nunito", "Inter", sans-serif', true);
        ctx.fillText(d.subtitulo, W/2, y);
        y += pw*.026;
      }

      // ---- Reparto vertical: primero se reserva el cierre y el codigo se
      //      queda con lo que sobra. Apilando hacia abajo y confiando en que
      //      cupiera, la URL acababa encima de los beneficios.
      const yFirma = py + ph - ph*.030;
      const hPastilla = pw*.082;
      const altoMarca = pw*.052;
      const hMarcas = (d.marcas && d.marcas.length) ? g.marcasAlto(d.marcas, pw*.86, altoMarca) + ph*.014 : 0;
      const hBenef = d.beneficios.length ? pw*.185 : 0;
      const yPastilla = (d.mostrarUrl && d.urlCorta)
        ? yFirma - (d.mostrarBixo ? ph*.030 : 0) - hPastilla
        : yFirma - (d.mostrarBixo ? ph*.030 : 0);
      const yMarcas = yPastilla - ph*.020 - hMarcas;
      const yBenef = yMarcas - ph*.010 - hBenef;

      // El codigo ocupa desde donde acaba el titulo hasta donde empieza el cierre
      const arriba = Math.max(py + bandaH - ph*.085, y + ph*.006);
      const disponible = (hBenef ? yBenef : (hMarcas ? yMarcas : yPastilla)) - ph*.038 - arriba;
      // La tarjeta blanca sobresale del codigo por los cuatro lados, asi que
      // el bloque ocupa mas que el propio QR: si no se descuenta, los iconos
      // acaban tocandolo.
      const margenTarjeta = .10;
      const lado = Math.min(pw*.80, (disponible / (1 + margenTarjeta*2)) * (d.escalaQr || 1));
      const bloque = lado * (1 + margenTarjeta*2);
      const qy = arriba + lado*margenTarjeta + Math.max(0, (disponible - bloque)/2);

      g.codigo(d.qr, W/2, qy, lado, d.fondoQr, lado*.13, lado*.10, lado*margenTarjeta);
      if (d.conEscuadras !== false) g.escuadras(W/2, qy, lado, d.header);
      if (d.logoEnQr && d.logo) g.logoEnCodigo(d.logo, W/2, qy + lado/2, lado*.115, d.header, d.fondoQr);

      // ---- Beneficios
      if (d.beneficios.length) {
        const n = Math.min(d.beneficios.length, 4);
        const zonaW = pw*.94, x0 = px + (pw-zonaW)/2, colW = zonaW/n;
        const rIcono = Math.min(colW*.25, pw*.049);
        d.beneficios.slice(0,4).forEach((b, i) => {
          const cx = x0 + colW*i + colW/2;
          const tipoIco = d.iconos[i] || 'check';
          const colorIco = (tipoIco === 'corazon' && d.acento && d.acento !== d.header) ? d.acento : d.header;
          g.iconoBeneficio(tipoIco, cx, yBenef + rIcono, rIcono, colorIco, d.iconoFondo);
          g.relleno(d.textoCuerpo); g.fuente(700, pw*.0245);
          g.texto(b, cx, yBenef + rIcono*2 + pw*.040, colW*.96, pw*.031, 2);
          if (i < n-1) g.punteado(x0 + colW*(i+1), yBenef + rIcono*.3, yBenef + rIcono*1.7, d.header);
        });
      }

      if (hMarcas) g.marcas(d.marcas, W/2, yMarcas, pw*.86, altoMarca, d.textoCuerpo);
      if (d.mostrarUrl && d.urlCorta) g.pastillaUrl(d.urlCorta, W/2, yPastilla, hPastilla, d.header, d.textoHeader, pw);
      if (d.mostrarBixo) {
        g.relleno(d.textoCuerpo); g.alfa(.45); g.fuente(600, pw*.022);
        ctx.fillText(FIRMA, W/2, yFirma); g.alfa(1);
      }
      ctx.restore();
    },
  },

  /* 2 · PROMOCIONAL — mensaje grande arriba y franja de cierre. Mas agresiva
        comercialmente que Marca. */
  promocional: {
    label: 'Promocional', formato: 'flyer',
    mini: [ ['banda',46], ['qr',38,'monta'], ['franja'] ],
    dibujar(g, d) {
      const { W, H, ctx } = g;
      const RED = '"Nunito", "Inter", sans-serif';
      g.relleno(d.fondo); ctx.fillRect(0,0,W,H);
      ctx.textAlign = 'center';

      /* La banda se MIDE antes de pintarse. Con altura fija dejaba un hueco
         muerto entre el subtitulo y el codigo en cuanto el titulo era corto. */
      const rDisco = W*.098*d.escalaLogo;
      const hLogo  = (d.logo || g._iniciales) ? rDisco*2 + H*.022 : 0;
      g.fuente(800, W*.070, RED);
      let hTexto = W*.038 + g.medir(d.titulo, W*.86, 2)*W*.082;
      if (d.subtitulo) { g.fuente(900, W*.082, RED, true); hTexto += W*.018 + g.medir(d.subtitulo, W*.86, 1)*W*.094; }
      const finTexto = H*.042 + hLogo + hTexto + H*.006;

      /* El cierre se reserva su sitio abajo y el codigo ocupa lo que queda.
         La banda crece hasta cubrir la parte de tarjeta que monta sobre ella:
         calculada al reves, la tarjeta tapaba el subtitulo. */
      const franjaH = H*.085;
      const altoMarca = W*.050;
      const hMarcas = (d.marcas && d.marcas.length) ? g.marcasAlto(d.marcas, W*.86, altoMarca) + H*.012 : 0;
      const hBenef  = d.beneficios.length ? W*.180 : 0;
      const yMarcas = H - franjaH - H*.026 - hMarcas;
      const yBenef  = yMarcas - (hMarcas ? H*.014 : 0) - hBenef;
      const hCta    = d.cta ? W*.070 : 0;
      const yCta    = yBenef - hCta;
      const lado    = Math.max(W*.32, Math.min(W*.64, (yCta - H*.026 - finTexto) / 1.20));
      const qy      = finTexto + lado*.10;
      const bandaH  = qy + lado*.28;

      ctx.fillStyle = d.degradado ? g.degradado(0,0,W,bandaH, d.header, d.header2) : d.header;
      ctx.fillRect(0,0,W,bandaH);
      if (d.conTrama !== false) g.trama(0, 0, W, bandaH, '#ffffff', (d.iconos||[]).concat(['estrella']));

      let y = H*.042;
      if (hLogo) { g.logoAuto(d.logo, W/2, y, rDisco, d.header); y += rDisco*2 + H*.022; }
      // Titulo a dos voces, como la pieza de referencia: recta y cursiva.
      g.relleno(d.textoHeader); g.fuente(800, W*.070, RED);
      y = g.texto(d.titulo, W/2, y + W*.038, W*.86, W*.082, 2);
      if (d.subtitulo) { g.fuente(900, W*.082, RED, true); y = g.texto(d.subtitulo, W/2, y + W*.018, W*.86, W*.094, 1); }

      g.codigo(d.qr, W/2, qy, lado, d.fondoQr, lado*.09, lado*.10);
      if (d.conEscuadras !== false) g.escuadras(W/2, qy, lado, d.header);
      if (d.logoEnQr && d.logo) g.logoEnCodigo(d.logo, W/2, qy + lado/2, lado*.115, d.header, d.fondoQr);

      if (d.cta) { g.relleno(d.textoCuerpo); g.fuente(800, W*.042, RED); g.texto(d.cta, W/2, yCta + W*.030, W*.84, W*.052, 1); }

      if (d.beneficios.length) {
        const n = Math.min(d.beneficios.length,4), colW = W*.94/n, rI = Math.min(colW*.24, W*.048);
        d.beneficios.slice(0,4).forEach((b,i) => {
          const cx = W*.03 + colW*i + colW/2;
          const ico = d.iconos[i] || 'check';
          const col = (ico === 'corazon' && d.acento && d.acento !== d.header) ? d.acento : d.header;
          g.iconoBeneficio(ico, cx, yBenef + rI, rI, col, d.iconoFondo);
          g.relleno(d.textoCuerpo); g.fuente(700, W*.0245, RED);
          g.texto(b, cx, yBenef + rI*2 + W*.040, colW*.96, W*.031, 2);
        });
      }

      if (hMarcas) g.marcas(d.marcas, W/2, yMarcas, W*.86, altoMarca, d.textoCuerpo);
      ctx.fillStyle = d.header; ctx.fillRect(0, H-franjaH, W, franjaH);
      if (d.mostrarUrl && d.urlCorta) { g.relleno(d.textoHeader); g.fuente(700, W*.032); ctx.fillText(d.urlCorta, W/2, H-franjaH + franjaH*.58); }
      if (d.mostrarBixo) { g.relleno(d.textoHeader); g.alfa(.6); g.fuente(500, W*.018); ctx.fillText(FIRMA, W/2, H-H*.016); g.alfa(1); }
    },
  },

  minimal: {
    label: 'Minimal', formato: 'cuadrado',
    mini: [ ['aire'], ['qr',58], ['lineas',1] ],
    dibujar(g, d) {
      const { W, H, ctx } = g;
      g.relleno(d.fondo); ctx.fillRect(0,0,W,H);
      ctx.textAlign = 'center';
      const pieH = H*.115;                       // url y firma, al pie
      const hLogo = d.logo ? H*.07*d.escalaLogo + H*.05 : 0;
      g.fuente(600, W*.036);
      const hTit = g.medir(d.titulo, W*.72, 2)*W*.046 + H*.045;
      const lado = Math.min(W*.66, H - pieH - hLogo - hTit - H*.13);
      // El bloque se centra: fluyendo desde arriba dejaba muerto el tercio
      // inferior del lienzo.
      let y = Math.max(H*.07, (H - pieH - hLogo - hTit - lado) / 2);
      if (d.logo) y = g.logo(d.logo, W/2, y, W*.24*d.escalaLogo, H*.07*d.escalaLogo, false) + H*.05;
      g.relleno(d.textoCuerpo); g.fuente(600, W*.036);
      y = g.texto(d.titulo, W/2, y, W*.72, W*.046, 2) + H*.045;
      y = g.codigo(d.qr, W/2, y, lado, d.fondoQr, lado*.04, 0) + H*.055;
      if (d.mostrarUrl && d.urlCorta) { g.relleno(d.textoCuerpo); g.alfa(.6);
        ctx.font = `500 ${Math.round(W*.026)}px ui-monospace, Menlo, monospace`; ctx.fillText(d.urlCorta, W/2, y); g.alfa(1); }
      if (d.mostrarBixo) { g.relleno(d.textoCuerpo); g.alfa(.32); g.fuente(500, W*.019); ctx.fillText(FIRMA, W/2, H-H*.05); g.alfa(1); }
    },
  },

  /* 4 · REDES — historia vertical: titular enorme y tarjeta clara abajo. */
  redes: {
    label: 'Redes', formato: 'historia',
    mini: [ ['banda',58], ['qr',34,'abajo'] ],
    dibujar(g, d) {
      const { W, H, ctx } = g;
      ctx.fillStyle = d.degradado ? g.degradado(0,0,0,H, d.header, d.header2) : d.header;
      ctx.fillRect(0,0,W,H);
      if (d.conTrama !== false) g.trama(0, 0, W, H*.55, '#ffffff', (d.iconos||[]).concat(['estrella']));
      const RED = '"Nunito", "Inter", sans-serif';
      ctx.textAlign = 'center';

      let y = H*.075;
      const rD = W*.100*d.escalaLogo;
      if (d.logo || g._iniciales) y = g.logoAuto(d.logo, W/2, y, rD, d.header) + H*.032;
      g.relleno(d.textoHeader); g.fuente(800, W*.092, RED);
      y = g.texto(d.titulo, W/2, y, W*.86, W*.098, 3);
      // Titular a dos voces, como el resto de la familia.
      if (d.subtitulo) { g.fuente(900, W*.100, RED, true); y = g.texto(d.subtitulo, W/2, y + W*.014, W*.86, W*.110, 1); }

      /* La tarjeta se ancla al pie y el codigo llena lo que queda: con la
         tarjeta en un punto fijo sobraba medio lienzo entre ella y el titular. */
      const pieH  = d.mostrarBixo ? H*.058 : H*.022;
      const hCta  = d.cta ? W*.058 : 0;
      const hUrl  = (d.mostrarUrl && d.urlCorta) ? W*.052 : 0;
      const pad   = H*.030;
      const abajo = H - pieH;
      const lado  = Math.max(W*.40, Math.min(W*.64, (abajo - (y + H*.028) - hCta - hUrl - pad*2) / 1.10));
      const cardH = lado*1.10 + hCta + hUrl + pad*2;
      const cardY = abajo - cardH;
      g.caja(W*.06, cardY, W*.88, cardH, W*.06, d.fondo, W*.05);
      const qy = cardY + pad;
      g.codigo(d.qr, W/2, qy, lado, d.fondoQr, lado*.08, lado*.05);
      if (d.logoEnQr && d.logo) g.logoEnCodigo(d.logo, W/2, qy + lado/2, lado*.115, d.header, d.fondoQr);
      let yb = qy + lado + lado*.075 + H*.026;
      if (d.cta) { g.relleno(d.textoCuerpo); g.fuente(800, W*.042); yb = g.texto(d.cta, W/2, yb, W*.76, W*.052, 2) + H*.012; }
      if (d.mostrarUrl && d.urlCorta) { g.relleno(d.textoCuerpo); g.alfa(.72);
        ctx.font = `600 ${Math.round(W*.030)}px ui-monospace, Menlo, monospace`; ctx.fillText(d.urlCorta, W/2, yb); g.alfa(1); }
      if (d.mostrarBixo) { g.relleno(d.textoHeader); g.alfa(.5); g.fuente(500, W*.021); ctx.fillText(FIRMA, W/2, H-H*.028); g.alfa(1); }
    },
  },

  /* 5 · IMPRESION — volante formal con marco y beneficios en lista. */
  impresion: {
    label: 'Impresión', formato: 'impresion',
    mini: [ ['marco'], ['qr',38], ['lineas',3] ],
    dibujar(g, d) {
      const { W, H, ctx } = g;
      g.relleno('#ffffff'); ctx.fillRect(0,0,W,H);
      ctx.strokeStyle = d.header; ctx.lineWidth = W*.005;
      ctx.strokeRect(W*.04, H*.028, W*.92, H*.944);
      ctx.fillStyle = d.header; ctx.fillRect(W*.04, H*.028, W*.92, H*.014);
      ctx.textAlign = 'center';

      let y = H*.068;
      if (d.logo) y = g.logo(d.logo, W/2, y, W*.30*d.escalaLogo, H*.070*d.escalaLogo, false) + H*.025;
      if (d.mostrarNombre) { g.relleno(d.textoCuerpo); g.alfa(.7); g.fuente(600, W*.026); ctx.fillText(d.nombre, W/2, y); g.alfa(1); y += W*.032; }

      g.relleno(d.header); g.fuente(800, W*.048);
      y = g.texto(d.titulo, W/2, y + W*.02, W*.78, W*.058, 2);
      if (d.subtitulo) { g.relleno(d.textoCuerpo); g.alfa(.8); g.fuente(500, W*.027); y = g.texto(d.subtitulo, W/2, y + W*.016, W*.74, W*.035, 2); g.alfa(1); }

      y += H*.016; ctx.strokeStyle = '#e2e8f0'; ctx.lineWidth = W*.002;
      ctx.beginPath(); ctx.moveTo(W*.22, y); ctx.lineTo(W*.78, y); ctx.stroke();

      /* El cierre se ancla al pie del marco y el codigo ocupa el centro: antes
         todo fluia desde arriba y el tercio inferior quedaba en blanco. */
      const hLista = d.beneficios.length ? d.beneficios.length*(W*.042) + H*.016 : 0;
      const altoMarca = W*.044;
      const hMarcas = (d.marcas && d.marcas.length) ? g.marcasAlto(d.marcas, W*.76, altoMarca) + H*.010 : 0;
      const hCta   = d.cta ? W*.050 : 0;
      const hUrl   = (d.mostrarUrl && d.urlCorta) ? W*.070 + H*.015 : 0;
      const hFirma = d.mostrarBixo ? H*.030 : 0;
      const pie    = H*.972 - H*.022 - hFirma - hUrl - hMarcas - hLista - hCta;
      const lado = Math.max(W*.34, Math.min(W*.56, (pie - y - H*.056) / 1.10));
      const qy = y + H*.028;
      g.codigo(d.qr, W/2, qy, lado, d.fondoQr, lado*.03, 0);
      g.escuadras(W/2, qy, lado, d.header, lado*.018);
      if (d.logoEnQr && d.logo) g.logoEnCodigo(d.logo, W/2, qy + lado/2, lado*.11, d.header, d.fondoQr);
      y = Math.max(qy + lado + lado*.075 + H*.028, pie);

      if (d.cta) { g.relleno(d.header); g.fuente(700, W*.034); y = g.texto(d.cta, W/2, y, W*.78, W*.042, 2) + H*.016; }
      if (d.beneficios.length) {
        g.fuente(500, W*.025); ctx.textAlign='left';
        const x0 = W*.26;
        d.beneficios.forEach((b,i) => { const by = y + i*(W*.042);
          g.vineta(x0, by - W*.008, W*.012, d.header);
          g.relleno(d.textoCuerpo); ctx.fillText(b, x0 + W*.032, by); });
        ctx.textAlign='center'; y += d.beneficios.length*(W*.042) + H*.016;
      }
      if (hMarcas) { ctx.textAlign='center'; y = g.marcas(d.marcas, W/2, y, W*.76, altoMarca, d.header) + H*.010; }
      if (d.mostrarUrl && d.urlCorta) y = g.pastillaUrl(d.urlCorta, W/2, y, W*.070, d.header, '#ffffff', W) + H*.015;
      if (d.mostrarBixo) { g.relleno(d.textoCuerpo); g.alfa(.4); g.fuente(500, W*.019); ctx.fillText(FIRMA, W/2, H*.972 - H*.020); g.alfa(1); }
    },
  },
};

/* ---- Modo Codigo QR: SOLO el codigo, limpio y funcional. La pieza
        disenada es el flyer; duplicarla aqui no aportaba nada. ---- */
const QX_TARJETA_QR = function (g, d) {
  const { W, H, ctx } = g;
  g.relleno(d.fondoQr); ctx.fillRect(0,0,W,H);
  ctx.textAlign = 'center';
  const lado = Math.min(W, H) * .84;
  const qy = (H - lado) / 2 - (d.mostrarUrl && d.urlCorta ? H*.025 : 0);
  ctx.drawImage(d.qr, (W-lado)/2, qy, lado, lado);
  if (d.logoEnQr && d.logo) g.logoEnCodigo(d.logo, W/2, qy + lado/2, lado*.115, d.header, d.fondoQr);
  if (d.mostrarUrl && d.urlCorta) {
    g.relleno('#334155'); g.alfa(.85);
    ctx.font = `600 ${Math.round(W*.032)}px ui-monospace, Menlo, monospace`;
    ctx.fillText(d.urlCorta, W/2, qy + lado + H*.05); g.alfa(1);
  }
};
</script>
