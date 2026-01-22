<?php

namespace SSystems\SFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class FileManagerController extends Controller
{
    private \Illuminate\Contracts\Filesystem\Filesystem $disk;
    private string $selectedDisk;
    private string $selectedDir;
    private string $basePath;

    public function __construct()
    {
        $this->selectedDisk = config('sfiles.disk', 'public');
        $this->selectedDir = config('sfiles.public_dir', 'uploads');
        $this->disk = Storage::disk($this->selectedDisk);
        
        // Создаем базовую директорию, если она не существует
        if (!$this->disk->exists($this->selectedDir)) {
            try {
                $this->disk->makeDirectory($this->selectedDir);
            } catch (\Exception $e) {
                // Логируем ошибку, но не прерываем выполнение
                Log::warning('Не удалось создать директорию ' . $this->selectedDir . ': ' . $e->getMessage());
            }
        }
        
        // Получаем реальный базовый путь для проверки безопасности
        try {
            $basePath = realpath($this->disk->path(''));
            if ($basePath === false) {
                $basePath = $this->disk->path('');
            }
            // Нормализуем путь (убираем обратные слэши, двойные слэши)
            $this->basePath = str_replace('\\', '/', rtrim($basePath, '/\\'));
        } catch (\Throwable $e) {
            $basePath = $this->disk->path('');
            $this->basePath = str_replace('\\', '/', rtrim($basePath, '/\\'));
        }
    }

    /**
     * Get current user ID for logging (optional)
     */
    private function getUserId(): ?int
    {
        if (!config('sfiles.auth.enabled', false)) {
            return null;
        }

        $user = Auth::user();
        return $user ? $user->id : null;
    }

    /**
     * Normalize and validate path with enhanced security
     * 
     * @param string|null $path Путь для нормализации
     * @param bool $checkExistence Проверять ли существование пути (false для создания новых)
     */
    private function normalizePath(?string $path, bool $checkExistence = true): string
    {
        $path = (string)($path ?? '');
        $path = trim($path);

        // Единый разделитель
        $path = str_replace('\\', '/', $path);

        // Убираем ведущие слэши
        $path = ltrim($path, '/');

        // Убираем двойные слэши
        $path = preg_replace('#/+#', '/', $path);

        // Блокируем traversal - проверяем только целые сегменты пути
        // Разбиваем путь на сегменты и проверяем каждый
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            // Пропускаем пустые сегменты
            if ($segment === '') {
                continue;
            }
            
            // Декодируем сегмент для проверки (на случай URL-encoded пути)
            $decodedSegment = urldecode($segment);
            
            // Проверяем точное совпадение (не подстроку!) - это безопаснее для кириллицы
            if ($decodedSegment === '..' || $decodedSegment === '.') {
                $this->logAction('security_violation', [
                    'attempted_path' => $path,
                    'segment' => $segment,
                    'decoded_segment' => $decodedSegment,
                    'user_id' => $this->getUserId(),
                ]);
                abort(422, 'Invalid path: path traversal detected');
            }
            
            // Также проверяем URL-encoded версии в исходном сегменте
            if (str_contains($segment, '%2e%2e') || str_contains($segment, '%2E%2E')) {
                $this->logAction('security_violation', [
                    'attempted_path' => $path,
                    'segment' => $segment,
                    'user_id' => $this->getUserId(),
                ]);
                abort(422, 'Invalid path: path traversal detected');
            }
        }
        
        // Декодируем весь путь после проверки безопасности (для дальнейшей обработки)
        $path = urldecode($path);

        // Проверка глубины пути
        $maxDepth = config('sfiles.security.max_path_depth', 20);
        $depth = substr_count($path, '/') + 1;
        if ($depth > $maxDepth) {
            abort(422, 'Path depth exceeds maximum allowed');
        }

