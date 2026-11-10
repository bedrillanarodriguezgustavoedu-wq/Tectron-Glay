/* GALY · mejoras de interfaz de la tienda */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    function toggleZeroClass(el) {
        if (!el) return;
        var value = parseInt(String(el.textContent).replace(/[^\d]/g, ''), 10) || 0;
        el.classList.toggle('is-zero', value <= 0);
    }

    function syncCounters() {
        document.querySelectorAll('.g-count').forEach(toggleZeroClass);
        document.querySelectorAll('.g-dock-badge').forEach(toggleZeroClass);
    }

    ready(function () {
        syncCounters();

        /* Header compacto al hacer scroll */
        var header = document.getElementById('header');
        var last = 0;
        if (header) {
            window.addEventListener('scroll', function () {
                var y = window.scrollY || 0;
                if (Math.abs(y - last) < 8) return;
                header.classList.toggle('is-scrolled', y > 24);
                last = y;
            }, { passive: true });
        }

        /* Marcar el enlace activo de la barra inferior */
        var path = window.location.pathname.replace(/\/+$/, '') || '/';
        document.querySelectorAll('.g-dock-link').forEach(function (link) {
            var href = link.getAttribute('href') || '';
            try {
                var url = new URL(href, window.location.origin);
                var target = url.pathname.replace(/\/+$/, '') || '/';
                if (target === path || (target !== '/' && path.indexOf(target) === 0)) {
                    link.classList.add('is-active');
                }
            } catch (e) { /* ignore */ }
        });

        /* El contador del carrito del header se reescribe en el layout:
           mantenemos sincronizada la barra inferior. */
        var observer = new MutationObserver(syncCounters);
        document.querySelectorAll('.cart-counter, .fav-counter').forEach(function (el) {
            observer.observe(el, { childList: true, characterData: true, subtree: true });
        });

        /* Cerrar el buscador de sugerencias con Escape */
        var search = document.getElementById('search-input');
        var suggestions = document.getElementById('search-suggestions');
        if (search && suggestions) {
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    suggestions.classList.add('d-none');
                    search.blur();
                }
            });
        }

        /* Botón "Buscar" de la barra inferior */
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-g-search]');
            if (!trigger) return;
            e.preventDefault();
            var input = document.getElementById('search-input');
            if (!input) return;
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(function () { input.focus({ preventScroll: true }); }, 320);
        });

        /* Animación de entrada para tarjetas de producto visibles */
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('g-in');
                        io.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -40px 0px' });

            document.querySelectorAll('.product-card, .g-cat-card, .g-trust-item').forEach(function (el) {
                io.observe(el);
            });
        }
    });

    /* Refrescar contadores después de operaciones AJAX del layout */
    document.addEventListener('galy:counters', syncCounters);
})();
