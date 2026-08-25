<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
requerirProfesional();

$db = Conexion::getConexion();
$userModel = new UserModel($db);
$profesionalController = new ProfesionalController(new AsignacionModel($db));
$flash = obtenerFlash();
$profesionalId = (int) ($_SESSION['usuario']['id'] ?? 0);

// Cargar foto del profesional
$adjuntos = $userModel->listarAdjuntosProfesional($profesionalId);
$fotoProfesional = null;
foreach ($adjuntos as $adjunto) {
    if (str_contains(strtolower((string) ($adjunto['tipo'] ?? '')), 'foto')) {
        $fotoProfesional = (string) $adjunto['ruta_archivo'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!validarTokenCsrf($token)) {
        flash('error', 'Token CSRF invalido.');
        redirect('/Sanpablo/public/dashboard.php');
    }

    try {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear_informe') {
            $profesionalController->crearInforme($profesionalId, $_POST);
            flash('ok', 'Informe creado correctamente.');
        } else {
            flash('error', 'Accion no soportada.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    redirect('/Sanpablo/public/dashboard.php');
}

$csrfToken = generarTokenCsrf();
$asignaciones = $profesionalController->listarPacientesAsignados($profesionalId);
$informes = $profesionalController->listarInformes($profesionalId);

$totalPacientesAsignados = count($asignaciones);
$totalAsignacionesActivas = count(array_filter($asignaciones, static fn(array $a): bool => $a['estado'] === 'activo'));
$totalInformes = count($informes);

function badgeEstadoProfesional(string $estado): string
{
    return $estado === 'activo' ? 'badge-activo' : 'badge-off';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Profesional | San Pablo</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

        :root {
            --blue-sp: #3984c6;
            --violet-sp: #8b3a8b;
            --magenta-sp: #e21b79;
            --lime-sp: #a3d133;
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
            margin-bottom: 18px;
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

        .icon.patient::before { content: ""; position: absolute; inset: 3px; border: 2px solid #94a4bc; border-radius: 50%; }
        .icon.logout::before { content: ""; position: absolute; left: 2px; top: 7px; width: 11px; height: 2px; background: #94a4bc; }

        .main {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e2e9f2;
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 14px;
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

        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field.full { grid-column: 1 / -1; }

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

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--blue-sp);
            box-shadow: 0 0 0 3px rgba(57, 132, 198, 0.16);
        }

        textarea { min-height: 90px; resize: vertical; }

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

        .detalle-frame-panel.active {
            display: block;
        }

        #panel-pacientes.detalle-activo .action-panel,
        #panel-pacientes.detalle-activo .table-title,
        #panel-pacientes.detalle-activo .table-wrap {
            display: none !important;
        }

        .detalle-frame-panel iframe {
            width: 100%;
            height: calc(100vh - 92px);
            min-height: 540px;
            border: 0;
            background: #fff;
            display: block;
        }

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
            .layout { grid-template-columns: 1fr; padding: 8px; }
            .sidebar { min-height: auto; }
            .stats, .field-grid { grid-template-columns: 1fr; }
            .detalle-frame-panel iframe { height: calc(100vh - 92px); min-height: 520px; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <img class="brand-logo" src="/Sanpablo/public/assets/logo-sanpablo-horizontal.svg" alt="San Pablo">
            </div>
            <p class="brand-sub">Centro terapeutico integral</p>
            <div class="sidebar-context">
                <div class="sidebar-profile">
                    <?php if ($fotoProfesional !== null): ?>
                        <img src="<?= e($fotoProfesional) ?>" alt="Foto del profesional" class="sidebar-profile-photo">
                    <?php else: ?>
                        <div class="sidebar-profile-placeholder">Sin foto</div>
                    <?php endif; ?>
                </div>
                <p class="sidebar-role">Panel profesional</p>
                <p class="sidebar-session">Sesion: <?= e($_SESSION['usuario']['nombre_completo']) ?></p>
            </div>

            <ul class="menu">
                <li><button type="button" data-panel="pacientes"><span class="icon patient"></span>Pacientes asignados</button></li>
                <li><button type="button" data-panel="perfil"><span class="icon patient"></span>Mi perfil</button></li>
                <li class="menu-logout"><a href="/Sanpablo/public/logout.php"><span class="icon logout"></span>Logout</a></li>
            </ul>
        </aside>

        <main class="main">

            <?= renderToastFlash($flash) ?>

            <section class="overview active" id="overview">
                <div class="stats">
                    <article class="stat"><h4>Pacientes asignados</h4><p><?= e((string) $totalPacientesAsignados) ?></p></article>
                    <article class="stat"><h4>Asignaciones activas</h4><p><?= e((string) $totalAsignacionesActivas) ?></p></article>
                    <article class="stat"><h4>Informes creados</h4><p><?= e((string) $totalInformes) ?></p></article>
                </div>
            </section>

            <section class="panel" id="panel-pacientes">
                <section class="detalle-frame-panel" id="detalle-paciente-panel">
                    <iframe id="detallePacienteFrame" title="Detalle del paciente" src="about:blank"></iframe>
                </section>

                <h3 class="table-title">Mis pacientes asignados</h3>
                <div class="table-wrap" style="margin-bottom: 12px;">
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
                        <?php if (count($asignaciones) === 0): ?>
                            <tr><td colspan="9">No tienes pacientes asignados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($asignaciones as $asignacion): ?>
                                <tr>
                                    <td><?= e((string) $asignacion['paciente_documento']) ?></td>
                                    <td><?= e($asignacion['paciente_nombre'] . ' ' . $asignacion['paciente_apellido']) ?></td>
                                    <td><?= e((string) ($asignacion['paciente_edad'] ?? 0)) ?></td>
                                    <td><?= e((string) ($asignacion['paciente_colegio'] ?? '-')) ?></td>
                                    <td><?= e((string) ($asignacion['paciente_acudiente'] ?? '-')) ?></td>
                                    <td><?= e((string) ($asignacion['paciente_contacto'] ?? '-')) ?></td>
                                    <td><span class="badge <?= e(badgeEstadoProfesional((string) $asignacion['paciente_estado'])) ?>"><?= e((string) $asignacion['paciente_estado']) ?></span></td>
                                    <td><?= e((string) ($asignacion['profesional_nombre'] ?? '-')) ?></td>
                                    <td><button class="link-btn" type="button" data-paciente-id="<?= e((string) $asignacion['paciente_id']) ?>">Ver detalle</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" id="panel-perfil">
                <section class="detalle-frame-panel active" id="detalle-perfil-panel">
                    <iframe id="detallePerfilFrame" title="Mi perfil" src="/Sanpablo/public/profesional_detalle.php"></iframe>
                </section>
            </section>
        </main>
    </div>

    <script>
        (function () {
            const menuButtons = document.querySelectorAll('.menu button[data-panel]');
            const panels = document.querySelectorAll('.panel');
            const overview = document.getElementById('overview');
            const pacientesPanel = document.getElementById('panel-pacientes');
            const detailPanel = document.getElementById('detalle-paciente-panel');
            const detailFrame = document.getElementById('detallePacienteFrame');

            function showOverview() {
                menuButtons.forEach((btn) => btn.classList.remove('active'));
                panels.forEach((panel) => panel.classList.remove('active'));
                overview.classList.add('active');
            }

            function openPanel(panelName) {
                overview.classList.remove('active');
                menuButtons.forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.panel === panelName);
                });
                panels.forEach((panel) => {
                    panel.classList.toggle('active', panel.id === 'panel-' + panelName);
                });
            }

            function setDetallePacienteActivo(activo) {
                if (pacientesPanel) {
                    pacientesPanel.classList.toggle('detalle-activo', activo);
                }
                if (detailPanel) {
                    detailPanel.classList.toggle('active', activo);
                }
            }

            menuButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    openPanel(button.dataset.panel);
                });
            });

            document.querySelectorAll('.link-btn[data-paciente-id]').forEach((button) => {
                button.addEventListener('click', function () {
                    const pacienteId = button.dataset.pacienteId;
                    if (detailFrame) {
                        detailFrame.src = '/Sanpablo/public/paciente_detalle.php?id=' + pacienteId;
                    }
                    setDetallePacienteActivo(true);
                });
            });

            showOverview();
        })();
    </script>
</body>
</html>
