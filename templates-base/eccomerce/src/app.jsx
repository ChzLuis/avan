/* GABDE — main App: routing, state, tweaks, viewport simulator */

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "dark": false,
  "primaryColor": "#3340ff",
  "accentColor": "#ff5b3d",
  "fontHeading": "Space Grotesk",
  "fontBody": "DM Sans",
  "radius": 12,
  "density": "regular",
  "cardVariant": "regular",
  "businessName": "GABDE",
  "vertical": "tech"
}/*EDITMODE-END*/;

// Vertical preset packs — let one click reskin to another business
const VERTICALS = {
  tech:     { name: 'GABDE',     primary: '#3340ff', accent: '#ff5b3d', fontH: 'Space Grotesk', fontB: 'DM Sans', radius: 12, label: 'Tecnología' },
  fashion:  { name: 'ATELIER',   primary: '#0e0e10', accent: '#c2410c', fontH: 'Fraunces',      fontB: 'DM Sans', radius: 6,  label: 'Moda' },
  liquor:   { name: 'CAVAS',     primary: '#7c2d12', accent: '#d4af37', fontH: 'Fraunces',      fontB: 'DM Sans', radius: 4,  label: 'Licorería' },
  beauty:   { name: 'BLOOM',     primary: '#be185d', accent: '#65a30d', fontH: 'Fraunces',      fontB: 'DM Sans', radius: 16, label: 'Cosmética' },
  home:     { name: 'CASA NORTE',primary: '#166534', accent: '#ca8a04', fontH: 'Space Grotesk', fontB: 'DM Sans', radius: 8,  label: 'Hogar' },
  sports:   { name: 'KINETIK',   primary: '#dc2626', accent: '#0e0e10', fontH: 'Space Grotesk', fontB: 'DM Sans', radius: 4,  label: 'Deportes' },
};

