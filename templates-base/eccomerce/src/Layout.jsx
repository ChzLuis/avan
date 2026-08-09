/* GABDE — Layout: TopBar, Header, Nav, Footer, Cart preview, Toast host */

const PROMOS = [
  '🚚 ENVÍO GRATIS en compras superiores a $999 MXN',
  '⚡ Entrega en 2 horas en CDMX, GDL y MTY',
  '🎁 Hasta 12 MSI sin intereses con bancos seleccionados',
];

function TopBar({ businessName }) {
  const [i, setI] = useState(0);
  useEffect(() => {
    const t = setInterval(() => setI(p => (p + 1) % PROMOS.length), 5000);
    return () => clearInterval(t);
  }, []);
  return (
    <div className="topbar" role="region" aria-label="Información promocional">
      <div className="topbar-inner">
        <span className="topbar-promo">
          <span className="rot" key={i} style={{ animation: 'fadeIn 320ms var(--ease)' }}>
            {PROMOS[i]}
          </span>
        </span>
        <nav className="topbar-links" aria-label="Atención">
          <a href="#">Preguntas frecuentes</a>
          <a href="#">Sucursales</a>
          <a href="#">Atención a clientes</a>
        </nav>
      </div>
    </div>
  );
}

function Header({ businessName, route, go, cart, wishCount, onMenu }) {
  const [query, setQuery] = useState('');
  const [focused, setFocused] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [hoverCart, setHoverCart] = useState(false);
  const [hoverUser, setHoverUser] = useState(false);
  const cartRef = useRef(null);

  const results = useMemo(() => {
    if (!query) return [];
    const q = query.toLowerCase();
    return window.GABDE_DATA.products
      .filter(p => p.name.toLowerCase().includes(q) || p.brand.toLowerCase().includes(q) || p.cat.toLowerCase().includes(q))
      .slice(0, 5);
  }, [query]);

  const cartCount = cart.reduce((s, l) => s + l.qty, 0);

  return (
    <header className={`header ${searchOpen ? 'search-open' : ''}`}>
      <div className="header-inner">
        <button className="icon-btn hamburger" onClick={onMenu} aria-label="Abrir menú">
          {I.menu}
        </button>
        <a onClick={() => go({ name: 'home' })} style={{cursor:'pointer'}}>
          <Logo name={businessName} />
        </a>
        <div className="search">
          <input
            className="search-input"
            placeholder="Buscar productos, marcas o categorías…"
            value={query}
            onChange={e => setQuery(e.target.value)}
            onFocus={() => setFocused(true)}
            onBlur={() => setTimeout(() => setFocused(false), 200)}
            aria-label="Buscar"
          />
          <span className="search-icon">{I.search}</span>
          {focused && (query ? results.length : true) && (
            <div className="search-dropdown" role="listbox">
              {!query && (
                <>
                  <div className="sd-section">Búsquedas frecuentes</div>
                  {['Audífonos inalámbricos', 'Laptop ligera 14"', 'Reloj con GPS', 'Hub USB-C'].map(s => (
                    <div key={s} className="sd-item" onMouseDown={() => { setQuery(s); }}>
                      <span style={{width:36,height:36, display:'grid',placeItems:'center', background:'var(--bg-inset)', borderRadius:'var(--radius-sm)', color:'var(--text-secondary)'}}>{I.search}</span>
                      <span className="sd-name">{s}</span>
                    </div>
                  ))}
                </>
              )}
              {query && results.length > 0 && (
                <>
                  <div className="sd-section">Productos</div>
                  {results.map(p => (
                    <div key={p.id} className="sd-item" onMouseDown={() => { setQuery(''); go({ name: 'pdp', id: p.id }); }}>
                      <div className="sd-thumb">
                        <Placeholder palette={window.GABDE_DATA.palette[p.cat]} label={p.name} accent={p.colors[0]} />
                      </div>
                      <div style={{minWidth:0}}>
                        <div className="sd-name">{p.name}</div>
                        <div className="sd-meta">{p.brand} · {p.sku}</div>
                      </div>
                      <span className="sd-price mono">{fmtPrice(p.price)}</span>
                    </div>
                  ))}
                </>
              )}
              {query && !results.length && (
                <div style={{padding:'16px 10px', color:'var(--text-muted)', fontSize:13}}>
                  Sin resultados para "{query}".
                </div>
              )}
            </div>
          )}
        </div>
        <div className="header-icons">
          <button className="icon-btn" aria-label="Buscar" onClick={() => setSearchOpen(s => !s)} style={{display:'none'}}>{I.search}</button>
          <div onMouseEnter={() => setHoverUser(true)} onMouseLeave={() => setHoverUser(false)} style={{position:'relative'}}>
            <button className="icon-btn" onClick={() => go({ name: 'account' })} aria-label="Mi cuenta">{I.user}</button>
            {hoverUser && (
              <div className="cart-preview" style={{width: 220}}>
                <h5>Hola, Andrea</h5>
                <div style={{display:'flex', flexDirection:'column', gap:2}}>
                  <a onClick={() => go({ name: 'account', tab: 'orders' })} className="sd-item" style={{cursor:'pointer'}}><span className="sd-name">Mis pedidos</span></a>
                  <a onClick={() => go({ name: 'account', tab: 'addresses' })} className="sd-item" style={{cursor:'pointer'}}><span className="sd-name">Direcciones</span></a>
                  <a onClick={() => go({ name: 'account', tab: 'wishlist' })} className="sd-item" style={{cursor:'pointer'}}><span className="sd-name">Lista de deseos</span></a>
                  <a className="sd-item" style={{cursor:'pointer'}}><span className="sd-name" style={{color:'var(--danger)'}}>Cerrar sesión</span></a>
                </div>
              </div>
            )}
          </div>
          <button className="icon-btn" onClick={() => go({ name: 'account', tab: 'wishlist' })} aria-label="Lista de deseos">
            {I.heart}
            {wishCount > 0 && <span className="icon-badge">{wishCount}</span>}
          </button>
          <div ref={cartRef} onMouseEnter={() => setHoverCart(true)} onMouseLeave={() => setHoverCart(false)} style={{position:'relative'}}>
            <button id="cart-icon-btn" className="icon-btn" onClick={() => go({ name: 'cart' })} aria-label="Carrito">
              {I.cart}
              {cartCount > 0 && <span className="icon-badge">{cartCount}</span>}
            </button>
            {hoverCart && cart.length > 0 && (
              <CartPreview cart={cart} onView={() => go({ name: 'cart' })} onCheckout={() => go({ name: 'checkout' })} />
            )}
          </div>
        </div>
      </div>
    </header>
  );
}

