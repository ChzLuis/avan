/* GABDE — ProductCard + Home screen */

function ProductCard({ product, variant = 'regular', onAdd, onWish, wished, onOpen }) {
  const cardRef = useRef(null);
  const [added, setAdded] = useState(false);

  const handleAdd = (e) => {
    e.stopPropagation();
    // fly-to-cart animation
    const card = cardRef.current;
    const targetEl = document.getElementById('cart-icon-btn');
    if (card && targetEl) {
      const imgEl = card.querySelector('.card-media');
      const r = imgEl.getBoundingClientRect();
      const t = targetEl.getBoundingClientRect();
      const fly = document.createElement('div');
      fly.className = 'fly-cart';
      fly.style.left = r.left + r.width/2 - 28 + 'px';
      fly.style.top  = r.top  + r.height/2 - 28 + 'px';
      const inner = document.createElement('div');
      inner.style.cssText = `width:100%;height:100%;background:${product.colors[0] || '#1f2937'};`;
      fly.appendChild(inner);
      document.body.appendChild(fly);
      requestAnimationFrame(() => {
        fly.style.left = t.left + t.width/2 - 18 + 'px';
        fly.style.top  = t.top  + t.height/2 - 18 + 'px';
        fly.style.width = '20px';
        fly.style.height = '20px';
        fly.style.opacity = '0.2';
      });
      setTimeout(() => fly.remove(), 720);
    }
    setAdded(true);
    setTimeout(() => setAdded(false), 1800);
    onAdd(product);
  };

  const pct = product.compareAt ? Math.round((1 - product.price / product.compareAt) * 100) : 0;
  const out = product.stock === 0;

  return (
    <article
      ref={cardRef}
      className={`card card-v-${variant} fade-in`}
      onClick={() => onOpen(product)}
      style={{cursor:'pointer'}}
    >
      <div className="card-media">
        <Placeholder palette={window.GABDE_DATA.palette[product.cat]} label={product.name} accent={product.colors[0]} />
        <div className="card-badges">
          {product.badges.slice(0, 2).map(b => (
            <span key={b} className={
              b === 'Agotado'        ? 'badge badge-out' :
              b.includes('-')        ? 'badge badge-sale' :
              b === 'Nuevo'          ? 'badge badge-new' :
              b === 'Más vendido'    ? 'badge badge-hot' :
              'badge badge-soft'
            }>{b}</span>
          ))}
        </div>
        <button className="card-wishlist" aria-pressed={wished} aria-label="Lista de deseos"
          onClick={(e) => { e.stopPropagation(); onWish(product); }}>
          {wished ? I.heartF : I.heart}
        </button>
      </div>
      <div className="card-body">
        <div className="card-brand">{product.brand} · {product.sku}</div>
        <div className="card-name">{product.name}</div>
        <div className="card-rating">
          <Stars value={product.rating}/>
          <span>{product.rating} ({product.reviews})</span>
        </div>
        <div className="card-price-row">
          <span className="price-now mono">{fmtPrice(product.price)}</span>
          {product.compareAt && <span className="price-was mono">{fmtPrice(product.compareAt)}</span>}
          {pct > 0 && <span className="price-pct">-{pct}%</span>}
        </div>
        {product.stock > 0 && product.stock < 5 && (
          <span className="card-stock-low">¡Solo quedan {product.stock} unidades!</span>
        )}
        <div className="card-cta">
          {out ? (
            <button className="btn btn-outline btn-sm btn-block" disabled>Avísame cuando llegue</button>
          ) : (
            <>
              <button className="btn btn-primary btn-sm btn-block" onClick={handleAdd}>
                {added ? '✓ Agregado' : '+ Carrito'}
              </button>
            </>
          )}
        </div>
      </div>
    </article>
  );
}

function ProductCardSkeleton() {
  return (
    <div className="sk-card">
      <div className="sk-media skeleton"/>
      <div className="sk-body">
        <div className="sk-line short skeleton"/>
        <div className="sk-line med skeleton"/>
        <div className="sk-line short skeleton"/>
      </div>
    </div>
  );
}

