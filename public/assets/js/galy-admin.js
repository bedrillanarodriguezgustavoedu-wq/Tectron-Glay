/* ==========================================================================
   GALY · Comportamientos del panel de administración
   Mejoras progresivas sobre Mazer. No requiere dependencias.
   ========================================================================== */

(function () {
    'use strict';

    var ready = function (fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    };

    /* ----------------------------------------------------------------------
       1. Contexto en la barra superior (título + breadcrumb)
       ---------------------------------------------------------------------- */
    function syncTopbar() {
        var titleEl = document.querySelector('.ga-topbar-title');
        var crumbEl = document.querySelector('.ga-crumb-current');
        if (!titleEl) {
            return;
        }

        var content = document.querySelector('.page-content');
        if (!content) {
            return;
        }

        var heading = content.querySelector('h1, .ga-page-title');
        var text = heading ? heading.textContent.trim() : '';
        if (!text) {
            text = document.title.split('·')[0].trim() || 'Panel de administración';
        }

        titleEl.textContent = text;
        if (crumbEl) {
            crumbEl.textContent = text;
        }

        if (document.title.indexOf('·') === -1) {
            document.title = text + ' · ' + document.title;
        }
    }

    /* ----------------------------------------------------------------------
       2. Buscador del menú lateral
       ---------------------------------------------------------------------- */
    function setupSidebarFilter() {
        var input = document.getElementById('ga-sidebar-filter');
        var menu = document.querySelector('.sidebar-wrapper .menu');
        if (!input || !menu) {
            return;
        }

        var items = Array.prototype.slice.call(menu.querySelectorAll('.sidebar-item'));
        var titles = Array.prototype.slice.call(menu.querySelectorAll('.sidebar-title'));

        var normalize = function (value) {
            return (value || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        };

        var apply = function () {
            var query = normalize(input.value).trim();
            menu.classList.toggle('ga-menu-filtering', query !== '');

            titles.forEach(function (title) {
                title.hidden = query !== '';
            });

            items.forEach(function (item) {
                var submenu = item.querySelector('.submenu');
                var text = normalize(item.textContent);

                if (!submenu) {
                    item.hidden = query !== '' && text.indexOf(query) === -1;
                    return;
                }

                var children = Array.prototype.slice.call(submenu.querySelectorAll('.submenu-item'));
                var childMatch = false;

                children.forEach(function (child) {
                    var match = query === '' || normalize(child.textContent).indexOf(query) !== -1;
                    child.hidden = query !== '' && !match;
                    childMatch = childMatch || match;
                });

                var selfMatch = query === '' || text.indexOf(query) !== -1;
                item.hidden = query !== '' && !selfMatch && !childMatch;

                if (query !== '' && childMatch && !item.hidden) {
                    submenu.classList.add('submenu-open');
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                }
            });

            if (query === '') {
                menu.querySelectorAll('.submenu').forEach(function (submenu) {
                    submenu.style.maxHeight = '';
                });
            }
        };

        input.addEventListener('input', apply);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                input.value = '';
                apply();
                input.blur();
            }
        });

        document.addEventListener('keydown', function (event) {
            var tag = (event.target && event.target.tagName) || '';
            if (event.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
                event.preventDefault();
                input.focus();
            }
        });
    }

    /* ----------------------------------------------------------------------
       3. Tablas: envolver en contenedor desplazable
       ---------------------------------------------------------------------- */
    function setupTables() {
        document.querySelectorAll('.page-content table.table').forEach(function (table) {
            if (table.closest('.table-responsive')) {
                table.classList.add('align-middle');
                return;
            }
            var wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    }

    /* ----------------------------------------------------------------------
       4. Alertas con autocierre suave
       ---------------------------------------------------------------------- */
    function setupAlerts() {
        document.querySelectorAll('.page-content .alert.alert-dismissible').forEach(function (alert) {
            if (alert.id === 'flash-errores') {
                return;
            }
            window.setTimeout(function () {
                if (!document.body.contains(alert)) {
                    return;
                }
                alert.classList.remove('show');
                alert.classList.add('fade');
                window.setTimeout(function () {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 220);
            }, 7000);
        });
    }

    /* ----------------------------------------------------------------------
       5. Cerrar el menú al navegar en móvil
       ---------------------------------------------------------------------- */
    function setupMobileSidebar() {
        var wrapper = document.querySelector('.sidebar-wrapper');
        if (!wrapper) {
            return;
        }

        wrapper.querySelectorAll('.sidebar-link, .submenu-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 1199.98px)').matches) {
                    var hide = document.querySelector('.sidebar-hide');
                    if (hide) {
                        hide.click();
                    } else {
                        wrapper.classList.remove('active');
                    }
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && window.matchMedia('(max-width: 1199.98px)').matches) {
                wrapper.classList.remove('active');
            }
        });
    }

    /* ----------------------------------------------------------------------
       6. Resaltar la sección activa del menú a partir de la URL
       ---------------------------------------------------------------------- */
    function syncActiveMenu() {
        var current = window.location.pathname.replace(/\/+$/, '') || '/';
        var wrapper = document.querySelector('.sidebar-wrapper');
        if (!wrapper) {
            return;
        }

        wrapper.querySelectorAll('.sidebar-item > .sidebar-link').forEach(function (link) {
            var url;
            try {
                url = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
            } catch (error) {
                return;
            }

            if (url === '/' || url === '/home' || url === '/admin') {
                return;
            }

            if (current === url || current.indexOf(url + '/') === 0) {
                link.parentElement.classList.add('active');
            }
        });
    }

    ready(function () {
        syncTopbar();
        setupSidebarFilter();
        setupTables();
        setupAlerts();
        setupMobileSidebar();
        syncActiveMenu();
    });
})();
