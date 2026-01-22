import Alpine from 'alpinejs';
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';
import Compressor from 'compressorjs';

window.Alpine = Alpine;

function fileManager() {
    // Получаем конфигурацию из window (устанавливается в blade шаблоне)
    const config = window.sfilesConfig || {
        baseUrl: '/s-files',
        uploadUrl: '/s-files/upload',
        filesUrl: '/s-files/files',
        createFolderUrl: '/s-files/create-folder',
        deleteUrl: '/s-files/delete',
        deleteFolderUrl: '/s-files/delete-folder',
        renameUrl: '/s-files/rename',
        downloadFolderUrl: '/s-files/download-folder',
        downloadFilesUrl: '/s-files/download-files',
    };

    return {
        // 1) State
        currentPath: '',
        directories: [],
        files: [],
        allFiles: [], // Все файлы для пагинации
        newFolder: '',
        breadcrumbs: [],
        dropzoneInstance: null,
        dropzoneModalInstance: null,
        selectedFiles: [],
        config: config, // Сохраняем конфигурацию

        viewMode: 'grid',
        searchQuery: '',

        loading: false,
        operationLoading: false, // Для операций (delete, rename и т.д.)
        lastError: null,
        totalProgress: 0,
        uploadProgress: 0,
        isUploading: false,

        // Пагинация
        pagination: {
            enabled: true,
            currentPage: 1,
            perPage: 50,
            total: 0,
            totalPages: 1,
        },

        // Кэш
        cache: new Map(),
        cacheEnabled: true,

        // Уведомления
        notification: {
            show: false,
            message: '',
            type: 'info', // info, success, error, warning
        },

        // Drag & Drop
        dragOver: false,
        draggedFiles: [],

        contextMenu: {
            show: false,
            x: 0,
            y: 0,
            dir: ''
        },

        fileContextMenu: {
            show: false,
            x: 0,
            y: 0,
            file: null
        },

        previewModal: {
            show: false,
            url: '',
            type: ''
        },

        renameModal: {
            show: false,
            type: 'file',
            path: '',
            displayName: '',
            newName: ''
        },

        uploadModal: {
            show: false
        },

        openUploadModal() {
            this.uploadModal.show = true;
            // Инициализируем dropzone на модальном окне после его открытия
            setTimeout(() => {
                const uploadZoneModal = document.getElementById("uploadZoneModal");
                if (uploadZoneModal && !this.dropzoneModalInstance) {
                    this.dropzoneModalInstance = this.createDropzone(uploadZoneModal, true);
                }
            }, 100);
        },

        // 2) Computed
        get filteredFiles() {
            if (!this.searchQuery.trim()) {
                return this.paginatedFiles;
            }
            const q = this.searchQuery.trim().toLowerCase();
            return this.paginatedFiles.filter(file =>
                file.name.toLowerCase().includes(q) ||
                (file.name && file.name.toLowerCase().includes(q))
            );
        },

        get paginatedFiles() {
            if (!this.pagination.enabled) {
                return this.files;
            }

            const start = (this.pagination.currentPage - 1) * this.pagination.perPage;
            const end = start + this.pagination.perPage;
            return this.files.slice(start, end);
        },

        get filteredDirectories() {
            if (!this.searchQuery.trim()) return this.directories;
            const q = this.searchQuery.trim().toLowerCase();
            return this.directories.filter(dir => {
                const name = dir.split('/').pop().toLowerCase();
                return name.includes(q);
            });
        },

        get allFilesSelected() {
            return this.filteredFiles.length > 0 && this.selectedFiles.length === this.filteredFiles.length;
        },

        get selectedFilesCount() {
            return this.selectedFiles.length;
        },

        get selectedFilesSize() {
            return this.files
                .filter(file => this.selectedFiles.includes(file.opPath))
                .reduce((sum, file) => sum + (Number(file.size) || 0), 0);
        },

        get totalFilesCount() {
            return this.files.length;
        },

        get totalFilesSize() {
            return this.files.reduce((sum, file) => sum + (Number(file.size) || 0), 0);
        },

        // 3) Utils
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
            this.contextMenu.show = false;
            this.fileContextMenu.show = false;
        },

        closeAllPopups() {
            this.closeContextMenus();
            this.previewModal.show = false;
            this.renameModal.show = false;
        },

        setMenuPosition(menuObj, clientX, clientY) {
            const menuW = 190;
            const menuH = 200; // Увеличиваем для учета всех элементов

            // Позиционируем меню рядом с курсором, но не слишком далеко
            let x = clientX;
            let y = clientY;

            // Проверяем границы экрана
            const maxX = window.innerWidth - menuW - 10;
            const maxY = window.innerHeight - menuH - 10;

            // Если меню выходит за правую границу, позиционируем слева от курсора
            if (x > maxX) {
                x = clientX - menuW - 10;
            }

            // Если меню выходит за нижнюю границу, позиционируем выше курсора
            if (y > maxY) {
                y = clientY - menuH - 10;
            }

            // Минимальные отступы от краев
            menuObj.x = Math.max(10, Math.min(x, maxX));
            menuObj.y = Math.max(10, Math.min(y, maxY));
        },

        openDirContextMenu(dir, event) {
            event.preventDefault();
            this.fileContextMenu.show = false;

            this.contextMenu.dir = dir;
            this.setMenuPosition(this.contextMenu, event.clientX + 5, event.clientY + 5);
            this.contextMenu.show = true;
        },

        openFileContextMenu(file, e) {
            e.preventDefault();
            e.stopPropagation();

            this.contextMenu.show = false; // если есть другое меню
            this.fileContextMenu.file = file;

            // Сначала показываем (чтобы можно было измерить размеры)
            this.fileContextMenu.show = true;

            this.$nextTick(() => {
                const el = this.$refs.fileCtxMenu;
                const rect = el.getBoundingClientRect();

                let x = e.clientX + 6;
                let y = e.clientY + 6;

                // Правый/нижний край
                if (x + rect.width > window.innerWidth - 10) {
                    x = e.clientX - rect.width - 6;
                }
                if (y + rect.height > window.innerHeight - 10) {
                    y = e.clientY - rect.height - 6;
                }

                // Защита от выхода за границы
                this.fileContextMenu.x = Math.max(10, Math.min(x, window.innerWidth - rect.width - 10));
                this.fileContextMenu.y = Math.max(10, Math.min(y, window.innerHeight - rect.height - 10));
            });
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

                // Обработка rate limiting
                if (res.status === 429) {
                    msg = data?.rate_limit || 'Слишком много запросов. Попробуйте позже.';
                }

                throw new Error(msg);
            }

            return data;
        },

        // 4) Data loading / navigation with cache
        async fetchFiles(page = 1) {
            this.loading = true;
            this.lastError = null;
            this.closeContextMenus();

            const cacheKey = `files:${this.currentPath}:${page}`;

            // Проверка кэша
            if (this.cacheEnabled && this.cache.has(cacheKey)) {
                const cached = this.cache.get(cacheKey);
                if (Date.now() - cached.timestamp < 300000) { // 5 минут
                    this.directories = cached.directories;
                    this.files = cached.files;
                    this.allFiles = cached.allFiles;
                    this.pagination = cached.pagination || this.pagination;
                    this.updateBreadcrumbs();
                    this.loading = false;
                    return;
                }
            }

            try {
                const url = `${this.config.filesUrl}?path=${encodeURIComponent(this.currentPath)}&page=${page}`;
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

                this.allFiles = [...this.files];

                // Обновление пагинации
                if (data?.pagination) {
                    this.pagination = {
                        enabled: true,
                        currentPage: data.pagination.current_page || page,
                        perPage: data.pagination.per_page || 50,
                        total: data.pagination.total || this.files.length,
                        totalPages: data.pagination.total_pages || 1,
                    };
                } else {
                    this.pagination.currentPage = page;
                    this.pagination.total = this.files.length;
                    this.pagination.totalPages = Math.ceil(this.files.length / this.pagination.perPage);
                }

                // Сохранение в кэш
                if (this.cacheEnabled) {
                    this.cache.set(cacheKey, {
                        directories: this.directories,
                        files: this.files,
                        allFiles: this.allFiles,
                        pagination: this.pagination,
                        timestamp: Date.now()
                    });
                }

                this.updateBreadcrumbs();
            } catch (e) {
                console.error('Error fetching files:', e);
                this.lastError = e?.message || 'Error fetching files';
                this.showNotification(this.lastError, 'error');
                this.directories = [];
                this.files = [];
                this.allFiles = [];
                this.updateBreadcrumbs();
            } finally {
                this.loading = false;
            }
        },

        clearCache(path = null) {
            if (path === null) {
                this.cache.clear();
            } else {
                // Очистка кэша для конкретного пути
                for (const key of this.cache.keys()) {
                    if (key.includes(`files:${path}:`)) {
                        this.cache.delete(key);
                    }
                }
            }
        },

        updateBreadcrumbs() {
            const parts = this.currentPath.split('/').filter(Boolean);
            this.breadcrumbs = ['root', ...parts];
        },

        goToBreadcrumb(index) {
            this.selectedFiles = [];
            this.closeContextMenus();

            this.currentPath = index === 0 ? '' : this.breadcrumbs.slice(1, index + 1).join('/');
            this.pagination.currentPage = 1;
            this.fetchFiles(1);
        },

        openDirectory(dir) {
            this.selectedFiles = [];
            this.closeContextMenus();

            this.currentPath = dir || '';
            this.pagination.currentPage = 1;
            this.fetchFiles(1);
        },

        goUp() {
            this.selectedFiles = [];
            this.closeContextMenus();

            if (this.currentPath === '') return;
            const parts = this.currentPath.split('/');
            parts.pop();
            this.currentPath = parts.join('/');
            this.pagination.currentPage = 1;
            this.fetchFiles(1);
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.totalPages) return;
            this.pagination.currentPage = page;
            this.fetchFiles(page);
        },

        // 5) Folder ops
        async createFolder() {
            const name = this.newFolder.trim();
            if (!name) return;

            this.operationLoading = true;

            const newPath = this.currentPath ? `${this.currentPath}/${name}` : name;

            try {
                await this.apiFetch(this.config.createFolderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify({path: newPath})
                });

                this.newFolder = '';
                this.clearCache(this.currentPath);
                this.showNotification('Папка успешно создана', 'success');
                await this.fetchFiles(this.pagination.currentPage);
            } catch (e) {
                console.error('Ошибка создания папки:', e);
                this.showNotification(e?.message || 'Ошибка создания папки', 'error');
            } finally {
                this.operationLoading = false;
            }
        },

        async deleteFolder(dir) {
            const target = String(dir || '').trim();
            if (!target) return;

            const folderName = target.split('/').pop() || target;
            const confirmed = confirm(`Вы уверены, что хотите удалить папку "${folderName}"?\n\nЭто действие нельзя отменить.`);

            if (!confirmed) return;

            this.operationLoading = true;

            try {
                await this.apiFetch(this.config.deleteFolderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify({path: target})
                });

                this.contextMenu.show = false;
                this.clearCache(this.currentPath);
                this.showNotification('Папка успешно удалена', 'success');
                await this.fetchFiles(this.pagination.currentPage);
            } catch (e) {
                console.error('Ошибка удаления папки:', e);
                this.showNotification(e?.message || 'Ошибка удаления папки', 'error');
            } finally {
                this.operationLoading = false;
            }
        },

        copyDirectoryLink(dir) {
            const target = String(dir || '').trim();
            if (!target) return;

            this.copyToClipboard(target, 'Путь скопирован: ' + target);
        },

        // Универсальная функция копирования с fallback
        copyToClipboard(text, successMessage = 'Скопировано в буфер обмена') {
            // Проверяем доступность Clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => this.showNotification(successMessage, 'success'))
                    .catch(err => {
                        console.error('Ошибка копирования через Clipboard API:', err);
                        // Fallback на старый метод
                        this.fallbackCopyToClipboard(text, successMessage);
                    });
            } else {
                // Используем fallback метод
                this.fallbackCopyToClipboard(text, successMessage);
            }
        },

        // Fallback метод копирования через временный textarea
        fallbackCopyToClipboard(text, successMessage) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (successful) {
                    this.showNotification(successMessage, 'success');
                } else {
                    this.showNotification('Не удалось скопировать. Попробуйте скопировать вручную.', 'error');
                }
            } catch (err) {
                document.body.removeChild(textArea);
                console.error('Ошибка fallback копирования:', err);
                this.showNotification('Не удалось скопировать. Попробуйте скопировать вручную.', 'error');
            }
        },

        downloadFolder(dir) {
            const target = String(dir || '').trim();
            if (!target) return;

            this.closeContextMenus();
            window.location.href = `${this.config.downloadFolderUrl}?path=${encodeURIComponent(target)}`;
        },

        // Rename
        openRenameForFile(file) {
            if (!file) return;
            this.closeContextMenus();

            this.renameModal = {
                show: true,
                type: 'file',
                path: file.opPath,
                displayName: file.name,
                newName: file.name
            };
        },

        openRenameForDir(dir) {
            const target = String(dir || '').trim();
            if (!target) return;
            this.closeContextMenus();

            const name = target.split('/').filter(Boolean).pop() || target;

            this.renameModal = {
                show: true,
                type: 'dir',
                path: target,
                displayName: name,
                newName: name
            };
        },

        closeRenameModal() {
            this.renameModal.show = false;
        },

        async submitRename() {
            const newName = String(this.renameModal.newName || '').trim();
            if (!newName) {
                this.showNotification('Имя не может быть пустым', 'error');
                return;
            }

            this.operationLoading = true;

            try {
                await this.apiFetch(this.config.renameUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify({
                        type: this.renameModal.type,
                        path: this.renameModal.path,
                        new_name: newName
                    })
                });

                this.selectedFiles = [];
                this.renameModal.show = false;
                this.clearCache(this.currentPath);
                this.showNotification('Успешно переименовано', 'success');
                await this.fetchFiles(this.pagination.currentPage);
            } catch (e) {
                console.error('Ошибка переименования:', e);
                this.showNotification(e?.message || 'Ошибка переименования', 'error');
            } finally {
                this.operationLoading = false;
            }
        },

        // 6) File helpers / ops
        isImage(file) {
            return /\.(jpg|jpeg|png|gif|webp)$/i.test(file.name);
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

        previewFile(file) {
            this.fileContextMenu.show = false;

            const url = this.fileHref(file);
            const extension = file.name.split('.').pop().toLowerCase();
            const isWord = ['doc', 'docx'].includes(extension);

            this.previewModal = {
                show: true,
                url,
                type: isWord ? 'word' : this.isImage(file) ? 'image' : extension === 'pdf' ? 'pdf' : 'other'
            };
        },

        async deleteFile(file) {
            const confirmed = confirm(`Удалить файл "${file.name}"?\n\nЭто действие нельзя отменить.`);
            if (!confirmed) return;

            this.operationLoading = true;

            try {
                await this.apiFetch(this.config.deleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify({path: file.opPath})
                });

                this.clearCache(this.currentPath);
                this.showNotification('Файл успешно удален', 'success');
                await this.fetchFiles(this.pagination.currentPage);
                this.selectedFiles = this.selectedFiles.filter(p => p !== file.opPath);
            } catch (e) {
                console.error('Ошибка удаления файла:', e);
                this.showNotification(e?.message || 'Ошибка удаления файла', 'error');
            } finally {
                this.operationLoading = false;
            }
        },

        async deleteSelectedFiles() {
            const count = this.selectedFiles.length;
            if (!count) return;

            const confirmed = confirm(`Удалить ${count} выбранных файл(ов)?\n\nЭто действие нельзя отменить.`);
            if (!confirmed) return;

            this.operationLoading = true;
            const paths = [...this.selectedFiles];

            try {
                await Promise.all(paths.map(path =>
                    this.apiFetch('/s-files/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken()
                        },
                        body: JSON.stringify({path})
                    })
                ));

                this.selectedFiles = [];
                this.clearCache(this.currentPath);
                this.showNotification(`Успешно удалено файлов: ${count}`, 'success');
                await this.fetchFiles(this.pagination.currentPage);
            } catch (e) {
                console.error('Ошибка удаления нескольких файлов:', e);
                this.showNotification(e?.message || 'Ошибка удаления файлов', 'error');
            } finally {
                this.operationLoading = false;
            }
        },

        downloadSelectedFiles() {
            if (!this.selectedFiles.length) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.config.downloadFilesUrl;
            form.target = '_blank';
            form.style.display = 'none';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = this.csrfToken();
            form.appendChild(csrf);

            this.selectedFiles.forEach(p => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'paths[]';
                input.value = p;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            form.remove();
        },

        copyFileLink() {
            const file = this.fileContextMenu.file;
            if (!file) return;

            const url = this.fileHref(file);
            this.copyToClipboard(url, 'Ссылка скопирована: ' + url);
        },

        passFiles() {
            if (window.opener && !window.opener.closed) {
                window.opener.handleSelectedFiles(this.selectedFiles);
                window.close();
            } else {
                this.showNotification('Окно родителя закрыто. Невозможно передать файлы.', 'error');
            }
        },

        // 7) Selection
        toggleAllFiles(checked) {
            this.selectedFiles = checked ? this.filteredFiles.map(file => file.opPath) : [];
        },

        // 8) View
        toggleView(mode) {
            if (['grid', 'list'].includes(mode)) this.viewMode = mode;
        },

        // 9) Drag & Drop
        handleDragOver(event) {
            event.preventDefault();
            event.stopPropagation();
            this.dragOver = true;
        },

        handleDragLeave(event) {
            event.preventDefault();
            event.stopPropagation();

            // Проверяем, что мы действительно покинули область
            // relatedTarget может быть null или указывать на дочерний элемент
            const relatedTarget = event.relatedTarget;
            const currentTarget = event.currentTarget;

            // Если relatedTarget null или не является дочерним элементом, скрываем
            if (!relatedTarget || !currentTarget.contains(relatedTarget)) {
                // Используем небольшую задержку для предотвращения мигания
                setTimeout(() => {
                    // Проверяем еще раз, что мы действительно вне области
                    if (!this.dragOver) return;
                    this.dragOver = false;
                }, 50);
            }
        },

        handleDrop(event) {
            event.preventDefault();
            event.stopPropagation();

            // Сбрасываем состояние
            this.dragOver = false;
            this.dragOverCounter = 0;

            const files = Array.from(event.dataTransfer.files);
            if (files.length === 0) return;

            // Используем Dropzone для загрузки
            if (this.dropzoneInstance && this.dropzoneInstance.element) {
                try {
                    files.forEach(file => {
                        // Проверяем, что файл валидный
                        if (file && file.size > 0) {
                            this.dropzoneInstance.addFile(file);
                        }
                    });
                    this.showNotification(`Начинается загрузка ${files.length} файл(ов)`, 'info');
                } catch (error) {
                    console.error('Ошибка при добавлении файлов в Dropzone:', error);
                    this.showNotification('Ошибка при загрузке файлов. Попробуйте использовать кнопку "Загрузить файлы"', 'error');
                }
            } else {
                // Если dropzone не инициализирован, показываем ошибку
                this.showNotification('Ошибка: система загрузки не готова. Попробуйте использовать кнопку "Загрузить файлы"', 'error');
            }
        },

        // 10) Init
        init() {
            this.fetchFiles(1);

            const self = this;

            Dropzone.autoDiscover = false;

            if (this.dropzoneInstance) {
                this.dropzoneInstance.destroy();
            }
            if (this.dropzoneModalInstance) {
                this.dropzoneModalInstance.destroy();
            }

            // Инициализируем dropzone на скрытом элементе для drag & drop
            const uploadZoneHidden = document.getElementById("uploadZoneHidden");
            if (uploadZoneHidden) {
                this.dropzoneInstance = this.createDropzone(uploadZoneHidden, false);
            }
        },

        createDropzone(element, clickable) {
            const self = this;
            return new Dropzone(element, {
                url: self.config.uploadUrl,
                paramName: "file",
                maxFilesize: 10,
                withCredentials: true,
                timeout: 120000,
                parallelUploads: 3,
                addRemoveLinks: false,

                transformFile: function (file, done) {
                    if (file.type && file.type.startsWith('image/')) {
                        new Compressor(file, {
                            quality: 0.6,
                            maxWidth: 2560,
                            maxHeight: 2560,
                            convertSize: 500000,
                            success(result) {
                                done(result);
                            },
                            error(err) {
                                console.error('Compression error:', err);
                                self.showNotification('Ошибка сжатия изображения', 'warning');
                                done(file);
                            }
                        });
                    } else {
                        done(file);
                    }
                },

                renameFile: function (file) {
                    return file.name;
                },

                headers: {
                    'X-CSRF-TOKEN': self.csrfToken()
                },

                clickable: clickable,
                autoProcessQueue: true,

                init: function () {
                    this.on("sending", function (file, xhr, formData) {
                        formData.append("path", self.currentPath);
                        self.isUploading = true;
                        self.uploadProgress = 0;
                    });

                    this.on("uploadprogress", function (file, progress, bytesSent) {
                        self.uploadProgress = Math.round(progress);
                    });

                    this.on("totaluploadprogress", function (progress) {
                        self.uploadProgress = Math.round(progress);
                    });

                    this.on("success", function (file, response) {
                        self.showNotification(`Файл "${file.name}" успешно загружен`, 'success');
                        self.clearCache(self.currentPath);
                        self.fetchFiles(self.pagination.currentPage);
                    });

                    this.on("error", function (file, message, xhr) {
                        let errorMsg = 'Ошибка загрузки файла';

                        if (xhr && xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMsg = response.message || response.error || errorMsg;
                            } catch (e) {
                                errorMsg = xhr.responseText || errorMsg;
                            }
                        } else if (typeof message === 'string') {
                            errorMsg = message;
                        } else if (message && message.message) {
                            errorMsg = message.message;
                        }

                        self.showNotification(`Ошибка загрузки "${file.name}": ${errorMsg}`, 'error');
                        console.error("Dropzone error:", {
                            name: file?.name,
                            status: xhr?.status,
                            message,
                            response: xhr?.responseText
                        });
                    });

                    this.on("queuecomplete", function () {
                        self.isUploading = false;
                        setTimeout(() => {
                            self.uploadProgress = 0;
                        }, 500);
                    });

                    this.on("addedfile", function (file) {
                        // Показываем прогресс для каждого файла
                    });
                }
            });
        },
    };
}

window.fileManager = fileManager;
Alpine.start();
