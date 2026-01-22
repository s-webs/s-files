<?php

namespace Swebs\Sfiles\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FileManagerAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Если авторизация отключена в конфигурации - пропускаем
        if (!config('sfiles.auth.enabled', false)) {
            return $next($request);
        }

        // Проверяем авторизацию пользователя
        if (!Auth::check()) {
            abort(401, 'Unauthorized');
        }

        // Дополнительная проверка через middleware, если указано
        $middleware = config('sfiles.auth.middleware');
        if ($middleware && $middleware !== 'sfiles.auth') {
            // Применяем указанный middleware через router
            return app('router')->middleware($middleware)->handle($request, $next);
        }

        return $next($request);
    }
}
