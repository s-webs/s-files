import { Component } from '../core/Component.js';

/**
 * Компонент контекстного меню для папок
 */
export class FolderContextMenu extends Component {
    constructor(element, fileManager) {
        super(element);
        this.fileManager = fileManager;
        this.setupStateListener();
    }

    setupStateListener() {
        this.fileManager.stateManager.on('change:contextMenu', (menu) => {
            this.update(menu);
        });
    }

    setupEventListeners() {
        // Закрытие при клике вне меню
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target) && this.fileManager.get('contextMenu').show) {
                this.close();
            }
        });

        // Копировать путь
        const copyPathBtn = this.query('[data-context-copy-path]');
        if (copyPathBtn) {
            copyPathBtn.addEventListener('click', () => {
                const dir = this.fileManager.get('contextMenu').dir;
                if (dir) {
                    this.fileManager.Utils.copyToClipboard(dir).then(() => {
                        this.fileManager.showNotification(
                            this.fileManager.t('path_copied', { path: dir }),
                            'success'
                        );
                    });
                }
                this.close();
            });
        }

        // Скачать папку
        const downloadBtn = this.query('[data-context-download]');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                const dir = this.fileManager.get('contextMenu').dir;
                if (dir) {
                    this.fileManager.downloadFolder(dir);
                }
                this.close();
            });
        }

        // Переименовать
        const renameBtn = this.query('[data-context-rename]');
        if (renameBtn) {
            renameBtn.addEventListener('click', () => {
                const dir = this.fileManager.get('contextMenu').dir;
                if (dir) {
                    this.fileManager.openRenameForDir(dir);
                }
                this.close();
            });
        }

        // Удалить
        const deleteBtn = this.query('[data-context-delete]');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                const dir = this.fileManager.get('contextMenu').dir;
                if (dir) {
                    this.fileManager.deleteFolder(dir);
                }
                this.close();
            });
        }
    }

    update(menu) {
        if (menu.show) {
            this.show();
            this.setPosition(menu.x, menu.y);
        } else {
            this.hide();
        }
    }

    setPosition(x, y) {
        const rect = this.element.getBoundingClientRect();
        const menuW = rect.width || 190;
        const menuH = rect.height || 200;

        // Проверяем границы экрана
        const maxX = window.innerWidth - menuW - 10;
        const maxY = window.innerHeight - menuH - 10;

        // Корректируем позицию
        let finalX = x;
        let finalY = y;

        if (finalX > maxX) {
            finalX = x - menuW - 10;
        }
        if (finalY > maxY) {
            finalY = y - menuH - 10;
        }

        // Минимальные отступы
        finalX = Math.max(10, Math.min(finalX, maxX));
        finalY = Math.max(10, Math.min(finalY, maxY));

        this.element.style.left = `${finalX}px`;
        this.element.style.top = `${finalY}px`;
    }

    close() {
        this.fileManager.stateManager.set('contextMenu', {
            show: false,
            x: 0,
            y: 0,
            dir: ''
        });
    }
}
