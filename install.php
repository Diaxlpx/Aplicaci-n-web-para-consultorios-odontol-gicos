<?php
declare(strict_types=1);

/**
 * Instalador local.
 *
 * Crea la base de datos, las tablas y los datos iniciales.
 * Ejecútelo UNA sola vez desde el navegador:
 *     http://localhost/Trabajo_web/install.php
 *
 * Luego elimine este archivo en producción.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$ok = false;
$mensajes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);

        $pdo->exec(
            'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` ' .
            'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        $pdo->exec('USE `' . DB_NAME . '`');

        $sql = file_get_contents(__DIR__ . '/database/schema.sql');

        if ($sql === false) {
            throw new RuntimeException('No se pudo leer database/schema.sql');
        }

        $pdo->exec($sql);

        $ok = true;
        $mensajes[] = 'Base de datos creada correctamente.';
        $mensajes[] = 'Tablas y datos iniciales cargados.';
    } catch (Throwable $e) {
        $mensajes[] = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width: 720px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Instalación de <?= APP_NAME ?></h1>

                <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                    <p class="text-secondary">
                        Este asistente creará la base de datos
                        <code><?= DB_NAME ?></code>, las tablas y los datos iniciales.
                    </p>
                    <div class="alert alert-warning">
                        <strong>Requisito:</strong> el usuario de MySQL debe tener permiso para
                        crear la base de datos. En XAMPP el usuario <code>root</code> lo tiene.
                    </div>
                    <form method="post">
                        <button class="btn btn-success" type="submit">Instalar ahora</button>
                    </form>
                <?php else: ?>
                    <?php foreach ($mensajes as $mensaje): ?>
                        <div class="alert alert-<?= $ok ? 'success' : 'danger' ?> mb-2">
                            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($ok): ?>
                        <p class="mt-3">Credenciales de acceso:</p>
                        <ul>
                            <li>Correo: <code>admin@odontologia.com</code></li>
                            <li>Contraseña: <code>admin123</code></li>
                        </ul>
                        <div class="d-flex gap-2">
                            <a href="login.php" class="btn btn-primary">Ir al inicio de sesión</a>
                        </div>
                        <hr>
                        <p class="text-danger small mb-0">
                            Por seguridad, elimine <code>install.php</code> después de instalar.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
