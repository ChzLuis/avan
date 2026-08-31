{{--
  Plantilla automática de imágenes de producto (etapa Catálogo).

  La vista previa se compone en el SERVIDOR con el mismo compositor que genera
  las imágenes publicadas, así que lo que se ve aquí es exactamente lo que se
  publica. El lienzo del navegador se usa solo para arrastrar el logo y la
  marca de agua: al soltar, se vuelve a pedir la composición real.

  Este partial no guarda nada por su cuenta: habla con
  /settings/builder/image-template, que es el único punto de escritura.
--}}
<div class="bxb-card" x-data="plantillaImagenes()" x-init="cargar()">
    <div class="bxb-it-head">
        <div>
            <strong class="bxb-card-title">Plantilla de imágenes de producto</strong>
            <p class="bxb-note">Da un acabado uniforme a todo el catálogo: fondo, encuadre, logo y marca de agua. La foto original nunca se modifica.</p>
        </div>
        <div class="bxb-it-cabecera-acciones">
            {{-- Plantillas guardadas: "Blanco profesional", "Campaña Navidad"… --}}
            <label class="bxb-field bxb-it-selector" x-show="guardadas.length > 1" x-cloak>
                <select :value="tpl.id" @change="activar($event.target.value)">
                    <template x-for="g in guardadas" :key="g.id">
                        <option :value="g.id" x-text="g.name"></option>
                    </template>
                </select>
            </label>
            <label class="bxb-switch bxb-it-switch">
                <input type="checkbox" :checked="tpl.enabled" @change="alternar($event.target.checked)">
                <span x-text="tpl.enabled ? 'Activa' : 'Desactivada'"></span>
            </label>
        </div>
    </div>

    <template x-if="cargando">
        <p class="bxb-note">Cargando la plantilla…</p>
    </template>

    <template x-if="!cargando">
    <div>
        {{-- El logo del negocio cambió después de generar: se avisa, no se
             regenera solo. Cientos de imágenes no se rehacen sin permiso. --}}
        <div class="bxb-it-confirm" x-show="logoCambio" x-cloak>
            <p>El <b>logo del negocio</b> cambió después de generar estas imágenes. ¿Quieres regenerarlas con el logo nuevo?</p>
            <div class="bxb-actions-row">
                <button type="button" class="bxb-btn" @click="logoCambio=false">Ahora no</button>
                <button type="button" class="bxb-btn bxb-btn-primary" @click="logoCambio=false; aplicar('all')">Regenerar catálogo</button>
            </div>
        </div>

        {{-- Estado del catálogo --}}
        <div class="bxb-it-stats">
            <span><b x-text="stats.images ?? 0"></b> fotos</span>
            <span><b x-text="stats.up_to_date ?? 0"></b> al día</span>
            <span :data-warn="(stats.pending ?? 0) > 0"><b x-text="stats.pending ?? 0"></b> pendientes</span>
            <template x-if="(stats.errors ?? 0) > 0"><span data-warn="true"><b x-text="stats.errors"></b> con error</span></template>
            <div class="bxb-it-bar" :title="(stats.percent ?? 0) + '% al día'"><i :style="'width:' + (stats.percent ?? 0) + '%'"></i></div>
        </div>

        <div class="bxb-it-grid">
            {{-- ── Vista previa ── --}}
            <div class="bxb-it-preview">
                <div class="bxb-it-canvas" x-ref="marco"
                     @mousemove="arrastrar($event)" @mouseup="soltar()" @mouseleave="soltar()"
                     @touchmove.prevent="arrastrar($event)" @touchend="soltar()">
                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="Vista previa de la plantilla" draggable="false">
                    </template>
                    <template x-if="!previewUrl">
                        <div class="bxb-it-vacio" x-text="mensaje || 'Sin vista previa'"></div>
                    </template>

                    {{-- Tiradores: solo para arrastrar; el dibujo real lo hace el servidor --}}
                    <template x-if="previewUrl && cfg.logo_enabled">
                        <button type="button" class="bxb-it-handle" title="Arrastra el logo"
                                :style="posicion(cfg.logo_x, cfg.logo_y)"
                                @mousedown.prevent="tomar('logo',$event)" @touchstart.prevent="tomar('logo',$event)">Logo</button>
                    </template>
                    <template x-if="previewUrl && cfg.watermark_enabled">
                        <button type="button" class="bxb-it-handle bxb-it-handle-wm" title="Arrastra la marca de agua"
                                :style="posicion(cfg.watermark_x, cfg.watermark_y)"
                                @mousedown.prevent="tomar('watermark',$event)" @touchstart.prevent="tomar('watermark',$event)">Marca</button>
                    </template>
                    <div class="bxb-it-cargando" x-show="pintando" x-cloak><span></span></div>
                </div>

                <label class="bxb-field">Producto de prueba
                    <select x-model="muestraId" @change="refrescar()">
                        <template x-for="m in muestras" :key="m.id">
                            <option :value="m.id" x-text="m.name"></option>
                        </template>
                    </select>
                </label>
                <p class="bxb-note" x-show="mensaje" x-text="mensaje" x-cloak></p>
            </div>

            {{-- ── Configuración ── --}}
            <div class="bxb-it-config">
                <details open>
                    <summary>Fondo</summary>
                    <div class="bxb-it-bloque">
                        <div class="bxb-seg">
                            <button type="button" :class="cfg.background_type==='white'?'on':''" @click="set('background_type','white')">Blanco</button>
                            <button type="button" :class="cfg.background_type==='color'?'on':''" @click="set('background_type','color')">Color</button>
                            <button type="button" :class="cfg.background_type==='image'?'on':''" @click="set('background_type','image')">Imagen</button>
                        </div>
                        <label class="bxb-field" x-show="cfg.background_type==='color'" x-cloak>Color de fondo
                            <input type="color" :value="cfg.background_color" @input.debounce.400ms="set('background_color',$event.target.value)">
                        </label>
                        <div x-show="cfg.background_type==='image'" x-cloak>
                            <label class="bxb-btn">Subir fondo<input type="file" accept="image/*" class="bxb-hidden" @change="subir($event,'background_image')"></label>
                            <button type="button" class="bxb-link" x-show="cfg.background_image" @click="set('background_image',null)">Quitar</button>
                        </div>
                    </div>
                </details>

                <details open>
                    <summary>Producto</summary>
                    <div class="bxb-it-bloque">
                        <label class="bxb-field">Tamaño <b x-text="cfg.product_scale + '%'"></b>
                            <input type="range" min="30" max="100" :value="cfg.product_scale" @input.debounce.300ms="set('product_scale',+$event.target.value)">
                        </label>
                        <label class="bxb-switch"><input type="checkbox" :checked="cfg.product_autocenter" @change="centrar($event.target.checked)"> Centrar automáticamente</label>
                        <label class="bxb-field">Sombra
                            <select :value="cfg.product_shadow" @change="set('product_shadow',$event.target.value)">
                                <option value="none">Ninguna</option>
                                <option value="soft">Suave</option>
                            </select>
                        </label>
                    </div>
                </details>

                <details>
                    <summary>Marca de agua</summary>
                    <div class="bxb-it-bloque">
                        <label class="bxb-switch"><input type="checkbox" :checked="cfg.watermark_enabled" @change="set('watermark_enabled',$event.target.checked)"> Mostrar marca de agua</label>
                        <div x-show="cfg.watermark_enabled" x-cloak>
                            <div class="bxb-seg chico">
                                <button type="button" :class="cfg.watermark_source==='logo'?'on':''" @click="set('watermark_source','logo')">Logo del negocio</button>
                                <button type="button" :class="cfg.watermark_source==='custom'?'on':''" @click="set('watermark_source','custom')">Imagen propia</button>
                            </div>
                            <label class="bxb-btn" x-show="cfg.watermark_source==='custom'">Subir imagen<input type="file" accept="image/*" class="bxb-hidden" @change="subir($event,'watermark_image')"></label>
                            <span class="bxb-lab">Posición</span>
                            <div class="bxb-it-rejilla">
                                <template x-for="p in posiciones" :key="'wm'+p.k">
                                    <button type="button" :class="activa('watermark',p)?'on':''" :title="p.t" @click="colocar('watermark',p)"></button>
                                </template>
                            </div>
                            <label class="bxb-field">Tamaño <b x-text="cfg.watermark_scale + '%'"></b>
                                <input type="range" min="5" max="100" :value="cfg.watermark_scale" @input.debounce.300ms="set('watermark_scale',+$event.target.value)">
                            </label>
                            <label class="bxb-field">Opacidad <b x-text="cfg.watermark_opacity + '%'"></b>
                                <input type="range" min="0" max="100" :value="cfg.watermark_opacity" @input.debounce.300ms="set('watermark_opacity',+$event.target.value)">
                            </label>
                        </div>
                    </div>
                </details>

                <details>
                    <summary>Logo</summary>
                    <div class="bxb-it-bloque">
                        <label class="bxb-switch"><input type="checkbox" :checked="cfg.logo_enabled" @change="set('logo_enabled',$event.target.checked)"> Mostrar logo</label>
                        <div x-show="cfg.logo_enabled" x-cloak>
                            <div class="bxb-seg chico">
                                <button type="button" :class="cfg.logo_source==='logo'?'on':''" @click="set('logo_source','logo')">Logo del negocio</button>
                                <button type="button" :class="cfg.logo_source==='custom'?'on':''" @click="set('logo_source','custom')">Otro logo</button>
                            </div>
                            <p class="bxb-note" x-show="cfg.logo_source==='logo' && !logoNegocio" x-cloak>Aún no has subido el logo en <b>Datos del negocio</b>: la imagen se generará sin logo.</p>
                            <label class="bxb-btn" x-show="cfg.logo_source==='custom'">Subir logo<input type="file" accept="image/*" class="bxb-hidden" @change="subir($event,'logo_image')"></label>
                            <span class="bxb-lab">Posición</span>
                            <div class="bxb-it-rejilla">
                                <template x-for="p in posiciones" :key="'lg'+p.k">
                                    <button type="button" :class="activa('logo',p)?'on':''" :title="p.t" @click="colocar('logo',p)"></button>
                                </template>
                            </div>
                            <label class="bxb-field">Tamaño <b x-text="cfg.logo_scale + '%'"></b>
                                <input type="range" min="5" max="60" :value="cfg.logo_scale" @input.debounce.300ms="set('logo_scale',+$event.target.value)">
                            </label>
                            <label class="bxb-field">Opacidad <b x-text="cfg.logo_opacity + '%'"></b>
                                <input type="range" min="0" max="100" :value="cfg.logo_opacity" @input.debounce.300ms="set('logo_opacity',+$event.target.value)">
                            </label>
                        </div>
                    </div>
                </details>

                <details>
                    <summary>Formato</summary>
                    <div class="bxb-it-bloque">
                        <div class="bxb-seg chico">
                            <button type="button" :class="cfg.aspect_ratio==='1:1'?'on':''" @click="set('aspect_ratio','1:1')">1:1</button>
                            <button type="button" :class="cfg.aspect_ratio==='4:5'?'on':''" @click="set('aspect_ratio','4:5')">4:5</button>
                            <button type="button" :class="cfg.aspect_ratio==='3:4'?'on':''" @click="set('aspect_ratio','3:4')">3:4</button>
                        </div>
                        <label class="bxb-field">Ancho de salida
                            <select :value="cfg.output_width" @change="set('output_width',+$event.target.value)">
                                <option :value="800">800 px — ligero</option>
                                <option :value="1200">1200 px — recomendado</option>
                                <option :value="1600">1600 px — alta calidad</option>
                            </select>
                            <small x-text="'Resultado: ' + cfg.output_width + ' × ' + alto() + ' px'"></small>
                        </label>
                        <label class="bxb-switch"><input type="checkbox" :checked="cfg.apply_to_gallery" @change="set('apply_to_gallery',$event.target.checked)"> Aplicar también a las demás fotos del producto</label>
                    </div>
                </details>
            </div>
        </div>

        {{-- ── Aplicar ── --}}
        <div class="bxb-it-acciones">
            <button type="button" class="bxb-btn" @click="guardar()" :disabled="guardando">Guardar configuración</button>
            <button type="button" class="bxb-btn" @click="aplicar('product')" :disabled="ocupado">Aplicar al de prueba</button>

            {{-- Por categoría: el selector elige el ámbito y el botón lo aplica --}}
            <span class="bxb-it-ambito" x-show="categorias.length" x-cloak>
                <select :value="categoriaId" @change="categoriaId=$event.target.value">
                    <option value="">Elige una categoría…</option>
                    <template x-for="c in categorias" :key="c.id">
                        <option :value="c.id" x-text="c.name + ' (' + c.products + ')'"></option>
                    </template>
                </select>
                <button type="button" class="bxb-btn" @click="aplicar('category')" :disabled="ocupado || !categoriaId">Aplicar a la categoría</button>
            </span>

            <button type="button" class="bxb-btn" @click="aplicar('all')" :disabled="ocupado">Aplicar a todo el catálogo</button>
            <button type="button" class="bxb-link" @click="guardarComo()">Guardar como plantilla nueva</button>
            <button type="button" class="bxb-link" @click="restablecer()">Restablecer</button>
            <small x-text="estado" role="status"></small>
        </div>

        {{-- Confirmación antes de una regeneración masiva --}}
        <div class="bxb-it-confirm" x-show="confirmar" x-cloak>
            <p>Se regenerarán <b x-text="confirmar && confirmar.count"></b> imágenes con la plantilla actual. <b>Las fotos originales se conservan.</b></p>
            <div class="bxb-actions-row">
                <button type="button" class="bxb-btn" @click="confirmar=null">Cancelar</button>
                <button type="button" class="bxb-btn bxb-btn-primary" @click="ejecutar()">Regenerar imágenes</button>
            </div>
        </div>
    </div>
    </template>
</div>
