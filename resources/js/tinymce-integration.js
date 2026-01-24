/**
 * TinyMCE Integration Helper for S-Files
 * 
 * Usage:
 * 
 * tinymce.init({
 *   selector: '#mytextarea',
 *   plugins: 'image',
 *   file_picker_callback: sfilesTinyMCEPicker({
 *     baseUrl: '/s-files', // Optional, defaults to '/s-files'
 *     width: 900,          // Optional, popup width
 *     height: 600          // Optional, popup height
 *   })
 * });
 */

(function() {
    'use strict';

    /**
     * Creates a file picker callback function for TinyMCE
     * 
     * @param {Object} options Configuration options
     * @param {string} options.baseUrl Base URL for the file manager (default: '/s-files')
     * @param {number} options.width Popup width in pixels (default: 900)
     * @param {number} options.height Popup height in pixels (default: 600)
     * @param {string} options.type File type filter: 'image', 'file', or 'media' (default: 'file')
     * @returns {Function} TinyMCE file_picker_callback function
     */
    window.sfilesTinyMCEPicker = function(options) {
        options = options || {};
        const baseUrl = options.baseUrl || '/s-files';
        const width = options.width || 900;
        const height = options.height || 600;
        const type = options.type || 'file';

        return function(callback, value, meta) {
            // Определяем тип файла на основе meta
            const fileType = meta.filetype || type;
            
            // URL для открытия файлового менеджера
            const pickerUrl = baseUrl + '/tinymce?type=' + encodeURIComponent(fileType);
            
            // Сохраняем callback для использования после выбора файла
            window.tinymceFilePickerCallback = function(url, meta) {
                callback(url, meta);
                delete window.tinymceFilePickerCallback;
            };

            // Функция для закрытия picker (если используется iframe)
            window.closeTinyMCEFilePicker = function() {
                if (window.tinymceFilePickerModal) {
                    window.tinymceFilePickerModal.close();
                    window.tinymceFilePickerModal = null;
                }
                delete window.closeTinyMCEFilePicker;
            };

            // Открываем файловый менеджер в popup окне
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;
            
            const popup = window.open(
                pickerUrl,
                'sfiles-tinymce-picker',
                'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + 
                ',toolbar=no,menubar=no,scrollbars=yes,resizable=yes,location=no,directories=no,status=no'
            );

            // Сохраняем ссылку на popup для возможного закрытия
            window.tinymceFilePickerModal = popup;

            // Если popup был заблокирован, пытаемся использовать iframe
            if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                // Fallback: создаем модальное окно с iframe
                createModalPicker(pickerUrl, callback);
            }
        };
    };

    /**
     * Creates a modal dialog with iframe as fallback if popup is blocked
     */
    function createModalPicker(url, callback) {
        // Проверяем, есть ли уже модальное окно
        let modal = document.getElementById('sfiles-tinymce-modal');
        if (modal) {
            modal.remove();
        }

        // Создаем модальное окно
        modal = document.createElement('div');
        modal.id = 'sfiles-tinymce-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        // Создаем контейнер для iframe
        const container = document.createElement('div');
        container.style.cssText = `
            position: relative;
            width: 90%;
            height: 90%;
            max-width: 1200px;
            max-height: 800px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        `;

        // Кнопка закрытия
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            font-size: 24px;
            cursor: pointer;
            border-radius: 50%;
            z-index: 1000000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        `;
        closeBtn.onmouseover = function() {
            this.style.background = 'rgba(0, 0, 0, 0.7)';
        };
        closeBtn.onmouseout = function() {
            this.style.background = 'rgba(0, 0, 0, 0.5)';
        };
        closeBtn.onclick = function() {
            modal.remove();
            delete window.tinymceFilePickerCallback;
        };

        // Создаем iframe
        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.style.cssText = `
            width: 100%;
            height: 100%;
            border: none;
        `;

        // Обработка сообщений от iframe
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'tinymce-file-selected') {
                if (window.tinymceFilePickerCallback) {
                    window.tinymceFilePickerCallback(event.data.url, event.data.meta);
                    delete window.tinymceFilePickerCallback;
                }
                modal.remove();
            }
        });

        container.appendChild(closeBtn);
        container.appendChild(iframe);
        modal.appendChild(container);
        document.body.appendChild(modal);

        // Закрытие по клику вне модального окна
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.remove();
                delete window.tinymceFilePickerCallback;
            }
        };
    }

    // Экспортируем функцию для использования в модульных системах
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = window.sfilesTinyMCEPicker;
    }
})();
