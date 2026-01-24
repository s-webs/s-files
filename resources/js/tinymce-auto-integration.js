/**
 * TinyMCE Auto-Integration for S-Files
 * 
 * Этот скрипт автоматически подключает S-Files интеграцию к TinyMCE,
 * если в конфигурации указано 'sfiles' в file_picker_callback
 * 
 * Использование:
 * 1. Подключите этот скрипт после загрузки TinyMCE и конфигурации
 * 2. В конфигурации TinyMCE укажите:
 *    callbacks: {
 *      file_picker_callback: 'sfiles'
 *    }
 * 3. Опционально добавьте настройки:
 *    sfiles: {
 *      base_url: '/s-files',
 *      width: 900,
 *      height: 600
 *    }
 */

(function() {
    'use strict';
    
    /**
     * Обрабатывает конфигурацию TinyMCE и подключает S-Files интеграцию
     */
    function processTinyMCEConfig(config) {
        if (!config || typeof config !== 'object') {
            return config;
        }
        
        // Проверяем, используется ли S-Files интеграция
        var useSfiles = false;
        var filePickerCallback = null;
        
        // Проверяем различные варианты маркеров для S-Files интеграции
        var filePickerValue = null;
        
        // Проверяем в callbacks
        if (config.callbacks && config.callbacks.file_picker_callback) {
            filePickerValue = config.callbacks.file_picker_callback;
        }
        // Или проверяем напрямую в file_picker_callback
        else if (config.file_picker_callback) {
            filePickerValue = config.file_picker_callback;
        }
        
        // Проверяем различные форматы маркера
        if (filePickerValue === 'sfiles' || 
            filePickerValue === '__SFILES__' ||
            (typeof filePickerValue === 'object' && filePickerValue !== null && 
             (filePickerValue.__sfiles__ === true || filePickerValue.__SFILES__ === true))) {
            useSfiles = true;
        }
        
        if (useSfiles) {
            // Проверяем, что скрипт интеграции загружен
            if (typeof sfilesTinyMCEPicker === 'undefined') {
                console.error('S-Files TinyMCE integration script is not loaded. Please include the integration script before initializing TinyMCE.');
                return config;
            }
            
            // Получаем настройки S-Files
            var sfilesConfig = config.sfiles || {};
            var baseUrl = sfilesConfig.base_url || window.sfilesBaseUrl || '/s-files';
            var width = sfilesConfig.width || 900;
            var height = sfilesConfig.height || 600;
            
            // Создаем функцию callback
            filePickerCallback = function(callback, value, meta) {
                // Определяем тип файла на основе meta
                var fileType = 'file';
                if (meta && meta.filetype === 'image') {
                    fileType = 'image';
                } else if (meta && meta.filetype === 'media') {
                    fileType = 'media';
                }
                
                // Используем S-Files picker
                var picker = sfilesTinyMCEPicker({
                    baseUrl: baseUrl,
                    width: width,
                    height: height,
                    type: fileType
                });
                
                // Вызываем picker
                picker(callback, value, meta);
            };
            
            // Устанавливаем callback
            if (config.callbacks) {
                config.callbacks.file_picker_callback = filePickerCallback;
            } else {
                config.file_picker_callback = filePickerCallback;
            }
            
            // Удаляем sfiles из конфигурации перед передачей в TinyMCE
            delete config.sfiles;
        }
        
        // Преобразуем callbacks в правильный формат для TinyMCE
        if (config.callbacks) {
            Object.keys(config.callbacks).forEach(function(key) {
                config[key] = config.callbacks[key];
            });
            delete config.callbacks;
        }
        
        return config;
    }
    
    /**
     * Переопределяем tinymce.init для автоматической обработки конфигурации
     */
    var originalInit = null;
    
    function initTinyMCE() {
        if (typeof tinymce === 'undefined') {
            // Если TinyMCE еще не загружен, ждем
            setTimeout(initTinyMCE, 100);
            return;
        }
        
        // Сохраняем оригинальную функцию
        if (!originalInit) {
            originalInit = tinymce.init;
        }
        
        // Переопределяем tinymce.init
        tinymce.init = function(config) {
            // Обрабатываем конфигурацию
            if (Array.isArray(config)) {
                config = config.map(processTinyMCEConfig);
            } else if (typeof config === 'object') {
                config = processTinyMCEConfig(config);
            }
            
            // Вызываем оригинальную функцию
            return originalInit.call(tinymce, config);
        };
    }
    
    // Запускаем инициализацию
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTinyMCE);
    } else {
        initTinyMCE();
    }
    
    // Экспортируем функцию для ручного использования
    window.sfilesProcessTinyMCEConfig = processTinyMCEConfig;
})();
