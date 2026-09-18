{{-- Velo del hero: claro u oscuro, según la foto que ponga el negocio.

     El preset comercial pinta un velo BLANCO al 92 % sobre la mitad izquierda
     para que el texto oscuro se lea. Funciona con fotos claras y arruina las
     oscuras: una foto nocturna quedaba lavada a gris y no se veía el producto.

     No se toca esa regla —otras tiendas dependen de ella—; se ofrece la
     alternativa y el negocio elige en el Constructor. Solo se emite cuando
     está elegida la oscura, así que para el resto no cambia nada. --}}
@if(($settings['hero_veil'] ?? 'claro') === 'oscuro')
<style>
  /* Mismo degradado, del lado del texto, pero en la tinta del negocio: la foto
     se asienta en vez de lavarse. Lleva la especificidad de la regla original
     (`#storefront-main`) porque si no, no la alcanza. */
  #storefront-main .premium-hero.has-bg::before{
    background:linear-gradient(100deg,
      rgba(6,14,34,.88) 0%, rgba(6,14,34,.74) 34%,
      rgba(6,14,34,.28) 56%, rgba(6,14,34,0) 74%)!important;
  }
  @media (max-width:760px){
    /* En móvil el texto ocupa todo el ancho: el velo cubre de arriba abajo. */
    #storefront-main .premium-hero.has-bg::before{
      background:linear-gradient(180deg,
        rgba(6,14,34,.55) 0%, rgba(6,14,34,.80) 55%, rgba(6,14,34,.92) 100%)!important;
    }
  }
  /* Sobre fondo oscuro el texto tiene que ir claro, o no se lee nada. */
  #storefront-main .premium-hero.has-bg .ph-title,
  #storefront-main .premium-hero.has-bg .premium-hero-copy h1,
  #storefront-main .premium-hero.has-bg .premium-hero-copy h2{color:#fff!important}
  #storefront-main .premium-hero.has-bg .ph-sub,
  #storefront-main .premium-hero.has-bg .premium-hero-copy p{color:rgba(255,255,255,.92)!important}
  #storefront-main .premium-hero.has-bg .button-ghost{
    color:#fff!important; border-color:rgba(255,255,255,.6)!important;
    background:rgba(255,255,255,.10)!important;
  }
</style>
@endif
