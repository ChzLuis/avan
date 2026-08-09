filepath = '/home/user_mercado/htdocs/admin.mercadosmayoristas.com.pe/resources/views/public/templates/catha.blade.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

start_marker = '{{-- ═══ TOP BAR ═══ --}}'
end_marker = '</header>'

start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx) + len(end_marker)

new_header = """{{-- TOP BAR CATHA --}}
<div style="background:#080c10;border-bottom:1px solid rgba(201,168,76,.1);" class="text-xs py-2">
  <div class="max-w-[1400px] mx-auto px-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-5">
      @if($project->phone)
      <span class="flex items-center gap-1.5 text-gray-400">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        {{ $project->phone }}
      </span>
      @endif
      @if($shippingFreeFrom > 0)
      <span class="hidden sm:inline" style="color:var(--gold)">Envio gratis desde {{ $currency }} {{ number_format($shippingFreeFrom,0) }}</span>
      @endif
    </div>
    <span class="text-gray-500 tracking-[.25em] uppercase text-[10px] hidden md:inline">Calidad &middot; Tradicion &middot; Excelencia</span>
    <div class="flex items-center gap-4 text-gray-500">
      @if($settings['instagram_url'] ?? null)
      <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener" class="hover:text-gold transition text-xs font-bold">IG</a>
      @endif
      @if($settings['facebook_url'] ?? null)
      <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" class="hover:text-gold transition text-xs font-bold">FB</a>
      @endif
      @if($settings['tiktok_url'] ?? null)
      <a href="{{ $settings['tiktok_url'] }}" target="_blank" rel="noopener" class="hover:text-gold transition text-xs font-bold">TK</a>
      @endif
    </div>
  </div>
</div>

{{-- HEADER CATHA centrado --}}
<header style="background:var(--navy2);border-bottom:1px solid rgba(201,168,76,.15);" class="sticky top-0 z-30">
  <div style="height:2px;background:linear-gradient(90deg,transparent,var(--gold),#e8c96a,var(--gold),transparent);"></div>
  <div class="max-w-[1400px] mx-auto px-4 py-4 flex flex-col items-center relative">
    <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-3">
      <div class="relative hidden md:block" @click.outside="searchOpen=false">
        <input x-model="search" type="search" placeholder="Buscar..."
               @input="searchOpen=search.trim().length>=2; searchIdx=-1; if(search.trim().length>=2) _scrollToCatalog()"
               @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
               @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
               @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
               @keydown.escape="searchOpen=false;searchIdx=-1"
               class="search-dark rounded-full pl-9 pr-4 py-1.5 text-sm w-40 focus:w-56 transition-all">
        <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
        <div x-show="searchOpen && suggestions.length>0" x-cloak
             style="background:var(--navy2);border:1px solid rgba(201,168,76,.2);"
             class="absolute right-0 top-full mt-1 rounded-xl shadow-2xl z-[200] overflow-hidden min-w-[280px]">
          <template x-for="(p,i) in suggestions" :key="p.id">
            <button @click="selectSuggestion(p)" :class="searchIdx===i?'bg-white/5':''"
                    class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-white/5 transition text-left border-b border-white/5 last:border-0">
              <div class="w-9 h-9 rounded-lg overflow-hidden flex-shrink-0 bg-white/5">
                <img x-show="p.img" :src="p.img" class="w-full h-full object-cover">
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-200 truncate" x-html="_highlight(p.name)"></p>
                <p class="text-xs text-gray-500" x-text="p.cat"></p>
              </div>
              <p class="text-sm font-bold text-gold flex-shrink-0" x-text="'{{ $currency }} '+p.price.toFixed(2)"></p>
            </button>
          </template>
        </div>
      </div>
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
         class="hidden sm:flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full transition btn-gold">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
        Pedidos
      </a>
      @endif
      <button @click="drawerOpen=true" class="relative p-2 rounded-full hover:bg-white/10 transition" aria-label="Ver carrito">
        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span x-show="cartCount>0" x-text="cartCount"
              class="absolute -top-0.5 -right-0.5 text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center text-gray-900 bg-gold"></span>
      </button>
    </div>
    <a href="{{ $canonicalUrl }}" class="flex flex-col items-center gap-1">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-14 object-contain">
      @else
        <span class="serif font-bold text-3xl leading-none tracking-[6px] uppercase" style="color:var(--gold)">{{ $project->name }}</span>
      @endif
      <span class="text-[9px] tracking-[.35em] uppercase text-gray-500">{{ $settings['footer_tagline'] ?? 'Calidad y Tradicion' }}</span>
    </a>
    <div class="w-24 h-px mt-2" style="background:linear-gradient(90deg,transparent,var(--gold),transparent);"></div>
  </div>
  <nav style="border-top:1px solid rgba(201,168,76,.08);" class="hidden lg:block">
    <div class="max-w-[1400px] mx-auto px-4 flex items-center justify-center">
      <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='' ? 'text-gold border-b-2 border-gold' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent'"
              class="px-5 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap transition">Todo</button>
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='{{ $cat->id }}' ? 'text-gold border-b-2 border-gold' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent'"
              class="px-5 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap transition">{{ $cat->name }}</button>
      @endforeach
    </div>
  </nav>
  <div class="lg:hidden flex gap-1 px-4 pb-2 pt-1 no-scroll overflow-x-auto" style="border-top:1px solid rgba(201,168,76,.08);">
    <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            :class="filterCat===''?'bg-gold text-gray-900':'bg-white/5 text-gray-400'"
            class="flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition">Todo</button>
    @foreach($categories as $cat)
    <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            :class="filterCat==='{{ $cat->id }}'?'bg-gold text-gray-900':'bg-white/5 text-gray-400'"
            class="flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition">{{ $cat->name }}</button>
    @endforeach
  </div>
</header>"""

