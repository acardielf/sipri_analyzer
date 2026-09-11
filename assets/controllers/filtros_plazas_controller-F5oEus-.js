import { Controller } from '@hotwired/stimulus';
import { renderStats } from '../lib/plazasStats.js';

/**
 * Filtra las filas de la tabla de plazas por provincia y por tipo. Ambos
 * filtros se combinan: una fila se muestra si supera los dos.
 *
 * El estado de los filtros vive en la query string (`?prov=4&tipo=V`), así que
 * cualquier combinación se puede compartir copiando la URL. Al entrar con esos
 * parámetros se aplican directamente; al cambiar un filtro se reescribe la URI
 * con history.replaceState (sin recargar).
 *
 * Además recalculan las tarjetas de estadísticas a partir de las filas
 * visibles, de modo que reflejan la provincia y el tipo seleccionados.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['fila', 'btnProv', 'btnTipo', 'count', 'stats', 'crumbProvNombre', 'badgeProvNombre'];

    connect() {
        this._prov = 'all';
        this._tipo = 'all';

        const params = new URLSearchParams(location.search);

        const prov = params.get('prov');
        if (prov) {
            const btn = this.btnProvTargets.find(b => b.dataset.prov === prov);
            if (btn) {
                this._prov = prov;
                this._marcarActivo(this.btnProvTargets, btn);
            }
        }

        const tipo = params.get('tipo');
        if (tipo === 'V' || tipo === 'S') {
            const btn = this.btnTipoTargets.find(b => b.dataset.tipo === tipo);
            if (btn) {
                this._tipo = tipo;
                this._marcarActivo(this.btnTipoTargets, btn);
            }
        }

        this._aplicar();
    }

    selectProv(event) {
        this._prov = event.currentTarget.dataset.prov;
        this._marcarActivo(this.btnProvTargets, event.currentTarget);
        this._actualizarUri();
        this._aplicar();
    }

    selectTipo(event) {
        this._tipo = event.currentTarget.dataset.tipo;
        this._marcarActivo(this.btnTipoTargets, event.currentTarget);
        this._actualizarUri();
        this._aplicar();
    }

    _actualizarUri() {
        const params = new URLSearchParams();
        if (this._prov !== 'all') params.set('prov', this._prov);
        if (this._tipo !== 'all') params.set('tipo', this._tipo);

        const qs = params.toString();
        history.replaceState(null, '', qs ? `?${qs}` : location.pathname);
    }

    _marcarActivo(botones, activo) {
        botones.forEach(btn => btn.classList.toggle('prov-btn--activo', btn === activo));
    }

    _aplicar() {
        const stats = {
            plazas: 0,
            vacantes: 0,
            vacantesInicio: 0,
            vacantesDesiertas: 0,
            vacantesAdjudicadas: 0,
            sustituciones: 0,
            sustitucionesDesiertas: 0,
            sustitucionesAdjudicadas: 0,
            adjudicadas: 0,
            desiertas: 0,
            minOrden: 0,
            maxOrden: 0,
        };
        let visibles = 0;
        // Convocatoria de la última fila visible, para reubicar el separador de grupo
        let convoPrevia = null;

        this.filaTargets.forEach(fila => {
            const okProv = this._prov === 'all' || fila.dataset.prov === this._prov;
            const okTipo = this._tipo === 'all' || fila.dataset.tipo === this._tipo;
            const visible = okProv && okTipo;

            fila.classList.toggle('d-none', !visible);
            if (!visible) return;

            const convo = fila.dataset.convo;
            fila.classList.toggle('tr-convo-ini', convoPrevia !== null && convo !== convoPrevia);
            convoPrevia = convo;

            // Una fila puede ofertar varios puestos
            const numero = parseInt(fila.dataset.numero || '1', 10);
            const esVacante = fila.dataset.tipo === 'V';
            const sinCubrir = parseInt(fila.dataset.sinCubrir || '0', 10);
            const inicio = fila.dataset.inicio === '1';
            const ordenes = (fila.dataset.adj || '')
                .split(',')
                .map(o => parseInt(o.trim(), 10))
                .filter(o => Number.isFinite(o) && o > 0);

            visibles += numero;
            stats.plazas += numero;

            if (esVacante) {
                stats.vacantes += numero;
                if (inicio) stats.vacantesInicio += numero;
                stats.vacantesAdjudicadas += ordenes.length;
                stats.vacantesDesiertas += sinCubrir;
            } else {
                stats.sustituciones += numero;
                stats.sustitucionesAdjudicadas += ordenes.length;
                stats.sustitucionesDesiertas += sinCubrir;
            }

            if (ordenes.length) {
                const min = Math.min(...ordenes);
                const max = Math.max(...ordenes);
                if (stats.minOrden === 0 || min < stats.minOrden) stats.minOrden = min;
                if (max > stats.maxOrden) stats.maxOrden = max;
            }
        });

        stats.adjudicadas = stats.vacantesAdjudicadas + stats.sustitucionesAdjudicadas;
        stats.desiertas = stats.vacantesDesiertas + stats.sustitucionesDesiertas;

        this._actualizarCabecera();

        if (this.hasCountTarget) {
            this.countTarget.textContent = visibles;
        }
        if (this.hasStatsTarget) {
            this.statsTarget.innerHTML = renderStats(stats, true);
        }
    }

    _nombreProvinciaActiva() {
        const btn = this.btnProvTargets.find(b => b.dataset.prov === this._prov);
        return btn?.dataset.provNombre || '';
    }

    _actualizarCabecera() {
        const nombre = this._prov === 'all' ? 'Todas las provincias' : this._nombreProvinciaActiva();
        if (this.hasCrumbProvNombreTarget) this.crumbProvNombreTarget.textContent = nombre;
        if (this.hasBadgeProvNombreTarget) this.badgeProvNombreTarget.textContent = nombre;
    }
}
