<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user        = current_user();
$pageTitle   = $pageTitle ?? 'Panel principal';
$activePage  = $activePage ?? '';

/**
 * Imprime los enlaces del menú lateral.
 */
function render_menu(string $active): void
{
    $items = [
        ['panel',          'Panel principal', 'bi-speedometer2',      'index.php'],
        ['pacientes',      'Pacientes',       'bi-people-fill',       'pacientes.php'],
        ['procedimientos', 'Procedimientos',  'bi-clipboard2-pulse',  'procedimientos.php'],
    ];

    foreach ($items as [$key, $label, $icon, $href]) {
        $class = $key === $active ? 'side-link active' : 'side-link';
        echo '<a class="' . $class . '" href="' . $href . '">';
        echo '<i class="bi ' . $icon . '"></i><span>' . $label . '</span>';
        echo '</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · <?= APP_NAME ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="currency" content="<?= e(MONEDA) ?>">
</head>
<body>
<div class="app-shell">

    <!-- Menú lateral (escritorio) -->
    <aside class="sidebar d-none d-lg-flex flex-column">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></div>
            <div>
                <strong><?= APP_SHORT ?></strong>
                <small><?= APP_SUBTITLE ?></small>
            </div>
        </div>

        <nav class="side-nav"><?php render_menu($activePage); ?></nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar avatar-light"><?= e(strtoupper(substr($user['nombre'] ?? 'U', 0, 1))) ?></div>
                <div class="sidebar-user-info">
                    <strong><?= e($user['nombre'] ?? 'Usuario') ?></strong>
                    <small><?= e(ucfirst($user['rol'] ?? '')) ?></small>
                </div>
            </div>
            <a class="side-link side-link-logout" href="logout.php">
                <i class="bi bi-box-arrow-right"></i><span>Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- Menú lateral (móvil) -->
    <div class="offcanvas offcanvas-start sidebar-offcanvas" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header border-0">
            <div class="brand mb-0">
                <div class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></div>
                <div>
                    <strong><?= APP_SHORT ?></strong>
                    <small><?= APP_SUBTITLE ?></small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <nav class="side-nav"><?php render_menu($activePage); ?></nav>
            <div class="sidebar-footer mt-auto">
                <a class="side-link side-link-logout" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>Cerrar sesión</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="main-area">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Abrir menú">
                <i class="bi bi-list fs-5"></i>
            </button>

            <h1 class="topbar-title"><?= e($pageTitle) ?></h1>

            <div class="topbar-user">
                <span class="d-none d-sm-inline"><?= e($user['nombre'] ?? '') ?></span>
                <div class="avatar"><?= e(strtoupper(substr($user['nombre'] ?? 'U', 0, 1))) ?></div>
            </div>
        </header>

        <main class="content p-3 p-md-4">
            <?php foreach (get_flashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>
