<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/SimplePDF.php';

$id  = (int) ($_GET['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    http_response_code(404);
    exit('Paciente no encontrado.');
}

// Historia clínica completa.
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
    'SELECT cp.costo, pr.nombre
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

// ---------------------------------------------------------------------------
// Construcción del PDF
// ---------------------------------------------------------------------------
$pdf = new SimplePDF();
$pdf->setFooterLeft(APP_NAME . ' · Generado el ' . date('d/m/Y H:i'));

$left  = 40.0;
$right = $pdf->pageWidth() - 40.0;
$ancho = $right - $left;

$pdf->addPage();

// Cabecera.
$pdf->rect(0, 0, $pdf->pageWidth(), 92, 'F', [0.055, 0.231, 0.173]);
$pdf->setTextColor(1.0, 1.0, 1.0);
$pdf->setFont('bold', 20);
$pdf->text($left, 42, APP_NAME);
$pdf->setFont('regular', 10.5);
$pdf->setTextColor(0.78, 0.90, 0.84);
$pdf->text($left, 62, APP_SUBTITLE);
$pdf->text($left, 78, APP_DOCTOR);
$pdf->setTextColor(0.0, 0.0, 0.0);

/**
 * Nueva página con un pequeño encabezado y devuelve la coordenada Y inicial.
 */
$nuevaPagina = function () use ($pdf, $paciente, $left, $right): float {
    $pdf->addPage();
    $pdf->setFont('regular', 8);
    $pdf->setTextColor(0.42, 0.45, 0.47);
    $pdf->text(
        $left,
        30,
        'Historia clínica · ' . $paciente['nombres'] . ' ' . $paciente['apellidos'] .
        ' · C.I. ' . $paciente['cedula']
    );
    $pdf->setDrawColor(0.80, 0.84, 0.86);
    $pdf->line($left, 38, $right, 38, 0.4);
    $pdf->setTextColor(0.0, 0.0, 0.0);
    $pdf->setDrawColor(0.0, 0.0, 0.0);

    return 58.0;
};

/**
 * Dibuja una etiqueta y su valor con salto de línea automático.
 */
$campo = function (string $label, string $value, float &$y) use ($pdf, $left, $right, $nuevaPagina): void {
    $texto  = $value !== '' ? $value : '—';
    $lines  = $pdf->wrap($texto, $right - ($left + 120), 10.5, false);
    $height = max(16.0, count($lines) * 14.0 + 4.0);

    if ($y + $height > 755) {
        $y = $nuevaPagina();
    }

    $pdf->setFont('bold', 9);
    $pdf->setTextColor(0.42, 0.45, 0.47);
    $pdf->text($left, $y, strtoupper($label));

    $pdf->setFont('regular', 10.5);
    $pdf->setTextColor(0.10, 0.16, 0.12);

    foreach ($lines as $i => $line) {
        $pdf->text($left + 120, $y + $i * 14, $line);
    }

    $y += $height;
    $pdf->setTextColor(0.0, 0.0, 0.0);
};

$y = 122.0;

// ---------------------------------------------------------------------------
// Datos del paciente
// ---------------------------------------------------------------------------
$pdf->setFont('bold', 14);
$pdf->setTextColor(0.07, 0.30, 0.21);
$pdf->text($left, $y, 'Datos del paciente');
$pdf->setDrawColor(0.85, 0.90, 0.87);
$pdf->line($left, $y + 8, $right, $y + 8, 0.6);
$pdf->setTextColor(0.0, 0.0, 0.0);
$pdf->setDrawColor(0.0, 0.0, 0.0);

$y += 26;

$campo('Nombre completo', $paciente['nombres'] . ' ' . $paciente['apellidos'], $y);
$campo('Cédula', $paciente['cedula'], $y);
$campo(
    'Fecha de nacimiento',
    $paciente['fecha_nacimiento']
        ? date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) .
          ($edad !== null ? ' (' . $edad . ' años)' : '')
        : '',
    $y
);
$campo('Sexo', $sexoLabel, $y);
$campo('Teléfono', $paciente['telefono'] ?? '', $y);
$campo('Correo', $paciente['email'] ?? '', $y);
$campo('Dirección', $paciente['direccion'] ?? '', $y);
$campo('Alergias', $paciente['alergias'] ?? 'Ninguna registrada', $y);
$campo('Antecedentes', $paciente['antecedentes'] ?? 'Ninguno registrado', $y);

