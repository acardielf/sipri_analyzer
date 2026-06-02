import { Controller } from '@hotwired/stimulus';
import { toggle, has } from '../lib/favoritos.js';

export default class extends Controller {
    static values = { type: String, id: String, nombre: String, href: String };

    connect() {
        this.#update(has(this.typeValue, this.idValue));
        this._handler = () => this.#update(has(this.typeValue, this.idValue));
        window.addEventListener('sipri:favoritos', this._handler);
    }

    disconnect() {
        window.removeEventListener('sipri:favoritos', this._handler);
    }

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();
        const added = toggle(
            this.typeValue,
            this.idValue,
            this.nombreValue || null,
            this.hrefValue || null,
        );
        this.#update(added);
    }

    #update(activo) {
        this.element.classList.toggle('fav-btn--activo', activo);
        const label = activo ? 'Quitar de favoritos' : 'Añadir a favoritos';
        this.element.setAttribute('aria-label', label);
        this.element.setAttribute('title', label);
        this.element.querySelector('.fav-btn-icon').className =
            `fav-btn-icon bi ${activo ? 'bi-star-fill' : 'bi-star'}`;
    }
}
