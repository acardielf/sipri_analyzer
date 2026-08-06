import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['col', 'btn'];
    static values  = { shown: { type: Boolean, default: false }, count: Number };

    toggle() {
        this.shownValue = !this.shownValue;
        this.colTargets.forEach(el => el.classList.toggle('d-none', !this.shownValue));
        if (this.hasBtnTarget) {
            const sufijo = this.hasCountValue ? ` (${this.countValue})` : '';
            this.btnTarget.textContent = this.shownValue
                ? `Ocultar cursos anteriores${sufijo}`
                : `Ver cursos anteriores${sufijo}`;
        }
    }
}
