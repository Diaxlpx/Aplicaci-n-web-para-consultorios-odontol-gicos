/* Catálogo de procedimientos: listado, búsqueda y CRUD vía AJAX. */
(function () {
    'use strict';

    const tbody    = document.getElementById('procedimientosBody');
    const buscar   = document.getElementById('buscar');
    const contador = document.getElementById('contador');
    const form     = document.getElementById('procedimientoForm');
    const modalEl  = document.getElementById('procedimientoModal');
    const modal    = new bootstrap.Modal(modalEl);
    const title    = document.getElementById('procedimientoModalTitle');

    let cache = {};

    function row(p) {
        const estado = Number(p.activo) === 1
            ? '<span class="badge badge-soft">Activo</span>'
            : '<span class="badge badge-soft-muted">Inactivo</span>';

        return '' +
            '<tr>' +
                '<td class="fw-semibold">' + App.escape(p.nombre) + '</td>' +
                '<td class="text-muted-sm">' + (p.descripcion ? App.escape(p.descripcion) : '—') + '</td>' +
                '<td class="text-end text-nowrap">' + App.money(p.precio) + '</td>' +
                '<td>' + estado + '</td>' +
                '<td class="text-end">' +
                    '<div class="table-actions">' +
                        '<button class="btn btn-sm btn-outline-secondary" data-edit="' + p.id + '" ' +
                                'title="Editar"><i class="bi bi-pencil"></i></button>' +
                        '<button class="btn btn-sm btn-outline-danger" data-delete="' + p.id + '" ' +
                                'title="Eliminar"><i class="bi bi-trash"></i></button>' +
                    '</div>' +
                '</td>' +
            '</tr>';
    }

    async function cargar(q) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>';

        try {
            const res  = await App.api('api/procedimientos.php?action=list&q=' + encodeURIComponent(q || ''));
            const lista = res.data || [];

            cache = {};

            if (lista.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="5">' +
                        '<div class="empty-state">' +
                            '<i class="bi bi-clipboard2-x"></i>' +
                            '<p class="mb-0">No hay procedimientos registrados.</p>' +
                        '</div>' +
                    '</td></tr>';
                contador.textContent = '';
                return;
            }

            tbody.innerHTML = lista.map(function (p) {
                cache[p.id] = p;
                return row(p);
            }).join('');

            contador.textContent = lista.length + (lista.length === 1 ? ' procedimiento' : ' procedimientos');
        } catch (error) {
            tbody.innerHTML =
                '<tr><td colspan="5" class="text-center py-4 text-danger">' +
                    App.escape(error.message) +
                '</td></tr>';
            App.toast(error.message, 'danger');
        }
    }

    function abrirNuevo() {
        form.reset();
        form.elements['id'].value = '';
        form.elements['precio'].value = '0';
        form.elements['activo'].value = '1';
        title.textContent = 'Nuevo procedimiento';
        modal.show();
    }

    function abrirEditar(id) {
        const p = cache[id];

        if (!p) {
            return;
        }

        form.reset();
        form.elements['id'].value = p.id;
        form.elements['nombre'].value = p.nombre || '';
        form.elements['descripcion'].value = p.descripcion || '';
        form.elements['precio'].value = p.precio || '0';
        form.elements['activo'].value = Number(p.activo) === 1 ? '1' : '0';

        title.textContent = 'Editar procedimiento';
        modal.show();
    }

    document.getElementById('btnNuevo').addEventListener('click', abrirNuevo);

    tbody.addEventListener('click', async function (event) {
        const editBtn = event.target.closest('[data-edit]');

        if (editBtn) {
            abrirEditar(editBtn.getAttribute('data-edit'));
            return;
        }

        const deleteBtn = event.target.closest('[data-delete]');

        if (!deleteBtn) {
            return;
        }

        const id = deleteBtn.getAttribute('data-delete');
        const procedimiento = cache[id];
        const nombre = procedimiento ? procedimiento.nombre : 'este procedimiento';

        if (!confirm('¿Eliminar "' + nombre + '"?')) {
            return;
        }

        try {
            const res = await App.api('api/procedimientos.php?action=delete', {
                method: 'POST',
                body: { id: id }
            });

            App.toast(res.message || 'Procedimiento eliminado.');
            cargar(buscar.value);
        } catch (error) {
            App.toast(error.message, 'danger');
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const payload = {
            id: form.elements['id'].value,
            nombre: form.elements['nombre'].value,
            descripcion: form.elements['descripcion'].value,
            precio: form.elements['precio'].value,
            activo: form.elements['activo'].value
        };

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res = await App.api('api/procedimientos.php?action=save', {
                method: 'POST',
                body: payload
            });

            modal.hide();
            App.toast(res.message || 'Procedimiento guardado.');
            cargar(buscar.value);
        } catch (error) {
            App.toast(error.message, 'danger');
        } finally {
            submitBtn.disabled = false;
        }
    });

    let timer = null;

    buscar.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            cargar(buscar.value);
        }, 300);
    });

    cargar('');
})();
