<script>
/* ============================================================================
   Estudio de QR de BIXO.
   Todos los datos vienen del proyecto activo: sirve para cualquier tienda.

   Idea central: UNA sola rutina de dibujo. La vista previa y el archivo que
   se descarga salen del mismo canvas, solo cambia la escala. Antes la preview
   era HTML y la descarga se pintaba aparte, y nunca coincidian.
   ========================================================================== */
const QX = {
  url:       @json($baseUrl),
  publico:   @json($isPublicUrl),
  logo:      @json($logo),
  nombre:    @json($project->name),
  primary:   @json($primary),
  secondary: @json($secondary),
  accent:    @json($accent),
  guardar:   @json(route('settings.qr.save')),
};

const QX_FORMATOS = {
  flyer:     { label:'Flyer',     w:1080, h:1620 },
  historia:  { label:'Historia',  w:1080, h:1920 },
  post:      { label:'Post',      w:1080, h:1350 },
  cuadrado:  { label:'Cuadrado',  w:1080, h:1080 },
  impresion: { label:'Impresión', w:1748, h:2480 },  // A5 a 300 ppp
};

/* Piel de cada composicion: colores y extras con los que se presenta.
   La usan el boton de plantilla y las miniaturas del selector. */
function QX_PIEL(id) {
  const claro = (c) => { c=c.slice(1); const a=[0,2,4].map(i=>parseInt(c.slice(i,i+2),16)/255)
    .map(v=>v<=.03928?v/12.92:((v+.055)/1.055)**2.4); return (.2126*a[0]+.7152*a[1]+.0722*a[2]) > .55; };
  const cdm = claro(QX.primary);
  return {
    marca:       { header: QX.primary, tinte: 62, card:'#ffffff', body:'#1e293b', texto: cdm?'#0f172a':'#ffffff', benef: true, logoQr: true },
    promocional: { header: QX.primary, tinte: 40, card:'#ffffff', body:'#0f172a', texto: cdm?'#0f172a':'#ffffff', benef: true },
    minimal:     { header: '#111827',  tinte: 90, card:'#ffffff', body:'#111827', texto:'#111827' },
    redes:       { header: QX.primary, tinte: 30, card:'#ffffff', body:'#0f172a', texto: cdm?'#0f172a':'#ffffff' },
    impresion:   { header: QX.primary, tinte: 70, card:'#ffffff', body:'#334155', texto:'#ffffff', benef: true },
  }[id];
}

