/* GABDE — shared atoms: icons, placeholders, helpers
   Exposes: Icon, Stars, Placeholder, fmtPrice, palette helpers
   ───────────────────────────────────────────────────────────── */

const { useState, useEffect, useRef, useMemo, useCallback, useLayoutEffect } = React;

// ─── Currency ─────────────────────────────────────────────────────
const fmtPrice = (n, currency = 'MXN') => {
  try {
    return new Intl.NumberFormat('es-MX', {
      style: 'currency', currency, maximumFractionDigits: 0,
    }).format(n);
  } catch { return `$${n.toLocaleString()}`; }
};

// ─── Stars rating ──────────────────────────────────────────────────
function Stars({ value = 0, size = 12 }) {
  const full = Math.floor(value);
  const half = value - full >= 0.5;
  const out = [];
  for (let i = 0; i < 5; i++) {
    let ch = '☆';
    if (i < full) ch = '★';
    else if (i === full && half) ch = '★';
    out.push(<span key={i}>{ch}</span>);
  }
  return <span className="stars" style={{ fontSize: size, letterSpacing: 1 }}>{out}</span>;
}

// ─── Icons ─────────────────────────────────────────────────────────
const I = {
  search:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>,
  user:    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>,
  heart:   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round"><path d="M12 20s-7-4.6-7-10a4.4 4.4 0 0 1 8-2.6A4.4 4.4 0 0 1 21 10c0 5.4-7 10-7 10z"/></svg>,
  heartF:  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-8-5-8-11a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 6-8 11-8 11z"/></svg>,
  cart:    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 5h2l2.5 12.5a2 2 0 0 0 2 1.5h8a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="21" r="1.4"/><circle cx="17" cy="21" r="1.4"/></svg>,
  menu:    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>,
  close:   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>,
  arrowL:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><path d="m15 6-6 6 6 6"/></svg>,
  arrowR:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><path d="m9 6 6 6-6 6"/></svg>,
  filter:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>,
  truck:   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>,
  shield:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round"><path d="M12 3 4 6v6c0 5 4 8 8 9 4-1 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/></svg>,
  chat:    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round"><path d="M21 12a8 8 0 1 1-3.5-6.6L21 4l-1.4 3.4A8 8 0 0 1 21 12z"/></svg>,
  refresh: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>,
  arrowTop:<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>,
  grid2:   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="8" height="8"/><rect x="13" y="3" width="8" height="8"/><rect x="3" y="13" width="8" height="8"/><rect x="13" y="13" width="8" height="8"/></svg>,
  grid3:   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="5" height="5"/><rect x="10" y="3" width="5" height="5"/><rect x="17" y="3" width="4" height="5"/><rect x="3" y="10" width="5" height="5"/><rect x="10" y="10" width="5" height="5"/><rect x="17" y="10" width="4" height="5"/><rect x="3" y="17" width="5" height="4"/><rect x="10" y="17" width="5" height="4"/><rect x="17" y="17" width="4" height="4"/></svg>,
  desktop: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M9 20h6M12 16v4"/></svg>,
  tablet:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><rect x="5" y="3" width="14" height="18" rx="2"/><circle cx="12" cy="18" r=".7" fill="currentColor"/></svg>,
  mobile:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><rect x="7" y="2.5" width="10" height="19" rx="2"/><circle cx="12" cy="18.5" r=".7" fill="currentColor"/></svg>,
  sun:     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/></svg>,
  moon:    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round"><path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10z"/></svg>,
  // category icons
  cAudio:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/></svg>,
  cLaptop: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><rect x="4" y="5" width="16" height="11" rx="1.5"/><path d="M2 19h20"/></svg>,
  cPhone:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 18h4"/></svg>,
  cWatch:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><rect x="6" y="6" width="12" height="12" rx="3"/><path d="M9 6V3h6v3M9 18v3h6v-3"/></svg>,
  cGame:   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><path d="M6 9h12a3 3 0 0 1 3 3v3a3 3 0 0 1-5.4 1.8L14 14H10l-1.6 2.8A3 3 0 0 1 3 15v-3a3 3 0 0 1 3-3z"/><path d="M8 12v2M7 13h2M15 13h.01M17 13h.01"/></svg>,
  cAccess: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><rect x="3" y="9" width="18" height="9" rx="1.5"/><path d="M7 9V6h10v3"/></svg>,
  cHome:   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"><path d="m3 11 9-7 9 7v9a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z"/></svg>,
};

