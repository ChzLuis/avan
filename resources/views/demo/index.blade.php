<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Crear mi demo gratis — BIXO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
[x-cloak]{display:none!important}
.rubro-card.selected { border-color:#4f46e5!important; background:#ede9fe!important; }
.rubro-card.selected .rubro-check { opacity:1!important; }
.feature-card.selected { border-color:#4f46e5!important; background:#ede9fe!important; }
.step-line { height:2px; background:#e5e7eb; flex:1; }
.step-line.done { background:#4f46e5; }
</style>
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="demoForm()" x-cloak>

{{-- HEADER --}}
<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
      </div>
      <span class="font-bold text-gray-900 text-lg">BIXO</span>
    </div>
    <span class="text-sm text-gray-500">Demo gratuita por 14 días</span>
  </div>
</header>

<div class="max-w-3xl mx-auto px-4 py-10">

  {{-- TÍTULO --}}
  <div class="text-center mb-10">
    <h1 class="text-3xl md:text-4xl font-black text-gray-900 leading-tight">
      Crea tu demo en <span class="text-indigo-600">30 segundos</span>
    </h1>
    <p class="text-gray-500 mt-3 text-lg">Sin tarjeta de crédito · Sin instalaciones · Todo listo al instante</p>
  </div>

  {{-- STEPS INDICATOR --}}
  <div class="flex items-center gap-2 mb-10 max-w-sm mx-auto">
    <div class="flex flex-col items-center gap-1">
      <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
           :class="step >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">1</div>
      <span class="text-xs text-gray-500">Rubro</span>
    </div>
    <div class="step-line mb-4" :class="step > 1 ? 'done' : ''"></div>
    <div class="flex flex-col items-center gap-1">
      <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
           :class="step >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">2</div>
      <span class="text-xs text-gray-500">Módulos</span>
    </div>
    <div class="step-line mb-4" :class="step > 2 ? 'done' : ''"></div>
    <div class="flex flex-col items-center gap-1">
      <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
           :class="step >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">3</div>
      <span class="text-xs text-gray-500">Datos</span>
    </div>
  </div>

  {{-- STEP 1: RUBRO --}}
  <div x-show="step === 1">
    <h2 class="text-xl font-bold text-gray-900 mb-2 text-center">¿Qué tipo de negocio tienes?</h2>
    <p class="text-gray-500 text-center mb-6">Elige el rubro para ver las funciones que más te sirven</p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      @foreach($rubros as $key => $rubro)
      <button type="button"
        class="rubro-card relative border-2 border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50 transition-all"
        :class="selectedRubro === '{{ $key }}' ? 'selected' : ''"
        @click="selectRubro('{{ $key }}')">
        <div class="absolute top-2 right-2 rubro-check opacity-0 transition-opacity">
          <div class="w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center">
            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
        </div>
        <div class="text-3xl mb-2">{{ $rubro['emoji'] }}</div>
        <div class="text-sm font-600 text-gray-800 leading-tight">{{ $rubro['label'] }}</div>
      </button>
      @endforeach
    </div>

    <div class="mt-8 text-center">
      <button type="button" @click="goToStep2()"
        :disabled="!selectedRubro"
        class="inline-flex items-center gap-2 bg-indigo-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
        Continuar
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>
  </div>

  {{-- STEP 2: FEATURES --}}
  <div x-show="step === 2">
    <h2 class="text-xl font-bold text-gray-900 mb-2 text-center">¿Qué necesitas?</h2>
    <p class="text-gray-500 text-center mb-6">Las marcadas por defecto son las más usadas en tu rubro</p>

    <div x-show="loadingFeatures" class="text-center py-10 text-gray-400">
      <svg class="w-8 h-8 animate-spin mx-auto mb-2" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
      Cargando opciones...
    </div>

    <div x-show="!loadingFeatures" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <template x-for="feature in features" :key="feature.key">
        <div class="feature-card border-2 border-gray-200 rounded-xl p-4 cursor-pointer transition-all"
             :class="[
               isSelected(feature.key) ? 'selected' : '',
               !feature.available ? 'opacity-60' : 'hover:border-indigo-300 hover:bg-indigo-50'
             ]"
             @click="feature.available && toggleFeature(feature)">
          <div class="flex items-start gap-3">
            <div class="mt-0.5 w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                 :class="isSelected(feature.key) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'">
              <svg x-show="isSelected(feature.key)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-600 text-gray-800" x-text="feature.label"></span>
                <span x-show="!feature.available"
                  class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Próximamente</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <div class="mt-8 flex items-center justify-between">
      <button type="button" @click="step = 1"
        class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Atrás
      </button>
      <button type="button" @click="step = 3"
        :disabled="selectedFeatures.length === 0"
        class="inline-flex items-center gap-2 bg-indigo-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
        Continuar
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>
  </div>

  {{-- STEP 3: DATOS --}}
  <div x-show="step === 3">
    <h2 class="text-xl font-bold text-gray-900 mb-2 text-center">Tus datos</h2>
    <p class="text-gray-500 text-center mb-6">Recibirás tus accesos al instante en tu correo</p>

    <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
      <div>
        <label class="block text-sm font-600 text-gray-700 mb-1">Nombre de tu negocio *</label>
        <input type="text" x-model="form.business_name" placeholder="Ej: Restaurante El Buen Sabor"
          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        <p x-show="errors.business_name" class="text-red-500 text-xs mt-1" x-text="errors.business_name"></p>
      </div>
      <div>
        <label class="block text-sm font-600 text-gray-700 mb-1">Tu nombre completo *</label>
        <input type="text" x-model="form.contact_name" placeholder="Ej: Juan Pérez"
          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        <p x-show="errors.contact_name" class="text-red-500 text-xs mt-1" x-text="errors.contact_name"></p>
      </div>
      <div>
        <label class="block text-sm font-600 text-gray-700 mb-1">Correo electrónico *</label>
        <input type="email" x-model="form.email" placeholder="tu@correo.com"
          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        <p x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-600 text-gray-700 mb-1">Teléfono</label>
          <input type="tel" x-model="form.phone" placeholder="987654321"
            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-600 text-gray-700 mb-1">WhatsApp</label>
          <input type="tel" x-model="form.whatsapp" placeholder="987654321"
            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
    </div>

    {{-- Resumen --}}
    <div class="mt-4 bg-indigo-50 border border-indigo-200 rounded-xl p-4">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-indigo-700 font-700 text-sm">Resumen de tu demo:</span>
      </div>
      <div class="text-sm text-indigo-800 space-y-1">
        <div>📋 Rubro: <strong x-text="rubroLabel"></strong></div>
        <div>✅ Módulos seleccionados: <strong x-text="selectedFeatures.length"></strong></div>
        <div>⏰ Válida por 14 días gratis</div>
      </div>
    </div>

    <p x-show="errors.general" class="mt-3 text-red-600 text-sm text-center font-medium" x-text="errors.general"></p>

    <div class="mt-6 flex items-center justify-between">
      <button type="button" @click="step = 2"
        class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Atrás
      </button>
      <button type="button" @click="submit()"
        :disabled="submitting"
        class="inline-flex items-center gap-2 bg-indigo-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-indigo-700 transition disabled:opacity-60">
        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <span x-text="submitting ? 'Creando tu demo...' : '🚀 Crear mi demo gratis'"></span>
      </button>
    </div>
  </div>

</div>

{{-- MODAL ÉXITO --}}
<div x-show="success" x-cloak
  class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 px-4"
  x-transition:enter="transition ease-out duration-200"
  x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
    <div class="text-6xl mb-4">🎉</div>
    <h2 class="text-2xl font-black text-gray-900 mb-2">¡Tu demo está lista!</h2>
    <p class="text-gray-500 mb-6">Te enviamos los accesos a <strong x-text="result.email"></strong></p>

    <div class="bg-gray-50 rounded-xl p-4 text-left space-y-2 mb-6">
      <div class="flex justify-between text-sm">
        <span class="text-gray-500 font-medium">Email:</span>
        <span class="font-mono font-bold text-gray-900" x-text="result.email"></span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-500 font-medium">Contraseña:</span>
        <span class="font-mono font-bold text-gray-900" x-text="result.password"></span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-500 font-medium">Expira:</span>
        <span class="font-bold text-amber-700" x-text="result.expires_at"></span>
      </div>
    </div>

    <a :href="result.login_url"
      class="block w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition text-center">
      👉 Ir a mi demo ahora
    </a>
    <p class="text-xs text-gray-400 mt-4">Guarda bien tu contraseña. También te la enviamos por correo.</p>
  </div>
</div>

<script>
function demoForm() {
  return {
    step: 1,
    selectedRubro: '',
    rubroLabel: '',
    features: [],
    selectedFeatures: [],
    loadingFeatures: false,
    form: { business_name: '', contact_name: '', email: '', phone: '', whatsapp: '' },
    errors: {},
    submitting: false,
    success: false,
    result: {},

    async selectRubro(key) {
      this.selectedRubro = key;
    },

    async goToStep2() {
      if (!this.selectedRubro) return;
      this.step = 2;
      this.loadingFeatures = true;
      this.features = [];
      this.selectedFeatures = [];
      try {
        const res = await fetch(`/demo/features?rubro=${this.selectedRubro}`);
        const data = await res.json();
        if (data.ok) {
          this.features = data.features;
          // Pre-seleccionar los defaults disponibles
          this.selectedFeatures = data.features
            .filter(f => f.default && f.available)
            .map(f => ({ key: f.key, label: f.label }));
          // Guardar label del rubro
          const card = document.querySelector(`[\\@click="selectRubro('${this.selectedRubro}')"]`);
          this.rubroLabel = this.selectedRubro;
        }
      } catch (e) {}
      this.loadingFeatures = false;
    },

    isSelected(key) {
      return this.selectedFeatures.some(f => f.key === key);
    },

    toggleFeature(feature) {
      if (!feature.available) return;
      const idx = this.selectedFeatures.findIndex(f => f.key === feature.key);
      if (idx >= 0) {
        this.selectedFeatures.splice(idx, 1);
      } else {
        this.selectedFeatures.push({ key: feature.key, label: feature.label });
      }
    },

    async submit() {
      this.errors = {};
      if (!this.form.business_name) { this.errors.business_name = 'Requerido'; return; }
      if (!this.form.contact_name)  { this.errors.contact_name  = 'Requerido'; return; }
      if (!this.form.email)         { this.errors.email         = 'Requerido'; return; }

      this.submitting = true;
      try {
        const res = await fetch('/demo', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            business_name:     this.form.business_name,
            rubro:             this.selectedRubro,
            features_selected: this.selectedFeatures.map(f => f.label),
            contact_name:      this.form.contact_name,
            email:             this.form.email,
            phone:             this.form.phone,
            whatsapp:          this.form.whatsapp,
          }),
        });
        const data = await res.json();
        if (data.ok) {
          this.result = data;
          this.success = true;
        } else {
          this.errors.general = data.message || 'Ocurrió un error. Inténtalo de nuevo.';
        }
      } catch (e) {
        this.errors.general = 'Error de conexión. Inténtalo de nuevo.';
      }
      this.submitting = false;
    },
  };
}
</script>
</body>
</html>
