/* VINUM & SPIRITS — Interactions */

// === Theme toggle (light/dark with persistence + system pref) ===
(function () {
  const STORE = 'vs_theme';
  const saved = localStorage.getItem(STORE);
  const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
  const initial = saved || (prefersLight ? 'light' : 'dark');
  if (initial === 'light') document.body.classList.add('light');
  const btn = document.getElementById('themeToggle');
  if (btn) {
    btn.addEventListener('click', () => {
      const isLight = document.body.classList.toggle('light');
      localStorage.setItem(STORE, isLight ? 'light' : 'dark');
    });
  }
})();

// === Utility bar collapse on scroll ===
(function () {
  const bar = document.querySelector('.utility-bar');
  let last = 0;
  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    if (y > 80 && y > last) bar.classList.add('is-collapsed');
    else if (y < 30) bar.classList.remove('is-collapsed');
    last = y;
  }, { passive: true });
})();

// === Hero carousel ===
(function () {
  const track = document.getElementById('heroTrack');
  if (!track) return;
  const slides = track.children.length;
  let idx = 0;
  const dots = document.querySelectorAll('.hero-dots button');
  function go(n) {
    idx = (n + slides) % slides;
    track.style.transform = `translateX(-${idx * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === idx));
  }
  dots.forEach((d, i) => d.addEventListener('click', () => go(i)));
  document.getElementById('heroPrev').addEventListener('click', () => go(idx - 1));
  document.getElementById('heroNext').addEventListener('click', () => go(idx + 1));
  let timer = setInterval(() => go(idx + 1), 6000);
  track.addEventListener('mouseenter', () => clearInterval(timer));
  track.addEventListener('mouseleave', () => { timer = setInterval(() => go(idx + 1), 6000); });
})();

// === Filter checkboxes ===
document.querySelectorAll('.check-list label').forEach(l => {
  l.addEventListener('click', e => {
    if (e.target.tagName.toLowerCase() === 'a') return;
    l.classList.toggle('checked');
  });
});

// === Filter tags ===
document.querySelectorAll('.tag-row .tag').forEach(t => {
  t.addEventListener('click', () => t.classList.toggle('on'));
});

// === Wishlist hearts ===
document.querySelectorAll('.wish-btn').forEach(b => {
  b.addEventListener('click', e => {
    e.stopPropagation();
    b.classList.toggle('on');
  });
});

// === Add to cart toast ===
(function () {
  const toast = document.getElementById('toast');
  const cartBadge = document.querySelector('.cart-badge');
  let count = parseInt(cartBadge?.textContent || '3', 10);
  let toastTimer = null;
  document.querySelectorAll('.add-cart').forEach(btn => {
    btn.addEventListener('click', () => {
      count++;
      if (cartBadge) cartBadge.textContent = String(count);
      const card = btn.closest('.product');
      const name = card?.querySelector('.product-name')?.textContent || 'Producto';
      toast.querySelector('.toast-title').textContent = 'Agregado al carrito';
      toast.querySelector('.toast-sub').textContent = name;
      toast.classList.add('show');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
    });
  });
  toast.querySelector('.toast-link')?.addEventListener('click', () => toast.classList.remove('show'));
})();

// === Age verification modal ===
(function () {
  const modal = document.getElementById('ageModal');
  if (!modal) return;
  const seen = sessionStorage.getItem('vs_age_ok');
  if (!seen) {
    setTimeout(() => modal.classList.add('show'), 400);
  }
  document.getElementById('ageYes')?.addEventListener('click', () => {
    sessionStorage.setItem('vs_age_ok', '1');
    modal.classList.remove('show');
  });
  document.getElementById('ageNo')?.addEventListener('click', () => {
    window.location.href = 'https://www.google.com';
  });
})();

// === Chat bubble dismiss ===
document.querySelector('.close-bubble')?.addEventListener('click', () => {
  document.querySelector('.chat-bubble').style.display = 'none';
});

// === View toggle (cosmetic) ===
document.querySelectorAll('.view-toggle button').forEach(b => {
  b.addEventListener('click', () => {
    document.querySelectorAll('.view-toggle button').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
  });
});

// === Sort pills ===
document.querySelectorAll('.tab-pills button').forEach(b => {
  b.addEventListener('click', () => {
    const sibs = b.parentElement.querySelectorAll('button');
    sibs.forEach(x => x.classList.remove('active'));
    b.classList.add('active');
  });
});
