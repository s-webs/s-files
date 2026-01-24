# TinyMCE Integration через конфигурацию

S-Files поддерживает интеграцию с TinyMCE **только через конфигурационный файл**, без необходимости использования Blade шаблонов.

## Быстрый старт

### 1. Подключите необходимые скрипты

В вашем HTML шаблоне (или в layout файле) подключите скрипты **в правильном порядке**:

```html
<!-- 1. TinyMCE (если еще не подключен) -->
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>

<!-- 2. S-Files интеграция -->
<script src="{{ route('sfiles.index') }}/assets/js/tinymce-integration.js"></script>

<!-- 3. Автоматическая интеграция (обрабатывает конфигурацию) -->
<script src="{{ route('sfiles.index') }}/assets/js/tinymce-auto-integration.js"></script>
```

### 2. Настройте конфигурацию TinyMCE

В вашем `config/tinymce.php`:

```php
<?php

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
    
    // Настройки S-Files интеграции
    'sfiles' => [
        'base_url' => env('SFILES_ROUTE_PREFIX', '/s-files'),
        'width' => 900,   // Ширина popup окна
        'height' => 600,  // Высота popup окна
    ],
    
    'callbacks' => [
        // Укажите 'sfiles' для автоматического подключения интеграции
        'file_picker_callback' => 'sfiles',
    ],
    
    'options' => [],
];
```

### 3. Инициализируйте TinyMCE

В вашем JavaScript коде (или в отдельном файле):

```javascript
// Получаем конфигурацию из PHP
const config = @json(config('tinymce'));

// Инициализируем TinyMCE
// Скрипт tinymce-auto-integration.js автоматически обработает конфигурацию
tinymce.init(config);
```

## Настройки S-Files

В секции `sfiles` конфигурации можно указать:

- `base_url` (string) - Базовый URL файлового менеджера. По умолчанию: `/s-files`
- `width` (number) - Ширина popup окна в пикселях. По умолчанию: `900`
- `height` (number) - Высота popup окна в пикселях. По умолчанию: `600`

## Как это работает

1. Скрипт `tinymce-auto-integration.js` перехватывает вызов `tinymce.init()`
2. Проверяет, указано ли `'sfiles'` в `file_picker_callback`
3. Если да, автоматически заменяет строку `'sfiles'` на реальную функцию интеграции
4. Тип файла (image/file/media) определяется автоматически на основе контекста TinyMCE

## Примеры

### Минимальная конфигурация

```php
'callbacks' => [
    'file_picker_callback' => 'sfiles',
],
```

### С кастомными настройками

```php
'sfiles' => [
    'base_url' => '/s-files',
    'width' => 1200,
    'height' => 800,
],
'callbacks' => [
    'file_picker_callback' => 'sfiles',
],
```

### Использование через Helper класс (опционально)

Если вы хотите использовать PHP Helper класс для генерации JavaScript кода:

```php
use Swebs\Sfiles\Helpers\TinyMCEHelper;

$config = config('tinymce');
$script = TinyMCEHelper::generateScript($config);
```

Затем выведите `$script` в вашем шаблоне.

## Примечания

- Скрипт `tinymce-auto-integration.js` должен быть подключен **после** `tinymce-integration.js`
- Тип файла определяется автоматически на основе `meta.filetype` от TinyMCE
- Если popup окна заблокированы браузером, автоматически используется модальное окно с iframe
- Все настройки безопасности и авторизации S-Files применяются автоматически
