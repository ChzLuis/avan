{{-- Lógica del Diseñador (Alpine). Guarda con el endpoint canónico
     settings.design.update — mismos name= que el diseñador anterior. --}}
@php
  $dzIcons = \App\Support\DesignerIcons::all();
  // Serializa las secciones del Inicio con su estado (para el árbol y la preview).
  $componentLabels = [
    'announcement_bar'=>'Barra de anuncio','hero'=>'Banner principal','benefits'=>'Beneficios',
    'announcements'=>'Anuncios','featured_categories'=>'Categorías destacadas',
    'featured_products'=>'Productos destacados','daily_offer'=>'Oferta del día','custom_page'=>'Sección personalizada',
  ];
  $sectionsPayload = $homeSections->map(fn($s)=>[
    'component'   => $s->component,
    'label'       => $componentLabels[$s->component] ?? ucfirst(str_replace('_',' ',$s->component)),
    'icon'        => $dzIcons[$s->component] ?? $dzIcons['custom_page'],
    'enabled'     => (bool)($s->draft_is_enabled ?? $s->is_enabled),
    'show_desktop'=> (bool)($s->draft_show_desktop ?? $s->show_desktop ?? true),
    'show_mobile' => (bool)($s->draft_show_mobile ?? $s->show_mobile ?? true),
    'sort'        => (int)($s->draft_sort_order ?? $s->sort_order),
  ])->values();
