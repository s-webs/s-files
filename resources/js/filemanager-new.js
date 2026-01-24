import { FileManager } from './components/FileManager.js';
import { PreviewModal } from './components/PreviewModal.js';
import { RenameModal } from './components/RenameModal.js';
import { FileContextMenu } from './components/FileContextMenu.js';
import { FolderContextMenu } from './components/FolderContextMenu.js';
import { FileList } from './components/FileList.js';
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';
import Compressor from 'compressorjs';

/**
 * Адаптер для связи новой архитектуры с существующими Blade шаблонами
 */
class TemplateAdapter {
    constructor(fileManager) {
        this.fileManager = fileManager;
        this.setupBindings();
    }

    setupBindings() {
        // Подписка на изменения состояния для обновления UI
        this.fileManager.stateManager.on('change', () => {
            this.updateUI();
        });

        // Привязка событий к элементам
        this.bindEvents();
    }

    bindEvents() {
        const root = this.fileManager.element;

        // Поиск
        const searchInput = root.querySelector('[data-search]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.fileManager.stateManager.set('searchQuery', e.target.value);
            });
        }

        // Переключение вида
        root.querySelectorAll('[data-view-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const mode = btn.getAttribute('data-view-toggle');
                this.fileManager.toggleView(mode);
            });
        });

        // Создание папки
        const newFolderInput = root.querySelector('[data-new-folder-input]');
        const newFolderBtn = root.querySelector('[data-new-folder-btn]');
        if (newFolderInput && newFolderBtn) {
            newFolderBtn.addEventListener('click', () => {
                const name = newFolderInput.value;
                this.fileManager.createFolder(name);
                newFolderInput.value = '';
            });
            newFolderInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    newFolderBtn.click();
                }
            });
        }

        // Выбор всех файлов
        const selectAllCheckbox = root.querySelector('[data-select-all]');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.fileManager.toggleAllFiles(e.target.checked);
            });
        }

        // Удаление выбранных файлов
        const deleteSelectedBtn = root.querySelector('[data-delete-selected]');
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', () => {
                this.fileManager.deleteSelectedFiles();
            });
        }

        // Скачивание выбранных файлов
        const downloadSelectedBtn = root.querySelector('[data-download-selected]');
        if (downloadSelectedBtn) {
            downloadSelectedBtn.addEventListener('click', () => {
                this.fileManager.downloadSelectedFiles();
            });
        }

        // Назад
        const backBtn = root.querySelector('[data-back]');
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                this.fileManager.goUp();
            });
        }

        // Breadcrumbs
        root.querySelectorAll('[data-breadcrumb]').forEach((el, index) => {
            el.addEventListener('click', () => {
                this.fileManager.goToBreadcrumb(index);
            });
        });

        // Файлы и папки
        root.querySelectorAll('[data-file]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const fileData = JSON.parse(el.getAttribute('data-file'));
                this.fileManager.selectFile(fileData);
            });
            el.addEventListener('dblclick', (e) => {
                e.preventDefault();
                const fileData = JSON.parse(el.getAttribute('data-file'));
                this.fileManager.openFileInNewTab(fileData);
            });
            el.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                const fileData = JSON.parse(el.getAttribute('data-file'));
                this.openFileContextMenu(fileData, e);
            });
        });

        root.querySelectorAll('[data-folder]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const dir = el.getAttribute('data-folder');
                this.fileManager.openDirectory(dir);
            });
            el.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                const dir = el.getAttribute('data-folder');
                this.openFolderContextMenu(dir, e);
            });
        });
    }

    updateUI() {
        const state = this.fileManager.getState();
        const root = this.fileManager.element;

        // Обновление уведомлений
        this.updateNotification(state.notification);

        // Обновление индикатора загрузки
        this.updateLoading(state.loading, state.operationLoading);

        // Обновление drag & drop зоны
        this.updateDragOver(state.dragOver);

        // Обновление breadcrumbs
        this.updateBreadcrumbs(state.breadcrumbs);

        // Обновление пагинации
        this.updatePagination(state.pagination);

        // Обновление счетчиков выбранных файлов
        this.updateSelectedCount(state.selectedFiles, state.files);

        // Обновление переключателя вида
        this.updateViewToggle(state.viewMode);
    }

    updateViewToggle(viewMode) {
        const root = this.fileManager.element;
        root.querySelectorAll('[data-view-toggle]').forEach(btn => {
            const mode = btn.getAttribute('data-view-toggle');
            if (mode === viewMode) {
                btn.className = 'px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2 font-medium bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg';
            } else {
                btn.className = 'px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2 font-medium bg-white text-gray-600 hover:bg-gray-100';
            }
        });
    }

    updateNotification(notification) {
        const notificationEl = this.fileManager.element.querySelector('[data-notification]');
        if (!notificationEl) return;

        if (notification.show) {
            notificationEl.classList.remove('hidden');
            notificationEl.style.display = 'flex';
            
            const messageEl = notificationEl.querySelector('[data-notification-message]');
            if (messageEl) {
                messageEl.textContent = notification.message;
            }

            const iconEl = notificationEl.querySelector('[data-notification-icon]');
            if (iconEl) {
                const icons = {
                    success: 'ph ph-check-circle',
                    error: 'ph ph-x-circle',
                    warning: 'ph ph-warning',
                    info: 'ph ph-info'
                };
                iconEl.className = `text-xl ${icons[notification.type] || icons.info}`;
            }

            // Обновляем классы для типа уведомления
            const container = notificationEl.querySelector('div');
            if (container) {
                container.className = `p-4 rounded-xl shadow-2xl backdrop-blur-sm border ${this.getNotificationClasses(notification.type)}`;
            }

            // Кнопка закрытия
            const closeBtn = notificationEl.querySelector('[data-notification-close]');
            if (closeBtn) {
                closeBtn.onclick = () => {
                    this.fileManager.stateManager.set('notification', {
                        show: false,
                        message: '',
                        type: 'info'
                    });
                };
            }
        } else {
            notificationEl.style.display = 'none';
            notificationEl.classList.add('hidden');
        }
    }

    getNotificationClasses(type) {
        const classes = {
            success: 'bg-gradient-to-r from-green-500 to-emerald-600 text-white border-green-400',
            error: 'bg-gradient-to-r from-red-500 to-rose-600 text-white border-red-400',
            warning: 'bg-gradient-to-r from-yellow-500 to-amber-600 text-white border-yellow-400',
            info: 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white border-blue-400'
        };
        return classes[type] || classes.info;
    }

    updateLoading(loading, operationLoading) {
        const loadingEl = this.fileManager.element.querySelector('[data-loading]');
        if (loadingEl) {
            loadingEl.style.display = loading ? 'flex' : 'none';
        }

        const operationLoadingEl = this.fileManager.element.querySelector('[data-operation-loading]');
        if (operationLoadingEl) {
            operationLoadingEl.style.display = operationLoading ? 'flex' : 'none';
        }
    }

    updateDragOver(dragOver) {
        const dragOverEl = this.fileManager.element.querySelector('[data-drag-over]');
        if (dragOverEl) {
            dragOverEl.style.display = dragOver ? 'flex' : 'none';
        }
    }

    updateFileList(state) {
        // Обновление списка файлов будет происходить через рендеринг компонентов
        // Здесь можно добавить логику для обновления существующих элементов
    }

    updateBreadcrumbs(breadcrumbs) {
        const breadcrumbsEl = this.fileManager.element.querySelector('[data-breadcrumbs]');
        if (!breadcrumbsEl) return;

        breadcrumbsEl.innerHTML = breadcrumbs.map((crumb, index) => {
            const name = index === 0 ? '🏠' : crumb;
            return `<span data-breadcrumb="${index}" class="cursor-pointer hover:text-blue-600">${name}</span>`;
        }).join(' > ');
    }

    updatePagination(pagination) {
        const paginationEl = this.fileManager.element.querySelector('[data-pagination]');
        if (!paginationEl || !pagination.enabled || pagination.totalPages <= 1) {
            if (paginationEl) paginationEl.style.display = 'none';
            return;
        }

        paginationEl.style.display = 'flex';
        // Обновление пагинации будет происходить через рендеринг
    }

    updateSelectedCount(selectedFiles, allFiles) {
        const countEl = this.fileManager.element.querySelector('[data-selected-count]');
        if (countEl) {
            countEl.textContent = selectedFiles.length;
        }

        const sizeEl = this.fileManager.element.querySelector('[data-selected-size]');
        if (sizeEl) {
            const totalSize = allFiles
                .filter(file => selectedFiles.includes(file.opPath))
                .reduce((sum, file) => sum + (Number(file.size) || 0), 0);
            sizeEl.textContent = this.fileManager.Utils.formatFileSize(totalSize);
        }
    }

    openFileContextMenu(file, event) {
        const x = event.clientX;
        const y = event.clientY;
        this.fileManager.stateManager.set('fileContextMenu', {
            show: true,
            x,
            y,
            file
        });
    }

    openFolderContextMenu(dir, event) {
        const x = event.clientX;
        const y = event.clientY;
        this.fileManager.stateManager.set('contextMenu', {
            show: true,
            x,
            y,
            dir
        });
    }
}