        // Проверка реального пути (защита от симлинков)
        if ($path !== '') {
            try {
                if ($checkExistence) {
                    // Для существующих путей - проверяем реальный путь
                    $fullPath = $this->disk->path($path);
                    $realPath = realpath($fullPath);
                    
                    // На Windows с кириллицей realpath() может возвращать false даже для существующих директорий
                    // Используем fallback проверку через Storage
                    if ($realPath === false) {
                        // Проверяем существование через Storage (работает с кириллицей)
                        if (!$this->disk->directoryExists($path)) {
                            $this->logAction('security_violation', [
                                'attempted_path' => $path,
                                'full_path' => $fullPath,
                                'base_path' => $this->basePath,
                                'user_id' => $this->getUserId(),
                            ]);
                            abort(422, 'Invalid path: path does not exist');
                        }
                        
                        // Если директория существует через Storage, но realpath() вернул false,
                        // используем fallback проверку безопасности через file_exists() и базовый путь
                        if (file_exists($fullPath) && is_dir($fullPath)) {
                            $normalizedFullPath = str_replace('\\', '/', rtrim($fullPath, '/\\'));
                            $normalizedBasePath = str_replace('\\', '/', rtrim($this->basePath, '/\\'));
                            
                            // Для Windows учитываем регистр
                            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                $normalizedFullPath = strtolower($normalizedFullPath);
                                $normalizedBasePath = strtolower($normalizedBasePath);
                            }
                            
                            if (!str_starts_with($normalizedFullPath, $normalizedBasePath)) {
                                $this->logAction('security_violation', [
                                    'attempted_path' => $path,
                                    'full_path' => $fullPath,
                                    'base_path' => $this->basePath,
                                    'user_id' => $this->getUserId(),
                                ]);
                                abort(422, 'Invalid path: outside allowed directory');
                            }
                            
                            // Путь валиден, продолжаем
                            return $path;
                        } else {
                            $this->logAction('security_violation', [
                                'attempted_path' => $path,
                                'full_path' => $fullPath,
                                'base_path' => $this->basePath,
                                'user_id' => $this->getUserId(),
                            ]);
                            abort(422, 'Invalid path: path does not exist');
                        }
                    }
                    
                    // Нормализуем пути для сравнения
                    $normalizedRealPath = str_replace('\\', '/', rtrim($realPath, '/\\'));
                    $normalizedBasePath = str_replace('\\', '/', rtrim($this->basePath, '/\\'));
                    
                    if (!str_starts_with($normalizedRealPath, $normalizedBasePath)) {
                        $this->logAction('security_violation', [
                            'attempted_path' => $path,
                            'real_path' => $normalizedRealPath,
                            'base_path' => $normalizedBasePath,
                            'user_id' => $this->getUserId(),
                        ]);
                        abort(422, 'Invalid path: outside allowed directory');
                    }
                } else {
                    // Для несуществующих путей (при создании) - упрощенная проверка
                    // Проверяем только родительскую директорию
                    $parentPath = dirname($path);
                    if ($parentPath === '.' || $parentPath === '') {
                        $parentPath = '';
                    }
                    
                    // Если есть родительская директория - проверяем её существование
                    if ($parentPath !== '') {
                        // Нормализуем родительский путь (без рекурсии - просто базовые проверки)
                        $normalizedParent = trim($parentPath);
                        $normalizedParent = str_replace('\\', '/', $normalizedParent);
                        $normalizedParent = ltrim($normalizedParent, '/');
                        $normalizedParent = preg_replace('#/+#', '/', $normalizedParent);
                        
                        // Проверяем, что родительская директория существует
                        if (!$this->disk->directoryExists($normalizedParent)) {
                            abort(422, 'Invalid path: parent directory does not exist');
                        }
                        
                        // Дополнительная проверка безопасности родительской директории (опционально)
                        // Если Storage уже проверил существование - это достаточно безопасно
                        // Но для дополнительной защиты проверяем через realpath
                        try {
                            $parentFullPath = $this->disk->path($normalizedParent);
                            if (file_exists($parentFullPath)) {
                                $parentRealPath = realpath($parentFullPath);
                                
                                if ($parentRealPath !== false && $this->basePath !== '') {
                                    $normalizedParentPath = str_replace('\\', '/', rtrim($parentRealPath, '/\\'));
                                    $normalizedBasePath = str_replace('\\', '/', rtrim($this->basePath, '/\\'));
                                    
                                    // Для Windows учитываем регистр
                                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                        $normalizedParentPath = strtolower($normalizedParentPath);
                                        $normalizedBasePath = strtolower($normalizedBasePath);
                                    }
                                    
                                    if (!str_starts_with($normalizedParentPath, $normalizedBasePath)) {
                                        // Логируем, но не блокируем - Storage уже проверил существование
                                        $this->logAction('security_warning', [
                                            'attempted_path' => $path,
                                            'parent_path' => $normalizedParent,
                                            'parent_real_path' => $normalizedParentPath,
                                            'base_path' => $normalizedBasePath,
                                        ]);
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            // Если не можем проверить - разрешаем (родитель существует в Storage)
                        }
                    }
                    // Для корневой директории (parentPath === '') - путь уже валиден,
                    // так как мы проверили отсутствие traversal и глубину
                }
            } catch (\Throwable $e) {
                // Если не можем проверить - блокируем только если это не ожидаемая ошибка
                if ($checkExistence || str_contains($e->getMessage(), 'outside allowed')) {
                    abort(422, 'Invalid path: ' . $e->getMessage());
                }
            }
        }

        return $path;
    }

    /**
     * Log file manager actions
     */
    private function logAction(string $action, array $data = []): void
    {
        if (!config('sfiles.logging.enabled', true)) {
            return;
        }

        $channel = config('sfiles.logging.channel', 'daily');
        
        Log::channel($channel)->info("SFiles: {$action}", array_merge([
            'user_id' => $this->getUserId(),
            'ip' => request()->ip(),
        ], $data));
    }

