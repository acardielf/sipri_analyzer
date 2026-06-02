import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['btn'];
    static values  = { shown: { type: Boolean, default: false } };

    connect() {
        this._getItems().forEach(el => el.classList.add('d-none'));
    }

    toggle() {
        this.shownValue = !this.shownValue;
        this._getItems().forEach(el => el.classList.toggle('d-none', !this.shownValue));
        if (this.hasBtnTarget) {
            const n = this._getItems().length;
            this.btnTarget.textContent = this.shownValue
                ? `Ocultar históricas (${n})`
                : `Ver históricas (${n})`;
        }
    }

    _getItems() {
        return Array.from(this.element.querySelectorAll('[data-historica="true"]'));
    }
}
