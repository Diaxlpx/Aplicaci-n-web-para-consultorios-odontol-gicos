<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$id  = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    http_response_code(404);
    $pageTitle  = 'Paciente no encontrado';
    $activePage = 'pacientes';

    require __DIR__ . '/includes/header.php';
    ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        El paciente solicitado no existe.
        <a href="pacientes.php" class="alert-link">Volver a la lista</a>.
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Historia clínica: consultas del paciente con el odontólogo que atendió.
$stmt = $pdo->prepare(
    'SELECT c.*, u.nombre AS odontologo
     FROM consultas c
     INNER JOIN usuarios u ON u.id = c.usuario_id
     WHERE c.paciente_id = ?
     ORDER BY c.fecha DESC, c.id DESC'
);
$stmt->execute([$id]);
$consultas = $stmt->fetchAll();

$procStmt = $pdo->prepare(
    'SELECT cp.id, cp.costo, cp.observaciones, pr.nombre
     FROM consulta_procedimientos cp
     INNER JOIN procedimientos pr ON pr.id = cp.procedimiento_id
     WHERE cp.consulta_id = ?
     ORDER BY cp.id ASC'
);

$totalHistoria = 0.0;

foreach ($consultas as &$consulta) {
    $procStmt->execute([$consulta['id']]);
    $consulta['procedimientos'] = $procStmt->fetchAll();
    $consulta['total'] = array_sum(array_map(
        static fn (array $p): float => (float) $p['costo'],
        $consulta['procedimientos']
    ));

    $totalHistoria += $consulta['total'];
}
unset($consulta);

$edad = null;

if (!empty($paciente['fecha_nacimiento'])) {
    try {
        $nacimiento = new DateTime($paciente['fecha_nacimiento']);
        $edad = $nacimiento->diff(new DateTime('today'))->y;
    } catch (Exception $e) {
        $edad = null;
    }
}

$sexoLabel = [
    'M' => 'Masculino',
    'F' => 'Femenino',
    'O' => 'Otro',
][$paciente['sexo'] ?? ''] ?? 'No indicado';

$pageTitle  = 'Historia clínica';
$activePage = 'pacientes';

