/* GABDE — PLP (listing with filters + sorting), Search results */

const SORT_OPTS = [
  { id: 'relevance', label: 'Relevancia' },
  { id: 'price-asc', label: 'Precio: menor a mayor' },
  { id: 'price-desc', label: 'Precio: mayor a menor' },
  { id: 'best-selling', label: 'Más vendidos' },
  { id: 'rating', label: 'Mejor valorados' },
  { id: 'newest', label: 'Novedades' },
];

function RangeSlider({ min, max, value, onChange, step = 100 }) {
  const [lo, hi] = value;
  const pct = (v) => ((v - min) / (max - min)) * 100;
  return (
    <div>
      <div className="range-slider">
        <div className="range-track"/>
        <div className="range-fill" style={{left: pct(lo) + '%', right: (100 - pct(hi)) + '%'}}/>
        <input type="range" min={min} max={max} step={step} value={lo}
          onChange={e => onChange([Math.min(+e.target.value, hi - step), hi])} aria-label="Precio mínimo"/>
        <input type="range" min={min} max={max} step={step} value={hi}
          onChange={e => onChange([lo, Math.max(+e.target.value, lo + step)])} aria-label="Precio máximo"/>
      </div>
      <div className="range-labels">
        <span>{fmtPrice(lo)}</span><span>{fmtPrice(hi)}</span>
      </div>
    </div>
  );
}

function FilterBlock({ label, defaultOpen = true, children }) {
  return (
    <details className="filter-group" open={defaultOpen}>
      <summary>{label}</summary>
      <div className="filter-options">{children}</div>
    </details>
  );
}

