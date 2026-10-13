/* =========================================================
   TaquillApp — capa de pagos
   ---------------------------------------------------------
   Dos modos:
   · live: WordPress + plugin "TaquillApp Commerce". window.TQ_CONFIG trae
     la URL de la API REST, el nonce y las llaves PÚBLICAS de OpenPay y PayPal.
     El cobro, la confirmación y la emisión de códigos (RedPass) ocurren en el
     servidor; aquí solo se tokeniza la tarjeta y se muestran los botones de PayPal.
   · demo: sitio estático (GitHub Pages). Simula todo sin cobrar.
   ========================================================= */
window.TQPayments = (() => {
  const CFG = window.TQ_CONFIG || null;
  const live = !!(CFG && CFG.restUrl);

  /* ---------------- API REST ---------------- */
  async function api(path, body, method) {
    const res = await fetch(CFG.restUrl.replace(/\/$/, '') + path, {
      method: method || (body ? 'POST' : 'GET'),
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
      credentials: 'same-origin',
      body: body ? JSON.stringify(body) : undefined,
    });
    let data = {};
    try { data = await res.json(); } catch {}
    if (!res.ok) throw new Error(data.message || 'No pudimos completar la operación. Intenta de nuevo.');
    return data;
  }

  /* ---------------- Carga de SDKs ---------------- */
  const loaded = {};
  function loadScript(src) {
    if (!loaded[src]) {
      loaded[src] = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src; s.async = true;
        s.onload = resolve;
        s.onerror = () => reject(new Error('No se pudo cargar el módulo de pago. Revisa tu conexión.'));
        document.head.appendChild(s);
      });
    }
    return loaded[src];
  }

  let deviceSessionId = null;
  async function initOpenPay() {
    if (!live || !CFG.openpay || !CFG.openpay.merchantId) return false;
    await loadScript('https://js.openpay.mx/openpay.v1.min.js');
    await loadScript('https://js.openpay.mx/openpay-data.v1.min.js');
    OpenPay.setId(CFG.openpay.merchantId);
    OpenPay.setApiKey(CFG.openpay.publicKey);
    OpenPay.setSandboxMode(!!CFG.openpay.sandbox);
    if (!deviceSessionId) deviceSessionId = OpenPay.deviceData.setup('coStep2', 'deviceSessionId');
    return true;
  }

  function tokenizeCard(card) {
    return new Promise((resolve, reject) => {
      OpenPay.token.create(card,
        r => resolve(r.data.id),
        e => reject(new Error(openpayError(e))));
    });
  }

  function openpayError(e) {
    const code = e && e.data && e.data.error_code;
    const map = {
      2004: 'El número de tarjeta no es válido.',
      2005: 'La fecha de vencimiento ya pasó.',
      2006: 'Falta el código de seguridad (CVV).',
      3001: 'La tarjeta fue rechazada.',
      3002: 'La tarjeta está vencida.',
      3003: 'La tarjeta no tiene fondos suficientes.',
      3004: 'La tarjeta fue rechazada.',
      3005: 'La tarjeta fue rechazada.',
    };
    return map[code] || (e && e.data && e.data.description) || 'No pudimos validar tu tarjeta.';
  }

  /* ---------------- Flujo de compra ---------------- */
  // 1) Crea el pedido en el servidor. El precio se recalcula allá; el del navegador no cuenta.
  async function createOrder(items, customer) {
    if (!live) return { order_id: 'demo', order_key: 'demo', total: null };
    return api('/orders', { items, customer });
  }

  // 2a) Tarjeta vía OpenPay.
  async function payOpenPay(order, card) {
    if (!live) return demoResult();
    const body = { order_key: order.order_key, method: 'card' };
    if (!(await initOpenPay())) throw new Error('El pago con tarjeta aún no está configurado.');
    body.token_id = await tokenizeCard(card);
    body.device_session_id = deviceSessionId;
    const r = await api(`/orders/${order.order_id}/pay/openpay`, body);
    if (r.status === 'redirect' && r.redirect_url) { location.href = r.redirect_url; return new Promise(() => {}); }
    return r;
  }

  // 2b) PayPal: botones oficiales; la orden y la captura se hacen en el servidor.
  async function mountPayPal(container, getOrder, onDone, onError) {
    container.innerHTML = '';
    if (!live) {
      container.innerHTML = '<button type="button" class="paypal-demo">PayPal</button>';
      container.firstChild.addEventListener('click', async () => onDone(await demoResult()));
      return;
    }
    if (!CFG.paypal || !CFG.paypal.clientId) { container.innerHTML = '<p class="paynote">PayPal aún no está configurado.</p>'; return; }
    await loadScript(`https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(CFG.paypal.clientId)}&currency=MXN&intent=capture&components=buttons&locale=es_MX`);
    paypal.Buttons({
      style: { shape: 'pill', color: 'gold', label: 'pay', height: 48 },
      createOrder: async () => {
        const order = await getOrder();
        const r = await api(`/orders/${order.order_id}/pay/paypal`, { order_key: order.order_key });
        return r.paypal_order_id;
      },
      onApprove: async data => {
        const order = await getOrder();
        onDone(await api(`/orders/${order.order_id}/pay/paypal/capture`, { order_key: order.order_key, paypal_order_id: data.orderID }));
      },
      onError: err => onError(new Error('PayPal no pudo procesar el pago. Intenta de nuevo o usa otro método.')),
    }).render(container);
  }

  // Estado de un pedido (regreso de 3D Secure o consulta posterior).
  async function getOrderStatus(id, key) {
    return api(`/orders/${encodeURIComponent(id)}?order_key=${encodeURIComponent(key)}`);
  }

  /* ---------------- Demo ---------------- */
  const demoCode = () => 'TQ-' + Array.from({ length: 8 }, () => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[Math.random() * 31 | 0]).join('').replace(/(.{4})/, '$1-');
  function demoResult() {
    return new Promise(resolve => setTimeout(() => resolve({ status: 'paid', demo: true }), 1200));
  }

  return { live, createOrder, payOpenPay, mountPayPal, getOrderStatus, initOpenPay, demoCode };
})();
