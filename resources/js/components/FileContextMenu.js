import { Component } from '../core/Component.js';

/**
 * Компонент контекстного меню для файлов
 */
export class FileContextMenu extends Component {
    constructor(element, fileManager) {
        super(element);
        this.fileManager = fileManager;
        this.setupStateListener();
    }

    setupStateListener() {
        this.fileManager.stateManager.on('change:fileContextMenu', (menu) => {
            this.update(menu);
        });
    }

    setupEventListeners() {
        // Закрытие при клике вне меню
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target) && this.fileManager.get('fileContextMenu').show) {
                this.close();
            }
        });

        // Предпросмотр
        const previewBtn = this.query('[data-context-preview]');
        if (previewBtn) {
            previewBtn.addEventListener('click', () => {
                const file = this.fileManager.get('fileContextMenu').file;
                if (file) {
                    this.fileManager.previewFile(file);
                }
                this.close();
            });
        }

        // Открыть в новой вкладке
        const openTabBtn = this.query('[data-context-open-tab]');
        if (openTabBtn) {
            openTabBtn.addEventListener('click', () => {
                const file = this.fileManager.get('fileContextMenu').file;
                if (file) {
                    this.fileManager.openFileInNewTab(file);
                }
                this.close();
            });
        }

        // Копировать ссылку
        const copyLinkBtn = this.query('[data-context-copy-link]');
        if (copyLinkBtn) {
            copyLinkBtn.addEventListener('click', () => {
                const file = this.fileManager.get('fileContextMenu').file;
                if (file) {
                    const url = this.fileManager.Utils.buildPublicUrl(file.publicPath);
                    this.fileManager.Utils.copyToClipboard(url).then(() => {
                        this.fileManager.showNotification(
                            this.fileManager.t('link_copied', { url }),
                            'success'
                        );
                    });
                }
                this.close();
            });
        }

        // Переименовать
        const renameBtn = this.query('[data-context-rename]');
        if (renameBtn) {
            renameBtn.addEventListener('click', () => {
                const file = this.fileManager.get('fileContextMenu').file;
                if (file) {
                    this.fileManager.openRenameForFile(file);
                }
                this.close();
            });
        }

        // Удалить
        const deleteBtn = this.query('[data-context-delete]');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                const file = this.fileManager.get('fileContextMenu').file;
                if (file) {
                    this.fileManager.deleteFile(file);
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
        this.fileManager.closeFileContextMenu();
    }
}
