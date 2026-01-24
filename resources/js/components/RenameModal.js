import { Component } from '../core/Component.js';

/**
 * Компонент модального окна переименования
 */
export class RenameModal extends Component {
    constructor(element, fileManager) {
        super(element);
        this.fileManager = fileManager;
        this.setupStateListener();
    }

    setupStateListener() {
        this.fileManager.stateManager.on('change:renameModal', (modal) => {
            this.update(modal);
        });
    }

    setupEventListeners() {
        // Закрытие по клику на фон
        const backdrop = this.query('[data-modal-backdrop]');
        if (backdrop) {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    this.close();
                }
            });
        }

        // Закрытие по кнопке
        const closeBtn = this.query('[data-modal-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        // Кнопка отмены
        const cancelBtn = this.query('[data-rename-cancel]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.close());
        }

        // Кнопка подтверждения
        const submitBtn = this.query('[data-rename-submit]');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submit());
        }

        // Ввод в поле имени
        const nameInput = this.query('[data-rename-input]');
        if (nameInput) {
            nameInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.submit();
                }
            });
        }

        // Закрытие по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.fileManager.get('renameModal').show) {
                this.close();
            }
        });
    }

    update(modal) {
        if (modal.show) {
            this.show();
            this.renderContent(modal);
        } else {
            this.hide();
        }
    }

    renderContent(modal) {
        const { type, displayName, newName } = modal;

        const title = this.query('[data-rename-title]');
        if (title) {
            title.textContent = type === 'file' 
                ? this.fileManager.t('rename_file')
                : this.fileManager.t('rename_folder');
        }

        const currentName = this.query('[data-rename-current]');
        if (currentName) {
            currentName.textContent = displayName;
        }

        const nameInput = this.query('[data-rename-input]');
        if (nameInput) {
            nameInput.value = newName;
            nameInput.focus();
            nameInput.select();
        }

        const hint = this.query('[data-rename-hint]');
        if (hint && type === 'file') {
            hint.textContent = this.fileManager.t('rename_ext_hint');
            hint.style.display = '';
        } else if (hint) {
            hint.style.display = 'none';
        }
    }

    submit() {
        const modal = this.fileManager.get('renameModal');
        const { type, path, newName } = modal;

        const nameInput = this.query('[data-rename-input]');
        const trimmedName = nameInput ? nameInput.value.trim() : newName.trim();

        if (!trimmedName) {
            this.fileManager.showNotification(this.fileManager.t('name_required'), 'error');
            return;
        }

        this.fileManager.rename(type, path, trimmedName);
    }

    close() {
        this.fileManager.stateManager.set('renameModal', {
            show: false,
            type: 'file',
            path: '',
            displayName: '',
            newName: ''
        });
    }
}
