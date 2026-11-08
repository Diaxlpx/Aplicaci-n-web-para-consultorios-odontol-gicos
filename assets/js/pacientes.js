/* Gestión de pacientes: carga, búsqueda, alta, edición y baja vía AJAX. */
(function () {
    'use strict';

    const tbody    = document.getElementById('pacientesBody');
    const buscar   = document.getElementById('buscar');
    const contador = document.getElementById('contador');
    const form     = document.getElementById('pacienteForm');
    const modalEl  = document.getElementById('pacienteModal');
    const modal    = new bootstrap.Modal(modalEl);
    const title    = document.getElementById('pacienteModalTitle');

    let cache = {};

    function fmtDate(value) {
        if (!value) {
            return '—';
        }

        const parts = value.split('-');

        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
    }

    function row(p) {
        return '' +
            '<tr>' +
                '<td>' +
                    '<div class="fw-semibold">' + App.escape(p.nombres) + ' ' + App.escape(p.apellidos) + '</div>' +
                    '<div class="text-muted-sm">C.I. ' + App.escape(p.cedula) + '</div>' +
                '</td>' +
                '<td>' + (p.telefono ? App.escape(p.telefono) : '—') + '</td>' +
                '<td>' + (p.email ? App.escape(p.email) : '—') + '</td>' +
                '<td class="text-nowrap">' + fmtDate(p.fecha_nacimiento) + '</td>' +
                '<td class="text-end">' +
                    '<div class="table-actions">' +
                        '<a class="btn btn-sm btn-outline-success" href="paciente.php?id=' + p.id + '" ' +
                           'title="Ver historia clínica"><i class="bi bi-file-earmark-medical"></i></a>' +
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
            const res  = await App.api('api/pacientes.php?action=list&q=' + encodeURIComponent(q || ''));
            const lista = res.data || [];

            cache = {};

            if (lista.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="5">' +
                        '<div class="empty-state">' +
                            '<i class="bi bi-people"></i>' +
                            '<p class="mb-0">No se encontraron pacientes.</p>' +
                        '</div>' +
                    '</td></tr>';
                contador.textContent = '';
                return;
            }

            tbody.innerHTML = lista.map(function (p) {
                cache[p.id] = p;
                return row(p);
            }).join('');

            contador.textContent = lista.length + (lista.length === 1 ? ' paciente' : ' pacientes');
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
        title.textContent = 'Nuevo paciente';
        modal.show();
    }

    function abrirEditar(id) {
        const p = cache[id];

        if (!p) {
            return;
        }

        form.reset();

        [
            'id', 'nombres', 'apellidos', 'cedula', 'fecha_nacimiento',
            'sexo', 'telefono', 'email', 'direccion', 'alergias', 'antecedentes'
        ].forEach(function (key) {
            if (form.elements[key]) {
                form.elements[key].value = p[key] || '';
            }
        });

        title.textContent = 'Editar paciente';
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
        const paciente = cache[id];
        const nombre = paciente ? paciente.nombres + ' ' + paciente.apellidos : 'este paciente';

        if (!confirm('¿Eliminar a ' + nombre + '? También se eliminará toda su historia clínica.')) {
            return;
        }

        try {
            const res = await App.api('api/pacientes.php?action=delete', {
                method: 'POST',
                body: { id: id }
            });

            App.toast(res.message || 'Paciente eliminado.');
            cargar(buscar.value);
        } catch (error) {
            App.toast(error.message, 'danger');
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const payload = {};

        [
            'id', 'nombres', 'apellidos', 'cedula', 'fecha_nacimiento',
            'sexo', 'telefono', 'email', 'direccion', 'alergias', 'antecedentes'
        ].forEach(function (key) {
            payload[key] = form.elements[key] ? form.elements[key].value : '';
        });

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res = await App.api('api/pacientes.php?action=save', {
                method: 'POST',
                body: payload
            });

            modal.hide();
            App.toast(res.message || 'Paciente guardado.');
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
