<x-portal-layout layout="panel" :project="$project" pageTitle="Copilot">

<div class="max-w-3xl mx-auto p-6 flex flex-col" style="height:calc(100vh - 56px)"
     x-data="copilotChat()">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3 mb-4 flex-shrink-0">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg font-bold flex-shrink-0"
             style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">B</div>
        <div>
            <h1 class="text-lg font-bold text-gray-800">Copilot de {{ $project->name }}</h1>
            <p class="text-xs text-gray-500">Pregúntale a tu negocio en español. Responde con tus datos reales.</p>
        </div>
    </div>

    {{-- Chat --}}
    <div class="flex-1 overflow-y-auto space-y-3 mb-3 pr-1" x-ref="chat">
        <template x-if="mensajes.length === 0">
            <div class="text-center text-gray-400 mt-10">
                <div class="text-3xl mb-2">💬</div>
                <p class="text-sm">Escribe una pregunta o prueba una de abajo.</p>
            </div>
        </template>
        <template x-for="(m,i) in mensajes" :key="i">
            <div :class="m.from==='user' ? 'flex justify-end' : 'flex'">
                <div :class="m.from==='user' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-800'"
                     class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm whitespace-pre-wrap" x-text="m.texto"></div>
            </div>
        </template>
        <div x-show="pensando" class="flex">
            <div class="bg-white border border-gray-200 rounded-2xl px-4 py-2.5 text-sm text-gray-400">pensando…</div>
        </div>
    </div>

    {{-- Preguntas sugeridas --}}
    <div class="flex gap-2 flex-wrap mb-2 flex-shrink-0" x-show="mensajes.length === 0">
        <template x-for="s in sugeridas" :key="s">
            <button @click="pregunta=s; enviar()"
                    class="text-xs px-3 py-1.5 rounded-full border border-gray-200 bg-white hover:border-indigo-400 text-gray-600" x-text="s"></button>
        </template>
    </div>

    {{-- Input --}}
    <div class="flex gap-2 flex-shrink-0">
        <input x-model="pregunta" @keydown.enter="enviar()" placeholder="Ej: ¿Cuánto vendí este mes?"
               class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500">
        <button @click="enviar()" :disabled="pensando"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl disabled:opacity-50">Preguntar</button>
    </div>
</div>

<script>
function copilotChat() {
  return {
    pregunta: '',
    mensajes: [],
    pensando: false,
    sugeridas: [
      '¿Cuánto vendí este mes?',
      '¿Qué productos están por agotarse?',
      'Dame un resumen del negocio',
    ],
    async enviar() {
      const q = this.pregunta.trim();
      if (!q || this.pensando) return;
      this.mensajes.push({ from: 'user', texto: q });
      this.pregunta = '';
      this.pensando = true;
      this.$nextTick(() => this.$refs.chat.scrollTop = 9e9);
      try {
        const res = await fetch('{{ route('copilot.preguntar') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ pregunta: q }),
        });
        const d = await res.json();
        this.mensajes.push({ from: 'bot', texto: d.respuesta || 'No pude responder.' });
      } catch (e) {
        this.mensajes.push({ from: 'bot', texto: 'Error de conexión.' });
      }
      this.pensando = false;
      this.$nextTick(() => this.$refs.chat.scrollTop = 9e9);
    },
  };
}
</script>
</x-portal-layout>
