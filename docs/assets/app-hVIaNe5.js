import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.min.js'
import 'bootstrap-icons/font/bootstrap-icons.min.css'

/*
 * Chart.js no se importa aquí: sólo lo usan 740 de las ~20.000 páginas del sitio
 * estático, y lo que cuelgue de este entrypoint se precarga en todas ellas. Vive
 * en el entrypoint `graficos`, que esas plantillas cargan sobrescribiendo el
 * bloque `importmap` de base.html.twig. TomSelect llega por el buscador, que es
 * un controller lazy.
 */

//console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
