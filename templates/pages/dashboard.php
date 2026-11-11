<section class="hello card-reveal">
  <span class="hello-avatar" id="helloAvatar" data-icon="user"></span>
  <div class="hello-text"><p class="muted" data-i18n="hello"></p><h2 id="userName" class="skeleton-text">&nbsp;</h2></div>
</section>
<section class="card balance-card card-reveal">
  <div class="bc-circle2"></div>
  <div class="bc-coin" aria-hidden="true"><span data-icon="wallet" data-size="28"></span></div>
  <div class="balance-top">
    <div class="label"><span data-icon="wallet"></span><span data-i18n="now_balance"></span></div>
    <button class="icon-btn bal-eye" id="balToggle" type="button" aria-label="Show/Hide balance"><span data-icon="eye"></span></button>
  </div>
  <div class="amount" id="balance"><span class="skeleton">&nbsp;</span></div>
  <div class="sub muted"><span data-i18n="bdt_label"></span></div>
</section>
<section class="grid-2">
  <div class="card mini in card-reveal"><div class="label"><span data-icon="deposit"></span><span data-i18n="total_deposit"></span></div><div class="amount-sm" id="totalDeposit">—</div><span class="trend" aria-hidden="true"><span data-icon="deposit" data-size="16"></span></span></div>
  <div class="card mini out card-reveal"><div class="label"><span data-icon="withdraw"></span><span data-i18n="total_withdraw"></span></div><div class="amount-sm" id="totalWithdraw">—</div><span class="trend" aria-hidden="true"><span data-icon="withdraw" data-size="16"></span></span></div>
</section>
<section class="grid-2 actions quick-grid">
  <a class="action-card dep card-reveal" href="/deposit.php"><span class="action-ic" data-icon="deposit" data-size="28"></span><b data-i18n="deposit"></b><small data-i18n="deposit_hint"></small></a>
  <a class="action-card wd card-reveal" href="/withdraw.php"><span class="action-ic" data-icon="withdraw" data-size="28"></span><b data-i18n="withdraw"></b><small data-i18n="withdraw_hint"></small></a>
  <a class="action-card ntf card-reveal" href="/notifications.php"><span class="action-ic" data-icon="bell" data-size="28"></span><b data-i18n="notifications"></b></a>
</section>
