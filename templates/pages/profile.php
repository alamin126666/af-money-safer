<section class="card profile-head card-reveal">
  <div class="avatar-xl" id="avatar"></div>
  <h2 id="pName" class="skeleton-text">&nbsp;</h2><p class="muted" id="pEmail"></p>
  <span class="verified-badge"><span data-icon="shield-check"></span>Verified</span>
</section>
<section class="card info-list">
  <div class="row"><span class="muted" data-i18n="account_id"></span><b id="pId">—</b></div>
  <div class="row"><span class="muted" data-i18n="reg_date"></span><b id="pDate">—</b></div>
</section>
<section class="card">
  <h3 data-i18n="edit_profile"></h3>
  <div class="menu">
    <button class="menu-item" data-act="name"><span class="m-ic" data-icon="user"></span><span data-i18n="change_name"></span><span class="m-chev" data-icon="chevron"></span></button>
    <button class="menu-item" data-act="email"><span class="m-ic" data-icon="mail"></span><span data-i18n="change_email"></span><span class="m-chev" data-icon="chevron"></span></button>
    <button class="menu-item" data-act="password"><span class="m-ic" data-icon="lock"></span><span data-i18n="change_password"></span><span class="m-chev" data-icon="chevron"></span></button>
    <button class="menu-item" data-act="photo"><span class="m-ic" data-icon="camera"></span><span data-i18n="change_photo"></span><span class="m-chev" data-icon="chevron"></span></button>
    <button class="menu-item" data-act="avatar"><span class="m-ic" data-icon="user"></span><span data-i18n="choose_avatar"></span><span class="m-chev" data-icon="chevron"></span></button>
  </div>
  <input type="file" id="photoFile" accept="image/jpeg,image/png,image/webp" hidden>
</section>
<section class="card">
  <h3 data-i18n="preferences"></h3>
  <div class="pref"><span data-i18n="language"></span><div class="seg" id="segLang"><button data-v="bn">বাংলা</button><button data-v="en">English</button></div></div>
  <div class="pref"><span data-i18n="theme"></span><div class="seg" id="segTheme"><button data-v="light" data-i18n="light"></button><button data-v="dark" data-i18n="dark"></button></div></div>
</section>
<section class="card logout-card card-reveal">
  <button class="btn btn-danger btn-block" id="logout" type="button"><span data-icon="logout"></span><span data-i18n="logout"></span></button>
</section>