function Filters({ filters, setFilters, products, allBrands, allCategories }) {
  const [brandQ, setBrandQ] = useState('');
  // counts
  const counts = useMemo(() => {
    const cat = {}, brand = {};
    products.forEach(p => {
      cat[p.cat] = (cat[p.cat] || 0) + 1;
      brand[p.brand] = (brand[p.brand] || 0) + 1;
    });
    return { cat, brand };
  }, [products]);

  const toggle = (key, value) => {
    setFilters(f => {
      const set = new Set(f[key]);
      set.has(value) ? set.delete(value) : set.add(value);
      return { ...f, [key]: Array.from(set) };
    });
  };

  return (
    <aside className="filters" aria-label="Filtros">
      <FilterBlock label="Precio">
        <RangeSlider min={0} max={40000} value={filters.price} onChange={(v) => setFilters(f => ({...f, price: v}))}/>
      </FilterBlock>

      <FilterBlock label="Categoría">
        {allCategories.map(c => (
          <label key={c} className="filter-check">
            <input type="checkbox" checked={filters.cat.includes(c)} onChange={() => toggle('cat', c)}/>
            <span>{c}</span>
            <span className="count">{counts.cat[c] || 0}</span>
          </label>
        ))}
      </FilterBlock>

      <FilterBlock label="Marca">
        <input className="brand-search" placeholder="Buscar marca…" value={brandQ} onChange={e => setBrandQ(e.target.value)}/>
        {allBrands.filter(b => b.toLowerCase().includes(brandQ.toLowerCase())).map(b => (
          <label key={b} className="filter-check">
            <input type="checkbox" checked={filters.brand.includes(b)} onChange={() => toggle('brand', b)}/>
            <span>{b}</span>
            <span className="count">{counts.brand[b] || 0}</span>
          </label>
        ))}
      </FilterBlock>

      <FilterBlock label="Color">
        <div className="filter-swatches">
          {[
            ['#0f172a', 'Negro'], ['#f5f5f7', 'Blanco'], ['#dc2626', 'Rojo'],
            ['#3340ff', 'Azul'], ['#16a34a', 'Verde'], ['#fbbf24', 'Amarillo'],
            ['#7c3aed', 'Morado'], ['#f97316', 'Naranja'],
          ].map(([c, name]) => (
            <button key={c} className="swatch" style={{background: c}}
              aria-label={name}
              aria-pressed={filters.color.includes(c)}
              onClick={() => toggle('color', c)} />
          ))}
        </div>
      </FilterBlock>

      <FilterBlock label="Valoración">
        {[5, 4, 3].map(n => (
          <label key={n} className="filter-check">
            <input type="checkbox" checked={filters.rating === n} onChange={() => setFilters(f => ({...f, rating: f.rating === n ? 0 : n}))}/>
            <span><Stars value={n}/> y más</span>
          </label>
        ))}
      </FilterBlock>

      <FilterBlock label="Disponibilidad" defaultOpen={false}>
        {[
          ['in_stock', 'En stock'],
          ['low', 'Stock bajo'],
          ['out', 'Próximamente / Agotado'],
        ].map(([k, l]) => (
          <label key={k} className="filter-check">
            <input type="checkbox" checked={filters.stock.includes(k)} onChange={() => toggle('stock', k)}/>
            <span>{l}</span>
          </label>
        ))}
      </FilterBlock>

      <FilterBlock label="Almacenamiento" defaultOpen={false}>
        {['64GB','128GB','256GB','512GB','1TB'].map(s => (
          <label key={s} className="filter-check">
            <input type="checkbox" checked={filters.attr.includes(s)} onChange={() => toggle('attr', s)}/>
            <span>{s}</span>
          </label>
        ))}
      </FilterBlock>

      <FilterBlock label="Características" defaultOpen={false}>
        {[
          ['anc','Cancelación de ruido'],
          ['envio-gratis','Envío gratis'],
          ['nuevo','Sólo novedades'],
          ['oferta','Sólo ofertas'],
        ].map(([k,l]) => (
          <label key={k} className="filter-check">
            <input type="checkbox" checked={filters.feature.includes(k)} onChange={() => toggle('feature', k)}/>
            <span>{l}</span>
          </label>
        ))}
      </FilterBlock>

      <div style={{display:'grid', gap:8, marginTop:16}}>
        <button className="btn btn-primary btn-block">Aplicar filtros</button>
        <button className="btn btn-ghost btn-block btn-sm" onClick={() => setFilters({
          price: [0, 40000], cat: [], brand: [], color: [], rating: 0,
          stock: [], attr: [], feature: [],
        })}>Limpiar todo</button>
      </div>
    </aside>
  );
}

function activeChips(filters, setFilters) {
  const out = [];
  if (filters.price[0] > 0 || filters.price[1] < 40000) {
    out.push({key:'price', label: `${fmtPrice(filters.price[0])} – ${fmtPrice(filters.price[1])}`, remove: () => setFilters(f => ({...f, price: [0, 40000]}))});
  }
  filters.cat.forEach(c => out.push({key: 'cat-'+c, label: c, remove: () => setFilters(f => ({...f, cat: f.cat.filter(x => x !== c)}))}));
  filters.brand.forEach(b => out.push({key: 'brand-'+b, label: b, remove: () => setFilters(f => ({...f, brand: f.brand.filter(x => x !== b)}))}));
  filters.color.forEach(c => out.push({key: 'col-'+c, label: 'Color', remove: () => setFilters(f => ({...f, color: f.color.filter(x => x !== c)})), swatch: c}));
  if (filters.rating) out.push({key:'rt', label: `${filters.rating}★+`, remove: () => setFilters(f => ({...f, rating: 0}))});
  filters.stock.forEach(s => out.push({key:'st-'+s, label: s, remove: () => setFilters(f => ({...f, stock: f.stock.filter(x => x !== s)}))}));
  filters.attr.forEach(a => out.push({key:'a-'+a, label: a, remove: () => setFilters(f => ({...f, attr: f.attr.filter(x => x !== a)}))}));
  filters.feature.forEach(a => out.push({key:'f-'+a, label: a, remove: () => setFilters(f => ({...f, feature: f.feature.filter(x => x !== a)}))}));
  return out;
}

