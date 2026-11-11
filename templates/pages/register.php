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
    <div class="auth-head"><div data-logo="64"></div><h1 data-i18n="create_account"></h1><p class="muted" data-i18n="register_sub"></p></div>
    <form id="f" novalidate autocomplete="on">
      <label class="field"><span data-i18n="full_name"></span>
        <div class="input"><span data-icon="user"></span><input type="text" name="fullName" autocomplete="name" maxlength="60" required></div><small class="err" data-err="fullName"></small></label>
      <label class="field"><span data-i18n="email"></span>
        <div class="input"><span data-icon="mail"></span><input type="email" name="email" autocomplete="email" inputmode="email" required></div><small class="err" data-err="email"></small></label>
      <label class="field"><span data-i18n="password"></span>
        <div class="input"><span data-icon="lock"></span><input type="password" name="password" autocomplete="new-password" required>
          <button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div>
        <div class="pw-meter" data-s="0"><i></i></div><small class="pw-meter-label"></small>
        <small class="hint" data-i18n="password_rules"></small><small class="err" data-err="password"></small></label>
      <label class="field"><span data-i18n="confirm_password"></span>
        <div class="input"><span data-icon="lock"></span><input type="password" name="confirmPassword" autocomplete="new-password" required></div><small class="err" data-err="confirmPassword"></small></label>
      <div id="captcha" class="captcha"></div>
      <button class="btn btn-primary btn-block" id="submit" type="submit"><span data-i18n="register"></span></button>
    </form>
    <p class="auth-foot"><span class="muted" data-i18n="have_account"></span> <a href="/login.php" data-i18n="login"></a></p>
  </section>
</div>
