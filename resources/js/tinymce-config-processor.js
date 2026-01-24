/**
 * TinyMCE Config Processor для S-Files
 * 
 * Этот скрипт обрабатывает конфигурацию TinyMCE перед её использованием,
 * заменяя маркеры S-Files на реальные функции.
 * 
 * Использование:
 * 1. Подключите этот скрипт ПЕРЕД инициализацией TinyMCE
 * 2. Вызовите sfilesProcessTinyMCEConfig(config) перед tinymce.init()
 * 
 * Или используйте автоматическую интеграцию через tinymce-auto-integration.js
 */

(function() {
    'use strict';
    
    /**
     * Обрабатывает конфигурацию TinyMCE и заменяет маркеры S-Files на реальные функции
     * 
     * @param {Object|Array} config Конфигурация TinyMCE
     * @returns {Object|Array} Обработанная конфигурация
     */
    function processConfig(config) {
        if (!config) {
            return config;
        }
        
        // Обрабатываем массив конфигураций
        if (Array.isArray(config)) {
            return config.map(processConfig);
        }
        
        // Обрабатываем объект конфигурации
        if (typeof config !== 'object') {
            return config;
        }
        
        // Создаем копию конфигурации
        var processedConfig = JSON.parse(JSON.stringify(config));
        
        // Проверяем file_picker_callback в разных местах
        var filePickerValue = null;
        var filePickerLocation = null;
        
        if (processedConfig.callbacks && processedConfig.callbacks.file_picker_callback) {
            filePickerValue = processedConfig.callbacks.file_picker_callback;
            filePickerLocation = 'callbacks';
        } else if (processedConfig.file_picker_callback) {
            filePickerValue = processedConfig.file_picker_callback;
            filePickerLocation = 'root';
        }
        
        // Проверяем, является ли это маркером S-Files
        var isSfilesMarker = false;
        
        if (filePickerValue === 'sfiles' || 
            filePickerValue === '__SFILES__' ||
            (typeof filePickerValue === 'object' && filePickerValue !== null && 
             (filePickerValue.__sfiles__ === true || filePickerValue.__SFILES__ === true))) {
            isSfilesMarker = true;
        }
        
        // Если это маркер S-Files, заменяем на реальную функцию
        if (isSfilesMarker) {
            // Проверяем, что скрипт интеграции загружен
            if (typeof sfilesTinyMCEPicker === 'undefined') {
                console.error('S-Files TinyMCE integration script is not loaded. Please include: ' + 
                    (processedConfig.sfiles && processedConfig.sfiles.base_url || '/s-files') + 
                    '/assets/js/tinymce-integration.js');
                return processedConfig;
            }
            
            // Получаем настройки S-Files
            var sfilesConfig = processedConfig.sfiles || {};
            var baseUrl = sfilesConfig.base_url || window.sfilesBaseUrl || '/s-files';
            var width = sfilesConfig.width || 900;
            var height = sfilesConfig.height || 600;
            
            // Создаем функцию callback
            var filePickerCallback = function(callback, value, meta) {
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
            
            // Устанавливаем callback в нужное место
            if (filePickerLocation === 'callbacks') {
                processedConfig.callbacks.file_picker_callback = filePickerCallback;
            } else if (filePickerLocation === 'root') {
                processedConfig.file_picker_callback = filePickerCallback;
            }
            
            // Удаляем sfiles из конфигурации перед передачей в TinyMCE
            delete processedConfig.sfiles;
        }
        
        // Преобразуем callbacks в правильный формат для TinyMCE
        if (processedConfig.callbacks) {
            Object.keys(processedConfig.callbacks).forEach(function(key) {
                processedConfig[key] = processedConfig.callbacks[key];
            });
            delete processedConfig.callbacks;
        }
        
        return processedConfig;
    }
    
    // Экспортируем функцию
    window.sfilesProcessTinyMCEConfig = processConfig;
    
    // Если конфигурация уже передана через window, обрабатываем её
    if (window.tinymceConfig) {
        window.tinymceConfig = processConfig(window.tinymceConfig);
    }
})();
