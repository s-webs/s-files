<?php

return [
    /*
    |--------------------------------------------------------------------------
    | S-Files Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the S-Files file manager package
    |
    */

    // Storage disk to use
    'disk' => env('SFILES_DISK', 'public'),

    // Public directory prefix
    'public_dir' => env('SFILES_PUBLIC_DIR', 'uploads'),

    // Routes configuration
    'routes' => [
        'prefix' => env('SFILES_ROUTE_PREFIX', 's-files'),
        'middleware' => ['web'], // базовые middleware
    ],

    // Authentication configuration
    'auth' => [
        'enabled' => env('SFILES_AUTH_ENABLED', false), // Опциональная авторизация
        'middleware' => env('SFILES_AUTH_MIDDLEWARE', 'auth'), // middleware для защиты (если enabled = true)
    ],

    // Default locale for the file manager UI (en, ru)
    'locale' => env('SFILES_LOCALE', 'en'),

    // Assets configuration
    'assets' => [
        'use_cdn' => env('SFILES_USE_CDN', false),
    ],

    // Allowed MIME types for upload
    'allowed_mimes' => [
        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',

        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'text/plain',
        'text/csv',
        'application/rtf',

        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/gzip',
        'application/x-7z-compressed',

        // Media
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',

        // Other
        'application/json',
        'application/xml',
        'text/xml',

        // Fallback for files with undetermined MIME type (will be validated by extension)
        // 'application/octet-stream' - не добавляем сюда, проверяем по расширению
    ],

    // Allowed file extensions (as fallback)
    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'odt', 'ods', 'odp', 'txt', 'csv', 'rtf',
        'zip', 'rar', 'tar', 'gz', '7z',
        'mp3', 'wav', 'ogg', 'mp4', 'mov', 'avi',
        'json', 'xml',
    ],

    // Max file size in KB (default 10MB = 10240)
    'max_file_size' => env('SFILES_MAX_SIZE', 10240),

    // Rate limiting
    'rate_limit' => [
        'upload' => env('SFILES_RATE_LIMIT_UPLOAD', 100), // requests per minute (увеличено для массовой загрузки)
        'delete' => env('SFILES_RATE_LIMIT_DELETE', 30),
        'general' => env('SFILES_RATE_LIMIT_GENERAL', 60),
    ],

    // Pagination
    'pagination' => [
        'enabled' => env('SFILES_PAGINATION_ENABLED', true),
        'per_page' => env('SFILES_PER_PAGE', 50),
    ],

    // Cache settings
    'cache' => [
        'enabled' => env('SFILES_CACHE_ENABLED', true),
        'ttl' => env('SFILES_CACHE_TTL', 300), // 5 minutes
    ],

    // Logging
    'logging' => [
        'enabled' => env('SFILES_LOGGING_ENABLED', true),
        'channel' => env('SFILES_LOG_CHANNEL', 'daily'),
    ],

    // Security
    'security' => [
        // Block dangerous file extensions even if MIME is allowed
        'blocked_extensions' => [
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
            'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js',
            'sh', 'bash', 'csh', 'ksh', 'pl', 'py', 'rb',
            'jar', 'war', 'ear', 'class',
        ],
        // Maximum filename length
        'max_filename_length' => 255,
        // Maximum path depth
        'max_path_depth' => 20,
    ],
];
