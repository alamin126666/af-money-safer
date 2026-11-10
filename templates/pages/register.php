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
      <small class="hint" data-i18n="password_rules"></small><small class="err" data-err="password"></small></label>
    <label class="field"><span data-i18n="confirm_password"></span>
      <div class="input"><span data-icon="lock"></span><input type="password" name="confirmPassword" autocomplete="new-password" required></div><small class="err" data-err="confirmPassword"></small></label>
    <div id="captcha" class="captcha"></div>
    <button class="btn btn-primary btn-block" id="submit" type="submit"><span data-i18n="register"></span></button>
  </form>
  <p class="auth-foot"><span class="muted" data-i18n="have_account"></span> <a href="/login.php" data-i18n="login"></a></p>
</section>
