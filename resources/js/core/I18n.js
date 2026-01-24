/**
 * Сервис интернационализации
 */
export class I18n {
    constructor(translations = {}, defaultLocale = 'en') {
        this.translations = translations;
        this.locale = localStorage.getItem('sfiles_locale') || defaultLocale;
    }

    setLocale(locale) {
        if (locale !== 'en' && locale !== 'ru') return;
        this.locale = locale;
        localStorage.setItem('sfiles_locale', locale);
        
        try {
            document.documentElement.lang = locale;
            const title = this.t('title');
            if (title) {
                document.title = title;
            }
        } catch (e) {
            console.error('Error setting locale:', e);
        }
    }

    getLocale() {
        return this.locale;
    }

    t(key, params = {}) {
        let translation = this.translations?.[this.locale]?.[key] || 
                        this.translations?.en?.[key] || 
                        key;

        // Заменяем параметры в строке
        Object.keys(params).forEach(paramKey => {
            translation = translation.replace(
                new RegExp(`\\{${paramKey}\\}`, 'g'),
                String(params[paramKey])
            );
        });

        return translation;
    }
}
