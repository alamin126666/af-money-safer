<a class="back" href="/dashboard.php"><span data-icon="chevron-left"></span><span data-i18n="back"></span></a>
<div class="page-head">
  <p class="eyebrow" data-i18n="withdraw"></p>
  <h2 data-i18n="withdraw_title"></h2>
  <p class="muted" data-i18n="withdraw_hint"></p>
</div>
<ol class="stepper">
  <li class="st on" data-n="1"><i>1</i><span data-i18n="amount"></span></li>
</ol>
<section class="card balance-card-sm"><div class="label"><span data-icon="wallet"></span><span data-i18n="current_balance"></span></div><div class="amount-sm" id="balance">—</div></section>
<section class="card" id="formCard">
  <form id="f" novalidate>
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
    <label class="field"><span data-i18n="amount"></span>
      <div class="input big"><span class="cur">৳</span><input type="text" name="amount" inputmode="numeric" autocomplete="off" placeholder="0" maxlength="9" required></div></label>
    <label class="field"><span data-i18n="wallet_number"></span>
      <div class="input"><span data-icon="wallet"></span><input type="tel" name="walletNo" inputmode="numeric" autocomplete="off" placeholder="01XXXXXXXXX" maxlength="14" required></div></label>
    <label class="field"><span data-i18n="holder_name"></span>
      <div class="input"><span data-icon="user"></span><input type="text" name="holderName" autocomplete="off" maxlength="60" required></div></label>
    <div class="summary" id="summary"></div>
    <p class="limits muted" id="limits"></p>
    <button class="btn btn-primary btn-block" id="submit" type="submit"><span data-i18n="withdraw_btn"></span></button>
  </form>
</section>
<section class="card" id="statusCard" hidden></section>
