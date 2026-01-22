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

        // Получаем middleware из конфигурации
        $middleware = config('sfiles.auth.middleware', 'auth');

        // Если указан кастомный middleware (не стандартный 'auth' и не 'sfiles.auth')
        if ($middleware && $middleware !== 'sfiles.auth' && $middleware !== 'auth') {
            // Пытаемся применить кастомный middleware
            try {
                $middlewareInstance = null;
                
                // Пытаемся получить middleware через router (если это алиас)
                $router = app('router');
                if (method_exists($router, 'getMiddleware')) {
                    $middlewareAliases = $router->getMiddleware();
                    if (isset($middlewareAliases[$middleware])) {
                        $middlewareClass = $middlewareAliases[$middleware];
                        if (is_string($middlewareClass) && class_exists($middlewareClass)) {
                            $middlewareInstance = app($middlewareClass);
                        }
                    }
                }
                
                // Если не получилось через алиас, пытаемся как класс
                if (!$middlewareInstance && class_exists($middleware)) {
                    $middlewareInstance = app($middleware);
                }
                
                // Если получили экземпляр middleware, применяем его
                if ($middlewareInstance && method_exists($middlewareInstance, 'handle')) {
                    return $middlewareInstance->handle($request, $next);
                }
                
                // Если middleware не был применен, логируем предупреждение
                if (config('sfiles.logging.enabled', true)) {
                    \Illuminate\Support\Facades\Log::warning(
                        "Custom middleware '{$middleware}' was not applied. Falling back to Auth::check()."
                    );
                }
            } catch (\Exception $e) {
                // Если произошла ошибка при применении кастомного middleware,
                // логируем и используем стандартную проверку
                if (config('sfiles.logging.enabled', true)) {
                    \Illuminate\Support\Facades\Log::warning(
                        "Failed to apply custom middleware '{$middleware}': " . $e->getMessage()
                    );
                }
            }
        }

        // Стандартная проверка авторизации (если middleware = 'auth' или не указан, или не удалось применить кастомный)
        if (!Auth::check()) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
