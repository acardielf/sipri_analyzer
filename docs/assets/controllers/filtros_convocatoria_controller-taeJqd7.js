import { Controller } from '@hotwired/stimulus';
import { getList }     from '../lib/favoritos.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['fila', 'grupo', 'btnProv', 'btnEsp'];
    // Condiciones globales de la convocatoria: sin ellas no se pueden reproducir
    // en cliente las mismas cifras que calcula ConvocatoriaDetalleController
    static values  = { inicioCurso: Boolean, conAdjudicaciones: Boolean };

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
        // 1. Filas — y, de paso, recuento de lo que queda visible
        const s = {
            plazas: 0, adjudicadas: 0, desiertas: 0,
            vacantes: 0, vacantesAdjudicadas: 0, vacantesDesiertas: 0,
            sustituciones: 0, sustitucionesAdjudicadas: 0, sustitucionesDesiertas: 0,
        };

        this.filaTargets.forEach(row => {
            const matchProv = this._prov === 'all' || row.dataset.prov === this._prov;
            const matchEsp  = this._esp  === 'all' || row.dataset.esp  === this._esp;
            const visible   = matchProv && matchEsp;
            row.classList.toggle('d-none', !visible);
            if (!visible) return;

            // La plantilla omite `data-n` y `data-adj` cuando valen su valor por
            // defecto: casi todas las filas ofertan un puesto y lo adjudican
            const puestos = row.dataset.n !== undefined ? +row.dataset.n : 1;
            const adj     = row.dataset.adj !== undefined
                ? +row.dataset.adj
                // Sin adjudicaciones extraídas no se puede saber qué se cubrió
                : (this.conAdjudicacionesValue ? puestos : 0);
            const sinCubrir = this.conAdjudicacionesValue ? Math.max(0, puestos - adj) : 0;

            s.plazas      += puestos;
            s.adjudicadas += adj;
            s.desiertas   += sinCubrir;

            if (row.dataset.tipo === 'V') {
                s.vacantes            += puestos;
                s.vacantesAdjudicadas += adj;
                s.vacantesDesiertas   += sinCubrir;
            } else {
                s.sustituciones            += puestos;
                s.sustitucionesAdjudicadas += adj;
                s.sustitucionesDesiertas   += sinCubrir;
            }
        });

        // Una convocatoria de septiembre publica las vacantes de inicio de curso
        s.vacantesInicio = this.inicioCursoValue ? s.vacantes : 0;
        this._pintarStats(s);

        // 2. Grupos: se ocultan si se quedan vacíos y su contador sigue al filtro.
        //    Cuenta filas, no puestos ofertados, como hace la plantilla.
        this.grupoTargets.forEach(grupo => {
            const visibles = Array.from(
                grupo.querySelectorAll('[data-filtros-convocatoria-target="fila"]')
            ).filter(f => !f.classList.contains('d-none')).length;

            grupo.classList.toggle('d-none', visibles === 0);

            const contador = grupo.querySelector('[data-grupo-contador]');
            if (contador) contador.textContent = `(${visibles})`;
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

    // ── Tarjetas de resumen ───────────────────────────────────────────────────
    // Reproduce `especialidades/_stats_plazas.html.twig`: si cambia el pie de
    // una tarjeta allí, hay que cambiarlo aquí también.
    _pintarStats(s) {
        const p = (n, sing, plur) => n != 1 ? plur : sing;

        this._card('plazas', s.plazas, `
            <div class="stat-foot">
                <strong>${s.vacantes}</strong> vacante${p(s.vacantes, '', 's')}
                + <strong>${s.sustituciones}</strong> sustitucion${p(s.sustituciones, '', 'es')}
            </div>`);

        this._card('vacantes', s.vacantes, this._pieVacantes(s));

        this._card('sustituciones', s.sustituciones, `
            <div class="stat-foot">
                <strong>${s.sustitucionesAdjudicadas}</strong> adjudicadas
                · <strong class="text-danger">${s.sustitucionesDesiertas}</strong> desiertas
            </div>`);

        this._card('adjudicadas', s.adjudicadas, `
            <div class="stat-foot">
                <strong>${s.vacantesAdjudicadas}</strong> vacante${p(s.vacantesAdjudicadas, '', 's')}
                + <strong>${s.sustitucionesAdjudicadas}</strong> sustitucion${p(s.sustitucionesAdjudicadas, '', 'es')}
            </div>`);

        this._card('desiertas', s.desiertas, `
            <div class="stat-foot">
                <strong class="text-danger">${s.vacantesDesiertas}</strong> vacante${p(s.vacantesDesiertas, '', 's')}
                + <strong class="text-danger">${s.sustitucionesDesiertas}</strong> sustitucion${p(s.sustitucionesDesiertas, '', 'es')}
            </div>`);
    }

    _pieVacantes(s) {
        if (s.vacantes === 0) return '<div class="stat-foot">Sin vacantes</div>';

        const sobrevenidas = s.vacantes - s.vacantesInicio;
        const pctInicio    = Math.round(s.vacantesInicio / s.vacantes * 100);
        const title        = `${s.vacantesInicio} en septiembre y ${sobrevenidas} ` +
                             `${sobrevenidas === 1 ? 'surgida' : 'surgidas'} después`;

        // La barra solo aporta cuando hay mezcla de ambas
        const barra = (s.vacantesInicio > 0 && sobrevenidas > 0)
            ? `<div class="stat-split-bar"><span style="width:${pctInicio}%"></span></div>`
            : '';

        let texto = '';
        if (s.vacantesInicio > 0) {
            texto += sobrevenidas > 0
                ? `<strong>${s.vacantesInicio}</strong> durante septiembre · <strong>${sobrevenidas}</strong> después`
                : 'todas de inicio de curso';
            if (s.vacantesDesiertas > 0) texto += '<br>';
        }
        if (s.vacantesDesiertas > 0) {
            texto += `<strong class="text-danger">${s.vacantesDesiertas}</strong> sin cubrir`;
        } else if (s.vacantesInicio === 0) {
            texto += `<strong>${s.vacantesAdjudicadas}</strong> adjudicada${s.vacantesAdjudicadas != 1 ? 's' : ''}`;
        }

        return `<div class="stat-split" title="${title}">${barra}<div class="stat-foot">${texto}</div></div>`;
    }

    _card(nombre, numero, cuerpoHtml) {
        const card = this.element.querySelector(`[data-stat-card="${nombre}"]`);
        if (!card) return;
        card.querySelector('.stat-num').textContent = numero;
        card.querySelector('[data-stat-body]').innerHTML = cuerpoHtml;
    }
}
