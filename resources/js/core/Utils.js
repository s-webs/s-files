/**
 * Утилиты
 */
export class Utils {
    static formatFileSize(size) {
        const s = Number(size) || 0;
        if (s < 1024) return s + ' B';
        if (s < 1048576) return (s / 1024).toFixed(2) + ' KB';
        if (s < 1073741824) return (s / 1048576).toFixed(2) + ' MB';
        return (s / 1073741824).toFixed(2) + ' GB';
    }

    static isImage(fileName) {
        return /\.(jpg|jpeg|png|gif|webp)$/i.test(fileName);
    }

    static getFileIcon(fileName) {
        const ext = fileName.split('.').pop().toLowerCase();
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
    }

    static buildPublicUrl(path) {
        const p = String(path || '').replace(/^\/+/, '');
        return '/' + p;
    }

    static copyToClipboard(text) {
        return new Promise((resolve, reject) => {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => resolve())
                    .catch(err => {
                        // Fallback
                        this.fallbackCopyToClipboard(text)
                            .then(() => resolve())
                            .catch(reject);
                    });
            } else {
                this.fallbackCopyToClipboard(text)
                    .then(() => resolve())
                    .catch(reject);
            }
        });
    }

    static fallbackCopyToClipboard(text) {
        return new Promise((resolve, reject) => {
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
                    resolve();
                } else {
                    reject(new Error('Copy command failed'));
                }
            } catch (err) {
                document.body.removeChild(textArea);
                reject(err);
            }
        });
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}
