<x-app-layout>
<x-slot name="slot">
<div class="flex flex-1 overflow-hidden" x-data="{
    appointments: {{ Js::from($appointments->map(fn($a) => [
        'id'=>$a->id,'client_name'=>$a->client_name,'client_phone'=>$a->client_phone ?? '',
        'service_id'=>$a->service_id,'status'=>$a->status,'notes'=>$a->notes ?? '',
        'date'=>$a->date->format('Y-m-d'),'start_time'=>$a->start_time,'end_time'=>$a->end_time,
        'date_fmt'=>$a->date->format('d/m/Y'),
        'appointment_type'=>$a->appointment_type??'','priority'=>$a->priority??'',
    ])) }},
    services:         {{ Js::from($services->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'duration_min'=>$s->duration_min])) }},
    appointmentTypes: {{ Illuminate\Support\Js::from($appointmentTypes) }},
    priorities:       {{ Illuminate\Support\Js::from($priorities) }},
    search: '',
    filterStatus: '',
    panel: 'list',

    selected: null,
    creating: false,
    form: { client_name:'', client_phone:'', service_id:'', date:'', start_time:'', end_time:'', notes:'', status:'pending' },
    saving: false,

    statuses: { pending:'Pendiente', confirmed:'Confirmada', done:'Realizada', cancelled:'Cancelada' },
    statusColors: { pending:'badge-pending', confirmed:'badge-process', done:'badge-active', cancelled:'badge-inactive' },

    get filtered() {
        return this.appointments.filter(a => {
            const s = !this.search || a.client_name.toLowerCase().includes(this.search.toLowerCase());
            const f = !this.filterStatus || a.status === this.filterStatus;
            return s && f;
        }).sort((a,b) => a.date < b.date ? 1 : -1);
    },

    select(a) { this.selected={...a}; this.form={...a}; this.creating=false; if(window.innerWidth<768)this.panel='detail'; },

    openNew() {
        const today = new Date().toISOString().split('T')[0];
        this.selected=null; this.creating=true; if(window.innerWidth<768)this.panel='detail';
        this.form={client_name:'',client_phone:'',service_id:'',date:today,start_time:'09:00',end_time:'10:00',notes:'',status:'pending'};
    },

    calcEndTime() {
        if(!this.form.service_id || !this.form.start_time) return;
        const svc = this.services.find(s=>s.id==this.form.service_id);
        if(!svc) return;
        const [h,m] = this.form.start_time.split(':').map(Number);
        const end = new Date(); end.setHours(h); end.setMinutes(m + svc.duration_min);
        this.form.end_time = end.getHours().toString().padStart(2,'0') + ':' + end.getMinutes().toString().padStart(2,'0');
    },

    async save() {
        this.saving=true;
        const base = window.location.pathname.replace(/\/[0-9]+\/appointments.*/,'') + '/' + {{ $project->id }};
        const isCreate = this.creating;
        const url  = isCreate ? base+'/appointments' : base+'/appointments/'+this.selected.id;
        const body = isCreate ? {...this.form} : {status:this.form.status,notes:this.form.notes};
        const res  = await fetch(url, {
            method: isCreate ? 'POST' : 'PUT',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (isCreate) { this.appointments.push(data.appointment); }
        else {
            const idx = this.appointments.findIndex(a=>a.id===data.appointment.id);
            if(idx>-1) this.appointments[idx]={...this.appointments[idx],...data.appointment};
        }
        this.selected=data.appointment; this.creating=false; this.saving=false;
    },

    async del() {
        if(!confirm('Eliminar esta cita?')) return;
        const base = window.location.pathname.replace(/\/[0-9]+\/appointments.*/,'') + '/' + {{ $project->id }};
        await fetch(base+'/appointments/'+this.selected.id, {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        this.appointments=this.appointments.filter(a=>a.id!==this.selected.id); this.selected=null;
    }
}">

{{-- PANEL 2: LISTA --}}
<div class="flex-shrink-0 border-r border-gray-200 bg-white flex-col" :class="panel==='list' ? 'flex w-full md:w-[340px]' : 'hidden md:flex md:w-[340px]'">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0">
        <div class="relative flex-1 mr-2">
            <input type="text" x-model="search" placeholder="Buscar cliente..." class="input pl-8 py-1.5 text-xs">
            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
        </div>
        <button @click="openNew()" class="btn-primary text-xs px-3 py-1.5">+ Nueva</button>
    </div>

    <div class="px-3 py-2 border-b border-gray-100 flex gap-1 overflow-x-auto flex-shrink-0">
        <button @click="filterStatus=''" :class="filterStatus==='' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Todas</button>
        <button @click="filterStatus='pending'" :class="filterStatus==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Pendientes</button>
        <button @click="filterStatus='confirmed'" :class="filterStatus==='confirmed' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Confirmadas</button>
        <button @click="filterStatus='done'" :class="filterStatus==='done' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Realizadas</button>
    </div>

    <div class="overflow-y-auto flex-1">
        <template x-for="a in filtered" :key="a.id">
            <div @click="select(a)" class="list-item" :class="selected && selected.id===a.id ? 'selected' : ''">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="a.client_name"></p>
                    <p class="text-xs text-gray-500" x-text="a.date_fmt + ' ' + a.start_time"></p>
                </div>
                <span :class="statusColors[a.status]" x-text="statuses[a.status]"></span>
            </div>
        </template>
        <div x-show="filtered.length===0" class="py-10 text-center text-sm text-gray-400">Sin citas</div>
    </div>
</div>

{{-- PANEL 3: DETALLE --}}
<div class="flex-col overflow-hidden bg-white" :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">
    <button @click="panel='list'" type="button" class="md:hidden flex items-center gap-1 px-4 py-3 text-sm text-gray-500 border-b border-gray-100 w-full hover:bg-gray-50 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Volver a la lista
    </button>
    <div x-show="!selected && !creating" class="flex-1 flex items-center justify-center flex-col gap-2 text-gray-300">
        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-sm text-gray-400">Selecciona una cita o crea una nueva</p>
    </div>

    <template x-if="selected || creating">
        <div class="flex flex-col h-full">
            <div class="flex border-b border-gray-200 px-4 bg-white flex-shrink-0">
                <button class="detail-tab active">Cita</button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <h3 class="font-semibold text-gray-800" x-text="creating ? 'Nueva cita' : 'Editar cita'"></h3>

                <template x-if="creating">
                    <div class="space-y-4">
                        <div><label class="label">Cliente *</label><input type="text" x-model="form.client_name" class="input"></div>
                        <div><label class="label">Teléfono</label><input type="text" x-model="form.client_phone" class="input"></div>
                        <div>
                            <label class="label">Servicio</label>
                            <select x-model="form.service_id" @change="calcEndTime()" class="input">
                                <option value="">Sin servicio</option>
                                @foreach($services as $svc)
                                <option value="{{ $svc->id }}">{{ $svc->name }} ({{ $svc->duration_min }} min)</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="label">Fecha *</label><input type="date" x-model="form.date" class="input"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="label">Hora inicio</label><input type="time" x-model="form.start_time" @change="calcEndTime()" class="input"></div>
                            <div><label class="label">Hora fin</label><input type="time" x-model="form.end_time" class="input"></div>
                        </div>
                        <div><label class="label">Notas</label><textarea x-model="form.notes" class="input resize-none" rows="3"></textarea></div>
                        <template x-if="appointmentTypes.length > 0 || priorities.length > 0">
                            <div class="grid grid-cols-2 gap-3">
                                <template x-if="appointmentTypes.length > 0">
                                    <div>
                                        <label class="label">Tipo de cita</label>
                                        <select x-model="form.appointment_type" class="input">
                                            <option value="">Sin tipo</option>
                                            <template x-for="t in appointmentTypes" :key="t"><option :value="t" x-text="t"></option></template>
                                        </select>
                                    </div>
                                </template>
                                <template x-if="priorities.length > 0">
                                    <div>
                                        <label class="label">Prioridad</label>
                                        <select x-model="form.priority" class="input">
                                            <option value="">Sin prioridad</option>
                                            <template x-for="p in priorities" :key="p"><option :value="p" x-text="p"></option></template>
                                        </select>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!creating && selected">
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Cliente</p><p class="text-sm font-medium" x-text="selected.client_name"></p></div>
                            <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Fecha</p><p class="text-sm font-medium" x-text="selected.date_fmt + ' ' + selected.start_time"></p></div>
                        </div>
                        <div>
                            <label class="label">Estado</label>
                            <select x-model="form.status" class="input">
                                <option value="pending">Pendiente</option>
                                <option value="confirmed">Confirmada</option>
                                <option value="done">Realizada</option>
                                <option value="cancelled">Cancelada</option>
                            </select>
                        </div>
                        <div><label class="label">Notas</label><textarea x-model="form.notes" class="input resize-none" rows="3"></textarea></div>
                        <template x-if="appointmentTypes.length > 0 || priorities.length > 0">
                            <div class="grid grid-cols-2 gap-3">
                                <template x-if="appointmentTypes.length > 0">
                                    <div>
                                        <label class="label">Tipo de cita</label>
                                        <select x-model="form.appointment_type" class="input">
                                            <option value="">Sin tipo</option>
                                            <template x-for="t in appointmentTypes" :key="t"><option :value="t" x-text="t"></option></template>
                                        </select>
                                    </div>
                                </template>
                                <template x-if="priorities.length > 0">
                                    <div>
                                        <label class="label">Prioridad</label>
                                        <select x-model="form.priority" class="input">
                                            <option value="">Sin prioridad</option>
                                            <template x-for="p in priorities" :key="p"><option :value="p" x-text="p"></option></template>
                                        </select>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="border-t border-gray-200 px-5 py-3 flex gap-3 bg-white flex-shrink-0">
                <button @click="save()" :disabled="saving" class="btn-primary flex-1" x-text="saving ? 'Guardando...' : 'Guardar'"></button>
                <button x-show="!creating" @click="del()" class="btn-danger px-4">Eliminar</button>
            </div>
        </div>
    </template>
</div>

</div>
</x-slot>
</x-app-layout>