function App() {
  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);

  // Route state — { name: 'home' | 'plp' | 'pdp' | 'cart' | 'checkout' | 'account' | ... , params }
  const [route, setRoute] = useState({ name: 'home' });
  const [vp, setVp] = useState('desktop'); // desktop | tablet | mobile
  const [menuOpen, setMenuOpen] = useState(false);
  const scrollRef = useRef(null);

  // Cart & wishlist with persistence
  const [cart, setCart] = useLocalStorage('gabde:cart', []);
  const [wishlistArr, setWishlistArr] = useLocalStorage('gabde:wish', []);
  const wishlist = useMemo(() => new Set(wishlistArr), [wishlistArr]);

  const [toasts, setToasts] = useState([]);
  const undoBufRef = useRef({}); // toastId -> snapshot

  const go = useCallback((r) => {
    setRoute(r);
    setMenuOpen(false);
    setTimeout(() => scrollRef.current?.scrollTo({ top: 0, behavior: 'instant' }), 0);
  }, []);

  const addToCart = useCallback((product, qty = 1) => {
    setCart(prev => {
      undoBufRef.current[`add-${product.id}`] = prev;
      const ex = prev.find(l => l.id === product.id);
      if (ex) return prev.map(l => l.id === product.id ? { ...l, qty: l.qty + qty } : l);
      return [...prev, {
        id: product.id, name: product.name, brand: product.brand, sku: product.sku,
        cat: product.cat, price: product.price, color: product.color || product.colors[0],
        qty,
      }];
    });
    const tid = Math.random().toString(36).slice(2);
    setToasts(ts => [...ts, {
      id: tid, text: `Agregado · ${product.name}`, undo: true, thumb: true,
      palette: window.GABDE_DATA.palette[product.cat],
      label: product.name,
      undoKey: `add-${product.id}`,
    }]);
    setTimeout(() => setToasts(ts => ts.filter(t => t.id !== tid)), 4500);
  }, [setCart]);

  const onUndo = useCallback((tid) => {
    setToasts(ts => {
      const tt = ts.find(t => t.id === tid);
      if (tt && undoBufRef.current[tt.undoKey]) {
        setCart(undoBufRef.current[tt.undoKey]);
      }
      return ts.filter(t => t.id !== tid);
    });
  }, [setCart]);

  const toggleWish = useCallback((p) => {
    setWishlistArr(prev => prev.includes(p.id) ? prev.filter(id => id !== p.id) : [...prev, p.id]);
  }, [setWishlistArr]);

  const onPlaceOrder = useCallback(() => { setCart([]); }, [setCart]);

  // Apply tweaks → CSS vars
  useEffect(() => {
    const root = document.documentElement;
    root.setAttribute('data-theme', t.dark ? 'dark' : 'light');
    root.style.setProperty('--primary', t.primaryColor);
    root.style.setProperty('--accent', t.accentColor);
    root.style.setProperty('--font-display', `'${t.fontHeading}', ui-sans-serif, system-ui, sans-serif`);
    root.style.setProperty('--font-body', `'${t.fontBody}', ui-sans-serif, system-ui, sans-serif`);
    root.style.setProperty('--radius-md', `${t.radius}px`);
    root.style.setProperty('--radius-lg', `${Math.round(t.radius * 1.3)}px`);
    root.style.setProperty('--radius-xl', `${Math.round(t.radius * 2)}px`);
    // density => grid gap
    const gaps = { compact: 14, regular: 20, comfy: 28 };
    root.style.setProperty('--grid-gap', `${gaps[t.density] || 20}px`);
  }, [t]);

  // viewport sizing
  const vpSize = { desktop: 1440, tablet: 768, mobile: 390 }[vp];
  const vpPad  = { desktop: 32,   tablet: 24,  mobile: 16  }[vp];

  // Selected screen
  let screen;
  switch (route.name) {
    case 'plp':
    case 'offers':
    case 'new':
    case 'brands':
      screen = <PLPScreen route={route} go={go} addToCart={addToCart} toggleWish={toggleWish} wishlist={wishlist} cardVariant={t.cardVariant}/>;
      break;
    case 'pdp':
      screen = <PDPScreen route={route} go={go} addToCart={addToCart} toggleWish={toggleWish} wishlist={wishlist} cardVariant={t.cardVariant}/>;
      break;
    case 'cart':
      screen = <CartScreen cart={cart} setCart={setCart} go={go}/>;
      break;
    case 'checkout':
      screen = cart.length === 0 ? <CartScreen cart={cart} setCart={setCart} go={go}/> :
              <CheckoutScreen cart={cart} go={go} onPlaceOrder={onPlaceOrder}/>;
      break;
    case 'account':
      screen = <AccountScreen route={route} go={go} cart={cart} wishlist={wishlist} toggleWish={toggleWish} cardVariant={t.cardVariant} addToCart={addToCart}/>;
      break;
    case 'blog':
    case 'contact':
      screen = <main className="page page-pad fade-in"><h1 className="h1">{route.name === 'blog' ? 'Blog & Guías' : 'Contacto'}</h1><p style={{color:'var(--text-secondary)', marginTop: 12}}>Sección de demostración — completa con contenido para tu negocio.</p><BlogStrip/></main>;
      break;
    default:
      screen = <HomeScreen go={go} addToCart={addToCart} toggleWish={toggleWish} wishlist={wishlist} cardVariant={t.cardVariant}/>;
  }

  return (
    <div className="vp-stage" data-vp={vp} style={{minHeight:'100vh'}}>
      <VPTabs vp={vp} setVp={setVp}/>
      <div
        className="vp-shell"
        style={{
          maxWidth: vp === 'desktop' ? '100%' : vpSize,
          width: vp === 'desktop' ? '100%' : vpSize,
          '--page-max': vp === 'desktop' ? '1440px' : `${vpSize}px`,
          '--page-pad': `${vpPad}px`,
        }}
      >
        <a href="#content" className="skip">Saltar al contenido</a>
        <TopBar businessName={t.businessName}/>
        <Header
          businessName={t.businessName}
          route={route} go={go}
          cart={cart} wishCount={wishlist.size}
          onMenu={() => setMenuOpen(true)}
        />
        {vp !== 'mobile' && <Nav route={route} go={go}/>}
        <div ref={scrollRef} style={{
          maxHeight: vp === 'desktop' ? 'none' : `calc(100vh - 56px)`,
          overflowY: vp === 'desktop' ? 'visible' : 'auto',
        }}>
          {screen}
          <Footer businessName={t.businessName} year={2026}/>
        </div>
        <MobileMenu open={menuOpen} onClose={() => setMenuOpen(false)} businessName={t.businessName} go={go}/>
      </div>
      <ToastHost toasts={toasts} onUndo={onUndo}/>
      <BackToTop scrollRef={scrollRef}/>

      <TweaksPanel>
        <TweakSection label="Identidad"/>
        <TweakText label="Nombre del negocio" value={t.businessName} onChange={(v) => setTweak('businessName', v)}/>
        <TweakSelect label="Vertical preset" value={t.vertical}
          options={Object.entries(VERTICALS).map(([k, v]) => ({value: k, label: v.label}))}
          onChange={(v) => {
            const p = VERTICALS[v];
            setTweak({
              vertical: v, businessName: p.name, primaryColor: p.primary,
              accentColor: p.accent, fontHeading: p.fontH, fontBody: p.fontB, radius: p.radius,
            });
          }}/>

        <TweakSection label="Tema"/>
        <TweakToggle label="Modo oscuro" value={t.dark} onChange={(v) => setTweak('dark', v)}/>
        <TweakColor label="Color primario" value={t.primaryColor}
          options={['#3340ff','#0e0e10','#dc2626','#16a34a','#7c2d12','#be185d','#d4af37']}
          onChange={(v) => setTweak('primaryColor', v)}/>
        <TweakColor label="Color de acento" value={t.accentColor}
          options={['#ff5b3d','#d4af37','#65a30d','#0ea5e9','#ec4899','#7c3aed']}
          onChange={(v) => setTweak('accentColor', v)}/>

        <TweakSection label="Tipografía"/>
        <TweakSelect label="Headings" value={t.fontHeading}
          options={['Space Grotesk','Fraunces','DM Sans','Plus Jakarta Sans','Geist','Bricolage Grotesque','Instrument Serif']}
          onChange={(v) => setTweak('fontHeading', v)}/>
        <TweakSelect label="Body" value={t.fontBody}
          options={['DM Sans','Space Grotesk','Plus Jakarta Sans','Geist','Manrope']}
          onChange={(v) => setTweak('fontBody', v)}/>

        <TweakSection label="Layout"/>
        <TweakSlider label="Radio de bordes" value={t.radius} min={0} max={28} unit="px"
          onChange={(v) => setTweak('radius', v)}/>
        <TweakRadio label="Densidad" value={t.density} options={['compact','regular','comfy']}
          onChange={(v) => setTweak('density', v)}/>
        <TweakRadio label="Card layout" value={t.cardVariant} options={['compact','regular','editorial']}
          onChange={(v) => setTweak('cardVariant', v)}/>

        <TweakSection label="Navegación"/>
        <TweakButton label="Ir a Home" onClick={() => go({name:'home'})}/>
        <TweakButton label="Ir a Listado (PLP)" onClick={() => go({name:'plp'})}/>
        <TweakButton label="Ir a Detalle (PDP)" onClick={() => go({name:'pdp', id:'p01'})}/>
        <TweakButton label="Ir a Carrito" onClick={() => go({name:'cart'})}/>
        <TweakButton label="Ir a Checkout" onClick={() => go({name:'checkout'})}/>
        <TweakButton label="Ir a Mi cuenta" onClick={() => go({name:'account'})}/>
      </TweaksPanel>
    </div>
  );
}

function VPTabs({ vp, setVp }) {
  return (
    <div className="vp-tabs" role="tablist" aria-label="Viewport">
      {[
        ['desktop', I.desktop, '1440'],
        ['tablet', I.tablet, '768'],
        ['mobile', I.mobile, '390'],
      ].map(([id, ic, size]) => (
        <button key={id} className="vp-tab" aria-selected={vp === id} role="tab" onClick={() => setVp(id)}>
          {ic}
          <span style={{textTransform:'capitalize'}}>{id}</span>
          <span style={{opacity:.5, fontFamily:'var(--font-mono)'}}>{size}</span>
        </button>
      ))}
    </div>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App/>);
