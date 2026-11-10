<?php
declare(strict_types=1);

namespace App\Core;

final class Api
{
    /**
     * @param callable(?array):array{0:string,1:array} $fn  returns [successCode, data]
     * @param array{methods?:string[],csrf?:bool,auth?:bool,bypassMaintenance?:bool} $opts
     */
    public static function handle(callable $fn, array $opts = []): never
    {
        $o = $opts + ['methods' => ['POST'], 'csrf' => true, 'auth' => false, 'bypassMaintenance' => false];
        try {
            if (!in_array(Request::method(), $o['methods'], true)) {
                throw new ApiException('METHOD_NOT_ALLOWED', 405);
            }
            Session::start();
            Lang::init();
            if (!$o['bypassMaintenance'] && \App\Core\Settings::get('maintenance') && !\App\Services\AdminGuard::isAdminSession()) {
                throw new ApiException('MAINTENANCE', 503);
            }
            if ($o['csrf'] && Request::method() !== 'GET') {
                Csrf::verify();
            }
            $user = $o['auth'] ? \App\Services\AuthService::requireUser() : null;
            [$code, $data] = $fn($user);
            Response::send(true, $code, $data);
        } catch (ApiException $e) {
            Response::send(false, $e->errorCode, $e->data, $e->status);
        } catch (\Throwable $e) {
            Logger::error('api.unhandled', ['e' => get_class($e), 'm' => $e->getMessage(), 'at' => basename($e->getFile()) . ':' . $e->getLine()]);
            Response::send(false, 'INTERNAL_ERROR', [], 500);
        }
    }
}
