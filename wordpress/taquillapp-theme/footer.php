<?php
/**
 * Generado por tools/build-wp-theme.py desde index.html. No editar a mano.
 */
?>
<!-- ============ FOOTER ============ -->
<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <div class="footer__brand">
        <img src="<?php echo esc_url( tq_asset( 'img/' ) ); ?>logo-light.png" alt="TaquillApp" width="190" height="38">
        <p>Cine con descuento, en pocos pasos.</p>
      </div>
      <div class="footer__cols">
        <div>
          <h4>Comprar</h4>
          <a href="<?php echo esc_url( tq_anchor( 'catalogo' ) ); ?>" data-quick="boleto">Boletos</a>
          <a href="<?php echo esc_url( tq_anchor( 'catalogo' ) ); ?>" data-quick="combo">Combos</a>
          <a href="<?php echo esc_url( tq_anchor( 'catalogo' ) ); ?>" data-quick="paquete">Paquetes</a>
        </div>
        <div>
          <h4>Ayuda</h4>
          <a href="<?php echo esc_url( tq_anchor( 'como-funciona' ) ); ?>">Cómo funciona</a>
          <a href="<?php echo esc_url( tq_anchor( 'faq' ) ); ?>">Preguntas frecuentes</a>
          <a href="mailto:<?php echo esc_attr( tq_contact_email() ); ?>">Contacto</a>
        </div>
        <div>
          <h4>Legal</h4>
          <a href="<?php echo esc_url( tq_page_url( 'terminos-y-condiciones' ) ); ?>">Términos y condiciones</a>
          <a href="<?php echo esc_url( tq_page_url( 'aviso-de-privacidad' ) ); ?>">Aviso de privacidad</a>
          <a href="<?php echo esc_url( tq_page_url( 'politica-de-reembolsos' ) ); ?>">Política de reembolsos</a>
        </div>
      </div>
    </div>
    <div class="footer__big" aria-hidden="true"><img src="<?php echo esc_url( tq_asset( 'img/' ) ); ?>logo-light.png" alt="" width="1576" height="318" loading="lazy"></div>
    <div class="footer__bottom">
      <span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> TaquillApp · taquillapp.com.mx</span>
      <span>TaquillApp es una plataforma independiente. Cinépolis y Cinemex son marcas de sus respectivos titulares.</span>
    </div>
  </div>
</footer>

<!-- ============ PRODUCT MODAL ============ -->
<div class="overlay" id="overlay"></div>

<dialog class="modal" id="productModal" aria-labelledby="pmTitle">
  <button class="icon-btn modal__close" data-close aria-label="Cerrar"><svg><use href="#i-close"/></svg></button>
  <div class="pm" id="pmBody"></div>
</dialog>

<!-- ============ CART DRAWER ============ -->
<aside class="drawer" id="drawer" aria-label="Carrito" aria-hidden="true">
  <div class="drawer__head">
    <h3>Tu carrito</h3>
    <button class="icon-btn" id="cartClose" aria-label="Cerrar carrito"><svg><use href="#i-close"/></svg></button>
  </div>
  <div class="drawer__body" id="cartItems"></div>
  <div class="drawer__foot" id="cartFoot">
    <div class="sum-row"><span>Subtotal</span><b id="cartSubtotal">$0</b></div>
    <div class="sum-row sum-row--muted"><span>Cargo por servicio</span><b>$0</b></div>
    <button class="btn btn--grad btn--block" id="checkoutBtn">Pagar ahora <svg><use href="#i-arrow"/></svg></button>
    <p class="drawer__hint"><svg><use href="#i-shield"/></svg> Pago protegido · Código por correo al instante</p>
  </div>
</aside>

