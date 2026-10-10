# TaquillApp — sitio web

Propuesta de sitio e-commerce para TaquillApp: boletos, combos y paquetes de Cinépolis y Cinemex con descuento.

Sitio estático, sin dependencias ni build. Abre `index.html` en el navegador o sírvelo con cualquier servidor estático:

```bash
python3 -m http.server 8000
```

## Estructura

- `index.html` — página: hero, beneficios, catálogo, cómo funciona, FAQ, boletín, footer, carrito y checkout.
- `css/styles.css` — estilos y tokens de color.
- `js/data.js` — catálogo de productos (precios "desde" en MXN). Para editar productos, cambia solo este archivo.
- `js/app.js` — filtros, búsqueda, carrito (guardado en `localStorage`), modal de producto y checkout.
- `assets/` — logo (versión para fondo oscuro y claro), ícono y favicon.

## Pendiente para producción

- El checkout es un prototipo: no envía datos ni cobra. Hay que conectarlo a la pasarela de pago (tarjeta, SPEI, efectivo) y al envío de códigos.
- Confirmar precios y condiciones contra el catálogo vigente.
