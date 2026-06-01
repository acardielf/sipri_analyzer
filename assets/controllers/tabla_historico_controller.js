import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['col', 'btn'];
    static values  = { shown: { type: Boolean, default: false } };

    toggle() {
        this.shownValue = !this.shownValue;
        this.colTargets.forEach(el => el.classList.toggle('d-none', !this.shownValue));
        if (this.hasBtnTarget) {
            this.btnTarget.textContent = this.shownValue
                ? 'Ocultar cursos anteriores'
                : 'Ver cursos anteriores';
        }
    }
}
