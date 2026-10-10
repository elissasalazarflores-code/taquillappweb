(() => {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const money = n => '$' + n.toLocaleString('es-MX');
  const icon = (name, cls = '') => `<svg class="${cls}"><use href="#i-${name}"/></svg>`;
  const byId = id => PRODUCTS.find(p => p.id === id);
  const TYPE_SINGULAR = { boleto: 'Boleto', combo: 'Combo', paquete: 'Paquete' };

  /* ---------------- Storage-safe cart ---------------- */
  const store = {
    get() { try { return JSON.parse(localStorage.getItem('tq-cart')) || {}; } catch { return {}; } },
    set(v) { try { localStorage.setItem('tq-cart', JSON.stringify(v)); } catch {} },
  };
  let cart = store.get();
  Object.keys(cart).forEach(id => { if (!byId(id)) delete cart[id]; });

  const cartCount = () => Object.values(cart).reduce((a, b) => a + b, 0);
  const cartTotal = () => Object.entries(cart).reduce((a, [id, q]) => a + byId(id).price * q, 0);

  function setQty(id, q) {
    if (q <= 0) delete cart[id]; else cart[id] = Math.min(q, 20);
    store.set(cart);
    renderCart();
  }
  function addToCart(id, q = 1) {
    setQty(id, (cart[id] || 0) + q);
    const c = $('#cartCount');
    c.classList.remove('bump'); void c.offsetWidth; c.classList.add('bump');
    toast(`${byId(id).name} agregado al carrito`);
  }

  /* ---------------- Toast ---------------- */
  let toastT;
  function toast(msg) {
    const t = $('#toast');
    t.innerHTML = `<i></i>${msg}`;
    t.classList.add('is-on');
    clearTimeout(toastT);
    toastT = setTimeout(() => t.classList.remove('is-on'), 2400);
  }

  /* ---------------- Catalog ---------------- */
  const state = { type: 'all', chain: 'all', q: '', sort: 'featured' };
  const grid = $('#productGrid');
  const norm = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

  function renderGrid() {
    let list = PRODUCTS.filter(p =>
      (state.type === 'all' || p.type === state.type) &&
      (state.chain === 'all' || p.chain === state.chain) &&
      (!state.q || norm(`${p.name} ${p.days} ${CHAINS[p.chain]} ${p.type} ${p.desc}`).includes(norm(state.q)))
    );
    if (state.sort === 'price-asc') list = [...list].sort((a, b) => a.price - b.price);
    if (state.sort === 'price-desc') list = [...list].sort((a, b) => b.price - a.price);

    $('#catalogCount').textContent = `${list.length} ${list.length === 1 ? 'producto' : 'productos'}`;
    if (!list.length) {
      grid.innerHTML = `<div class="empty">No encontramos nada con esos filtros. Prueba con otra búsqueda.</div>`;
      return;
    }
    grid.innerHTML = list.map((p, i) => `
      <article class="card" data-chain="${p.chain}" data-id="${p.id}" style="animation-delay:${Math.min(i, 10) * 45}ms" tabindex="0" aria-label="${p.name}, ${CHAINS[p.chain]}, desde ${money(p.price)}">
        <div class="card__media">
          <span class="card__chain">${CHAINS[p.chain]}</span>
          ${p.tag ? `<span class="card__tag">${p.tag}</span>` : ''}
          ${icon(p.icon)}
        </div>
        <div class="card__body">
          <span class="card__type">${TYPE_SINGULAR[p.type]}</span>
          <h3 class="card__name">${p.name}</h3>
          <p class="card__days">${p.days}</p>
          <div class="card__foot">
            <div class="price"><small>Desde</small><b><sup>$</sup>${p.price}</b></div>
            <button class="add-btn" data-add="${p.id}" aria-label="Agregar ${p.name} al carrito">${icon('plus')}</button>
          </div>
        </div>
      </article>`).join('');
  }

  function setSeg(groupSel, attr, value) {
    $$(`${groupSel} button`).forEach(b => b.classList.toggle('is-active', b.dataset[attr] === value));
  }
  $('#typeFilter').addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    state.type = b.dataset.type; setSeg('#typeFilter', 'type', state.type); renderGrid();
  });
  $('#chainFilter').addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    state.chain = b.dataset.chain; setSeg('#chainFilter', 'chain', state.chain); renderGrid();
  });
  $('#searchInput').addEventListener('input', e => { state.q = e.target.value.trim(); renderGrid(); });
  $('#sortSelect').addEventListener('change', e => { state.sort = e.target.value; renderGrid(); });
  $$('[data-quick]').forEach(a => a.addEventListener('click', () => {
    state.type = a.dataset.quick; setSeg('#typeFilter', 'type', state.type); renderGrid();
  }));

  grid.addEventListener('click', e => {
    const add = e.target.closest('[data-add]');
    if (add) {
      e.stopPropagation();
      addToCart(add.dataset.add);
      add.classList.add('is-added'); add.innerHTML = icon('check');
      setTimeout(() => { add.classList.remove('is-added'); add.innerHTML = icon('plus'); }, 1200);
      return;
    }
    const card = e.target.closest('.card');
    if (card) openProduct(card.dataset.id);
  });
  grid.addEventListener('keydown', e => {
    if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('card')) {
      e.preventDefault(); openProduct(e.target.dataset.id);
    }
  });

  /* ---------------- Product modal ---------------- */
  const pModal = $('#productModal');
  function openProduct(id) {
    const p = byId(id);
    let q = 1;
    $('#pmBody').innerHTML = `
      <div class="pm__media">
        <span class="card__chain">${CHAINS[p.chain]}</span>
        ${icon(p.icon)}
      </div>
      <div class="pm__body">
        <span class="card__type">${TYPE_SINGULAR[p.type]}${p.tag ? ' · ' + p.tag : ''}</span>
        <h3 id="pmTitle">${p.name}</h3>
        <p class="card__days">${p.days}</p>
        <p class="pm__desc">${p.desc}</p>
        <div class="price"><small>Desde</small><b><sup>$</sup>${p.price}</b></div>
        <ul class="pm__terms">${p.terms.map(t => `<li>${icon('check')}<span>${t}</span></li>`).join('')}</ul>
        <div class="pm__buy">
          <div class="qty"><button data-q="-1" aria-label="Menos">${icon('minus')}</button><output id="pmQty">1</output><button data-q="1" aria-label="Más">${icon('plus')}</button></div>
          <button class="btn btn--grad" id="pmAdd" style="flex:1">Agregar · <span id="pmTotal">${money(p.price)}</span></button>
        </div>
      </div>`;
    $$('.pm [data-q]').forEach(b => b.addEventListener('click', () => {
      q = Math.max(1, Math.min(20, q + +b.dataset.q));
      $('#pmQty').textContent = q;
      $('#pmTotal').textContent = money(p.price * q);
    }));
    $('#pmAdd').addEventListener('click', () => { addToCart(p.id, q); pModal.close(); });
    pModal.showModal();
  }

  $$('dialog').forEach(d => {
    d.addEventListener('click', e => {
      if (e.target === d || e.target.closest('[data-close]')) d.close();
    });
  });

  /* ---------------- Cart drawer ---------------- */
  const drawer = $('#drawer'), overlay = $('#overlay');
  function openDrawer() { drawer.classList.add('is-open'); overlay.classList.add('is-on'); drawer.setAttribute('aria-hidden', 'false'); }
  function closeDrawer() { drawer.classList.remove('is-open'); overlay.classList.remove('is-on'); drawer.setAttribute('aria-hidden', 'true'); }
  $('#cartOpen').addEventListener('click', openDrawer);
  $('#cartClose').addEventListener('click', closeDrawer);
  overlay.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

  function renderCart() {
    $('#cartCount').textContent = cartCount();
    const entries = Object.entries(cart);
    $('#cartFoot').style.display = entries.length ? '' : 'none';
    $('#cartSubtotal').textContent = money(cartTotal());
    $('#cartItems').innerHTML = entries.length ? entries.map(([id, q]) => {
      const p = byId(id);
      return `<div class="line-item">
        <div class="line-item__thumb">${icon(p.icon)}</div>
        <div>
          <h4>${p.name}</h4>
          <small>${CHAINS[p.chain]} · ${p.days}</small>
          <div class="qty qty--sm"><button data-dec="${id}" aria-label="Menos">${icon('minus')}</button><output>${q}</output><button data-inc="${id}" aria-label="Más">${icon('plus')}</button></div>
        </div>
        <div class="line-item__price">${money(p.price * q)}<button class="line-item__remove" data-rm="${id}">Quitar</button></div>
      </div>`;
    }).join('') : `<div class="cart-empty">
        <img src="assets/icon.png" alt="">
        <h4>Tu carrito está vacío</h4>
        <p>Agrega boletos o combos para tu próxima función.</p>
        <a href="#catalogo" class="btn btn--ghost btn--sm" id="emptyGo">Ver catálogo</a>
      </div>`;
  }
  $('#cartItems').addEventListener('click', e => {
    const t = e.target.closest('button, a'); if (!t) return;
    if (t.dataset.inc) setQty(t.dataset.inc, cart[t.dataset.inc] + 1);
    if (t.dataset.dec) setQty(t.dataset.dec, cart[t.dataset.dec] - 1);
    if (t.dataset.rm) setQty(t.dataset.rm, 0);
    if (t.id === 'emptyGo') closeDrawer();
  });

  /* ---------------- Checkout ---------------- */
  // Prototipo: los datos de pago no se envían a ningún servidor.
  const coModal = $('#checkoutModal');
  function goStep(n) {
    $$('.co__panel').forEach((p, i) => p.classList.toggle('is-active', i === n - 1));
    $$('#coSteps li').forEach((li, i) => {
      li.classList.toggle('is-active', i === n - 1);
      li.classList.toggle('is-done', i < n - 1);
    });
  }
  function renderSummary() {
    $('#coSummary').innerHTML = Object.entries(cart).map(([id, q]) => {
      const p = byId(id);
      return `<div class="sum-row"><span>${q} × ${p.name} · ${CHAINS[p.chain]}</span><b>${money(p.price * q)}</b></div>`;
    }).join('');
    $('#coTotal').textContent = money(cartTotal());
    $('#coPay').textContent = `Pagar ${money(cartTotal())}`;
  }
  $('#checkoutBtn').addEventListener('click', () => {
    if (!cartCount()) return;
    closeDrawer(); renderSummary(); goStep(1); coModal.showModal();
  });

  function validate(form) {
    let ok = true;
    $$('input[required]', form).forEach(i => {
      const bad = i.type === 'checkbox' ? !i.checked : !i.checkValidity() || !i.value.trim();
      i.classList.toggle('is-invalid', bad);
      if (bad && ok) { i.focus(); ok = false; }
    });
    return ok;
  }
  let buyer = {};
  $('#coStep1').addEventListener('submit', e => {
    e.preventDefault();
    if (!validate(e.target)) { toast('Revisa los campos marcados'); return; }
    buyer = Object.fromEntries(new FormData(e.target));
    goStep(2);
  });
  $('#coBack').addEventListener('click', () => goStep(1));
  $$('#coStep2 input[name="method"]').forEach(r => r.addEventListener('change', () => {
    $$('.paybox').forEach(b => b.hidden = b.dataset.method !== r.value);
  }));
  $('[data-cc]').addEventListener('input', e => {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
  });
  $('[data-exp]').addEventListener('input', e => {
    const v = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v;
  });

  const code = () => 'TQ-' + Array.from({ length: 8 }, () => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[Math.random() * 31 | 0]).join('').replace(/(.{4})/, '$1-');
  $('#coStep2').addEventListener('submit', e => {
    e.preventDefault();
    const btn = $('#coPay');
    btn.disabled = true; btn.textContent = 'Procesando…';
    setTimeout(() => {
      const method = new FormData(e.target).get('method');
      $('#doneMsg').textContent = method === 'card'
        ? `Enviamos tus códigos a ${buyer.email}. También los ves aquí:`
        : `Te enviamos las instrucciones de pago a ${buyer.email}. Tus códigos se activan al confirmarse el pago.`;
      $('#doneCodes').innerHTML = Object.entries(cart).flatMap(([id, q]) =>
        Array.from({ length: q }, () => `<div class="done__code"><small>${byId(id).name} · ${CHAINS[byId(id).chain]}</small><b>${code()}</b></div>`)
      ).join('');
      cart = {}; store.set(cart); renderCart();
      btn.disabled = false;
      goStep(3);
    }, 1400);
  });

  /* ---------------- Newsletter ---------------- */
  $('#newsForm').addEventListener('submit', e => {
    e.preventDefault(); e.target.reset(); toast('¡Listo! Te escribiremos con lo mejor de la semana.');
  });

  /* ---------------- Nav ---------------- */
  const nav = $('#nav');
  const onScroll = () => nav.classList.toggle('is-scrolled', scrollY > 30);
  addEventListener('scroll', onScroll, { passive: true }); onScroll();
  $('#menuBtn').addEventListener('click', () => $('#mobileMenu').classList.toggle('is-open'));
  $$('#mobileMenu a').forEach(a => a.addEventListener('click', () => $('#mobileMenu').classList.remove('is-open')));

  /* ---------------- Reveal + counters ---------------- */
  const io = new IntersectionObserver(entries => entries.forEach(en => {
    if (!en.isIntersecting) return;
    en.target.classList.add('is-in');
    io.unobserve(en.target);
  }), { threshold: .15 });
  $$('.reveal, .step').forEach(el => io.observe(el));

  $$('[data-count]').forEach(el => {
    const end = +el.dataset.count, t0 = performance.now(), d = 1400;
    const tick = t => {
      const k = Math.min(1, (t - t0) / d);
      el.textContent = Math.round(end * (1 - Math.pow(1 - k, 3)));
      if (k < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });

  /* ---------------- Hero parallax + rotating code ---------------- */
  const stage = $('#stage'), front = $('.tk--front');
  if (stage && matchMedia('(hover: hover)').matches) {
    $('.hero').addEventListener('pointermove', e => {
      const r = stage.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      front.style.setProperty('--ry', `${x * 18}deg`);
      front.style.setProperty('--rx', `${-y * 14}deg`);
      front.style.setProperty('--px', `${x * 20}px`);
      front.style.setProperty('--py', `${y * 20}px`);
    });
  }
  setInterval(() => { const c = $('#heroCode'); if (c) c.textContent = code(); }, 2600);

  renderGrid();
  renderCart();
})();
