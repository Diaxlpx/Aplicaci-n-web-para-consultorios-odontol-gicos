/* Ficha del paciente: registro de consultas y procedimientos por AJAX. */
(function () {
    'use strict';

    const pacienteId = document.getElementById('pacienteId');
    const form       = document.getElementById('consultaForm');
    const modalEl    = document.getElementById('consultaModal');
    const procRows   = document.getElementById('procRows');
    const totalEl    = document.getElementById('totalConsulta');

    if (!pacienteId || !form || !modalEl || !procRows) {
        return;
    }

    const modal = new bootstrap.Modal(modalEl);

    let procedimientos = [];
    let cargados = false;

    async function cargarProcedimientos() {
        if (cargados) {
            return;
        }

        const res = await App.api('api/procedimientos.php?action=list&activos=1');
        procedimientos = res.data || [];
        cargados = true;
    }

    function opciones(seleccionado) {
        let html = '<option value="">Seleccione un procedimiento...</option>';

        procedimientos.forEach(function (p) {
            const selected = String(p.id) === String(seleccionado) ? ' selected' : '';
            html += '<option value="' + p.id + '"' + selected + '>' +
                App.escape(p.nombre) + ' — ' + App.money(p.precio) +
            '</option>';
        });

        return html;
    }

    function recalcular() {
        let total = 0;

        procRows.querySelectorAll('[data-row]').forEach(function (row) {
            const costo = parseFloat(row.querySelector('.proc-costo').value) || 0;
            total += costo;
        });

        totalEl.textContent = App.money(total);
    }

    function crearFila(procedimientoId) {
        const row = document.createElement('div');
        row.className = 'proc-row';
        row.setAttribute('data-row', '');

        const select = document.createElement('select');
        select.className = 'form-select form-select-sm proc-select';
        select.innerHTML = opciones(procedimientoId || '');
        select.value = procedimientoId || '';

        const costo = document.createElement('input');
        costo.type = 'number';
        costo.className = 'form-control form-control-sm proc-costo';
        costo.min = '0';
        costo.step = '0.01';
        costo.value = '0';

        const quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.className = 'btn btn-sm btn-outline-danger';
        quitar.innerHTML = '<i class="bi bi-x-lg"></i>';
        quitar.title = 'Quitar';

        select.addEventListener('change', function () {
            const encontrado = procedimientos.find(function (p) {
                return String(p.id) === String(select.value);
            });

            if (encontrado) {
                costo.value = Number(encontrado.precio).toFixed(2);
            }

            recalcular();
        });

        costo.addEventListener('input', recalcular);

        quitar.addEventListener('click', function () {
            row.remove();

            if (procRows.querySelectorAll('[data-row]').length === 0) {
                procRows.appendChild(crearFila(''));
            }

            recalcular();
        });

        row.appendChild(select);
        row.appendChild(costo);
        row.appendChild(quitar);

        return row;
    }

    async function abrirModal() {
        form.reset();
        procRows.innerHTML = '';

        try {
            await cargarProcedimientos();
        } catch (error) {
            App.toast(error.message, 'danger');
            return;
        }

        procRows.appendChild(crearFila(''));
        recalcular();
        modal.show();
    }

    document.getElementById('btnNuevaConsulta').addEventListener('click', abrirModal);

    document.getElementById('btnAddProc').addEventListener('click', function () {
        procRows.appendChild(crearFila(''));
        recalcular();
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const filas = [];

        procRows.querySelectorAll('[data-row]').forEach(function (row) {
            const select = row.querySelector('.proc-select');
            const costo = row.querySelector('.proc-costo');

            if (select.value !== '') {
                filas.push({
                    procedimiento_id: select.value,
                    costo: costo.value
                });
            }
        });

        if (filas.length === 0) {
            App.toast('Agregue al menos un procedimiento realizado.', 'warning');
            return;
        }

        const payload = {
            paciente_id: pacienteId.value,
            fecha: form.elements['fecha'].value,
            motivo: form.elements['motivo'].value,
            diagnostico: form.elements['diagnostico'].value,
            tratamiento: form.elements['tratamiento'].value,
            observaciones: form.elements['observaciones'].value,
            procedimientos: filas
        };

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res = await App.api('api/consultas.php?action=save', {
                method: 'POST',
                body: payload
            });

            modal.hide();
            App.toast(res.message || 'Consulta registrada.');

            setTimeout(function () {
                window.location.reload();
            }, 700);
        } catch (error) {
            App.toast(error.message, 'danger');
        } finally {
            submitBtn.disabled = false;
        }
    });

    document.addEventListener('click', async function (event) {
        const boton = event.target.closest('[data-del-consulta]');

        if (!boton) {
            return;
        }

        if (!confirm('¿Eliminar esta consulta de la historia clínica?')) {
            return;
        }

        try {
            const res = await App.api('api/consultas.php?action=delete', {
                method: 'POST',
                body: { id: boton.getAttribute('data-del-consulta') }
            });

            App.toast(res.message || 'Consulta eliminada.');

            setTimeout(function () {
                window.location.reload();
            }, 600);
        } catch (error) {
            App.toast(error.message, 'danger');
        }
    });
})();
