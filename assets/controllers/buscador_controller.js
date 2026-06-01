import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';
import { getList, setList, savePosicion, getPosicion } from '../lib/favoritos.js';

export default class extends Controller {
    static targets = [
        'select', 'posicion', 'resultado',
        'cursoHeaders', 'tbody', 'placeholder',
        'provinciasRow', 'provinciasBtns',
    ];

    #data = null;
    #ts = null;
    #provinciasActivas = new Set();
    #especialidadActual = null;

    connect() {
        this.#provinciasActivas = new Set(getList('provincias'));

        this.#ts = new TomSelect(this.selectTarget, {
            placeholder: 'Escribe para buscar una especialidad…',
            allowEmptyOption: true,
            onChange: (value) => this.#onEspecialidadChange(value),
        });

        this._cargarHandler = (e) => this.#preseleccionar(e.detail.id);
        window.addEventListener('sipri:cargar-especialidad', this._cargarHandler);
    }

    disconnect() {
        this.#ts?.destroy();
        this.#ts = null;
        window.removeEventListener('sipri:cargar-especialidad', this._cargarHandler);
    }

    filtrar() {
        if (this.#data) this.#render();
    }

    toggleProvincia(event) {
        const id = event.currentTarget.dataset.provId;
        if (this.#provinciasActivas.has(id)) {
            this.#provinciasActivas.delete(id);
        } else {
            this.#provinciasActivas.add(id);
        }
        setList('provincias', [...this.#provinciasActivas]);
        this.#updateProvinciasBtns();
        if (this.#data) this.#render();
    }

    async #preseleccionar(id) {
        if (!this.#ts) return;
        this.#ts.setValue(id, false);
    }

    async #onEspecialidadChange(id) {
        this.#data = null;
        this.#especialidadActual = null;

        if (!id) {
            this.resultadoTarget.classList.add('d-none');
            this.placeholderTarget.classList.remove('d-none');
            this.provinciasRowTarget.classList.add('d-none');
            return;
        }

        const url = this.selectTarget.querySelector(`option[value="${CSS.escape(id)}"]`)?.dataset?.url;
        if (!url) return;

        try {
            const resp = await fetch(url);
            if (!resp.ok) throw new Error();
            this.#data = await resp.json();
            this.#especialidadActual = id;
        } catch {
            this.resultadoTarget.classList.add('d-none');
            return;
        }

        // Restaurar la última posición usada para esta especialidad
        const savedPosicion = getPosicion(id);
        this.posicionTarget.value = savedPosicion;

        this.#renderProvinciasBtns();
        this.#render();
    }

    #renderProvinciasBtns() {
        const { provincias } = this.#data;
        this.provinciasBtnsTarget.innerHTML = Object.entries(provincias)
            .map(([id, nombre]) => {
                const activo = this.#provinciasActivas.has(id);
                return `<button class="prov-btn${activo ? ' prov-btn--activo' : ''}"
                            data-prov-id="${id}"
                            data-action="click->buscador#toggleProvincia"
                            title="${nombre}">
                    ${nombre}
                </button>`;
            }).join('');
        this.provinciasRowTarget.classList.remove('d-none');
    }

    #updateProvinciasBtns() {
        this.provinciasBtnsTarget.querySelectorAll('.prov-btn').forEach(btn => {
            btn.classList.toggle('prov-btn--activo', this.#provinciasActivas.has(btn.dataset.provId));
        });
    }

    #render() {
        const { cursos, provincias, posiciones } = this.#data;
        const rawPosicion = this.posicionTarget.value.trim();
        const n = parseInt(rawPosicion, 10);

        if (this.#especialidadActual && rawPosicion) {
            savePosicion(this.#especialidadActual, rawPosicion);
        }
        const filtroProv = this.#provinciasActivas.size > 0;

        this.cursoHeadersTarget.innerHTML =
            '<th scope="col">Posición</th>' +
            cursos.map(c => `<th scope="col">${c}</th>`).join('');

        let entries = Object.entries(posiciones);

        if (filtroProv) {
            entries = entries.filter(([, cursosData]) =>
                Object.values(cursosData).some(llamadas =>
                    llamadas.some(ll => this.#provinciasActivas.has(ll.p))
                )
            );
        }

        if (!isNaN(n) && n > 0) {
            entries = entries.filter(([orden]) => {
                const o = parseInt(orden, 10);
                return o >= n - 10 && o <= n + 10;
            });
        } else {
            entries = entries.slice(0, 60);
        }

        if (!entries.length) {
            this.tbodyTarget.innerHTML = `<tr><td colspan="${cursos.length + 1}" class="text-center text-muted py-4">Sin datos para este criterio.</td></tr>`;
        } else {
            this.tbodyTarget.innerHTML = entries.map(([orden, cursosData]) => {
                const esTarget = parseInt(orden, 10) === n;
                const cells = cursos.map(curso => {
                    const llamadas = cursosData[curso];
                    if (!llamadas?.length) return '<td class="text-center text-muted">—</td>';

                    const badges = llamadas
                        .filter(ll => !filtroProv || this.#provinciasActivas.has(ll.p))
                        .map(ll => {
                            const cls = ll.t === 'V' ? 'badge-vacante' : 'badge-sustitucion';
                            const prov = provincias[ll.p] ?? ll.p;
                            const fav = this.#provinciasActivas.has(ll.p) ? ' badge--fav-prov' : '';
                            return `<span class="buscador-badge ${cls}${fav}">${ll.f}<br><small>${prov}</small></span>`;
                        }).join('');

                    return badges ? `<td>${badges}</td>` : '<td class="text-center text-muted">—</td>';
                }).join('');
                return `<tr${esTarget ? ' class="buscador-row-target"' : ''}><td class="fw-bold">${orden}</td>${cells}</tr>`;
            }).join('');
        }

        this.placeholderTarget.classList.add('d-none');
        this.resultadoTarget.classList.remove('d-none');
    }
}
