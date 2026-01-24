import { Component } from './core/Component.js';
import { StateManager } from './core/StateManager.js';
import { ApiService } from './core/ApiService.js';
import { I18n } from './core/I18n.js';
import { Utils } from './core/Utils.js';

/**
 * Главный компонент файлового менеджера
 */
export class FileManager extends Component {
    constructor(element, options = {}) {
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

        const translations = window.sfilesTranslations || { en: {}, ru: {} };
        const defaultLocale = window.sfilesDefaultLocale || 'en';

        super(element, { ...options, config, translations, defaultLocale });

        // Инициализация сервисов
        this.api = new ApiService(config);
        this.i18n = new I18n(translations, defaultLocale);
        
        // Инициализация состояния
        this.stateManager = new StateManager({
            currentPath: '',
            directories: [],
            files: [],
            allFiles: [],
            selectedFiles: [],
            breadcrumbs: [],
            viewMode: 'grid',
            searchQuery: '',
            loading: false,
            operationLoading: false,
            pagination: {
                enabled: true,
                currentPage: 1,
                perPage: 50,
                total: 0,
                totalPages: 1,
            },
            notification: {
                show: false,
                message: '',
                type: 'info',
            },
            dragOver: false,
            contextMenu: {
                show: false,
                x: 0,
                y: 0,
                dir: '',
            },
            fileContextMenu: {
                show: false,
                x: 0,
                y: 0,
                file: null,
            },
            previewModal: {
                show: false,
                url: '',
                type: '',
            },
            renameModal: {
                show: false,
                type: 'file',
                path: '',
                displayName: '',
                newName: '',
            },
        });

        // Кэш
        this.cache = new Map();
        this.cacheEnabled = true;

        // Dropzone instances
        this.dropzoneInstance = null;
        this.dropzoneModalInstance = null;

        // Подписка на изменения состояния
        this.setupStateListeners();
    }

    setupStateListeners() {
        // Подписываемся на изменения состояния для обновления UI
        this.stateManager.on('change', (data) => {
            this.render();
        });
    }

