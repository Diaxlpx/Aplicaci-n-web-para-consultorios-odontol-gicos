<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();

$totalPacientes      = (int) $pdo->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
$totalProcedimientos = (int) $pdo->query('SELECT COUNT(*) FROM procedimientos WHERE activo = 1')->fetchColumn();
$totalConsultas      = (int) $pdo->query('SELECT COUNT(*) FROM consultas')->fetchColumn();
$consultasMes        = (int) $pdo->query(
    'SELECT COUNT(*) FROM consultas
     WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())'
)->fetchColumn();

$ultimasConsultas = $pdo->query(
    'SELECT c.id, c.fecha, c.motivo, p.id AS paciente_id,
            p.nombres, p.apellidos, p.cedula
     FROM consultas c
     INNER JOIN pacientes p ON p.id = c.paciente_id
     ORDER BY c.fecha DESC, c.id DESC
     LIMIT 8'
)->fetchAll();

$pageTitle  = 'Panel principal';
$activePage = 'panel';

require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2>Bienvenido, <?= e(explode(' ', $user['nombre'] ?? 'Doctor')[0]) ?></h2>
        <p>Resumen general del consultorio odontológico.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="pacientes.php" class="btn btn-success">
            <i class="bi bi-person-plus me-1"></i> Nuevo paciente
        </a>
        <a href="procedimientos.php" class="btn btn-outline-success">
            <i class="bi bi-clipboard2-plus me-1"></i> Procedimientos
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e3f3ea;color:#1c7c51;">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-value"><?= number_format($totalPacientes, 0, ',', '.') ?></div>
            <div class="stat-label">Pacientes registrados</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e8eefc;color:#2f5fbf;">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>
            <div class="stat-value"><?= number_format($totalProcedimientos, 0, ',', '.') ?></div>
            <div class="stat-label">Procedimientos activos</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fdf0e3;color:#b26a12;">
                <i class="bi bi-journal-medical"></i>
            </div>
            <div class="stat-value"><?= number_format($totalConsultas, 0, ',', '.') ?></div>
            <div class="stat-label">Consultas registradas</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fde8ea;color:#b02a37;">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>
            <div class="stat-value"><?= number_format($consultasMes, 0, ',', '.') ?></div>
            <div class="stat-label">Consultas este mes</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-1"></i> Últimas consultas</span>
                <a href="pacientes.php" class="btn btn-sm btn-outline-success">Ver pacientes</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimasConsultas)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p class="mb-0">Todavía no hay consultas registradas.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Paciente</th>
                                    <th>Motivo</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasConsultas as $consulta): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e(date('d/m/Y', strtotime($consulta['fecha']))) ?></td>
                                        <td>
                                            <div class="fw-semibold">
                                                <?= e($consulta['nombres'] . ' ' . $consulta['apellidos']) ?>
                                            </div>
                                            <div class="text-muted-sm">C.I. <?= e($consulta['cedula']) ?></div>
                                        </td>
                                        <td><?= e($consulta['motivo'] ?: '—') ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-success"
                                               href="paciente.php?id=<?= (int) $consulta['paciente_id'] ?>">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-lightning-charge me-1"></i> Accesos rápidos</div>
            <div class="card-body d-grid gap-2">
                <a href="pacientes.php" class="btn btn-outline-success text-start">
                    <i class="bi bi-people me-2"></i> Gestionar pacientes
                </a>
                <a href="procedimientos.php" class="btn btn-outline-success text-start">
                    <i class="bi bi-clipboard2-pulse me-2"></i> Catálogo de procedimientos
                </a>
                <a href="pacientes.php" class="btn btn-outline-success text-start">
                    <i class="bi bi-file-earmark-medical me-2"></i> Historias clínicas
                </a>
            </div>
            <div class="card-body border-top">
                <p class="text-muted-sm mb-1"><strong>Consultorio:</strong> <?= APP_NAME ?></p>
                <p class="text-muted-sm mb-0"><strong>Odontólogo:</strong> <?= APP_DOCTOR ?></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