function CartPreview({ cart, onView, onCheckout }) {
  const subtotal = cart.reduce((s, l) => s + l.price * l.qty, 0);
  return (
    <div className="cart-preview" role="dialog" aria-label="Resumen del carrito">
      <h5>Tu carrito ({cart.length})</h5>
      <div style={{maxHeight:240, overflowY:'auto'}}>
        {cart.slice(0, 3).map(l => (
          <div key={l.id} className="cp-row">
            <div className="cp-thumb">
              <Placeholder palette={window.GABDE_DATA.palette[l.cat]} label={l.name} accent={l.color} />
            </div>
            <div style={{minWidth:0}}>
              <div className="cp-name" style={{whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis'}}>{l.name}</div>
              <div className="cp-qty">Cant. {l.qty}</div>
            </div>
            <span className="cp-price">{fmtPrice(l.price * l.qty)}</span>
          </div>
        ))}
      </div>
      {cart.length > 3 && <div style={{fontSize:12, color:'var(--text-muted)', padding:'6px 0'}}>+ {cart.length - 3} más</div>}
      <div className="cp-foot">
        <div className="cp-total">
          <span>Subtotal</span>
          <span className="mono">{fmtPrice(subtotal)}</span>
        </div>
        <button className="btn btn-outline btn-sm" onClick={onView}>Ver carrito</button>
        <button className="btn btn-primary btn-sm" onClick={onCheckout}>Finalizar compra</button>
      </div>
    </div>
  );
}

function Nav({ route, go }) {
  const [open, setOpen] = useState(null);
  const items = [
    { id: 'home',    label: 'Inicio' },
    { id: 'plp',     label: 'Productos', sub: window.GABDE_DATA.categories },
    { id: 'offers',  label: 'Ofertas', pill: '-30%' },
    { id: 'new',     label: 'Novedades' },
    { id: 'brands',  label: 'Marcas', sub: window.GABDE_DATA.brands },
    { id: 'blog',    label: 'Blog & Guías' },
    { id: 'contact', label: 'Contacto' },
  ];
  return (
    <nav className="nav" aria-label="Categorías">
      <div className="nav-inner">
        {items.map(it => (
          <div
            key={it.id}
            className="nav-item"
            aria-current={route.name === it.id ? 'page' : undefined}
            onClick={() => go({ name: it.id === 'plp' ? 'plp' : it.id === 'offers' ? 'plp' : it.id === 'new' ? 'plp' : it.id === 'brands' ? 'plp' : it.id })}
            onMouseEnter={() => setOpen(it.id)}
            onMouseLeave={() => setOpen(null)}
          >
            {it.label}
            {it.pill && <span className="nav-pill">{it.pill}</span>}
            {it.sub && open === it.id && (
              <div className="nav-flyout" onMouseEnter={() => setOpen(it.id)}>
                {it.sub.map(s => (
                  <a key={s} onClick={(e) => { e.stopPropagation(); go({ name: 'plp', cat: it.id === 'plp' ? s : undefined, brand: it.id === 'brands' ? s : undefined }); }}>{s}</a>
                ))}
              </div>
            )}
          </div>
        ))}
      </div>
    </nav>
  );
}

// ─── Footer ───────────────────────────────────────────────────────
function Footer({ businessName, year = 2026 }) {
  return (
    <footer className="footer">
      <div className="page">
        <div className="footer-newsletter">
          <div>
            <h3>Únete al club {businessName}.</h3>
            <p>Suscríbete y recibe 10% OFF en tu primer pedido + acceso anticipado a lanzamientos.</p>
          </div>
          <form className="newsletter-form" onSubmit={(e) => { e.preventDefault(); alert('Suscripción enviada (demo).'); }}>
            <input type="email" placeholder="tu@correo.com" aria-label="Email" required/>
            <button className="btn" style={{background:'var(--primary)', color:'var(--primary-ink)'}}>Suscribirme</button>
          </form>
        </div>

        <div className="trust-strip">
          <div className="trust-item">{I.shield}<div><div className="ti-title">Pago 100% seguro</div><div className="ti-sub">Encriptación SSL · 3DS2</div></div></div>
          <div className="trust-item">{I.truck}<div><div className="ti-title">Envíos rastreables</div><div className="ti-sub">DHL · FedEx · Estafeta</div></div></div>
          <div className="trust-item">{I.refresh}<div><div className="ti-title">Devolución 30 días</div><div className="ti-sub">Sin preguntas</div></div></div>
          <div className="trust-item">{I.chat}<div><div className="ti-title">Atención 24/7</div><div className="ti-sub">Chat, WhatsApp y mail</div></div></div>
        </div>

        <div className="footer-cols">
          <div className="footer-col">
            <Logo name={businessName} />
            <p style={{color:'var(--text-secondary)', fontSize:13, marginTop:10, maxWidth: '28ch'}}>
              Tecnología de calidad, curada y entregada con cuidado. Fundada en 2018.
            </p>
            <div className="footer-social">
              <button className="icon-btn" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.8" fill="currentColor"/></svg></button>
              <button className="icon-btn" aria-label="X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 4h3l-7 8 8 8h-6l-5-6-6 6H2l8-9-8-7h6l4 5z"/></svg></button>
              <button className="icon-btn" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 3v2.5a4.5 4.5 0 0 0 4.5 4.5V13a7.5 7.5 0 0 1-4.5-1.5V16a5 5 0 1 1-5-5v3a2 2 0 1 0 2 2V3z"/></svg></button>
              <button className="icon-btn" aria-label="WhatsApp"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg></button>
            </div>
          </div>
          <div className="footer-col">
            <h6>Compra</h6>
            <ul><li><a>Todas las categorías</a></li><li><a>Novedades</a></li><li><a>Ofertas</a></li><li><a>Marcas</a></li><li><a>Tarjeta de regalo</a></li></ul>
          </div>
          <div className="footer-col">
            <h6>Ayuda</h6>
            <ul><li><a>Envíos y entregas</a></li><li><a>Devoluciones</a></li><li><a>Garantía</a></li><li><a>Preguntas frecuentes</a></li><li><a>Rastrea tu pedido</a></li></ul>
          </div>
          <div className="footer-col">
            <h6>{businessName}</h6>
            <ul><li><a>Sobre nosotros</a></li><li><a>Tiendas</a></li><li><a>Sostenibilidad</a></li><li><a>Trabaja con nosotros</a></li><li><a>Programa de afiliados</a></li></ul>
          </div>
          <div className="footer-col">
            <h6>Pago & envío</h6>
            <div className="pay-icons">
              <span className="pay-pill">VISA</span>
              <span className="pay-pill">MC</span>
              <span className="pay-pill">AMEX</span>
              <span className="pay-pill">OXXO</span>
              <span className="pay-pill">SPEI</span>
              <span className="pay-pill">PAYPAL</span>
              <span className="pay-pill">MERCADO</span>
            </div>
            <h6 style={{marginTop: 18}}>App</h6>
            <div className="pay-icons">
              <span className="pay-pill" style={{height:32, padding:'0 12px'}}>📱 App Store</span>
              <span className="pay-pill" style={{height:32, padding:'0 12px'}}>🤖 Google Play</span>
            </div>
          </div>
        </div>

        <div className="footer-base">
          <span>© {year} {businessName}. Todos los derechos reservados. <a style={{marginLeft:8}}>Privacidad</a> · <a>Términos</a> · <a>Aviso legal</a></span>
          <span className="locale">
            <select aria-label="Idioma"><option>ES</option><option>EN</option><option>PT</option></select>
            <select aria-label="Moneda"><option>MXN $</option><option>USD $</option><option>EUR €</option><option>COP $</option></select>
          </span>
        </div>
      </div>
    </footer>
  );
}

// ─── Toast Host ────────────────────────────────────────────────────
function ToastHost({ toasts, onUndo }) {
  return (
    <div className="toast-host" role="status" aria-live="polite">
      {toasts.map(t => (
        <div key={t.id} className="toast">
          {t.thumb && <div className="toast-thumb"><Placeholder palette={t.palette || ['#1f2937','#475569']} label={t.label}/></div>}
          <span>{t.text}</span>
          {t.undo && <button className="toast-undo" onClick={() => onUndo(t.id)}>Deshacer</button>}
        </div>
      ))}
    </div>
  );
}

// ─── Back to top ───────────────────────────────────────────────────
function BackToTop({ scrollRef }) {
  const [show, setShow] = useState(false);
  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;
    const f = () => setShow(el.scrollTop > 320);
    el.addEventListener('scroll', f);
    return () => el.removeEventListener('scroll', f);
  }, [scrollRef]);
  return (
    <button className={`back-top ${show ? 'show' : ''}`} onClick={() => scrollRef.current?.scrollTo({top:0, behavior:'smooth'})} aria-label="Volver arriba">
      {I.arrowTop}
    </button>
  );
}