    setupEventListeners() {
        // Закрытие контекстных меню при клике
        this.element.addEventListener('click', (e) => {
            if (!e.target.closest('[data-context-menu]')) {
                this.closeContextMenus();
            }
        });

        // Закрытие попапов по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllPopups();
            }
        });

        // Drag & Drop
        this.setupDragAndDrop();
    }

    setupDragAndDrop() {
        let dragCounter = 0;

        this.element.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.stateManager.set('dragOver', true);
        });

        this.element.addEventListener('dragenter', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dragCounter++;
            this.stateManager.set('dragOver', true);
        });

        this.element.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dragCounter--;
            if (dragCounter === 0) {
                this.stateManager.set('dragOver', false);
            }
        });

        this.element.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dragCounter = 0;
            this.stateManager.set('dragOver', false);

            const files = Array.from(e.dataTransfer.files);
            if (files.length === 0) return;

            this.handleDrop(files);
        });
    }

    async handleDrop(files) {
        if (this.dropzoneInstance) {
            try {
                files.forEach(file => {
                    if (file && file.size > 0) {
                        this.dropzoneInstance.addFile(file);
                    }
                });
                this.showNotification(this.i18n.t('upload_start', { count: files.length }), 'info');
            } catch (error) {
                console.error('Dropzone add files error:', error);
                this.showNotification(this.i18n.t('upload_error'), 'error');
            }
        }
    }

    // Методы для работы с файлами
    async fetchFiles(page = 1) {
        const currentPath = this.stateManager.get('currentPath');
        this.stateManager.set('loading', true);
        this.stateManager.set('lastError', null);
        this.closeContextMenus();

        const cacheKey = `files:${currentPath}:${page}`;

        // Проверка кэша
        if (this.cacheEnabled && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < 300000) { // 5 минут
                this.stateManager.setState({
                    directories: cached.directories,
                    files: cached.files,
                    allFiles: cached.allFiles,
                    pagination: cached.pagination || this.stateManager.get('pagination'),
                });
                this.updateBreadcrumbs();
                this.stateManager.set('loading', false);
                return;
            }
        }

        try {
            const data = await this.api.getFiles(currentPath, page);

            const directories = Array.isArray(data?.directories) ? data.directories : [];
            const files = (Array.isArray(data?.files) ? data.files : []).map(f => {
                const publicPath = f.public_path || f.path || '';
                const opPath = f.disk_path || f.diskPath || publicPath;
                return {
                    name: f.name,
                    size: Number(f.size) || 0,
                    publicPath,
                    opPath
                };
            });

            const pagination = data?.pagination ? {
                enabled: true,
                currentPage: data.pagination.current_page || page,
                perPage: data.pagination.per_page || 50,
                total: data.pagination.total || files.length,
                totalPages: data.pagination.total_pages || 1,
            } : {
                ...this.stateManager.get('pagination'),
                currentPage: page,
                total: files.length,
                totalPages: Math.ceil(files.length / this.stateManager.get('pagination').perPage),
            };

            this.stateManager.setState({
                directories,
                files,
                allFiles: [...files],
                pagination,
            });

            // Сохранение в кэш
            if (this.cacheEnabled) {
                this.cache.set(cacheKey, {
                    directories,
                    files,
                    allFiles: [...files],
                    pagination,
                    timestamp: Date.now()
                });
            }

            this.updateBreadcrumbs();
            
            // Эмитим событие для обновления UI
            this.stateManager.emit('filesLoaded', { files, directories, pagination });
        } catch (error) {
            console.error('Error fetching files:', error);
            const errorMessage = error?.message || this.i18n.t('error_fetching');
            this.stateManager.set('lastError', errorMessage);
            this.showNotification(errorMessage, 'error');
            this.stateManager.setState({
                directories: [],
                files: [],
                allFiles: [],
            });
            this.updateBreadcrumbs();
        } finally {
            this.stateManager.set('loading', false);
        }
    }

    updateBreadcrumbs() {
        const currentPath = this.stateManager.get('currentPath');
        const parts = currentPath.split('/').filter(Boolean);
        this.stateManager.set('breadcrumbs', ['root', ...parts]);
    }

    openDirectory(dir) {
        this.stateManager.set('selectedFiles', []);
        this.closeContextMenus();
        this.stateManager.setState({
            currentPath: dir || '',
            pagination: { ...this.stateManager.get('pagination'), currentPage: 1 },
        });
        this.fetchFiles(1);
    }

    goUp() {
        const currentPath = this.stateManager.get('currentPath');
        if (currentPath === '') return;
        
        this.stateManager.set('selectedFiles', []);
        this.closeContextMenus();
        
        const parts = currentPath.split('/');
        parts.pop();
        this.stateManager.setState({
            currentPath: parts.join('/'),
            pagination: { ...this.stateManager.get('pagination'), currentPage: 1 },
        });
        this.fetchFiles(1);
    }

    goToBreadcrumb(index) {
        this.stateManager.set('selectedFiles', []);
        this.closeContextMenus();

        const breadcrumbs = this.stateManager.get('breadcrumbs');
        const newPath = index === 0 ? '' : breadcrumbs.slice(1, index + 1).join('/');
        this.stateManager.setState({
            currentPath: newPath,
            pagination: { ...this.stateManager.get('pagination'), currentPage: 1 },
        });
        this.fetchFiles(1);
    }

    selectFile(file) {
        if (!file) return;

        const fileData = {
            path: file.opPath,
            publicPath: file.publicPath,
            name: file.name,
            size: file.size,
            fullPath: file.publicPath || file.opPath
        };

        const fileUrl = Utils.buildPublicUrl(file.publicPath);

        // TinyMCE интеграция через window.opener
        if (window.opener && !window.opener.closed) {
            if (typeof window.opener.tinymceFilePickerCallback === 'function') {
                try {
                    window.opener.tinymceFilePickerCallback(fileUrl, {});
                    if (window.closeTinyMCEFilePicker && typeof window.closeTinyMCEFilePicker === 'function') {
                        window.closeTinyMCEFilePicker();
                    } else {
                        window.close();
                    }
                    return;
                } catch (e) {
                    console.error('Error calling TinyMCE callback:', e);
                }
            }
        }

        // TinyMCE интеграция через postMessage (для iframe)
        if (window.parent && window.parent !== window) {
            try {
                window.parent.postMessage({
                    type: 'tinymce-file-selected',
                    url: fileUrl,
                    meta: {}
                }, '*');
                return;
            } catch (e) {
                console.error('Error sending postMessage to parent:', e);
            }
        }

        // Callback функция
        if (typeof window.sfilesOnFileSelect === 'function') {
            window.sfilesOnFileSelect(fileData);
            return;
        }

        // window.opener.handleFileSelect
        if (window.opener && !window.opener.closed) {
            if (typeof window.opener.handleFileSelect === 'function') {
                window.opener.handleFileSelect(fileData);
                window.close();
                return;
            }
            window.opener.postMessage({
                type: 'sfiles_file_selected',
                data: fileData
            }, '*');
            window.close();
            return;
        }

        // postMessage
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({
                type: 'sfiles_file_selected',
                data: fileData
            }, '*');
            return;
        }

        // Fallback: копирование в буфер обмена
        Utils.copyToClipboard(fileData.publicPath || fileData.path).then(() => {
            this.showNotification(this.i18n.t('path_copied', { path: fileData.publicPath || fileData.path }), 'success');
        });
    }

    openFileInNewTab(file) {
        if (!file) return;
        const url = Utils.buildPublicUrl(file.publicPath);
        window.open(url, '_blank');
        this.closeFileContextMenu();
    }

    // Уведомления
    showNotification(message, type = 'info', duration = 3000) {
        this.stateManager.set('notification', {
            show: true,
            message,
            type
        });

        setTimeout(() => {
            this.stateManager.set('notification', {
                show: false,
                message: '',
                type: 'info'
            });
        }, duration);
    }

    // Контекстные меню
    closeContextMenus() {
        this.stateManager.set('contextMenu', { show: false, x: 0, y: 0, dir: '' });
        this.closeFileContextMenu();
    }

    closeFileContextMenu() {
        this.stateManager.set('fileContextMenu', { show: false, x: 0, y: 0, file: null });
    }

    closeAllPopups() {
        this.closeContextMenus();
        this.stateManager.set('previewModal', { show: false, url: '', type: '' });
        this.stateManager.set('renameModal', { show: false, type: 'file', path: '', displayName: '', newName: '' });
    }

    // Геттеры для удобства
    getState() {
        return this.stateManager.getState();
    }

    get(key) {
        return this.stateManager.get(key);
    }

    t(key, params) {
        return this.i18n.t(key, params);
    }

    // Операции с файлами и папками
    async createFolder(name) {
        const trimmedName = name.trim();
        if (!trimmedName) return;

        this.stateManager.set('operationLoading', true);
        const currentPath = this.stateManager.get('currentPath');
        const newPath = currentPath ? `${currentPath}/${trimmedName}` : trimmedName;

        try {
            await this.api.createFolder(newPath);
            this.clearCache(currentPath);
            this.showNotification(this.i18n.t('folder_created'), 'success');
            await this.fetchFiles(this.stateManager.get('pagination').currentPage);
        } catch (error) {
            console.error('Folder create error:', error);
            this.showNotification(error?.message || this.i18n.t('folder_create_error'), 'error');
        } finally {
            this.stateManager.set('operationLoading', false);
        }
    }

    async deleteFolder(dir) {
        const target = String(dir || '').trim();
        if (!target) return;

        const folderName = target.split('/').pop() || target;
        const confirmed = confirm(this.i18n.t('confirm_delete_folder', { name: folderName }));

        if (!confirmed) return;

        this.stateManager.set('operationLoading', true);

        try {
            await this.api.deleteFolder(target);
            this.clearCache(this.stateManager.get('currentPath'));
            this.showNotification(this.i18n.t('folder_deleted'), 'success');
            await this.fetchFiles(this.stateManager.get('pagination').currentPage);
        } catch (error) {
            console.error('Folder delete error:', error);
            this.showNotification(error?.message || this.i18n.t('folder_delete_error'), 'error');
        } finally {
            this.stateManager.set('operationLoading', false);
        }
    }

    async deleteFile(file) {
        const confirmed = confirm(this.i18n.t('confirm_delete_file', { name: file.name }));
        if (!confirmed) return;

        this.stateManager.set('operationLoading', true);

        try {
            await this.api.deleteFile(file.opPath);
            this.clearCache(this.stateManager.get('currentPath'));
            this.showNotification(this.i18n.t('file_deleted'), 'success');
            
            const selectedFiles = this.stateManager.get('selectedFiles');
            this.stateManager.set('selectedFiles', selectedFiles.filter(p => p !== file.opPath));
            
            await this.fetchFiles(this.stateManager.get('pagination').currentPage);
        } catch (error) {
            console.error('File delete error:', error);
            this.showNotification(error?.message || this.i18n.t('file_delete_error'), 'error');
        } finally {
            this.stateManager.set('operationLoading', false);
        }
    }

    async deleteSelectedFiles() {
        const selectedFiles = this.stateManager.get('selectedFiles');
        const count = selectedFiles.length;
        if (!count) return;

        const confirmed = confirm(this.i18n.t('confirm_delete_files', { count }));
        if (!confirmed) return;

        this.stateManager.set('operationLoading', true);
        const paths = [...selectedFiles];

        try {
            await Promise.all(paths.map(path => this.api.deleteFile(path)));
            this.stateManager.set('selectedFiles', []);
            this.clearCache(this.stateManager.get('currentPath'));
            this.showNotification(this.i18n.t('files_deleted', { count }), 'success');
            await this.fetchFiles(this.stateManager.get('pagination').currentPage);
        } catch (error) {
            console.error('Delete files error:', error);
            this.showNotification(error?.message || this.i18n.t('files_delete_error'), 'error');
        } finally {
            this.stateManager.set('operationLoading', false);
        }
    }

    async rename(type, path, newName) {
        const trimmedName = newName.trim();
        if (!trimmedName) {
            this.showNotification(this.i18n.t('name_required'), 'error');
            return;
        }

        this.stateManager.set('operationLoading', true);

        try {
            await this.api.rename(type, path, trimmedName);
            this.stateManager.set('selectedFiles', []);
            this.stateManager.set('renameModal', {
                show: false,
                type: 'file',
                path: '',
                displayName: '',
                newName: ''
            });
            this.clearCache(this.stateManager.get('currentPath'));
            this.showNotification(this.i18n.t('renamed_success'), 'success');
            await this.fetchFiles(this.stateManager.get('pagination').currentPage);
        } catch (error) {
            console.error('Rename error:', error);
            this.showNotification(error?.message || this.i18n.t('rename_error'), 'error');
        } finally {
            this.stateManager.set('operationLoading', false);
        }
    }

    openRenameForFile(file) {
        if (!file) return;
        this.closeContextMenus();
        this.stateManager.set('renameModal', {
            show: true,
            type: 'file',
            path: file.opPath,
            displayName: file.name,
            newName: file.name
        });
    }

    openRenameForDir(dir) {
        const target = String(dir || '').trim();
        if (!target) return;
        this.closeContextMenus();

        const name = target.split('/').filter(Boolean).pop() || target;
        this.stateManager.set('renameModal', {
            show: true,
            type: 'dir',
            path: target,
            displayName: name,
            newName: name
        });
    }

    previewFile(file) {
        this.closeFileContextMenu();
        const url = Utils.buildPublicUrl(file.publicPath);
        const extension = file.name.split('.').pop().toLowerCase();
        const isWord = ['doc', 'docx'].includes(extension);

        this.stateManager.set('previewModal', {
            show: true,
            url,
            type: isWord ? 'word' : Utils.isImage(file.name) ? 'image' : extension === 'pdf' ? 'pdf' : 'other'
        });
    }

    downloadSelectedFiles() {
        const selectedFiles = this.stateManager.get('selectedFiles');
        if (!selectedFiles.length) return;
        this.api.downloadFiles(selectedFiles);
    }

    downloadFolder(dir) {
        const target = String(dir || '').trim();
        if (!target) return;
        this.closeContextMenus();
        this.api.downloadFolder(target);
    }

    toggleAllFiles(checked) {
        const files = this.getFilteredFiles();
        this.stateManager.set('selectedFiles', checked ? files.map(file => file.opPath) : []);
    }

    getFilteredFiles() {
        const files = this.stateManager.get('files');
        const searchQuery = this.stateManager.get('searchQuery');

        let filtered = files;

        // Фильтрация по поисковому запросу (только на клиенте)
        if (searchQuery.trim()) {
            const q = searchQuery.trim().toLowerCase();
            filtered = filtered.filter(file =>
                file.name.toLowerCase().includes(q)
            );
        }

        // Пагинация происходит на сервере, поэтому возвращаем все отфильтрованные файлы
        return filtered;
    }

    clearCache(path = null) {
        if (path === null) {
            this.cache.clear();
        } else {
            for (const key of this.cache.keys()) {
                if (key.includes(`files:${path}:`)) {
                    this.cache.delete(key);
                }
            }
        }
    }

    goToPage(page) {
        const pagination = this.stateManager.get('pagination');
        if (page < 1 || page > pagination.totalPages) return;
        this.stateManager.set('pagination', { ...pagination, currentPage: page });
        this.fetchFiles(page);
    }

    toggleView(mode) {
        if (['grid', 'list'].includes(mode)) {
            this.stateManager.set('viewMode', mode);
        }
    }

    setLocale(locale) {
        this.i18n.setLocale(locale);
        this.stateManager.emit('localeChanged', locale);
    }

    t(key, params) {
        return this.i18n.t(key, params);
    }

    // Добавляем Utils как свойство для доступа из адаптера
    get Utils() {
        return Utils;
    }

    render() {
        // Рендеринг делегируется дочерним компонентам
        this.emit('render');
    }
}