// ─── Hero carousel ────────────────────────────────────────────────
function Hero({ go, slides }) {
  const [i, setI] = useState(0);
  const [paused, setPaused] = useState(false);
  useEffect(() => {
    if (paused) return;
    const t = setInterval(() => setI(p => (p + 1) % slides.length), 6000);
    return () => clearInterval(t);
  }, [paused, slides.length]);

  return (
    <section className="hero"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      aria-roledescription="carrusel">
      {slides.map((s, idx) => (
        <div key={idx} className="hero-slide" aria-current={i === idx ? 'true' : 'false'}>
          <div className="hero-text">
            <span className="eyebrow hero-eyebrow">{s.eyebrow}</span>
            <h1 className="h1 hero-title">{s.title}</h1>
            <p className="hero-sub">{s.sub}</p>
            <div className="hero-cta">
              <button className="btn btn-primary btn-lg" onClick={() => go({name:'plp', cat: s.tag})}>{s.cta}</button>
            </div>
          </div>
          <div className="hero-art" style={{background: s.accent}}>
            <span className="hero-art-label">{s.tag}</span>
            <Placeholder layout="hero" palette={[s.accent, '#0f172a']} accent="#fff" />
          </div>
        </div>
      ))}
      <div className="hero-dots">
        {slides.map((_, idx) => (
          <button key={idx} className="hero-dot" aria-current={i === idx ? 'true' : 'false'} aria-label={`Diapositiva ${idx + 1}`} onClick={() => setI(idx)}/>
        ))}
      </div>
      <div className="hero-arrows">
        <button className="hero-arrow" aria-label="Anterior" onClick={() => setI(p => (p - 1 + slides.length) % slides.length)}>{I.arrowL}</button>
        <button className="hero-arrow" aria-label="Siguiente" onClick={() => setI(p => (p + 1) % slides.length)}>{I.arrowR}</button>
      </div>
    </section>
  );
}

