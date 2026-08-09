// GABDE — sample catalog (tech vertical demo)
// Universal schema: name, brand, sku, price, compareAt, rating, reviews, stock,
// badges, image (placeholder color), category, attributes (vertical-specific)

window.GABDE_DATA = (function () {
  const C = {
    audio: 'Audio',
    laptops: 'Laptops',
    phones: 'Móviles',
    wearables: 'Wearables',
    gaming: 'Gaming',
    accessories: 'Accesorios',
    home: 'Smart home',
  };

  const BRANDS = ['Lumen', 'Aperture', 'Northwave', 'Mira', 'Karbon', 'Verge', 'Halo', 'Nimbus'];

  // Placeholder palettes per category — used for striped product placeholders
  const PAL = {
    [C.audio]:       ['#1f2937', '#475569'],
    [C.laptops]:     ['#0f172a', '#334155'],
    [C.phones]:      ['#111827', '#3f3f46'],
    [C.wearables]:   ['#27272a', '#52525b'],
    [C.gaming]:      ['#18181b', '#3b0764'],
    [C.accessories]: ['#1c1917', '#44403c'],
    [C.home]:        ['#0c4a6e', '#155e75'],
  };

  const products = [
    // Audio
    { id: 'p01', name: 'Aurora Pro Wireless', brand: 'Lumen',     sku: 'LM-AP-001', cat: C.audio,       price: 4299, compareAt: 5799, rating: 4.7, reviews: 412, stock: 28, badges: ['Más vendido', '-26%'], colors: ['#1f2937','#f1f5f9','#7c3aed'], attrs: { connectivity: 'Bluetooth 5.3', battery: '36h', anc: true } },
    { id: 'p02', name: 'Earbud Mini X',       brand: 'Mira',      sku: 'MR-EM-014', cat: C.audio,       price: 1499, compareAt: 1899, rating: 4.4, reviews: 233, stock: 4,  badges: ['Stock bajo'],          colors: ['#111827','#f8fafc'],            attrs: { connectivity: 'Bluetooth 5.2', battery: '24h', anc: true } },
    { id: 'p03', name: 'Studio Reference 80', brand: 'Northwave', sku: 'NW-SR-080', cat: C.audio,       price: 8499, compareAt: null, rating: 4.9, reviews: 88,  stock: 12, badges: ['Edición limitada'],    colors: ['#1f2937','#92400e'],            attrs: { connectivity: 'Cable', battery: null, anc: false } },
    { id: 'p04', name: 'Onyx Bookshelf',      brand: 'Halo',      sku: 'HL-OX-220', cat: C.audio,       price: 6299, compareAt: 7299, rating: 4.6, reviews: 54,  stock: 9,  badges: ['-14%'],                colors: ['#0f172a'],                      attrs: { connectivity: 'Wi-Fi', battery: null, anc: false } },

    // Laptops
    { id: 'p05', name: 'Slate 14 Air',        brand: 'Aperture',  sku: 'AP-S14-A',  cat: C.laptops,     price: 22999, compareAt: 24999, rating: 4.8, reviews: 612, stock: 17, badges: ['Más vendido'],        colors: ['#94a3b8','#0f172a'],   attrs: { ram: '16GB', storage: '512GB', cpu: 'M-series' } },
    { id: 'p06', name: 'Slate 16 Pro',        brand: 'Aperture',  sku: 'AP-S16-P',  cat: C.laptops,     price: 34999, compareAt: null,  rating: 4.9, reviews: 211, stock: 6,  badges: ['Nuevo'],              colors: ['#0f172a'],             attrs: { ram: '32GB', storage: '1TB',   cpu: 'M-series' } },
    { id: 'p07', name: 'Karbon Carry 13',     brand: 'Karbon',    sku: 'KB-CC-013', cat: C.laptops,     price: 18499, compareAt: 21999, rating: 4.5, reviews: 134, stock: 22, badges: ['-15%'],               colors: ['#1f2937','#dc2626'],   attrs: { ram: '16GB', storage: '256GB', cpu: 'Ryzen' } },

    // Phones
    { id: 'p08', name: 'Verge Pulse 8',       brand: 'Verge',     sku: 'VR-PL-008', cat: C.phones,      price: 16999, compareAt: 18999, rating: 4.6, reviews: 904, stock: 41, badges: ['Más vendido'],         colors: ['#0f172a','#475569','#fde68a'], attrs: { storage: '256GB', ram: '8GB', screen: '6.5"' } },
    { id: 'p09', name: 'Verge Pulse 8 Pro',   brand: 'Verge',     sku: 'VR-PL-08P', cat: C.phones,      price: 22999, compareAt: null,  rating: 4.8, reviews: 322, stock: 19, badges: ['Nuevo','Envío gratis'],colors: ['#111827','#94a3b8'],           attrs: { storage: '512GB', ram: '12GB', screen: '6.7"' } },
    { id: 'p10', name: 'Mira Lite Phone',     brand: 'Mira',      sku: 'MR-LT-007', cat: C.phones,      price: 6499,  compareAt: 7999,  rating: 4.2, reviews: 156, stock: 33, badges: ['-19%'],                colors: ['#1c1917','#f8fafc','#0ea5e9'], attrs: { storage: '128GB', ram: '6GB',  screen: '6.1"' } },

    // Wearables
    { id: 'p11', name: 'Nimbus Watch 4',      brand: 'Nimbus',    sku: 'NB-W4-001', cat: C.wearables,   price: 4999, compareAt: 5999, rating: 4.5, reviews: 268, stock: 24, badges: ['-17%'],                colors: ['#0f172a','#dc2626','#f1f5f9'], attrs: { gps: true, lte: false, size: '42mm' } },
    { id: 'p12', name: 'Pulse Band Fit',      brand: 'Verge',     sku: 'VR-PB-FIT', cat: C.wearables,   price: 1299, compareAt: null, rating: 4.3, reviews: 412, stock: 80, badges: ['Más vendido'],         colors: ['#111827','#7c3aed','#22d3ee'], attrs: { gps: true, lte: false, size: 'Unisex' } },

    // Gaming
    { id: 'p13', name: 'Halo Trigger Pad',    brand: 'Halo',      sku: 'HL-TG-001', cat: C.gaming,      price: 1899, compareAt: 2199, rating: 4.6, reviews: 178, stock: 0,  badges: ['Agotado'],             colors: ['#18181b','#22c55e'],           attrs: { platform: 'Multi', wireless: true } },
    { id: 'p14', name: 'Northwave Mech 75',   brand: 'Northwave', sku: 'NW-MK-075', cat: C.gaming,      price: 2799, compareAt: null, rating: 4.8, reviews: 320, stock: 14, badges: ['Nuevo'],               colors: ['#1f2937','#f97316'],           attrs: { platform: 'PC',    wireless: false } },

    // Accessories
    { id: 'p15', name: 'Karbon USB-C Hub 7',  brand: 'Karbon',    sku: 'KB-HB-007', cat: C.accessories, price: 999,  compareAt: 1299, rating: 4.4, reviews: 99,  stock: 60, badges: ['-23%','Envío gratis'], colors: ['#1f2937'],                     attrs: { ports: 7,  speed: '10Gbps' } },
    { id: 'p16', name: 'Karbon GaN 65W',      brand: 'Karbon',    sku: 'KB-GN-065', cat: C.accessories, price: 749,  compareAt: null, rating: 4.7, reviews: 142, stock: 90, badges: [],                      colors: ['#0f172a','#f8fafc'],           attrs: { ports: 2,  speed: null } },

    // Smart home
    { id: 'p17', name: 'Nimbus Hub Mini',     brand: 'Nimbus',    sku: 'NB-HM-001', cat: C.home,        price: 2299, compareAt: 2699, rating: 4.5, reviews: 67,  stock: 26, badges: ['-15%'],                colors: ['#0c4a6e','#f1f5f9'],           attrs: { protocol: 'Matter', voice: true } },
    { id: 'p18', name: 'Lumen Bulb Pack 4',   brand: 'Lumen',     sku: 'LM-BP-004', cat: C.home,        price: 1199, compareAt: null, rating: 4.2, reviews: 311, stock: 120,badges: ['Más vendido'],         colors: ['#f8fafc','#fbbf24','#22d3ee'], attrs: { protocol: 'Matter', voice: false } },
  ];

  const categories = Object.values(C);

  const heroSlides = [
    { eyebrow: 'Colección Otoño 26', title: 'Sonido sin\ncompromiso.', sub: 'Hasta 40% OFF en audio premium hasta el domingo.', cta: 'Ver audio', tag: C.audio, accent: '#3b82f6', tone: 'dark' },
    { eyebrow: 'Lanzamiento',         title: 'Slate 16 Pro.\nFuerza pura.',   sub: 'Reserva ahora con 12 MSI sin intereses.',         cta: 'Reservar',  tag: C.laptops, accent: '#a3a3a3', tone: 'dark' },
    { eyebrow: 'Flash 48h',           title: 'Wearables\nhasta -30%.',        sub: 'Termina en menos de 48 horas. No se aceptan apartados.', cta: 'Ver ofertas', tag: C.wearables, accent: '#ef4444', tone: 'dark' },
  ];

  return { products, categories, brands: BRANDS, heroSlides, palette: PAL, C };
})();
