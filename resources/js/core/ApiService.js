/**
 * Сервис для работы с API
 */
export class ApiService {
    constructor(config) {
        this.config = config;
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async fetch(url, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken(),
            ...options.headers,
        };

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                ...options,
                headers,
            });

            const text = await response.text();
            let data = null;
            
            try {
                data = text ? JSON.parse(text) : null;
            } catch (e) {
                data = { raw: text };
            }

            if (!response.ok) {
                let msg = data?.message || data?.error || `HTTP ${response.status}`;
                
                if (response.status === 429) {
                    msg = data?.rate_limit || 'Too many requests. Please try again later.';
                }

                throw new Error(msg);
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    async getFiles(path = '', page = 1) {
        const url = `${this.config.filesUrl}?path=${encodeURIComponent(path)}&page=${page}`;
        return this.fetch(url);
    }

    async createFolder(path) {
        return this.fetch(this.config.createFolderUrl, {
            method: 'POST',
            body: JSON.stringify({ path }),
        });
    }

    async deleteFolder(path) {
        return this.fetch(this.config.deleteFolderUrl, {
            method: 'POST',
            body: JSON.stringify({ path }),
        });
    }

    async deleteFile(path) {
        return this.fetch(this.config.deleteUrl, {
            method: 'POST',
            body: JSON.stringify({ path }),
        });
    }

    async rename(type, path, newName) {
        return this.fetch(this.config.renameUrl, {
            method: 'POST',
            body: JSON.stringify({
                type,
                path,
                new_name: newName,
            }),
        });
    }

    async downloadFolder(path) {
        window.location.href = `${this.config.downloadFolderUrl}?path=${encodeURIComponent(path)}`;
    }

    async downloadFiles(paths) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = this.config.downloadFilesUrl;
        form.target = '_blank';
        form.style.display = 'none';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = this.getCsrfToken();
        form.appendChild(csrf);

        paths.forEach(p => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'paths[]';
            input.value = p;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        form.remove();
    }
}
