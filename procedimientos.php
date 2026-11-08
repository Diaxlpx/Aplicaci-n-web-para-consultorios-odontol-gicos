<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle  = 'Procedimientos';
$activePage = 'procedimientos';

require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2>Procedimientos odontológicos</h2>
        <p>Catálogo de tratamientos ofrecidos por el consultorio.</p>
    </div>
    <button class="btn btn-success" id="btnNuevo">
        <i class="bi bi-plus-lg me-1"></i> Nuevo procedimiento
    </button>
</div>

<div class="card">
    <div class="card-header">
        <div class="row g-2 align-items-center">
            <div class="col-sm-7 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="buscar"
                           placeholder="Buscar procedimiento..." autocomplete="off">
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
                        <th>Procedimiento</th>
                        <th>Descripción</th>
                        <th class="text-end">Precio</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="procedimientosBody">
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="procedimientoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="procedimientoForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="procedimientoModalTitle">Nuevo procedimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" value="">

                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                           placeholder="Ej: Limpieza dental" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                              placeholder="Detalle del procedimiento"></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="precio">Precio *</label>
                        <input type="number" class="form-control" id="precio" name="precio"
                               min="0" step="0.01" value="0" required>
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label" for="activo">Estado</label>
                        <select class="form-select" id="activo" name="activo">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = '<script src="assets/js/procedimientos.js"></script>';
require __DIR__ . '/includes/footer.php';
?>
