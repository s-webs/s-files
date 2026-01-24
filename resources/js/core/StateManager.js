import { EventEmitter } from './EventEmitter.js';

/**
 * Менеджер состояния приложения
 */
export class StateManager extends EventEmitter {
    constructor(initialState = {}) {
        super();
        this.state = initialState;
        this.listeners = new Map();
    }

    getState() {
        return { ...this.state };
    }

    get(key) {
        return this.state[key];
    }

    set(key, value) {
        const oldValue = this.state[key];
        this.state[key] = value;
        this.emit('change', { key, value, oldValue });
        this.emit(`change:${key}`, value, oldValue);
    }

    setState(updates) {
        const oldState = { ...this.state };
        Object.keys(updates).forEach(key => {
            this.state[key] = updates[key];
        });
        this.emit('change', { state: this.state, oldState });
        Object.keys(updates).forEach(key => {
            this.emit(`change:${key}`, updates[key], oldState[key]);
        });
    }

    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, []);
        }
        this.listeners.get(key).push(callback);
        
        return () => {
            const callbacks = this.listeners.get(key);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        };
    }

    unsubscribe(key, callback) {
        if (!this.listeners.has(key)) return;
        const callbacks = this.listeners.get(key);
        const index = callbacks.indexOf(callback);
        if (index > -1) {
            callbacks.splice(index, 1);
        }
    }
}