// ---------------------------------------------------------------------------
// Historia clínica
// ---------------------------------------------------------------------------
$y += 14;

if ($y + 60 > 755) {
    $y = $nuevaPagina();
}

$pdf->setFont('bold', 14);
$pdf->setTextColor(0.07, 0.30, 0.21);
$pdf->text($left, $y, 'Historia clínica');
$pdf->setDrawColor(0.85, 0.90, 0.87);
$pdf->line($left, $y + 8, $right, $y + 8, 0.6);
$pdf->setTextColor(0.0, 0.0, 0.0);
$pdf->setDrawColor(0.0, 0.0, 0.0);

$y += 26;

if (empty($consultas)) {
    $pdf->setFont('regular', 11);
    $pdf->text($left, $y, 'El paciente no tiene consultas registradas.');
    $y += 20;
} else {
    foreach ($consultas as $consulta) {
        if ($y + 120 > 755) {
            $y = $nuevaPagina();
        }

        // Encabezado de la consulta.
        $pdf->setFont('bold', 12);
        $pdf->setTextColor(0.07, 0.30, 0.21);
        $pdf->text($left, $y, 'Consulta del ' . date('d/m/Y', strtotime($consulta['fecha'])));

        $atendido = 'Atendido por: ' . $consulta['odontologo'];
        $pdf->setFont('regular', 9);
        $pdf->setTextColor(0.42, 0.45, 0.47);
        $pdf->text($right - $pdf->textWidth($atendido, 9), $y, $atendido);

        $pdf->setTextColor(0.0, 0.0, 0.0);
        $y += 18;

        $campo('Motivo', $consulta['motivo'] ?? '', $y);
        $campo('Diagnóstico', $consulta['diagnostico'] ?? '', $y);
        $campo('Tratamiento', $consulta['tratamiento'] ?? '', $y);
        $campo('Observaciones', $consulta['observaciones'] ?? '', $y);

        // Tabla de procedimientos.
        if ($y + 40 > 755) {
            $y = $nuevaPagina();
        }

        $pdf->setFont('bold', 9.5);
        $pdf->setTextColor(0.42, 0.45, 0.47);
        $pdf->text($left, $y, 'PROCEDIMIENTOS REALIZADOS');
        $y += 8;

        $pdf->setDrawColor(0.80, 0.84, 0.86);
        $pdf->line($left, $y, $right, $y, 0.4);
        $y += 14;

        foreach ($consulta['procedimientos'] as $procedimiento) {
            $nombre = $procedimiento['nombre'];
            $lineas = $pdf->wrap($nombre, $ancho - 100, 10, false);
            $alto   = max(14.0, count($lineas) * 13.0);

            if ($y + $alto > 750) {
                $y = $nuevaPagina();
            }

            $pdf->setFont('regular', 10);
            $pdf->setTextColor(0.10, 0.16, 0.12);

            foreach ($lineas as $i => $linea) {
                $pdf->text($left + 4, $y + $i * 13, $linea);
            }

            $costo = money($procedimiento['costo']);
            $pdf->text($right - 90, $y, $costo);

            $y += $alto;
            $pdf->setDrawColor(0.92, 0.94, 0.93);
            $pdf->line($left, $y - 4, $right, $y - 4, 0.3);
        }

        $pdf->setFont('bold', 10.5);
        $pdf->setTextColor(0.0, 0.0, 0.0);
        $y += 6;
        $pdf->text($right - 170, $y, 'Total de la consulta:');
        $pdf->text($right - 70, $y, money($consulta['total']));

        $y += 26;
    }
}

// Total general.
if (!empty($consultas)) {
    if ($y + 30 > 755) {
        $y = $nuevaPagina();
    }

    $pdf->setDrawColor(0.80, 0.84, 0.86);
    $pdf->line($left, $y - 10, $right, $y - 10, 0.6);

    $pdf->setFont('bold', 12);
    $pdf->setTextColor(0.07, 0.30, 0.21);
    $pdf->text($left, $y + 6, 'Total facturado en la historia:');
    $pdf->text($right - 100, $y + 6, money($totalHistoria));
}

// ---------------------------------------------------------------------------
// Salida
// ---------------------------------------------------------------------------
$pdfData  = $pdf->output();
$cedula   = preg_replace('/[^A-Za-z0-9_-]/', '_', $paciente['cedula']);
$filename = 'historia_clinica_' . $cedula . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfData));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfData;
exit;
