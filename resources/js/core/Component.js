import { EventEmitter } from './EventEmitter.js';

/**
 * Базовый класс для всех компонентов
 */
export class Component extends EventEmitter {
    constructor(element, options = {}) {
        super();
        this.element = typeof element === 'string' ? document.querySelector(element) : element;
        this.options = options;
        this.state = {};
        this.refs = {};
        
        if (!this.element) {
            console.warn('Component element not found:', element);
            return;
        }

        this.init();
    }

    init() {
        this.setupRefs();
        this.setupEventListeners();
        this.render();
    }

    setupRefs() {
        // Находим все элементы с data-ref
        const refElements = this.element.querySelectorAll('[data-ref]');
        refElements.forEach(el => {
            const refName = el.getAttribute('data-ref');
            this.refs[refName] = el;
        });
    }

    setupEventListeners() {
        // Переопределяется в дочерних классах
    }

    render() {
        // Переопределяется в дочерних классах
    }

    setState(updates) {
        this.state = { ...this.state, ...updates };
        this.render();
        this.emit('stateChanged', this.state);
    }

    show() {
        this.element.style.display = '';
        this.element.classList.remove('hidden');
    }

    hide() {
        this.element.style.display = 'none';
        this.element.classList.add('hidden');
    }

    toggle(condition) {
        if (condition !== undefined) {
            condition ? this.show() : this.hide();
        } else {
            const isHidden = this.element.classList.contains('hidden') || 
                            this.element.style.display === 'none';
            isHidden ? this.show() : this.hide();
        }
    }

    destroy() {
        this.removeAllListeners();
        if (this.element && this.element.parentNode) {
            this.element.remove();
        }
    }

    // Утилиты для работы с DOM
    query(selector) {
        return this.element.querySelector(selector);
    }

    queryAll(selector) {
        return Array.from(this.element.querySelectorAll(selector));
    }

    addClass(className) {
        this.element.classList.add(className);
    }

    removeClass(className) {
        this.element.classList.remove(className);
    }

    toggleClass(className, condition) {
        if (condition !== undefined) {
            condition ? this.addClass(className) : this.removeClass(className);
        } else {
            this.element.classList.toggle(className);
        }
    }

    setAttribute(name, value) {
        this.element.setAttribute(name, value);
    }

    getAttribute(name) {
        return this.element.getAttribute(name);
    }

    setText(text) {
        this.element.textContent = text;
    }

    setHTML(html) {
        this.element.innerHTML = html;
    }
}
