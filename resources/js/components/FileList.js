import { Component } from '../core/Component.js';

/**
 * Компонент списка файлов
 */
export class FileList extends Component {
    constructor(element, fileManager) {
        super(element);
        this.fileManager = fileManager;
        this.setupStateListener();
    }

    setupStateListener() {
        this.fileManager.stateManager.on('change:files', () => {
            this.render();
        });
        this.fileManager.stateManager.on('change:viewMode', () => {
            this.render();
        });
        this.fileManager.stateManager.on('change:searchQuery', () => {
            this.render();
        });
        this.fileManager.stateManager.on('change:selectedFiles', () => {
            this.updateSelection();
        });
    }

    setupEventListeners() {
        // Обработка кликов на файлы
        this.element.addEventListener('click', (e) => {
            const checkbox = e.target.closest('[data-file-checkbox]');
            if (checkbox) {
                e.stopPropagation();
                const fileEl = checkbox.closest('[data-file-item]');
                if (fileEl) {
                    const fileData = JSON.parse(fileEl.getAttribute('data-file-item'));
                    this.toggleFileSelection(fileData);
                }
                return;
            }

            const fileEl = e.target.closest('[data-file-item]');
            if (fileEl) {
                e.preventDefault();
                const fileData = JSON.parse(fileEl.getAttribute('data-file-item'));
                this.fileManager.selectFile(fileData);
            }
        });

        // Двойной клик
        this.element.addEventListener('dblclick', (e) => {
            const fileEl = e.target.closest('[data-file-item]');
            if (fileEl) {
                e.preventDefault();
                const fileData = JSON.parse(fileEl.getAttribute('data-file-item'));
                this.fileManager.openFileInNewTab(fileData);
            }
        });

        // Контекстное меню
        this.element.addEventListener('contextmenu', (e) => {
            const fileEl = e.target.closest('[data-file-item]');
            if (fileEl) {
                e.preventDefault();
                const fileData = JSON.parse(fileEl.getAttribute('data-file-item'));
                this.openContextMenu(fileData, e);
            }
        });
    }

    render() {
        const files = this.fileManager.getFilteredFiles();
        const viewMode = this.fileManager.get('viewMode');
        const selectedFiles = this.fileManager.get('selectedFiles');
        const loading = this.fileManager.get('loading');

        if (loading) {
            this.showLoading();
            return;
        }

        this.hideLoading();

        if (viewMode === 'grid') {
            this.renderGrid(files, selectedFiles);
        } else {
            this.renderList(files, selectedFiles);
        }
    }

