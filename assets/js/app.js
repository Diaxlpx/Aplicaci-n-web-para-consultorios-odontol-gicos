/* Utilidades compartidas por toda la aplicación. */
(function () {
    'use strict';

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const currencyMeta = document.querySelector('meta[name="currency"]');

    const CSRF = csrfMeta ? csrfMeta.content : '';
    const CURRENCY = currencyMeta ? currencyMeta.content : '$';

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[char];
            });
    }

    async function api(url, options) {
        const opts = options || {};
        const method = opts.method || 'GET';

        const config = {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': CSRF
            }
        };

        if (opts.body !== undefined && opts.body !== null) {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(opts.body);
        }

        let response;

        try {
            response = await fetch(url, config);
        } catch (error) {
            throw new Error('No se pudo conectar con el servidor.');
        }

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('El servidor devolvió una respuesta no válida.');
        }

        if (!response.ok || !payload || payload.ok !== true) {
            throw new Error((payload && payload.error) || 'Ocurrió un error inesperado.');
        }

        return payload;
    }

    function money(value) {
        const number = Number(value || 0);
        const parts = number.toFixed(2).split('.');
        const integer = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return CURRENCY + ' ' + integer + ',' + parts[1];
    }

    function toast(message, type) {
        const variant = type || 'success';

        let container = document.getElementById('toastContainer');

        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1090';
            document.body.appendChild(container);
        }

        const el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + variant + ' border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML =
            '<div class="d-flex">' +
                '<div class="toast-body">' + escapeHtml(message) + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>' +
            '</div>';

        container.appendChild(el);

        const toastInstance = new bootstrap.Toast(el, { delay: 4000 });
        toastInstance.show();

        el.addEventListener('hidden.bs.toast', function () {
            el.remove();
        });
    }

    window.App = {
        csrf: CSRF,
        currency: CURRENCY,
        api: api,
        money: money,
        escape: escapeHtml,
        toast: toast
    };
})();
