<?php

namespace Swebs\Sfiles\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FileManagerRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'general'): Response
    {
        $config = config('sfiles.rate_limit', []);
        $limit = $config[$type] ?? $config['general'] ?? 60;
        
        // Для загрузки файлов используем более длинное окно (2 минуты) для большей гибкости
        $decayMinutes = $type === 'upload' ? 2 : 1;
        
        $key = $this->resolveRequestSignature($request, $type);

        try {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                $seconds = RateLimiter::availableIn($key);
                
                throw ValidationException::withMessages([
                    'rate_limit' => "Слишком много запросов. Попробуйте снова через {$seconds} секунд.",
                ])->status(429);
            }

            // Пытаемся обновить счетчик rate limit
            // При параллельных запросах может возникнуть блокировка базы данных
            try {
                RateLimiter::hit($key, $decayMinutes * 60); // 1-2 minute window
            } catch (\Throwable $e) {
                // Если произошла блокировка базы данных при параллельных запросах,
                // пропускаем rate limiting для этого запроса, чтобы не блокировать загрузку
                if (str_contains($e->getMessage(), 'locked') || 
                    str_contains($e->getMessage(), 'database is locked') ||
                    str_contains($e->getMessage(), 'SQLSTATE[HY000]')) {
                    // Логируем предупреждение, но продолжаем выполнение запроса
                    if (config('sfiles.logging.enabled', true)) {
                        \Illuminate\Support\Facades\Log::warning(
                            "RateLimiter database lock for key {$key}: " . $e->getMessage()
                        );
                    }
                    // Продолжаем выполнение запроса без rate limiting
                } else {
                    // Для других ошибок пробрасываем исключение
                    throw $e;
                }
            }
        } catch (ValidationException $e) {
            // Пробрасываем ValidationException дальше
            throw $e;
        } catch (\Throwable $e) {
            // Для критических ошибок логируем и продолжаем выполнение
            if (config('sfiles.logging.enabled', true)) {
                \Illuminate\Support\Facades\Log::error(
                    "RateLimiter error for key {$key}: " . $e->getMessage()
                );
            }
            // Продолжаем выполнение запроса без rate limiting при ошибках
        }

        return $next($request);
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $user = $request->user();
        $identifier = $user ? $user->id : $request->ip();
        
        return 'sfiles:' . $type . ':' . $identifier;
    }
}
