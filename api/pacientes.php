<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$pdo    = db();
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $q   = cadena($_GET['q'] ?? '');
            $sql = 'SELECT id, nombres, apellidos, cedula, fecha_nacimiento, sexo,
                           telefono, email, direccion, alergias, antecedentes, creado_en
                    FROM pacientes';

            if ($q !== '') {
                $sql .= " WHERE nombres LIKE :q1
                           OR apellidos LIKE :q2
                           OR cedula LIKE :q3
                           OR CONCAT(nombres, ' ', apellidos) LIKE :q4";
            }

            $sql .= ' ORDER BY apellidos ASC, nombres ASC';

            $stmt = $pdo->prepare($sql);

            if ($q !== '') {
                $like = '%' . $q . '%';

                $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
                $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
                $stmt->bindValue(':q3', $like, PDO::PARAM_STR);
                $stmt->bindValue(':q4', $like, PDO::PARAM_STR);
            }

            $stmt->execute();

            json_response(['ok' => true, 'data' => $stmt->fetchAll()]);

        case 'get':
            $id = entero($_GET['id'] ?? 0);

            $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $paciente = $stmt->fetch();

            if (!$paciente) {
                json_response(['ok' => false, 'error' => 'Paciente no encontrado.'], 404);
            }

            json_response(['ok' => true, 'data' => $paciente]);

        case 'save':
            require_csrf();

            $data = input();

            $id        = entero($data['id'] ?? 0);
            $nombres   = cadena($data['nombres'] ?? '');
            $apellidos = cadena($data['apellidos'] ?? '');
            $cedula    = cadena($data['cedula'] ?? '');
            $fechaNac  = cadena($data['fecha_nacimiento'] ?? '');
            $sexo      = cadena($data['sexo'] ?? '');
            $telefono  = cadena($data['telefono'] ?? '');
            $email     = cadena($data['email'] ?? '');
            $direccion = cadena($data['direccion'] ?? '');
            $alergias  = cadena($data['alergias'] ?? '');
            $anteced   = cadena($data['antecedentes'] ?? '');

            if ($nombres === '' || $apellidos === '' || $cedula === '') {
                json_response([
                    'ok'    => false,
                    'error' => 'Los campos nombres, apellidos y cédula son obligatorios.',
                ], 422);
            }

            if ($fechaNac !== '' && !fecha_valida($fechaNac)) {
                json_response(['ok' => false, 'error' => 'La fecha de nacimiento no es válida.'], 422);
            }

            if ($sexo !== '' && !in_array($sexo, ['M', 'F', 'O'], true)) {
                json_response(['ok' => false, 'error' => 'El sexo seleccionado no es válido.'], 422);
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_response(['ok' => false, 'error' => 'El correo electrónico no es válido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT id FROM pacientes WHERE cedula = ? AND id <> ? LIMIT 1');
            $stmt->execute([$cedula, $id]);

            if ($stmt->fetch()) {
                json_response([
                    'ok'    => false,
                    'error' => 'Ya existe un paciente registrado con esa cédula.',
                ], 422);
            }

            $params = [
                ':nombres'          => $nombres,
                ':apellidos'        => $apellidos,
                ':cedula'           => $cedula,
                ':fecha_nacimiento' => $fechaNac !== '' ? $fechaNac : null,
                ':sexo'             => $sexo !== '' ? $sexo : null,
                ':telefono'         => $telefono !== '' ? $telefono : null,
                ':email'            => $email !== '' ? $email : null,
                ':direccion'        => $direccion !== '' ? $direccion : null,
                ':alergias'         => $alergias !== '' ? $alergias : null,
                ':antecedentes'     => $anteced !== '' ? $anteced : null,
            ];

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE pacientes SET
                        nombres = :nombres,
                        apellidos = :apellidos,
                        cedula = :cedula,
                        fecha_nacimiento = :fecha_nacimiento,
                        sexo = :sexo,
                        telefono = :telefono,
                        email = :email,
                        direccion = :direccion,
                        alergias = :alergias,
                        antecedentes = :antecedentes
                     WHERE id = :id'
                );

                $params[':id'] = $id;
                $stmt->execute($params);

                $mensaje = 'Paciente actualizado correctamente.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO pacientes
                        (nombres, apellidos, cedula, fecha_nacimiento, sexo,
                         telefono, email, direccion, alergias, antecedentes)
                     VALUES
                        (:nombres, :apellidos, :cedula, :fecha_nacimiento, :sexo,
                         :telefono, :email, :direccion, :alergias, :antecedentes)'
                );

                $stmt->execute($params);
                $id = (int) $pdo->lastInsertId();

                $mensaje = 'Paciente registrado correctamente.';
            }

            json_response(['ok' => true, 'id' => $id, 'message' => $mensaje]);

        case 'delete':
            require_csrf();

            $data = input();
            $id   = entero($data['id'] ?? 0);

            if ($id <= 0) {
                json_response(['ok' => false, 'error' => 'Paciente no válido.'], 422);
            }

            $stmt = $pdo->prepare('DELETE FROM pacientes WHERE id = ?');
            $stmt->execute([$id]);

            json_response(['ok' => true, 'message' => 'Paciente eliminado correctamente.']);

        default:
            json_response(['ok' => false, 'error' => 'Acción no reconocida.'], 400);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
