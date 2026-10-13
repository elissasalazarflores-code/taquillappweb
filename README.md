# TaquillApp — sitio web y plantilla para WordPress

Sitio e-commerce de TaquillApp: boletos, combos y paquetes de Cinépolis y Cinemex con más de 50% de descuento.

El repositorio tiene dos versiones del mismo diseño:

| Versión | Para qué sirve | Pagos |
|---|---|---|
| **Sitio estático** (`index.html`, `css/`, `js/`, `assets/`) | Vista previa pública en GitHub Pages | Demostración: no cobra |
| **WordPress** (`wordpress/`) | Sitio real | Tarjeta de crédito o débito (OpenPay), PayPal y códigos oficiales vía RedPass |

```
index.html, css/, js/, assets/      Diseño (fuente única)
wordpress/taquillapp-theme/         Tema: diseño, carrito y checkout
wordpress/taquillapp-commerce/      Plugin: catálogo, pedidos, OpenPay, PayPal, RedPass, correos
tools/build-wp-theme.py             Genera el tema desde el sitio estático y crea los .zip
dist/                               taquillapp-theme.zip y taquillapp-commerce.zip, listos para subir
```

---

## 1. Instalar en WordPress

Requisitos: WordPress 6.2+, PHP 7.4+, HTTPS activo y envío de correo funcionando (se recomienda un plugin SMTP).

1. **Plugins → Añadir nuevo → Subir plugin** → `dist/taquillapp-commerce.zip` → Activar.
   Al activarse importa los 20 productos del catálogo.
2. **Apariencia → Temas → Añadir nuevo → Subir tema** → `dist/taquillapp-theme.zip` → Activar.
3. **Ajustes → Lectura**: deja "Tus últimas entradas" (el tema usa su propia portada).
4. **Ajustes → Enlaces permanentes**: elige "Nombre de la entrada" y guarda.
5. Crea las páginas legales con estos slugs (el footer las enlaza solo):
   `terminos-y-condiciones`, `aviso-de-privacidad`, `politica-de-reembolsos`.
6. **Apariencia → Personalizar → TaquillApp · Contacto**: correo de contacto.

Los productos se editan en **Productos** (precio, condiciones, etiqueta, ícono y SKU de RedPass).
Los pedidos aparecen en **Pedidos**, con su historial, códigos y acciones manuales.

---

## 2. Conectar los pagos

Todo se configura en **Ajustes → TaquillApp**. Empieza en **Modo: Pruebas (sandbox)**.

> **Recomendado:** guarda las llaves privadas en `wp-config.php` en lugar de la base de datos.
> Si existe la constante, el campo del panel queda bloqueado:
>
> ```php
> define( 'TQC_MODE', 'live' );                 // 'sandbox' o 'live'
> define( 'TQC_OPENPAY_MERCHANT_ID', 'm...' );
> define( 'TQC_OPENPAY_PUBLIC_KEY',  'pk_...' );
> define( 'TQC_OPENPAY_PRIVATE_KEY', 'sk_...' );
> define( 'TQC_OPENPAY_WEBHOOK_USER', '...' );
> define( 'TQC_OPENPAY_WEBHOOK_PASS', '...' );
> define( 'TQC_PAYPAL_CLIENT_ID', '...' );
> define( 'TQC_PAYPAL_SECRET',    '...' );
> define( 'TQC_REDPASS_BASE_URL', 'https://...' );
> define( 'TQC_REDPASS_API_KEY',  '...' );
> ```

### OpenPay (tarjeta de crédito y débito)

1. En el panel de OpenPay copia **ID de comercio**, **llave pública** y **llave privada** (las de sandbox para pruebas).
2. Pégalas en **Ajustes → TaquillApp → OpenPay**.
3. En OpenPay, **Configuración → Webhooks → Agregar**:
   - URL: la que muestra la página de ajustes (`https://TU-DOMINIO/wp-json/taquillapp/v1/webhooks/openpay`).
   - Usuario y contraseña: los mismos que pongas en "Usuario/Contraseña del webhook".
   - Eventos: los de cargos (`charge.succeeded`, `charge.cancelled`, `charge.failed`).
   - El webhook sirve para confirmar pagos con 3D Secure cuando el comprador no regresa al sitio.
   - OpenPay manda un código de verificación; aparece en la página de ajustes para copiarlo.
