import { Controller } from '@hotwired/stimulus';

/**
 * Filtra las filas de la tabla de plazas por provincia y por tipo. Ambos
 * filtros se combinan: una fila se muestra si supera los dos.
 */
export default class extends Controller {
    static targets = ['fila', 'btnProv', 'btnTipo', 'count'];

    connect() {
        this._prov = 'all';
        this._tipo = 'all';
    }

    selectProv(event) {
        this._prov = event.currentTarget.dataset.prov;
        this._marcarActivo(this.btnProvTargets, event.currentTarget);
        this._aplicar();
    }

    selectTipo(event) {
        this._tipo = event.currentTarget.dataset.tipo;
        this._marcarActivo(this.btnTipoTargets, event.currentTarget);
        this._aplicar();
    }

    _marcarActivo(botones, activo) {
        botones.forEach(btn => btn.classList.toggle('prov-btn--activo', btn === activo));
    }

    _aplicar() {
        let visibles = 0;

        this.filaTargets.forEach(fila => {
            const okProv = this._prov === 'all' || fila.dataset.prov === this._prov;
            const okTipo = this._tipo === 'all' || fila.dataset.tipo === this._tipo;
            const visible = okProv && okTipo;

            fila.classList.toggle('d-none', !visible);
            // Una fila puede ofertar varios puestos
            if (visible) visibles += parseInt(fila.dataset.numero || '1', 10);
        });

        if (this.hasCountTarget) {
            this.countTarget.textContent = visibles;
        }
    }
}
