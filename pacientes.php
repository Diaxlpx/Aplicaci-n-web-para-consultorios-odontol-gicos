<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle  = 'Pacientes';
$activePage = 'pacientes';

require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2>Pacientes</h2>
        <p>Registre, actualice y consulte los pacientes del consultorio.</p>
    </div>
    <button class="btn btn-success" id="btnNuevo">
        <i class="bi bi-person-plus me-1"></i> Nuevo paciente
    </button>
</div>

<div class="card">
    <div class="card-header">
        <div class="row g-2 align-items-center">
            <div class="col-sm-7 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="buscar"
                           placeholder="Buscar por nombre o cédula..." autocomplete="off">
                </div>
            </div>
            <div class="col-sm-5 col-md-7 text-sm-end">
                <span class="text-muted-sm" id="contador"></span>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Nacimiento</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="pacientesBody">
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de registro / edición -->
<div class="modal fade" id="pacienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="pacienteForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="pacienteModalTitle">Nuevo paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" value="">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nombres">Nombres *</label>
                        <input type="text" class="form-control" id="nombres" name="nombres" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="apellidos">Apellidos *</label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="cedula">Cédula *</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="sexo">Sexo</label>
                        <select class="form-select" id="sexo" name="sexo">
                            <option value="">Seleccione</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="O">Otro</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="direccion">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="alergias">Alergias</label>
                        <textarea class="form-control" id="alergias" name="alergias" rows="3"
                                  placeholder="Ej: penicilina, anestesia..."></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="antecedentes">Antecedentes médicos</label>
                        <textarea class="form-control" id="antecedentes" name="antecedentes" rows="3"
                                  placeholder="Ej: diabetes, hipertensión..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Guardar paciente
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = '<script src="assets/js/pacientes.js"></script>';
require __DIR__ . '/includes/footer.php';
?>