/**
 * Инициализация файлового менеджера
 */
export function initFileManager() {
    const rootElement = document.querySelector('[data-file-manager]');
    if (!rootElement) {
        console.warn('File manager root element not found. Looking for default container...');
        // Попытка найти контейнер по классу или ID
        const fallback = document.querySelector('.file-manager-container') || 
                        document.querySelector('#file-manager') ||
                        document.body;
        if (fallback === document.body) {
            console.error('File manager container not found');
            return null;
        }
        fallback.setAttribute('data-file-manager', '');
        return initFileManager();
    }

    const fileManager = new FileManager(rootElement);
    
    // Инициализация компонентов
    const fileListEl = rootElement.querySelector('[data-file-list]');
    let fileList = null;
    if (fileListEl) {
        fileList = new FileList(fileListEl, fileManager);
        // Подписываемся на изменения файлов для обновления списка
        fileManager.stateManager.on('change:files', () => {
            if (fileList) fileList.render();
        });
        fileManager.stateManager.on('filesLoaded', () => {
            if (fileList) fileList.render();
        });
    }

    const previewModalEl = rootElement.querySelector('[data-preview-modal]');
    if (previewModalEl) {
        new PreviewModal(previewModalEl, fileManager);
    }

    const renameModalEl = rootElement.querySelector('[data-rename-modal]');
    if (renameModalEl) {
        new RenameModal(renameModalEl, fileManager);
    }

    const fileContextMenuEl = rootElement.querySelector('[data-file-context-menu]');
    if (fileContextMenuEl) {
        new FileContextMenu(fileContextMenuEl, fileManager);
    }

    const folderContextMenuEl = rootElement.querySelector('[data-folder-context-menu]');
    if (folderContextMenuEl) {
        new FolderContextMenu(folderContextMenuEl, fileManager);
    }
    
    // Создаем адаптер для связи с шаблонами
    const adapter = new TemplateAdapter(fileManager);

    // Инициализация Dropzone
    initDropzone(fileManager);

    // Загрузка файлов после небольшой задержки для инициализации компонентов
    setTimeout(() => {
        fileManager.fetchFiles(1);
    }, 200);

    // Экспортируем в window для глобального доступа
    window.sfilesManager = fileManager;
    window.sfilesAdapter = adapter;

    return fileManager;
}

