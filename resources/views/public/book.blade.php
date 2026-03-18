<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservar cita — {{ $project->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans">

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm w-full max-w-md p-8"
         x-data="{
             form: { service_id:'', client_name:'', client_phone:'', date:'', start_time:'09:00', notes:'' },
             sent: false, sending: false,
             async submit() {
                 this.sending=true;
                 const res = await fetch('{{ route('public.book.store', $project->slug) }}', {
                     method:'POST',
                     headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                     body: JSON.stringify(this.form)
                 });
                 this.sending=false;
                 if(res.ok) { this.sent=true; }
             }
         }">

        <div class="mb-6 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold">
                {{ strtoupper(substr($project->name, 0, 2)) }}
            </div>
            <div>
                <h1 class="font-bold text-gray-900">{{ $project->name }}</h1>
                <a href="/p/{{ $project->slug }}" class="text-xs text-indigo-600">← Ver catálogo</a>
            </div>
        </div>

        <div x-show="!sent" class="space-y-4">
            <h2 class="text-xl font-bold text-gray-900">Reservar una cita</h2>
            <div>
                <label class="label">Servicio *</label>
                <select x-model="form.service_id" class="input">
                    <option value="">Selecciona un servicio</option>
                    @foreach($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->duration_min }} min) — S/ {{ number_format($service->price, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Tu nombre *</label>
                <input type="text" x-model="form.client_name" class="input" placeholder="Nombre completo">
            </div>
            <div>
                <label class="label">Teléfono *</label>
                <input type="tel" x-model="form.client_phone" class="input" placeholder="999 999 999">
            </div>
            <div>
                <label class="label">Fecha *</label>
                <input type="date" x-model="form.date" class="input" :min="new Date().toISOString().split('T')[0]">
            </div>
            <div>
                <label class="label">Hora</label>
                <input type="time" x-model="form.start_time" class="input">
            </div>
            <div>
                <label class="label">Notas</label>
                <textarea x-model="form.notes" class="input resize-none" rows="2" placeholder="Alguna indicación especial..."></textarea>
            </div>
            <button @click="submit()" :disabled="sending || !form.service_id || !form.client_name || !form.client_phone || !form.date"
                    class="btn-primary w-full disabled:opacity-50"
                    x-text="sending ? 'Enviando...' : 'Confirmar reserva'"></button>
        </div>

        <div x-show="sent" class="text-center py-8">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">¡Reserva confirmada!</h2>
            <p class="text-gray-500 text-sm mb-6">Te contactaremos para confirmar tu cita.</p>
            <a href="/p/{{ $project->slug }}" class="btn-primary">Volver al catálogo</a>
        </div>
    </div>
</div>

</body>
</html>