const CAT_ICON = {
  'Audio':       I.cAudio,
  'Laptops':     I.cLaptop,
  'Móviles':     I.cPhone,
  'Wearables':   I.cWatch,
  'Gaming':      I.cGame,
  'Accesorios':  I.cAccess,
  'Smart home':  I.cHome,
};

// ─── Striped product placeholder (no real images) ──────────────────
function Placeholder({ palette = ['#1f2937', '#475569'], label, accent, layout = 'product' }) {
  const [a, b] = palette;
  // generate deterministic abstract shape based on label
  const seed = (label || '').split('').reduce((s, c) => s + c.charCodeAt(0), 0);
  const shapeKind = seed % 4;
  const accentColor = accent || palette[2] || '#f5f5f7';

  return (
    <div className="placeholder" style={{
      background: a,
      width: '100%', height: '100%',
      position: 'relative', overflow: 'hidden',
      display: 'grid', placeItems: 'center',
    }}>
      <div style={{
        position:'absolute', inset:0,
        background: `repeating-linear-gradient(135deg, ${b} 0 1px, transparent 1px 14px)`,
        opacity: .35,
      }}/>
      {layout === 'product' && (
        <>
          {shapeKind === 0 && (
            <div style={{
              width: '54%', height: '54%', borderRadius: '50%',
              background: `radial-gradient(circle at 32% 28%, ${accentColor} 0%, ${b} 70%)`,
              boxShadow: `inset 0 -16px 30px rgba(0,0,0,.35)`,
            }}/>
          )}
          {shapeKind === 1 && (
            <div style={{
              width: '46%', height: '62%', borderRadius: '14px',
              background: `linear-gradient(160deg, ${accentColor} 0%, ${b} 100%)`,
              boxShadow: `inset 0 -10px 30px rgba(0,0,0,.3)`,
            }}/>
          )}
          {shapeKind === 2 && (
            <div style={{
              width: '64%', height: '40%', borderRadius: '8px',
              background: `linear-gradient(180deg, ${b} 0%, ${accentColor} 100%)`,
              transform: 'rotate(-6deg)',
              boxShadow: `0 12px 30px rgba(0,0,0,.35)`,
            }}/>
          )}
          {shapeKind === 3 && (
            <div style={{
              width: '50%', height: '50%',
              background: `conic-gradient(from 180deg at 50% 50%, ${b}, ${accentColor}, ${b})`,
              borderRadius: '50%',
              boxShadow: `inset 0 0 24px rgba(0,0,0,.35)`,
            }}/>
          )}
        </>
      )}
      {layout === 'hero' && (
        <>
          <div style={{
            position:'absolute', right:'-8%', top:'-12%',
            width:'70%', height:'70%', borderRadius:'50%',
            background: `radial-gradient(circle at 30% 30%, ${accentColor}, transparent 70%)`,
            opacity: .55, filter: 'blur(4px)',
          }}/>
          <div style={{
            position:'absolute', left:'8%', bottom:'10%',
            width:'40%', height:'40%', borderRadius:'24px',
            background: `linear-gradient(180deg, ${b}, ${accentColor})`,
            transform: 'rotate(-8deg)',
            boxShadow:'0 20px 40px rgba(0,0,0,.35)',
          }}/>
        </>
      )}
      {label && layout !== 'product' && (
        <span style={{
          position:'absolute', bottom: 12, left: 16,
          fontFamily:'var(--font-mono)', fontSize: 10, letterSpacing: '.12em',
          textTransform:'uppercase', color: '#fff', opacity: .7, zIndex: 2,
        }}>{label}</span>
      )}
    </div>
  );
}

// ─── Logo SVG ──────────────────────────────────────────────────────
function Logo({ name = 'GABDE' }) {
  return (
    <div className="logo" role="img" aria-label={name}>
      <div className="logo-mark">G</div>
      <span style={{ fontFamily: 'var(--font-display)' }}>{name}</span>
    </div>
  );
}

// ─── Hooks ────────────────────────────────────────────────────────
function useLocalStorage(key, initial) {
  const [v, setV] = useState(() => {
    try { const s = localStorage.getItem(key); return s ? JSON.parse(s) : initial; }
    catch { return initial; }
  });
  useEffect(() => { try { localStorage.setItem(key, JSON.stringify(v)); } catch {} }, [key, v]);
  return [v, setV];
}

Object.assign(window, { fmtPrice, Stars, Placeholder, Logo, I, CAT_ICON, useLocalStorage });
