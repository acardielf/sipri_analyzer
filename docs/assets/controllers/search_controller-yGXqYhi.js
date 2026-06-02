import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'item', 'count', 'empty'];
    static values  = { minLength: { type: Number, default: 0 } };

    connect() {
        this._timer = null;
        this._updateCount();
    }

    filter() {
        clearTimeout(this._timer);
        this._timer = setTimeout(() => this._doFilter(), 80);
    }

    _doFilter() {
        const query = this.inputTarget.value.toLowerCase().trim();
        let visible = 0;
        let delay   = 0;

        this.itemTargets.forEach(item => {
            const terms   = (item.dataset.searchTerms || '').toLowerCase();
            const matches = !query || terms.includes(query);

            if (matches) {
                item.classList.remove('search-hidden');
                item.style.setProperty('--anim-delay', `${Math.min(delay, 12) * 28}ms`);
                item.classList.add('search-visible');
                delay++;
                visible++;
            } else {
                item.classList.add('search-hidden');
                item.classList.remove('search-visible');
            }
        });

        this._updateCount(visible);

        if (this.hasEmptyTarget) {
            this.emptyTarget.classList.toggle('d-none', visible > 0);
        }
    }

    _updateCount(n) {
        if (!this.hasCountTarget) return;
        const total = n ?? this.itemTargets.length;
        this.countTarget.textContent = total;
    }
}
