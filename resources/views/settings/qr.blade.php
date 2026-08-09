<x-app-layout>
<x-slot name="slot">
@php
    $value = fn ($key, $default = '') => $storefrontContext->setting($key, $default);
    $logo = $value('logo_url') ? asset('storage/' . $value('logo_url')) : null;
    $primary = $value('primary_color', '#e85d04');
    $isRestaurant = \App\Support\BusinessTerms::usaMesas($project);
    $days = ['monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miércoles', 'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sábado', 'sunday' => 'Domingo'];
    $savedSchedule = json_decode($value('qr_schedule', '{}'), true) ?: [];
@endphp

<div x-data="storeQr()" x-init="init()" class="min-h-full bg-slate-50 pb-24">
  <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 lg:px-8">
      <div>
        <h1 class="text-base font-bold text-slate-900">Código QR de mi tienda</h1>
        <p class="mt-0.5 text-xs text-slate-500">Personaliza y descarga el código QR que llevará a tus clientes directamente a tu catálogo.</p>
      </div>
      <button @click="save" :disabled="saving" class="hidden rounded-xl px-4 py-2 text-sm font-bold text-white shadow-sm transition disabled:opacity-60 md:inline-flex" :style="`background:${form.header}`">
        <span x-text="saving ? 'Guardando…' : 'Guardar cambios'"></span>
      </button>
    </div>
  </header>

  <main class="qr-layout mx-auto max-w-7xl gap-5 px-4 py-5 lg:px-8">
    {{-- Vista previa --}}
    <section class="lg:sticky lg:top-20 lg:self-start">
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <div><h2 class="text-sm font-bold text-slate-800">Vista previa</h2><p class="text-xs text-slate-500">Así lo verán tus clientes</p></div>
          <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="publicUrl ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" x-text="publicUrl ? 'Enlace público' : 'Dominio pendiente'"></span>
        </div>
        <div class="bg-slate-100 p-4 sm:p-7">
          <div id="qr-card" class="mx-auto max-w-sm overflow-hidden rounded-2xl bg-white shadow-xl" :style="`--header:${form.header};--qrbg:${form.bg}`">
            <div class="px-6 pb-8 pt-6 text-center text-white" style="background:var(--header)">
              <template x-if="form.showLogo && logo"><img :src="logo" class="mx-auto mb-3 max-h-12 max-w-40 object-contain" alt="Logo del negocio"></template>
              <p x-show="!form.showLogo || !logo" class="text-lg font-black" x-text="name"></p>
              <p class="mt-2 text-sm font-medium text-white/90" x-text="form.topText"></p>
            </div>
            <div class="relative mx-5 -mt-5 rounded-2xl bg-white p-4 text-center shadow-lg">
              <template x-if="publicUrl"><img :src="qrUrl(600, 'png')" class="mx-auto aspect-square w-full max-w-56 rounded-lg" alt="Código QR de la tienda"></template>
              <template x-if="!publicUrl"><div class="mx-auto flex aspect-square w-full max-w-56 flex-col items-center justify-center rounded-lg border-2 border-dashed border-amber-200 bg-amber-50 px-5 text-center text-xs font-medium text-amber-800"><span class="mb-2 text-2xl">⌁</span>Configura un dominio público para generar tu QR</div></template>
            </div>
            <div class="px-6 pb-6 pt-4 text-center">
              <p class="text-sm font-bold text-slate-800" x-text="form.bottomText"></p>
              <p x-show="form.showUrl" class="mt-2 truncate font-mono text-[11px] text-slate-500" x-text="shortUrl"></p>
              <p class="mt-4 text-[10px] text-slate-300">Tecnología de BIXO</p>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-2 border-t border-slate-100 p-3 sm:grid-cols-4">
          <button @click="download('png')" :disabled="!publicUrl" class="qr-action">PNG</button>
          <button @click="download('flyer')" :disabled="!publicUrl" class="qr-action">Flyer</button>
          <button @click="share" :disabled="!publicUrl" class="qr-action">Compartir</button>
          <button @click="testQr" :disabled="!publicUrl" class="qr-action">Probar QR</button>
        </div>
      </div>
      <div x-show="!publicUrl" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
        Necesitas configurar un dominio público para generar un QR descargable. Las direcciones locales no se pueden compartir con tus clientes.
      </div>
      <div x-show="contrastWarning" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" x-text="contrastWarning"></div>
    </section>

    {{-- Configuración --}}
    <section class="space-y-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Información</h2>
        <p class="mt-1 text-xs text-slate-500">Este enlace se genera automáticamente desde tu tienda pública.</p>
        <div class="mt-3 flex gap-2">
          <input readonly :value="publicUrl || 'Dominio público pendiente'" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
          <button @click="copyUrl" :disabled="!publicUrl" class="rounded-xl border border-slate-200 px-3 text-xs font-bold text-slate-700 disabled:opacity-40">Copiar</button>
          <a x-show="publicUrl" :href="publicUrl" target="_blank" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">Abrir</a>
        </div>
      </div>

      <details open class="qr-panel"><summary>Diseño <span>Personaliza colores y tamaño</span></summary>
        <div class="grid gap-4 pt-4 sm:grid-cols-2">
          <label class="qr-field">Tamaño <small x-text="form.size + ' px'"></small><input x-model.number="form.size" type="range" min="160" max="1200" step="20"></label>
          <label class="qr-field">Margen <small x-text="form.margin + ' módulos'"></small><input x-model.number="form.margin" type="range" min="0" max="12"></label>
          <label class="qr-field">Color del QR<input x-model="form.fg" type="color"></label>
          <label class="qr-field">Color de fondo<input x-model="form.bg" type="color"></label>
          <label class="qr-field">Color del encabezado<input x-model="form.header" type="color"></label>
          <label class="qr-field">Calidad<select x-model="form.quality"><option value="standard">Estándar</option><option value="high">Alta resolución</option></select></label>
        </div>
        <p class="mt-3 text-xs text-slate-500">El QR usa corrección alta de errores y conserva sus patrones de escaneo.</p>
        <div class="mt-4 flex flex-wrap gap-2">
          <template x-for="preset in presets" :key="preset.key"><button @click="applyPreset(preset)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700" x-text="preset.label"></button></template>
          <button @click="reset" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600">Restablecer diseño</button>
        </div>
      </details>

      <details class="qr-panel"><summary>Texto <span>Mensajes que acompañan al QR</span></summary>
        <div class="space-y-3 pt-4">
          <label class="qr-field">Texto superior<input x-model="form.topText" maxlength="120" placeholder="Escanea y visita nuestra tienda"></label>
          <label class="qr-field">Texto inferior<input x-model="form.bottomText" maxlength="120" placeholder="Realiza tu pedido por WhatsApp"></label>
          <label class="flex items-center gap-2 text-sm text-slate-700"><input x-model="form.showLogo" type="checkbox"> Mostrar logo del negocio en el encabezado</label>
          <label class="flex items-center gap-2 text-sm text-slate-700"><input x-model="form.showUrl" type="checkbox"> Mostrar URL corta en el material</label>
        </div>
      </details>

      <details class="qr-panel"><summary>Descarga y compartir <span>Material listo para imprimir o redes</span></summary>
        <div class="grid grid-cols-2 gap-2 pt-4 sm:grid-cols-3">
          <button @click="download('png')" :disabled="!publicUrl" class="qr-download">Solo QR PNG</button>
          <button @click="download('svg')" :disabled="!publicUrl" class="qr-download">QR SVG</button>
          <button @click="download('flyer')" :disabled="!publicUrl" class="qr-download">Flyer vertical</button>
          <button @click="download('square')" :disabled="!publicUrl" class="qr-download">Tarjeta redes</button>
          <button @click="download('a4')" :disabled="!publicUrl" class="qr-download">A4 imprimir</button>
          <button @click="share" :disabled="!publicUrl" class="qr-download">WhatsApp</button>
        </div>
        <label class="qr-field mt-4">Mensaje para compartir<textarea x-model="form.shareMessage" rows="3"></textarea></label>
      </details>

      @if($isRestaurant)
      <details class="qr-panel"><summary>Opciones para QR por mesa <span>Configuración existente para restaurantes</span></summary>
        <div class="grid gap-3 pt-4 sm:grid-cols-2">
          <label class="qr-field">Experiencia<select x-model="form.mode"><option value="catalog">Solo carta</option><option value="orders">Carta y pedidos</option></select></label>
          <label class="qr-field">Cantidad de mesas<input x-model.number="form.tableCount" min="1" max="50" type="number"></label>
          <label class="qr-field">Recepción<select x-model="form.reception"><option value="auto">Automática</option><option value="manual">Manual</option></select></label>
          <label class="qr-field">Cobro<select x-model="form.payment"><option value="cashier">En caja</option><option value="waiter">Con mozo</option></select></label>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-4">
          <p class="text-xs font-bold text-slate-700">Horario de QR por mesa</p>
          <p class="mt-1 text-xs text-slate-500">Se conserva la configuración que ya usabas para activar pedidos.</p>
          <div class="mt-3 grid gap-2 sm:grid-cols-2">
            @foreach($days as $day => $label)
              @php($hours = $savedSchedule[$day] ?? ['enabled' => false, 'start' => '09:00', 'end' => '22:00'])
              <div class="flex items-center gap-2 rounded-lg bg-slate-50 p-2 text-xs">
                <label class="flex min-w-20 items-center gap-1.5 font-semibold text-slate-700"><input name="qr_schedule[{{ $day }}][enabled]" value="1" type="checkbox" @checked(!empty($hours['enabled']))>{{ $label }}</label>
                <input name="qr_schedule[{{ $day }}][start]" value="{{ $hours['start'] ?? '09:00' }}" type="time" class="min-w-0 rounded border-slate-200 px-1 py-1 text-xs">
                <span class="text-slate-400">–</span>
                <input name="qr_schedule[{{ $day }}][end]" value="{{ $hours['end'] ?? '22:00' }}" type="time" class="min-w-0 rounded border-slate-200 px-1 py-1 text-xs">
              </div>
            @endforeach
          </div>
        </div>
      </details>
      @endif
    </section>
  </main>

  <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white p-3 md:hidden"><button @click="save" :disabled="saving" class="w-full rounded-xl py-3 text-sm font-bold text-white" :style="`background:${form.header}`" x-text="saving ? 'Guardando…' : 'Guardar cambios'"></button></div>
