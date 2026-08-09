/* GABDE — PDP (product detail), Cart, Checkout, Account screens */

function PDPScreen({ route, go, addToCart, toggleWish, wishlist, cardVariant }) {
  const product = window.GABDE_DATA.products.find(p => p.id === route.id) || window.GABDE_DATA.products[0];
  const [galleryIdx, setGalleryIdx] = useState(0);
  const [color, setColor] = useState(product.colors[0]);
  const [option, setOption] = useState(null); // generic option (storage, size…)
  const [qty, setQty] = useState(1);
  const [added, setAdded] = useState(false);

  // related
  const related = window.GABDE_DATA.products.filter(p => p.cat === product.cat && p.id !== product.id).slice(0, 4);

  const pct = product.compareAt ? Math.round((1 - product.price / product.compareAt) * 100) : 0;

  const handleAdd = () => {
    addToCart({ ...product, color }, qty);
    setAdded(true);
    setTimeout(() => setAdded(false), 1800);
  };

  // dynamic option groups by category
  const opts = [];
  if (product.attrs.storage) opts.push({label:'Almacenamiento', current: product.attrs.storage, options: ['128GB','256GB','512GB','1TB']});
  if (product.attrs.ram)     opts.push({label:'RAM', current: product.attrs.ram, options: ['8GB','16GB','32GB']});
  if (product.attrs.size)    opts.push({label:'Tamaño', current: product.attrs.size, options: ['38mm','42mm','46mm']});

  return (
    <main id="content" className="page page-pad fade-in">
      <nav className="breadcrumbs">
        <a onClick={() => go({name:'home'})} style={{cursor:'pointer'}}>Inicio</a><span>/</span>
        <a onClick={() => go({name:'plp', cat: product.cat})} style={{cursor:'pointer'}}>{product.cat}</a><span>/</span>
        <span style={{color:'var(--text-primary)'}}>{product.name}</span>
      </nav>

      <div className="pdp">
        <div className="pdp-gallery">
          <div className="pdp-thumbs" role="tablist">
            {[0,1,2,3].map(i => (
              <button key={i} className="pdp-thumb" aria-pressed={galleryIdx === i} onClick={() => setGalleryIdx(i)}>
                <Placeholder palette={window.GABDE_DATA.palette[product.cat]} label={product.name + i} accent={product.colors[i % product.colors.length]} />
              </button>
            ))}
          </div>
          <div className="pdp-main">
            <Placeholder palette={window.GABDE_DATA.palette[product.cat]} label={product.name + galleryIdx} accent={product.colors[galleryIdx % product.colors.length] || color}/>
            <div className="card-badges" style={{top:16, left:16, position:'absolute'}}>
              {product.badges.slice(0,2).map(b => (
                <span key={b} className={
                  b === 'Agotado' ? 'badge badge-out' :
                  b.includes('-') ? 'badge badge-sale' :
                  b === 'Nuevo' ? 'badge badge-new' :
                  b === 'Más vendido' ? 'badge badge-hot' :
                  'badge badge-soft'
                }>{b}</span>
              ))}
            </div>
          </div>
        </div>

        <div className="pdp-info">
          <div className="card-brand">{product.brand} · SKU {product.sku}</div>
          <h1 className="pdp-title">{product.name}</h1>
          <div className="pdp-meta">
            <Stars value={product.rating}/>
            <span>{product.rating} · {product.reviews} reseñas</span>
            <span>·</span>
            <span style={{color: product.stock > 0 ? 'var(--success)' : 'var(--danger)'}}>
              {product.stock > 0 ? `En stock (${product.stock})` : 'Agotado'}
            </span>
          </div>
          <div className="pdp-price">
            <span className="price-now mono">{fmtPrice(product.price)}</span>
            {product.compareAt && <span className="price-was mono">{fmtPrice(product.compareAt)}</span>}
            {pct > 0 && <span className="price-pct" style={{fontSize:14}}>-{pct}%</span>}
          </div>
          <p style={{color:'var(--text-secondary)', fontSize: 14, lineHeight: 1.55, maxWidth:'52ch'}}>
            Cancelación activa de ruido, audio espacial y batería de hasta {product.attrs.battery || 'larga duración'}. Diseño minimalista en aluminio reciclado con perfil delgado. Conectividad {product.attrs.connectivity || 'inalámbrica'} de baja latencia.
          </p>

          <div className="pdp-opt-grp">
            <h5>Color <small>{color}</small></h5>
            <div className="opt-pills">
              {product.colors.map(c => (
                <button key={c} className="opt-pill" aria-pressed={color === c} onClick={() => setColor(c)} style={{minWidth: 40, padding: 0, width:40}}>
                  <span style={{width:22, height:22, borderRadius:'50%', background: c, display:'inline-block', outline:'1px solid var(--border-strong)'}}/>
                </button>
              ))}
            </div>
          </div>

          {opts.map(o => (
            <div key={o.label} className="pdp-opt-grp">
              <h5>{o.label} <small>{o.current}</small></h5>
              <div className="opt-pills">
                {o.options.map(v => (
                  <button key={v} className="opt-pill" aria-pressed={o.current === v} disabled={v !== o.current && Math.random() < 0.3 && false}>{v}</button>
                ))}
              </div>
            </div>
          ))}

          <div className="pdp-opt-grp">
            <h5>Cantidad</h5>
            <div className="qty-stepper">
              <button onClick={() => setQty(q => Math.max(1, q - 1))} aria-label="Menos">−</button>
              <input value={qty} onChange={e => setQty(Math.max(1, +e.target.value || 1))} aria-label="Cantidad"/>
              <button onClick={() => setQty(q => q + 1)} aria-label="Más">+</button>
            </div>
          </div>

          <div className="pdp-cta">
            <button className="btn btn-primary btn-lg" disabled={product.stock === 0} onClick={handleAdd}>
              {product.stock === 0 ? 'Agotado' : added ? '✓ Agregado al carrito' : 'Agregar al carrito'}
            </button>
            <button className="btn btn-outline btn-lg" onClick={() => { handleAdd(); go({name:'checkout'}); }} disabled={product.stock === 0}>
              Comprar ahora
            </button>
            <button className="icon-btn" onClick={() => toggleWish(product)} aria-label="Wishlist" style={{width:52, height:52, border:'1px solid var(--border-strong)', borderRadius:'var(--radius-md)'}}>
              {wishlist.has(product.id) ? I.heartF : I.heart}
            </button>
          </div>

          <div className="pdp-features">
            <div className="pdp-feature">{I.truck}<div><b>Envío gratis</b><span>En pedidos +$999</span></div></div>
            <div className="pdp-feature">{I.refresh}<div><b>30 días devolución</b><span>Sin preguntas</span></div></div>
            <div className="pdp-feature">{I.shield}<div><b>2 años garantía</b><span>Cobertura GABDE</span></div></div>
            <div className="pdp-feature">{I.chat}<div><b>Soporte 24/7</b><span>Chat y WhatsApp</span></div></div>
          </div>
        </div>
      </div>

      <section style={{marginTop: 64}}>
        <div className="sect-head">
          <h2 className="sect-title">Productos similares</h2>
        </div>
        <div className="grid-products">
          {related.map(p => (
            <ProductCard key={p.id} product={p} variant={cardVariant}
              onAdd={addToCart} onWish={toggleWish}
              wished={wishlist.has(p.id)} onOpen={() => go({name:'pdp', id: p.id})}/>
          ))}
        </div>
      </section>
    </main>
  );
}

