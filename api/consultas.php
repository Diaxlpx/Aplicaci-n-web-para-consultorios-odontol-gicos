<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$pdo    = db();
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $pacienteId = entero($_GET['paciente_id'] ?? 0);

            $sql = 'SELECT c.*, u.nombre AS odontologo
                    FROM consultas c
                    INNER JOIN usuarios u ON u.id = c.usuario_id';

            $params = [];

            if ($pacienteId > 0) {
                $sql .= ' WHERE c.paciente_id = :pid';
                $params[':pid'] = $pacienteId;
            }

            $sql .= ' ORDER BY c.fecha DESC, c.id DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $consultas = $stmt->fetchAll();

            $procStmt = $pdo->prepare(
                'SELECT cp.id, cp.costo, cp.observaciones, pr.nombre
                 FROM consulta_procedimientos cp
                 INNER JOIN procedimientos pr ON pr.id = cp.procedimiento_id
                 WHERE cp.consulta_id = ?
                 ORDER BY cp.id ASC'
            );

            foreach ($consultas as &$consulta) {
                $procStmt->execute([$consulta['id']]);
                $consulta['procedimientos'] = $procStmt->fetchAll();
            }
            unset($consulta);

            json_response(['ok' => true, 'data' => $consultas]);

        case 'save':
            require_csrf();

            $data = input();

            $pacienteId    = entero($data['paciente_id'] ?? 0);
            $fecha         = cadena($data['fecha'] ?? '');
            $motivo        = cadena($data['motivo'] ?? '');
            $diagnostico   = cadena($data['diagnostico'] ?? '');
            $tratamiento   = cadena($data['tratamiento'] ?? '');
            $observaciones = cadena($data['observaciones'] ?? '');
            $procedimientos = $data['procedimientos'] ?? [];

            if ($pacienteId <= 0) {
                json_response(['ok' => false, 'error' => 'Paciente no válido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT id FROM pacientes WHERE id = ? LIMIT 1');
            $stmt->execute([$pacienteId]);

            if (!$stmt->fetch()) {
                json_response(['ok' => false, 'error' => 'El paciente no existe.'], 404);
            }

            if ($fecha === '' || !fecha_valida($fecha)) {
                json_response(['ok' => false, 'error' => 'Indique una fecha válida.'], 422);
            }

            if (!is_array($procedimientos) || count($procedimientos) === 0) {
                json_response([
                    'ok'    => false,
                    'error' => 'Debe agregar al menos un procedimiento realizado.',
                ], 422);
            }

            // Normalizar y validar los procedimientos enviados.
            $normalizados = [];
            $ids = [];

            foreach ($procedimientos as $item) {
                $procId = entero($item['procedimiento_id'] ?? 0);
                $costo  = (float) ($item['costo'] ?? 0);

                if ($procId <= 0) {
                    continue;
                }

                $normalizados[] = ['id' => $procId, 'costo' => max(0.0, $costo)];
                $ids[] = $procId;
            }

            if (empty($normalizados)) {
                json_response([
                    'ok'    => false,
                    'error' => 'Debe seleccionar procedimientos válidos.',
                ], 422);
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id FROM procedimientos WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $validos = array_map('intval', array_column($stmt->fetchAll(), 'id'));

            foreach ($normalizados as &$item) {
                if (!in_array($item['id'], $validos, true)) {
                    json_response([
                        'ok'    => false,
                        'error' => 'Uno de los procedimientos seleccionados no existe.',
                    ], 422);
                }
            }
            unset($item);

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO consultas
                        (paciente_id, usuario_id, fecha, motivo, diagnostico, tratamiento, observaciones)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $pacienteId,
                    (int) current_user()['id'],
                    $fecha,
                    $motivo !== '' ? $motivo : null,
                    $diagnostico !== '' ? $diagnostico : null,
                    $tratamiento !== '' ? $tratamiento : null,
                    $observaciones !== '' ? $observaciones : null,
                ]);

                $consultaId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    'INSERT INTO consulta_procedimientos (consulta_id, procedimiento_id, costo)
                     VALUES (?, ?, ?)'
                );

                foreach ($normalizados as $item) {
                    $stmt->execute([$consultaId, $item['id'], $item['costo']]);
                }

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            json_response([
                'ok'      => true,
                'id'      => $consultaId,
                'message' => 'Consulta registrada en la historia clínica.',
            ]);

        case 'delete':
            require_csrf();

            $data = input();
            $id   = entero($data['id'] ?? 0);

            if ($id <= 0) {
                json_response(['ok' => false, 'error' => 'Consulta no válida.'], 422);
            }

            $stmt = $pdo->prepare('DELETE FROM consultas WHERE id = ?');
            $stmt->execute([$id]);

            json_response(['ok' => true, 'message' => 'Consulta eliminada de la historia clínica.']);

        default:
            json_response(['ok' => false, 'error' => 'Acción no reconocida.'], 400);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
