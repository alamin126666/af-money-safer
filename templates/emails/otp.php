<?php use App\Mail\Mailer;
$d = dirname(__FILE__);
require $d . '/_layout_start.php'; ?>
<p style="margin:0 0 18px;font-size:15px;line-height:1.6;"><?= Mailer::esc($intro) ?></p>
<div style="text-align:center;margin:18px 0;padding:18px;background:#eef6f2;border-radius:12px;font-size:34px;font-weight:bold;letter-spacing:10px;color:#0b3d2e;"><?= Mailer::esc($code) ?></div>
<p style="margin:0;font-size:14px;color:#44505e;"><?= Mailer::esc($expires) ?></p>
<?php require $d . '/_layout_end.php'; ?>
