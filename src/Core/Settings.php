<?php
declare(strict_types=1);

namespace App\Core;

/** Runtime settings: config defaults overridden by Firestore settings/app (edited in the admin panel). Cached 15 s. */
final class Settings
{
    private static ?array $mem = null;
    private static ?array $override = null;

    public static function defaults(): array
    {
        $l = Config::get('limits');
        return [
            'minDeposit' => (int)$l['min_deposit'], 'maxDeposit' => (int)$l['max_deposit'],
            'minWithdraw' => (int)$l['min_withdraw'], 'maxWithdraw' => (int)$l['max_withdraw'],
            'depositFeePercent' => (float)$l['deposit_fee_percent'], 'withdrawFeePercent' => (float)$l['withdraw_fee_percent'],
            'maintenance' => (bool)Config::get('app.maintenance'),
            'maintenanceReason' => '', 'maintenanceUntilMs' => 0,
        ];
    }

    public static function all(): array
    {
        if (self::$override !== null) { return self::$override + self::defaults(); }
        if (self::$mem !== null) { return self::$mem; }
        $file = sys_get_temp_dir() . '/afms_settings.json';
        if (is_file($file) && time() - (int)filemtime($file) < 15) {
            $j = json_decode((string)file_get_contents($file), true);
            if (is_array($j)) { return self::$mem = $j; }
        }
        $d = self::defaults();
        try {
            $s = Firebase::db()->collection('settings')->document('app')->snapshot();
            if ($s->exists()) {
                $fs = array_intersect_key($s->data(), $d);
                $env = $d['maintenance'];
                $d = array_replace($d, $fs);
                $d['maintenance'] = $env || !empty($fs['maintenance']);   // env flag can force maintenance on
                // Countdown expired: maintenance ends by itself (env-forced stays on).
                if ($d['maintenance'] && !$env && (int)($d['maintenanceUntilMs'] ?? 0) > 0 && (int)$d['maintenanceUntilMs'] <= time() * 1000) {
                    $d['maintenance'] = false;
                }
            }
        } catch (\Throwable $e) {
            Logger::error('settings.load_failed', ['e' => $e->getMessage()]);
        }
        $d = [
            'minDeposit' => (int)$d['minDeposit'], 'maxDeposit' => (int)$d['maxDeposit'],
            'minWithdraw' => max(100, (int)$d['minWithdraw']), 'maxWithdraw' => (int)$d['maxWithdraw'],   // TashPay: withdraw >= 100
            'depositFeePercent' => (float)$d['depositFeePercent'], 'withdrawFeePercent' => (float)$d['withdrawFeePercent'],
            'maintenance' => (bool)$d['maintenance'],
            'maintenanceReason' => (string)($d['maintenanceReason'] ?? ''), 'maintenanceUntilMs' => (int)($d['maintenanceUntilMs'] ?? 0),
        ];
        @file_put_contents($file, json_encode($d), LOCK_EX);
        return self::$mem = $d;
    }

    public static function get(string $key): mixed { return self::all()[$key] ?? null; }

    public static function save(array $changes): void
    {
        Firebase::db()->collection('settings')->document('app')->set($changes, ['merge' => true]);
        @unlink(sys_get_temp_dir() . '/afms_settings.json');
        self::$mem = null;
    }

    /** Test hook. */
    public static function useArray(?array $a): void { self::$override = $a; self::$mem = null; }
}
