{{-- Modal de confirmacion global (window.__confirm).
     Vivia dentro del shell del panel, asi que las pantallas servidas por el
     shell comercial se quedaban SIN el: eliminar producto, eliminar imagen,
     categorias, servicios y descartar borrador llaman `window.__confirm(...)`
     sin comprobar que exista, asi que el boton reventaba en silencio.
     Ahora es un partial que incluyen los DOS shells: un solo modal, sin
     copiar Blade (unificacion 2026-08-30). --}}
    <div x-data="{
            show: false,
            title: '',
            msg: '',
            confirmLabel: 'Eliminar',
            cancelLabel: 'Cancelar',
            confirmClass: 'bg-red-600 hover:bg-red-700 text-white',
            _resolve: null,
            _confirmHandler: null,
            _previousFocus: null,
            _previousOverflow: '',
            _focusFrame: null,
            _focusAttempts: 0,
            _maxFocusAttempts: 4,
            _userInteracted: false,
            init() {
                this._confirmHandler = (opts) => this.open(opts);
                window.__confirm = this._confirmHandler;
            },
            destroy() {
                if (window.__confirm === this._confirmHandler) {
                    delete window.__confirm;
                }
                this.cancelPendingFocus();
                if (this._resolve) this._settle(false, false);
            },
            open(opts) {
                const options = opts !== null && typeof opts === 'object' && !Array.isArray(opts)
                    ? opts
                    : null;
                if (!options) return Promise.resolve(false);

                if (this._resolve) this._settle(false, false);

                const title = typeof options.title === 'string' ? options.title.trim() : '';
                const message = typeof options.msg === 'string'
                    ? options.msg
                    : (typeof options.message === 'string' ? options.message : '');
                const confirmText = typeof options.confirmLabel === 'string'
                    ? options.confirmLabel.trim()
                    : (typeof options.confirmText === 'string' ? options.confirmText.trim() : '');
                const cancelText = typeof options.cancelLabel === 'string'
                    ? options.cancelLabel.trim()
                    : (typeof options.cancelText === 'string' ? options.cancelText.trim() : '');
                const confirmClass = typeof options.confirmClass === 'string'
                    ? options.confirmClass.trim()
                    : '';

                this.title        = title || 'Â¿Confirmar acciÃ³n?';
                this.msg          = message;
                this.confirmLabel = confirmText || 'Confirmar';
                this.cancelLabel  = cancelText || 'Cancelar';
                this.confirmClass = confirmClass || 'bg-red-600 hover:bg-red-700 text-white';
                this._previousFocus = document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;
                this._previousOverflow = document.body.style.overflow;
                this.cancelPendingFocus();
                this._focusAttempts = 0;
                this._userInteracted = false;
                document.body.style.overflow = 'hidden';
                this.show = true;
                this.$nextTick(() => this.focusInitial());
                return new Promise(r => this._resolve = r);
            },
            focusInitial() {
                if (!this.show || this._userInteracted) return false;

                this._focusAttempts += 1;
                const dialog = this.$refs.dialog;
                const cancelButton = this.$refs.cancelButton;
                const modal = this.$root;
                const rect = cancelButton?.getBoundingClientRect();
                const style = cancelButton ? getComputedStyle(cancelButton) : null;
                const canFocus = !!(
                    dialog && cancelButton && !cancelButton.disabled && !cancelButton.hidden &&
                    !cancelButton.closest('[inert]') && rect && rect.width > 0 && rect.height > 0 &&
                    style?.display !== 'none' && style?.visibility !== 'hidden' &&
                    getComputedStyle(modal).display !== 'none'
                );

                if (canFocus) {
                    try {
                        cancelButton.focus({ preventScroll: true });
                    } catch (error) {
                        cancelButton.focus();
                    }
                    if (dialog.contains(document.activeElement)) return true;
                }

                if (this.show && !this._userInteracted && this._focusAttempts < this._maxFocusAttempts) {
                    this._focusFrame = requestAnimationFrame(() => {
                        this._focusFrame = null;
                        this.focusInitial();
                    });
                }
                return false;
            },
            cancelPendingFocus() {
                if (this._focusFrame !== null) {
                    cancelAnimationFrame(this._focusFrame);
                    this._focusFrame = null;
                }
            },
            markInteracted() {
                this._userInteracted = true;
                this.cancelPendingFocus();
            },
            _settle(value, restoreFocus = true) {
                const resolve = this._resolve;
                this._resolve = null;
                this.cancelPendingFocus();
                this._userInteracted = true;
                this.show = false;
                document.body.style.overflow = this._previousOverflow;
                const previousFocus = this._previousFocus;
                this._previousFocus = null;
                if (restoreFocus && previousFocus) {
                    this.$nextTick(() => previousFocus.focus());
                }
                if (resolve) resolve(value);
            },
            confirm() { this._settle(true); },
            cancel() { this._settle(false); },
            trapFocus(event) {
                this.markInteracted();
                const controls = [this.$refs.cancelButton, this.$refs.confirmButton]
                    .filter(control => control && !control.disabled);
                if (!controls.length) return;
                const current = controls.indexOf(document.activeElement);
                const next = event.shiftKey
                    ? (current <= 0 ? controls.length - 1 : current - 1)
                    : (current === controls.length - 1 ? 0 : current + 1);
                controls[next].focus();
            }
         }"
         x-show="show" x-cloak
         data-global-confirm-modal
         role="dialog" aria-modal="true"
         aria-labelledby="global-confirm-title"
         aria-describedby="global-confirm-message"
         @keydown.escape.window="show && cancel()"
         @keydown.tab.prevent="show && trapFocus($event)"
         class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="cancel()"></div>
        <div x-ref="dialog" @pointerdown="markInteracted()"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="global-confirm-title" class="text-sm font-semibold text-gray-900" x-text="title"></h3>
                    {{-- whitespace-pre-line: permite explicar la consecuencia en un
                         párrafo aparte usando \n, no solo una frase suelta. --}}
                    <p id="global-confirm-message" class="text-xs text-gray-500 mt-1 leading-relaxed whitespace-pre-line" x-text="msg"></p>
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" x-ref="cancelButton" @click="cancel()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                        x-text="cancelLabel"></button>
                <button type="button" x-ref="confirmButton" @click="confirm()"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition"
                        :class="confirmClass"
                        x-text="confirmLabel">
                </button>
            </div>
        </div>
    </div>
