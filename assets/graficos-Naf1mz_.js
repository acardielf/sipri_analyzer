/*
 * Entrypoint de las páginas con gráficos: la ficha de especialidad
 * (especialidades/curso.html.twig) y la de evolución (convocatorias/stats.html.twig).
 *
 * Sólo registra los plugins de Chart.js. Del controller se encarga
 * StimulusBundle, que lo tiene a `fetch: "lazy"` en controllers.json: así el
 * controller y Chart.js siguen en el importmap (hace falta para el import
 * dinámico) pero no se precargan en las ~19.500 páginas que no pintan gráficos.
 *
 * Este fichero se ejecuta de forma estática al cargar la página, mientras que el
 * controller lazy llega por un import() posterior, así que los plugins están
 * registrados antes de que se instancie ningún gráfico.
 */
import { Chart } from 'chart.js'
import 'chartjs-plugin-autocolors'
import annotationPlugin from 'chartjs-plugin-annotation'
import datalabelsPlugin from 'chartjs-plugin-datalabels'

Chart.register(annotationPlugin, datalabelsPlugin)
Chart.defaults.plugins.datalabels = { display: false }
