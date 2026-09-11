import { Controller } from '@hotwired/stimulus';

/**
 * Refleja la pestaña activa en el fragmento de la URL, para que recargar o
 * compartir el enlace devuelva a donde estabas: `#tab=currar&curso=2024`.
 *
 * Cada grupo de pestañas declara su propia clave, así que anidar grupos (las
 * pestañas de la ficha y las pills de curso dentro de una de ellas) funciona sin
 * que se pisen entre sí.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['btn'];
    static values = { key: String };

    connect() {
        this.onShown = this.onShown.bind(this);
        this.element.addEventListener('shown.bs.tab', this.onShown);

        this.restaurar();
    }

    disconnect() {
        this.element.removeEventListener('shown.bs.tab', this.onShown);
    }

    restaurar() {
        const valor = this.params().get(this.keyValue);
        if (!valor) {
            return;
        }

        const btn = this.btnTargets.find(b => b.dataset.hash === valor);

        // Se activa con un clic sintético en lugar de instanciando bootstrap.Tab:
        // el importmap es global y añadir ahí un import de Bootstrap afectaría a las
        // ~19.500 páginas del sitio, no sólo a las que tienen pestañas.
        if (btn && !btn.classList.contains('active')) {
            btn.click();
        }
    }

    onShown(event) {
        // Los grupos anidados burbujean su evento hasta aquí: sólo interesa el propio
        if (!this.btnTargets.includes(event.target)) {
            return;
        }

        const params = this.params();
        params.set(this.keyValue, event.target.dataset.hash);

        history.replaceState(null, '', `${location.pathname}${location.search}#${params}`);
    }

    /** El fragmento puede traer también un ancla de posición (#o743); al parsearlo
     *  como pares clave-valor, esa entrada simplemente se ignora. */
    params() {
        return new URLSearchParams(location.hash.slice(1));
    }
}
