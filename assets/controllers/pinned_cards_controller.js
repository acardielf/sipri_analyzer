import { Controller } from '@hotwired/stimulus';
import { getList, getNombre, getHref, saveNombreHrefSilent } from '../lib/favoritos.js';

export default class extends Controller {
    static targets = ['seccion', 'lista', 'grid'];

    connect() {
        this._handler = () => this.#render();
        window.addEventListener('sipri:favoritos', this._handler);
        this.#render();
    }

    disconnect() {
        window.removeEventListener('sipri:favoritos', this._handler);
    }

    #render() {
        const ids = getList('especialidades');
        const grid = this.gridTarget;

        const items = ids.map(id => {
            const btn = grid.querySelector(`[data-favorito-btn-id-value="${CSS.escape(id)}"]`);
            if (!btn) return null;
            const col = btn.closest('[data-search-target="item"]');
            if (!col) return null;
            const link = col.querySelector('a.card-sipri');
            const code = col.querySelector('.card-code')?.textContent?.trim() ?? id;
            // Nombre: localStorage primero, luego DOM
            const nombre = getNombre('especialidades', id)
                ?? col.querySelector('.card-name')?.textContent?.trim()
                ?? id;
            // Href: localStorage primero, luego DOM
            const href = getHref('especialidades', id) ?? link?.href ?? '#';

            // Guardar silenciosamente si lo encontramos en el DOM y no estaba guardado
            if (!getNombre('especialidades', id) || !getHref('especialidades', id)) {
                saveNombreHrefSilent(
                    'especialidades', id,
                    getNombre('especialidades', id) ?? col.querySelector('.card-name')?.textContent?.trim(),
                    getHref('especialidades', id) ?? link?.href,
                );
            }

            return { id, code, nombre, href };
        }).filter(Boolean);

        if (!items.length) {
            this.seccionTarget.classList.add('d-none');
            return;
        }

        this.listaTarget.innerHTML = items.map(({ id, code, nombre, href }) => `
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card-sipri-wrap">
                    <a href="${href}" class="card-sipri card-sipri--pinned">
                        <span class="card-code">${code}</span>
                        <span class="card-name">${nombre}</span>
                        <i class="bi bi-chevron-right card-arrow"></i>
                    </a>
                    <button class="fav-btn fav-btn--activo"
                            data-controller="favorito-btn"
                            data-favorito-btn-type-value="especialidades"
                            data-favorito-btn-id-value="${id}"
                            data-favorito-btn-nombre-value="${nombre}"
                            data-favorito-btn-href-value="${href}"
                            data-action="click->favorito-btn#toggle"
                            aria-label="Quitar de favoritos"
                            title="Quitar de favoritos">
                        <i class="fav-btn-icon bi bi-star-fill"></i>
                    </button>
                </div>
            </div>
        `).join('');

        this.seccionTarget.classList.remove('d-none');
    }
}
