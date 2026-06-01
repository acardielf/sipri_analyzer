import { Controller } from '@hotwired/stimulus';

const MESES   = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const SEMANA  = ['L','M','X','J','V','S','D'];

export default class extends Controller {
    static targets = ['calendar', 'lista', 'resumen'];

    connect() {
        const raw = document.getElementById('cal-data');
        if (!raw) return;
        this._data = JSON.parse(raw.textContent);

        // IDs ordenados descendente (más reciente primero)
        const ids = Object.keys(this._data).sort((a, b) => Number(b) - Number(a));
        if (ids.length > 0) this._show(ids[0]);
    }

    select(event) {
        const id = event.currentTarget.dataset.cursoId;
        this.element.querySelectorAll('[data-curso-id]').forEach(btn =>
            btn.classList.toggle('prov-btn--activo', btn.dataset.cursoId === id)
        );
        this._show(id);
    }

    _show(cursoId) {
        const curso = this._data[cursoId];
        if (!curso) return;

        const convs = curso.convocatorias.filter(c => c.fecha);

        // ── Resumen ──
        const total = convs.reduce((s, c) => s + c.plazas, 0);
        this.resumenTarget.innerHTML = `
            <div class="cal-resumen">
                <span><strong>${convs.length}</strong> convocatorias</span>
                <span class="cal-resumen-sep">·</span>
                <span><strong>${total.toLocaleString('es-ES')}</strong> plazas totales</span>
            </div>`;

        // ── Calendario ──
        this.calendarTarget.innerHTML = '';
        if (convs.length === 0) {
            this.calendarTarget.innerHTML = '<p style="color:var(--sipri-muted)">Sin convocatorias registradas.</p>';
            this.listaTarget.innerHTML = '';
            return;
        }

        const idx = {};
        convs.forEach(c => { idx[c.fecha] = c; });

        const sorted = [...convs].sort((a, b) => a.fecha.localeCompare(b.fecha));
        const first  = new Date(sorted[0].fecha + 'T00:00:00');
        const startY = first.getMonth() >= 8 ? first.getFullYear() : first.getFullYear() - 1;

        const months = [
            [startY, 8], [startY, 9], [startY, 10], [startY, 11],
            [startY+1, 0], [startY+1, 1], [startY+1, 2],
            [startY+1, 3], [startY+1, 4], [startY+1, 5],
        ];

        const grid = document.createElement('div');
        grid.className = 'cal-grid';
        months.forEach(([y, m]) => grid.appendChild(this._buildMonth(y, m, idx)));
        this.calendarTarget.appendChild(grid);

        // ── Lista ──
        this._buildLista(sorted);
    }

    _buildMonth(year, month, idx) {
        const wrap = document.createElement('div');
        wrap.className = 'cal-month';

        const hdr = document.createElement('div');
        hdr.className = 'cal-month-name';
        hdr.textContent = `${MESES[month]} ${year}`;
        wrap.appendChild(hdr);

        const wd = document.createElement('div');
        wd.className = 'cal-weekdays';
        SEMANA.forEach(d => { const s = document.createElement('span'); s.textContent = d; wd.appendChild(s); });
        wrap.appendChild(wd);

        const daysEl = document.createElement('div');
        daysEl.className = 'cal-days';

        const dow = new Date(year, month, 1).getDay();
        const offset = dow === 0 ? 6 : dow - 1;
        for (let i = 0; i < offset; i++) {
            const e = document.createElement('span');
            e.className = 'cal-day cal-day--empty';
            daysEl.appendChild(e);
        }

        const total = new Date(year, month + 1, 0).getDate();
        for (let day = 1; day <= total; day++) {
            const key  = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const conv = idx[key];
            const cell = document.createElement('span');
            cell.className = 'cal-day' + (conv ? ' cal-day--conv' : '');

            if (conv) {
                cell.innerHTML = `<span class="cal-day-num">${day}</span><span class="cal-day-plazas">${conv.plazas}</span>`;
                cell.title     = `Conv. ${conv.id} · ${conv.plazas} plazas`;
                cell.addEventListener('click', () => { window.location.href = conv.url; });
            } else {
                cell.textContent = day;
            }
            daysEl.appendChild(cell);
        }

        wrap.appendChild(daysEl);
        return wrap;
    }

    _buildLista(sorted) {
        const rows = sorted.map(c => {
            const d   = new Date(c.fecha + 'T00:00:00');
            const fmt = d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            return `<tr>
                <td><span class="badge-code">${c.id}</span></td>
                <td style="color:var(--sipri-muted)">${fmt}</td>
                <td class="text-end fw-semibold">${c.plazas}</td>
                <td class="text-end"><span class="badge badge-vacante">${c.vacantes}</span></td>
                <td class="text-end"><span class="badge badge-sustitucion">${c.plazas - c.vacantes}</span></td>
                <td class="text-end">
                    <a href="${c.url}" class="btn btn-sm"
                       style="background:var(--sipri-blue);color:#fff;border-radius:var(--sipri-radius-sm)">
                        Ver <i class="bi bi-chevron-right"></i>
                    </a>
                </td>
            </tr>`;
        }).join('');

        this.listaTarget.innerHTML = `
            <h6 class="cal-lista-title">Detalle de convocatorias</h6>
            <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.88rem">
                <thead style="background:var(--sipri-bg)">
                    <tr>
                        <th style="width:6rem">Nº</th>
                        <th style="width:9rem">Fecha</th>
                        <th class="text-end">Plazas</th>
                        <th class="text-end">Vacantes</th>
                        <th class="text-end">Sustituciones</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            </div>`;
    }
}
