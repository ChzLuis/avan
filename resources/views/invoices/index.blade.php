@php
    $invoicesApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.facturas')
        : route('invoices.index');
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Facturas / Boletas">
<div class="flex flex-1 overflow-hidden"
     x-data="invoicesApp()"
     x-init="init()"
     @resize.window="isMobile = window.innerWidth < 768">

{{-- PANEL 2: LISTA --}}
<div class="flex flex-col border-r overflow-hidden flex-shrink-0"
     style="background:#fff; border-color:#e5e7eb; --list-width:340px; width:var(--list-width,340px);"
     :class="panel==='list'||!isMobile ? 'flex' : 'hidden'">

    {{-- Header lista --}}
    <div class="px-4 py-3 flex items-center gap-2 border-b" style="border-color:#e5e7eb;">
        <input x-model="search" type="text" placeholder="Buscar..."
               class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
        <select x-model="filterType" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none">
            <option value="">Todos</option>
            <option value="boleta">Boleta</option>
            <option value="factura">Factura</option>
            <option value="nota_credito">N. Crédito</option>
            <option value="nota_debito">N. Débito</option>
        </select>
        <button @click="openNew()"
                class="flex-shrink-0 w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
        <template x-if="filtered.length === 0">
            <p class="text-center text-sm text-gray-400 py-10">Sin comprobantes</p>
        </template>
        <template x-for="inv in filtered" :key="inv.id">
            <div @click="select(inv)"
                 class="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                 :class="selected && selected.id===inv.id ? 'bg-indigo-50 border-l-2 border-indigo-500' : ''">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="inv.numero || ('Sin número #'+inv.id)"></p>
                        <p class="text-xs text-gray-500 truncate" x-text="inv.client_name"></p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="text-xs font-semibold text-gray-800" x-text="'S/ '+inv.total.toFixed(2)"></span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                              :class="{
                                'bg-yellow-100 text-yellow-700': inv.status==='draft',
                                'bg-blue-100 text-blue-700': inv.status==='issued',
                                'bg-green-100 text-green-700': inv.status==='sent',
                                'bg-red-100 text-red-700': inv.status==='cancelled'
                              }"
                              x-text="inv.status_label"></span>
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-[11px] text-gray-400"
                          :class="{
                            'text-purple-600 font-medium': inv.type==='boleta',
                            'text-indigo-600 font-medium': inv.type==='factura'
                          }"
                          x-text="inv.type_label"></span>
                    <span class="text-[11px] text-gray-400" x-text="inv.issue_date || ''"></span>
                    <template x-if="inv.sunat_status">
                        <span class="text-[10px] px-1 py-0.5 rounded bg-green-50 text-green-600 border border-green-200"
                              x-text="'SUNAT: '+inv.sunat_status"></span>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- PANEL 3: DETALLE --}}
