<a class="back" href="/dashboard.php"><span data-icon="chevron-left"></span><span data-i18n="back"></span></a>
<h2 data-i18n="withdraw_title"></h2>
<section class="card"><div class="label"><span data-icon="wallet"></span><span data-i18n="current_balance"></span></div><div class="amount-sm" id="balance">—</div></section>
<section class="card" id="formCard">
  <form id="f" novalidate>
    <div class="field"><span data-i18n="payment_method"></span>
      <div class="methods" id="methods">
        <button type="button" class="method sel" data-m="bKash"><i class="dot bk"></i>bKash</button>
        <button type="button" class="method" data-m="Nagad"><i class="dot ng"></i>Nagad</button></div></div>
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
