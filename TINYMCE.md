# TinyMCE Integration

S-Files поддерживает интеграцию с TinyMCE через специальный интерфейс выбора файлов.

## Способ 1: Интеграция через конфигурацию (рекомендуется)

Этот способ позволяет настроить интеграцию **только через конфигурационный файл**, без использования Blade шаблонов.

### Установка

1. Подключите необходимые скрипты в правильном порядке:

```html
<!-- 1. TinyMCE -->
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>

<!-- 2. S-Files интеграция -->
<script src="{{ route('sfiles.index') }}/assets/js/tinymce-integration.js"></script>

<!-- 3. Автоматическая интеграция -->
<script src="{{ route('sfiles.index') }}/assets/js/tinymce-auto-integration.js"></script>
```

2. Настройте конфигурацию в `config/tinymce.php`:

```php
<?php

return [
    'token' => env('TINYMCE_TOKEN', ''),
    'plugins' => ['image', 'link', 'media', /* ... */],
    'toolbar' => 'image link media | /* ... */',
    
    // Настройки S-Files интеграции
    'sfiles' => [
        'base_url' => env('SFILES_ROUTE_PREFIX', '/s-files'),
        'width' => 900,
        'height' => 600,
    ],
    
    'callbacks' => [
        // Укажите 'sfiles' для автоматического подключения
        'file_picker_callback' => 'sfiles',
    ],
];
```

3. Инициализируйте TinyMCE:

```javascript
const config = @json(config('tinymce'));
tinymce.init(config);
```

Скрипт `tinymce-auto-integration.js` автоматически обработает конфигурацию и подключит интеграцию.

**Подробная документация:** [TINYMCE-CONFIG.md](TINYMCE-CONFIG.md)

## Способ 2: Ручная интеграция

Если вам нужен больший контроль, вы можете настроить интеграцию вручную.

### Установка

1. Подключите JavaScript файл интеграции:

```html
<script src="{{ route('sfiles.index') }}/assets/js/tinymce-integration.js"></script>
```

2. Настройте TinyMCE с использованием `sfilesTinyMCEPicker`:

```javascript
tinymce.init({
    selector: '#mytextarea',
    plugins: 'image link',
    toolbar: 'image link',
    
    // Интеграция с S-Files
    file_picker_callback: sfilesTinyMCEPicker({
        baseUrl: '/s-files',  // Базовый URL файлового менеджера (по умолчанию: '/s-files')
        width: 900,            // Ширина popup окна (по умолчанию: 900)
        height: 600,           // Высота popup окна (по умолчанию: 600)
        type: 'file'          // Тип файлов: 'image', 'file', или 'media' (по умолчанию: 'file')
    })
});
```

## Примеры использования

### Для изображений

```javascript
tinymce.init({
    selector: '#editor',
    plugins: 'image',
    toolbar: 'image',
    
    file_picker_callback: sfilesTinyMCEPicker({
        baseUrl: '/s-files',
        type: 'image'  // Фильтр только для изображений
    })
});
```

### Для медиа файлов

```javascript
tinymce.init({
    selector: '#editor',
    plugins: 'media',
    toolbar: 'media',
    
    file_picker_callback: sfilesTinyMCEPicker({
        baseUrl: '/s-files',
        type: 'media'
    })
});
```

### С кастомными размерами окна

```javascript
tinymce.init({
    selector: '#editor',
    plugins: 'image link',
    toolbar: 'image link',
    
    file_picker_callback: sfilesTinyMCEPicker({
        baseUrl: '/s-files',
        width: 1200,
        height: 800
    })
});
```

### Умный callback с автоматическим определением типа

```javascript
tinymce.init({
    selector: '#editor',
    plugins: 'image link media',
    toolbar: 'image link media',
    
    file_picker_callback: function(callback, value, meta) {
        // Определяем тип файла на основе meta
        let fileType = 'file';
        if (meta.filetype === 'image') {
            fileType = 'image';
        } else if (meta.filetype === 'media') {
            fileType = 'media';
        }
        
        // Используем S-Files picker
        const picker = sfilesTinyMCEPicker({
            baseUrl: '/s-files',
            type: fileType
        });
        
        picker(callback, value, meta);
    }
});
```