require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <a href="pacientes.php" class="text-decoration-none text-muted-sm">
            <i class="bi bi-arrow-left"></i> Volver a pacientes
        </a>
        <h2 class="mt-1"><?= e($paciente['nombres'] . ' ' . $paciente['apellidos']) ?></h2>
        <p>Cédula: <?= e($paciente['cedula']) ?></p>
    </div>

    <div class="d-flex gap-2">
        <a href="historia_pdf.php?id=<?= (int) $paciente['id'] ?>" class="btn btn-outline-danger" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i> Descargar PDF
        </a>
        <button type="button" class="btn btn-success" id="btnNuevaConsulta">
            <i class="bi bi-plus-lg me-1"></i> Nueva consulta
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="avatar" style="width:56px;height:56px;font-size:1.35rem;flex:0 0 56px;">
                <?= e(strtoupper(substr($paciente['nombres'], 0, 1))) ?>
            </div>
            <div class="flex-grow-1">
                <h3 class="h5 mb-1"><?= e($paciente['nombres'] . ' ' . $paciente['apellidos']) ?></h3>
                <div class="text-muted-sm">
                    C.I. <?= e($paciente['cedula']) ?> ·
                    <?= e($sexoLabel) ?><?= $edad !== null ? ' · ' . (int) $edad . ' años' : '' ?>
                </div>
            </div>
        </div>

        <hr>

        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="text-muted-sm">Teléfono</div>
                <div class="fw-semibold"><?= $paciente['telefono'] ? e($paciente['telefono']) : '—' ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="text-muted-sm">Correo</div>
                <div class="fw-semibold"><?= $paciente['email'] ? e($paciente['email']) : '—' ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="text-muted-sm">Fecha de nacimiento</div>
                <div class="fw-semibold">
                    <?= $paciente['fecha_nacimiento'] ? e(date('d/m/Y', strtotime($paciente['fecha_nacimiento']))) : '—' ?>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="text-muted-sm">Dirección</div>
                <div class="fw-semibold"><?= $paciente['direccion'] ? e($paciente['direccion']) : '—' ?></div>
            </div>
        </div>

        <?php if (!empty($paciente['alergias'])): ?>
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>Alergias:</strong> <?= e($paciente['alergias']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($paciente['antecedentes'])): ?>
            <div class="alert alert-light border mt-2 mb-0 py-2">
                <strong>Antecedentes médicos:</strong> <?= e($paciente['antecedentes']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h2 class="h5 mb-0"><i class="bi bi-journal-medical me-1"></i> Historia clínica</h2>
    <span class="text-muted-sm">
        <?= count($consultas) ?> consulta<?= count($consultas) === 1 ? '' : 's' ?>
        · Total facturado: <strong><?= money($totalHistoria) ?></strong>
    </span>
</div>

<?php if (empty($consultas)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <p class="mb-1">Este paciente todavía no tiene consultas registradas.</p>
                <p class="text-muted-sm">Use el botón <strong>Nueva consulta</strong> para comenzar su historia clínica.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($consultas as $consulta): ?>
        <div class="card history-card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <div class="history-date">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= e(date('d/m/Y', strtotime($consulta['fecha']))) ?>
                        </div>
                        <div class="history-meta">Atendido por <?= e($consulta['odontologo']) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold"><?= money($consulta['total']) ?></div>
                        <div class="history-meta">
                            <?= count($consulta['procedimientos']) ?>
                            procedimiento<?= count($consulta['procedimientos']) === 1 ? '' : 's' ?>
                        </div>
                    </div>
                </div>

                <dl class="row history-block mb-0">
                    <?php if (!empty($consulta['motivo'])): ?>
                        <dt class="col-sm-3">Motivo</dt>
                        <dd class="col-sm-9"><?= e($consulta['motivo']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($consulta['diagnostico'])): ?>
                        <dt class="col-sm-3">Diagnóstico</dt>
                        <dd class="col-sm-9"><?= e($consulta['diagnostico']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($consulta['tratamiento'])): ?>
                        <dt class="col-sm-3">Tratamiento</dt>
                        <dd class="col-sm-9"><?= e($consulta['tratamiento']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($consulta['observaciones'])): ?>
                        <dt class="col-sm-3">Observaciones</dt>
                        <dd class="col-sm-9"><?= e($consulta['observaciones']) ?></dd>
                    <?php endif; ?>
                </dl>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Procedimiento realizado</th>
                                <th class="text-end">Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consulta['procedimientos'] as $procedimiento): ?>
                                <tr>
                                    <td><?= e($procedimiento['nombre']) ?></td>
                                    <td class="text-end text-nowrap"><?= money($procedimiento['costo']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            data-del-consulta="<?= (int) $consulta['id'] ?>">
                        <i class="bi bi-trash me-1"></i> Eliminar consulta
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal: nueva consulta -->
<div class="modal fade" id="consultaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="consultaForm" novalidate>
            <input type="hidden" id="pacienteId" value="<?= (int) $paciente['id'] ?>">

            <div class="modal-header">
                <h5 class="modal-title">Nueva consulta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="fecha">Fecha *</label>
                        <input type="date" class="form-control" id="fecha" name="fecha"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label" for="motivo">Motivo de la consulta</label>
                        <input type="text" class="form-control" id="motivo" name="motivo"
                               placeholder="Ej: dolor en muela del juicio">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="diagnostico">Diagnóstico</label>
                        <textarea class="form-control" id="diagnostico" name="diagnostico" rows="3"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="tratamiento">Tratamiento</label>
                        <textarea class="form-control" id="tratamiento" name="tratamiento" rows="3"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="observaciones">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Procedimientos realizados *</label>
                    <button type="button" class="btn btn-sm btn-outline-success" id="btnAddProc">
                        <i class="bi bi-plus-lg me-1"></i> Agregar
                    </button>
                </div>

                <div id="procRows"></div>

                <div class="text-end fw-semibold mt-2">
                    Total: <span id="totalConsulta"><?= money(0) ?></span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Guardar consulta
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = '<script src="assets/js/paciente.js"></script>';
require __DIR__ . '/includes/footer.php';
?>