// ─── Mobile drawer for nav ────────────────────────────────────────
function MobileMenu({ open, onClose, businessName, go }) {
  const [expanded, setExpanded] = useState(null);
  const cats = window.GABDE_DATA.categories;
  return (
    <>
      <div className={`drawer-backdrop ${open ? 'show' : ''}`} onClick={onClose}/>
      <aside className={`drawer drawer-left ${open ? 'show' : ''}`} aria-hidden={!open}>
        <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', padding:'14px 16px', borderBottom:'1px solid var(--border)'}}>
          <Logo name={businessName}/>
          <button className="icon-btn" onClick={onClose} aria-label="Cerrar">{I.close}</button>
        </div>
        <div style={{padding: 8}}>
          {['Inicio','Productos','Ofertas','Novedades','Marcas','Blog','Contacto'].map((label, i) => (
            <div key={label}>
              <button
                onClick={() => {
                  if (label === 'Productos' || label === 'Marcas') {
                    setExpanded(expanded === label ? null : label);
                  } else {
                    onClose();
                    go({ name: label === 'Inicio' ? 'home' : 'plp' });
                  }
                }}
                style={{display:'flex', alignItems:'center', justifyContent:'space-between', width:'100%', height:48, padding:'0 12px', borderRadius:8, border:0, background:'transparent', fontSize:15, fontWeight:500, cursor:'pointer', color:'var(--text-primary)'}}>
                {label}
                {(label === 'Productos' || label === 'Marcas') && <span style={{transform: expanded === label ? 'rotate(90deg)':'none', transition:'transform 200ms'}}>›</span>}
              </button>
              {expanded === label && (
                <div style={{paddingLeft: 16, display:'flex', flexDirection:'column', gap:4, paddingBottom:8}}>
                  {(label === 'Productos' ? cats : window.GABDE_DATA.brands).map(s => (
                    <button key={s}
                      onClick={() => { onClose(); go({ name: 'plp', cat: label === 'Productos' ? s : undefined, brand: label === 'Marcas' ? s : undefined }); }}
                      style={{textAlign:'left', height:40, padding:'0 12px', border:0, background:'transparent', borderRadius:8, fontSize:13.5, color:'var(--text-secondary)', cursor:'pointer'}}>{s}</button>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      </aside>
    </>
  );
}

Object.assign(window, { TopBar, Header, Nav, Footer, ToastHost, BackToTop, MobileMenu });
