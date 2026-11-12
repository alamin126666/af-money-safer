<a class="back" href="/dashboard.php" id="depBack"><span data-icon="chevron-left"></span><span data-i18n="back"></span></a>
<div class="page-head">
  <p class="eyebrow" data-i18n="deposit"></p>
  <h2 data-i18n="deposit_title"></h2>
  <p class="muted" data-i18n="deposit_hint"></p>
</div>
<ol class="stepper">
  <li class="st on" data-n="1"><i>1</i><span data-i18n="method"></span></li>
  <li class="st" data-n="2"><i>2</i><span data-i18n="amount"></span></li>
  <li class="st" data-n="3"><i>3</i><span data-i18n="done"></span></li>
</ol>
<section class="card balance-card-sm"><div class="label"><span data-icon="wallet"></span><span data-i18n="current_balance"></span></div><div class="amount-sm" id="balance">—</div></section>
<section class="card" id="formCard">
  <form id="f" novalidate>
    <div id="stepMethod">
      <div class="field"><span data-i18n="payment_method"></span>
        <div class="methods" id="methods">
          <button type="button" class="method sel" data-m="bKash" data-method="bKash">
            <span class="bkash-logo"><span class="brand-mark bkash-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26"><rect width="24" height="24" rx="6" fill="#E2136E"/><path d="M8.5 5.5v13M8.5 12.2h3.2a3.4 3.4 0 0 1 0 6.8H8.5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>bKash</span>
            <small class="m-sub">Send Money</small>
          </button>
          <button type="button" class="method" data-m="Nagad" data-method="Nagad">
            <span class="nagad-logo"><span class="brand-mark nagad-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26"><rect width="24" height="24" rx="6" fill="#F6921E"/><path d="M8 18.5v-13l8 13v-13" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Nagad</span>
            <small class="m-sub">Send Money</small>
          </button>
        </div></div>
      <button class="btn btn-primary btn-block" id="toAmount" type="button"><span data-i18n="continue"></span></button>
    </div>
    <div id="stepAmount" hidden>
      <label class="field"><span data-i18n="deposit_amount"></span>
        <div class="input big"><span class="cur">৳</span><input type="text" name="amount" inputmode="numeric" autocomplete="off" placeholder="0" maxlength="9" required></div></label>
      <div class="chips" id="quick"></div>
      <p class="limits muted" id="limits"></p>
      <div class="summary" id="summary" hidden></div>
      <div class="note"><span data-icon="shield"></span><span data-i18n="gateway_info"></span></div>
      <button class="btn btn-primary btn-block" id="submit" type="submit"><span data-i18n="deposit_btn"></span></button>
      <button class="btn btn-ghost btn-block" id="backMethod" type="button" style="margin-top:10px"><span data-i18n="back"></span></button>
    </div>
  </form>
</section>
<section class="card" id="statusCard" hidden></section>