    /**
     * Получить полный путь на диске с учетом public_dir
     * Преобразует относительный путь клиента в полный путь на диске
     * Автоматически убирает префикс public_dir, если он присутствует
     */
    private function getWorkingPath(?string $relativePath = null): string
    {
        $relativePath = $relativePath ? $this->normalizePath($relativePath, false) : '';
        
        // Убираем префикс public_dir, если путь начинается с него
        $prefix = rtrim($this->selectedDir, '/') . '/';
        if (str_starts_with($relativePath, $prefix)) {
            $relativePath = substr($relativePath, strlen($prefix));
        } elseif ($relativePath === $this->selectedDir) {
            $relativePath = '';
        }
        
        // Если путь пустой, возвращаем только public_dir
        if ($relativePath === '') {
            return $this->selectedDir;
        }
        
        // Объединяем public_dir с относительным путем
        return $this->selectedDir . '/' . $relativePath;
    }

    /**
     * Получить относительный путь от public_dir
     * Преобразует полный путь на диске в относительный путь для клиента
     */
    private function getRelativePath(string $fullPath): string
    {
        $fullPath = $this->normalizePath($fullPath);
        $prefix = rtrim($this->selectedDir, '/') . '/';
        
        // Если путь начинается с public_dir, убираем префикс
        if (str_starts_with($fullPath, $prefix)) {
            return substr($fullPath, strlen($prefix));
        }
        
        // Если путь равен public_dir, возвращаем пустую строку
        if ($fullPath === $this->selectedDir) {
            return '';
        }
        
        return $fullPath;
    }

