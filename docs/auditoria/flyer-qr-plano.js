/* Composicion del flyer del QR: comprobacion numerica.
 *
 * El flyer no se dibuja con HTML sino en un <canvas>, asi que ni Blade, ni los
 * tests, ni mirar la pantalla detectan que una pieza se salga: hay que revisar
 * las coordenadas. Con el logo al 220% el recuadro blanco del logo empezaba en
 * y = -14 (se cortaba por arriba) y bajaba hasta tapar el texto superior, que
 * es blanco sobre blanco y por eso desaparecia sin dejar rastro. El logo iba
 * ademas sobre un recuadro blanco que la vista previa no dibuja: se quito, la
 * descarga tiene que ser lo mismo que se ve en pantalla.
 *
 * La correccion compone el encabezado en cascada —la banda crece con el logo,
 * el QR se coloca despues y encoge si hace falta— en vez de usar constantes
 * fijas (h*.205 para el texto, h*.27 para el QR) que solo cuadraban con el
 * tamano de logo original, cuando aun no habia control para cambiarlo.
 *
 *   node docs/auditoria/flyer-qr-plano.js [ruta a settings/qr.blade.php]
 *
 * Recorta el metodo `plano` de la vista y prueba 867 combinaciones de formato,
 * forma de logo, tamano, color de cabecera y url visible.
 *
 * NOTA: esta vista esta en deriva. ARIN tiene el autoguardado y el control de
 * tamano de logo; la copia local tiene el storefrontContext de esta rama. El
 * arreglo se aplico sobre la version de ARIN, que es donde ocurre el fallo.
 */
import fs from 'node:fs';

const ruta = process.argv[2] || 'resources/views/settings/qr.blade.php';
const vista = fs.readFileSync(ruta, 'utf8');

/* Se recorta el metodo `plano` de la vista y se evalua tal cual: asi se
   comprueba el codigo que corre de verdad, no una copia que puede haberse
   quedado atras. */
const ini = vista.indexOf('plano(w,h,logo){');
if (ini < 0) {
  console.log('No encuentro el metodo plano() en ' + ruta);
  process.exit(2);
}
let n = 0, fin = vista.indexOf('{', ini);
for (let i = fin; i < vista.length; i++) {
  if (vista[i] === '{') n++;
  else if (vista[i] === '}') { n--; if (!n) { fin = i + 1; break; } }
}
const obj = new Function('return {' + vista.slice(ini, fin) + '}')();

const FORMATOS = { flyer: [1080, 1350], square: [1080, 1080], a4: [2480, 3508] };
const LOGOS = {
  cuadrado: { width: 600, height: 600 },
  ancho:    { width: 1200, height: 300 },
  alto:     { width: 300, height: 900 },
  diminuto: { width: 64, height: 64 },
};

let fallos = 0, total = 0;

function revisa(nombre, p, h, w) {
  const mal = [];
  const dentro = (que, y, alto = 0) => {
    if (y < -0.01) mal.push(que + ' se sale por arriba (y=' + y.toFixed(1) + ')');
    if (y + alto > h + 0.01) mal.push(que + ' se sale por abajo (' + (y + alto).toFixed(1) + ' > ' + h + ')');
  };

  dentro('el logo', p.logo.y, p.logo.h);
  dentro('la tarjeta del QR', p.tarjeta.y, p.tarjeta.h);

  if (p.logo.x < -0.01) mal.push('el logo se sale por el lado');
  if (p.tarjeta.x < -0.01) mal.push('la tarjeta del QR se sale por el lado');
  if (p.textoTop > p.tarjeta.y) mal.push('el texto de arriba queda tapado por la tarjeta del QR');
  if (p.textoTop - p.fTop < p.logo.y + p.logo.h - 0.01) mal.push('el texto de arriba pisa el logo');
  if (p.textoTop > p.banda) mal.push('el texto de arriba se sale de la banda de color');
  if (p.textoBot - p.fBot < p.qr.y + p.qr.s - 0.01) mal.push('el texto de abajo pisa el QR');
  if (p.url - p.fUrl < p.textoBot - 0.01) mal.push('la url pisa el texto de abajo');
  if (p.pie - p.fPie < p.url - 0.01) mal.push('el pie pisa la url');
  if (p.qr.s < w * 0.3) mal.push('el QR queda demasiado pequeno (' + p.qr.s.toFixed(0) + 'px)');

  total++;
  if (mal.length) {
    fallos++;
    console.log('  MAL ' + nombre);
    mal.forEach((m) => console.log('        - ' + m));
  }
}

for (const [fmt, [w, h]] of Object.entries(FORMATOS)) {
  for (const [forma, logo] of Object.entries(LOGOS)) {
    for (const size of [40, 55, 70, 100, 120, 140, 180, 200, 220]) {
      for (const header of ['#0A7A80', '#ffffff', '#111827', '#fde68a']) {
        for (const showUrl of [true, false]) {
          const ctx = { form: { logoSize: size, header, showUrl, showLogo: true, bg: '#fff' } };
          revisa(`${fmt} · ${forma} · ${size}% · ${header} · url:${showUrl}`,
                 obj.plano.call(ctx, w, h, logo), h, w);
        }
      }
    }
  }
}

// Y sin logo, que en su lugar escribe el nombre del negocio.
for (const [fmt, [w, h]] of Object.entries(FORMATOS)) {
  const ctx = { form: { logoSize: 100, header: '#0A7A80', showUrl: true, showLogo: false, bg: '#fff' } };
  revisa(fmt + ' · sin logo', obj.plano.call(ctx, w, h, null), h, w);
}

// El caso que lo destapó, con sus medidas de antes y de ahora.
const ctx = { form: { logoSize: 220, header: '#0A7A80', showUrl: true, showLogo: true, bg: '#fff' } };
const p = obj.plano.call(ctx, 1080, 1350, { width: 600, height: 600 });
console.log('\nBaby Toncito · flyer · logo 220% · cabecera #0A7A80');
console.log('  logo                y=' + p.logo.y.toFixed(1) + '  ' + p.logo.w.toFixed(0) + 'x' + p.logo.h.toFixed(0) + ' px   (antes su recuadro empezaba en y=-14.4, cortado)');
console.log('  texto de arriba     y=' + p.textoTop.toFixed(1) + '   tarjeta del QR y=' + p.tarjeta.y.toFixed(1) + '   (antes el texto caia debajo de la tarjeta)');
console.log('  banda de color      ' + p.banda.toFixed(1) + '   QR ' + p.qr.s.toFixed(0) + 'px');

console.log('\n' + (fallos ? fallos + ' de ' + total + ' MAL' : 'las ' + total + ' combinaciones caben'));
process.exit(fallos ? 1 : 0);