## Как это работает

1. Когда пользователь нажимает кнопку вставки изображения/файла в TinyMCE, открывается popup окно (или модальное окно с iframe, если popup заблокирован) с упрощенным интерфейсом файлового менеджера.

2. Пользователь выбирает файл из списка.

3. После нажатия кнопки "Выбрать", URL файла автоматически вставляется в TinyMCE редактор.

## API

### `sfilesTinyMCEPicker(options)`

Создает callback функцию для TinyMCE `file_picker_callback`.

**Параметры:**

- `baseUrl` (string, опционально): Базовый URL файлового менеджера. По умолчанию: `'/s-files'`
- `width` (number, опционально): Ширина popup окна в пикселях. По умолчанию: `900`
- `height` (number, опционально): Высота popup окна в пикселях. По умолчанию: `600`
- `type` (string, опционально): Тип файлов для фильтрации. Возможные значения: `'image'`, `'file'`, `'media'`. По умолчанию: `'file'`

**Возвращает:**

Функцию callback для использования в `file_picker_callback` опции TinyMCE.

## Примечания

- Если браузер блокирует popup окна, автоматически используется модальное окно с iframe.
- Файловый менеджер использует те же настройки авторизации и безопасности, что и основной интерфейс.
- Поддерживаются все типы файлов, настроенные в конфигурации S-Files.
- При использовании способа 1 (через конфигурацию), тип файла определяется автоматически на основе контекста TinyMCE.

## Примеры использования

### Для изображений

```javascript
tinymce.init({
    selector: '#editor',
    plugins: 'image',
    toolbar: 'image',
    
    file_picker_callback: sfilesTinyMCEPicker({
        baseUrl: '/s-files',
        type: 'image'  // Фильтр только для изображений
    })
});
```

### Для медиа файлов

```javascript
tinymce.init({
    selector: '#editor',
    plugins: 'media',
    toolbar: 'media',
    
    file_picker_callback: sfilesTinyMCEPicker({
        baseUrl: '/s-files',
        type: 'media'
    })
});
```

### С кастомными размерами окна

```javascript
tinymce.init({
    selector: '#editor',
    plugins: 'image link',
    toolbar: 'image link',
    
    file_picker_callback: sfilesTinyMCEPicker({
        baseUrl: '/s-files',
        width: 1200,
        height: 800
    })
});
```

## Как это работает

1. Когда пользователь нажимает кнопку вставки изображения/файла в TinyMCE, открывается popup окно (или модальное окно с iframe, если popup заблокирован) с упрощенным интерфейсом файлового менеджера.

2. Пользователь выбирает файл из списка.

3. После нажатия кнопки "Выбрать", URL файла автоматически вставляется в TinyMCE редактор.

## API

### `sfilesTinyMCEPicker(options)`

Создает callback функцию для TinyMCE `file_picker_callback`.

**Параметры:**

- `baseUrl` (string, опционально): Базовый URL файлового менеджера. По умолчанию: `'/s-files'`
- `width` (number, опционально): Ширина popup окна в пикселях. По умолчанию: `900`
- `height` (number, опционально): Высота popup окна в пикселях. По умолчанию: `600`
- `type` (string, опционально): Тип файлов для фильтрации. Возможные значения: `'image'`, `'file'`, `'media'`. По умолчанию: `'file'`

**Возвращает:**

Функцию callback для использования в `file_picker_callback` опции TinyMCE.

## Примечания

- Если браузер блокирует popup окна, автоматически используется модальное окно с iframe.
- Файловый менеджер использует те же настройки авторизации и безопасности, что и основной интерфейс.
- Поддерживаются все типы файлов, настроенные в конфигурации S-Files.
