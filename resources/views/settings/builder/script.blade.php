<script>
// Selector de presets de encabezado (Encabezado y navegación).
// Vive DENTRO de builderApp (hereda settings/setSetting por scope de Alpine).
function hpPresetPicker(presets) {
    return {
        hpPresets: presets,
        get hpActive() {
            const k = this.settings.header_preset || '';
            return this.hpPresets.find(p => p.key === k) || null;
        },
        hpCan(cap) { return !!(this.hpActive && this.hpActive.capabilities && this.hpActive.capabilities[cap]); },
        hpSelect(p) {
            this.setSetting('header_preset', p.key);
            // Defaults inteligentes: solo claves que el negocio aún no configuró.
            Object.entries(p.defaults || {}).forEach(([k, v]) => {
                if (this.settings[k] === undefined || this.settings[k] === null || this.settings[k] === '') {
                    if (v !== '') this.setSetting(k, v);
                }
            });
        },
        hpUni() {
            const k = (this.settings.header_preset || '') === 'multiverse' ? 'hp_multiverse_style' : 'hp_boutique_universe_style';
            return this.settings[k] || 'pill';
        },
        hpSetUni(v) {
            const k = (this.settings.header_preset || '') === 'multiverse' ? 'hp_multiverse_style' : 'hp_boutique_universe_style';
            this.setSetting(k, v);
        },
    };
}
function builderApp(cfg) {
    return {
        project: cfg.project, urls: cfg.urls, _csrf0: cfg.csrf,
        // Token vivo: el layout lo refresca en el <meta> cada 15 min; leerlo
        // al momento de cada petición evita el 419 en pestañas abiertas mucho tiempo.
        get csrf() { const m = document.querySelector('meta[name="csrf-token"]'); return (m && m.content) || this._csrf0; },
        async refreshCsrfNow() {
            try {
                const r = await fetch('/csrf-token', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const d = r.ok ? await r.json() : null;
                if (d && d.token) {
                    const m = document.querySelector('meta[name="csrf-token"]');
                    if (m) m.setAttribute('content', d.token);
                    return true;
                }
            } catch (e) {}
            return false;
        },
        settings: cfg.settings || {},
        progress: cfg.progress,
        presets: cfg.presets || {}, templates: cfg.templates || [], palettes: cfg.palettes || [],
        uploadUrl: cfg.uploadUrl, rubroApplied: null,
        blocks: cfg.blocks || [], editingBlock: null, blockContent: {},
        storeCategories: cfg.storeCategories || [], catIcons: cfg.catIcons || {},
        saleProducts: cfg.saleProducts || [], allProductsLite: cfg.allProductsLite || [],
        profiles: cfg.profiles || [], profilesEnabled: !!cfg.profilesEnabled,
        orphanPolicy: cfg.orphanPolicy || 'hide', newProfileName: '',
        iconPicker: null, iconQuery: '', iconResults: [], iconAssignError: '',
        sectionStateUrl: cfg.sectionStateUrl, sectionContentUrl: cfg.sectionContentUrl,
        stage: 'business',
        device: 'desktop',
        expertMode: localStorage.getItem('bxb_expert') === '1',
        previewOpen: false, previewError: false,
        publishing: false, publishSuccess: null, warningsToConfirm: null,
        saveState: 'clean', lastSavedAt: '',
        pendingDrafts: !!cfg.hasDrafts,
        _queue: {}, _seq: 0, _lastAck: 0, _history: [], _future: [], _previewTimer: null,

        init() {
            // Continuar donde quedaste: hash de la URL (tras guardar un formulario embebido) o primera etapa incompleta.
            const fromHash = (location.hash || '').replace('#', '');
            if (fromHash && this.stageList.some(s => s.key === fromHash)) {
                this.stage = fromHash;
            } else {
                const firstPending = this.stageList.find(s => s.state !== 'complete');
                this.stage = firstPending ? firstPending.key : 'publish';
            }
            this.$watch('stage', v => { try { history.replaceState(null, '', '#' + v); } catch (e) {} });
            // Ancho del preview persistido + escala de escritorio.
            document.documentElement.style.setProperty('--bxb-pw', this.previewWidth + 'px');
            window.addEventListener('resize', () => this.fitPreview());
            this.$nextTick(() => this.fitPreview());
            this.$watch('expertMode', v => localStorage.setItem('bxb_expert', v ? '1' : '0'));
            window.addEventListener('online', () => { if (this.saveState === 'offline') this.flushQueue(); });
            window.addEventListener('beforeunload', e => {
                if (this.saveState === 'dirty' || this.saveState === 'saving') { e.preventDefault(); e.returnValue = ''; }
            });
            // Puente postMessage del preview (validando origen y tipos).
            window.addEventListener('message', e => {
                if (e.origin !== window.location.origin || !e.data || typeof e.data.type !== 'string') return;
                if (e.data.type === 'storefront:ready') { this.previewError = false; this.highlightForStage(); }
                if (e.data.type === 'storefront:section-selected') { this.stage = 'home'; }
            });
        },

        get stageList() {
            return Object.values(this.progress.stages);
        },
        get stageIndex() { return this.stageList.findIndex(s => s.key === this.stage); },
        goStage(dir) {
            const i = this.stageIndex + dir;
            if (i >= 0 && i < this.stageList.length) { this.stage = this.stageList[i].key; this.highlightForStage(); }
        },

        get saveLabel() {
            return { clean: 'Sin cambios', dirty: 'Cambios sin guardar', saving: 'Guardando…', saved: 'Guardado', error: 'Error al guardar', offline: 'Sin conexión' }[this.saveState];
        },

        // ── Autosave (SIEMPRE a borrador) ──
        setSetting(key, value, recordHistory = true) {
            if (recordHistory) this._pushHistory(key, this.settings[key] ?? null, value);
            this.settings[key] = value;
            this._queue[key] = value;
            this.saveState = 'dirty';
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => this.flushQueue(), 600);
        },
        async flushQueue() {
            const batch = { ...this._queue };
            if (Object.keys(batch).length === 0) return;
            this._queue = {};
            const seq = ++this._seq;
            this.saveState = 'saving';
            try {
                const post = () => fetch(this.urls.draftSettings, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ settings: batch }),
                });
                let res = await post();
                // Sesión con token vencido: refrescar y reintentar una vez.
                if (res.status === 419 && await this.refreshCsrfNow()) res = await post();
                if (!res.ok) throw new Error('http ' + res.status);
                const data = await res.json();
                if (seq < this._lastAck) return; // respuesta vieja: ignorar
                this._lastAck = seq;
                if (data.progress) this.progress = data.progress;
                if (Object.keys(this._queue).length === 0) {
                    this.saveState = 'saved';
                    this.pendingDrafts = true;
                    this.lastSavedAt = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
                }
                this.refreshPreview();
            } catch (e) {
                Object.assign(this._queue, batch); // reencolar para reintento
                this.saveState = navigator.onLine ? 'error' : 'offline';
            }
        },

        batchSet(pairs) {
            Object.entries(pairs).forEach(([k, v]) => this.setSetting(k, v));
        },

        // ── Bloques de la Página de inicio (SIEMPRE borrador) ──
        async _saveBlockState(b) {
            const fd = new FormData();
            fd.append('_token', this.csrf);
            fd.append('action', 'draft');
            fd.append('is_enabled', b.enabled ? '1' : '0');
            fd.append('show_desktop', b.show_desktop ? '1' : '0');
            fd.append('show_mobile', b.show_mobile ? '1' : '0');
            fd.append('show_tablet', b.show_mobile ? '1' : '0');
            // Orden persistido del bloque (no el índice: evita mover al activar/desactivar).
            fd.append('sort_order', Number.isInteger(b.sort) ? b.sort : this.blocks.indexOf(b));
            this.saveState = 'saving';
            try {
                const r = await fetch(this.sectionStateUrl.replace('__C__', b.component), { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' }, body: fd });
                if (!r.ok) throw new Error('http');
                b.has_draft = true;
                this.pendingDrafts = true;
                this.saveState = 'saved';
                this.lastSavedAt = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
                this.refreshPreview();
                this.reloadProgress();
            } catch (e) {
                this.saveState = navigator.onLine ? 'error' : 'offline';
            }
        },
        toggleBlock(b) { b.enabled = !b.enabled; this._saveBlockState(b); },
        toggleBlockDevice(b, dev) {
            if (dev === 'desktop') b.show_desktop = !b.show_desktop; else b.show_mobile = !b.show_mobile;
            this._saveBlockState(b);
        },
        // Reordenar arrastrando (nativo, sin librerías). Las flechas quedan
        // como respaldo accesible por teclado y en táctil.
        dragging: null,
        dragStart(ev, b) {
            this.dragging = b.component;
            ev.dataTransfer.effectAllowed = 'move';
            try { ev.dataTransfer.setData('text/plain', b.component); } catch (e) {}
        },
        dragOver(ev, b) {
            if (!this.dragging || this.dragging === b.component) return;
            const from = this.blocks.findIndex(x => x.component === this.dragging);
            const to = this.blocks.findIndex(x => x.component === b.component);
            if (from < 0 || to < 0 || from === to) return;
            const arr = [...this.blocks];
            const [moved] = arr.splice(from, 1);
            arr.splice(to, 0, moved);
            this.blocks = arr;
        },
        async dragEnd() {
            if (!this.dragging) return;
            this.dragging = null;
            this.blocks.forEach((blk, idx) => blk.sort = idx);
            for (const blk of this.blocks) await this._saveBlockState(blk);
        },

        async moveBlock(b, dir) {
            const i = this.blocks.indexOf(b), j = i + dir;
            if (i < 0 || j < 0 || j >= this.blocks.length) return;
            const arr = [...this.blocks];
            [arr[i], arr[j]] = [arr[j], arr[i]];
            this.blocks = arr;
            // Re-numerar TODOS los bloques (a prueba de órdenes antiguos no contiguos).
            this.blocks.forEach((blk, idx) => blk.sort = idx);
            for (const blk of this.blocks) await this._saveBlockState(blk);
        },
        openBlock(b) {
            this.editingBlock = b;
            this.blockContent = { ...(b.content || {}) };
            this._post({ type: 'builder:highlight-section', component: b.native });
        },
        closeBlock() {
            this.editingBlock = null;
            this._post({ type: 'builder:clear-highlight' });
        },
        // Contenido del bloque → endpoint canónico (validación intacta), action=draft.
        setBlockContent(key, value) {
            this.blockContent[key] = value;
            clearTimeout(this._blockDebounce);
            this._blockDebounce = setTimeout(() => this._saveBlockContent(), 600);
        },
        async _saveBlockContent() {
            if (!this.editingBlock) return;
            const b = this.editingBlock;
            const fd = new FormData();
            fd.append('_token', this.csrf);
            fd.append('action', 'draft');
            fd.append('is_enabled', b.enabled ? '1' : '0');
            fd.append('show_desktop', b.show_desktop ? '1' : '0');
            fd.append('show_mobile', b.show_mobile ? '1' : '0');
            fd.append('show_tablet', b.show_mobile ? '1' : '0');
            // Requisitos del endpoint canónico por componente (defaults seguros).
            const c = this.blockContent;
            if (b.component === 'daily_offer') { c.expired_action = c.expired_action || 'message'; }
            if (b.component === 'featured_categories') { c.display = c.display || 'images'; c.limit = c.limit || 8; }
            if (b.component === 'discounts') {
                c.limit = c.limit || 8; c.layout = c.layout || 'grid';
                c.columns_desktop = c.columns_desktop || 4; c.columns_tablet = c.columns_tablet || 3; c.columns_mobile = c.columns_mobile || 2;
                c.selection = (c.product_ids || []).length ? 'manual' : 'automatic';
            }
            if (b.component === 'featured_products') {
                c.limit = c.limit || 8;
                c.selection = (c.product_ids || []).length ? 'manual' : 'automatic';
            }
            Object.entries(c).forEach(([k, v]) => {
                if (Array.isArray(v)) {
                    v.forEach((item, i) => {
                        // Ítems complejos (objetos): content[items][0][titulo]=...
                        if (item && typeof item === 'object') {
                            Object.entries(item).forEach(([ik, iv]) => {
                                if (iv !== null && iv !== undefined) fd.append('content[' + k + '][' + i + '][' + ik + ']', iv);
                            });
                        } else {
                            fd.append('content[' + k + '][]', item);
                        }
                    });
                }
                else fd.append('content[' + k + ']', v ?? '');
            });
            this.saveState = 'saving';
            try {
                const r = await fetch(this.sectionContentUrl.replace('__C__', b.component), { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' }, body: fd });
                if (!r.ok) throw new Error('http');
                b.content = { ...this.blockContent };
                b.has_draft = true;
                this.pendingDrafts = true;
                this.saveState = 'saved';
                this.lastSavedAt = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
                this.refreshPreview();
            } catch (e) {
                this.saveState = navigator.onLine ? 'error' : 'offline';
            }
        },
        applyRecommendedStructure() {
            // Orden recomendado de portada; solo reordena y activa lo esencial (borrador).
            const order = ['hero', 'benefits', 'featured_categories', 'daily_offer', 'featured_products', 'announcements', 'discounts', 'blog'];
            const essentials = ['hero', 'benefits', 'featured_categories', 'featured_products'];
            this.blocks = [...this.blocks].sort((a, b) => order.indexOf(a.component) - order.indexOf(b.component));
            this.blocks.forEach(b => { if (essentials.includes(b.component)) b.enabled = true; this._saveBlockState(b); });
        },

        // ── Copiar de otra tienda (B7) ──
        copySources: [], copySource: '', copyParts: [], copySummary: '', copyResult: '',
        async loadCopySources() {
            try {
                const r = await fetch(this.urls.copySources, { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.copySources = (await r.json()).sources || [];
            } catch (e) {}
        },
        async copyPreview() {
            this.copySummary = ''; this.copyResult = '';
            try {
                const r = await fetch(this.urls.copy, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ source_id: Number(this.copySource), parts: this.copyParts, dry_run: true }),
                });
                const d = await r.json();
                if (r.ok) this.copySummary = 'Se copiarán ' + d.summary.settings + ' ajustes' + (d.summary.sections ? (' y ' + d.summary.sections + ' secciones del inicio') : '') + ' a tu borrador. Puedes deshacerlo no publicando.';
            } catch (e) {}
        },
        async copyConfirm() {
            try {
                const r = await fetch(this.urls.copy, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ source_id: Number(this.copySource), parts: this.copyParts }),
                });
                const d = await r.json();
                if (!r.ok) { this.copyResult = 'No se pudo copiar.'; return; }
                this.copySummary = '';
                this.copyResult = '✓ Copiado a borrador: ' + d.copied.settings + ' ajustes, ' + d.copied.sections + ' secciones. Revisa el preview y publica cuando estés listo.';
                if (d.progress) this.progress = d.progress;
                location.reload();
            } catch (e) { this.copyResult = 'Error de conexión.'; }
        },

        // ── Revisar y publicar (B6) ──
        checklist: { critical: [], warning: [], recommendation: [], complete: [], can_publish: false },
        async loadChecklist() {
            try {
                const r = await fetch(this.urls.checklist, { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.checklist = await r.json();
            } catch (e) {}
        },
        goFix(itm) {
            // Deep-link a la etapa y, en catálogo, abrir la lista exacta.
            const target = itm.target || '';
            const stage = target.split('.')[0];
            const map = { business: 'business', appearance: 'appearance', home: 'home', catalog: 'catalog', sales: 'sales' };
            if (map[stage]) this.stage = map[stage];
            const fixMap = { 'catalog.fix-price': 'no_price', 'catalog.fix-image': 'no_image', 'catalog.fix-sku': 'sku_dup', 'catalog.start': 'all' };
            if (fixMap[target]) this.$nextTick(() => this.loadFixList(fixMap[target]));
            if (target === 'home.slider') this.$nextTick(() => { const b = this.blocks.find(x => x.component === 'hero'); if (b) this.openBlock(b); });
        },

        // ── Catálogo (B4): métricas, corrección y masivas ──
        metrics: {}, fixFilter: null, fixItems: [], fixPage: 1, fixHasMore: false,
        selectedIds: [], bulkValue: '', bulkPct: '', bulkResult: '',
        async loadMetrics() {
            try {
                const r = await fetch(cfg.metricsUrl, { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.metrics = (await r.json()).metrics || {};
            } catch (e) {}
        },
        async loadFixList(filter, more = false) {
            this.fixFilter = filter;
            if (!more) { this.fixItems = []; this.fixPage = 1; this.selectedIds = []; this.bulkResult = ''; }
            try {
                const r = await fetch(cfg.catalogListUrl + '?filter=' + filter + '&page=' + this.fixPage, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                this.fixItems = more ? this.fixItems.concat(d.items) : d.items;
                this.fixHasMore = d.has_more;
                this.fixPage++;
            } catch (e) {}
        },
        toggleSelectAll(on) { this.selectedIds = on ? this.fixItems.map(p => String(p.id)) : []; },
        async bulk(action) {
            const ids = this.selectedIds.map(Number);
            if (!ids.length) return;
            const payload = { action, ids };
            if (action === 'price_set') payload.value = parseFloat(this.bulkValue);
            if (action === 'price_adjust') payload.value = parseFloat(this.bulkPct);
            if ((action === 'price_set' || action === 'price_adjust') && isNaN(payload.value)) return;
            try {
                const r = await fetch(cfg.catalogBulkUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify(payload),
                });
                const d = await r.json();
                this.bulkResult = r.ok ? ('✓ ' + d.affected + ' productos actualizados.') : 'No se pudo aplicar la acción.';
                this.loadMetrics();
                this.loadFixList(this.fixFilter);
                this.reloadProgress();
                this.refreshPreview();
            } catch (e) { this.bulkResult = 'Error de conexión.'; }
        },
        async fixPrice(p, value) {
            const v = parseFloat(value);
            if (isNaN(v) || v < 0) return;
            this.selectedIds = [];
            try {
                const r = await fetch(cfg.catalogBulkUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ action: 'price_set', ids: [p.id], value: v }),
                });
                if (r.ok) { p.price = v; this.loadMetrics(); this.reloadProgress(); }
            } catch (e) {}
        },

        // ── Selección por ids en el contenido del bloque (categorías/productos) ──
        inContent(key, id) { return (this.blockContent[key] || []).map(String).includes(String(id)); },
        toggleContentId(key, id) {
            const arr = (this.blockContent[key] || []).map(String);
            const i = arr.indexOf(String(id));
            if (i >= 0) arr.splice(i, 1); else arr.push(String(id));
            this.setBlockContent(key, arr);
        },

        // ── Foto de categoría (dato de catálogo: aplica de inmediato) ──
        async uploadCategoryPhoto(c, file) {
            if (!file) return;
            const fd = new FormData();
            fd.append('_token', this.csrf); fd.append('category_id', c.id); fd.append('image', file);
            const r = await fetch(cfg.categoryPhotoUrl, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
            if (r.ok) { c.image = (await r.json()).image_url; this.refreshPreview(); }
        },
        async removeCategoryPhoto(c) {
            const fd = new FormData();
            fd.append('_token', this.csrf); fd.append('category_id', c.id); fd.append('remove', '1');
            const r = await fetch(cfg.categoryPhotoUrl, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
            if (r.ok) { c.image = null; this.refreshPreview(); }
        },

        // ── Perfiles de catálogo (datos vivos: se aplican de inmediato) ──
        async _profilePost(url, fields) {
            const fd = new FormData();
            fd.append('_token', this.csrf);
            Object.entries(fields).forEach(([k, v]) => fd.append(k, v));
            this.saveState = 'saving';
            try {
                const r = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf }, body: fd });
                if (!r.ok) throw new Error('http');
                this.saveState = 'saved';
                this.refreshPreview();
                return await r.json().catch(() => ({}));
            } catch (e) { this.saveState = 'error'; return null; }
        },
        async profilesFeature(enabled) {
            this.profilesEnabled = enabled;
            await this._profilePost(cfg.profileUrls.feature, { enabled: enabled ? 1 : 0, orphan_policy: this.orphanPolicy });
        },
        async saveOrphanPolicy(v) {
            this.orphanPolicy = v;
            await this._profilePost(cfg.profileUrls.feature, { enabled: this.profilesEnabled ? 1 : 0, orphan_policy: v });
        },
        async profileSave(p) {
            await this._profilePost(cfg.profileUrls.update.replace('987654321', p.id), {
                _method: 'PUT', name: p.name, menu_label: p.menu_label || '',
                is_enabled: p.is_enabled ? 1 : 0, show_in_menu: p.show_in_menu ? 1 : 0,
            });
        },
        async profileCreate() {
            const name = (this.newProfileName || '').trim();
            if (!name) return;
            const d = await this._profilePost(cfg.profileUrls.store, { name });
            if (d && d.profile) { this.profiles.push(d.profile); this.newProfileName = ''; }
        },
        async profileDelete(p) {
            if (!confirm('¿Eliminar el perfil "' + p.name + '"? Los productos no se borran.')) return;
            const d = await this._profilePost(cfg.profileUrls.destroy.replace('987654321', p.id), { _method: 'DELETE' });
            if (d) this.profiles = this.profiles.filter(x => x.id !== p.id);
        },

        // ── Biblioteca de iconos (Iconify vía backend, guardado en borrador) ──
        openIconPicker(c) { this.iconPicker = c; this.iconQuery = ''; this.iconResults = []; this.iconAssignError = ''; },
        async searchIcons() {
            const q = this.iconQuery.trim();
            if (q.length < 2) { this.iconResults = []; return; }
            try {
                const r = await fetch(cfg.iconSearchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.iconResults = (await r.json()).icons || [];
            } catch (e) { this.iconResults = []; }
        },
        async assignIcon(ic) {
            if (!this.iconPicker) return;
            this.iconAssignError = '';
            try {
                const r = await fetch(cfg.iconAssignUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ category_id: this.iconPicker.id, icon: ic }),
                });
                const d = await r.json();
                if (!r.ok) { this.iconAssignError = d.message || 'No se pudo asignar el icono.'; return; }
                this.catIcons[d.category_id] = d.svg;
                this.pendingDrafts = true;
                this.iconPicker = null;
                this.refreshPreview();
            } catch (e) { this.iconAssignError = 'Error de conexión.'; }
        },

        // ── Paquete de DISEÑO del rubro: tema + variantes + secciones (borrador) ──
        designPresetResult: '',
        async applyDesignPreset(key) {
            if (!key) return;
            this.designPresetResult = 'Aplicando…';
            try {
                const r = await fetch(this.urls.designPreset, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ key }),
                });
                const d = await r.json();
                if (!r.ok || !d.ok) { this.designPresetResult = d.message || 'Este rubro aún no tiene diseño completo.'; return; }
                this.designPresetResult = '✓ Diseño aplicado en borrador. Actualizando…';
                this.pendingDrafts = true;
                // Recargar para que bloques y settings reflejen el nuevo borrador.
                setTimeout(() => location.reload(), 900);
            } catch (e) {
                this.designPresetResult = 'Error de conexión.';
            }
        },

        // ── Rubro → recomendaciones (todo a borrador) ──
        applyRubro(key) {
            this.setSetting('business_category', key);
            const preset = this.presets[key];
            if (!preset) { this.rubroApplied = null; return; }
            const recommended = { ...preset.settings };
            // No pisar lo que el usuario ya definió a propósito.
            ['hero_title', 'hero_subtitle', 'announcement_text'].forEach(k => {
                if ((this.settings[k] || '').trim() !== '') delete recommended[k];
            });
            this.batchSet(recommended);
            this.rubroApplied = preset;
        },

        // ── Imágenes: subir archivo y guardar la clave EN BORRADOR ──
        async uploadMedia(evOrFile, key) {
            const file = evOrFile?.target ? evOrFile.target.files[0] : evOrFile;
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            fd.append('type', key);
            try {
                const post = () => fetch(this.uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' }, body: fd });
                let res = await post();
                if (res.status === 419 && await this.refreshCsrfNow()) res = await post();
                const d = await res.json();
                if (d.path) {
                    this.setSetting(key, d.path);
                } else {
                    // La subida falló (formato no soportado, > 4MB, etc.) — antes esto
                    // se ignoraba en silencio y parecía que "no pasaba nada".
                    const msg = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'No se pudo subir el archivo.');
                    window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg, type: 'error' } }));
                }
            } catch (e) {
                this.saveState = 'error';
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Error de conexión al subir el archivo.', type: 'error' } }));
            } finally {
                if (evOrFile?.target) evOrFile.target.value = '';
            }
        },
        assetUrl(v) {
            if (!v) return '';
            if (/^(https?:)?\/\//.test(v) || String(v).startsWith('data:')) return v;
            return '/storage/' + String(v).replace(/^storage\//, '');
        },

        // ── Ítems de las secciones nuevas (listas de objetos en blockContent) ──
        contentItems(key) { return Array.isArray(this.blockContent[key]) ? this.blockContent[key] : []; },
        addContentItem(key, template = {}) {
            const arr = [...this.contentItems(key)];
            arr.push({ key: 'item-' + Date.now().toString(36), enabled: true, sort_order: (arr.length + 1) * 10, ...template });
            this.setBlockContent(key, arr);
        },
        removeContentItem(key, i) {
            const arr = [...this.contentItems(key)];
            arr.splice(i, 1);
            this.setBlockContent(key, arr);
        },
        setContentItem(key, i, field, value) {
            const arr = [...this.contentItems(key)];
            if (!arr[i]) return;
            arr[i] = { ...arr[i], [field]: value };
            this.setBlockContent(key, arr);
        },
        moveContentItem(key, i, dir) {
            const arr = [...this.contentItems(key)], j = i + dir;
            if (j < 0 || j >= arr.length) return;
            [arr[i], arr[j]] = [arr[j], arr[i]];
            arr.forEach((item, idx) => item.sort_order = (idx + 1) * 10);
            this.setBlockContent(key, arr);
        },
        // Subida de imagen que escribe en el CONTENIDO del bloque (no en settings).
        async uploadContentImage(evOrFile, listKey, i, field = 'image') {
            const file = evOrFile?.target ? evOrFile.target.files[0] : evOrFile;
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            fd.append('type', 'section_' + (this.editingBlock?.component || 'block'));
            try {
                const post = () => fetch(this.uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' }, body: fd });
                let res = await post();
                if (res.status === 419 && await this.refreshCsrfNow()) res = await post();
                const d = await res.json();
                if (!d.path) {
                    const msg = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'No se pudo subir el archivo.');
                    window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg, type: 'error' } }));
                    return;
                }
                if (i === null) this.setBlockContent(field, d.path);
                else this.setContentItem(listKey, i, field, d.path);
            } catch (e) {
                this.saveState = 'error';
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Error de conexión al subir el archivo.', type: 'error' } }));
            }
            finally { if (evOrFile?.target) evOrFile.target.value = ''; }
        },

        // ── Paleta desde el logo (canvas, sin backend) ──
        paletteFromLogo() {
            const src = this.assetUrl(this.settings.logo_url);
            if (!src) return;
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                try {
                    const c = document.createElement('canvas');
                    const size = 40;
                    c.width = size; c.height = size;
                    const ctx = c.getContext('2d');
                    ctx.drawImage(img, 0, 0, size, size);
                    const px = ctx.getImageData(0, 0, size, size).data;
                    let best = null, bestScore = -1, rs = 0, gs = 0, bs = 0, n = 0;
                    for (let i = 0; i < px.length; i += 4) {
                        const r = px[i], g = px[i + 1], b = px[i + 2], a = px[i + 3];
                        if (a < 128) continue;
                        const mx = Math.max(r, g, b), mn = Math.min(r, g, b);
                        const sat = mx === 0 ? 0 : (mx - mn) / mx, lum = (r + g + b) / 3;
                        rs += r; gs += g; bs += b; n++;
                        const score = sat * (1 - Math.abs(lum - 128) / 128);
                        if (score > bestScore) { bestScore = score; best = [r, g, b]; }
                    }
                    const hex = (v) => v.toString(16).padStart(2, '0');
                    if (best && bestScore > 0.08) {
                        this.batchSet({ primary_color: '#' + hex(best[0]) + hex(best[1]) + hex(best[2]) });
                    } else if (n) {
                        this.batchSet({ primary_color: '#' + hex(Math.round(rs / n)) + hex(Math.round(gs / n)) + hex(Math.round(bs / n)) });
                    }
                } catch (e) { /* canvas bloqueado: sin cambios */ }
            };
            img.src = src;
        },

        // ── Undo / Redo (persisten: cada paso vuelve a guardarse en borrador) ──
        _pushHistory(key, prev, next) {
            this._history.push({ key, prev, next });
            if (this._history.length > 50) this._history.shift();
            this._future = [];
        },
        get canUndo() { return this._history.length > 0; },
        get canRedo() { return this._future.length > 0; },
        undo() {
            const a = this._history.pop(); if (!a) return;
            this._future.push(a);
            this.setSetting(a.key, a.prev, false);
        },
        redo() {
            const a = this._future.pop(); if (!a) return;
            this._history.push(a);
            this.setSetting(a.key, a.next, false);
        },

        // ── Preview: escala real de escritorio + panel redimensionable ──
        previewWidth: parseInt(localStorage.getItem('bxb_pw') || '430', 10),
        previewScale: 1,
        get previewScaleStyle() {
            const base = this.device === 'desktop' ? 1280 : 390;
            const f = this.previewScale;
            return `width:${base}px;transform:scale(${f});transform-origin:top left;height:calc((100%)/${f});`;
        },
        fitPreview() {
            this.$nextTick(() => {
                const wrap = this.$refs.previewWrap;
                if (!wrap) return;
                const base = this.device === 'desktop' ? 1280 : 390;
                const avail = wrap.clientWidth - 24;
                this.previewScale = Math.min(1, Math.max(0.2, avail / base));
            });
        },
        startPreviewResize(ev) {
            ev.preventDefault();
            const startX = ev.clientX, startW = this.previewWidth;
            const move = (e) => {
                this.previewWidth = Math.min(Math.round(window.innerWidth * 0.65), Math.max(320, startW + (startX - e.clientX)));
                document.documentElement.style.setProperty('--bxb-pw', this.previewWidth + 'px');
                this.fitPreview();
            };
            const up = () => {
                localStorage.setItem('bxb_pw', String(this.previewWidth));
                window.removeEventListener('pointermove', move);
                window.removeEventListener('pointerup', up);
            };
            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', up);
        },
        setDevice(d) { this.device = d; this.fitPreview(); this._post({ type: 'builder:set-device', device: d }); },
        refreshPreview(force = false) {
            clearTimeout(this._previewTimer);
            this._previewTimer = setTimeout(() => {
                const f = this.$refs.previewFrame;
                if (!f) return;
                this.previewError = false;
                try { f.contentWindow.location.reload(); } catch (e) { f.src = f.src; }
            }, force ? 0 : 400);
        },
        highlightForStage() {
            const map = { home: 'hero', appearance: 'hero' };
            if (map[this.stage]) this._post({ type: 'builder:highlight-section', component: map[this.stage] });
            else this._post({ type: 'builder:clear-highlight' });
        },
        _post(msg) {
            const f = this.$refs.previewFrame;
            if (f && f.contentWindow) { try { f.contentWindow.postMessage(msg, window.location.origin); } catch (e) {} }
        },

        // ── Publicación ──
        async publish(confirmWarnings) {
            if (this.publishing) return;
            this.publishing = true;
            try {
                const res = await fetch(this.urls.publish, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ confirm_warnings: !!confirmWarnings }),
                });
                const data = await res.json();
                if (res.status === 409 && data.reason === 'warnings') { this.warningsToConfirm = [...(data.checklist.attention||[]), ...(data.checklist.recommendation||[])]; return; }
                if (!res.ok) { alert(data.message || 'No se pudo publicar. Revisa los pendientes.'); return; }
                this.warningsToConfirm = null;
                this.publishSuccess = data;
                this.pendingDrafts = false;
                this.blocks.forEach(b => b.has_draft = false);
                await this.reloadProgress();
                this.loadChecklist();
                this.refreshPreview(true);
            } catch (e) {
                alert('No se pudo publicar. Revisa tu conexión e inténtalo de nuevo.');
            } finally {
                this.publishing = false;
            }
        },
        async reloadProgress() {
            try {
                const res = await fetch(this.urls.progress, { headers: { 'Accept': 'application/json' } });
                if (res.ok) this.progress = await res.json();
            } catch (e) {}
        },
    };
}

// Editor de campos del checkout (guarda el JSON como borrador vía setSetting del builder).
function ckFieldsEditor() {
    return {
        fields: { fixed: {}, custom: [] },
        newLabel: '',
        init() {
            const root = Alpine.$data(document.querySelector('.bx-builder'));
            let parsed = null;
            try { parsed = JSON.parse(root.settings.checkout_fields || 'null'); } catch (e) {}
            this.fields = (parsed && parsed.fixed) ? parsed : {
                fixed: {
                    lname: { label: 'Apellido', enabled: true },
                    email: { label: 'Email', enabled: true },
                    dni: { label: 'DNI / RUC', enabled: true },
                    address: { label: 'Dirección', enabled: false },
                    notes: { label: 'Notas', enabled: true },
                },
                custom: [],
            };
        },
        persist() {
            const root = Alpine.$data(document.querySelector('.bx-builder'));
            root.setSetting('checkout_fields', JSON.stringify(this.fields));
        },
        addField() {
            const label = (this.newLabel || '').trim();
            if (!label) return;
            this.fields.custom.push({ key: 'cf_' + Date.now(), label, type: 'text', required: false, enabled: true });
            this.newLabel = '';
            this.persist();
        },
    };
}
</script>
