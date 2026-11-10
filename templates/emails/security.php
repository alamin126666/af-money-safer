<?php use App\Mail\Mailer;
$d = dirname(__FILE__);
require $d . '/_layout_start.php'; ?>
<p style="margin:0;font-size:15px;line-height:1.6;"><?= Mailer::esc($intro) ?></p>
<?php require $d . '/_layout_end.php'; ?>
