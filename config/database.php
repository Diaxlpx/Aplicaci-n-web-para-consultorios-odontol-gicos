<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Conexión a la base de datos (PDO / MySQL)
|--------------------------------------------------------------------------
| En XAMPP los valores por defecto son:
|   host: 127.0.0.1   usuario: root   contraseña: (vacía)
|
| En un hosting gratuito (InfinityFree, FreeHostia, etc.) cambie estos
| valores por los que le entregue el proveedor.
*/

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'consultorio_odontologico');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Devuelve una única conexión PDO reutilizable.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit(
            'No se pudo conectar a la base de datos.<br>' .
            'Revise <code>config/database.php</code>.<br><br>' .
            'Detalle: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        );
    }

    return $pdo;
}
