import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this._onScroll = this._onScroll.bind(this);
        window.addEventListener('scroll', this._onScroll, { passive: true });
        this._onScroll();
        this._markActive();
    }

    disconnect() {
        window.removeEventListener('scroll', this._onScroll);
    }

    _onScroll() {
        this.element.classList.toggle('navbar--scrolled', window.scrollY > 8);
    }

    _markActive() {
        const path = window.location.pathname;
        this.element.querySelectorAll('.nav-link[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href === '#') return;
            const prefixes = (link.dataset.navPrefix || href).split(' ');
            const active = prefixes.some(p =>
                p === '/' ? path === '/' : path.startsWith(p)
            );
            if (active) link.classList.add('active');
        });
    }
}
