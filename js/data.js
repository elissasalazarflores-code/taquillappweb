// Catálogo TaquillApp — precios "desde" en MXN.
// type: boleto | combo | paquete · chain: cinepolis | cinemex
const COMMON = [
  'Vigencia de 3 meses a partir de la fecha de compra.',
  'No acumulable con otras promociones.',
  'Sujeto a disponibilidad del complejo.',
];

const PRODUCTS = [
  // ——— Boletos Cinemex
  { id: 'cmx-trad-lv', chain: 'cinemex', type: 'boleto', icon: 'ticket', name: 'Boleto Tradicional', days: 'Lunes a viernes', price: 61, tag: 'Más barato',
    desc: 'Tu entrada a cualquier función 2D en sala tradicional, entre semana.',
    terms: ['Válido de lunes a viernes.', 'Solo salas tradicionales 2D.'] },
  { id: 'cmx-trad-ld', chain: 'cinemex', type: 'boleto', icon: 'ticket', name: 'Boleto Tradicional', days: 'Lunes a domingo', price: 67, tag: 'Top ventas',
    desc: 'La entrada clásica, cualquier día de la semana, incluido el fin.',
    terms: ['Válido los 7 días.', 'Solo salas tradicionales 2D.'] },
  { id: 'cmx-3d-ld', chain: 'cinemex', type: 'boleto', icon: 'glasses', name: 'Boleto 3D', days: 'Lunes a domingo', price: 91,
    desc: 'Funciones en 3D para ver los estrenos con profundidad.',
    terms: ['Válido los 7 días.', 'Solo funciones en formato 3D.'] },
  { id: 'cmx-premium-ld', chain: 'cinemex', type: 'boleto', icon: 'star', name: 'Boleto Premium', days: 'Lunes a domingo', price: 105,
    desc: 'Asientos más amplios y una experiencia un nivel arriba.',
    terms: ['Válido los 7 días.', 'Solo salas Premium.'] },
  { id: 'cmx-platino-ld', chain: 'cinemex', type: 'boleto', icon: 'crown', name: 'Boleto Platino', days: 'Lunes a domingo', price: 122, tag: 'Experiencia',
    desc: 'Butacas reclinables y servicio a tu asiento en salas Platino.',
    terms: ['Válido los 7 días.', 'Solo salas Platino.'] },

  // ——— Boletos Cinépolis
  { id: 'cnp-trad-lv', chain: 'cinepolis', type: 'boleto', icon: 'ticket', name: 'Boleto Tradicional', days: 'Lunes a viernes', price: 67,
    desc: 'Entrada a sala tradicional 2D, entre semana.',
    terms: ['Válido de lunes a viernes.', 'No aplica en formatos diferenciados (IMAX, 4DX, Macro XE, Sala Junior, Pluus).'] },
  { id: 'cnp-trad-ld', chain: 'cinepolis', type: 'boleto', icon: 'ticket', name: 'Boleto Tradicional', days: 'Lunes a domingo', price: 72, tag: 'Top ventas',
    desc: 'Entrada a sala tradicional 2D, cualquier día.',
    terms: ['Válido los 7 días.', 'No aplica en formatos diferenciados (IMAX, 4DX, Macro XE, Sala Junior, Pluus).'] },
  { id: 'cnp-dif-ld', chain: 'cinepolis', type: 'boleto', icon: 'spark', name: 'Formatos Diferenciados', days: 'Lunes a domingo', price: 115, tag: 'IMAX · 4DX',
    desc: 'IMAX, 4DX, Macro XE y más: la película como se pensó para verse.',
    terms: ['Válido los 7 días.', 'Aplica en IMAX, 4DX, Macro XE y formatos participantes.'] },
  { id: 'cnp-vip-lv', chain: 'cinepolis', type: 'boleto', icon: 'crown', name: 'Boleto VIP', days: 'Lunes a viernes', price: 132,
    desc: 'Sala VIP con butacas reclinables y servicio a tu lugar.',
    terms: ['Válido de lunes a viernes.', 'Solo salas VIP.'] },

  // ——— Combos Cinemex
  { id: 'cmx-combo-grande', chain: 'cinemex', type: 'combo', icon: 'popcorn', name: 'Combo Grande', days: 'Dulcería', price: 110, tag: 'Top ventas',
    desc: 'Palomitas grandes y refresco para compartir la función.',
    terms: ['Canje únicamente en dulcería.'] },
  { id: 'cmx-combo-hotdog', chain: 'cinemex', type: 'combo', icon: 'hotdog', name: 'Combo Hot Dog', days: 'Dulcería', price: 145,
    desc: 'Hot dog y refresco, el clásico de antes de la película.',
    terms: ['Canje únicamente en dulcería.'] },
  { id: 'cmx-combo-platino', chain: 'cinemex', type: 'combo', icon: 'popcorn', name: 'Combo Grande Platino', days: 'Salas Platino', price: 160,
    desc: 'El combo grande servido en tu butaca de sala Platino.',
    terms: ['Canje únicamente en dulcería o servicio Platino.', 'Solo salas Platino.'] },
  { id: 'cmx-combo-nachos', chain: 'cinemex', type: 'combo', icon: 'nachos', name: 'Combo Nachos', days: 'Dulcería', price: 166,
    desc: 'Nachos con queso y refresco para la botana completa.',
    terms: ['Canje únicamente en dulcería.'] },

  // ——— Combos Cinépolis
  { id: 'cnp-combo-hotdog', chain: 'cinepolis', type: 'combo', icon: 'hotdog', name: 'Hot Dog + Refresco Mediano', days: 'Dulcería', price: 116,
    desc: 'Hot dog y refresco mediano, listo para la función.',
    terms: ['Canje únicamente en dulcería.'] },
  { id: 'cnp-combo-nachos', chain: 'cinepolis', type: 'combo', icon: 'nachos', name: 'Nachos con Queso + Refresco Mediano', days: 'Dulcería', price: 120,
    desc: 'Nachos con queso y un refresco mediano.',
    terms: ['Canje únicamente en dulcería.'] },
  { id: 'cnp-combo-palomitas', chain: 'cinepolis', type: 'combo', icon: 'popcorn', name: 'Palomitas Medianas + Refresco Mediano', days: 'Salas no VIP', price: 124, tag: 'Top ventas',
    desc: 'Las palomitas de siempre con refresco mediano.',
    terms: ['Canje únicamente en dulcería.', 'No válido en salas VIP.'] },
  { id: 'cnp-combo-vip-palomitas', chain: 'cinepolis', type: 'combo', icon: 'popcorn', name: 'VIP Palomitas Medianas + Refresco', days: 'Salas VIP', price: 140,
    desc: 'Palomitas medianas y refresco mediano para sala VIP.',
    terms: ['Válido solo en salas VIP.'] },
  { id: 'cnp-combo-vip-hotdog', chain: 'cinepolis', type: 'combo', icon: 'hotdog', name: 'VIP Hot Dog + Refresco Mediano', days: 'Salas VIP', price: 216,
    desc: 'Hot dog gourmet y refresco mediano en sala VIP.',
    terms: ['Válido solo en salas VIP.'] },

  // ——— Paquetes
  { id: 'cmx-paquete-pareja', chain: 'cinemex', type: 'paquete', icon: 'duo', name: 'Paquete Pareja', days: '2 entradas + combo', price: 312, tag: 'Para dos',
    desc: '2 entradas tradicionales, palomitas y refrescos. La cita resuelta.',
    terms: ['Incluye 2 boletos tradicionales 2D.', 'Combo canjeable en dulcería.'] },
  { id: 'cnp-paquete-vip', chain: 'cinepolis', type: 'paquete', icon: 'duo', name: 'Paquete VIP para Dos', days: '2 entradas VIP + combo', price: 453, tag: 'Premium',
    desc: '2 entradas VIP, palomitas grandes y 2 refrescos medianos.',
    terms: ['Válido solo en salas VIP.', 'Combo canjeable en dulcería.'] },
].map(p => ({ ...p, terms: [...p.terms, ...COMMON] }));

const CHAINS = { cinepolis: 'Cinépolis', cinemex: 'Cinemex' };
const TYPES = { boleto: 'Boletos', combo: 'Combos', paquete: 'Paquetes' };