<!-- ============ CHECKOUT ============ -->
<dialog class="modal modal--wide" id="checkoutModal" aria-labelledby="coTitle">
  <button class="icon-btn modal__close" data-close aria-label="Cerrar"><svg><use href="#i-close"/></svg></button>
  <div class="co">
    <div class="co__main">
      <ol class="co__steps" id="coSteps">
        <li class="is-active"><span>1</span>Tus datos</li>
        <li><span>2</span>Pago</li>
        <li><span>3</span>Listo</li>
      </ol>

      <form class="co__panel is-active" id="coStep1" novalidate>
        <h3 id="coTitle">¿A dónde te enviamos tus códigos?</h3>
        <div class="field-row">
          <label class="field"><span>Nombre</span><input name="name" required autocomplete="given-name" placeholder="Ana"></label>
          <label class="field"><span>Apellido</span><input name="last" required autocomplete="family-name" placeholder="López"></label>
        </div>
        <label class="field"><span>Correo electrónico</span><input name="email" type="email" required autocomplete="email" placeholder="ana@correo.com"></label>
        <label class="field"><span>Celular (opcional)</span><input name="phone" type="tel" autocomplete="tel" placeholder="55 1234 5678"></label>
        <label class="check"><input type="checkbox" required> <span>Acepto los términos y condiciones y el aviso de privacidad.</span></label>
        <button class="btn btn--grad btn--block" type="submit">Continuar al pago</button>
      </form>

      <form class="co__panel" id="coStep2" novalidate>
        <h3>Elige cómo pagar</h3>
        <div class="paymethods" role="radiogroup" aria-label="Método de pago">
          <label class="paym"><input type="radio" name="method" value="card" checked><span><b>Tarjeta</b><small>Crédito o débito</small></span></label>
          <label class="paym"><input type="radio" name="method" value="paypal"><span><b>PayPal</b><small>Tu cuenta PayPal</small></span></label>
          <label class="paym"><input type="radio" name="method" value="spei"><span><b>SPEI</b><small>Transferencia</small></span></label>
          <label class="paym"><input type="radio" name="method" value="store"><span><b>Efectivo</b><small>En tienda</small></span></label>
        </div>

        <!-- Tarjeta: los datos se tokenizan con OpenPay.js en el navegador; nunca llegan al servidor. -->
        <div class="paybox" data-method="card" id="openpayCardForm">
          <label class="field"><span>Nombre en la tarjeta</span><input autocomplete="cc-name" placeholder="Como aparece en la tarjeta" data-openpay-card="holder_name"></label>
          <label class="field"><span>Número de tarjeta</span><input inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" maxlength="19" data-cc data-openpay-card="card_number"></label>
          <div class="field-row">
            <label class="field"><span>Vencimiento</span><input autocomplete="cc-exp" placeholder="MM/AA" maxlength="5" data-exp></label>
            <label class="field"><span>CVV</span><input inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" data-openpay-card="cvv2"></label>
          </div>
          <input type="hidden" id="deviceSessionId">
          <p class="paysecure"><svg><use href="#i-shield"/></svg> Pago procesado por OpenPay. Tus datos de tarjeta no se guardan en TaquillApp.</p>
        </div>
        <div class="paybox" data-method="paypal" hidden>
          <p class="paynote">Paga con tu saldo o tarjetas guardadas en PayPal. Al aprobar el pago, tus códigos se generan al instante.</p>
          <div id="paypalButtons" class="paypal-slot"></div>
        </div>
        <div class="paybox" data-method="spei" hidden>
          <p class="paynote">Al confirmar te damos una CLABE única para transferir desde tu banca en línea. Tus códigos se envían en cuanto se refleje el pago.</p>
        </div>
        <div class="paybox" data-method="store" hidden>
          <p class="paynote">Generamos una referencia para pagar en efectivo en tiendas de conveniencia participantes. Tus códigos se envían al confirmarse el pago.</p>
        </div>
        <p class="co__error" id="coError" role="alert" hidden></p>
        <div class="co__actions">
          <button class="btn btn--ghost-dark" type="button" id="coBack">Atrás</button>
          <button class="btn btn--grad" type="submit" id="coPay">Pagar</button>
        </div>
      </form>

      <div class="co__panel co__done" id="coStep3">
        <div class="done__badge" id="doneBadge"><svg><use href="#i-check"/></svg></div>
        <h3 id="doneTitle">¡Listo! Tus códigos van en camino.</h3>
        <p id="doneMsg">Te los enviamos por correo.</p>
        <div class="done__codes" id="doneCodes"></div>
        <button class="btn btn--grad" data-close>Seguir comprando</button>
      </div>
    </div>

    <aside class="co__summary">
      <h4>Resumen</h4>
      <div id="coSummary"></div>
      <div class="sum-row sum-row--total"><span>Total</span><b id="coTotal">$0</b></div>
    </aside>
  </div>
</dialog>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>
