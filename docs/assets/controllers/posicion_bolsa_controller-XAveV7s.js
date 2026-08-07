import { Controller } from '@hotwired/stimulus';
import { savePosicion, getPosicion } from '../lib/favoritos.js';

/**
 * Campo con la posición del usuario en la bolsa. Es único para las cuatro pestañas
 * de curso, así que publica el valor en `window` y cada pestaña lo aplica por su
 * cuenta. Se recuerda por especialidad, compartido con el buscador de la portada.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input'];
    static values = { especialidad: String };

    connect() {
        this.inputTarget.value = getPosicion(this.especialidadValue);
        this.publicar();
    }

    cambiar() {
        savePosicion(this.especialidadValue, this.inputTarget.value.trim());
        this.publicar();
    }

    publicar() {
        window.dispatchEvent(new CustomEvent('sipri:posicion', {
            detail: {
                especialidad: this.especialidadValue,
                posicion: this.inputTarget.value.trim(),
            },
        }));
    }
}
