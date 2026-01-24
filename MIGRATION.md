# Миграция на компонентную архитектуру

Файловый менеджер был переписан с Alpine.js на нативный JavaScript с компонентным подходом для улучшения поддерживаемости.

## Структура новой архитектуры

```
resources/js/
├── core/
│   ├── Component.js       # Базовый класс компонента
│   ├── EventEmitter.js    # Система событий
│   ├── StateManager.js    # Управление состоянием
│   ├── ApiService.js      # Работа с API
│   ├── I18n.js           # Интернационализация
│   └── Utils.js           # Утилиты
├── components/
│   └── FileManager.js     # Главный компонент
└── filemanager-new.js     # Точка входа и инициализация
```

## Использование

### 1. Обновление Blade шаблона

Замените Alpine.js директивы на data-атрибуты:

**Было:**
```blade
<div x-data="fileManager()" x-init="init()">
```

**Стало:**
```blade
<div data-file-manager>
```

### 2. Подключение скрипта

В `filemanager.blade.php` замените импорт:

**Было:**
```blade
@vite(['vendor/s-webs/s-files/resources/js/filemanager.js'])
```

**Стало:**
```blade
@vite(['vendor/s-webs/s-files/resources/js/filemanager-new.js'])
```

### 3. Использование в коде

Новая архитектура автоматически инициализируется при загрузке страницы. Доступ к менеджеру:

```javascript
// Глобальный доступ
const fileManager = window.sfilesManager;

// Методы
fileManager.fetchFiles(1);
fileManager.selectFile(file);
fileManager.createFolder('new-folder');
fileManager.deleteFile(file);

// Состояние
const state = fileManager.getState();
const currentPath = fileManager.get('currentPath');

// События
fileManager.on('render', () => {
    console.log('Rendering...');
});

// Переводы
const text = fileManager.t('folder_created');
```

## Преимущества новой архитектуры

1. **Модульность** - код разделен на логические компоненты
2. **Поддерживаемость** - легче находить и исправлять ошибки
3. **Тестируемость** - компоненты можно тестировать независимо
4. **Расширяемость** - легко добавлять новые функции
5. **Производительность** - нет зависимости от Alpine.js

## Миграция компонентов

### Уведомления

**Было:**
```blade
<div x-show="notification.show">
```

**Стало:**
```blade
<div data-notification style="display: none;">
    <p data-notification-message></p>
</div>
```

### Загрузка

**Было:**
```blade
<div x-show="loading">
```

**Стало:**
```blade
<div data-loading style="display: none;">
```

### Файлы

**Было:**
```blade
<a @click="selectFile(file)">
```

**Стало:**
```blade
<a data-file='@json($file)' href="#">
```

## API компонентов

### FileManager

Основные методы:
- `fetchFiles(page)` - загрузка файлов
- `selectFile(file)` - выбор файла
- `createFolder(name)` - создание папки
- `deleteFile(file)` - удаление файла
- `deleteFolder(dir)` - удаление папки
- `rename(type, path, newName)` - переименование
- `openDirectory(dir)` - открытие директории
- `goUp()` - переход на уровень вверх
- `showNotification(message, type, duration)` - показ уведомления

### StateManager

- `getState()` - получить все состояние
- `get(key)` - получить значение по ключу
- `set(key, value)` - установить значение
- `setState(updates)` - установить несколько значений
- `on(event, callback)` - подписаться на событие
- `off(event, callback)` - отписаться от события

## Примеры

### Создание папки

```javascript
const fileManager = window.sfilesManager;
fileManager.createFolder('my-folder');
```

### Удаление файла

```javascript
const file = { opPath: 'path/to/file.jpg', name: 'file.jpg' };
fileManager.deleteFile(file);
```

### Подписка на события

```javascript
fileManager.stateManager.on('change:files', (files) => {
    console.log('Files updated:', files);
});
```

## Обратная совместимость

Старый код с Alpine.js продолжит работать, но рекомендуется мигрировать на новую архитектуру для лучшей поддерживаемости.