function applyFilters(products, f, sort) {
  let out = products.filter(p => {
    if (p.price < f.price[0] || p.price > f.price[1]) return false;
    if (f.cat.length && !f.cat.includes(p.cat)) return false;
    if (f.brand.length && !f.brand.includes(p.brand)) return false;
    if (f.rating && p.rating < f.rating) return false;
    if (f.stock.length) {
      const st = p.stock === 0 ? 'out' : p.stock < 5 ? 'low' : 'in_stock';
      if (!f.stock.includes(st)) return false;
    }
    if (f.feature.includes('envio-gratis') && !p.badges.includes('Envío gratis')) return false;
    if (f.feature.includes('nuevo') && !p.badges.includes('Nuevo')) return false;
    if (f.feature.includes('oferta') && !p.badges.some(b => b.includes('-'))) return false;
    if (f.feature.includes('anc') && p.attrs.anc !== true) return false;
    return true;
  });
  switch (sort) {
    case 'price-asc':    out.sort((a, b) => a.price - b.price); break;
    case 'price-desc':   out.sort((a, b) => b.price - a.price); break;
    case 'best-selling': out.sort((a, b) => b.reviews - a.reviews); break;
    case 'rating':       out.sort((a, b) => b.rating - a.rating); break;
    case 'newest':       out.sort((a, b) => (b.badges.includes('Nuevo')?1:0) - (a.badges.includes('Nuevo')?1:0)); break;
  }
  return out;
}

