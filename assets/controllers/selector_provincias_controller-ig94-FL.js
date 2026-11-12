import { Controller } from '@hotwired/stimulus';
import { saveProvinciasBolsa, getProvinciasBolsa } from '../lib/favoritos.js';

/**
 * Filtro de provincias de la bolsa. Es único para las cuatro pestañas de curso, así
 * que no envuelve a nadie: publica la selección en `window` y cada pestaña la aplica
 * por su cuenta. La selección se recuerda por especialidad.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['btn'];
    static values = { especialidad: String };

    /** Vacío significa «todas»: el sistema de sustituciones deja elegir de 1 a 8
     *  provincias, así que el filtro es de selección múltiple. */
    #activas = new Set();

    connect() {
        this.#activas = new Set(getProvinciasBolsa(this.especialidadValue));
        this.#aplicar(false);
    }

    select(event) {
        const prov = event.currentTarget.dataset.prov;

        if (prov === 'all') {
            this.#activas.clear();
        } else if (this.#activas.has(prov)) {
            this.#activas.delete(prov);
        } else {
            this.#activas.add(prov);
        }

        this.#aplicar(true);
    }

    #aplicar(persistir) {
        const todas = this.#activas.size === 0;

        this.btnTargets.forEach(btn => {
            const activo = btn.dataset.prov === 'all' ? todas : this.#activas.has(btn.dataset.prov);
            btn.classList.toggle('prov-btn--activo', activo);
            btn.setAttribute('aria-pressed', String(activo));
        });

        if (persistir) {
            saveProvinciasBolsa(this.especialidadValue, [...this.#activas]);
        }

        window.dispatchEvent(new CustomEvent('sipri:provincias', {
            detail: {
                especialidad: this.especialidadValue,
                provincias: [...this.#activas],
                nombres: this.btnTargets
                    .filter(btn => this.#activas.has(btn.dataset.prov))
                    .map(btn => btn.dataset.nombre ?? btn.textContent.trim()),
            },
        }));
    }
}