@endphp
<script>
function designerShell(cfg){
  return {
    saveUrl: cfg.saveUrl, csrf: cfg.csrf, project: cfg.project, template: cfg.template,
    sectionSaveUrl: @js(route('settings.experience.home.state', ['component' => '__C__'])),
    device: 'desktop',
    selected: null,
    quickOpen: false,
    settings: @json($settings),
    homeSections: @json($sectionsPayload),
    status: 'clean',          // clean | dirty | saving | draft | published | error
    _history: [], _future: [],

    init(){
      // Snapshot inicial para undo.
      this._snapshot();
    },

    // ── selección ──
    select(id){ this.selected = id; },
    get inspectorTitle(){
      if(this.selected==='header') return 'Encabezado';
      if(this.selected && this.selected.startsWith('section:')){
        const c=this.selected.slice(8);
        const s=this.homeSections.find(x=>x.component===c);
        return s ? s.label : 'Sección';
      }
      const map={catalog:'Catálogo',product:'Página de producto',footer:'Pie de página',pages:'Páginas',checkout:'Venta y checkout'};
      return map[this.selected] || '';
    },

    // ── estado de cambios ──
    get statusLabel(){
      return {clean:'Sin cambios',dirty:'Cambios sin guardar',saving:'Guardando…',draft:'Borrador guardado',published:'Publicado',error:'Error al guardar'}[this.status];
    },
    markDirty(){ if(this.status!=='saving'){ this.status='dirty'; this._snapshot(); } },
    setValue(key,val){ this.settings[key]=val; this.markDirty(); },
    setToggle(key,checked){ this.settings[key]= checked ? '1':'0'; this.markDirty(); },

    // ── secciones ──
    toggleSection(component){
      const s=this.homeSections.find(x=>x.component===component);
      if(s){ s.enabled=!s.enabled; this.markDirty(); this._saveSectionState(s); }
    },
    // Reordena con flechas: aplica al instante en la tienda y en la preview.
    moveSection(component, dir){
      const i=this.homeSections.findIndex(x=>x.component===component);
      const j=i+dir;
      if(i<0||j<0||j>=this.homeSections.length) return;
      const arr=[...this.homeSections];
      [arr[i],arr[j]]=[arr[j],arr[i]];
      this.homeSections=arr;
      this.markDirty();
      this._saveSectionState(this.homeSections[i]);
      this._saveSectionState(this.homeSections[j]);
    },
    toggleDevice(component,dev){
      const s=this.homeSections.find(x=>x.component===component);
      if(!s) return;
      if(dev==='desktop') s.show_desktop=!s.show_desktop; else s.show_mobile=!s.show_mobile;
      this.markDirty(); this._saveSectionState(s);
    },
    // Autosave del estado de UNA sección al alternar u ordenar.
    // Publica al instante: lo que el usuario ve en el panel es lo que ve su tienda.
    async _saveSectionState(s){
      const i=this.homeSections.indexOf(s);
      const sf=new FormData();
      sf.append('_token',this.csrf); sf.append('action','publish');
      sf.append('is_enabled', s.enabled?'1':'0');
      sf.append('show_desktop', s.show_desktop?'1':'0');
      sf.append('show_mobile', s.show_mobile?'1':'0');
      sf.append('show_tablet', s.show_mobile?'1':'0');
      sf.append('sort_order', i<0?0:i);
      try{
        const r=await fetch(this.sectionSaveUrl.replace('__C__',s.component),{method:'POST',headers:{'X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},body:sf});
        if(r.ok) this.status='published';
      }catch(e){}
    },

    // ── undo / redo ──
    _snapshot(){
      const snap=JSON.stringify({settings:this.settings,homeSections:this.homeSections});
      if(this._history[this._history.length-1]!==snap){ this._history.push(snap); this._future=[]; }
      if(this._history.length>50) this._history.shift();
    },
    get canUndo(){ return this._history.length>1; },
    get canRedo(){ return this._future.length>0; },
    undo(){ if(!this.canUndo) return; this._future.push(this._history.pop()); this._restore(this._history[this._history.length-1]); },
    redo(){ if(!this.canRedo) return; const s=this._future.pop(); this._history.push(s); this._restore(s); },
    _restore(snap){ const o=JSON.parse(snap); this.settings=o.settings; this.homeSections=o.homeSections; this.status='dirty'; },

    // ── imágenes ──
    assetUrl(v){
      if(!v) return '';
      if(/^(https?:)?\/\//.test(v)||v.startsWith('data:')) return v;
      return '/storage/'+String(v).replace(/^storage\//,'');
    },
    async uploadImage(ev,key){
      const file=ev.target.files[0]; if(!file) return;
      const fd=new FormData(); fd.append('file',file); fd.append('type',key);
      try{
        const res=await fetch(@js(route('settings.upload-logo')),{method:'POST',headers:{'X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},body:fd});
        const d=await res.json();
        if(d.path){ this.settings[key]=d.path; this.markDirty(); }
      }catch(e){ this.status='error'; }
    },

    // ── guardado (endpoint canónico) ──
    _buildForm(){
      // Envía settings + estado de secciones con los MISMOS name= del diseñador anterior.
      // Sólo los settings visuales (identidad, hero, colores…) van al endpoint
      // de diseño. El estado de secciones se guarda por su servicio canónico.
      const fd=new FormData();
      fd.append('_token',this.csrf);
      for(const [k,v] of Object.entries(this.settings)){
        if(v!==null && v!==undefined) fd.append(k, v);
      }
      return fd;
    },
    async saveDraft(){ await this._save('draft'); },
    async publish(){ await this._save('publish'); },
    async _save(mode){
      if(this.status==='saving') return;
      this.status='saving';
      try{
        // 1) Settings visuales → endpoint de diseño (mismos name= del anterior).
        const res=await fetch(this.saveUrl,{method:'POST',headers:{'X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},body:this._buildForm()});
        if(!res.ok) throw new Error('design http '+res.status);

        // 2) Estado de cada sección (activar/orden/visibilidad) → servicio canónico
        //    de secciones (StoreSectionWriteService vía saveHomeSection).
        for(let i=0;i<this.homeSections.length;i++){
          const s=this.homeSections[i];
          const sf=new FormData();
          sf.append('_token',this.csrf);
          sf.append('action', mode==='publish'?'publish':'draft');
          sf.append('is_enabled', s.enabled?'1':'0');
          sf.append('show_desktop', s.show_desktop?'1':'0');
          sf.append('show_mobile', s.show_mobile?'1':'0');
          sf.append('show_tablet', s.show_mobile?'1':'0');
          sf.append('sort_order', i);
          const url=this.sectionSaveUrl.replace('__C__', s.component);
          const r2=await fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},body:sf});
          if(!r2.ok && r2.status!==302) throw new Error('section '+s.component+' http '+r2.status);
        }
        this.status = mode==='publish' ? 'published' : 'draft';
      }catch(e){ this.status='error'; }
    },
  };
}
</script>
