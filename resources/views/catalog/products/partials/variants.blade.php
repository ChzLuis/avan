<div x-show="tab==='variants'" x-cloak class="p-4 sm:p-6 max-w-6xl space-y-5">
    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-bold text-slate-900">Variantes reales del producto</p>
                <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-600">Combina hasta tres atributos. Cada combinación puede tener su propio SKU, precio, fotografía y stock. Los campos vacíos heredan el valor principal.</p>
            </div>
            <button type="button" @click="addVariantAttribute()" :disabled="variantState.attributes.length>=3"
                    class="pe-btn pe-btn-secondary min-h-11 whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50">
                + Agregar atributo
            </button>
        </div>
    </div>

    <div x-show="variantState.loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500" role="status">
        Cargando variantes…
    </div>

    <div x-show="variantState.error" x-cloak class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert" x-text="variantState.error"></div>

    <template x-if="!variantState.loading">
        <div class="space-y-4">
            <template x-for="(attribute, attributeIndex) in variantState.attributes" :key="attribute.key">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                    <div class="grid gap-3 sm:grid-cols-[minmax(180px,1fr)_170px_auto] sm:items-end">
                        <label class="pe-label">Nombre del atributo
                            <input class="pe-input mt-1" type="text" maxlength="80" placeholder="Ej: Color, Talla o Capacidad"
                                   x-model="attribute.name" @input="variantDirty=true">
                        </label>
                        <label class="pe-label">Presentación
                            <select class="pe-input mt-1" x-model="attribute.type" @change="variantDirty=true">
                                <option value="select">Selector</option>
                                <option value="button">Botones</option>
                                <option value="color">Muestras de color</option>
                            </select>
                        </label>
                        <button type="button" class="min-h-11 px-3 text-sm font-semibold text-red-600 hover:text-red-700"
                                @click="removeVariantAttribute(attributeIndex)">Quitar</button>
                    </div>

                    <label class="mt-4 flex min-h-11 items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" class="rounded border-slate-300" x-model="attribute.is_filterable" @change="variantDirty=true">
                        Usar también como filtro en la tienda
                    </label>

                    <div class="mt-3 space-y-2">
                        <template x-for="(value, valueIndex) in attribute.values" :key="value.key">
                            <div class="grid gap-2 rounded-xl bg-slate-50 p-2 sm:grid-cols-[minmax(150px,1fr)_110px_auto] sm:items-center">
                                <input class="pe-input" type="text" maxlength="100" placeholder="Ej: Azul, M o 256 GB"
                                       x-model="value.label" @input="variantDirty=true">
                                <label class="flex min-h-11 items-center gap-2 text-xs text-slate-600" x-show="attribute.type==='color'" x-cloak>
                                    <input type="color" class="h-10 w-12 cursor-pointer rounded border border-slate-300 bg-white p-1"
                                           :value="value.color_hex||'#2563EB'" @input="value.color_hex=$event.target.value.toUpperCase();variantDirty=true">
                                    <span x-text="value.color_hex||'Color'"></span>
                                </label>
                                <span x-show="attribute.type!=='color'"></span>
                                <button type="button" class="min-h-11 px-3 text-sm text-slate-500 hover:text-red-600"
                                        @click="removeVariantValue(attribute,valueIndex)">Eliminar</button>
                            </div>
                        </template>
                        <button type="button" class="pe-btn pe-btn-secondary min-h-11" @click="addVariantValue(attribute)">+ Agregar valor</button>
                    </div>
                </section>
            </template>

            <div x-show="variantState.attributes.length" class="flex flex-wrap items-center gap-3">
                <button type="button" class="pe-btn pe-btn-primary min-h-11" @click="generateVariantCombinations()">Generar combinaciones</button>
                <p class="text-xs text-slate-500">Las combinaciones existentes conservan sus datos cuando agregas o corriges valores.</p>
            </div>

            <div x-show="!variantState.attributes.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <p class="font-semibold text-slate-800">Este producto todavía no tiene variantes.</p>
                <p class="mt-1 text-sm text-slate-500">Agrega “Color”, “Talla”, “Material” o el atributo que utilice tu negocio.</p>
            </div>

            <section x-show="variantState.variants.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <strong class="text-sm text-slate-900"><span x-text="variantState.variants.length"></span> combinaciones</strong>
                    <p class="text-xs text-slate-500">Deja precio y stock vacíos para utilizar los datos generales del producto.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[920px] w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-3">Combinación</th><th class="px-3 py-3">SKU</th><th class="px-3 py-3">Precio</th><th class="px-3 py-3">Stock</th><th class="px-3 py-3">Imagen</th><th class="px-3 py-3">Visible</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="variant in variantState.variants" :key="variant.id||[...variant.value_keys].sort().join('|')">
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-800" x-text="variantLabel(variant)"></td>
                                    <td class="px-3 py-2"><input class="pe-input min-w-32" type="text" maxlength="100" placeholder="Opcional" x-model="variant.sku" @input="variantDirty=true"></td>
                                    <td class="px-3 py-2"><input class="pe-input w-28" type="number" min="0" step="0.01" placeholder="Hereda" x-model="variant.price" @input="variantDirty=true"></td>
                                    <td class="px-3 py-2"><input class="pe-input w-24" type="number" min="0" step="1" placeholder="Hereda" x-model="variant.stock" @input="variantDirty=true"></td>
                                    <td class="px-3 py-2">
                                        <select class="pe-input min-w-36" x-model="variant.product_image_id" @change="variantDirty=true">
                                            <option :value="null">Imagen principal</option>
                                            <template x-for="(image,index) in (selected.images||[])" :key="image.id"><option :value="image.id" x-text="'Imagen '+(index+1)"></option></template>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2"><label class="flex min-h-11 items-center justify-center"><input type="checkbox" class="rounded border-slate-300" x-model="variant.is_active" @change="variantDirty=true" :aria-label="'Mostrar '+variantLabel(variant)"></label></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </template>
</div>