<div class="flex flex-col flex-1 overflow-hidden bg-white"
     :class="panel==='detail'||!isMobile ? 'flex' : 'hidden'">

    {{-- Back mobile --}}
    <div class="px-4 py-2 border-b md:hidden" style="border-color:#e5e7eb;">
        <button @click="panel='list'" class="text-sm text-indigo-600 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </button>
    </div>

    {{-- Estado vacío --}}
    <template x-if="!selected && !creating">
        <div class="flex flex-col items-center justify-center flex-1 text-gray-400">
            <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
            </svg>
            <p class="text-sm">Selecciona un comprobante</p>
            <button @click="openNew()" class="mt-3 text-sm text-indigo-600 hover:underline">
                + Nuevo comprobante
            </button>
        </div>
    </template>

    {{-- FORMULARIO NUEVO --}}
    <template x-if="creating">
        <div class="flex flex-col flex-1 overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between" style="border-color:#e5e7eb;">
                <h2 class="text-sm font-semibold text-gray-800">Nuevo Comprobante</h2>
                <button @click="creating=false; selected=null" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

                {{-- Tipo y serie --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="label">Tipo</label>
                        <select x-model="form.type" @change="autoSerie()" class="input">
                            <option value="boleta">Boleta</option>
                            <option value="factura">Factura</option>
                            <option value="nota_credito">Nota de Crédito</option>
                            <option value="nota_debito">Nota de Débito</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Serie</label>
                        <input x-model="form.serie" type="text" maxlength="10" placeholder="B001" class="input">
                    </div>
                </div>

                {{-- Fechas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="label">Fecha emisión</label>
                        <input x-model="form.issue_date" type="date" class="input">
                    </div>
                    <div>
                        <label class="label">Vencimiento</label>
                        <input x-model="form.due_date" type="date" class="input">
                    </div>
                </div>

                {{-- Cliente --}}
                <div class="border rounded-lg p-3 bg-gray-50 space-y-2">
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Receptor</p>
                    <div>
                        <label class="label">Nombre / Razón social *</label>
                        <input x-model="form.client_name" type="text" class="input" placeholder="Cliente">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Tipo doc.</label>
                            <select x-model="form.client_doc_type" class="input">
                                <option value="">—</option>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">C.E.</option>
                                <option value="pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Nro. doc.</label>
                            <input x-model="form.client_doc_number" type="text" maxlength="15" class="input">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Teléfono</label>
                            <input x-model="form.client_phone" type="text" class="input">
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input x-model="form.client_email" type="email" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">Dirección</label>
                        <input x-model="form.client_address" type="text" class="input">
                    </div>
                </div>

                {{-- Items --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Ítems</p>
                        <button @click="addItem()" type="button"
                                class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar ítem
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(item, idx) in form.items" :key="idx">
                            <div class="border rounded-lg p-2 bg-white">
                                <div class="flex gap-2 mb-1.5">
                                    <input x-model="item.description" type="text" placeholder="Descripción *"
                                           class="flex-1 text-sm border border-gray-200 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    <button @click="removeItem(idx)" type="button"
                                            class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                                    <div>
                                        <label class="text-[10px] text-gray-500">Cantidad</label>
                                        <input x-model="item.quantity" type="number" min="0.001" step="0.001"
                                               class="w-full text-sm border border-gray-200 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500">Precio unit.</label>
                                        <input x-model="item.unit_price" type="number" min="0" step="0.01"
                                               class="w-full text-sm border border-gray-200 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500">Subtotal</label>
                                        <p class="text-sm font-medium text-gray-700 px-2 py-1"
                                           x-text="'S/ '+((parseFloat(item.quantity)||0)*(parseFloat(item.unit_price)||0)).toFixed(2)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Resumen importes --}}
                <div class="border rounded-lg p-3 bg-gray-50 text-sm">
                    <div class="flex justify-between mb-1">
                        <span class="text-gray-500">IGV incluido</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.igv_included" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal (sin IGV)</span>
                        <span x-text="'S/ '+calcSubtotal().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>IGV (18%)</span>
                        <span x-text="'S/ '+calcIgv().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between font-semibold text-gray-800 border-t pt-1 mt-1">
                        <span>Total</span>
                        <span x-text="'S/ '+calcTotal().toFixed(2)"></span>
                    </div>
                </div>

                {{-- Pago y notas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="label">Método de pago</label>
                        <input x-model="form.payment_method" type="text" placeholder="Efectivo, Yape..." class="input">
                    </div>
                    <div>
                        <label class="label">Moneda</label>
                        <select x-model="form.currency" class="input">
                            <option value="PEN">PEN (Soles)</option>
                            <option value="USD">USD (Dólares)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label">Observaciones</label>
                    <textarea x-model="form.notes" rows="2" class="form-input resize-none"></textarea>
                </div>

                <template x-if="saveError">
                    <p class="text-xs text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2" x-text="saveError"></p>
                </template>
            </div>

            <div class="px-5 py-3 border-t bg-gray-50 flex justify-end gap-2" style="border-color:#e5e7eb;">
                <button @click="creating=false" class="btn-secondary text-sm">Cancelar</button>
                <button @click="save()" :disabled="saving"
                        class="btn-primary text-sm" x-text="saving ? 'Guardando...' : 'Emitir comprobante'"></button>
            </div>
        </div>
    </template>

    {{-- PANEL DETALLE --}}
    <template x-if="selected && !creating">
        <div class="flex flex-col flex-1 overflow-hidden">

            {{-- Header --}}
            <div class="px-5 py-3 border-b flex items-center justify-between" style="border-color:#e5e7eb;">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800" x-text="selected.numero || 'Comprobante #'+selected.id"></h2>
                    <p class="text-xs text-gray-500" x-text="selected.type_label + ' · ' + selected.client_name"></p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`{{ $invoicesApiBase }}/`+selected.id+`/pdf`"
                       target="_blank"
                       class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                       title="Ver PDF / Imprimir">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                    </a>
                    <button @click="deleteInvoice()"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

                {{-- Estado y badges --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs px-2 py-1 rounded-full font-medium"
                          :class="{
                            'bg-yellow-100 text-yellow-700': selected.status==='draft',
                            'bg-blue-100 text-blue-700': selected.status==='issued',
                            'bg-green-100 text-green-700': selected.status==='sent',
                            'bg-red-100 text-red-700': selected.status==='cancelled'
                          }"
                          x-text="selected.status_label"></span>
                    <template x-if="selected.sunat_status">
                        <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-200"
                              x-text="'SUNAT: '+selected.sunat_status"></span>
                    </template>
                    <div class="ml-auto flex items-center gap-1">
                        <label class="text-xs text-gray-500">Estado:</label>
                        <select x-model="editStatus" @change="updateStatus()" class="text-xs border border-gray-200 rounded px-2 py-1">
                            <option value="draft">Borrador</option>
                            <option value="issued">Emitida</option>
                            <option value="sent">Enviada</option>
                            <option value="cancelled">Anulada</option>
                        </select>
                    </div>
                </div>

                {{-- Datos del comprobante --}}
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-400">Número</p>
                        <p class="font-medium text-gray-800" x-text="selected.numero"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Tipo</p>
                        <p class="font-medium text-gray-800" x-text="selected.type_label"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Fecha emisión</p>
                        <p class="text-gray-700" x-text="selected.issue_date || '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Vencimiento</p>
                        <p class="text-gray-700" x-text="selected.due_date || '—'"></p>
                    </div>
                </div>

                {{-- Emisor --}}
                <template x-if="selected.emisor_razon_social">
                    <div class="border rounded-lg p-3 bg-gray-50 text-sm">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Emisor</p>
                        <p class="font-medium text-gray-800" x-text="selected.emisor_razon_social"></p>
                        <p class="text-gray-600 text-xs" x-text="'RUC: '+(selected.emisor_ruc||'—')"></p>
                        <p class="text-gray-500 text-xs" x-text="selected.emisor_direccion||''"></p>
                    </div>
                </template>

                {{-- Receptor --}}
                <div class="border rounded-lg p-3 bg-gray-50 text-sm">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Receptor</p>
                    <p class="font-medium text-gray-800" x-text="selected.client_name"></p>
                    <template x-if="selected.client_doc_type">
                        <p class="text-gray-600 text-xs" x-text="selected.client_doc_type+': '+(selected.client_doc_number||'—')"></p>
                    </template>
                    <template x-if="selected.client_phone">
                        <p class="text-gray-500 text-xs" x-text="selected.client_phone"></p>
                    </template>
                    <template x-if="selected.client_email">
                        <p class="text-gray-500 text-xs" x-text="selected.client_email"></p>
                    </template>
                    <template x-if="selected.client_address">
                        <p class="text-gray-500 text-xs" x-text="selected.client_address"></p>
                    </template>
                </div>

                {{-- Items --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Ítems</p>
                    <div class="border rounded-lg overflow-hidden overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="text-left px-3 py-2 text-gray-500 font-medium">Descripción</th>
                                    <th class="text-right px-3 py-2 text-gray-500 font-medium">Cant.</th>
                                    <th class="text-right px-3 py-2 text-gray-500 font-medium">Precio</th>
                                    <th class="text-right px-3 py-2 text-gray-500 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="item in selected.items" :key="item.id">
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700" x-text="item.description"></td>
                                        <td class="px-3 py-2 text-right text-gray-600" x-text="item.quantity"></td>
                                        <td class="px-3 py-2 text-right text-gray-600" x-text="'S/ '+item.unit_price.toFixed(2)"></td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-800" x-text="'S/ '+item.total.toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Totales --}}
                <div class="border rounded-lg p-3 bg-gray-50 text-sm space-y-1">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal (sin IGV)</span>
                        <span x-text="'S/ '+(selected.subtotal||0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>IGV (18%)</span>
                        <span x-text="'S/ '+(selected.igv||0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-800 border-t pt-1 mt-1 text-base">
                        <span>Total</span>
                        <span x-text="'S/ '+(selected.total||0).toFixed(2)"></span>
                    </div>
                </div>

                {{-- Info pago --}}
                <template x-if="selected.payment_method || selected.paid_at">
                    <div class="text-sm grid grid-cols-2 gap-2">
                        <template x-if="selected.payment_method">
                            <div>
                                <p class="text-xs text-gray-400">Método de pago</p>
                                <p class="text-gray-700" x-text="selected.payment_method"></p>
                            </div>
                        </template>
                        <template x-if="selected.paid_at">
                            <div>
                                <p class="text-xs text-gray-400">Fecha de pago</p>
                                <p class="text-gray-700" x-text="selected.paid_at"></p>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Notas --}}
                <template x-if="selected.notes">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Observaciones</p>
                        <p class="text-sm text-gray-600 bg-gray-50 border rounded p-2" x-text="selected.notes"></p>
                    </div>
                </template>

                {{-- SUNAT info --}}
                <template x-if="selected.sunat_status || selected.sunat_error">
                    <div class="border rounded-lg p-3 bg-yellow-50 text-xs">
                        <p class="font-semibold text-yellow-700 mb-1">SUNAT / OSE</p>
                        <template x-if="selected.sunat_status">
                            <p class="text-yellow-700">Estado: <span x-text="selected.sunat_status" class="font-medium"></span></p>
                        </template>
                        <template x-if="selected.sunat_error">
                            <p class="text-red-600 mt-1" x-text="selected.sunat_error"></p>
                        </template>
                        <p class="text-yellow-600 mt-2 italic">Integración SUNAT próximamente disponible.</p>
                    </div>
                </template>

                {{-- Bloque SUNAT --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b border-gray-200">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">SUNAT</span>
                        </div>
                        <template x-if="selected.sunat_status">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :class="{
                                    'bg-green-100 text-green-700': selected.sunat_status==='accepted',
                                    'bg-red-100 text-red-700':    selected.sunat_status==='rejected',
                                    'bg-yellow-100 text-yellow-700': selected.sunat_status==='pending' || selected.sunat_status==='error',
                                  }"
                                  x-text="{accepted:'Aceptado',rejected:'Rechazado',pending:'Pendiente',error:'Error'}[selected.sunat_status] ?? selected.sunat_status">
                            </span>
                        </template>
                    </div>
                    <div class="px-3 py-3 space-y-2">
                        <template x-if="selected.sunat_status === 'accepted'">
                            <p class="text-xs text-green-700">✓ Comprobante aceptado y registrado en SUNAT.</p>
                        </template>
                        <template x-if="selected.sunat_error">
                            <p class="text-xs text-red-600 break-words" x-text="selected.sunat_error"></p>
                        </template>
                        <template x-if="selected.sunat_status !== 'accepted' && selected.status !== 'cancelled'">
                            <button @click="enviarSunat()"
                                    :disabled="sendingSunat"
                                    class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-semibold transition
                                           bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg x-show="sendingSunat" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span x-text="sendingSunat ? 'Enviando...' : (selected.sunat_status ? 'Reintentar envío' : 'Enviar a SUNAT')"></span>
                            </button>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </template>

</div>
</div>

<script>
function invoicesApp() {
    return {
        invoices: @json($invoices),
        search: '',
        filterType: '',
        panel: 'list',
        isMobile: window.innerWidth < 768,
        selected: null,
        creating: false,
        sendingSunat: false,
        editStatus: '',
        saving: false,
        saveError: '',
        form: {
            type: 'boleta', serie: 'B001', issue_date: '', due_date: '',
            client_name: '', client_phone: '', client_email: '',
            client_doc_type: '', client_doc_number: '', client_address: '',
            payment_method: '', currency: 'PEN', notes: '',
            igv_included: true,
            items: [{ description: '', quantity: 1, unit_price: 0 }]
        },

        init() {
            // set today as default
            const today = new Date().toISOString().slice(0,10);
            this.form.issue_date = today;
        },

        get filtered() {
            return this.invoices.filter(inv => {
                const s = !this.search ||
                    inv.client_name.toLowerCase().includes(this.search.toLowerCase()) ||
                    (inv.numero || '').toLowerCase().includes(this.search.toLowerCase());
                const t = !this.filterType || inv.type === this.filterType;
                return s && t;
            });
        },

        async select(inv) {
            this.creating = false;
            this.panel = 'detail';
            // fetch full detail
            const res = await fetch(`{{ $invoicesApiBase }}/` + inv.id, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            this.selected = data;
            this.editStatus = data.status;
        },

        openNew() {
            this.selected = null;
            this.creating = true;
            this.panel = 'detail';
            const today = new Date().toISOString().slice(0,10);
            this.form = {
                type: 'boleta', serie: 'B001', issue_date: today, due_date: '',
                client_name: '', client_phone: '', client_email: '',
                client_doc_type: '', client_doc_number: '', client_address: '',
                payment_method: '', currency: 'PEN', notes: '',
                igv_included: true,
                items: [{ description: '', quantity: 1, unit_price: 0 }]
            };
            this.saveError = '';
        },

        autoSerie() {
            if (this.form.type === 'factura') this.form.serie = 'F001';
            else if (this.form.type === 'boleta') this.form.serie = 'B001';
        },

        addItem() {
            this.form.items.push({ description: '', quantity: 1, unit_price: 0 });
        },
        removeItem(idx) {
            if (this.form.items.length > 1) this.form.items.splice(idx, 1);
        },

        calcSubtotal() {
            const lineTotal = this.form.items.reduce((s, i) =>
                s + (parseFloat(i.quantity)||0) * (parseFloat(i.unit_price)||0), 0);
            return this.form.igv_included ? lineTotal / 1.18 : lineTotal;
        },
        calcIgv() {
            const sub = this.calcSubtotal();
            return sub * 0.18;
        },
        calcTotal() {
            return this.calcSubtotal() + this.calcIgv();
        },

        async save() {
            this.saving = true;
            this.saveError = '';
            const res = await fetch(`{{ $invoicesApiBase }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.form)
            });
            const data = await res.json();
            if (!res.ok) {
                const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error al guardar.');
                this.saveError = msgs;
                this.saving = false;
                return;
            }
            const inv = data.invoice;
            // Map to list format
            const listItem = {
                id: inv.id,
                numero: inv.numero,
                type: inv.type,
                type_label: { boleta:'Boleta', factura:'Factura', nota_credito:'N. Crédito', nota_debito:'N. Débito' }[inv.type] || inv.type,
                client_name: inv.client_name,
                total: parseFloat(inv.total),
                status: inv.status,
                status_label: { draft:'Borrador', issued:'Emitida', sent:'Enviada', cancelled:'Anulada' }[inv.status] || inv.status,
                issue_date: inv.issue_date ? new Date(inv.issue_date).toLocaleDateString('es-PE') : '',
                sunat_status: inv.sunat_status,
            };
            this.invoices.unshift(listItem);
            this.creating = false;
            this.saving = false;
            await this.select(listItem);
        },

        async updateStatus() {
            await fetch(`{{ $invoicesApiBase }}/` + this.selected.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: this.editStatus })
            });
            this.selected.status = this.editStatus;
            this.selected.status_label = { draft:'Borrador', issued:'Emitida', sent:'Enviada', cancelled:'Anulada' }[this.editStatus] || this.editStatus;
            const idx = this.invoices.findIndex(i => i.id === this.selected.id);
            if (idx > -1) { this.invoices[idx].status = this.editStatus; this.invoices[idx].status_label = this.selected.status_label; }
        },

        async enviarSunat() {
            if (!this.selected) return;
            this.sendingSunat = true;
            const res = await fetch(`{{ $invoicesApiBase }}/` + this.selected.id + '/sunat', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            this.sendingSunat = false;
            if (data.ok) {
                const idx = this.invoices.findIndex(i => i.id === this.selected.id);
                const upd = { sunat_status: 'accepted', status: 'sent', status_label: 'Enviada' };
                if (idx > -1) this.invoices[idx] = { ...this.invoices[idx], ...upd };
                this.selected = { ...this.selected, ...upd, sunat_error: null };
                alert('✓ ' + (data.message || 'Aceptado por SUNAT'));
            } else {
                const errUpd = { sunat_status: 'rejected' };
                const idx = this.invoices.findIndex(i => i.id === this.selected.id);
                if (idx > -1) this.invoices[idx] = { ...this.invoices[idx], ...errUpd };
                this.selected = { ...this.selected, ...errUpd, sunat_error: data.message };
            }
        },

        async deleteInvoice() {
            if (!confirm('¿Eliminar este comprobante?')) return;
            const res = await fetch(`{{ $invoicesApiBase }}/` + this.selected.id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!res.ok) { alert(data.message || 'No se pudo eliminar.'); return; }
            this.invoices = this.invoices.filter(i => i.id !== this.selected.id);
            this.selected = null;
            this.panel = 'list';
        }
    };
}
</script>
</x-portal-layout>
