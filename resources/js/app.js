import './bootstrap';

import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';
// Trampa de foco para dialogos modales (F1c): con x-trap el teclado no puede
// escaparse del modal por detras, y al cerrarlo el foco vuelve al disparador.
import Focus from '@alpinejs/focus';

Alpine.plugin(Collapse);
Alpine.plugin(Focus);

window.Alpine = Alpine;

Alpine.start();
