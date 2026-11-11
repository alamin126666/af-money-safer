<?php /** @var string $lang,$appName,$title */ use App\Mail\Mailer; ?>
<!DOCTYPE html>
<html lang="<?= Mailer::esc($lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#e9f2ec;font-family:Arial,Helvetica,sans-serif;color:#1b2430;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e9f2ec;padding:24px 12px;"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(11,61,46,0.12);">
<tr><td style="background:#0b3d2e;padding:24px 28px;color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:1px;border-bottom:4px solid #d4af37;"><?= Mailer::esc($appName) ?></td></tr>
<tr><td style="padding:28px;">
<h1 style="margin:0 0 10px;font-size:20px;color:#0b3d2e;"><?= Mailer::esc($title) ?></h1>
<div style="width:48px;height:3px;background:#d4af37;border-radius:2px;margin:0 0 18px;"></div>
