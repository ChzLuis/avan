<script>
/**
 * Plantilla de imágenes de producto — lado del navegador.
 *
 * La vista previa NO se dibuja aquí: se pide al servidor, que la compone con
 * el mismo código que genera las imágenes publicadas. Así no puede haber
 * diferencia entre lo que el comerciante ve y lo que sale en la tienda.
 *
 * El navegador solo aporta: los controles, el arrastre de logo/marca de agua
 * (en porcentaje, nunca en píxeles) y el guardado con espera para no disparar
 * una composición por cada movimiento del deslizador.
 */
function plantillaImagenes() {
  const RUTAS = {
    show:    @json(route('builder.image-template.show')),
    save:    @json(route('builder.image-template.save')),
    toggle:  @json(route('builder.image-template.toggle')),
    reset:   @json(route('builder.image-template.reset')),
    upload:  @json(route('builder.image-template.upload')),
    preview: @json(route('builder.image-template.preview')),
    apply:   @json(route('builder.image-template.apply')),
    status:  @json(route('builder.image-template.status')),
    saveAs:   @json(route('builder.image-template.save-as')),
    activate: @json(route('builder.image-template.activate')),
  };
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

  return {
    cargando: true, pintando: false, guardando: false, ocupado: false,
    tpl: { enabled: false, config: {} },
    cfg: {},
    stats: {}, muestras: [], muestraId: null, logoNegocio: null,
    guardadas: [], categorias: [], categoriaId: '',
    previewUrl: '', mensaje: '', estado: '',
    pidiendoNombre: false,
    nombreNuevo: '', confirmar: null, logoCambio: false,
    _t: null, _tomado: null, _sondeo: null,

    // Rejilla 3x3: atajos a las nueve posiciones habituales.
    posiciones: [
      {k:'tl',x:12,y:12,t:'Superior izquierda'},   {k:'tc',x:50,y:12,t:'Superior centro'},   {k:'tr',x:88,y:12,t:'Superior derecha'},
      {k:'cl',x:12,y:50,t:'Centro izquierda'},     {k:'c', x:50,y:50,t:'Centro'},            {k:'cr',x:88,y:50,t:'Centro derecha'},
      {k:'bl',x:12,y:88,t:'Inferior izquierda'},   {k:'bc',x:50,y:88,t:'Inferior centro'},   {k:'br',x:88,y:88,t:'Inferior derecha'},
    ],

    async cargar() {
      try {
        const d = await this.pedir(RUTAS.show, null, 'GET');
        this.tpl = d.template; this.cfg = { ...d.template.config };
        this.stats = d.stats; this.muestras = d.samples; this.logoNegocio = d.business_logo;
        this.guardadas = d.templates || []; this.categorias = d.categories || [];
        this.logoCambio = !!d.logo_changed;
        this.muestraId = d.samples[0]?.id ?? null;
        this.cargando = false;
        if (this.muestraId) this.refrescar();
        else this.mensaje = 'Sube la foto de algún producto para ver la plantilla aplicada.';
      } catch (e) { this.cargando = false; this.mensaje = 'No se pudo cargar la plantilla.'; }
    },

    alto() {
      const r = { '1:1': 1, '4:5': 1.25, '3:4': 1.3333 }[this.cfg.aspect_ratio] || 1;
      return Math.round((this.cfg.output_width || 1200) * r);
    },

    /** Un cambio de control: se guarda y se repinta, con espera. */
    set(clave, valor) {
      this.cfg = { ...this.cfg, [clave]: valor };
      clearTimeout(this._t);
      this._t = setTimeout(() => this.guardar(true), 450);
    },

    centrar(on) {
      // "Centrar automáticamente" devuelve el producto al centro del lienzo.
      this.cfg = { ...this.cfg, product_autocenter: on, ...(on ? { product_x: 50, product_y: 50 } : {}) };
      clearTimeout(this._t);
      this._t = setTimeout(() => this.guardar(true), 300);
    },

    colocar(cual, p) { this.cfg = { ...this.cfg, [cual+'_x']: p.x, [cual+'_y']: p.y }; this.guardar(true); },
    activa(cual, p) { return Math.abs((this.cfg[cual+'_x']??0)-p.x) < 2 && Math.abs((this.cfg[cual+'_y']??0)-p.y) < 2; },
    posicion(x, y) { return `left:${x}%;top:${y}%`; },

    // ── Arrastre: la posición se guarda en % del lienzo, nunca en píxeles,
    //    para que salga igual en cualquier resolución de salida.
    tomar(cual, ev) { this._tomado = cual; },
    arrastrar(ev) {
      if (!this._tomado) return;
      const r = this.$refs.marco.getBoundingClientRect();
      const punto = ev.touches ? ev.touches[0] : ev;
      const x = Math.max(0, Math.min(100, ((punto.clientX - r.left) / r.width) * 100));
      const y = Math.max(0, Math.min(100, ((punto.clientY - r.top) / r.height) * 100));
      this.cfg = { ...this.cfg, [this._tomado+'_x']: Math.round(x), [this._tomado+'_y']: Math.round(y) };
    },
    soltar() { if (this._tomado) { this._tomado = null; this.guardar(true); } },

    async guardar(silencioso) {
      this.guardando = true;
      if (!silencioso) this.estado = 'Guardando…';
      try {
        const d = await this.pedir(RUTAS.save, { name: this.tpl.name, enabled: this.tpl.enabled, config: this.cfg });
        this.tpl = d.template; this.stats = d.stats;
        this.estado = silencioso ? '' : 'Guardado';
        this.refrescar();
      } catch (e) { this.estado = 'No se pudo guardar.'; }
      finally { this.guardando = false; }
    },

    /** Pide al servidor la composición real de la foto de prueba. */
    async refrescar() {
      if (!this.muestraId) return;
      this.pintando = true; this.mensaje = '';
      try {
        const d = await this.pedir(RUTAS.preview, { image_id: this.muestraId });
        this.previewUrl = d.data_url;
      } catch (e) { this.mensaje = e.message || 'No se pudo generar la vista previa.'; }
      finally { this.pintando = false; }
    },

    async alternar(on) {
      this.tpl = { ...this.tpl, enabled: on };
      try {
        const d = await this.pedir(RUTAS.toggle, { enabled: on ? 1 : 0 });
        this.stats = d.stats;
        // Apagar no borra nada: solo deja de servirse la versión generada.
        this.estado = on ? 'Plantilla activa: la tienda usa las imágenes generadas.'
                         : 'Plantilla desactivada: la tienda vuelve a las fotos originales.';
      } catch (e) { this.estado = 'No se pudo cambiar el estado.'; }
    },

    async subir(ev, slot) {
      const f = ev.target.files?.[0]; if (!f) return;
      this.estado = 'Subiendo…';
      const fd = new FormData(); fd.append('file', f); fd.append('slot', slot);
      try {
        const r = await fetch(RUTAS.upload, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: fd });
        const d = await r.json();
        if (!r.ok || !d.ok) throw new Error(d.message || 'No se pudo subir.');
        this.estado = ''; this.set(slot, d.url);
      } catch (e) { this.estado = e.message; }
      finally { ev.target.value = ''; }
    },

    /** Producto al que pertenece la foto de prueba elegida. */
    productoMuestra() {
      return this.muestras.find(m => String(m.id) === String(this.muestraId))?.product_id ?? null;
    },

    /** Pide primero el recuento para poder confirmar antes de regenerar. */
    async aplicar(scope) {
      this.ocupado = true;
      try {
        if (scope === 'product') {
          // Una sola foto: no hace falta confirmar.
          await this.regenerarMuestra();
          return;
        }
        const cuerpo = { scope, dry_run: 1 };
        if (scope === 'category') cuerpo.category_id = this.categoriaId;
        const d = await this.pedir(RUTAS.apply, cuerpo);
        this.confirmar = { scope, count: d.count };
      } catch (e) { this.estado = 'No se pudo preparar la regeneración.'; }
      finally { this.ocupado = false; }
    },

    /** Regenera solo el producto que se está previsualizando. */
    async regenerarMuestra() {
      const pid = this.productoMuestra();
      if (!pid) { this.estado = 'Elige un producto de prueba.'; return; }
      this.estado = 'Regenerando la foto de prueba…';
      try {
        await this.pedir(RUTAS.apply, { scope:'product', product_id: pid, force: 1 });
        await this.refrescar();
        this.estado = 'Imagen del producto de prueba regenerada.';
      } catch (e) { this.estado = 'No se pudo regenerar.'; }
    },

    async ejecutar() {
      const scope = this.confirmar?.scope; this.confirmar = null;
      this.ocupado = true; this.estado = 'Regenerando imágenes…';
      try {
        const cuerpo = { scope, force: 1 };
        if (scope === 'category') cuerpo.category_id = this.categoriaId;
        const d = await this.pedir(RUTAS.apply, cuerpo);
        this.stats = d.stats || this.stats;
        this.estado = `${d.count} imágenes en cola.`;
        this.sondear();
      } catch (e) { this.estado = 'No se pudo lanzar la regeneración.'; }
      finally { this.ocupado = false; }
    },

    /** Progreso sin bloquear la pantalla. */
    sondear() {
      clearInterval(this._sondeo);
      this._sondeo = setInterval(async () => {
        try {
          const d = await this.pedir(RUTAS.status, null, 'GET');
          this.stats = d.stats;
          if ((d.stats.in_progress ?? 0) === 0) {
            clearInterval(this._sondeo);
            this.estado = `Listo: ${d.stats.up_to_date} de ${d.stats.images} al día.`;
            this.refrescar();
          } else {
            this.estado = `${d.stats.up_to_date} / ${d.stats.images} procesadas (${d.stats.percent}%)`;
          }
        } catch (e) { clearInterval(this._sondeo); }
      }, 2500);
    },

    /** Congela la configuración actual como plantilla aparte y la deja activa. */
    async guardarComo() {
      const nombre = (this.nombreNuevo || '').trim();
      if (!nombre) { this.pidiendoNombre = true; return; }
      this.pidiendoNombre = false;
      this.estado = 'Guardando la plantilla…';
      try {
        const d = await this.pedir(RUTAS.saveAs, { name: nombre });
        this.tpl = d.template; this.cfg = { ...d.template.config }; this.stats = d.stats;
        await this.recargarGuardadas();
        this.estado = 'Plantilla «' + nombre + '» creada y activa.';
      } catch (e) { this.estado = 'No se pudo guardar la plantilla.'; }
    },

    /** Cambiar de plantilla NO regenera nada: solo cambia la que manda. */
    async activar(id) {
      this.estado = 'Cambiando de plantilla…';
      try {
        const d = await this.pedir(RUTAS.activate, { id: +id });
        this.tpl = d.template; this.cfg = { ...d.template.config }; this.stats = d.stats;
        await this.recargarGuardadas();
        this.refrescar();
        this.estado = 'Plantilla activa: ' + d.template.name + '. Regenera para aplicarla al catálogo.';
      } catch (e) { this.estado = 'No se pudo cambiar de plantilla.'; }
    },

    async recargarGuardadas() {
      try {
        const d = await this.pedir(RUTAS.show, null, 'GET');
        this.guardadas = d.templates || [];
      } catch (e) { /* la lista se queda como estaba */ }
    },

    async restablecer() {
      this.estado = 'Restableciendo…';
      try {
        const d = await this.pedir(RUTAS.reset);
        this.tpl = d.template; this.cfg = { ...d.template.config };
        this.estado = 'Valores por defecto. No se borró ninguna imagen.';
        this.refrescar();
      } catch (e) { this.estado = 'No se pudo restablecer.'; }
    },

    /**
     * El metodo va SIEMPRE explicito. Deducirlo de la URL no sirve aqui: leer
     * y guardar la plantilla comparten ruta (GET y POST sobre
     * /settings/builder/image-template), y adivinar convertia cada guardado en
     * una lectura silenciosa que decia "Guardado" sin guardar nada.
     */
    async pedir(url, cuerpo, metodo = 'POST') {
      const esGet = metodo === 'GET';
      const r = await fetch(url, {
        method: metodo,
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        ...(esGet ? {} : { body: JSON.stringify(cuerpo || {}) }),
      });
      const d = await r.json().catch(() => ({}));
      if (!r.ok || d.ok === false) throw new Error(d.message || 'Error');
      return d;
    },
  };
}
</script>
