import { Controller } from '@hotwired/stimulus';
import { getList }     from '../lib/favoritos.js';

export default class extends Controller {
    static targets = ['fila', 'grupo', 'btnProv', 'btnEsp'];

    connect() {
        this._prov = 'all';
        this._esp  = 'all';
        this._favs = getList('especialidades');
        this._aplicarFavoritas();
    }

    // ── Favoritas ─────────────────────────────────────────────────────────────
    _aplicarFavoritas() {
        if (!this._favs.length) return;

        const container = this.element.querySelector('.esp-filter-btns');
        if (!container) return;

        // Marcar botones favoritos con estrella
        this.btnEspTargets.forEach(btn => {
            if (btn.dataset.esp === 'all') return;
            if (this._favs.includes(btn.dataset.esp)) {
                btn.classList.add('prov-btn--favorita');
                if (!btn.querySelector('.esp-fav-star')) {
                    const star = document.createElement('i');
                    star.className = 'bi bi-star-fill esp-fav-star';
                    btn.prepend(star);
                }
            }
        });

        // Reordenar: favoritas primero
        const todosBtn  = container.querySelector('[data-esp="all"]');
        const restoBtns = Array.from(container.querySelectorAll('[data-esp]:not([data-esp="all"])'));

        restoBtns.sort((a, b) => {
            const af = this._favs.includes(a.dataset.esp);
            const bf = this._favs.includes(b.dataset.esp);
            return (af === bf) ? 0 : af ? -1 : 1;
        });

        restoBtns.forEach(b => container.appendChild(b));
    }

    // ── Buscador interno ──────────────────────────────────────────────────────
    searchEsp(event) {
        const q = event.target.value.toLowerCase().trim();
        this.btnEspTargets.forEach(btn => {
            if (btn.dataset.esp === 'all') return;
            btn.style.display = (!q || btn.textContent.toLowerCase().includes(q)) ? '' : 'none';
        });
    }

    // ── Filtro provincia ──────────────────────────────────────────────────────
    selectProv(event) {
        this._prov = event.currentTarget.dataset.prov;

        if (this._esp !== 'all' && this._prov !== 'all') {
            const existe = this.filaTargets.some(
                r => r.dataset.prov === this._prov && r.dataset.esp === this._esp
            );
            if (!existe) {
                this._esp = 'all';
                this.btnEspTargets.forEach(b =>
                    b.classList.toggle('prov-btn--activo', b.dataset.esp === 'all')
                );
            }
        }

        this.btnProvTargets.forEach(b =>
            b.classList.toggle('prov-btn--activo', b.dataset.prov === this._prov)
        );
        this._apply();
    }

    // ── Filtro especialidad ───────────────────────────────────────────────────
    selectEsp(event) {
        this._esp = event.currentTarget.dataset.esp;

        if (this._prov !== 'all' && this._esp !== 'all') {
            const existe = this.filaTargets.some(
                r => r.dataset.esp === this._esp && r.dataset.prov === this._prov
            );
            if (!existe) {
                this._prov = 'all';
                this.btnProvTargets.forEach(b =>
                    b.classList.toggle('prov-btn--activo', b.dataset.prov === 'all')
                );
            }
        }

        this.btnEspTargets.forEach(b =>
            b.classList.toggle('prov-btn--activo', b.dataset.esp === this._esp)
        );
        this._apply();
    }

    // ── Aplicar filtros ───────────────────────────────────────────────────────
    _apply() {
        // 1. Filas
        this.filaTargets.forEach(row => {
            const matchProv = this._prov === 'all' || row.dataset.prov === this._prov;
            const matchEsp  = this._esp  === 'all' || row.dataset.esp  === this._esp;
            row.classList.toggle('d-none', !(matchProv && matchEsp));
        });

        // 2. Grupos (cabeceras de provincia)
        this.grupoTargets.forEach(grupo => {
            const hayVisibles = Array.from(
                grupo.querySelectorAll('[data-filtros-convocatoria-target="fila"]')
            ).some(f => !f.classList.contains('d-none'));
            grupo.classList.toggle('d-none', !hayVisibles);
        });

        // 3. Disponibilidad cruzada — especialidades según provincia activa
        const espDisponibles = new Set(['all']);
        this.filaTargets.forEach(row => {
            if (this._prov === 'all' || row.dataset.prov === this._prov) {
                espDisponibles.add(row.dataset.esp);
            }
        });
        this.btnEspTargets.forEach(btn => {
            const ok = espDisponibles.has(btn.dataset.esp);
            btn.classList.toggle('prov-btn--disabled', !ok);
            btn.disabled = !ok;
        });

        // 4. Disponibilidad cruzada — provincias según especialidad activa
        const provDisponibles = new Set(['all']);
        this.filaTargets.forEach(row => {
            if (this._esp === 'all' || row.dataset.esp === this._esp) {
                provDisponibles.add(row.dataset.prov);
            }
        });
        this.btnProvTargets.forEach(btn => {
            const ok = provDisponibles.has(btn.dataset.prov);
            btn.classList.toggle('prov-btn--disabled', !ok);
            btn.disabled = !ok;
        });
    }
}
