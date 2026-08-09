{{-- CARRITO EN PÁGINA COMPLETA: toda apertura del carrito lleva a la vista
     de página completa que ya existe en el núcleo (cartPageOpen). Sin drawer.
     Cero lógica duplicada: un observador redirige el estado. --}}
<div x-data x-init="$watch('cartOpen', value => { if (value) { cartOpen = false; openCartPage(); } })" aria-hidden="true"></div>