    private function stripPublicPrefix(string $path): string
    {
        $path = $this->normalizePath($path);

        $prefix = rtrim($this->selectedDir, '/') . '/';
        if ($prefix !== '/' && str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        return $path;
    }

    private function publicPath(string $diskPath): string
    {
        $diskPath = $this->normalizePath($diskPath);
        
        // Если путь уже начинается с public_dir, возвращаем его как есть
        $prefix = rtrim($this->selectedDir, '/') . '/';
        if (str_starts_with($diskPath, $prefix)) {
            return $diskPath;
        }
        
        // Если путь равен public_dir, возвращаем его
        if ($diskPath === $this->selectedDir) {
            return $diskPath;
        }
        
        // Иначе добавляем префикс
        return rtrim($this->selectedDir, '/') . ($diskPath !== '' ? '/' . $diskPath : '');
    }

    public function index()
    {
        $langPath = __DIR__.'/../../../resources/lang';
        $translations = [
            'en' => require $langPath.'/en/sfiles.php',
            'ru' => require $langPath.'/ru/sfiles.php',
        ];

        return view('sfiles::filemanager', ['translations' => $translations]);
    }

    public function files(Request $request)
    {
        // Получаем относительный путь от клиента (относительно uploads)
        $relativePath = $this->normalizePath($request->query('path', ''), false);
        
        // Преобразуем в полный путь на диске (uploads/...)
        $fullPath = $this->getWorkingPath($relativePath);

        try {
            // Получаем директории и файлы из полного пути
            $directories = $this->disk->directories($fullPath);
            sort($directories, SORT_NATURAL | SORT_FLAG_CASE);
            
            // Преобразуем пути директорий в относительные
            $relativeDirectories = array_map(function($dir) {
                return $this->getRelativePath($dir);
            }, $directories);

            $files = [];
            foreach ($this->disk->files($fullPath) as $file) {
                $relativeFile = $this->getRelativePath($file);
                // Формируем public_path из относительного пути
                $publicPathValue = $relativeFile !== '' 
                    ? rtrim($this->selectedDir, '/') . '/' . $relativeFile 
                    : $this->selectedDir;
                
                $files[] = [
                    'name' => basename($file),
                    'size' => (int)$this->disk->size($file),
                    // disk-relative путь для операций (delete/rename/etc) - полный путь на диске
                    'disk_path' => $file,
                    // opPath для совместимости с фронтендом - относительный путь
                    'opPath' => $relativeFile,
                    // public путь для открытия в браузере (формируем из относительного пути)
                    'public_path' => $publicPathValue,
                    // backward compatibility: ваш текущий фронт читает file.path
                    'path' => $publicPathValue,
                    // Добавляем publicPath для fileHref()
                    'publicPath' => $publicPathValue,
                ];
            }

            usort($files, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

            return response()->json([
                'path' => $relativePath, // Возвращаем относительный путь для клиента
                'directories' => $relativeDirectories, // Относительные пути директорий
                'files' => $files
            ]);
        } catch (\Throwable $e) {
            $this->logAction('error', [
                'action' => 'read_directory',
                'path' => $fullPath,
                'relative_path' => $relativePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Если прилетел "кривой" путь/директория — лучше вернуть пустой список, чем падать
            return response()->json([
                'path' => $relativePath,
                'directories' => [],
                'files' => [],
                'error' => 'Unable to read directory'
            ], 200);
        }
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'path' => 'required|string|max:' . config('sfiles.security.max_filename_length', 255),
        ]);

        try {
            // Получаем относительный путь от клиента
            $relativePath = $this->normalizePath($request->input('path'), false);
            if ($relativePath === '') {
                return response()->json(['error' => 'Empty path'], 422);
            }

            // Преобразуем в полный путь на диске
            $fullPath = $this->getWorkingPath($relativePath);

            // Проверка длины имени папки
            $folderName = basename($relativePath);
            $maxLength = config('sfiles.security.max_filename_length', 255);
            if (mb_strlen($folderName) > $maxLength) {
                return response()->json(['error' => "Folder name exceeds maximum length of {$maxLength} characters"], 422);
            }

            // Проверяем, что директория еще не существует
            if ($this->disk->directoryExists($fullPath)) {
                return response()->json(['error' => 'Directory already exists'], 422);
            }

            $this->disk->makeDirectory($fullPath);
            
            // Очистка кэша родительской директории
            $parentRelativePath = dirname($relativePath);
            if ($parentRelativePath === '.') {
                $parentRelativePath = '';
            }
            $this->clearCache($this->getWorkingPath($parentRelativePath));

            $this->logAction('create_folder', ['relative_path' => $relativePath, 'full_path' => $fullPath]);

            return response()->json(['success' => true]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logAction('error', [
                'action' => 'create_folder',
                'path' => $request->input('path'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Error creating folder: ' . $e->getMessage()], 422);
        }
    }

    public function deleteFolder(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        // Получаем относительный путь от клиента
        $relativePath = $this->normalizePath($request->input('path'), false);
        if ($relativePath === '') {
            abort(422, 'Refuse to delete root');
        }

        // Преобразуем в полный путь на диске
        $fullPath = $this->getWorkingPath($relativePath);

        $this->disk->deleteDirectory($fullPath);
        
        // Очистка кэша родительской директории
        $parentRelativePath = dirname($relativePath);
        if ($parentRelativePath === '.') {
            $parentRelativePath = '';
        }
        $this->clearCache($this->getWorkingPath($parentRelativePath));

        $this->logAction('delete_folder', ['relative_path' => $relativePath, 'full_path' => $fullPath]);

        return response()->json(['success' => true]);
    }

    public function upload(Request $request)
    {
        $maxSize = config('sfiles.max_file_size', 10240);
        $allowedMimes = config('sfiles.allowed_mimes', []);
        $blockedExtensions = config('sfiles.security.blocked_extensions', []);

        $request->validate([
            'file' => [
                'required',
                'file',
                "max:{$maxSize}",
                function ($attribute, $value, $fail) use ($allowedMimes, $blockedExtensions) {
                    if (!$value) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    
                    // Проверка заблокированных расширений
                    if (in_array($extension, $blockedExtensions)) {
                        $fail("Файлы с расширением .{$extension} не разрешены к загрузке.");
                        return;
                    }

                    // Получаем MIME тип от Laravel
                    $mimeType = $value->getMimeType();
                    
                    // Получаем реальный MIME тип из содержимого файла (приоритетный)
                    $realMime = $this->getRealMimeType($value->getRealPath());
                    
                    // Используем реальный MIME тип, если он определен, иначе используем MIME от Laravel
                    $finalMimeType = $realMime ?: $mimeType;
                    
                    // Если MIME тип application/octet-stream (неопределенный), проверяем по расширению
                    if ($finalMimeType === 'application/octet-stream' || empty($finalMimeType)) {
                        $allowedExtensions = config('sfiles.allowed_extensions', []);
                        
                        // Если расширение в списке разрешенных - разрешаем
                        if (!empty($allowedExtensions) && in_array($extension, $allowedExtensions)) {
                            // Дополнительная проверка: для Office документов проверяем сигнатуру файла
                            if (in_array($extension, ['docx', 'xlsx', 'pptx', 'doc', 'xls', 'ppt'])) {
                                // Проверяем, что это действительно Office документ по сигнатуре
                                $isValidOffice = $this->validateOfficeFile($value->getRealPath(), $extension);
                                if (!$isValidOffice) {
                                    $fail("Файл не является валидным Office документом.");
                                    return;
                                }
                            }
                            // Разрешаем загрузку для разрешенных расширений с application/octet-stream
                            return;
                        } else {
                            $fail("Тип файла не может быть определен. Файлы с расширением .{$extension} не разрешены к загрузке.");
                            return;
                        }
                    }

                    // Проверка MIME типа (если он определен и не application/octet-stream)
                    if (!empty($allowedMimes) && !in_array($finalMimeType, $allowedMimes)) {
                        // Если реальный MIME не совпадает с заявленным - это подозрительно
                        if ($realMime && $realMime !== $mimeType) {
                            $fail("Обнаружено несоответствие типа файла. Загрузка отклонена.");
                            return;
                        }
                        
                        // Если MIME не в списке разрешенных, но расширение разрешено - разрешаем
                        $allowedExtensions = config('sfiles.allowed_extensions', []);
                        if (!empty($allowedExtensions) && in_array($extension, $allowedExtensions)) {
                            // Разрешаем для известных безопасных расширений
                            return;
                        }
                        
                        $fail("Тип файла {$finalMimeType} не разрешен к загрузке.");
                        return;
                    }
                },
            ],
            'path' => 'nullable|string'
        ]);

        $file = $request->file('file');
        // Получаем относительный путь от клиента
        $relativePath = $this->normalizePath($request->input('path', ''), false);
        
        // Преобразуем в полный путь на диске
        $fullPath = $this->getWorkingPath($relativePath);

        // Оригинальное имя с правильной кодировкой
        $filename = $file->getClientOriginalName();
        $filename = mb_convert_encoding($filename, 'UTF-8', 'auto');
        
        // Санитизация имени файла
        $filename = $this->sanitizeFileName($filename);

        // Проверка длины имени
        $maxLength = config('sfiles.security.max_filename_length', 255);
        if (mb_strlen($filename) > $maxLength) {
            $originalName = pathinfo($filename, PATHINFO_FILENAME);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $maxNameLength = $maxLength - (mb_strlen($extension) + 1);
            $filename = mb_substr($originalName, 0, $maxNameLength) . '.' . $extension;
        }

        $counter = 1;
        $originalName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $probe = $fullPath !== '' ? ($fullPath . '/' . $filename) : $filename;

        while ($this->disk->exists($probe)) {
            $filename = $extension
                ? "{$originalName} ({$counter}).{$extension}"
                : "{$originalName} ({$counter})";

            $probe = $fullPath !== '' ? ($fullPath . '/' . $filename) : $filename;
            $counter++;
        }

        $filePath = $file->storeAs(
            $fullPath,
            $filename,
            $this->selectedDisk
        );

        // Очистка кэша
        $this->clearCache($fullPath);

        $this->logAction('upload_file', [
            'filename' => $filename,
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'disk_path' => $filePath,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        // Формируем относительный путь для public_path
        $uploadedRelativePath = $this->getRelativePath($filePath);
        $uploadedPublicPath = $uploadedRelativePath !== '' 
            ? rtrim($this->selectedDir, '/') . '/' . $uploadedRelativePath 
            : $this->selectedDir;

        return response()->json([
            'success' => true,
            'disk_path' => $filePath,
            'public_path' => $uploadedPublicPath,
            'filename' => $filename
        ]);
    }

    /**
     * Get real MIME type by reading file content
     */
    private function getRealMimeType(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mime ?: null;
    }

    /**
     * Validate Office file by checking file signature
     */
    private function validateOfficeFile(string $filePath, string $extension): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        // Читаем первые байты для проверки сигнатуры
        $header = fread($handle, 8);
        fclose($handle);

        if ($header === false) {
            return false;
        }

        // Проверка сигнатур Office файлов
        switch (strtolower($extension)) {
            case 'docx':
            case 'xlsx':
            case 'pptx':
                // Office Open XML (OOXML) файлы начинаются с PK (ZIP архив)
                // Сигнатура: 50 4B 03 04 (PK\x03\x04)
                return substr($header, 0, 2) === 'PK' && 
                       (ord($header[2]) === 0x03 || ord($header[2]) === 0x05 || ord($header[2]) === 0x07);
            
            case 'doc':
                // Старые DOC файлы (OLE2)
                // Сигнатура: D0 CF 11 E0 A1 B1 1A E1
                $oleSignature = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
                return substr($header, 0, 8) === $oleSignature;
            
            case 'xls':
                // Старые XLS файлы (OLE2 или BIFF)
                // OLE2 сигнатура
                $oleSignature = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
                if (substr($header, 0, 8) === $oleSignature) {
                    return true;
                }
                // BIFF сигнатура (старые Excel файлы)
                // Первые байты могут быть 09 08 или 00 00
                return true; // Более мягкая проверка для старых форматов
            
            case 'ppt':
                // Старые PPT файлы (OLE2)
                $oleSignature = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
                return substr($header, 0, 8) === $oleSignature;
            
            default:
                return true; // Для других расширений считаем валидным
        }
    }

    /**
     * Sanitize filename with proper encoding handling
     */
    private function sanitizeFileName(string $filename): string
    {
        // Нормализация Unicode
        if (function_exists('normalizer_normalize')) {
            $filename = normalizer_normalize($filename, \Normalizer::FORM_C);
        }

        // Конвертация в UTF-8
        $filename = mb_convert_encoding($filename, 'UTF-8', 'auto');

        // Запрещаем путь, разрешаем только имя
        $filename = str_replace(['\\', '/'], '_', $filename);

        // Нулевые байты и управляющие символы
        $filename = preg_replace('/\x00|[\x00-\x1F\x7F]/u', '_', $filename);

        // Спецсимволы, которые часто ломают FS/URL
        $filename = preg_replace('/[<>:"|?*]/u', '_', $filename);

        // Схлопываем пробелы
        $filename = preg_replace('/\s+/u', ' ', $filename);

        // Убираем ведущие/конечные точки и пробелы
        $filename = trim($filename, '. ');

        return $filename;
    }

    /**
     * Clear cache for a specific path
     * Использует try-catch для обработки ошибок блокировки базы данных при параллельных запросах
     * Оптимизирован для уменьшения количества операций с кэшем
     */
    private function clearCache(string $path): void
    {
        if (!config('sfiles.cache.enabled', true)) {
            return;
        }

        try {
            // Очищаем кэш только для первых 10 страниц (обычно этого достаточно)
            // Это уменьшает количество операций с кэшем при параллельных запросах
            $maxPages = 10;
            
            for ($i = 1; $i <= $maxPages; $i++) {
                try {
                    Cache::forget("sfiles:files:{$path}:{$i}");
                } catch (\Throwable $e) {
                    // Игнорируем ошибки блокировки при параллельных запросах
                    if (str_contains($e->getMessage(), 'locked') || str_contains($e->getMessage(), 'database is locked')) {
                        continue;
                    }
                    // Для других ошибок логируем, но продолжаем
                    if (config('sfiles.logging.enabled', true)) {
                        Log::warning("Cache clear error for path {$path}:{$i}: " . $e->getMessage());
                    }
                }
            }
            
            // Также очищаем кэш родительской директории (только первые страницы)
            if ($path !== '') {
                $parentPath = dirname($path);
                if ($parentPath === '.') {
                    $parentPath = '';
                }
                for ($i = 1; $i <= $maxPages; $i++) {
                    try {
                        Cache::forget("sfiles:files:{$parentPath}:{$i}");
                    } catch (\Throwable $e) {
                        // Игнорируем ошибки блокировки при параллельных запросах
                        if (str_contains($e->getMessage(), 'locked') || str_contains($e->getMessage(), 'database is locked')) {
                            continue;
                        }
                        // Для других ошибок логируем, но продолжаем
                        if (config('sfiles.logging.enabled', true)) {
                            Log::warning("Cache clear error for parent path {$parentPath}:{$i}: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Если произошла критическая ошибка - логируем, но не прерываем выполнение
            if (config('sfiles.logging.enabled', true)) {
                Log::warning("Cache clear failed for path {$path}: " . $e->getMessage());
            }
        }
    }

    public function delete(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        // Получаем относительный путь от клиента (opPath из файла)
        $relativePath = $this->normalizePath($request->input('path'), false);
        
        if ($relativePath === '') {
            return response()->json(['error' => 'Invalid file path'], 422);
        }

        // Преобразуем в полный путь на диске
        $fullPath = $this->getWorkingPath($relativePath);

        if ($this->disk->exists($fullPath)) {
            $this->disk->delete($fullPath);
            
            // Очистка кэша родительской директории
            $parentRelativePath = dirname($relativePath);
            if ($parentRelativePath === '.') {
                $parentRelativePath = '';
            }
            $this->clearCache($this->getWorkingPath($parentRelativePath));

            $this->logAction('delete_file', ['relative_path' => $relativePath, 'full_path' => $fullPath]);

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    public function downloadFolder(Request $request)
    {
        if (!class_exists(\ZipArchive::class)) {
            abort(500, 'ZipArchive is not available (php-zip extension missing).');
        }

        // Получаем относительный путь от клиента
        $relativePath = $this->normalizePath($request->query('path', ''), false);

        // Чтобы случайно не архивировать весь корень
        if ($relativePath === '') {
            abort(422, 'Path is required');
        }

        // Преобразуем в полный путь на диске
        $fullPath = $this->getWorkingPath($relativePath);

        if (!$this->disk->directoryExists($fullPath)) {
            abort(404, 'Directory not found');
        }

        @set_time_limit(0);

        $folderName = basename($relativePath);
        $zipName = $folderName . '.zip';

        // Временный файл
        $tmp = tempnam(sys_get_temp_dir(), 'sfiles_zip_');
        $zipPath = $tmp . '.zip';
        @rename($tmp, $zipPath);

        $zip = new \ZipArchive();
        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, 'Unable to create zip');
        }

        // Чтобы внутри архива всё лежало в папке <folderName>/
        $root = $folderName;
        $zip->addEmptyDir($root);

        // Добавляем пустые директории
        foreach ($this->disk->allDirectories($fullPath) as $dir) {
            $rel = ltrim(substr($dir, strlen($fullPath)), '/');
            if ($rel === '') continue;
            $zip->addEmptyDir($root . '/' . $rel);
        }

        // Добавляем файлы
        foreach ($this->disk->allFiles($fullPath) as $file) {
            $rel = ltrim(substr($file, strlen($fullPath)), '/');
            $entryName = $root . '/' . $rel;

            // Если диск локальный — лучше addFile (не грузит файл в память)
            try {
                $absPath = Storage::disk($this->selectedDisk)->path($file);
                if (is_file($absPath)) {
                    $zip->addFile($absPath, $entryName);
                    continue;
                }
            } catch (\Throwable $e) {
                // fallback ниже
            }

            // Fallback для не-local: читаем содержимое и кладём в zip
            $zip->addFromString($entryName, $this->disk->get($file));
        }

        $zip->close();

        $this->logAction('download_folder', ['relative_path' => $relativePath, 'full_path' => $fullPath]);

        return response()
            ->download($zipPath, $zipName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function downloadFiles(Request $request)
    {
        if (!class_exists(\ZipArchive::class)) {
            abort(500, 'ZipArchive is not available (php-zip extension missing).');
        }

        $paths = $request->input('paths', []);

        if (!is_array($paths) || count($paths) === 0) {
            abort(422, 'No files selected');
        }

        // ограничение, чтобы не убить сервер
        if (count($paths) > 300) {
            abort(422, 'Too many files selected');
        }

        // Получаем относительные пути от клиента (opPath из файлов)
        $relativePaths = [];
        foreach ($paths as $p) {
            if (!is_string($p)) continue;

            $relativePath = $this->normalizePath($p, false);
            if ($relativePath === '') continue;

            // Преобразуем в полный путь на диске
            $fullPath = $this->getWorkingPath($relativePath);

            if (!$this->disk->exists($fullPath)) {
                abort(404, "File not found: {$relativePath}");
            }

            $relativePaths[] = [
                'relative' => $relativePath,
                'full' => $fullPath
            ];
        }

        if (count($relativePaths) === 0) {
            abort(404, 'Files not found');
        }

        @set_time_limit(0);

        $zipName = 'files_' . date('Y-m-d_H-i-s') . '.zip';

        // Временный файл
        $tmp = tempnam(sys_get_temp_dir(), 'sfiles_zip_');
        $zipPath = $tmp . '.zip';
        @rename($tmp, $zipPath);

        $zip = new \ZipArchive();
        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, 'Unable to create zip');
        }

        // Папка внутри архива
        $root = 'files';
        $zip->addEmptyDir($root);

        // Чтобы не было конфликтов имён в архиве
        $used = [];

        foreach ($relativePaths as $item) {
            $fullPath = $item['full'];
            $base = basename($fullPath);
            $entry = $base;

            $key = mb_strtolower($entry);
            if (isset($used[$key])) {
                $pi = pathinfo($base);
                $stem = $pi['filename'] ?? $base;
                $ext = isset($pi['extension']) && $pi['extension'] !== '' ? '.' . $pi['extension'] : '';

                $i = 1;
                do {
                    $entry = "{$stem} ({$i}){$ext}";
                    $key = mb_strtolower($entry);
                    $i++;
                } while (isset($used[$key]));
            }

            $used[$key] = true;

            $zipEntryName = $root . '/' . $entry;

            // Если диск локальный — addFile (не тащит файл в память)
            try {
                $absPath = Storage::disk($this->selectedDisk)->path($fullPath);
                if (is_file($absPath)) {
                    $zip->addFile($absPath, $zipEntryName);
                    continue;
                }
            } catch (\Throwable $e) {
                // fallback ниже
            }

            // Fallback для не-local
            $zip->addFromString($zipEntryName, $this->disk->get($fullPath));
        }

        $zip->close();

        $this->logAction('download_files', ['count' => count($relativePaths)]);

        return response()
            ->download($zipPath, $zipName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function sanitizeName(string $name): string
    {
        // Нормализация Unicode
        if (function_exists('normalizer_normalize')) {
            $name = normalizer_normalize($name, \Normalizer::FORM_C);
        }

        // Конвертация в UTF-8
        $name = mb_convert_encoding($name, 'UTF-8', 'auto');
        $name = trim($name);

        // Запрещаем путь, разрешаем только имя
        $name = str_replace(['\\', '/'], '_', $name);

        // Нулевые байты и управляющие символы
        $name = preg_replace('/\x00|[\x00-\x1F\x7F]/u', '_', $name);

        // Спецсимволы, которые часто ломают FS/URL
        $name = preg_replace('/[<>:"|?*]/u', '_', $name);

        // Схлопываем пробелы
        $name = preg_replace('/\s+/u', ' ', $name);

        // Убираем ведущие/конечные точки и пробелы
        $name = trim($name, '. ');

        return $name;
    }

    private function moveDirectory(string $from, string $to): void
    {
        $from = $this->normalizePath($from);
        $to = $this->normalizePath($to);

        // создать корневую папку назначения
        if (!$this->disk->directoryExists($to)) {
            $this->disk->makeDirectory($to);
        }

        // создать все подпапки
        foreach ($this->disk->allDirectories($from) as $dir) {
            $rel = ltrim(substr($dir, strlen($from)), '/');
            if ($rel !== '') {
                $this->disk->makeDirectory($to . '/' . $rel);
            }
        }

        // перенести все файлы
        foreach ($this->disk->allFiles($from) as $file) {
            $rel = ltrim(substr($file, strlen($from)), '/');
            $this->disk->move($file, $to . '/' . $rel);
        }

        // удалить исходную папку
        $this->disk->deleteDirectory($from);
    }


    public function rename(Request $request)
    {
        $request->validate([
            'type' => 'required|in:file,dir',
            'path' => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        $type = $request->input('type');

        // Получаем относительный путь от клиента (opPath из файла)
        $oldRelativePath = $this->normalizePath($request->input('path'), false);

        if ($oldRelativePath === '') {
            return response()->json(['error' => 'Invalid path'], 422);
        }

        // Преобразуем в полный путь на диске
        $oldFullPath = $this->getWorkingPath($oldRelativePath);

        $newName = $this->sanitizeName($request->input('new_name'));

        if ($newName === '' || $newName === '.' || $newName === '..') {
            return response()->json(['error' => 'Invalid name'], 422);
        }

        $parentRelativePath = dirname($oldRelativePath);
        $parentRelativePath = ($parentRelativePath === '.' ? '' : $parentRelativePath);

        // Для файла: если пользователь ввёл имя без расширения — сохраняем старое расширение
        if ($type === 'file') {
            if (!$this->disk->exists($oldFullPath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $oldExt = pathinfo($oldRelativePath, PATHINFO_EXTENSION);
            $newExt = pathinfo($newName, PATHINFO_EXTENSION);

            if ($oldExt && !$newExt) {
                $newName = $newName . '.' . $oldExt;
            }
        }

        // Для папки: проверяем существование директории
        if ($type === 'dir') {
            if (!$this->disk->directoryExists($oldFullPath)) {
                return response()->json(['error' => 'Directory not found'], 404);
            }
        }

        $newRelativePath = $parentRelativePath !== '' ? ($parentRelativePath . '/' . $newName) : $newName;
        
        // Нормализуем новый относительный путь
        $newRelativePath = $this->normalizePath($newRelativePath, false);
        
        // Преобразуем в полный путь на диске
        $newFullPath = $this->getWorkingPath($newRelativePath);

        // no-op
        if ($newFullPath === $oldFullPath) {
            // Формируем public_path из относительного пути
            $newPublicPath = $newRelativePath !== '' 
                ? rtrim($this->selectedDir, '/') . '/' . $newRelativePath 
                : $this->selectedDir;
            
            return response()->json([
                'success' => true,
                'old_path' => $oldRelativePath,
                'new_path' => $newRelativePath,
                'public_path' => $type === 'file' ? $newPublicPath : null,
            ]);
        }

        // конфликт имён
        if ($this->disk->exists($newFullPath) || $this->disk->directoryExists($newFullPath)) {
            return response()->json(['error' => 'Target already exists'], 409);
        }

        if ($type === 'file') {
            $this->disk->move($oldFullPath, $newFullPath);
        } else {
            $this->moveDirectory($oldFullPath, $newFullPath);
        }

        // Очистка кэша родительской директории
        $this->clearCache($this->getWorkingPath($parentRelativePath));

        $this->logAction('rename', [
            'type' => $type,
            'old_relative_path' => $oldRelativePath,
            'new_relative_path' => $newRelativePath,
            'old_full_path' => $oldFullPath,
            'new_full_path' => $newFullPath,
        ]);

        // Формируем public_path из относительного пути
        $newPublicPath = $newRelativePath !== '' 
            ? rtrim($this->selectedDir, '/') . '/' . $newRelativePath 
            : $this->selectedDir;

        return response()->json([
            'success' => true,
            'old_path' => $oldRelativePath,
            'new_path' => $newRelativePath,
            'public_path' => $type === 'file' ? $newPublicPath : null,
        ]);
    }

}
