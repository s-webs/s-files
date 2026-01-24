# TinyMCE Integration

S-Files поддерживает интеграцию с TinyMCE через специальный интерфейс выбора файлов.

## Установка

1. Подключите JavaScript файл интеграции в ваш проект:

```html
<script src="{{ asset('vendor/s-webs/s-files/resources/js/tinymce-integration.js') }}"></script>
```

Или если используете Vite:

```javascript
import 'vendor/s-webs/s-files/resources/js/tinymce-integration.js';
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
