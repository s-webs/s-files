import Alpine from 'alpinejs';

window.Alpine = Alpine;

function tinymceFilePicker() {
    const config = window.sfilesConfig || {
        baseUrl: '/s-files',
        filesUrl: '/s-files/files',
    };

    const translations = window.sfilesTranslations || { en: {}, ru: {} };

    return {
        // State
        currentPath: '',
        directories: [],
        files: [],
        breadcrumbs: [],
        selectedFile: null,
        loading: false,
        config: config,

        // Locale / i18n
        translations,
        locale: localStorage.getItem('sfiles_locale') || (window.sfilesDefaultLocale || 'en'),

        // Notifications
        notification: {
            show: false,
            message: '',
            type: 'info',
        },

        // Utils
        t(key, params = {}) {
            let s = this.translations?.[this.locale]?.[key] || this.translations?.en?.[key] || key;
            for (const [k, v] of Object.entries(params)) {
                s = s.replace(new RegExp('\\{' + k + '\\}', 'g'), String(v));
            }
            return s;
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        buildPublicUrl(publicPathOrPath) {
            const p = String(publicPathOrPath || '').replace(/^\/+/, '');
            return '/' + p;
        },

        showNotification(message, type = 'info', duration = 3000) {
            this.notification = {
                show: true,
                message,
                type
            };

            setTimeout(() => {
                this.notification.show = false;
            }, duration);
        },

        closeContextMenus() {
            // Для TinyMCE версии контекстные меню не нужны
        },

        closeAllPopups() {
            // Для TinyMCE версии попапы не нужны
        },

        async apiFetch(url, options = {}) {
            const headers = options.headers || {};
            const res = await fetch(url, {
                credentials: 'same-origin',
                ...options,
                headers: {
                    ...headers,
                }
            });

            const text = await res.text();
            let data = null;
            try {
                data = text ? JSON.parse(text) : null;
            } catch (e) {
                data = {raw: text};
            }

            if (!res.ok) {
                let msg = data?.message || data?.error || `HTTP ${res.status}`;
                throw new Error(msg);
            }

            return data;
        },

        // Data loading
        async fetchFiles() {
            this.loading = true;

            try {
                const url = `${this.config.filesUrl}?path=${encodeURIComponent(this.currentPath)}`;
                const data = await this.apiFetch(url);

                this.directories = Array.isArray(data?.directories) ? data.directories : [];

                this.files = (Array.isArray(data?.files) ? data.files : []).map(f => {
                    const publicPath = f.public_path || f.path || '';
                    const opPath = f.disk_path || f.diskPath || publicPath;
                    return {
                        name: f.name,
                        size: Number(f.size) || 0,
                        publicPath,
                        opPath
                    };
                });

                this.updateBreadcrumbs();
            } catch (e) {
                console.error('Error fetching files:', e);
                this.showNotification(e?.message || this.t('error_fetching'), 'error');
                this.directories = [];
                this.files = [];
                this.updateBreadcrumbs();
            } finally {
                this.loading = false;
            }
        },

        updateBreadcrumbs() {
            const parts = this.currentPath.split('/').filter(Boolean);
            this.breadcrumbs = ['root', ...parts];
        },

        goToBreadcrumb(index) {
            this.selectedFile = null;
            this.currentPath = index === 0 ? '' : this.breadcrumbs.slice(1, index + 1).join('/');
            this.fetchFiles();
        },

        openDirectory(dir) {
            this.selectedFile = null;
            this.currentPath = dir || '';
            this.fetchFiles();
        },

        goUp() {
            this.selectedFile = null;
            if (this.currentPath === '') return;
            const parts = this.currentPath.split('/');
            parts.pop();
            this.currentPath = parts.join('/');
            this.fetchFiles();
        },

        // File helpers
        isImage(file) {
            return /\.(jpg|jpeg|png|gif|webp|svg|bmp)$/i.test(file.name);
        },

        getFileIcon(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const icons = {
                pdf: 'ph ph-file-pdf text-red-500',
                doc: 'ph ph-file-doc text-blue-500',
                docx: 'ph ph-file-doc text-blue-500',
                xlsx: 'ph ph-file-xls text-green-500',
                pptx: 'ph ph-file-ppt text-orange-500',
                zip: 'ph ph-file-zip text-gray-500',
                default: 'ph ph-file text-gray-500'
            };
            return icons[ext] || icons.default;
        },

        formatFileSize(size) {
            const s = Number(size) || 0;
            if (s < 1024) return s + ' B';
            if (s < 1048576) return (s / 1024).toFixed(2) + ' KB';
            if (s < 1073741824) return (s / 1048576).toFixed(2) + ' MB';
            return (s / 1073741824).toFixed(2) + ' GB';
        },

        fileHref(file) {
            return this.buildPublicUrl(file.publicPath);
        },

        // TinyMCE integration
        selectFile() {
            if (!this.selectedFile) {
                this.showNotification(this.t('select_file_first'), 'warning');
                return;
            }

            const fileUrl = this.fileHref(this.selectedFile);
            
            // Проверяем, открыт ли файловый менеджер в iframe или popup
            if (window.opener && !window.opener.closed) {
                // Если открыт в popup - передаем URL родительскому окну
                if (window.opener.tinymceFilePickerCallback) {
                    window.opener.tinymceFilePickerCallback(fileUrl, {
                        text: this.selectedFile.name,
                        title: this.selectedFile.name
                    });
                }
                window.close();
            } else if (window.parent && window.parent !== window) {
                // Если открыт в iframe - передаем URL родительскому окну
                if (window.parent.tinymceFilePickerCallback) {
                    window.parent.tinymceFilePickerCallback(fileUrl, {
                        text: this.selectedFile.name,
                        title: this.selectedFile.name
                    });
                }
                // Закрываем iframe (если есть функция для этого)
                if (window.parent.closeTinyMCEFilePicker) {
                    window.parent.closeTinyMCEFilePicker();
                }
            } else {
                // Fallback: используем postMessage
                window.postMessage({
                    type: 'tinymce-file-selected',
                    url: fileUrl,
                    meta: {
                        text: this.selectedFile.name,
                        title: this.selectedFile.name
                    }
                }, '*');
            }
        },

        closePicker() {
            if (window.opener && !window.opener.closed) {
                window.close();
            } else if (window.parent && window.parent !== window) {
                if (window.parent.closeTinyMCEFilePicker) {
                    window.parent.closeTinyMCEFilePicker();
                }
            }
        },

        // Init
        init() {
            this.fetchFiles();
        },
    };
}

window.tinymceFilePicker = tinymceFilePicker;
Alpine.start();
