<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['ok' => false, 'error' => 'Sesión expirada. Inicie sesión nuevamente.'], 401);
}

/**
 * Lee el cuerpo de la petición: JSON o formulario.
 */
function input(): array
{
    $raw = file_get_contents('php://input');

    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return $_POST;
}

/**
 * Exige un token CSRF válido en peticiones de escritura.
 */
function require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');

    if (!verify_csrf((string) $token)) {
        json_response([
            'ok'    => false,
            'error' => 'Token de seguridad inválido. Recargue la página e intente de nuevo.',
        ], 419);
    }
}

function entero($valor): int
{
    return (int) ($valor ?? 0);
}

function cadena($valor): string
{
    return trim((string) ($valor ?? ''));
}

function fecha_valida(string $fecha): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $fecha);

    return $date !== false && $date->format('Y-m-d') === $fecha;
}
