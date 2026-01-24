<?php

namespace Swebs\Sfiles\Helpers;

class TinyMCEHelper
{
    /**
     * Преобразует конфигурацию TinyMCE для использования с S-Files
     * Заменяет строку 'sfiles' в file_picker_callback на специальный маркер
     * 
     * @param array $config Конфигурация TinyMCE
     * @return array Преобразованная конфигурация
     */
    public static function prepareConfig(array $config): array
    {
        // Если в callbacks есть file_picker_callback со значением 'sfiles'
        if (isset($config['callbacks']['file_picker_callback']) && 
            $config['callbacks']['file_picker_callback'] === 'sfiles') {
            
            // Заменяем на специальный маркер, который будет обработан JavaScript
            $config['callbacks']['file_picker_callback'] = '__SFILES_INTEGRATION__';
            
            // Добавляем настройки для S-Files, если они не указаны
            if (!isset($config['sfiles'])) {
                $config['sfiles'] = [
                    'base_url' => route('sfiles.index'),
                    'width' => 900,
                    'height' => 600,
                ];
            } else {
                // Убеждаемся, что base_url установлен
                if (!isset($config['sfiles']['base_url'])) {
                    $config['sfiles']['base_url'] = route('sfiles.index');
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Генерирует JavaScript код для инициализации TinyMCE с S-Files интеграцией
     * 
     * @param array $config Конфигурация TinyMCE
     * @return string JavaScript код
     */
    public static function generateScript(array $config): string
    {
        $config = self::prepareConfig($config);
        
        // Преобразуем конфигурацию в JSON
        $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Генерируем JavaScript код
        $script = <<<JS
(function() {
    // Загружаем конфигурацию
    var config = {$jsonConfig};
    
    // Если используется S-Files интеграция
    if (config.callbacks && config.callbacks.file_picker_callback === '__SFILES_INTEGRATION__') {
        // Проверяем, что скрипт интеграции загружен
        if (typeof sfilesTinyMCEPicker === 'undefined') {
            console.error('S-Files TinyMCE integration script is not loaded. Please include: ' + config.sfiles.base_url + '/assets/js/tinymce-integration.js');
        } else {
            // Заменяем маркер на реальную функцию
            config.callbacks.file_picker_callback = function(callback, value, meta) {
                // Определяем тип файла на основе meta
                var fileType = 'file';
                if (meta.filetype === 'image') {
                    fileType = 'image';
                } else if (meta.filetype === 'media') {
                    fileType = 'media';
                }
                
                // Используем S-Files picker
                var picker = sfilesTinyMCEPicker({
                    baseUrl: config.sfiles.base_url || '/s-files',
                    width: config.sfiles.width || 900,
                    height: config.sfiles.height || 600,
                    type: fileType
                });
                
                // Вызываем picker
                picker(callback, value, meta);
            };
        }
    }
    
    // Преобразуем callbacks в правильный формат для TinyMCE
    if (config.callbacks) {
        Object.keys(config.callbacks).forEach(function(key) {
            config[key] = config.callbacks[key];
        });
        delete config.callbacks;
    }
    
    // Удаляем sfiles из конфигурации перед передачей в TinyMCE
    delete config.sfiles;
    
    // Инициализируем TinyMCE
    if (typeof tinymce !== 'undefined') {
        tinymce.init(config);
    } else {
        console.error('TinyMCE is not loaded');
    }
})();
JS;
        
        return $script;
    }
}
