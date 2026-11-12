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
  <div class="card mini in card-reveal"><div class="label"><span data-icon="deposit"></span><span data-i18n="total_deposit"></span></div><div class="amount-sm" id="totalDeposit">—</div></div>
  <div class="card mini out card-reveal"><div class="label"><span data-icon="withdraw"></span><span data-i18n="total_withdraw"></span></div><div class="amount-sm" id="totalWithdraw">—</div></div>
</section>
<section class="card card-reveal" id="chartCard">
  <h3 style="margin-top:0" data-i18n="chart_title"></h3>
  <div class="chart-wrap"><canvas id="chartCv"></canvas></div>
  <div class="chart-legend"><span><i style="background:var(--ok)"></i><span data-i18n="t_deposit"></span></span><span><i style="background:var(--danger)"></i><span data-i18n="t_withdraw"></span></span></div>
  <p class="muted" id="chartEmpty" hidden data-i18n="chart_empty"></p>
</section>
<section class="card card-reveal" id="promoCard">
  <h3 style="margin-top:0" data-i18n="promo_title"></h3>
  <p class="muted" data-i18n="promo_desc"></p>
  <form id="promoForm" style="display:flex;gap:8px">
    <div class="input" style="flex:1"><input name="code" placeholder="CODE" maxlength="20" autocomplete="off" style="text-transform:uppercase"></div>
    <button class="btn btn-primary" type="submit"><span data-i18n="promo_redeem"></span></button>
  </form>
</section>
<section class="grid-2 actions quick-grid">
  <a class="action-card dep card-reveal" href="/deposit.php"><span class="action-ic" data-icon="deposit" data-size="28"></span><b data-i18n="deposit"></b><small data-i18n="deposit_hint"></small></a>
  <a class="action-card wd card-reveal" href="/withdraw.php"><span class="action-ic" data-icon="withdraw" data-size="28"></span><b data-i18n="withdraw"></b><small data-i18n="withdraw_hint"></small></a>
  <a class="action-card ntf card-reveal" href="/notifications.php"><span class="action-ic" data-icon="bell" data-size="28"></span><b data-i18n="notifications"></b></a>
</section>
