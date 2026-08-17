<script>
function quickSetup(cfg){
  return {
    saveUrl: cfg.saveUrl, csrf: cfg.csrf,
    step: 0, saving: false,
    stepLabels: ['Negocio','Identidad','Inicio','Venta'],
    form: { rubro:'', logo_url:'', primary_color:'#4f46e5', secondary_color:'#0f172a', quote_whatsapp:'', store_mode:'direct', template:'computienda' },

    // Rubros → configuración recomendada (plantilla + paleta). Sólo ajustes, sin marcas.
    rubroIcons: @js(collect(\App\Support\DesignerIcons::all())->only(['rubro_tecnologia','rubro_moda','rubro_bebes','rubro_muebles','rubro_ferreteria','rubro_alimentos','rubro_servicios','rubro_mayorista'])),
    rubros: [
      { id:'tecnologia', label:'Tecnología', icon:'rubro_tecnologia', template:'computienda', primary:'#2563eb', secondary:'#0f172a' },
      { id:'moda',       label:'Moda',        icon:'rubro_moda', template:'ecommerce',   primary:'#db2777', secondary:'#1f2937' },
      { id:'bebes',      label:'Bebés',       icon:'rubro_bebes', template:'ecommerce',   primary:'#38bdf8', secondary:'#334155' },
      { id:'muebles',    label:'Muebles',     icon:'rubro_muebles', template:'ecommerce',   primary:'#b45309', secondary:'#292524' },
      { id:'ferreteria', label:'Ferretería',  icon:'rubro_ferreteria', template:'computienda', primary:'#ea580c', secondary:'#1c1917' },
      { id:'alimentos',  label:'Alimentos',   icon:'rubro_alimentos', template:'ecommerce',   primary:'#16a34a', secondary:'#14532d' },
      { id:'servicios',  label:'Servicios',   icon:'rubro_servicios', template:'direct',      primary:'#7c3aed', secondary:'#1e1b4b' },
      { id:'mayorista',  label:'Mayorista',   icon:'rubro_mayorista', template:'computienda', primary:'#0891b2', secondary:'#0f172a' },
    ],
    palettes: [
      { name:'Índigo',   primary:'#4f46e5', secondary:'#0f172a' },
      { name:'Esmeralda',primary:'#059669', secondary:'#064e3b' },
      { name:'Coral',    primary:'#f43f5e', secondary:'#1f2937' },
      { name:'Ámbar',    primary:'#d97706', secondary:'#292524' },
      { name:'Cielo',    primary:'#0284c7', secondary:'#0c4a6e' },
    ],
    quickSections: [
      { key:'hero',                on:true,  label:'Banner principal' },
      { key:'benefits',            on:true,  label:'Beneficios' },
      { key:'featured_categories', on:true,  label:'Categorías destacadas' },
      { key:'featured_products',   on:true,  label:'Productos destacados' },
      { key:'announcements',       on:false, label:'Anuncios' },
    ],

    pickRubro(r){
      this.form.rubro = r.id;
      this.form.template = r.template;
      this.form.primary_color = r.primary;
      this.form.secondary_color = r.secondary;
    },
    async uploadLogo(ev){
      const file=ev.target.files[0]; if(!file) return;
      const fd=new FormData(); fd.append('file',file); fd.append('type','logo_url');
      try{
        const res=await fetch(@js(route('settings.upload-logo')),{method:'POST',headers:{'X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},body:fd});
        const d=await res.json();
        if(d.url){ this.form.logo_url=d.url; this.form._logo_path=d.path; }
      }catch(e){}
    },
    next(){ if(this.step===0 && !this.form.rubro) return; this.step++; },

    async finish(){
      this.saving=true;
      try{
        // 1) Plantilla → applyTemplate (flujo canónico que aplica sus settings).
        const tf=new FormData(); tf.append('_token',this.csrf); tf.append('template', this.form.template);
        await fetch(@js(route('settings.design.applyTemplate')),{method:'POST',headers:{'X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},body:tf});

        // 2) Identidad y venta → updateDesign (settings canónicos).
        const fd=new FormData();
        fd.append('_token',this.csrf);
        fd.append('primary_color', this.form.primary_color);
        fd.append('secondary_color', this.form.secondary_color);
        if(this.form._logo_path) fd.append('logo_url', this.form._logo_path);
        if(this.form.quote_whatsapp) fd.append('quote_whatsapp', this.form.quote_whatsapp);
        fd.append('store_mode', this.form.store_mode);
        await fetch(this.saveUrl,{method:'POST',headers:{'X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},body:fd});

        window.location.reload();
      }catch(e){ this.saving=false; }
    },
  };
}
</script>