function qrStudio() {
  return {
    publicUrl: QX.publico ? QX.url : '',
    tieneLogo: !!QX.logo,
    vista: 'qr',
    estado: '',
    cargando: false,
    fallo: '',
    avisoContraste: '',
    _t: null, _tp: null, _formatoManual: false, _matriz: null, _matrizClave: '', _minisListas: false, _sucio: false, _pintando: false, _cacheQr: {}, _logoImg: undefined,

    plantillas: Object.entries(QX_FLYERS).map(([id, p]) => ({ id, label: p.label, mini: p.mini, formato: p.formato })),
    formatos: Object.entries(QX_FORMATOS).map(([id, f]) => ({ id, ...f })),

    form: {
      template:  @json($ajuste('qr_template', 'marca')),
      // Piel de la pieza: tinte de la banda, color de la tarjeta y del cuerpo.
      tinte:     +@json($ajuste('qr_tinte', 62)),
      cardColor: @json($ajuste('qr_card_color', '#ffffff')),
      bodyColor: @json($ajuste('qr_body_color', '#1e293b')),
      background:@json($ajuste('qr_background_style', 'solido')),
      icons:     (@json($ajuste('qr_icons','')) || 'bolsa|corazon|oferta|envio').split('|'),
      showTrama:    @json($ajuste('qr_show_trama', '1') === '1'),
      showBrackets: @json($ajuste('qr_show_brackets', '1') === '1'),
      qrScale:      +@json($ajuste('qr_scale', 100)),
      format:    @json($ajuste('qr_format', 'post')),
      fg:        @json($ajuste('qr_foreground', '#111827')),
      bg:        @json($ajuste('qr_background', '#ffffff')),
      header:    @json($ajuste('qr_header_color', $primary)),
      textColor: @json($ajuste('qr_text_color', '#ffffff')),
      margin:    +@json($ajuste('qr_margin', 2)),
      quality:   @json($ajuste('qr_quality', 'standard')),
      topText:   @json($ajuste('qr_top_text', 'Escanea y visita nuestra tienda')),
      subtitle:  @json($ajuste('qr_subtitle', '')),
      bottomText:@json($ajuste('qr_bottom_text', 'Escanea y mira nuestros productos')),
      showLogo:  @json($ajuste('qr_show_logo', '1') === '1'),
      logoSize:  +@json($ajuste('qr_logo_size', 110)),
      logoInQr:  @json($ajuste('qr_logo_in_qr', '1') === '1'),
      showUrl:   @json($ajuste('qr_show_url', '1') === '1'),
      showName:  @json($ajuste('qr_show_name', '0') === '1'),
      showBixo:  @json($ajuste('qr_show_bixo', '1') === '1'),
      showBenefits: @json($ajuste('qr_show_benefits', '1') === '1'),
      benefits:  (@json($ajuste('qr_benefits', '')) || 'Explora nuestros productos|Novedades cada semana|Ofertas especiales|Compra fácil').split('|').slice(0,4),
      // Marcas que trabaja el negocio: dan credibilidad en el volante.
      brands:    (@json($ajuste('qr_brands', '')) || '').split('|').map(m => m.trim()).filter(Boolean).slice(0,8),
      showBrands: @json($ajuste('qr_show_brands', '1') === '1'),
      shareMessage: @json($ajuste('qr_share_message', '¡Hola! Te compartimos nuestra tienda:')),
      mode:      @json($ajuste('qr_mode','catalog')),
      tableCount:+@json($ajuste('qr_table_count',10)),
      reception: @json($ajuste('qr_reception','auto')),
      payment:   @json($ajuste('qr_payment','cashier')),
    },

    arrancar() {
      this.revisarContraste();
      if (document.fonts && document.fonts.ready) {
        Promise.all([
          document.fonts.load('800 40px "Nunito"'),
          document.fonts.load('italic 800 40px "Nunito"'),
          document.fonts.load('800 40px "Inter"'),
        ]).then(() => this.pintar()).catch(() => {});
      }
      this.pintar();
      // Autoguardado: se espera a que el usuario termine de mover el control,
      // si no cada pixel del deslizador seria una peticion.
      this.$watch('form', () => {
        this.revisarContraste();
        this.pintar();
        clearTimeout(this._t);
        this.estado = 'pendiente';
        this._t = setTimeout(() => this.guardar(), 900);
      }, { deep: true });
    },

    /* ---------- Color escrito a mano ---------- */
    hex(e, campo) {
      let v = e.target.value.trim().replace(/^#*/, '').toUpperCase().replace(/[^0-9A-F]/g, '').slice(0, 6);
      e.target.value = '#' + v;
      if (v.length === 3) v = v[0]+v[0]+v[1]+v[1]+v[2]+v[2];
      if (v.length === 6) this.form[campo] = '#' + v.toLowerCase();
    },

    usarPlantilla(id) {
      const p = QX_FLYERS[id]; if (!p) return;
      this.form.template = id;
      const piel = QX_PIEL(id);
      if (piel) {
        this.form.header = piel.header; this.form.tinte = piel.tinte;
        this.form.cardColor = piel.card; this.form.bodyColor = piel.body;
        this.form.textColor = piel.texto;
        this.form.fg = '#111827'; this.form.bg = '#ffffff';
        if (piel.benef)  this.form.showBenefits = true;
        if (piel.logoQr && this.tieneLogo) this.form.logoInQr = true;
      }
      // Cada composicion tiene su formato natural (historia para redes, A5
      // para impresion). Se adopta salvo que el usuario ya eligiera uno.
      if (p.formato && !this._formatoManual) this.form.format = p.formato;
    },

    coloresDeMarca() {
      this.form.header = QX.primary;
      this.form.fg = '#111827';
      this.form.bg = '#ffffff';
      this.form.textColor = this.claro(QX.primary) ? '#0f172a' : '#ffffff';
      this.avisar('Colores de tu tienda aplicados', 'success');
    },

    /* ---------- Legibilidad del código ---------- */
    lum(x){ x=x.slice(1); const a=[0,2,4].map(i=>parseInt(x.slice(i,i+2),16)/255)
      .map(v=>v<=.03928?v/12.92:((v+.055)/1.055)**2.4); return .2126*a[0]+.7152*a[1]+.0722*a[2]; },
    claro(c){ return this.lum(c) > .55; },
    revisarContraste() {
      const bgReal = this.fondoQr();
      const r = (Math.max(this.lum(this.form.fg), this.lum(bgReal)) + .05)
              / (Math.min(this.lum(this.form.fg), this.lum(bgReal)) + .05);
      // Por debajo de 4:1 muchos lectores ya fallan, sobre todo impreso.
      const fondo = this.fondoQr();
      const invertido = this.lum(this.form.fg) > this.lum(fondo);
      this.avisoContraste = r < 4
        ? 'El código puede no escanearse: usa colores más contrastados.'
        : invertido
          ? 'El código está en claro sobre oscuro y varios teléfonos no lo leen así. Usa un código oscuro sobre fondo claro.'
          : (this.form.margin < 1 ? 'Un margen menor a 1 dificulta la lectura del código.' : '');
    },

    /* ---------- Imagen del código ---------- */
    qrSrc(px) {
      if (!this.publicUrl) return '';
      // ecc=H (30% de recuperacion): es lo que permite poner un logo encima
      // sin que el codigo deje de leerse.
      return 'https://api.qrserver.com/v1/create-qr-code/?size='+px+'x'+px
        + '&data=' + encodeURIComponent(this.publicUrl)
        + '&color=' + this.form.fg.slice(1) + '&bgcolor=' + this.fondoQr().slice(1)
        + '&margin=' + this.form.margin + '&ecc=H&format=png';
    },

    /* Fondo real del codigo: si la plantilla trae uno propio (fondo oscuro
       de la pieza pero codigo sobre blanco) manda ese. */
    /* El codigo va SIEMPRE sobre claro: un QR invertido no lo leen todos
       los telefonos, por bonito que quede sobre un fondo oscuro. */
    fondoQr() { return this.claro(this.form.cardColor) ? this.form.cardColor : '#ffffff'; },

    /* Lee la matriz de modulos del SVG de la API: cada modulo llega como un
       cuadrado "M x,y l ...". Con la matriz se redibuja el codigo con
       modulos redondeados, como la pieza de referencia. */
    async matrizQr() {
      const clave = this.publicUrl;
      if (this._matriz && this._matrizClave === clave) return this._matriz;
      const url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
        + encodeURIComponent(this.publicUrl) + '&ecc=H&margin=0&format=svg';
      const svg = await fetch(url, { mode:'cors' }).then(r => { if (!r.ok) throw new Error('svg'); return r.text(); });
      const ancho = parseFloat((svg.match(/width="(\d+(?:\.\d+)?)"/) || [])[1]);
      const puntos = [...svg.matchAll(/M\s+(\d+(?:\.\d+)?),(\d+(?:\.\d+)?)\s+l/g)]
        .map(m => [parseFloat(m[1]), parseFloat(m[2])]);
      if (!ancho || puntos.length < 50) throw new Error('svg raro');
      const xs = [...new Set(puntos.map(p => p[0]))].sort((a,b)=>a-b);
      let celda = Infinity;
      for (let i = 1; i < xs.length; i++) { const d = xs[i]-xs[i-1]; if (d > .5 && d < celda) celda = d; }
      const n = Math.round(ancho / celda);
      const m = Array.from({length:n}, () => Array(n).fill(false));
      puntos.forEach(([x,y]) => {
        const c = Math.round(x/celda), f = Math.round(y/celda);
        if (f >= 0 && f < n && c >= 0 && c < n) m[f][c] = true;
      });
      this._matriz = m; this._matrizClave = clave;
      return m;
    },

    /* Dibuja el codigo con modulos redondeados y los tres localizadores como
       anillos, respetando la zona de silencio del margen configurado. */
    async lienzoQr(px) {
      try {
        const m = await this.matrizQr();
        const n = m.length, margen = Math.max(1, this.form.margin);
        const total = n + margen*2, celda = px/total;
        const c = document.createElement('canvas'); c.width = px; c.height = px;
        const x = c.getContext('2d');
        x.fillStyle = this.fondoQr(); x.fillRect(0, 0, px, px);
        x.fillStyle = this.form.fg;

        const enLocalizador = (f, col) =>
          (f < 7 && col < 7) || (f < 7 && col >= n-7) || (f >= n-7 && col < 7);

        const rr = (px_, py_, w, h, r) => { x.beginPath();
          if (x.roundRect) x.roundRect(px_, py_, w, h, r); else x.rect(px_, py_, w, h); x.fill(); };

        // Modulos sueltos, redondeados (con medio pixel extra: sin el quedan
        // hilos blancos entre modulos vecinos al escalar)
        for (let f = 0; f < n; f++) for (let col = 0; col < n; col++) {
          if (!m[f][col] || enLocalizador(f, col)) continue;
          const cx = (col+margen)*celda, cy = (f+margen)*celda;
          rr(cx - .5, cy - .5, celda + 1, celda + 1, celda*.26);
        }
        // Localizadores: anillo exterior 7x7 + nucleo 3x3, todo redondeado
        const localizador = (f0, c0) => {
          const cx = (c0+margen)*celda, cy = (f0+margen)*celda, l = celda*7;
          x.save(); x.beginPath();
          if (x.roundRect) { x.roundRect(cx, cy, l, l, celda*1.6); x.roundRect(cx+celda, cy+celda, l-celda*2, l-celda*2, celda*1.0); }
          else { x.rect(cx, cy, l, l); x.rect(cx+celda, cy+celda, l-celda*2, l-celda*2); }
          x.fill('evenodd'); x.restore();
          rr(cx+celda*2, cy+celda*2, celda*3, celda*3, celda*.75);
        };
        localizador(0, 0); localizador(0, n-7); localizador(n-7, 0);
        return c;
      } catch (e) {
        // Si el SVG cambia de formato, se vuelve a la imagen clasica
        return this.cargar(this.qrSrc(px));
      }
    },

    /* Se pinta desde un blob del mismo origen: una imagen remota cargada
       directa ensucia el canvas y toBlob() falla al descargar. */
    cargar(src) {
      if (this._cacheQr[src]) return Promise.resolve(this._cacheQr[src]);
      return fetch(src, { mode:'cors' })
        .then(r => { if (!r.ok) throw new Error('no disponible'); return r.blob(); })
        .then(b => new Promise((ok, no) => {
          const u = URL.createObjectURL(b), i = new Image();
          i.onload = () => { this._cacheQr[src] = i; ok(i); setTimeout(()=>URL.revokeObjectURL(u), 2000); };
          i.onerror = () => { URL.revokeObjectURL(u); no(new Error('no se pudo leer la imagen')); };
          i.src = u;
        }));
    },

    async logoImg() {
      if (this._logoImg !== undefined) return this._logoImg;
      if (!QX.logo) { this._logoImg = null; return null; }
      try { this._logoImg = this.recortar(await this.cargar(QX.logo)); }
      catch (e) { this._logoImg = null; }
      return this._logoImg;
    },

    /* Los logos suelen traer mucho aire alrededor; sin recortarlo, ese vacio
       se lleva el espacio y el logo se ve diminuto por grande que se ponga. */
    recortar(img) {
      try {
        const c = document.createElement('canvas'), x = c.getContext('2d', { willReadFrequently:true });
        c.width = img.width; c.height = img.height; x.drawImage(img, 0, 0);
        const d = x.getImageData(0, 0, c.width, c.height).data, f = [d[0],d[1],d[2],d[3]];
        const vacio = i => d[i+3] < 12 ? true : (f[3] < 12 ? false :
          Math.abs(d[i]-f[0])<14 && Math.abs(d[i+1]-f[1])<14 && Math.abs(d[i+2]-f[2])<14);
        let x0=c.width, y0=c.height, x1=-1, y1=-1;
        for (let y=0;y<c.height;y++) for (let xx=0;xx<c.width;xx++)
          if (!vacio((y*c.width+xx)*4)) { if(xx<x0)x0=xx; if(xx>x1)x1=xx; if(y<y0)y0=y; if(y>y1)y1=y; }
        if (x1<0 || x1-x0<8 || y1-y0<8) return img;
        const m = Math.round(Math.min(c.width,c.height)*.01);
        x0=Math.max(0,x0-m); y0=Math.max(0,y0-m);
        x1=Math.min(c.width-1,x1+m); y1=Math.min(c.height-1,y1+m);
        const r = document.createElement('canvas');
        r.width = x1-x0+1; r.height = y1-y0+1;
        r.getContext('2d').drawImage(c, x0,y0,r.width,r.height, 0,0,r.width,r.height);
        return r;
      } catch (e) { return img; }
    },

    /* ================= MOTOR DE DIBUJO =================
       Devuelve un canvas ya compuesto. Lo usan la preview y la descarga:
       una sola fuente visual de verdad. */
    /* ================= MOTOR =================
       Prepara los datos y delega el dibujo en la composicion elegida. El
       mismo resultado alimenta la vista previa y la descarga. */
    async componer(tipo, escala, tplId) {
      const idTpl = tplId || this.form.template;
      const comp = QX_FLYERS[idTpl] || QX_FLYERS.marca;
      const piel = tplId ? QX_PIEL(tplId) : null;
      const dim  = tipo === 'qr'
        ? { w: 1080, h: 1080 }
        : (QX_FORMATOS[tplId ? (comp.formato || 'post') : this.form.format] || QX_FORMATOS.post);
      const W = dim.w, H = dim.h;

      const c = document.createElement('canvas');
      c.width = Math.round(W*escala); c.height = Math.round(H*escala);
      const ctx = c.getContext('2d');
      ctx.scale(escala, escala);
      ctx.textBaseline = 'alphabetic';

      // La trama se rasteriza a la medida de la pieza: fija en 1000 px salia
      // blanda en la pieza de imprenta (A5 a 300 ppp).
      const img  = await this.lienzoQr(Math.round(Math.min(1800, Math.max(1000, dim.w*0.95))));
      const logo = this.form.showLogo ? await this.logoImg() : null;
      const headerClaro = this.claro(piel ? piel.header : this.form.header);

      const datos = {
        qr: img, logo, nombre: QX.nombre,
        urlCorta: (this.publicUrl || '').replace(/^https?:\/\//, ''),
        ...(function(t, st){
          t = (t || '').trim(); st = (st || '').trim();
          if (!st) {
            const pal = t.split(/\s+/);
            if (pal.length >= 4) { st = pal.slice(-2).join(' '); t = pal.slice(0, -2).join(' '); }
          }
          return { titulo: t, subtitulo: st };
        })(this.form.topText, this.form.subtitle),
        cta: this.form.bottomText,
        beneficios: (piel ? !!piel.benef : this.form.showBenefits)
          ? this.form.benefits.filter(b => (b||'').trim()).slice(0,4) : [],
        marcas: this.form.showBrands ? this.form.brands.filter(Boolean).slice(0,8) : [],
        iconos: this.form.icons,
        // Colores derivados: el tinte claro de la marca es lo que da el aire
        // de la pieza de referencia sin que el usuario tenga que calcularlo.
        header: piel ? piel.header : this.form.header,
        acento: QX.accent,
        conTrama: this.form.showTrama,
        conEscuadras: this.form.showBrackets,
        escalaQr: Math.min(1.15, Math.max(.80, (this.form.qrScale || 100) / 100)),
        header2: QX.secondary || this.form.header,
        headerSuave: this.mezclar(piel ? piel.header : this.form.header, '#ffffff', (piel ? piel.tinte : this.form.tinte)/100),
        headerClaro,
        tramaColor: headerClaro ? '#0f172a' : '#ffffff',
        textoHeader: headerClaro ? '#0f172a' : ((piel ? piel.texto : this.form.textColor) || '#ffffff'),
        tituloColor: headerClaro ? '#0f172a' : ((piel ? piel.texto : this.form.textColor) || '#ffffff'),
        tituloAcento: this.mezclar(piel ? piel.header : this.form.header, '#000000', .25),
        textoCuerpo: piel ? piel.body : this.form.bodyColor,
        fondo: piel ? piel.card : this.form.cardColor,
        fondoQr: this.fondoQr(),
        iconoFondo: this.mezclar(this.form.header, '#ffffff', .86),
        escalaLogo: (this.form.logoSize || 110) / 110,
        logoEnQr: piel ? (!!piel.logoQr && !!logo) : this.form.logoInQr,
        degradado: this.form.background === 'degradado',
        mostrarUrl: this.form.showUrl, mostrarNombre: this.form.showName,
        mostrarBixo: this.form.showBixo,
      };

      const g = QXLienzo(ctx, W, H);
      g._iniciales = (QX.nombre || '').trim().split(/\s+/).slice(0,2).map(p => p[0]).join('').toUpperCase();
      if (tipo === 'qr') QX_TARJETA_QR(g, datos);
      else comp.dibujar(g, datos);
      return c;
    },

    /* Mezcla dos colores: sirve para el tinte de la banda y los discos. */
    mezclar(a, b, p) {
      const h = x => [1,3,5].map(i => parseInt(x.slice(i,i+2),16));
      const [r1,g1,b1] = h(a), [r2,g2,b2] = h(b);
      const m = (x,y) => Math.round(x + (y-x)*p).toString(16).padStart(2,'0');
      return '#' + m(r1,r2) + m(g1,g2) + m(b1,b2);
    },

    /* Corta el texto largo en varias lineas: un nombre largo se salia del arte. */
    parrafo(x, texto, cx, y, maxW, alto) {
      const palabras = String(texto).split(/\s+/); let linea = '', filas = [];
      palabras.forEach(p => {
        const prueba = linea ? linea + ' ' + p : p;
        if (x.measureText(prueba).width > maxW && linea) { filas.push(linea); linea = p; }
        else linea = prueba;
      });
      if (linea) filas.push(linea);
      filas.slice(0, 3).forEach((f, i) => x.fillText(f, cx, y + i*alto));
      return y + filas.slice(0,3).length * alto;
    },
    recorta(t, n) { t = String(t||''); return t.length > n ? t.slice(0, n-1) + '…' : t; },

    /* ---------- Vista previa (mismo motor, escala menor) ---------- */
    /* Cambiar de plantilla toca varias propiedades a la vez. Se agrupan en
       un solo repintado: si no, la primera llamada bloqueaba a las demas y el
       lienzo se quedaba con el estado anterior. */
    pintar() {
      clearTimeout(this._tp);
      this._tp = setTimeout(() => this.pintarYa(), 120);
    },

    async pintarYa() {
      if (!this.publicUrl) return;
      if (this._pintando) { this._sucio = true; return; }
      this._pintando = true; this.cargando = true;
      try {
        const c = await this.componer(this.vista, .42);
        const destino = this.$refs.lienzo;
        destino.width = c.width; destino.height = c.height;
        destino.getContext('2d').drawImage(c, 0, 0);
        this.fallo = '';
      } catch (e) {
        console.error('QR preview:', e);
        this.fallo = 'No se pudo dibujar la vista previa. Revisa tu conexión e inténtalo de nuevo.';
      } finally {
        this._pintando = false; this.cargando = false;
        // Si llegaron cambios mientras se pintaba, se repinta con el estado final.
        if (this._sucio) { this._sucio = false; this.pintarYa(); }
        this.miniaturas();
      }
    },

    /* Miniaturas del selector: cada tarjeta muestra la plantilla REAL en
       pequeno, dibujada por el mismo motor. Sin cajas vacias. */
    async miniaturas() {
      if (this._minisListas || !this.publicUrl) return;
      this._minisListas = true;
      for (const id of Object.keys(QX_FLYERS)) {
        try {
          const destino = document.querySelector(`canvas[data-tpl-mini="${id}"]`);
          if (!destino) continue;
          const dim = QX_FORMATOS[QX_FLYERS[id].formato || 'post'];
          const c = await this.componer('flyer', 220/dim.h, id);
          destino.width = c.width; destino.height = c.height;
          destino.getContext('2d').drawImage(c, 0, 0);
        } catch (e) { /* la tarjeta conserva su etiqueta */ }
      }
    },

    /* Reintento manual tras un fallo de red: se tira la cache del codigo para
       no reusar una peticion fallida. */
    reintentar() { this.fallo = ''; this._cacheQr = {}; this._matriz = null; this._matrizClave = ''; this.pintarYa(); },

    /* ---------- Descargas ---------- */
    async descargar(tipo) {
      if (!this.publicUrl) return;
      this.cargando = true;
      try {
        if (tipo === 'svg') {
          const r = await fetch(this.qrSrc(1200).replace('format=png','format=svg'));
          if (!r.ok) throw new Error('El generador no respondió');
          this.bajar(await r.blob(), this.slug()+'-qr.svg');
        } else {
          const c = await this.componer(tipo, this.form.quality === 'high' ? 1 : .8);
          const blob = await new Promise(ok => c.toBlob(ok, 'image/png', 1));
          if (!blob) throw new Error('No se pudo generar la imagen');
          this.bajar(blob, this.slug() + (tipo === 'qr' ? '-qr.png' : '-' + this.form.template + '-' + this.form.format + '.png'));
        }
        this.avisar('Descarga lista', 'success');
      } catch (e) {
        console.error('QR descarga:', e);
        this.avisar('No se pudo generar la descarga. Revisa tu conexión.', 'error');
      } finally { this.cargando = false; }
    },
    bajar(blob, nombre) {
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob); a.download = nombre; a.click();
      setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    },
    slug() { return (QX.nombre||'tienda').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'')
      .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''); },

    /* ---------- Compartir ---------- */
    async compartir() {
      const texto = this.form.shareMessage + ' ' + this.publicUrl;
      // Web Share con archivo si el dispositivo lo permite; si no, WhatsApp.
      try {
        const c = await this.componer('flyer', .8);
        const blob = await new Promise(ok => c.toBlob(ok, 'image/png', 1));
        const file = new File([blob], this.slug()+'-flyer.png', { type:'image/png' });
        if (navigator.canShare && navigator.canShare({ files:[file] })) {
          await navigator.share({ files:[file], text: texto, title: QX.nombre });
          return;
        }
      } catch (e) { /* sin Web Share o cancelado: se sigue por WhatsApp */ }
      window.open('https://wa.me/?text=' + encodeURIComponent(texto), '_blank', 'noopener');
    },

    async copiarUrl() {
      try { await navigator.clipboard.writeText(this.publicUrl); this.avisar('Dirección copiada', 'success'); }
      catch (e) { this.avisar('No se pudo copiar', 'error'); }
    },

    /* Comprueba de verdad que la direccion responde antes de abrirla. */
    async probar() {
      if (!this.publicUrl) return;
      window.open(this.publicUrl, '_blank', 'noopener');
      this.avisar('Abrimos tu tienda: comprueba que sea la página correcta', 'info');
    },

    avisar(msg, type) {
      // Toast global del panel, en vez de uno propio duplicado.
      window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg, type: type||'success' } }));
    },

    /* ---------- Guardado ---------- */
    async guardar() {
      this.estado = 'guardando';
      try {
        const f = new FormData();
        // El token se lee en cada envio: el panel lo refresca cada 15 min.
        const tk = document.querySelector('meta[name="csrf-token"]').content;
        f.append('_token', tk);
        const datos = {
          qr_template: this.form.template, qr_format: this.form.format,
          qr_margin: this.form.margin,
          qr_foreground: this.form.fg, qr_background: this.form.bg,
          qr_header_color: this.form.header, qr_text_color: this.form.textColor,
          qr_top_text: this.form.topText, qr_subtitle: this.form.subtitle,
          qr_bottom_text: this.form.bottomText, qr_share_message: this.form.shareMessage,
          qr_quality: this.form.quality, qr_preset: this.form.template === 'marca' ? 'brand' : 'classic',
          qr_logo_size: this.form.logoSize,
          qr_tinte: this.form.tinte, qr_card_color: this.form.cardColor,
          qr_body_color: this.form.bodyColor, qr_background_style: this.form.background,
          qr_icons: this.form.icons.join('|'),
          qr_scale: this.form.qrScale,
          qr_brands: this.form.brands.join('|'), qr_show_brands: +this.form.showBrands,
          qr_benefits: this.form.benefits.filter(b=>(b||'').trim()).join('|'),
          qr_show_logo: +this.form.showLogo, qr_show_url: +this.form.showUrl,
          qr_show_name: +this.form.showName, qr_show_bixo: +this.form.showBixo,
          qr_show_benefits: +this.form.showBenefits, qr_logo_in_qr: +this.form.logoInQr,
          qr_show_trama: +this.form.showTrama, qr_show_brackets: +this.form.showBrackets,
          // Mesas: se reenvian tal cual para no perderlas al guardar el diseño.
          qr_mode: this.form.mode, qr_table_count: this.form.tableCount,
          qr_reception: this.form.reception, qr_payment: this.form.payment,
        };
        Object.entries(datos).forEach(([k,v]) => f.append(k, v));
        document.querySelectorAll('[name^="qr_schedule["]').forEach(i => {
          if (i.type !== 'checkbox' || i.checked) f.append(i.name, i.value);
        });
        const r = await fetch(QX.guardar, { method:'POST', headers:{ 'X-CSRF-TOKEN': tk, Accept:'application/json' }, body: f });
        const d = await r.json();
        if (!r.ok || !d.ok) throw new Error('rechazado');
        this.estado = 'guardado';
        setTimeout(() => { if (this.estado === 'guardado') this.estado = ''; }, 2500);
      } catch (e) {
        console.error('QR guardar:', e);
        this.estado = 'error';
      }
    },
  };
}
</script>
