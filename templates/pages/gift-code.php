<a class="back" href="/profile.php"><span data-icon="chevron-left"></span><span data-i18n="back"></span></a>
<section class="card gift-hero card-reveal">
  <div class="gift-ic"><span data-icon="gift" data-size="44"></span></div>
  <h2 data-i18n="gift_code"></h2>
  <p class="muted" data-i18n="gift_desc"></p>
  <form id="giftForm" class="gift-form" novalidate>
    <div class="input"><span data-icon="gift"></span><input name="code" data-i18n-ph="gift_code_ph" maxlength="20" autocomplete="off" style="text-transform:uppercase" required></div>
    <button class="btn btn-primary" type="submit"><span data-i18n="gift_claim"></span></button>
  </form>
  <div class="gift-success" id="giftSuccess" hidden>
    <span id="giftSuccessIc"></span>
    <b id="giftSuccessAmt"></b>
    <p class="muted" id="giftSuccessBal"></p>
  </div>
</section>
<section class="card card-reveal">
  <h3 style="margin-top:0" data-i18n="gift_history"></h3>
  <div class="scroll-x"><table class="tbl"><thead><tr><th data-i18n="gift_code"></th><th data-i18n="gift_bonus"></th><th data-i18n="time"></th></tr></thead><tbody id="giftRows"></tbody></table></div>
  <p class="muted" id="giftEmpty" hidden data-i18n="gift_empty"></p>
</section>
