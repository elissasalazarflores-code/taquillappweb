(() => {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const money = n => '$' + n.toLocaleString('es-MX');
  const icon = (name, cls = '') => `<svg class="${cls}"><use href="#i-${name}"/></svg>`;
  const CFG = window.TQ_CONFIG || {};
  const PRODUCTS = (CFG.products && CFG.products.length ? CFG.products : window.TQ_DEFAULT_PRODUCTS) || [];
  const CHAINS = window.TQ_CHAINS;
  const ASSETS = (window.TQ_THEME && window.TQ_THEME.assets) || 'assets/';
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
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
    toast(`${esc(byId(id).name)} agregado al carrito`);
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
    if (!grid) return;
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
      <article class="card" data-chain="${p.chain}" data-id="${esc(p.id)}" style="animation-delay:${Math.min(i, 10) * 45}ms" tabindex="0" aria-label="${esc(p.name)}, ${CHAINS[p.chain]}, desde ${money(p.price)}">
        <div class="card__media">
          <span class="card__chain">${CHAINS[p.chain]}</span>
          ${p.tag ? `<span class="card__tag">${esc(p.tag)}</span>` : ''}
          ${icon(p.icon)}
        </div>
        <div class="card__body">
          <span class="card__type">${TYPE_SINGULAR[p.type]}</span>
          <h3 class="card__name">${esc(p.name)}</h3>
          <p class="card__days">${esc(p.days)}</p>
          <div class="card__foot">
            <div class="price"><small>Desde</small><b><sup>$</sup>${p.price}</b></div>
            <button class="add-btn" data-add="${esc(p.id)}" aria-label="Agregar ${esc(p.name)} al carrito">${icon('plus')}</button>
          </div>
        </div>
      </article>`).join('');
  }

  function setSeg(groupSel, attr, value) {
    $$(`${groupSel} button`).forEach(b => b.classList.toggle('is-active', b.dataset[attr] === value));
  }
  $('#typeFilter')?.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    state.type = b.dataset.type; setSeg('#typeFilter', 'type', state.type); renderGrid();
  });
  $('#chainFilter')?.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    state.chain = b.dataset.chain; setSeg('#chainFilter', 'chain', state.chain); renderGrid();
  });
  $('#searchInput')?.addEventListener('input', e => { state.q = e.target.value.trim(); renderGrid(); });
  $('#sortSelect')?.addEventListener('change', e => { state.sort = e.target.value; renderGrid(); });
  $$('[data-quick]').forEach(a => a.addEventListener('click', () => {
    state.type = a.dataset.quick; setSeg('#typeFilter', 'type', state.type); renderGrid();
  }));

  grid?.addEventListener('click', e => {
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
  grid?.addEventListener('keydown', e => {
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
        <span class="card__type">${TYPE_SINGULAR[p.type]}${p.tag ? ' · ' + esc(p.tag) : ''}</span>
        <h3 id="pmTitle">${esc(p.name)}</h3>
        <p class="card__days">${esc(p.days)}</p>
        <p class="pm__desc">${esc(p.desc)}</p>
        <div class="price"><small>Desde</small><b><sup>$</sup>${p.price}</b></div>
        <ul class="pm__terms">${p.terms.map(t => `<li>${icon('check')}<span>${esc(t)}</span></li>`).join('')}</ul>
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
          <h4>${esc(p.name)}</h4>
          <small>${CHAINS[p.chain]} · ${esc(p.days)}</small>
          <div class="qty qty--sm"><button data-dec="${id}" aria-label="Menos">${icon('minus')}</button><output>${q}</output><button data-inc="${id}" aria-label="Más">${icon('plus')}</button></div>
        </div>
        <div class="line-item__price">${money(p.price * q)}<button class="line-item__remove" data-rm="${id}">Quitar</button></div>
      </div>`;
    }).join('') : `<div class="cart-empty">
        <img src="${ASSETS}icon.png" alt="">
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
  const Pay = window.TQPayments;
  const coModal = $('#checkoutModal');
  let buyer = {}, order = null, orderSig = '';

  function goStep(n) {
    $$('.co__panel').forEach((p, i) => p.classList.toggle('is-active', i === n - 1));
    $$('#coSteps li').forEach((li, i) => {
      li.classList.toggle('is-active', i === n - 1);
      li.classList.toggle('is-done', i < n - 1);
    });
  }
  function renderSummary(total) {
    $('#coSummary').innerHTML = Object.entries(cart).map(([id, q]) => {
      const p = byId(id);
      return `<div class="sum-row"><span>${q} × ${esc(p.name)} · ${CHAINS[p.chain]}</span><b>${money(p.price * q)}</b></div>`;
    }).join('');
    const t = total ?? cartTotal();
    $('#coTotal').textContent = money(t);
    $('#coPay').textContent = `Pagar ${money(t)}`;
  }
  function showError(msg) {
    const e = $('#coError');
    e.textContent = msg || ''; e.hidden = !msg;
  }
  function busy(on, label) {
    const btn = $('#coPay');
    btn.disabled = on;
    if (label) btn.textContent = label;
  }
  const currentMethod = () => new FormData($('#coStep2')).get('method');

  $('#checkoutBtn').addEventListener('click', () => {
    if (!cartCount()) return;
    closeDrawer(); renderSummary(); showError(''); goStep(1); coModal.showModal();
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

  // El pedido se crea en el servidor al pasar al paso de pago; si el carrito cambia, se crea otro.
  async function ensureOrder() {
    const sig = JSON.stringify(cart) + '|' + buyer.email;
    if (order && sig === orderSig) return order;
    order = await Pay.createOrder(
      Object.entries(cart).map(([id, qty]) => ({ id, qty })),
      { name: buyer.name, last_name: buyer.last, email: buyer.email, phone: buyer.phone || '' }
    );
    orderSig = sig;
    if (order.total != null) renderSummary(order.total);
    return order;
  }

  $('#coStep1').addEventListener('submit', async e => {
    e.preventDefault();
    if (!validate(e.target)) { toast('Revisa los campos marcados'); return; }
    buyer = Object.fromEntries(new FormData(e.target));
    const btn = e.submitter || $('#coStep1 button[type="submit"]');
    btn.disabled = true;
    try {
      await ensureOrder();
      showError('');
      goStep(2);
      selectMethod(currentMethod());
      Pay.initOpenPay().catch(() => {});
    } catch (err) {
      toast(err.message);
    } finally {
      btn.disabled = false;
    }
  });
  $('#coBack').addEventListener('click', () => goStep(1));

  function selectMethod(m) {
    $$('.paybox').forEach(b => b.hidden = b.dataset.method !== m);
    $('#coPay').hidden = m === 'paypal';
    showError('');
    if (m === 'paypal') {
      Pay.mountPayPal($('#paypalButtons'), ensureOrder, finish, err => showError(err.message))
        .catch(err => showError(err.message));
    }
  }
  $$('#coStep2 input[name="method"]').forEach(r => r.addEventListener('change', () => selectMethod(r.value)));
  $('#openpayCardForm').addEventListener('input', () => showError(''));

  $('[data-cc]').addEventListener('input', e => {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
  });
  $('[data-exp]').addEventListener('input', e => {
    const v = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v;
  });

  function readCard() {
    const f = $('#openpayCardForm');
    const get = k => $(`[data-openpay-card="${k}"]`, f).value.trim();
    const [mm, yy] = $('[data-exp]', f).value.split('/');
    const card = {
      holder_name: get('holder_name'),
      card_number: get('card_number').replace(/\s/g, ''),
      cvv2: get('cvv2'),
      expiration_month: (mm || '').trim(),
      expiration_year: (yy || '').trim(),
    };
    if (!card.holder_name) throw new Error('Escribe el nombre como aparece en la tarjeta.');
    if (card.card_number.length < 15) throw new Error('Revisa el número de tarjeta.');
    if (!/^\d{2}$/.test(card.expiration_month) || !/^\d{2}$/.test(card.expiration_year)) throw new Error('Revisa la fecha de vencimiento (MM/AA).');
    if (card.cvv2.length < 3) throw new Error('Revisa el código de seguridad.');
    return card;
  }

  $('#coStep2').addEventListener('submit', async e => {
    e.preventDefault();
    const method = currentMethod();
    if (method === 'paypal') return;
    showError('');
    const label = $('#coPay').textContent;
    try {
      const card = readCard();
      busy(true, 'Procesando…');
      const o = await ensureOrder();
      finish(await Pay.payOpenPay(o, card));
    } catch (err) {
      showError(err.message);
    } finally {
      busy(false, label);
    }
  });

  function finish(result) {
    $('#doneCodes').innerHTML = '';
    const email = esc(buyer.email || result.email || '');
    $('#doneTitle').textContent = '¡Listo! Tus códigos van en camino.';
    let codes = result.codes || [];
    if (result.demo) codes = Object.entries(cart).flatMap(([id, q]) => Array.from({ length: q }, () => ({ product: byId(id).name, chain: CHAINS[byId(id).chain], code: Pay.demoCode() })));
    $('#doneMsg').innerHTML = codes.length
      ? `Enviamos tus códigos a ${email}. También los ves aquí:`
      : `Pago confirmado. Estamos generando tus códigos oficiales y te llegarán a ${email} en unos minutos.`;
    $('#doneCodes').innerHTML = codes.map(c => `<div class="done__code"><small>${esc(c.product)}${c.chain ? ' · ' + esc(c.chain) : ''}</small><b>${esc(c.code)}</b></div>`).join('');
    cart = {}; store.set(cart); renderCart();
    order = null; orderSig = '';
    if (!coModal.open) coModal.showModal();
    goStep(3);
  }

  // Regreso desde 3D Secure (OpenPay redirige con ?tq_order=…&tq_key=…).
  (async () => {
    const q = new URLSearchParams(location.search);
    if (!Pay.live || !q.get('tq_order') || !q.get('tq_key')) return;
    history.replaceState(null, '', location.pathname + location.hash);
    try {
      const r = await Pay.getOrderStatus(q.get('tq_order'), q.get('tq_key'));
      if (r.status === 'paid') finish(r);
      else if (r.status === 'processing') toast('Tu banco está confirmando el pago. Te enviaremos tus códigos por correo.');
      else toast('El pago no se completó. Puedes intentarlo de nuevo.');
    } catch (err) { toast(err.message); }
  })();

  /* ---------------- Newsletter ---------------- */
  $('#newsForm')?.addEventListener('submit', e => {
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

  const sp = $('#statProducts');
  if (sp) sp.dataset.count = PRODUCTS.length;
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
  if (stage && front && matchMedia('(hover: hover)').matches) {
    $('.hero').addEventListener('pointermove', e => {
      const r = stage.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      front.style.setProperty('--ry', `${x * 18}deg`);
      front.style.setProperty('--rx', `${-y * 14}deg`);
      front.style.setProperty('--px', `${x * 20}px`);
      front.style.setProperty('--py', `${y * 20}px`);
    });
  }
  setInterval(() => { const c = $('#heroCode'); if (c) c.textContent = Pay.demoCode(); }, 2600);

  renderGrid();
  renderCart();
})();
