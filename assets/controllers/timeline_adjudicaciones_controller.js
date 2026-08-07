import { Controller } from '@hotwired/stimulus';
import { getPosicion, getProvinciasBolsa } from '../lib/favoritos.js';

const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const SEMANA = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

/** El curso escolar ocupa de septiembre a junio. */
const MESES_CURSO = [8, 9, 10, 11, 0, 1, 2, 3, 4, 5];

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['datos', 'barra', 'calendario', 'resumen', 'col'];
    static values = { curso: String, especialidad: String };

    connect() {
        // El filtro puede haberse elegido en una visita anterior, y el selector común
        // quizá ya emitió su evento inicial antes de que este controller (lazy) se
        // conectara: se lee de localStorage para no depender del orden de arranque.
        // Conjunto vacío = todas las provincias.
        this.provincias = new Set(getProvinciasBolsa(this.especialidadValue));
        this.nombresProvincias = this.nombresDe(this.provincias);
        this.posicion = getPosicion(this.especialidadValue);

        this.onProvincias = this.onProvincias.bind(this);
        window.addEventListener('sipri:provincias', this.onProvincias);

        this.aplicarColumnas();

        // Los cursos sin adjudicaciones no traen datos: el controller sigue vivo para
        // filtrar las columnas de la tabla, pero no hay nada que dibujar.
        if (!this.hasDatosTarget) {
            return;
        }

        const paquete = JSON.parse(this.datosTarget.textContent);

        this.convocatorias = paquete.convocatorias.map(iso => new Date(`${iso}T00:00:00`));
        this.puntos = paquete.adjudicaciones.map(([orden, conv, prov]) => ({ orden, conv, prov }));

        this.inicioCurso = this.calcularInicioCurso();

        // Los tabs de curso que no están activos arrancan ocultos y miden 0 px de
        // ancho: dibujar la barra ahí produce un SVG degenerado. El observer espera a
        // que haya ancho real, y de paso cubre el resize de ventana y la rotación en
        // móvil. Sólo reacciona a cambios de ancho para no realimentarse.
        this.ultimoAncho = 0;
        this.observer = new ResizeObserver(() => {
            if (this.anchoBarra() !== 0 && this.anchoBarra() !== this.ultimoAncho) {
                this.programarRender();
            }
        });
        this.observer.observe(this.barraTarget);

        this.onPosicionExterna = this.onPosicionExterna.bind(this);
        window.addEventListener('sipri:posicion', this.onPosicionExterna);
    }

    disconnect() {
        this.observer?.disconnect();
        window.removeEventListener('sipri:provincias', this.onProvincias);
        window.removeEventListener('sipri:posicion', this.onPosicionExterna);
        cancelAnimationFrame(this.pendiente);
    }

    onProvincias(event) {
        if (event.detail.especialidad !== this.especialidadValue) {
            return;
        }

        this.provincias = new Set(event.detail.provincias);
        this.nombresProvincias = event.detail.nombres;

        this.aplicarColumnas();
        this.programarRender();
    }

    /** Oculta las columnas de la tabla de detalle que quedan fuera del filtro. */
    aplicarColumnas() {
        const todas = this.provincias.size === 0;

        this.colTargets.forEach(col => {
            col.classList.toggle('d-none', !todas && !this.provincias.has(col.dataset.prov));
        });
    }

    /** Nombres de provincia a partir de los botones del selector, que es quien los
     *  conoce. Sólo hace falta al arrancar; después vienen en el propio evento. */
    nombresDe(ids) {
        return [...ids].map(id => document
            .querySelector(`[data-selector-provincias-target="btn"][data-prov="${CSS.escape(id)}"]`)
            ?.dataset.nombre ?? id);
    }

    /** La posición se escribe en un único campo, fuera de las pestañas de curso. */
    onPosicionExterna(event) {
        if (event.detail.especialidad !== this.especialidadValue) {
            return;
        }

        this.posicion = event.detail.posicion;
        this.programarRender();
    }

    programarRender() {
        cancelAnimationFrame(this.pendiente);
        this.pendiente = requestAnimationFrame(() => this.render());
    }

    /** El ancho lo manda el hueco real de la barra, no el del bloque: el contenedor
     *  tiene padding lateral y medirlo a él haría que el SVG se saliera por la derecha. */
    anchoBarra() {
        return this.barraTarget.clientWidth;
    }

    render() {
        if (!this.hasBarraTarget) {
            return;
        }

        const ancho = this.anchoBarra();
        if (ancho === 0) {
            return;
        }
        this.ultimoAncho = ancho;

        const puntos = this.provincias.size === 0
            ? this.puntos
            : this.puntos.filter(p => this.provincias.has(p.prov));

        this.dibujarBarra(puntos, ancho);
        this.dibujarCalendario(puntos);
    }

    /** Posición que ha escrito el usuario, o null si no hay ninguna válida. */
    posicionBuscada() {
        const n = parseInt(this.posicion, 10);

        return Number.isInteger(n) && n > 0 ? n : null;
    }

    /* ── Barra de recorrido ─────────────────────────────────────────────────── */

    /**
     * Tramos entre los máximos alcanzados al cierre de cada mes. Se apoya en el
     * máximo y no en el rango de posiciones llamadas cada mes porque ese rango no
     * es monótono: se sigue llamando a posiciones bajas todo el curso, así que los
     * rangos mensuales se solapan y no se pueden pintar como tramos contiguos.
     */
    calcularTramos(puntos) {
        const porMes = new Map();
        let maximo = -Infinity;

        [...puntos].sort((a, b) => a.conv - b.conv).forEach(p => {
            maximo = Math.max(maximo, p.orden);
            const fecha = this.convocatorias[p.conv];
            porMes.set(`${fecha.getFullYear()}-${fecha.getMonth()}`, { fecha, maximo });
        });

        let desde = Math.min(...puntos.map(p => p.orden));

        const tramos = [...porMes.values()].map(({ fecha, maximo: hasta }) => {
            const tramo = { desde, hasta, fecha };
            desde = hasta;
            return tramo;
        });

        // Un mes sin avance produce un tramo de ancho cero: no se pinta ni se
        // etiqueta, pero si todos lo son (un único día de adjudicaciones) hay que
        // conservar el primero para no dejar la barra en blanco.
        const conAvance = tramos.filter(t => t.hasta > t.desde);

        return conAvance.length > 0 ? conAvance : tramos.slice(0, 1);
    }

    dibujarBarra(puntos, ancho) {
        if (puntos.length === 0) {
            this.barraTarget.innerHTML = '<p class="timeline-vacio">Sin adjudicaciones con este filtro.</p>';
            return;
        }

        const minimo = Math.min(...puntos.map(p => p.orden));
        const maximo = Math.max(...puntos.map(p => p.orden));

        // Sin recorrido no hay nada que dibujar: una barra de ancho cero sólo
        // confundiría. Pasa en especialidades con una única adjudicación en el curso.
        if (minimo === maximo) {
            this.barraTarget.innerHTML =
                `<p class="timeline-vacio">Sólo se ha llamado a la posición <strong>${minimo}</strong> en todo el curso.</p>`;
            return;
        }

        const tramos = this.calcularTramos(puntos);
        const rango = maximo - minimo;

        // El margen lateral reserva sitio para la mitad de la etiqueta de mes del
        // último tramo, que va centrada sobre el extremo derecho de la barra.
        const alto = 78;
        const margen = 18;
        const util = ancho - margen * 2;
        const yBarra = 22;
        const altoBarra = 18;

        const x = valor => margen + ((valor - minimo) / rango) * util;

        const piezas = [];

        piezas.push(
            `<rect x="${margen}" y="${yBarra}" width="${util}" height="${altoBarra}" rx="4" class="timeline-barra-fondo"/>`
        );

        tramos.forEach((tramo, i) => {
            const x0 = x(tramo.desde);
            const x1 = x(tramo.hasta);
            const intensidad = tramos.length > 1 ? i / (tramos.length - 1) : 0;
            const mes = this.nombreMes(tramo.fecha);

            piezas.push(
                `<rect x="${x0}" y="${yBarra}" width="${Math.max(x1 - x0, 1)}" height="${altoBarra}"`
                + ` class="timeline-tramo" style="opacity:${(0.35 + intensidad * 0.65).toFixed(2)}">`
                + `<title>${mes}: la bolsa llegó hasta la posición ${tramo.hasta}`
                + ` (avance del mes: ${tramo.desde} → ${tramo.hasta})</title></rect>`
            );

            piezas.push(`<line x1="${x1}" y1="${yBarra}" x2="${x1}" y2="${yBarra + altoBarra + 5}" class="timeline-tick"/>`);
        });

        // Las etiquetas no caben todas: el eje es la posición, no el calendario, así
        // que un mes de mucho avance se lleva medio ancho y los demás se apiñan. Se
        // reparten de derecha a izquierda para que el mes más reciente —el que dice
        // por dónde va la bolsa ahora— nunca se quede sin rotular.
        let ultimaEtiqueta = Infinity;

        [...tramos].reverse().forEach(tramo => {
            const x1 = x(tramo.hasta);
            if (ultimaEtiqueta - x1 < 52) {
                return;
            }

            piezas.push(
                `<text x="${x1}" y="${yBarra + altoBarra + 17}" class="timeline-etiqueta-mes">${this.nombreMes(tramo.fecha)}</text>`
                + `<text x="${x1}" y="${yBarra + altoBarra + 29}" class="timeline-etiqueta-pos">${tramo.hasta}</text>`
            );
            ultimaEtiqueta = x1;
        });

        // La posición buscada se marca sobre la barra para situarla dentro del recorrido
        const buscada = this.posicionBuscada();
        if (buscada !== null && buscada >= minimo && buscada <= maximo) {
            const xb = x(buscada);
            piezas.push(
                `<line x1="${xb}" y1="${yBarra - 5}" x2="${xb}" y2="${yBarra + altoBarra + 5}" class="timeline-marca-tu"/>`
                + `<text x="${xb}" y="${yBarra - 9}" class="timeline-marca-tu-txt">tú (${buscada})</text>`
            );
        }

        piezas.push(`<text x="${margen}" y="14" class="timeline-extremo">pos. ${minimo}</text>`);
        piezas.push(`<text x="${ancho - margen}" y="14" class="timeline-extremo timeline-extremo--fin">pos. ${maximo}</text>`);

        this.barraTarget.innerHTML =
            `<svg viewBox="0 0 ${ancho} ${alto}" width="${ancho}" height="${alto}" role="img"`
            + ` aria-label="La bolsa recorrió de la posición ${minimo} a la ${maximo} durante el curso ${this.cursoValue}.">`
            + piezas.join('')
            + '</svg>';
    }

    /* ── Calendario del curso ───────────────────────────────────────────────── */

    /**
     * Hasta qué posición se llamó en cada convocatoria. Es la cifra que permite
     * estimar el turno propio: si una convocatoria bajó más allá de tu número, es
     * que la lista pasó por tu altura.
     *
     * @return Map de fecha 'YYYY-MM-DD' al máximo llamado ese día
     */
    maximosPorDia(puntos) {
        const porDia = new Map();

        puntos.forEach(p => {
            const clave = this.claveDia(this.convocatorias[p.conv]);
            porDia.set(clave, Math.max(porDia.get(clave) ?? 0, p.orden));
        });

        return porDia;
    }

    dibujarCalendario(puntos) {
        const maximos = this.maximosPorDia(puntos);
        const buscada = this.posicionBuscada();

        this.actualizarResumen(maximos, buscada);

        if (maximos.size === 0) {
            this.calendarioTarget.innerHTML = '<p class="timeline-vacio">Sin convocatorias con este filtro.</p>';
            return;
        }

        const anio = this.inicioCurso.getFullYear();
        const meses = MESES_CURSO.map(mes => this.mesHtml(mes < 8 ? anio + 1 : anio, mes, maximos, buscada));

        this.calendarioTarget.innerHTML = `<div class="cal-grid">${meses.join('')}</div>`;
    }

    mesHtml(anio, mes, maximos, buscada) {
        const primerDia = new Date(anio, mes, 1).getDay();
        const offset = primerDia === 0 ? 6 : primerDia - 1;
        const total = new Date(anio, mes + 1, 0).getDate();

        const celdas = [];

        for (let i = 0; i < offset; i++) {
            celdas.push('<span class="cal-day cal-day--empty"></span>');
        }

        for (let dia = 1; dia <= total; dia++) {
            const maximo = maximos.get(this.claveDia(new Date(anio, mes, dia)));

            if (maximo === undefined) {
                celdas.push(`<span class="cal-day">${dia}</span>`);
                continue;
            }

            const alcanza = buscada === null || maximo >= buscada;
            const clase = buscada === null
                ? 'cal-day cal-day--conv'
                : `cal-day cal-day--conv ${alcanza ? 'cal-day--alcanza' : 'cal-day--corto'}`;

            const titulo = buscada === null
                ? `Se adjudicó hasta la posición ${maximo}`
                : `Se adjudicó hasta la posición ${maximo}: ${alcanza ? 'pasó de tu número' : 'no llegó a tu número'}`;

            celdas.push(
                `<span class="${clase}" title="${titulo}">`
                + `<span class="cal-day-num">${dia}</span>`
                + `<span class="cal-day-pos">nº ${maximo}</span></span>`
            );
        }

        return '<div class="cal-month">'
            + `<div class="cal-month-name">${MESES[mes]} ${anio}</div>`
            + `<div class="cal-weekdays">${SEMANA.map(d => `<span>${d}</span>`).join('')}</div>`
            + `<div class="cal-days">${celdas.join('')}</div>`
            + '</div>';
    }

    actualizarResumen(maximos, buscada) {
        if (maximos.size === 0) {
            this.resumenTarget.innerHTML = '';
            return;
        }

        const ambito = this.textoAmbito();

        if (buscada === null) {
            this.resumenTarget.innerHTML =
                '<span class="timeline-resumen-neutro">Cada casilla muestra hasta qué posición se adjudicó ese día'
                + `${ambito}. Especifica arriba tu posición en la bolsa para compararla con estos datos.</span>`;
            return;
        }

        const dias = [...maximos.entries()].sort((a, b) => a[0].localeCompare(b[0]));
        const alcanzan = dias.filter(([, maximo]) => maximo >= buscada);

        if (alcanzan.length === 0) {
            this.resumenTarget.innerHTML =
                `En ${this.cursoValue}${ambito} la bolsa no llegó a la posición <strong>${buscada}</strong>.`;
            return;
        }

        this.resumenTarget.innerHTML =
            `En ${this.cursoValue}${ambito}, la primera vez que la bolsa llegó a la posición`
            + ` <strong>${buscada}</strong> fue el`
            + ` <strong>${this.fechaLarga(new Date(`${alcanzan[0][0]}T00:00:00`))}</strong>.`;
    }

    /** Coletilla que nombra el filtro activo, para que el resumen no mienta al filtrar. */
    textoAmbito() {
        const nombres = this.nombresProvincias;

        if (nombres.length === 0) {
            return '';
        }

        if (nombres.length > 3) {
            return ` en ${nombres.length} provincias`;
        }

        return ` en ${nombres.slice(0, -1).join(', ')}${nombres.length > 1 ? ' y ' : ''}${nombres.at(-1)}`;
    }

    /* ── Utilidades de fecha ────────────────────────────────────────────────── */

    /** El curso arranca el 1 de septiembre. */
    calcularInicioCurso() {
        const primera = this.convocatorias[0];
        const anio = primera.getMonth() >= 8 ? primera.getFullYear() : primera.getFullYear() - 1;

        return new Date(anio, 8, 1);
    }

    /** Clave local 'YYYY-MM-DD'. No vale toISOString(): desplaza el día según la zona. */
    claveDia(fecha) {
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');

        return `${fecha.getFullYear()}-${mes}-${dia}`;
    }

    nombreMes(fecha) {
        return fecha.toLocaleDateString('es-ES', { month: 'short' }).replace('.', '');
    }

    fechaLarga(fecha) {
        return fecha.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
    }
}
