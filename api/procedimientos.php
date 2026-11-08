<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$pdo    = db();
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $q       = cadena($_GET['q'] ?? '');
            $activos = isset($_GET['activos']) && $_GET['activos'] === '1';

            $sql    = 'SELECT id, nombre, descripcion, precio, activo, creado_en FROM procedimientos';
            $where  = [];
            $params = [];

            if ($activos) {
                $where[] = 'activo = 1';
            }

            if ($q !== '') {
                $where[] = '(nombre LIKE :q1 OR descripcion LIKE :q2)';
                $params[':q1'] = '%' . $q . '%';
                $params[':q2'] = '%' . $q . '%';
            }

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY nombre ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            json_response(['ok' => true, 'data' => $stmt->fetchAll()]);

        case 'get':
            $id = entero($_GET['id'] ?? 0);

            $stmt = $pdo->prepare('SELECT * FROM procedimientos WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $procedimiento = $stmt->fetch();

            if (!$procedimiento) {
                json_response(['ok' => false, 'error' => 'Procedimiento no encontrado.'], 404);
            }

            json_response(['ok' => true, 'data' => $procedimiento]);

        case 'save':
            require_csrf();

            $data = input();

            $id          = entero($data['id'] ?? 0);
            $nombre      = cadena($data['nombre'] ?? '');
            $descripcion = cadena($data['descripcion'] ?? '');
            $precio      = (float) ($data['precio'] ?? 0);
            $activo      = ((int) ($data['activo'] ?? 1)) === 1 ? 1 : 0;

            if ($nombre === '') {
                json_response(['ok' => false, 'error' => 'El nombre del procedimiento es obligatorio.'], 422);
            }

            if ($precio < 0) {
                json_response(['ok' => false, 'error' => 'El precio no puede ser negativo.'], 422);
            }

            $stmt = $pdo->prepare('SELECT id FROM procedimientos WHERE nombre = ? AND id <> ? LIMIT 1');
            $stmt->execute([$nombre, $id]);

            if ($stmt->fetch()) {
                json_response(['ok' => false, 'error' => 'Ya existe un procedimiento con ese nombre.'], 422);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE procedimientos
                     SET nombre = ?, descripcion = ?, precio = ?, activo = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $nombre,
                    $descripcion !== '' ? $descripcion : null,
                    $precio,
                    $activo,
                    $id,
                ]);

                $mensaje = 'Procedimiento actualizado correctamente.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO procedimientos (nombre, descripcion, precio, activo)
                     VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([
                    $nombre,
                    $descripcion !== '' ? $descripcion : null,
                    $precio,
                    $activo,
                ]);

                $id = (int) $pdo->lastInsertId();
                $mensaje = 'Procedimiento registrado correctamente.';
            }

            json_response(['ok' => true, 'id' => $id, 'message' => $mensaje]);

        case 'delete':
            require_csrf();

            $data = input();
            $id   = entero($data['id'] ?? 0);

            if ($id <= 0) {
                json_response(['ok' => false, 'error' => 'Procedimiento no válido.'], 422);
            }

            try {
                $stmt = $pdo->prepare('DELETE FROM procedimientos WHERE id = ?');
                $stmt->execute([$id]);

                json_response(['ok' => true, 'message' => 'Procedimiento eliminado correctamente.']);
            } catch (PDOException $e) {
                // 23000 = violación de llave foránea: el procedimiento está en uso.
                if ($e->getCode() === '23000') {
                    $stmt = $pdo->prepare('UPDATE procedimientos SET activo = 0 WHERE id = ?');
                    $stmt->execute([$id]);

                    json_response([
                        'ok'      => true,
                        'message' => 'El procedimiento está en uso en historias clínicas; se desactivó en lugar de eliminarse.',
                    ]);
                }

                throw $e;
            }

        default:
            json_response(['ok' => false, 'error' => 'Acción no reconocida.'], 400);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
