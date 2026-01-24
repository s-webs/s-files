<?php

/**
 * Пример конфигурации TinyMCE с интеграцией S-Files
 * 
 * Скопируйте этот файл в config/tinymce.php и настройте под свои нужды
 */

return [
    'token' => env('TINYMCE_TOKEN', ''),
    
    'plugins' => [
        'anchor', 'autolink', 'autoresize', 'charmap', 'codesample', 'code', 'emoticons', 'image', 'link',
        'lists', 'advlist', 'media', 'searchreplace', 'table', 'wordcount', 'directionality',
        'fullscreen', 'help', 'nonbreaking', 'pagebreak', 'preview', 'visualblocks', 'visualchars'
    ],
    
    'menubar' => 'file edit insert view format table tools',
    
    'toolbar' => 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | '
        . 'link image media table tabledelete hr nonbreaking pagebreak | align lineheight | '
        . 'numlist bullist indent outdent | emoticons charmap | removeformat | codesample | ltr rtl | '
        . 'tableprops tablerowprops tablecellprops | tableinsertrowbefore tableinsertrowafter tabledeleterow | '
        . 'tableinsertcolbefore tableinsertcolafter tabledeletecol | '
        . 'fullscreen preview print visualblocks visualchars code | help',
    
    'options' => [],
    
    /**
     * Настройки S-Files интеграции
     * 
     * Для подключения интеграции укажите 'sfiles' в callbacks.file_picker_callback
     */
    'sfiles' => [
        // Базовый URL файлового менеджера (обычно совпадает с SFILES_ROUTE_PREFIX)
        'base_url' => env('SFILES_ROUTE_PREFIX', '/s-files'),
        
        // Размеры popup окна файлового менеджера
        'width' => 900,
        'height' => 600,
    ],
    
    /**
     * Callbacks для TinyMCE
     * 
     * Для автоматического подключения интеграции S-Files используйте один из вариантов:
     * 
     * Вариант 1 (рекомендуется для админ-панелей с eval()):
     * Используйте объект-маркер для безопасной сериализации в JSON
     */
    'callbacks' => [
        'file_picker_callback' => ['__sfiles__' => true], // Безопасный маркер для eval()
    ],
    
    /**
     * Вариант 2 (для обычных проектов):
     * Можно использовать строку 'sfiles', но если получаете ошибку "sfiles is not defined",
     * переключитесь на вариант 1
     */
    // 'callbacks' => [
    //     'file_picker_callback' => 'sfiles', // Простая строка (может не работать с eval())
    // ],
];