// ─── Category tiles ───────────────────────────────────────────────
function CategoryGrid({ go, cardVariant }) {
  const counts = useMemo(() => {
    const c = {};
    window.GABDE_DATA.products.forEach(p => { c[p.cat] = (c[p.cat] || 0) + 1; });
    return c;
  }, []);
  return (
    <section>
      <div className="sect-head">
        <div>
          <h2 className="sect-title">Explorar categorías</h2>
          <p className="sect-sub">Encuentra lo tuyo en un par de toques.</p>
        </div>
        <a className="more" onClick={() => go({name:'plp'})} style={{cursor:'pointer'}}>Ver todo</a>
      </div>
      <div className="cat-grid">
        {window.GABDE_DATA.categories.map(c => (
          <div key={c} className="cat-tile" onClick={() => go({name:'plp', cat: c})}>
            <div className="cat-tile-icon">{CAT_ICON[c]}</div>
            <div>
              <div className="cat-tile-name">{c}</div>
              <div className="cat-tile-count">{counts[c] || 0} productos</div>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

// ─── Horizontal product rail ──────────────────────────────────────
function ProductRail({ title, sub, products, more, cardVariant, onAdd, onWish, wishlist, onOpen, go }) {
  const railRef = useRef(null);
  const scroll = (dir) => railRef.current?.scrollBy({ left: dir * 320, behavior: 'smooth' });
  return (
    <section>
      <div className="sect-head">
        <div>
          <h2 className="sect-title">{title}</h2>
          {sub && <p className="sect-sub">{sub}</p>}
        </div>
        <div style={{display:'flex', gap:8, alignItems:'center'}}>
          <button className="icon-btn" onClick={() => scroll(-1)} aria-label="Anterior" style={{background:'var(--bg-inset)'}}>{I.arrowL}</button>
          <button className="icon-btn" onClick={() => scroll(1)} aria-label="Siguiente" style={{background:'var(--bg-inset)'}}>{I.arrowR}</button>
        </div>
      </div>
      <div ref={railRef} style={{display:'flex', gap: 'var(--grid-gap)', overflowX:'auto', scrollSnapType:'x mandatory', paddingBottom: 8, scrollbarWidth:'none'}}>
        {products.map(p => (
          <div key={p.id} style={{flex:'0 0 calc(100% / var(--cols) - var(--grid-gap) + var(--grid-gap) / var(--cols)); minWidth:240px; scrollSnapAlign:start'}}>
            <div style={{width: 240}}>
              <ProductCard
                product={p} variant={cardVariant}
                onAdd={onAdd} onWish={onWish}
                wished={wishlist.has(p.id)}
                onOpen={() => go({name:'pdp', id: p.id})}
              />
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

// ─── Flash sale block ─────────────────────────────────────────────
function FlashSale({ products, cardVariant, onAdd, onWish, wishlist, go }) {
  const [t, setT] = useState({ h: 5, m: 42, s: 18 });
  useEffect(() => {
    const tick = setInterval(() => {
      setT(prev => {
        let { h, m, s } = prev;
        s--;
        if (s < 0) { s = 59; m--; }
        if (m < 0) { m = 59; h--; }
        if (h < 0) { h = 0; m = 0; s = 0; }
        return { h, m, s };
      });
    }, 1000);
    return () => clearInterval(tick);
  }, []);
  const z = (n) => String(n).padStart(2, '0');

  return (
    <section style={{
      background: 'var(--text-primary)',
      color: 'var(--text-on-dark)',
      borderRadius: 'var(--radius-xl)',
      padding: '32px',
      marginTop: 48,
    }}>
      <div style={{display:'flex', alignItems:'center', justifyContent:'space-between', gap:16, flexWrap:'wrap', marginBottom: 24}}>
        <div>
          <div style={{display:'flex', alignItems:'center', gap:10, marginBottom:6}}>
            <span style={{background:'var(--accent)', color:'white', padding:'4px 8px', borderRadius:6, fontFamily:'var(--font-mono)', fontSize:11, fontWeight:700, letterSpacing:'.06em'}}>FLASH 🔥</span>
            <span className="eyebrow" style={{color:'rgba(245,245,247,.7)'}}>Ofertas relámpago</span>
          </div>
          <h2 className="h2" style={{color:'var(--text-on-dark)'}}>Termina en…</h2>
        </div>
        <div style={{display:'flex', gap:8, fontFamily:'var(--font-mono)', alignItems:'center'}}>
          {[['h', t.h, 'h'],['m', t.m, 'min'],['s', t.s, 's']].map(([k, v, l]) => (
            <div key={k} style={{background:'rgba(255,255,255,.08)', borderRadius:10, padding:'8px 14px', textAlign:'center', minWidth:64}}>
              <div style={{fontSize:22, fontWeight:700}}>{z(v)}</div>
              <div style={{fontSize:10, opacity:.65, letterSpacing:'.1em', textTransform:'uppercase'}}>{l}</div>
            </div>
          ))}
        </div>
      </div>
      <div className="grid-products" style={{'--cols': 4}}>
        {products.slice(0, 4).map(p => {
          const sold = 60 + (p.reviews % 30);
          const total = 100;
          return (
            <div key={p.id} style={{background:'var(--bg-surface)', borderRadius:'var(--radius-lg)', padding:0, color:'var(--text-primary)'}}>
              <ProductCard
                product={p} variant={cardVariant}
                onAdd={onAdd} onWish={onWish}
                wished={wishlist.has(p.id)}
                onOpen={() => go({name:'pdp', id: p.id})}
              />
              <div style={{padding:'0 14px 14px'}}>
                <div style={{height:6, background:'var(--bg-inset)', borderRadius:3, overflow:'hidden'}}>
                  <div style={{width: `${sold}%`, height:'100%', background: 'var(--accent)'}}/>
                </div>
                <div style={{fontSize:11.5, color:'var(--text-secondary)', marginTop:6, fontFamily:'var(--font-mono)'}}>
                  {sold} vendidos de {total}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
}

// ─── Blog teasers ─────────────────────────────────────────────────
function BlogStrip() {
  const posts = [
    { tag: 'Guía de compra', title: '¿Audífonos in-ear o over-ear? Lo que sí importa.', date: '12 May 2026', accent: '#3b82f6' },
    { tag: 'Tips de uso',    title: 'Cómo extender la vida útil de tu laptop por 3 años más.', date: '04 May 2026', accent: '#ef4444' },
    { tag: 'Tendencias',     title: 'Matter en 2026: la guerra del smart home (por fin) acabó.', date: '28 Abr 2026', accent: '#16a34a' },
  ];
  return (
    <section>
      <div className="sect-head">
        <div>
          <h2 className="sect-title">Lectura recomendada</h2>
          <p className="sect-sub">Guías curadas por nuestro equipo de producto.</p>
        </div>
        <a className="more" style={{cursor:'pointer'}}>Ver el blog</a>
      </div>
      <div style={{display:'grid', gridTemplateColumns:'repeat(3, 1fr)', gap: 24}}>
        {posts.map((p, i) => (
          <article key={i} style={{background:'var(--bg-surface)', border:'1px solid var(--border)', borderRadius:'var(--radius-lg)', overflow:'hidden', cursor:'pointer'}}>
            <div style={{aspectRatio:'16/10', position:'relative'}}>
              <Placeholder palette={[p.accent, '#0f172a']} accent="#fff" layout="hero"/>
            </div>
            <div style={{padding:'18px 20px 22px'}}>
              <div className="eyebrow" style={{marginBottom:8}}>{p.tag} · {p.date}</div>
              <div style={{fontFamily:'var(--font-display)', fontSize:18, fontWeight:600, lineHeight:1.25, letterSpacing:'-.01em'}}>{p.title}</div>
              <div style={{marginTop:12, fontSize:13, fontWeight:600, color:'var(--primary)'}}>Leer más →</div>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}

function HomeScreen({ go, addToCart, toggleWish, wishlist, cardVariant }) {
  const [loaded, setLoaded] = useState(false);
  useEffect(() => {
    const t = setTimeout(() => setLoaded(true), 350);
    return () => clearTimeout(t);
  }, []);
  const ps = window.GABDE_DATA.products;
  const bestsellers = ps.filter(p => p.badges.includes('Más vendido')).concat(ps.filter(p => !p.badges.includes('Más vendido'))).slice(0, 8);
  const newArrivals = ps.filter(p => p.badges.includes('Nuevo')).concat(ps).slice(0, 8);
  const flash = ps.filter(p => p.badges.some(b => b.includes('-'))).slice(0, 4);
  return (
    <main id="content" className="page page-pad fade-in">
      <Hero go={go} slides={window.GABDE_DATA.heroSlides}/>
      <CategoryGrid go={go} cardVariant={cardVariant}/>
      <ProductRail
        title="Más vendidos" sub="Lo que se está moviendo esta semana."
        products={bestsellers} cardVariant={cardVariant}
        onAdd={addToCart} onWish={toggleWish} wishlist={wishlist} go={go}
      />
      <FlashSale products={flash} cardVariant={cardVariant} onAdd={addToCart} onWish={toggleWish} wishlist={wishlist} go={go}/>
      <ProductRail
        title="Recomendados para ti" sub="Basado en tu navegación reciente."
        products={newArrivals} cardVariant={cardVariant}
        onAdd={addToCart} onWish={toggleWish} wishlist={wishlist} go={go}
      />
      <BlogStrip/>
    </main>
  );
}

Object.assign(window, { ProductCard, ProductCardSkeleton, Hero, CategoryGrid, ProductRail, FlashSale, BlogStrip, HomeScreen });
