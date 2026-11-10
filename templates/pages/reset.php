<section class="auth-card">
  <div class="auth-head"><div class="ring" data-icon="lock" data-size="34"></div><h1 data-i18n="reset_title"></h1><p class="muted" data-i18n="reset_desc"></p></div>
  <form id="f" novalidate>
    <label class="field"><span data-i18n="new_password"></span>
      <div class="input"><span data-icon="lock"></span><input type="password" name="newPassword" autocomplete="new-password" required>
        <button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div>
      <small class="hint" data-i18n="password_rules"></small><small class="err" data-err="newPassword"></small></label>
    <label class="field"><span data-i18n="confirm_password"></span>
      <div class="input"><span data-icon="lock"></span><input type="password" name="confirmPassword" autocomplete="new-password" required></div><small class="err" data-err="confirmPassword"></small></label>
    <button class="btn btn-primary btn-block" id="submit" type="submit"><span data-i18n="set_password"></span></button>
  </form>
</section>
