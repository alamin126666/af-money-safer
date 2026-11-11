<section class="hello"><p class="muted" data-i18n="hello"></p><h2 id="userName" class="skeleton-text">&nbsp;</h2></section>
<section class="card balance-card card-reveal">
  <div class="bc-circle2"></div>
  <div class="label"><span data-icon="wallet"></span><span data-i18n="now_balance"></span></div>
  <div class="amount" id="balance"><span class="skeleton">&nbsp;</span></div>
  <div class="sub muted"><span data-i18n="bdt_label"></span></div>
</section>
<section class="grid-2">
  <div class="card mini in card-reveal"><div class="label"><span data-icon="deposit"></span><span data-i18n="total_deposit"></span></div><div class="amount-sm" id="totalDeposit">—</div></div>
  <div class="card mini out card-reveal"><div class="label"><span data-icon="withdraw"></span><span data-i18n="total_withdraw"></span></div><div class="amount-sm" id="totalWithdraw">—</div></div>
</section>
<section class="grid-2 actions">
  <a class="action-card dep card-reveal" href="/deposit.php"><span class="action-ic" data-icon="deposit" data-size="28"></span><b data-i18n="deposit"></b><small data-i18n="deposit_hint"></small></a>
  <a class="action-card wd card-reveal" href="/withdraw.php"><span class="action-ic" data-icon="withdraw" data-size="28"></span><b data-i18n="withdraw"></b><small data-i18n="withdraw_hint"></small></a>
</section>
<section class="card card-reveal">
  <div class="card-title"><h3 data-i18n="history"></h3><a href="/history.php" class="link" data-i18n="view_all"></a></div>
  <div id="history"></div>
</section>
