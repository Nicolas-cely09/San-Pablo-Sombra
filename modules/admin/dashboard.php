<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
requerirAdministrador();

$db = Conexion::getConexion();
$adminController = new AdminController(new UserModel($db), new PacienteModel($db), new AsignacionModel($db));
$flash = obtenerFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!validarTokenCsrf($token)) {
        flash('error', 'Token CSRF invalido.');
        redirect('/Sanpablo/public/dashboard.php');
    }

    try {
        $accion = trim((string) ($_POST['accion'] ?? ''));

        if ($accion === 'crear_profesional') {
            $adminController->crearProfesional($_POST, $_FILES);
            flash('ok', 'Profesional creado correctamente.');
        } elseif ($accion === 'actualizar_profesional') {
            $adminController->actualizarProfesional($_POST);
            flash('ok', 'Profesional actualizado correctamente.');
        } elseif ($accion === 'actualizar_paciente') {
            $adminController->actualizarPaciente($_POST);
            flash('ok', 'Paciente actualizado correctamente.');
        } else {
            flash('error', 'Accion no soportada.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    redirect('/Sanpablo/public/dashboard.php');
}

$csrfToken = generarTokenCsrf();
$profesionales = $adminController->listarProfesionales();
$pacientes = $adminController->listarPacientes();
$asignaciones = $adminController->listarAsignaciones();
$tiposDocumentosProfesional = UserModel::tiposDocumentosProfesional();

$totalProfesionales = count($profesionales);
$totalPacientes = count($pacientes);
$totalAsignacionesActivas = count(array_filter($asignaciones, static fn(array $a): bool => (string) ($a['estado'] ?? '') === 'activo'));

function selected(string $actual, string $esperado): string
{
    return $actual === $esperado ? 'selected' : '';
}

function badgeEstado(string $estado): string
{
    return $estado === 'activo' ? 'badge-activo' : 'badge-off';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador | San Pablo</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

        :root {
            --blue-sp: #3984c6;
            --violet-sp: #8b3a8b;
            --text-main: #222222;
            --text-soft: #61708a;
            --panel-bg: #ffffff;
            --line: #e0e0e0;
            --shadow: 0 14px 34px rgba(30, 54, 88, 0.12);
            --ok-bg: #e4f8d5;
            --ok-ink: #4a7b14;
            --off-bg: #ffe6f2;
            --off-ink: #8f2f62;
            --danger-bg: #ffe6eb;
            --danger-ink: #9f2444;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--text-main);
            background: linear-gradient(145deg, #f4f7fb 0%, #e9eff7 100%);
        }

        .layout {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 12px;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 14px;
        }

        .sidebar {
            min-height: calc(100vh - 24px);
            background: var(--panel-bg);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 14px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            border-radius: 999px;
            border: 1px solid #ecf1f7;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 18px rgba(54, 72, 97, 0.08);
            margin-bottom: 14px;
            background: #fff;
        }

        .brand-logo {
            width: 100%;
            max-width: 210px;
            height: auto;
            display: block;
        }

        .brand-sub {
            margin: 0;
            font-size: 0.66rem;
            color: var(--text-soft);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .sidebar-context {
            margin: 12px 0 14px;
            padding: 10px 12px;
            border: 1px solid #e8eef7;
            border-radius: 10px;
            background: #f8fbff;
        }

        .sidebar-profile {
            display: flex;
            justify-content: center;
            margin-bottom: 8px;
        }

        .sidebar-profile-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3984c6;
        }

        .sidebar-profile-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e8eef7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: #61708a;
            border: 2px dashed #cddbeb;
        }

        .sidebar-role {
            margin: 0 0 4px;
            font-size: 0.78rem;
            color: #24486f;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .sidebar-session {
            margin: 0;
            font-size: 0.78rem;
            color: var(--text-soft);
            font-weight: 600;
            line-height: 1.35;
        }

        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu button,
        .menu a {
            width: 100%;
            border: 0;
            text-decoration: none;
            background: transparent;
            color: #333333;
            font: inherit;
            font-weight: 600;
            text-align: left;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .menu button:hover,
        .menu a:hover {
            background: #f0f4f9;
            color: var(--blue-sp);
        }

        .menu button.active {
            background: #edf4fd;
            color: var(--blue-sp);
            border-left: 4px solid var(--blue-sp);
            padding-left: 12px;
        }

        .menu-logout {
            border-top: 1px solid #eeeeee;
            padding-top: 12px;
            margin-top: auto;
        }

        .icon {
            width: 18px;
            height: 18px;
            border: 2px solid #94a4bc;
            border-radius: 6px;
            position: relative;
            flex: 0 0 auto;
        }

        .icon.people::before,
        .icon.people::after {
            content: "";
            position: absolute;
            border: 2px solid #94a4bc;
            border-radius: 50%;
            width: 6px;
            height: 6px;
        }

        .icon.people::before { left: 1px; top: 1px; }
        .icon.people::after { right: 1px; bottom: 1px; }
        .icon.patient::before { content: ""; position: absolute; inset: 3px; border: 2px solid #94a4bc; border-radius: 50%; }
        .icon.logout::before { content: ""; position: absolute; left: 2px; top: 7px; width: 11px; height: 2px; background: #94a4bc; }

        .main {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e2e9f2;
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 14px;
        }

        .main-menu-row {
            display: none;
            margin-bottom: 10px;
        }

        .menu-toggle {
            display: none;
            border: 1px solid #d2deed;
            background: #f5f8fd;
            color: #24486f;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 1rem;
            cursor: pointer;
        }

        .flash {
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .flash.ok { background: var(--ok-bg); color: var(--ok-ink); border: 1px solid #cce9b3; }
        .flash.error { background: var(--danger-bg); color: var(--danger-ink); border: 1px solid #ffc9d5; }

        .overview { display: none; }
        .overview.active { display: block; }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .stat {
            background: #fff;
            border: 1px solid #e6eef8;
            border-radius: 12px;
            padding: 12px;
        }

        .stat h4 {
            margin: 0;
            font-size: 0.78rem;
            color: var(--text-soft);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat p {
            margin: 7px 0 0;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--blue-sp);
        }

        .panel { display: none; }
        .panel.active { display: block; }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .action-toggle {
            border: 1px solid #d2deed;
            background: #f5f8fd;
            color: #24486f;
            border-radius: 8px;
            padding: 8px 12px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .action-panel {
            display: none;
            background: #fff;
            border: 1px solid #e2ebf8;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .action-panel.active { display: block; }

        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field.full { grid-column: 1 / -1; }
        .document-profesional-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; gap: 8px; align-items: end; margin-bottom: 8px; }
        .document-profesional-row .link-btn { margin: 0; }

        label { font-size: 0.8rem; color: #4b6383; font-weight: 700; }

        input,
        select,
        textarea {
            border: 1px solid #cddbeb;
            border-radius: 8px;
            padding: 8px 10px;
            font: inherit;
            color: var(--text-main);
            background: #fff;
        }

        textarea { min-height: 78px; resize: vertical; }

        .btn {
            border: 0;
            border-radius: 8px;
            padding: 9px 12px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-primary { background: linear-gradient(135deg, #3984c6, #8b3a8b); color: #fff; }
        .btn-secondary { background: linear-gradient(135deg, #3984c6, #54b4ce); color: #fff; }

        .table-title {
            margin: 0 0 8px;
            color: #24486f;
        }

        .table-wrap {
            border: 1px solid #e5edf7;
            border-radius: 10px;
            overflow-x: auto;
            background: #fff;
        }

        .link-btn {
            border: 0;
            background: transparent;
            color: var(--blue-sp);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
        }

        .detalle-frame-panel {
            display: none;
            margin-top: 8px;
            border: 1px solid #e5edf7;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow);
        }

        .detalle-frame-panel.active { display: block; }

        #panel-pacientes.iframe-activo .actions .action-toggle[data-open-iframe] { display: none; }
        #panel-pacientes.iframe-activo .table-title,
        #panel-pacientes.iframe-activo .table-wrap { display: none !important; }

        #panel-profesionales.detalle-activo .actions,
        #panel-profesionales.detalle-activo .action-panel,
        #panel-profesionales.detalle-activo .table-title,
        #panel-profesionales.detalle-activo .table-wrap { display: none !important; }

        #panel-profesionales.detalle-activo > .detalle-frame-panel { margin-top: 0; }

        .detalle-frame-panel iframe {
            width: 100%;
            height: calc(100vh - 92px);
            min-height: 540px;
            border: 0;
            background: #fff;
            display: block;
        }
        .frame-footer {
            border-top: 1px solid #edf2fb;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .frame-footer .frame-label {
            margin: 0;
            font-size: 0.84rem;
            color: #4b6383;
            font-weight: 700;
        }

        .frame-footer .action-toggle { margin: 0; }

        table { width: 100%; border-collapse: collapse; min-width: 860px; }

        th,
        td {
            font-size: 0.84rem;
            padding: 9px;
            text-align: left;
            border-bottom: 1px solid #edf2fb;
            vertical-align: top;
        }

        th {
            background: #f3f7fd;
            color: #50627c;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.74rem;
        }

        .inline-form { display: flex; align-items: center; gap: 6px; }
        .inline-form select { min-width: 96px; }

        .badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 0.73rem;
            font-weight: 700;
        }

        .badge-activo { background: var(--ok-bg); color: var(--ok-ink); }
        .badge-off { background: var(--off-bg); color: var(--off-ink); }

        @media (max-width: 1100px) {
            .layout { grid-template-columns: 1fr; padding: 8px; margin: 0; }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 250px;
                min-height: 100vh;
                z-index: 40;
                transform: translateX(-102%);
                transition: transform 0.25s ease;
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(17, 24, 39, 0.35);
                z-index: 30;
            }
            .sidebar-overlay.active { display: block; }
            .main-menu-row { display: flex; }
            button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { outline: 3px solid #d91b72; outline-offset: 2px; }
            .menu-toggle { display: inline-flex; align-items: center; justify-content: center; }
            .stats, .field-grid { grid-template-columns: 1fr; }
            .detalle-frame-panel iframe { height: calc(100vh - 92px); min-height: 520px; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <img class="brand-logo" src="/Sanpablo/public/assets/logo-sanpablo-horizontal.svg" alt="San Pablo">
            </div>
            <p class="brand-sub">Centro terapeutico integral</p>
            <div class="sidebar-context">
                <p class="sidebar-role">Panel administrador</p>
                <p class="sidebar-session">Sesion: <?= e($_SESSION['usuario']['nombre_completo']) ?></p>
            </div>

            <nav aria-label="Navegacion principal">
            <ul class="menu">
                <li><button type="button" data-panel="profesionales"><span class="icon people"></span>Profesionales</button></li>
                <li><button type="button" data-panel="pacientes"><span class="icon patient"></span>Pacientes</button></li>
                <li class="menu-logout"><a href="/Sanpablo/public/logout.php"><span class="icon logout"></span>Logout</a></li>
            </ul>
            </nav>
        </aside>

        <main class="main">
            <div class="main-menu-row">
                <button class="menu-toggle" type="button" id="menuToggle" aria-label="Abrir menú">☰</button>
            </div>

            <?= renderToastFlash($flash) ?>

            <section class="overview active" id="overview">
                <div class="stats">
                    <article class="stat"><h4>Total profesionales</h4><p><?= e((string) $totalProfesionales) ?></p></article>
                    <article class="stat"><h4>Total pacientes</h4><p><?= e((string) $totalPacientes) ?></p></article>
                    <article class="stat"><h4>Asignaciones activas</h4><p><?= e((string) $totalAsignacionesActivas) ?></p></article>
                </div>
            </section>

            <section class="panel" id="panel-profesionales">
                <div class="actions">
                    <button class="action-toggle" type="button" data-toggle="crear-profesional">Crear profesional</button>
                </div>

                <section class="action-panel" id="crear-profesional">
                    <form method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="accion" value="crear_profesional">
                        <div class="field-grid">
                            <div class="field"><label>Documento</label><input type="text" name="documento_identidad" required></div>
                            <div class="field"><label>Correo</label><input type="email" name="email" required></div>
                            <div class="field"><label>Nombre</label><input type="text" name="nombre" required></div>
                            <div class="field"><label>Apellido</label><input type="text" name="apellido" required></div>
                            <div class="field"><label>Telefono</label><input type="text" name="telefono"></div>
                            <div class="field"><label>Contrasena inicial</label><input type="password" name="password" minlength="8" required></div>
                            <div class="field full">
                                <label>Documentos del profesional</label>
                                <div id="documentosProfesionalContainer">
                                    <div class="document-profesional-row">
                                        <select name="documentos_tipo[]">
                                            <option value="">Selecciona un tipo...</option>
                                            <?php foreach ($tiposDocumentosProfesional as $tipoDocumento): ?>
                                                <option value="<?= e($tipoDocumento) ?>"><?= e($tipoDocumento) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="file" name="documentos_archivo[]" data-profesional-file>
                                        <button class="link-btn" type="button" data-remove-profesional-document style="display: none;">Quitar</button>
                                    </div>
                                </div>
                                <button class="action-toggle" type="button" id="addProfesionalDocument">Añadir otro documento</button>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Guardar profesional</button>
                    </form>
                </section>

                <h3 class="table-title">Listado de profesionales</h3>
                        <section class="detalle-frame-panel" id="detalle-profesional-panel">
                            <iframe id="detalleProfesionalFrame" title="Detalle del profesional" src="about:blank"></iframe>
                                <div class="frame-footer">
                                    <p class="frame-label" id="detalleProfesionalTitulo">Detalle del profesional</p>
                                    <button id="btn-crear-profesional-footer" class="action-toggle" type="button" data-toggle="crear-profesional">Crear profesional</button>
                                </div>
                        </section>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Telefono</th>
                                <th>Estado</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($profesionales) === 0): ?>
                            <tr><td colspan="6">No hay profesionales registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($profesionales as $pro): ?>
                                <tr>
                                    <td><?= e((string) $pro['documento_identidad']) ?></td>
                                    <td><?= e($pro['nombre'] . ' ' . $pro['apellido']) ?></td>
                                    <td><?= e($pro['email']) ?></td>
                                    <td><?= e((string) $pro['telefono']) ?></td>
                                    <td>
                                        <form method="post" action="" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="accion" value="actualizar_profesional">
                                            <input type="hidden" name="id" value="<?= e((string) $pro['id']) ?>">
                                            <input type="hidden" name="documento_identidad" value="<?= e((string) $pro['documento_identidad']) ?>">
                                            <input type="hidden" name="nombre" value="<?= e($pro['nombre']) ?>">
                                            <input type="hidden" name="apellido" value="<?= e($pro['apellido']) ?>">
                                            <input type="hidden" name="email" value="<?= e($pro['email']) ?>">
                                            <input type="hidden" name="telefono" value="<?= e((string) $pro['telefono']) ?>">
                                            <select name="estado" onchange="this.form.submit()">
                                                <option value="activo" <?= selected((string) $pro['estado'], 'activo') ?>>Activo</option>
                                                <option value="inactivo" <?= selected((string) $pro['estado'], 'inactivo') ?>>Inactivo</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><button class="link-btn" type="button" data-profesional-id="<?= e((string) $pro['id']) ?>">Ver detalle</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" id="panel-pacientes">
                <div class="actions">
                    <button id="btn-crear-paciente" class="action-toggle" type="button" data-open-iframe="/Sanpablo/public/paciente_crear.php" data-frame-title="Crear paciente">Crear paciente</button>
                </div>

                <section class="detalle-frame-panel" id="detalle-paciente-panel">
                    <iframe id="detallePacienteFrame" title="Detalle del paciente" src="about:blank"></iframe>
                    <div class="frame-footer">
                        <p class="frame-label" id="detalleFrameTitulo">Detalle del paciente</p>
                        <button id="btn-crear-paciente-footer" class="action-toggle" type="button" data-open-iframe="/Sanpablo/public/paciente_crear.php" data-frame-title="Crear paciente">Crear paciente</button>
                    </div>
                </section>

                <h3 class="table-title">Listado de pacientes</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Edad</th>
                                <th>Colegio</th>
                                <th>Acudiente</th>
                                <th>Contacto</th>
                                <th>Estado</th>
                                <th>Profesional</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($pacientes) === 0): ?>
                            <tr><td colspan="9">No hay pacientes registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pacientes as $pac): ?>
                                <tr>
                                    <td><?= e((string) $pac['documento_identidad']) ?></td>
                                    <td><?= e($pac['nombre'] . ' ' . $pac['apellido']) ?></td>
                                    <td><?= e((string) ($pac['edad'] ?? 0)) ?></td>
                                    <td><?= e((string) ($pac['colegio'] ?? '-')) ?></td>
                                    <td><?= e((string) $pac['nombre_acudiente']) ?></td>
                                    <td><?= e((string) $pac['contacto_acudiente']) ?></td>
                                    <td>
                                        <form method="post" action="" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="accion" value="actualizar_paciente">
                                            <input type="hidden" name="id" value="<?= e((string) $pac['id']) ?>">
                                            <input type="hidden" name="documento_identidad" value="<?= e((string) $pac['documento_identidad']) ?>">
                                            <input type="hidden" name="nombre" value="<?= e((string) $pac['nombre']) ?>">
                                            <input type="hidden" name="apellido" value="<?= e((string) $pac['apellido']) ?>">
                                            <input type="hidden" name="fecha_nacimiento" value="<?= e((string) $pac['fecha_nacimiento']) ?>">
                                            <input type="hidden" name="nombre_acudiente" value="<?= e((string) $pac['nombre_acudiente']) ?>">
                                            <input type="hidden" name="contacto_acudiente" value="<?= e((string) $pac['contacto_acudiente']) ?>">
                                            <input type="hidden" name="colegio" value="<?= e((string) ($pac['colegio'] ?? '')) ?>">
                                            <input type="hidden" name="tipo_discapacidad" value="<?= e((string) ($pac['tipo_discapacidad'] ?? 'Intelectual')) ?>">
                                            <input type="hidden" name="otra_discapacidad" value="<?= e((string) ($pac['otra_discapacidad'] ?? '')) ?>">
                                            <input type="hidden" name="observaciones_iniciales" value="<?= e((string) $pac['observaciones_iniciales']) ?>">
                                            <select name="estado" onchange="this.form.submit()">
                                                <option value="activo" <?= selected((string) $pac['estado'], 'activo') ?>>Activo</option>
                                                <option value="inactivo" <?= selected((string) $pac['estado'], 'inactivo') ?>>Inactivo</option>
                                                <option value="finalizado" <?= selected((string) $pac['estado'], 'finalizado') ?>>Finalizado</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?= e((string) ($pac['profesional_nombre'] ?? '-')) ?></td>
                                    <td><button class="link-btn" type="button" data-paciente-id="<?= e((string) $pac['id']) ?>">Ver detalle</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        (function () {
            const menuButtons = document.querySelectorAll('.menu button[data-panel]');
            const panels = document.querySelectorAll('.panel');
            const overview = document.getElementById('overview');
            const toggles = document.querySelectorAll('[data-toggle]');
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const detailPanel = document.getElementById('detalle-paciente-panel');
            const detailFrame = document.getElementById('detallePacienteFrame');
            const pacientesPanel = document.getElementById('panel-pacientes');
            const detalleFrameTitulo = document.getElementById('detalleFrameTitulo');
            const crearPacienteBtn = document.getElementById('btn-crear-paciente');
                        const crearPacienteFooterBtn = document.getElementById('btn-crear-paciente-footer');
                        const crearProfesionalFooterBtn = document.getElementById('btn-crear-profesional-footer');
            const profesionalesPanel = document.getElementById('panel-profesionales');
            const profesionalDetailPanel = document.getElementById('detalle-profesional-panel');
            const profesionalDetailFrame = document.getElementById('detalleProfesionalFrame');
            const profesionalDetailTitle = document.getElementById('detalleProfesionalTitulo');

            function showOverview() {
                menuButtons.forEach((btn) => btn.classList.remove('active'));
                panels.forEach((panel) => panel.classList.remove('active'));
                overview.classList.add('active');
            }

            function setIframePacienteActivo(activo) {
                if (pacientesPanel) {
                    pacientesPanel.classList.toggle('iframe-activo', activo);
                }
                if (detailPanel) {
                    detailPanel.classList.toggle('active', activo);
                }
            }

            function setDetalleProfesionalActivo(activo) {
                if (profesionalesPanel) {
                    profesionalesPanel.classList.toggle('detalle-activo', activo);
                }
                if (profesionalDetailPanel) {
                    profesionalDetailPanel.classList.toggle('active', activo);
                }
            }

            function openPanel(panelName) {
                overview.classList.remove('active');
                menuButtons.forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.panel === panelName);
                });
                panels.forEach((panel) => {
                    panel.classList.toggle('active', panel.id === 'panel-' + panelName);
                });

                if (panelName !== 'pacientes') {
                    setIframePacienteActivo(false);
                }
                if (panelName !== 'profesionales') {
                    setDetalleProfesionalActivo(false);
                }
            }

            menuButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    openPanel(button.dataset.panel);
                });
            });

            toggles.forEach((toggle) => {
                toggle.addEventListener('click', function () {
                    const targetId = toggle.dataset.toggle;
                    const panel = document.getElementById(targetId);
                    if (!panel) {
                        return;
                    }
                    panel.classList.toggle('active');
                });
            });

            function abrirCreacionPaciente(boton) {
                const targetUrl = boton.dataset.openIframe || '';
                const targetTitle = boton.dataset.frameTitle || 'Crear paciente';

                if (detailFrame) {
                    detailFrame.src = targetUrl;
                }

                if (detalleFrameTitulo) {
                    detalleFrameTitulo.textContent = targetTitle;
                }

                setIframePacienteActivo(true);
            }

            if (crearPacienteBtn) {
                crearPacienteBtn.addEventListener('click', function () {
                    abrirCreacionPaciente(crearPacienteBtn);
                });
            }

            if (crearPacienteFooterBtn) {
                crearPacienteFooterBtn.addEventListener('click', function () {
                    abrirCreacionPaciente(crearPacienteFooterBtn);
                });
            }

            if (crearProfesionalFooterBtn) {
                crearProfesionalFooterBtn.addEventListener('click', function () {
                    setDetalleProfesionalActivo(false);
                });
            }

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                    if (overlay) {
                        overlay.classList.toggle('active');
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function () {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                });
            }

            document.querySelectorAll('.link-btn[data-paciente-id]').forEach((button) => {
                button.addEventListener('click', function () {
                    const pacienteId = button.dataset.pacienteId;
                    if (detailFrame) {
                        detailFrame.src = '/Sanpablo/public/paciente_detalle.php?id=' + pacienteId;
                    }
                    if (detalleFrameTitulo) {
                        detalleFrameTitulo.textContent = 'Detalle del paciente';
                    }
                    setIframePacienteActivo(true);
                });
            });

            document.querySelectorAll('.link-btn[data-profesional-id]').forEach((button) => {
                button.addEventListener('click', function () {
                    const profesionalId = button.dataset.profesionalId;
                    if (profesionalDetailFrame) {
                        profesionalDetailFrame.src = '/Sanpablo/public/profesional_detalle.php?id=' + profesionalId;
                    }
                    if (profesionalDetailTitle) {
                        profesionalDetailTitle.textContent = 'Detalle del profesional';
                    }
                    setDetalleProfesionalActivo(true);
                });
            });

            showOverview();

            const documentosProfesionalContainer = document.getElementById('documentosProfesionalContainer');
            const addProfesionalDocument = document.getElementById('addProfesionalDocument');
            if (documentosProfesionalContainer && addProfesionalDocument) {
                addProfesionalDocument.addEventListener('click', function () {
                    const fila = documentosProfesionalContainer.querySelector('.document-profesional-row').cloneNode(true);
                    fila.querySelector('select').value = '';
                    fila.querySelector('[data-profesional-file]').value = '';
                    fila.querySelector('[data-remove-profesional-document]').style.display = 'inline-block';
                    documentosProfesionalContainer.appendChild(fila);
                });

                documentosProfesionalContainer.addEventListener('click', function (event) {
                    const boton = event.target.closest('[data-remove-profesional-document]');
                    if (boton) {
                        boton.closest('.document-profesional-row').remove();
                    }
                });
            }
        })();
    </script>
</body>
</html>
