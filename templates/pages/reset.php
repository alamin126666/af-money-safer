<div class="auth-layout">
  <aside class="brand-panel">
    <div class="brand-panel-inner">
      <div data-logo="64"></div>
      <h2>AF MONEY SAFER</h2>
      <p class="muted" data-i18n="splash_tag"></p>
      <div class="brand-stats">
        <div class="brand-stat"><b>100%</b><span>Secure</span></div>
        <div class="brand-stat"><b>24/7</b><span>Available</span></div>
        <div class="brand-stat"><b>2</b><span>Methods</span></div>
      </div>
      <p class="brand-trust"><span data-icon="shield"></span><span data-i18n="splash_tag"></span></p>
    </div>
  </aside>
  <section class="auth-card">
    <div class="auth-head"><div class="ring" data-icon="lock" data-size="34"></div><h1 data-i18n="reset_title"></h1><p class="muted" data-i18n="reset_desc"></p></div>
    <ol class="steps">
      <li class="done"><i>1</i><span data-i18n="verify"></span></li>
      <li class="on"><i>2</i><span data-i18n="set_password"></span></li>
    </ol>
    <form id="f" novalidate>
      <label class="field"><span data-i18n="new_password"></span>
        <div class="input"><span data-icon="lock"></span><input type="password" name="newPassword" autocomplete="new-password" required>
          <button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div>
        <div class="pw-meter" data-s="0"><i></i></div><small class="pw-meter-label"></small>
        <small class="hint" data-i18n="password_rules"></small><small class="err" data-err="newPassword"></small></label>
      <label class="field"><span data-i18n="confirm_password"></span>
        <div class="input"><span data-icon="lock"></span><input type="password" name="confirmPassword" autocomplete="new-password" required></div><small class="err" data-err="confirmPassword"></small></label>
      <button class="btn btn-primary btn-block" id="submit" type="submit"><span data-i18n="set_password"></span></button>
    </form>
  </section>
</div>
