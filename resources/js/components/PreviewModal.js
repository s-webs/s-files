import { Component } from '../core/Component.js';

/**
 * Компонент модального окна предпросмотра файла
 */
export class PreviewModal extends Component {
    constructor(element, fileManager) {
        super(element);
        this.fileManager = fileManager;
        this.setupStateListener();
    }

    setupStateListener() {
        this.fileManager.stateManager.on('change:previewModal', (modal) => {
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

        // Закрытие по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.fileManager.get('previewModal').show) {
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
        const content = this.query('[data-modal-content]');
        if (!content) return;

        const { url, type } = modal;

        let html = '';

        if (type === 'image') {
            html = `<img src="${url}" alt="Preview" class="max-w-full max-h-[80vh] object-contain">`;
        } else if (type === 'pdf') {
            html = `<iframe src="${url}" class="w-full h-[80vh] border-0"></iframe>`;
        } else if (type === 'word') {
            html = `<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(window.location.origin + url)}" class="w-full h-[80vh] border-0"></iframe>`;
        } else {
            html = `
                <div class="flex flex-col items-center justify-center p-8">
                    <i class="ph ph-file text-6xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600">${this.fileManager.t('preview_unavailable')}</p>
                    <a href="${url}" target="_blank" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        ${this.fileManager.t('download_original')}
                    </a>
                </div>
            `;
        }

        content.innerHTML = html;
    }

    close() {
        this.fileManager.stateManager.set('previewModal', {
            show: false,
            url: '',
            type: ''
        });
    }
}
