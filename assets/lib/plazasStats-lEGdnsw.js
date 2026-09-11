// Render de las tarjetas de estadísticas de plazas en cliente.
//
// Es el equivalente en JS de `templates/especialidades/_stats_plazas.html.twig`:
// cuando un filtro cambia las estadísticas se recalculan a partir de las filas
// visibles y se vuelven a pintar con este mismo marcado. Si se toca el Twig,
// hay que mantener este fichero al día (y viceversa).

function plural(n, uno, varios) {
    return n === 1 ? uno : varios;
}

/**
 * @param {Object} s stats con la misma forma que `_stats_plazas.html.twig`
 * @param {boolean} [mostrarPosiciones]
 * @returns {string} marcado de `.stats-grid`
 */
export function renderStats(s, mostrarPosiciones = true) {
    const sobrevenidas = s.vacantes - s.vacantesInicio;
    const pctInicio = s.vacantes > 0 ? Math.round((s.vacantesInicio / s.vacantes) * 100) : 0;

    let vacantesFoot;
    if (s.vacantes > 0) {
        let bar = '';
        if (s.vacantesInicio > 0 && sobrevenidas > 0) {
            bar = `<div class="stat-split-bar"><span style="width:${pctInicio}%"></span></div>`;
        }

        let text = '';
        if (s.vacantesInicio > 0) {
            text += sobrevenidas > 0
                ? `<strong>${s.vacantesInicio}</strong> durante septiembre · <strong>${sobrevenidas}</strong> después`
                : 'todas de inicio de curso';
            if (s.vacantesDesiertas > 0) {
                text += '<br>';
            }
        }
        if (s.vacantesDesiertas > 0) {
            text += `<strong class="text-danger">${s.vacantesDesiertas}</strong> sin cubrir`;
        } else if (s.vacantesInicio === 0) {
            text += `<strong>${s.vacantesAdjudicadas}</strong> ${plural(s.vacantesAdjudicadas, 'adjudicada', 'adjudicadas')}`;
        }

        vacantesFoot = `<div class="stat-split" title="${s.vacantesInicio} en septiembre y ${sobrevenidas} ${sobrevenidas === 1 ? 'surgida' : 'surgidas'} después">${bar}<div class="stat-foot">${text}</div></div>`;
    } else {
        vacantesFoot = '<div class="stat-foot">Sin vacantes</div>';
    }

    const posiciones = mostrarPosiciones ? `
        <div class="stat-card">
            ${s.maxOrden > 0 ? `
            <div class="stat-num stat-num--rango">
                nº
                <span style="color:var(--sipri-blue-mid)">${s.minOrden}</span>
                <span class="stat-rango-sep">–</span>
                <span style="color:var(--sipri-accent)">${s.maxOrden}</span>
            </div>
            <div class="stat-label"><i class="bi bi-list-ol me-1"></i>Posiciones llamadas</div>
            <div class="stat-foot">primera y última de la bolsa</div>` : `
            <div class="stat-num stat-num--rango" style="color:var(--sipri-muted)">–</div>
            <div class="stat-label"><i class="bi bi-list-ol me-1"></i>Posiciones llamadas</div>
            <div class="stat-foot">sin posiciones registradas</div>`}
        </div>` : '';

    return `
    <div class="stats-grid mb-4">

        <div class="stat-card">
            <div class="stat-num" style="color:var(--sipri-blue)">${s.plazas}</div>
            <div class="stat-label">Plazas ofertadas</div>
            <div class="stat-foot">
                <strong>${s.vacantes}</strong> ${plural(s.vacantes, 'vacante', 'vacantes')}
                + <strong>${s.sustituciones}</strong> ${plural(s.sustituciones, 'sustitucion', 'sustituciones')}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-num" style="color:var(--sipri-vacante)">${s.vacantes}</div>
            <div class="stat-label"><i class="bi bi-calendar-check me-1"></i>Vacantes</div>
            ${vacantesFoot}
        </div>

        <div class="stat-card">
            <div class="stat-num" style="color:var(--sipri-sustitucion)">${s.sustituciones}</div>
            <div class="stat-label"><i class="bi bi-arrow-repeat me-1"></i>Sustituciones</div>
            <div class="stat-foot">
                <strong>${s.sustitucionesAdjudicadas}</strong> adjudicadas
                · <strong class="text-danger">${s.sustitucionesDesiertas}</strong> desiertas
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-num" style="color:var(--sipri-blue-mid)">${s.adjudicadas}</div>
            <div class="stat-label"><i class="bi bi-person-check me-1"></i>Adjudicadas</div>
            <div class="stat-foot">
                <strong>${s.vacantesAdjudicadas}</strong> ${plural(s.vacantesAdjudicadas, 'vacante', 'vacantes')}
                + <strong>${s.sustitucionesAdjudicadas}</strong> ${plural(s.sustitucionesAdjudicadas, 'sustitucion', 'sustituciones')}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-num" style="color:var(--sipri-muted)">${s.desiertas}</div>
            <div class="stat-label"><i class="bi bi-dash-circle me-1"></i>Desiertas</div>
            <div class="stat-foot">
                <strong class="text-danger">${s.vacantesDesiertas}</strong> ${plural(s.vacantesDesiertas, 'vacante', 'vacantes')}
                + <strong class="text-danger">${s.sustitucionesDesiertas}</strong> ${plural(s.sustitucionesDesiertas, 'sustitucion', 'sustituciones')}
            </div>
        </div>
${posiciones}

    </div>`;
}
