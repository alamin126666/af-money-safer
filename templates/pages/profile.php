<section class="card profile-head">
  <div class="avatar-xl" id="avatar"></div>
  <h2 id="pName" class="skeleton-text">&nbsp;</h2><p class="muted" id="pEmail"></p>
</section>
<section class="card info-list">
  <div class="row"><span class="muted" data-i18n="account_id"></span><b id="pId">—</b></div>
  <div class="row"><span class="muted" data-i18n="reg_date"></span><b id="pDate">—</b></div>
</section>
<section class="card">
  <h3 data-i18n="edit_profile"></h3>
  <div class="menu">
    <button class="menu-item" data-act="name"><span data-icon="user"></span><span data-i18n="change_name"></span><span data-icon="chevron"></span></button>
    <button class="menu-item" data-act="email"><span data-icon="mail"></span><span data-i18n="change_email"></span><span data-icon="chevron"></span></button>
    <button class="menu-item" data-act="password"><span data-icon="lock"></span><span data-i18n="change_password"></span><span data-icon="chevron"></span></button>
    <button class="menu-item" data-act="photo"><span data-icon="camera"></span><span data-i18n="change_photo"></span><span data-icon="chevron"></span></button>
    <button class="menu-item" data-act="avatar"><span data-icon="user"></span><span data-i18n="choose_avatar"></span><span data-icon="chevron"></span></button>
  </div>
  <input type="file" id="photoFile" accept="image/jpeg,image/png,image/webp" hidden>
</section>
<section class="card">
  <h3 data-i18n="preferences"></h3>
  <div class="pref"><span data-i18n="language"></span><div class="seg" id="segLang"><button data-v="bn">বাংলা</button><button data-v="en">English</button></div></div>
  <div class="pref"><span data-i18n="theme"></span><div class="seg" id="segTheme"><button data-v="light" data-i18n="light"></button><button data-v="dark" data-i18n="dark"></button></div></div>
</section>
<!-- Sticky bottom actions — always visible -->
<div class="profile-actions">
  <button class="btn btn-danger" id="logout" type="button"><span data-icon="logout"></span><span data-i18n="logout"></span></button>
</div>
