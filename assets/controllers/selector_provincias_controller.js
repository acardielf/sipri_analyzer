import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['col', 'btn'];

    select(event) {
        const prov = event.currentTarget.dataset.prov;

        this.btnTargets.forEach(btn => {
            btn.classList.toggle('prov-btn--activo', btn.dataset.prov === prov);
        });

        this.colTargets.forEach(col => {
            const show = prov === 'all' || col.dataset.prov === prov;
            col.classList.toggle('d-none', !show);
        });
    }
}