    renderGrid(files, selectedFiles) {
        const container = this.query('[data-file-list-grid]');
        if (!container) return;

        if (files.length === 0) {
            container.innerHTML = `
                <div class="w-full text-center py-16">
                    <div class="flex flex-col items-center">
                        <i class="ph ph-file-x text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg font-medium">${this.fileManager.t('no_files')}</p>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = files.map(file => {
            const isSelected = selectedFiles.includes(file.opPath);
            const isImage = this.fileManager.Utils.isImage(file.name);
            const fileUrl = this.fileManager.Utils.buildPublicUrl(file.publicPath);
            const icon = this.fileManager.Utils.getFileIcon(file.name);
            const size = this.fileManager.Utils.formatFileSize(file.size);

            return `
                <li class="relative group">
                    <div class="absolute top-2 left-2 z-[3] opacity-0 group-hover:opacity-100 transition-opacity">
                        <input type="checkbox" 
                               data-file-checkbox
                               ${isSelected ? 'checked' : ''}
                               class="form-checkbox h-5 w-5 bg-white shadow-lg">
                    </div>
                    <div class="relative" data-file-item='${JSON.stringify(file).replace(/'/g, "&#39;")}'>
                        <a href="${fileUrl}" 
                           class="block border-2 border-gray-200 bg-white w-[140px] h-[160px] flex flex-col items-center p-3 rounded-2xl hover:shadow-2xl hover:border-blue-400 transition-all duration-300 transform hover:scale-105 hover:-translate-y-1 cursor-pointer">
                            ${isImage 
                                ? `<div class="w-full h-[100px] mb-2 rounded-xl overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                                     <img src="${fileUrl}" class="w-full h-full object-cover" alt="Thumbnail">
                                   </div>`
                                : `<div class="w-full h-[100px] mb-2 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                                     <i class="${icon} text-5xl"></i>
                                   </div>`
                            }
                            <div class="w-full text-center text-xs font-semibold text-gray-700 truncate mb-1">
                                ${file.name}
                            </div>
                            <div class="w-full text-center text-xs text-gray-500">
                                ${size}
                            </div>
                        </a>
                    </div>
                </li>
            `;
        }).join('');
    }

    renderList(files, selectedFiles) {
        const container = this.query('[data-file-list-list]');
        if (!container) return;

        if (files.length === 0) {
            container.innerHTML = `
                <div class="w-full text-center py-16">
                    <div class="flex flex-col items-center">
                        <i class="ph ph-file-x text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg font-medium">${this.fileManager.t('no_files')}</p>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = files.map(file => {
            const isSelected = selectedFiles.includes(file.opPath);
            const isImage = this.fileManager.Utils.isImage(file.name);
            const fileUrl = this.fileManager.Utils.buildPublicUrl(file.publicPath);
            const icon = this.fileManager.Utils.getFileIcon(file.name);
            const size = this.fileManager.Utils.formatFileSize(file.size);

            return `
                <li class="flex items-center justify-between bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl px-4 py-3 hover:bg-white hover:border-blue-400 hover:shadow-lg transition-all duration-200 group relative"
                    data-file-item='${JSON.stringify(file).replace(/'/g, "&#39;")}'>
                    <div class="mr-4">
                        <input type="checkbox" 
                               data-file-checkbox
                               ${isSelected ? 'checked' : ''}
                               class="form-checkbox h-5 w-5">
                    </div>
                    <div class="flex items-center flex-1 cursor-pointer min-w-0">
                        ${isImage
                            ? `<div class="w-12 h-12 rounded-lg overflow-hidden mr-4 shadow-md flex-shrink-0">
                                 <img src="${fileUrl}" class="w-full h-full object-cover" alt="Thumbnail">
                               </div>`
                            : `<div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center mr-4 shadow-md flex-shrink-0">
                                 <i class="${icon} text-2xl"></i>
                               </div>`
                        }
                        <div class="flex-1 overflow-hidden min-w-0">
                            <a href="${fileUrl}" 
                               class="text-sm font-semibold text-gray-800 truncate hover:text-blue-600 transition-colors block">
                                ${file.name}
                            </a>
                            <div class="text-xs text-gray-500 mt-1">${size}</div>
                        </div>
                    </div>
                    <div class="ml-4">
                        <button class="text-gray-400 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                            <i class="ph ph-dots-three-outline-vertical text-xl"></i>
                        </button>
                    </div>
                </li>
            `;
        }).join('');
    }

    toggleFileSelection(file) {
        const selectedFiles = [...this.fileManager.get('selectedFiles')];
        const index = selectedFiles.indexOf(file.opPath);
        
        if (index > -1) {
            selectedFiles.splice(index, 1);
        } else {
            selectedFiles.push(file.opPath);
        }
        
        this.fileManager.stateManager.set('selectedFiles', selectedFiles);
    }

    updateSelection() {
        const selectedFiles = this.fileManager.get('selectedFiles');
        this.queryAll('[data-file-checkbox]').forEach(checkbox => {
            const fileEl = checkbox.closest('[data-file-item]');
            if (fileEl) {
                const fileData = JSON.parse(fileEl.getAttribute('data-file-item'));
                checkbox.checked = selectedFiles.includes(fileData.opPath);
            }
        });
    }

    openContextMenu(file, event) {
        const x = event.clientX;
        const y = event.clientY;
        this.fileManager.stateManager.set('fileContextMenu', {
            show: true,
            x,
            y,
            file
        });
    }

    showLoading() {
        const loadingEl = this.query('[data-file-list-loading]');
        if (loadingEl) loadingEl.style.display = 'flex';
    }

    hideLoading() {
        const loadingEl = this.query('[data-file-list-loading]');
        if (loadingEl) loadingEl.style.display = 'none';
    }
}