function PLPScreen({ route, go, addToCart, toggleWish, wishlist, cardVariant }) {
  const ALL = window.GABDE_DATA;
  const [filters, setFilters] = useState({
    price: [0, 40000],
    cat: route.cat ? [route.cat] : [],
    brand: route.brand ? [route.brand] : [],
    color: [], rating: 0, stock: [], attr: [], feature: route.name === 'offers' ? ['oferta'] : route.name === 'new' ? ['nuevo'] : [],
  });
  const [sort, setSort] = useState('relevance');
  const [density, setDensity] = useState('regular');
  const [loaded, setLoaded] = useState(false);
  const [filtersOpen, setFiltersOpen] = useState(false);

  useEffect(() => { const t = setTimeout(() => setLoaded(true), 400); return () => clearTimeout(t); }, []);

  useEffect(() => {
    setFilters(f => ({
      ...f,
      cat: route.cat ? [route.cat] : f.cat,
      brand: route.brand ? [route.brand] : f.brand,
      feature: route.name === 'offers' ? ['oferta'] : route.name === 'new' ? ['nuevo'] : f.feature,
    }));
  }, [route.cat, route.brand, route.name]);

  const results = useMemo(() => applyFilters(ALL.products, filters, sort), [filters, sort]);
  const chips = activeChips(filters, setFilters);
  const title =
    route.brand ? route.brand :
    route.cat ? route.cat :
    route.name === 'offers' ? 'Ofertas' :
    route.name === 'new' ? 'Novedades' :
    'Todos los productos';

  return (
    <main id="content" className="page page-pad fade-in">
      <nav className="breadcrumbs" aria-label="Navegación">
        <a onClick={() => go({name:'home'})} style={{cursor:'pointer'}}>Inicio</a>
        <span>/</span>
        <span style={{color:'var(--text-primary)'}}>{title}</span>
      </nav>
      <h1 className="h1" style={{marginTop: 8}}>{title}</h1>

      <div className="plp-layout" style={{marginTop: 24}}>
        <Filters filters={filters} setFilters={setFilters} products={ALL.products} allBrands={ALL.brands} allCategories={ALL.categories}/>
        <div>
          <div className="plp-head">
            <div className="plp-count"><strong>{results.length}</strong> de {ALL.products.length} productos</div>
            <div className="plp-tools">
              <button className="btn btn-outline btn-sm fab-filters" onClick={() => setFiltersOpen(true)}>
                {I.filter} Filtros {chips.length > 0 && <span style={{marginLeft:4, padding:'1px 6px', background:'var(--text-primary)', color:'var(--bg-surface)', borderRadius:999, fontSize:11, fontFamily:'var(--font-mono)'}}>{chips.length}</span>}
              </button>
              <select className="sort-select" value={sort} onChange={e => setSort(e.target.value)} aria-label="Ordenar">
                {SORT_OPTS.map(o => <option key={o.id} value={o.id}>{o.label}</option>)}
              </select>
              <div className="density-toggle" role="group" aria-label="Densidad">
                <button aria-pressed={density === 'regular'} onClick={() => setDensity('regular')} aria-label="Cuadrícula 2">{I.grid2}</button>
                <button aria-pressed={density === 'dense'} onClick={() => setDensity('dense')} aria-label="Cuadrícula 3">{I.grid3}</button>
              </div>
            </div>
          </div>

          {chips.length > 0 && (
            <div className="active-filters">
              {chips.map(c => (
                <button key={c.key} className="chip" onClick={c.remove}>
                  {c.swatch && <span style={{width:10, height:10, borderRadius:'50%', background:c.swatch, display:'inline-block'}}/>}
                  {c.label}
                  <span className="chip-remove" aria-hidden>{I.close}</span>
                </button>
              ))}
              <button className="chip" style={{background:'transparent', border:'1px solid var(--border-strong)'}} onClick={() => setFilters({price:[0,40000],cat:[],brand:[],color:[],rating:0,stock:[],attr:[],feature:[]})}>Limpiar todo</button>
            </div>
          )}

          <div className="grid-products" style={density === 'dense' ? {'--cols': 'min(6, calc(var(--cols, 4) + 1))'} : {}}>
            {!loaded && Array.from({length: 8}).map((_, i) => <ProductCardSkeleton key={i}/>)}
            {loaded && results.length > 0 && results.map(p => (
              <ProductCard
                key={p.id} product={p} variant={cardVariant}
                onAdd={addToCart} onWish={toggleWish}
                wished={wishlist.has(p.id)} onOpen={() => go({name:'pdp', id: p.id})}
              />
            ))}
          </div>

          {loaded && results.length === 0 && (
            <div style={{textAlign:'center', padding:'64px 16px', background:'var(--bg-surface)', borderRadius:'var(--radius-lg)', border:'1px solid var(--border)'}}>
              <div style={{fontSize:48, marginBottom:12}}>🛒</div>
              <h3 className="h3" style={{marginBottom:6}}>No encontramos productos con esos filtros</h3>
              <p style={{color:'var(--text-secondary)', marginBottom:16}}>Intenta ampliar el rango de precio o quitar algunas marcas.</p>
              <button className="btn btn-primary" onClick={() => setFilters({price:[0,40000],cat:[],brand:[],color:[],rating:0,stock:[],attr:[],feature:[]})}>Limpiar filtros</button>
            </div>
          )}
        </div>
      </div>

      {/* mobile filters drawer */}
      <div className={`drawer-backdrop ${filtersOpen ? 'show' : ''}`} onClick={() => setFiltersOpen(false)}/>
      <aside className={`drawer drawer-bottom ${filtersOpen ? 'show' : ''}`} style={{padding:'8px 16px 24px'}}>
        <div className="drawer-handle"/>
        <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', padding:'8px 0 12px'}}>
          <h3 className="h3">Filtros</h3>
          <button className="icon-btn" onClick={() => setFiltersOpen(false)}>{I.close}</button>
        </div>
        <Filters filters={filters} setFilters={setFilters} products={ALL.products} allBrands={ALL.brands} allCategories={ALL.categories}/>
      </aside>
    </main>
  );
}

Object.assign(window, { PLPScreen });
