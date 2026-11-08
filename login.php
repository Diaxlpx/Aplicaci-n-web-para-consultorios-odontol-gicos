<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión expiró. Intente nuevamente.';
    } elseif ($email === '' || $password === '') {
        $error = 'Ingrese su correo y contraseña.';
    } else {
        try {
            $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($password, $usuario['password'])) {
                session_regenerate_id(true);

                $_SESSION['usuario'] = [
                    'id'     => (int) $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email'  => $usuario['email'],
                    'rol'    => $usuario['rol'],
                ];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: index.php');
                exit;
            }

            $error = 'Correo o contraseña incorrectos.';
        } catch (Throwable $e) {
            $error = 'No se pudo iniciar sesión. Verifique la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="login-page">
    <main class="card login-card">
        <div class="card-body p-4 p-sm-5">
            <div class="login-logo"><i class="bi bi-heart-pulse-fill"></i></div>

            <h1 class="h4 text-center mb-1"><?= APP_NAME ?></h1>
            <p class="text-center text-muted mb-4"><?= APP_SUBTITLE ?></p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($email) ?>" placeholder="usuario@correo.com"
                               autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="••••••••" autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                </button>
            </form>

            <div class="login-hint mt-4">
                <strong>Credenciales de prueba</strong><br>
                Correo: <code>admin@odontologia.com</code><br>
                Contraseña: <code>admin123</code>
            </div>
        </div>
    </main>
</body>
</html>