/**
 * Инициализация Dropzone для загрузки файлов
 */
function initDropzone(fileManager) {
    Dropzone.autoDiscover = false;

    const uploadZoneHidden = document.getElementById('uploadZoneHidden');
    if (uploadZoneHidden) {
        fileManager.dropzoneInstance = createDropzone(uploadZoneHidden, false, fileManager);
    }

    const uploadZoneModal = document.getElementById('uploadZoneModal');
    if (uploadZoneModal) {
        fileManager.on('uploadModal:open', () => {
            if (!fileManager.dropzoneModalInstance) {
                fileManager.dropzoneModalInstance = createDropzone(uploadZoneModal, true, fileManager);
            }
        });
    }
}

/**
 * Создание экземпляра Dropzone
 */
function createDropzone(element, clickable, fileManager) {
    const config = fileManager.options.config;
    
    return new Dropzone(element, {
        url: config.uploadUrl,
        paramName: 'file',
        maxFilesize: 10,
        withCredentials: true,
        timeout: 120000,
        parallelUploads: 3,
        addRemoveLinks: false,
        clickable: clickable,
        autoProcessQueue: true,

        transformFile: function(file, done) {
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
                        fileManager.showNotification(fileManager.t('image_compress_error'), 'warning');
                        done(file);
                    }
                });
            } else {
                done(file);
            }
        },

        headers: {
            'X-CSRF-TOKEN': fileManager.api.getCsrfToken()
        },

        init: function() {
            this.on('sending', function(file, xhr, formData) {
                formData.append('path', fileManager.get('currentPath'));
                fileManager.stateManager.set('isUploading', true);
                fileManager.stateManager.set('uploadProgress', 0);
            });

            this.on('uploadprogress', function(file, progress) {
                fileManager.stateManager.set('uploadProgress', Math.round(progress));
            });

            this.on('totaluploadprogress', function(progress) {
                fileManager.stateManager.set('uploadProgress', Math.round(progress));
            });

            this.on('success', function(file, response) {
                fileManager.showNotification(
                    fileManager.t('file_uploaded', { name: file.name }),
                    'success'
                );
                fileManager.clearCache(fileManager.get('currentPath'));
                fileManager.fetchFiles(fileManager.get('pagination').currentPage);
            });

            this.on('error', function(file, message, xhr) {
                let errorMsg = fileManager.t('upload_error_file');
                
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

                fileManager.showNotification(`Ошибка загрузки "${file.name}": ${errorMsg}`, 'error');
            });

            this.on('queuecomplete', function() {
                fileManager.stateManager.set('isUploading', false);
                setTimeout(() => {
                    fileManager.stateManager.set('uploadProgress', 0);
                }, 500);
            });
        }
    });
}

// Автоматическая инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFileManager);
} else {
    initFileManager();
}

export default initFileManager;
