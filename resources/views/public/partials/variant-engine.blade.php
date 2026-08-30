{{--
  Motor de variantes compartido por los dos motores de tienda.

  Las reglas de disponibilidad, precio, stock y linea de carrito viven aqui y
  en ningun otro sitio. Antes estaban copiadas en la ficha publica y en
  Ecommerce con distinto nombre de variable, de modo que corregir un caso
  borde en uno dejaba el otro con el comportamiento viejo.

  Son funciones puras: reciben las variantes y la seleccion, y devuelven el
  resultado. Cada motor conserva su propio estado y su propia apariencia.

  El precio que se calcula aqui es SOLO para pintar. El servidor revalida
  precio, stock y pertenencia al proyecto en el checkout y nunca acepta lo que
  manda el navegador.
--}}
<script>
window.BixoVariantes = (function () {
  'use strict';

  const texto = (valor) => String(valor ?? '');
  const lista = (variantes) => Array.isArray(variantes) ? variantes : [];

  /** Mapa atributoId => valorId de una variante concreta. */
  function valoresDe(variante) {
    return new Map((variante.values || []).map(v => [texto(v.attributeId), texto(v.valueId)]));
  }

  /**
   * Atributos que hay que elegir, deducidos de las propias variantes.
   * Conserva el orden en que llegan: el servidor ya los ordena por sort_order.
   */
  function atributos(variantes) {
    const acumulado = new Map();
    lista(variantes).forEach(variante => (variante.values || []).forEach(valor => {
      const aid = texto(valor.attributeId), vid = texto(valor.valueId);
      if (!acumulado.has(aid)) {
        acumulado.set(aid, { id: aid, name: valor.attribute || 'Opción', type: valor.type || 'button', values: [] });
      }
      const atributo = acumulado.get(aid);
      if (!atributo.values.some(item => item.id === vid)) {
        atributo.values.push({ id: vid, label: valor.label, color: valor.color || null });
      }
    }));
    return Array.from(acumulado.values());
  }

  /** La variante que cumple TODA la seleccion, o null si falta elegir algo. */
  function seleccionada(variantes, seleccion) {
    const items = lista(variantes);
    if (!items.length) return null;
    const attrs = atributos(items);
    if (!attrs.every(a => seleccion[a.id])) return null;
    return items.find(variante => {
      const valores = valoresDe(variante);
      return attrs.every(a => valores.get(a.id) === seleccion[a.id]);
    }) || null;
  }

  /**
   * Si elegir este valor deja alguna combinacion con stock.
   * Un stock 0 descarta la variante; un stock nulo significa "sin control de
   * inventario" y por tanto disponible.
   */
  function disponible(variantes, seleccion, atributoId, valorId) {
    const propuesta = { ...seleccion, [texto(atributoId)]: texto(valorId) };
    return lista(variantes).some(variante => {
      if (variante.stock === 0) return false;
      const valores = valoresDe(variante);
      return Object.entries(propuesta).every(([aid, vid]) => !vid || valores.get(aid) === vid);
    });
  }

  /** Etiqueta del valor elegido en un atributo, para el resumen del selector. */
  function etiqueta(variantes, seleccion, atributoId) {
    const aid = texto(atributoId);
    const atributo = atributos(variantes).find(item => item.id === aid);
    return atributo?.values.find(v => v.id === seleccion[aid])?.label || '';
  }

  /** Devuelve una seleccion NUEVA: Alpine necesita el cambio de referencia. */
  function elegir(seleccion, atributoId, valorId) {
    return { ...seleccion, [texto(atributoId)]: texto(valorId) };
  }

  /** Falta elegir alguna opcion habiendo variantes. */
  function faltaElegir(variantes, seleccion) {
    return lista(variantes).length > 0 && !seleccionada(variantes, seleccion);
  }

  /**
   * Dos variantes del mismo producto son DOS lineas distintas del carrito.
   * Sin variante se usa 'base' para no chocar con los carritos antiguos de
   * localStorage, que no guardaban variantId.
   */
  function claveLinea(productoId, varianteId) {
    return texto(productoId) + ':' + texto(varianteId || 'base');
  }

  /** Precio, imagen y stock efectivos: la variante manda, el producto hereda. */
  function efectivo(producto, variante) {
    return {
      price: Number(variante?.price ?? producto?.price ?? 0),
      comparePrice: Number(variante?.comparePrice ?? producto?.cp ?? producto?.comparePrice ?? 0) || null,
      stock: variante ? variante.stock : (producto?.stock ?? null),
      sku: variante?.sku || producto?.sku || '',
      image: variante?.image || producto?.img || producto?.image || null,
      label: variante?.label || null,
    };
  }

  /** Linea de carrito ya resuelta, lista para empujar al array. */
  function linea(producto, variante, cantidad) {
    const datos = efectivo(producto, variante);
    return {
      id: producto.id,
      variantId: variante?.id || null,
      lineKey: claveLinea(producto.id, variante?.id),
      name: producto.name + (datos.label ? ' · ' + datos.label : ''),
      price: datos.price,
      img: datos.image,
      cat: producto.cat ?? null,
      qty: Number(cantidad) > 0 ? Number(cantidad) : 1,
    };
  }

  /**
   * Carritos guardados antes de las variantes: sus lineas no traen lineKey y
   * al agregar hoy el mismo producto se duplicaban en vez de fusionarse.
   * Se les asigna la clave al cargar; las lineas nuevas se dejan como estan.
   */
  function normalizarLineas(carrito) {
    return (Array.isArray(carrito) ? carrito : []).map(item => {
      if (!item || typeof item !== 'object') return item;
      if (item.lineKey) return item;
      return { ...item, variantId: item.variantId || null, lineKey: claveLinea(item.id, item.variantId) };
    });
  }

  return { atributos, seleccionada, disponible, etiqueta, elegir, faltaElegir, claveLinea, efectivo, linea, normalizarLineas };
})();
</script>