</div>

<style>
.qr-layout{display:grid}.qr-layout>*{min-width:0}@media (min-width:1024px){.qr-layout{grid-template-columns:minmax(330px,.9fr) minmax(420px,1.1fr)}}
.qr-panel{border:1px solid #e2e8f0;border-radius:16px;background:#fff;padding:16px;box-shadow:0 1px 2px rgb(15 23 42/.04)}
.qr-panel summary{cursor:pointer;list-style:none;font-size:14px;font-weight:700;color:#1e293b}.qr-panel summary::-webkit-details-marker{display:none}.qr-panel summary span{display:block;margin-top:3px;font-size:12px;font-weight:400;color:#64748b}
.qr-field{display:flex;flex-direction:column;gap:6px;font-size:12px;font-weight:700;color:#334155}.qr-field small{font-weight:500;color:#64748b}.qr-field input:not([type=color]):not([type=checkbox]),.qr-field select,.qr-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:9px;font-size:13px;font-weight:400;color:#334155}.qr-field input[type=color]{height:38px;width:100%;cursor:pointer;border:1px solid #cbd5e1;border-radius:10px;padding:3px}.qr-field input[type=range]{accent-color:var(--header,#e85d04)}
.qr-action,.qr-download{border:1px solid #e2e8f0;border-radius:10px;padding:9px;font-size:12px;font-weight:700;color:#334155;transition:.15s}.qr-action:hover,.qr-download:hover{background:#f8fafc}.qr-action:disabled,.qr-download:disabled{cursor:not-allowed;opacity:.4}
</style>

<script>
const QR_URL = @json($baseUrl); const QR_PUBLIC = @json($isPublicUrl); const QR_LOGO = @json($logo); const QR_NAME = @json($project->name); const QR_CSRF = document.querySelector('meta[name="csrf-token"]').content;
function storeQr(){return {publicUrl:QR_PUBLIC?QR_URL:'',logo:QR_LOGO,name:QR_NAME,saving:false,contrastWarning:'',presets:[{key:'classic',label:'Clásico',fg:'#111827',bg:'#ffffff',header:'#334155'},{key:'brand',label:'Colores de marca',fg:'#111827',bg:'#ffffff',header:@json($primary)},{key:'orange',label:'Naranja',fg:'#3b1d00',bg:'#ffffff',header:'#ea580c'},{key:'dark',label:'Oscuro',fg:'#ffffff',bg:'#111827',header:'#111827'},{key:'minimal',label:'Minimalista',fg:'#000000',bg:'#ffffff',header:'#ffffff'}],form:{size:+@json($value('qr_size', 600)),margin:+@json($value('qr_margin', 2)),fg:@json($value('qr_foreground', '#111827')),bg:@json($value('qr_background', '#ffffff')),header:@json($value('qr_header_color', $primary)),topText:@json($value('qr_top_text', 'Escanea y visita nuestra tienda')),bottomText:@json($value('qr_bottom_text', 'Escanea y mira nuestros productos')),showLogo:@json($value('qr_show_logo', '1') === '1'),showUrl:@json($value('qr_show_url', '1') === '1'),quality:@json($value('qr_quality', 'standard')),preset:@json($value('qr_preset', 'brand')),shareMessage:@json($value('qr_share_message', '¡Hola! Te compartimos nuestra tienda: ') . ' ' . $baseUrl),mode:@json($value('qr_mode','catalog')),tableCount:+@json($value('qr_table_count',10)),reception:@json($value('qr_reception','auto')),payment:@json($value('qr_payment','cashier'))},init(){this.$watch('form.fg',()=>this.checkContrast());this.$watch('form.bg',()=>this.checkContrast());this.checkContrast()},get shortUrl(){return this.publicUrl.replace(/^https?:\/\//,'')},qrUrl(size,format){if(!this.publicUrl)return '';return 'https://api.qrserver.com/v1/create-qr-code/?size='+size+'x'+size+'&data='+encodeURIComponent(this.publicUrl)+'&color='+this.form.fg.slice(1)+'&bgcolor='+this.form.bg.slice(1)+'&margin='+this.form.margin+'&ecc=H&format='+format},checkContrast(){const c=x=>{x=x.slice(1);let a=[0,2,4].map(i=>parseInt(x.slice(i,i+2),16)/255).map(v=>v<=.03928?v/12.92:((v+.055)/1.055)**2.4);return .2126*a[0]+.7152*a[1]+.0722*a[2]};let ratio=(Math.max(c(this.form.fg),c(this.form.bg))+.05)/(Math.min(c(this.form.fg),c(this.form.bg))+.05);this.contrastWarning=ratio<4?'El contraste es bajo; usa colores más distintos para asegurar que el QR se escanee bien.':''},applyPreset(p){Object.assign(this.form,{fg:p.fg,bg:p.bg,header:p.header,preset:p.key});this.checkContrast()},reset(){Object.assign(this.form,{size:600,margin:2,fg:'#111827',bg:'#ffffff',header:@json($primary),topText:'Escanea y visita nuestra tienda',bottomText:'Escanea y mira nuestros productos',showLogo:true,showUrl:true,quality:'standard',preset:'brand'});this.checkContrast()},async copyUrl(){try{await navigator.clipboard.writeText(this.publicUrl);toast('URL copiada','success')}catch(e){toast('No se pudo copiar la URL','error')}},testQr(){window.open(this.publicUrl,'_blank','noopener');toast('Abrimos tu tienda para comprobar el enlace','success')},share(){let text=encodeURIComponent(this.form.shareMessage.replace(QR_URL,this.publicUrl));window.open('https://wa.me/?text='+text,'_blank','noopener')},async download(type){if(!this.publicUrl)return;try{if(type==='svg'||type==='png'){let blob=await fetch(this.qrUrl(this.form.quality==='high'?1200:600,type)).then(r=>r.blob());downloadBlob(blob,`${slug(this.name)}-qr.${type}`);toast('Descarga lista','success');return}let canvas=await this.material(type);canvas.toBlob(b=>{downloadBlob(b,`${slug(this.name)}-${type}.png`);toast('Descarga lista','success')},'image/png',1)}catch(e){toast('No se pudo generar la descarga','error')}},async material(type){let sizes={flyer:[1080,1350],square:[1080,1080],a4:[2480,3508]},[w,h]=sizes[type]||sizes.flyer,c=document.createElement('canvas'),ctx=c.getContext('2d'),scale=this.form.quality==='high'?1:0.6;c.width=w*scale;c.height=h*scale;ctx.scale(scale,scale);ctx.fillStyle=this.form.bg;ctx.fillRect(0,0,w,h);ctx.fillStyle=this.form.header;ctx.fillRect(0,0,w,Math.round(h*.28));ctx.fillStyle='#fff';ctx.textAlign='center';let logo=null;if(this.form.showLogo&&this.logo){try{logo=await loadQr(this.logo)}catch(e){}}if(logo){let ratio=Math.min(170/logo.width,70/logo.height),lw=logo.width*ratio,lh=logo.height*ratio;ctx.drawImage(logo,(w-lw)/2,45,lw,lh)}else{ctx.font='bold 52px sans-serif';ctx.fillText(this.name,w/2,105)}ctx.font='600 34px sans-serif';ctx.fillText(this.form.topText,w/2,175);let img=await loadQr(this.qrUrl(1000,'png'));let s=Math.min(w*.62,h*.48),x=(w-s)/2,y=h*.27;ctx.fillStyle='#fff';ctx.fillRect(x-30,y-30,s+60,s+60);ctx.drawImage(img,x,y,s,s);ctx.fillStyle='#1e293b';ctx.font='bold 38px sans-serif';ctx.fillText(this.form.bottomText,w/2,y+s+100);if(this.form.showUrl){ctx.fillStyle='#64748b';ctx.font='28px monospace';ctx.fillText(this.shortUrl,w/2,y+s+150)}ctx.fillStyle='#94a3b8';ctx.font='22px sans-serif';ctx.fillText('Escanea y mira nuestros productos · BIXO',w/2,h-55);return c},async save(){this.saving=true;try{let f=new FormData();f.append('_token',QR_CSRF);Object.entries({qr_mode:this.form.mode,qr_table_count:this.form.tableCount,qr_reception:this.form.reception,qr_payment:this.form.payment,qr_size:this.form.size,qr_margin:this.form.margin,qr_foreground:this.form.fg,qr_background:this.form.bg,qr_header_color:this.form.header,qr_top_text:this.form.topText,qr_bottom_text:this.form.bottomText,qr_share_message:this.form.shareMessage,qr_preset:this.form.preset,qr_quality:this.form.quality,qr_show_logo:+this.form.showLogo,qr_show_url:+this.form.showUrl}).forEach(([k,v])=>f.append(k,v));document.querySelectorAll('[name^="qr_schedule["]').forEach(i=>{if(i.type!=='checkbox'||i.checked)f.append(i.name,i.value)});let r=await fetch(@json(route('settings.qr.save')),{method:'POST',headers:{'X-CSRF-TOKEN':QR_CSRF,Accept:'application/json'},body:f}),d=await r.json();if(!r.ok||!d.ok)throw Error();toast('Cambios guardados','success')}catch(e){toast('No se pudieron guardar los cambios','error')}finally{this.saving=false}}}}
function slug(s){return s.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')};function downloadBlob(b,n){let a=document.createElement('a');a.href=URL.createObjectURL(b);a.download=n;a.click();setTimeout(()=>URL.revokeObjectURL(a.href),500)};function loadQr(s){return new Promise((ok,no)=>{let i=new Image;i.crossOrigin='anonymous';i.onload=()=>ok(i);i.onerror=no;i.src=s})};function toast(m,t){let e=document.createElement('div');e.className='fixed right-4 top-20 z-50 rounded-xl px-4 py-3 text-sm font-bold text-white shadow-lg '+(t==='success'?'bg-emerald-600':'bg-rose-600');e.textContent=m;document.body.appendChild(e);setTimeout(()=>e.remove(),2800)}
</script>
</x-slot>
</x-app-layout>