// ─── CART ─────────────────────────────────────────────────────────
function CartScreen({ cart, setCart, go }) {
  const [coupon, setCoupon] = useState('');
  const [couponMsg, setCouponMsg] = useState('');
  const subtotal = cart.reduce((s, l) => s + l.price * l.qty, 0);
  const discount = coupon.toUpperCase() === 'GABDE10' ? Math.round(subtotal * 0.1) : 0;
  const shipping = subtotal > 999 ? 0 : 99;
  const total = subtotal - discount + shipping;

  const setQty = (id, q) => setCart(c => c.map(l => l.id === id ? { ...l, qty: Math.max(1, q) } : l));
  const remove = (id) => setCart(c => c.filter(l => l.id !== id));

  return (
    <main id="content" className="page page-pad fade-in">
      <nav className="breadcrumbs"><a onClick={() => go({name:'home'})} style={{cursor:'pointer'}}>Inicio</a><span>/</span><span style={{color:'var(--text-primary)'}}>Carrito</span></nav>
      <h1 className="h1" style={{marginTop:8}}>Tu carrito</h1>

      {cart.length === 0 ? (
        <div style={{textAlign:'center', padding:'80px 16px', marginTop:32, background:'var(--bg-surface)', borderRadius:'var(--radius-lg)', border:'1px solid var(--border)'}}>
          <div style={{fontSize:48, marginBottom:12}}>🛒</div>
          <h3 className="h3">Tu carrito está vacío</h3>
          <p style={{color:'var(--text-secondary)', marginTop:6}}>Explora nuestras categorías y descubre algo nuevo.</p>
          <button className="btn btn-primary" style={{marginTop:16}} onClick={() => go({name:'home'})}>Volver al inicio</button>
        </div>
      ) : (
        <div className="cart-page" style={{marginTop:24}}>
          <div className="cart-lines">
            {cart.map(l => (
              <div key={l.id} className="cart-line">
                <div className="cart-line-thumb">
                  <Placeholder palette={window.GABDE_DATA.palette[l.cat]} label={l.name} accent={l.color}/>
                </div>
                <div>
                  <div className="cart-line-brand">{l.brand} · {l.sku}</div>
                  <div className="cart-line-name">{l.name}</div>
                  <div className="cart-line-vars">
                    Color: <span style={{display:'inline-block', width:10, height:10, borderRadius:'50%', background:l.color, verticalAlign:'middle', marginLeft:4, outline:'1px solid var(--border-strong)'}}/>
                  </div>
                  <div className="cart-line-actions">
                    <div className="qty-stepper" style={{height:36}}>
                      <button onClick={() => setQty(l.id, l.qty - 1)} style={{height:36, width:32}}>−</button>
                      <input value={l.qty} onChange={e => setQty(l.id, +e.target.value || 1)} style={{height:36, width:36}}/>
                      <button onClick={() => setQty(l.id, l.qty + 1)} style={{height:36, width:32}}>+</button>
                    </div>
                    <span className="link" onClick={() => remove(l.id)}>Eliminar</span>
                    <span className="link">Guardar para después</span>
                  </div>
                </div>
                <div className="cart-line-price">{fmtPrice(l.price * l.qty)}</div>
              </div>
            ))}
          </div>

          <aside className="summary">
            <h4>Resumen</h4>
            <div className="summary-row"><span>Subtotal</span><span className="mono">{fmtPrice(subtotal)}</span></div>
            {discount > 0 && <div className="summary-row" style={{color:'var(--success)'}}><span>Descuento (GABDE10)</span><span className="mono">−{fmtPrice(discount)}</span></div>}
            <div className="summary-row"><span>Envío</span><span className="mono">{shipping === 0 ? 'Gratis' : fmtPrice(shipping)}</span></div>
            <div className="summary-row total"><span>Total</span><span className="mono">{fmtPrice(total)}</span></div>
            <div className="coupon-row">
              <input placeholder="Código de descuento" value={coupon} onChange={e => setCoupon(e.target.value)}/>
              <button className="btn btn-outline btn-sm" onClick={() => setCouponMsg(coupon.toUpperCase() === 'GABDE10' ? '✓ Aplicado' : 'Cupón no válido')}>Aplicar</button>
            </div>
            {couponMsg && <p style={{fontSize:12, color: couponMsg.startsWith('✓') ? 'var(--success)':'var(--danger)', marginTop:-8, marginBottom:8}}>{couponMsg}</p>}
            <button className="btn btn-primary btn-block btn-lg" onClick={() => go({name:'checkout'})}>Continuar al pago →</button>
            <p style={{fontSize:11.5, color:'var(--text-muted)', marginTop:10, textAlign:'center'}}>Pago 100% seguro · Envío rastreable</p>
          </aside>
        </div>
      )}
    </main>
  );
}