4. **3D Secure**: "Según reglas de OpenPay" es lo normal; "Siempre" pide validación del banco en cada compra.

Cómo funciona:
- **Tarjeta**: los datos se convierten en un token con OpenPay.js en el navegador; nunca pasan por WordPress. El cargo se hace en el servidor y, si se aprueba, se emiten los códigos al momento. Si el banco pide 3D Secure, el comprador regresa al sitio y ve su resultado.
- El webhook nunca se cree a ciegas: el plugin vuelve a consultar el cargo en OpenPay antes de marcar el pedido como pagado. Además, cada 15 minutos revisa los pagos con 3D Secure pendientes por si un webhook no llegó.

### PayPal

1. En [developer.paypal.com](https://developer.paypal.com) → **Apps & Credentials**, crea una app (Sandbox para pruebas, Live para producción).
2. Copia **Client ID** y **Secret** en **Ajustes → TaquillApp → PayPal**.
3. La cuenta de PayPal debe aceptar pagos en **MXN**.

El sitio muestra los botones oficiales de PayPal. La orden se crea y se cobra desde el servidor con el total del pedido, así que el monto no se puede alterar desde el navegador.

---

## 3. RedPass (códigos oficiales) — falta su documentación

Todavía no tenemos la documentación de la API de RedPass, así que **la conexión está preparada pero no confirmada**.
Todo lo de RedPass vive en un solo archivo: `wordpress/taquillapp-commerce/includes/class-tqc-redpass.php`.
Cuando RedPass entregue su API, solo hay que ajustar ese archivo; el resto (pedidos, reintentos, correos) ya está hecho.

Qué pedirle a RedPass:
- URL de la API (sandbox y producción) y forma de autenticación.
- Endpoint para emitir códigos: qué recibe y qué responde.
- Cómo evitar duplicados si una solicitud se reintenta (el plugin manda una `reference` única por línea de pedido).
- El identificador de cada producto en RedPass. Ese dato va en **Productos → SKU en RedPass**.
- Códigos de error y cómo consultar un código ya emitido.

Mientras tanto, en **modo sandbox y sin RedPass configurado**, el sistema genera códigos de ejemplo (`PRUEBA-XXXX`) para probar el flujo completo. En producción nunca se generan códigos falsos.

Si RedPass falla después de un pago, el pedido queda en **"Pagado · falta emitir códigos"**: se avisa por correo al administrador, se reintenta cada 15 minutos y se puede reintentar a mano desde el pedido. Los códigos ya emitidos nunca se vuelven a pedir.

---

## 4. Antes de salir a producción

- [ ] Llaves de producción de OpenPay y PayPal, y **Modo: Producción**.
- [ ] Webhook de OpenPay apuntando al dominio real, con usuario y contraseña.
- [ ] RedPass conectado y **SKU de RedPass** capturado en cada producto (la columna "SKU RedPass" marca los que faltan).
- [ ] Precios y condiciones revisados en **Productos**.
- [ ] Páginas legales publicadas.
- [ ] Correo saliente configurado (SMTP) y probado.
- [ ] Un cron real del servidor que llame a `wp-cron.php` cada 5 minutos, y `define( 'DISABLE_WP_CRON', true );` (WP-Cron solo corre con visitas).
- [ ] Si usas un plugin de caché, excluye `/wp-json/*` y no guardes en caché páginas para usuarios con sesión iniciada.
- [ ] Compra de prueba de punta a punta con cada método.

Tarjetas de prueba de OpenPay (sandbox): `4111 1111 1111 1111` (aprobada) y `4000 0000 0000 0002` (rechazada), con cualquier CVV y fecha futura. Lista completa en la documentación de OpenPay.

---

## 5. Cambiar el diseño

El sitio estático es la fuente del diseño. Para cambiar textos o estilos:

1. Edita `index.html`, `css/styles.css` o `js/*.js` y revisa el resultado abriendo `index.html`.
2. Ejecuta `python3 tools/build-wp-theme.py`. Regenera `header.php`, `front-page.php`, `footer.php` y `assets/` del tema, y los `.zip` de `dist/`.
3. Sube los cambios.

`functions.php`, `page.php`, `index.php` y `404.php` del tema se editan directamente.

Vista previa estática: `python3 -m http.server 8000` y abre http://localhost:8000.
