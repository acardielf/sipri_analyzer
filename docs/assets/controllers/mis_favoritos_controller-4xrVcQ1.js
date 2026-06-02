import { Controller } from '@hotwired/stimulus';
import { getList, getNombre, getHref } from '../lib/favoritos.js';

export default class extends Controller {
    static targets = ['lista', 'seccion'];

    connect() {
        this._handler = () => this.#render();
        window.addEventListener('sipri:favoritos', this._handler);
        this.#render();
    }

    disconnect() {
        window.removeEventListener('sipri:favoritos', this._handler);
    }

    cargar(event) {
        const id = event.currentTarget.dataset.id;
        const href = event.currentTarget.dataset.href;

        const buscador = document.querySelector('[data-controller~="buscador"]');
        if (buscador) {
            window.dispatchEvent(new CustomEvent('sipri:cargar-especialidad', { detail: { id } }));
            buscador.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (href && href !== '#') {
            window.location.href = href;
        }
    }

    #render() {
        const ids = getList('especialidades');

        if (!ids.length) {
            this.seccionTarget.classList.add('d-none');
            return;
        }

        const select = document.querySelector('[data-buscador-target="select"]');
        const hasBuscador = !!document.querySelector('[data-controller~="buscador"]');
        const basePath = this.#detectBasePath();

        const items = ids.map(id => {
            const opt = select?.querySelector(`option[value="${CSS.escape(id)}"]`);
            // Nombre: select > localStorage > ID
            const nombre = opt?.textContent?.trim()
                ?? getNombre('especialidades', id)
                ?? id;
            // Href: localStorage > construido desde basePath
            const href = getHref('especialidades', id)
                ?? (basePath !== null ? `${basePath}/especialidad/${id}/` : '#');
            return { id, nombre, href };
        });

        const icon = hasBuscador ? 'bi-arrow-down-circle' : 'bi-box-arrow-up-right';
        const hint = hasBuscador ? 'Cargar en el buscador' : 'Ir a la especialidad';

        this.listaTarget.innerHTML = items.map(({ id, nombre, href }) => `
            <button class="fav-chip" data-id="${id}" data-href="${href}"
                    data-action="click->mis-favoritos#cargar"
                    title="${hint}: ${nombre}">
                <i class="bi bi-star-fill fav-chip-star"></i>
                <span class="fav-chip-nombre">${nombre}</span>
                <i class="bi ${icon} fav-chip-action"></i>
            </button>
        `).join('');

        this.seccionTarget.classList.remove('d-none');
    }

    // Detecta el base path de la app desde cualquier enlace de navegación existente
    #detectBasePath() {
        const patterns = [
            { selector: 'a[href$="/cuerpos"]', strip: '/cuerpos' },
            { selector: 'a[href*="/cuerpo/"]', strip: /\/cuerpo\/.+$/ },
            { selector: 'a[href*="/especialidad/"]', strip: /\/especialidad\/.+$/ },
        ];
        for (const { selector, strip } of patterns) {
            const el = document.querySelector(selector);
            if (!el) continue;
            const href = el.getAttribute('href');
            const base = typeof strip === 'string'
                ? href.slice(0, href.lastIndexOf(strip))
                : href.replace(strip, '');
            return base; // puede ser '' (raíz) o '/sipri_analyzer'
        }
        return null;
    }
}