// ─── CHECKOUT ─────────────────────────────────────────────────────
function CheckoutScreen({ cart, go, onPlaceOrder }) {
  const [step, setStep] = useState(0);
  const [pay, setPay] = useState('card');
  const subtotal = cart.reduce((s, l) => s + l.price * l.qty, 0);
  const shipping = subtotal > 999 ? 0 : 99;
  const total = subtotal + shipping;

  const steps = ['Envío', 'Pago', 'Confirmación'];

  return (
    <main id="content" className="page page-pad fade-in">
      <nav className="breadcrumbs"><a onClick={() => go({name:'cart'})} style={{cursor:'pointer'}}>Carrito</a><span>/</span><span style={{color:'var(--text-primary)'}}>Pago</span></nav>
      <h1 className="h1" style={{marginTop: 8}}>Finalizar compra</h1>

      <div className="co-stepper" style={{marginTop: 24}}>
        {steps.map((s, i) => (
          <div key={s} className="co-step" aria-current={i === step ? 'step' : undefined} data-done={i < step}>
            <span className="num">{i < step ? '✓' : i + 1}</span>
            <span>{s}</span>
          </div>
        ))}
      </div>

      <div className="checkout">
        <div>
          {step === 0 && (
            <div className="co-section">
              <h3>Datos de envío</h3>
              <div className="field-row">
                <div className="field"><label>Nombre</label><input defaultValue="Andrea"/></div>
                <div className="field"><label>Apellido</label><input defaultValue="López"/></div>
              </div>
              <div className="field"><label>Email</label><input defaultValue="andrea@example.com" type="email"/></div>
              <div className="field"><label>Teléfono</label><input defaultValue="+52 55 1234 5678"/></div>
              <div className="field"><label>Dirección</label><input defaultValue="Av. Reforma 222, piso 4"/></div>
              <div className="field-row">
                <div className="field"><label>Colonia</label><input defaultValue="Juárez"/></div>
                <div className="field"><label>CP</label><input defaultValue="06600"/></div>
              </div>
              <div className="field-row">
                <div className="field"><label>Ciudad</label><input defaultValue="Ciudad de México"/></div>
                <div className="field"><label>Estado</label><input defaultValue="CDMX"/></div>
              </div>
              <h3 style={{marginTop: 24}}>Método de envío</h3>
              <label className="pay-method" aria-pressed="true">
                <input type="radio" name="ship" defaultChecked style={{accentColor:'var(--primary)'}}/>
                <div style={{flex:1}}>
                  <b>Estándar — 2 a 4 días hábiles</b>
                  <div style={{fontSize:12, color:'var(--text-muted)'}}>{shipping === 0 ? 'Gratis' : fmtPrice(shipping)}</div>
                </div>
                {I.truck}
              </label>
              <label className="pay-method">
                <input type="radio" name="ship" style={{accentColor:'var(--primary)'}}/>
                <div style={{flex:1}}>
                  <b>Express — Mañana antes de las 12pm</b>
                  <div style={{fontSize:12, color:'var(--text-muted)'}}>{fmtPrice(199)}</div>
                </div>
                <span className="pay-logo">DHL</span>
              </label>
              <label className="pay-method">
                <input type="radio" name="ship" style={{accentColor:'var(--primary)'}}/>
                <div style={{flex:1}}>
                  <b>Recoger en tienda — Polanco</b>
                  <div style={{fontSize:12, color:'var(--text-muted)'}}>Listo en 2 horas</div>
                </div>
                <span className="pay-logo">📍</span>
              </label>
            </div>
          )}
          {step === 1 && (
            <div className="co-section">
              <h3>Forma de pago</h3>
              <label className="pay-method" aria-pressed={pay === 'card'} onClick={() => setPay('card')}>
                <input type="radio" name="pay" checked={pay === 'card'} onChange={() => setPay('card')} style={{accentColor:'var(--primary)'}}/>
                <div style={{flex:1}}><b>Tarjeta de crédito o débito</b><div style={{fontSize:12, color:'var(--text-muted)'}}>Hasta 12 MSI</div></div>
                <span className="pay-logo">VISA</span><span className="pay-logo">MC</span>
              </label>
              {pay === 'card' && (
                <div style={{padding:'4px 0 12px', display:'grid', gap: 12, marginLeft:34}}>
                  <div className="field"><label>Número de tarjeta</label><input placeholder="•••• •••• •••• ••••"/></div>
                  <div className="field-row">
                    <div className="field"><label>Vencimiento</label><input placeholder="MM / AA"/></div>
                    <div className="field"><label>CVV</label><input placeholder="•••"/></div>
                  </div>
                  <div className="field"><label>Nombre en la tarjeta</label><input defaultValue="Andrea López"/></div>
                </div>
              )}
              <label className="pay-method" aria-pressed={pay === 'oxxo'} onClick={() => setPay('oxxo')}>
                <input type="radio" name="pay" checked={pay === 'oxxo'} onChange={() => setPay('oxxo')} style={{accentColor:'var(--primary)'}}/>
                <div style={{flex:1}}><b>OXXO Pay</b><div style={{fontSize:12, color:'var(--text-muted)'}}>Te enviamos el cupón por email</div></div>
                <span className="pay-logo">OXXO</span>
              </label>
              <label className="pay-method" aria-pressed={pay === 'spei'} onClick={() => setPay('spei')}>
                <input type="radio" name="pay" checked={pay === 'spei'} onChange={() => setPay('spei')} style={{accentColor:'var(--primary)'}}/>
                <div style={{flex:1}}><b>Transferencia SPEI</b><div style={{fontSize:12, color:'var(--text-muted)'}}>1 día hábil</div></div>
                <span className="pay-logo">SPEI</span>
              </label>
              <label className="pay-method" aria-pressed={pay === 'paypal'} onClick={() => setPay('paypal')}>
                <input type="radio" name="pay" checked={pay === 'paypal'} onChange={() => setPay('paypal')} style={{accentColor:'var(--primary)'}}/>
                <div style={{flex:1}}><b>PayPal</b><div style={{fontSize:12, color:'var(--text-muted)'}}>Te redirigimos a tu cuenta</div></div>
                <span className="pay-logo">PAYPAL</span>
              </label>
            </div>
          )}
          {step === 2 && (
            <div className="co-section" style={{textAlign:'center', padding:'48px 24px'}}>
              <div style={{width:64, height:64, borderRadius:'50%', background:'var(--success)', display:'inline-grid', placeItems:'center', color:'white', fontSize:32, marginBottom:16}}>✓</div>
              <h2 className="h2" style={{marginBottom: 8}}>¡Pedido confirmado!</h2>
              <p style={{color:'var(--text-secondary)', maxWidth: '42ch', margin: '0 auto'}}>Te enviamos el detalle a tu correo. Puedes seguir el estado desde "Mis pedidos".</p>
              <p className="mono" style={{marginTop: 16, fontSize: 13, color:'var(--text-muted)'}}>N° de pedido <strong style={{color:'var(--text-primary)'}}>GB-26-0917</strong></p>
              <div style={{display:'flex', gap:8, justifyContent:'center', marginTop: 24}}>
                <button className="btn btn-outline" onClick={() => go({name:'account', tab: 'orders'})}>Ver mis pedidos</button>
                <button className="btn btn-primary" onClick={() => go({name:'home'})}>Seguir comprando</button>
              </div>
            </div>
          )}

          {step < 2 && (
            <div style={{display:'flex', gap:8, marginTop: 16, justifyContent:'space-between'}}>
              <button className="btn btn-ghost" onClick={() => step === 0 ? go({name:'cart'}) : setStep(s => s - 1)}>
                ← {step === 0 ? 'Volver al carrito' : 'Atrás'}
              </button>
              <button className="btn btn-primary btn-lg" onClick={() => {
                if (step === 1) onPlaceOrder();
                setStep(s => s + 1);
              }}>
                {step === 0 ? 'Continuar al pago' : 'Confirmar pedido'} →
              </button>
            </div>
          )}
        </div>

        <aside className="summary">
          <h4>Tu pedido ({cart.length})</h4>
          {cart.slice(0, 4).map(l => (
            <div key={l.id} className="cp-row" style={{padding:'8px 0'}}>
              <div className="cp-thumb"><Placeholder palette={window.GABDE_DATA.palette[l.cat]} label={l.name} accent={l.color}/></div>
              <div style={{minWidth:0}}>
                <div className="cp-name" style={{whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis'}}>{l.name}</div>
                <div className="cp-qty">Cant. {l.qty}</div>
              </div>
              <span className="cp-price">{fmtPrice(l.price * l.qty)}</span>
            </div>
          ))}
          <div style={{paddingTop:12, marginTop:8, borderTop:'1px solid var(--border)'}}>
            <div className="summary-row"><span>Subtotal</span><span className="mono">{fmtPrice(subtotal)}</span></div>
            <div className="summary-row"><span>Envío</span><span className="mono">{shipping === 0 ? 'Gratis' : fmtPrice(shipping)}</span></div>
            <div className="summary-row total"><span>Total</span><span className="mono">{fmtPrice(total)}</span></div>
          </div>
        </aside>
      </div>
    </main>
  );
}

// ─── ACCOUNT ──────────────────────────────────────────────────────
function AccountScreen({ route, go, cart, wishlist, toggleWish, cardVariant, addToCart }) {
  const [tab, setTab] = useState(route.tab || 'overview');
  useEffect(() => { if (route.tab) setTab(route.tab); }, [route.tab]);

  const ORDERS = [
    { id: 'GB-26-0917', date: '12 May 2026', total: 6299, items: 2, status: 'transit', track: 'En camino · llega mañana' },
    { id: 'GB-26-0814', date: '02 May 2026', total: 1499, items: 1, status: 'delivered', track: 'Entregado el 5 May' },
    { id: 'GB-26-0701', date: '18 Abr 2026', total: 22999, items: 1, status: 'delivered', track: 'Entregado el 22 Abr' },
    { id: 'GB-26-0633', date: '03 Abr 2026', total: 749, items: 1, status: 'delivered', track: 'Entregado el 06 Abr' },
  ];
  const ADDR = [
    { name: 'Casa', addr: 'Av. Reforma 222, piso 4 · Juárez · CDMX 06600', tel: '+52 55 1234 5678', def: true },
    { name: 'Oficina', addr: 'Av. Patriotismo 871 · Mixcoac · CDMX 03910', tel: '+52 55 1234 5678', def: false },
  ];

  const wishProducts = window.GABDE_DATA.products.filter(p => wishlist.has(p.id));

  return (
    <main id="content" className="page page-pad fade-in">
      <nav className="breadcrumbs"><a onClick={() => go({name:'home'})} style={{cursor:'pointer'}}>Inicio</a><span>/</span><span style={{color:'var(--text-primary)'}}>Mi cuenta</span></nav>
      <h1 className="h1" style={{marginTop: 8}}>Hola, Andrea</h1>
      <p style={{color:'var(--text-secondary)', marginTop: 4}}>Miembro desde 2022 · 14 pedidos</p>

      <div className="account" style={{marginTop: 24}}>
        <aside className="account-nav" aria-label="Cuenta">
          {[
            ['overview', 'Resumen', '🏠'],
            ['orders', 'Mis pedidos', '📦'],
            ['wishlist', 'Lista de deseos', '♥'],
            ['addresses', 'Direcciones', '📍'],
            ['payments', 'Métodos de pago', '💳'],
            ['settings', 'Configuración', '⚙️'],
            ['logout', 'Cerrar sesión', '↩'],
          ].map(([id, label, em]) => (
            <button key={id} aria-current={tab === id ? 'page' : undefined} onClick={() => setTab(id)}>
              <span style={{fontSize:14}}>{em}</span>{label}
            </button>
          ))}
        </aside>
        <div>
          {tab === 'overview' && (
            <>
              <div style={{display:'grid', gridTemplateColumns:'repeat(3, 1fr)', gap:12}}>
                <div className="account-card"><div className="eyebrow">Pedidos activos</div><div style={{fontFamily:'var(--font-display)', fontSize:36, fontWeight:700, marginTop:8}}>2</div></div>
                <div className="account-card"><div className="eyebrow">Puntos GABDE</div><div style={{fontFamily:'var(--font-display)', fontSize:36, fontWeight:700, marginTop:8}}>1,240</div><div style={{fontSize:12, color:'var(--text-muted)'}}>= $124 MXN</div></div>
                <div className="account-card"><div className="eyebrow">En tu wishlist</div><div style={{fontFamily:'var(--font-display)', fontSize:36, fontWeight:700, marginTop:8}}>{wishlist.size}</div></div>
              </div>
              <div className="account-card" style={{marginTop: 16}}>
                <div style={{display:'flex', justifyContent:'space-between', alignItems:'baseline', marginBottom:16}}>
                  <h3 className="h3">Últimos pedidos</h3>
                  <a onClick={() => setTab('orders')} style={{cursor:'pointer', fontSize:13, fontWeight:600}}>Ver todos →</a>
                </div>
                {ORDERS.slice(0, 3).map(o => <OrderRow key={o.id} o={o}/>)}
              </div>
            </>
          )}

          {tab === 'orders' && (
            <div className="account-card">
              <h3 className="h3" style={{marginBottom: 8}}>Mis pedidos</h3>
              {ORDERS.map(o => <OrderRow key={o.id} o={o}/>)}
            </div>
          )}

          {tab === 'wishlist' && (
            <div>
              <div className="account-card" style={{marginBottom:16}}>
                <h3 className="h3">Lista de deseos ({wishProducts.length})</h3>
                <p style={{color:'var(--text-secondary)', fontSize:13, marginTop:4}}>Guarda lo que te gusta para después. Te avisamos cuando bajen de precio.</p>
              </div>
              {wishProducts.length === 0 ? (
                <div className="account-card" style={{textAlign:'center', padding:'48px 24px'}}>
                  <div style={{fontSize:40, marginBottom:8}}>♥</div>
                  <p style={{color:'var(--text-secondary)'}}>Tu wishlist está vacía. Toca el corazón en cualquier producto para guardarlo.</p>
                </div>
              ) : (
                <div className="grid-products">
                  {wishProducts.map(p => (
                    <ProductCard key={p.id} product={p} variant={cardVariant}
                      onAdd={addToCart} onWish={toggleWish}
                      wished={true} onOpen={() => go({name:'pdp', id: p.id})}/>
                  ))}
                </div>
              )}
            </div>
          )}

          {tab === 'addresses' && (
            <div className="account-card">
              <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:16}}>
                <h3 className="h3">Direcciones</h3>
                <button className="btn btn-outline btn-sm">+ Agregar</button>
              </div>
              <div style={{display:'grid', gap:12}}>
                {ADDR.map(a => (
                  <div key={a.name} style={{padding:16, border:'1px solid var(--border)', borderRadius:'var(--radius-md)', display:'flex', justifyContent:'space-between', gap:12}}>
                    <div>
                      <div style={{display:'flex', gap:8, alignItems:'center'}}>
                        <strong>{a.name}</strong>
                        {a.def && <span className="badge badge-soft">Predeterminada</span>}
                      </div>
                      <div style={{fontSize:13.5, color:'var(--text-secondary)', marginTop:4}}>{a.addr}</div>
                      <div className="mono" style={{fontSize:12, color:'var(--text-muted)', marginTop:2}}>{a.tel}</div>
                    </div>
                    <div style={{display:'flex', gap:6}}>
                      <button className="btn btn-ghost btn-sm">Editar</button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {tab === 'payments' && (
            <div className="account-card">
              <h3 className="h3" style={{marginBottom: 16}}>Métodos de pago</h3>
              {[{n:'Visa terminación 4231', def:true, exp:'05/28'}, {n:'Mastercard terminación 8810', def:false, exp:'11/26'}].map(c => (
                <div key={c.n} style={{padding:16, border:'1px solid var(--border)', borderRadius:'var(--radius-md)', marginBottom:8, display:'flex', justifyContent:'space-between', alignItems:'center'}}>
                  <div>
                    <div style={{display:'flex', gap:8, alignItems:'center'}}>
                      <span className="pay-logo">VISA</span><strong>{c.n}</strong>
                      {c.def && <span className="badge badge-soft">Predeterminada</span>}
                    </div>
                    <div className="mono" style={{fontSize:12, color:'var(--text-muted)', marginTop:4}}>Vence {c.exp}</div>
                  </div>
                  <button className="btn btn-ghost btn-sm">Eliminar</button>
                </div>
              ))}
              <button className="btn btn-outline" style={{marginTop:8}}>+ Agregar tarjeta</button>
            </div>
          )}

          {tab === 'settings' && (
            <div className="account-card">
              <h3 className="h3" style={{marginBottom: 16}}>Configuración</h3>
              <div className="field-row">
                <div className="field"><label>Nombre</label><input defaultValue="Andrea"/></div>
                <div className="field"><label>Apellido</label><input defaultValue="López"/></div>
              </div>
              <div className="field"><label>Email</label><input defaultValue="andrea@example.com"/></div>
              <div className="field"><label>Idioma</label><select><option>Español (MX)</option><option>English</option></select></div>
              <h4 style={{fontFamily:'var(--font-display)', fontSize:14, fontWeight:600, margin:'16px 0 12px'}}>Preferencias</h4>
              {['Recibir promociones por email', 'Notificaciones push de pedidos', 'Programa de afiliados'].map(p => (
                <label key={p} style={{display:'flex', gap:10, padding:'8px 0', fontSize:14}}>
                  <input type="checkbox" defaultChecked={Math.random() > .3} style={{accentColor:'var(--primary)'}}/>
                  {p}
                </label>
              ))}
              <button className="btn btn-primary" style={{marginTop:16}}>Guardar cambios</button>
            </div>
          )}
        </div>
      </div>
    </main>
  );
}

function OrderRow({ o }) {
  return (
    <div className="order-row">
      <div style={{width:48, height:48, borderRadius:8, background:'var(--bg-inset)', display:'grid', placeItems:'center', fontSize:18}}>📦</div>
      <div>
        <div style={{display:'flex', gap:8, alignItems:'center'}}>
          <strong className="mono" style={{fontSize:13}}>{o.id}</strong>
          <span className={`status ${o.status}`}>{o.status === 'delivered' ? 'Entregado' : o.status === 'transit' ? 'En camino' : 'En proceso'}</span>
        </div>
        <div style={{fontSize:12.5, color:'var(--text-secondary)', marginTop:2}}>{o.date} · {o.items} producto{o.items > 1 ? 's' : ''} · {o.track}</div>
      </div>
      <div style={{textAlign:'right'}}>
        <div className="mono" style={{fontWeight:600}}>{fmtPrice(o.total)}</div>
        <a style={{fontSize:12, color:'var(--text-secondary)', cursor:'pointer'}}>Detalle →</a>
      </div>
    </div>
  );
}

Object.assign(window, { PDPScreen, CartScreen, CheckoutScreen, AccountScreen });