# Reemplazar footer tambien
footer_old = """{{-- ═══ FOOTER ═══ --}}"""
footer_new_start = content.find('{{-- ═══ FOOTER ═══ --}}')
footer_end_marker = '</footer>'
footer_end_idx = content.find(footer_end_marker, footer_new_start) + len(footer_end_marker)

new_footer = """{{-- FOOTER CATHA --}}
<footer style="background:#080c10;border-top:1px solid rgba(201,168,76,.15);">
  <div style="height:2px;background:linear-gradient(90deg,transparent,var(--gold),#e8c96a,var(--gold),transparent);"></div>
  <div class="max-w-[1400px] mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-3 gap-12">
    {{-- Columna marca --}}
    <div class="flex flex-col items-start gap-4">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-12 object-contain opacity-90">
      @else
        <span class="serif font-bold text-2xl tracking-[5px] uppercase" style="color:var(--gold)">{{ $project->name }}</span>
      @endif
      <div class="w-16 h-px" style="background:linear-gradient(90deg,var(--gold),transparent);"></div>
      <p class="text-gray-500 text-sm leading-relaxed max-w-xs">{{ $footerTagline }}</p>
      <div class="flex items-center gap-4 mt-2">
        @if($settings['instagram_url'] ?? null)
        <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener"
           class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-gold hover:text-gray-900 transition text-gray-500"
           style="border:1px solid rgba(201,168,76,.3);">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
        </a>
        @endif
        @if($settings['facebook_url'] ?? null)
        <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener"
           class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-gold hover:text-gray-900 transition text-gray-500"
           style="border:1px solid rgba(201,168,76,.3);">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </a>
        @endif
        @if($settings['tiktok_url'] ?? null)
        <a href="{{ $settings['tiktok_url'] }}" target="_blank" rel="noopener"
           class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-gold hover:text-gray-900 transition text-gray-500 text-xs font-bold"
           style="border:1px solid rgba(201,168,76,.3);">TK</a>
        @endif
      </div>
    </div>

    {{-- Columna navegacion --}}
    <div>
      <h4 class="text-[10px] font-bold uppercase tracking-[.25em] mb-5" style="color:var(--gold)">Categorias</h4>
      <ul class="space-y-2">
        <li>
          <button @click="filterCat=''; window.scrollTo({top:0,behavior:'smooth'})"
                  class="text-gray-400 hover:text-gold transition text-sm">Ver todo</button>
        </li>
        @foreach($categories->take(6) as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-gray-400 hover:text-gold transition text-sm text-left">{{ $cat->name }}</button>
        </li>
        @endforeach
      </ul>
    </div>

    {{-- Columna contacto --}}
    <div>
      <h4 class="text-[10px] font-bold uppercase tracking-[.25em] mb-5" style="color:var(--gold)">Contacto</h4>
      <ul class="space-y-3 text-sm text-gray-400">
        @if($project->phone)
        <li class="flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          {{ $project->phone }}
        </li>
        @endif
        @if($project->email ?? null)
        <li class="flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          {{ $project->email }}
        </li>
        @endif
        @if($project->whatsapp)
        <li>
          <a href="https://wa.me/{{ preg_replace('/\\D/','',$project->whatsapp) }}" target="_blank"
             class="inline-flex items-center gap-2 font-semibold transition hover:opacity-80" style="color:var(--gold)">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
            WhatsApp
          </a>
        </li>
        @endif
      </ul>
    </div>
  </div>

  {{-- Bottom bar --}}
  <div style="border-top:1px solid rgba(201,168,76,.1);">
    <div class="max-w-[1400px] mx-auto px-6 py-4 flex items-center justify-between flex-wrap gap-2">
      <p class="text-xs text-gray-600">{{ $footerCopyright }}</p>
      <p class="text-xs text-gray-700">Powered by <span style="color:var(--gold)" class="font-semibold">BIXO</span></p>
    </div>
  </div>
</footer>"""

if footer_new_start != -1:
    content_after_header = content[:start_idx] + new_header + content[end_idx:]
    footer_start2 = content_after_header.find('{{-- ═══ FOOTER ═══ --}}')
    footer_end2 = content_after_header.find('</footer>', footer_start2) + len('</footer>')
    final_content = content_after_header[:footer_start2] + new_footer + content_after_header[footer_end2:]
else:
    final_content = content[:start_idx] + new_header + content[end_idx:]

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(final_content)
print('OK - ' + str(len(final_content.split('\n'))) + ' lineas')
