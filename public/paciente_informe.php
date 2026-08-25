<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requerirLogin();

$db = Conexion::getConexion();
$pacienteModel = new PacienteModel($db);
$asignacionModel = new AsignacionModel($db);

$pacienteId = (int) ($_GET['id'] ?? 0);
$usuarioId = (int) ($_SESSION['usuario']['id'] ?? 0);
$esVistaProfesional = esProfesional();

if ($esVistaProfesional && !$asignacionModel->pacienteAsignadoAProfesional($pacienteId, $usuarioId)) {
    http_response_code(403);
    exit('No tienes permiso para ver este paciente.');
}

$paciente = $pacienteModel->obtenerPorId($pacienteId);
if (!$paciente) {
    http_response_code(404);
    exit('Paciente no encontrado.');
}

$informes = $asignacionModel->listarPorPaciente($pacienteId);
$totalBitacoras = count($informes);
$ultimaBitacora = $informes[0]['fecha_registro'] ?? null;
$bitacoraSeleccionadaId = (int) ($_GET['bitacora'] ?? 0);
$bitacoraSeleccionada = null;
foreach ($informes as $informe) {
    if ((int) $informe['id'] === $bitacoraSeleccionadaId) {
        $bitacoraSeleccionada = $informe;
        break;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe general | San Pablo</title>
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
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--text-main);
            background: linear-gradient(145deg, #f4f7fb 0%, #e9eff7 100%);
            padding: 18px;
        }

        .card {
            background: var(--panel-bg);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 14px;
        }

        h1, h2 { margin: 0 0 10px; color: #24486f; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .summary-item {
            border: 1px solid #e3ebf8;
            border-radius: 10px;
            padding: 10px;
            background: #f8fbff;
        }

        .summary-item h3 {
            margin: 0;
            font-size: 0.78rem;
            color: #5d7090;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .summary-item p {
            margin: 6px 0 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #24486f;
        }

        .timeline {
            display: grid;
            gap: 12px;
        }

        .bitacora-item {
            border: 1px solid #dfe8f6;
            border-radius: 12px;
            background: #fff;
            padding: 12px;
        }

        .bitacora-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .bitacora-meta h3 {
            margin: 0;
            color: #24486f;
            font-size: 1rem;
        }

        .pill {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #edf4fd;
            color: #2e5f95;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .bitacora-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .bitacora-grid p {
            margin: 0;
            line-height: 1.4;
        }

        .bitacora-resumen {
            display: grid;
            grid-template-columns: 1fr auto auto;
            align-items: center;
            gap: 14px;
        }

        .bitacora-resumen h3,
        .bitacora-resumen p { margin: 0; }

        .bitacora-resumen h3 { color: #24486f; font-size: 1rem; }
        .bitacora-resumen p { color: var(--text-soft); font-size: .9rem; }
        .btn-link { text-decoration: none; white-space: nowrap; }

        .detail-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .detail-heading h2 { margin: 0; }

        .actions {
            display: flex;
            justify-content: flex-end;
        }

        .btn {
            border: 0;
            border-radius: 8px;
            padding: 9px 12px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, #3984c6, #8b3a8b);
            color: #fff;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            body { padding: 10px; }
            .summary-grid { grid-template-columns: 1fr; }
            .bitacora-resumen { grid-template-columns: 1fr; gap: 8px; }
            .bitacora-resumen .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Informe general del paciente</h1>
        <p><strong>Paciente:</strong> <?= e($paciente['nombre'] . ' ' . $paciente['apellido']) ?></p>
        <p><strong>Documento:</strong> <?= e((string) $paciente['documento_identidad']) ?></p>
        <div class="summary-grid">
            <article class="summary-item">
                <h3>Total bitácoras</h3>
                <p><?= e((string) $totalBitacoras) ?></p>
            </article>
            <article class="summary-item">
                <h3>Último registro</h3>
                <p><?= e((string) ($ultimaBitacora ?? '-')) ?></p>
            </article>
            <article class="summary-item">
                <h3>Estado actual</h3>
                <p><?= e((string) $paciente['estado']) ?></p>
            </article>
        </div>
    </div>

    <?php if ($bitacoraSeleccionada !== null): ?>
    <div class="card">
        <div class="detail-heading">
            <h2>Bitácora #<?= e((string) $bitacoraSeleccionada['id']) ?></h2>
            <a class="btn" href="/Sanpablo/public/paciente_informe.php?id=<?= e((string) $pacienteId) ?>">Volver al listado</a>
        </div>
        <p><strong>Fecha:</strong> <?= e((string) ($bitacoraSeleccionada['fecha_bitacora'] ?: $bitacoraSeleccionada['fecha_registro'])) ?></p>
        <div class="bitacora-grid">
            <p><strong>Nombre del estudiante:</strong> <?= e($paciente['nombre'] . ' ' . $paciente['apellido']) ?></p>
            <p><strong>Grado:</strong> <?= e((string) ($bitacoraSeleccionada['grado'] ?? '-')) ?></p>
            <p><strong>Profesional:</strong> <?= e((string) $bitacoraSeleccionada['profesional_nombre']) ?></p>
            <p><strong>Actividad realizada:</strong> <?= e((string) $bitacoraSeleccionada['resumen_jornada']) ?></p>
            <p><strong>Nivel de participación:</strong> <?= e((string) ($bitacoraSeleccionada['nivel_participacion'] ?: '-')) ?></p>
            <p><strong>Descripción de la participación:</strong> <?= e((string) ($bitacoraSeleccionada['descripcion_participacion'] ?: $bitacoraSeleccionada['comportamiento_observado'])) ?></p>
            <p><strong>Apoyos brindados:</strong> <?= e((string) ($bitacoraSeleccionada['apoyos_brindados'] ?: $bitacoraSeleccionada['manejo_brindado'])) ?></p>
            <p><strong>Avances de logros estipulados:</strong> <?= e((string) ($bitacoraSeleccionada['avances_logros'] ?? '-')) ?></p>
            <p><strong>Dificultades observadas:</strong> <?= e((string) ($bitacoraSeleccionada['dificultades_observadas'] ?: $bitacoraSeleccionada['novedades_alertas'])) ?></p>
            <p><strong>Observaciones:</strong> <?= e((string) ($bitacoraSeleccionada['observaciones'] ?? '-')) ?></p>
            <p><strong>Firma digital:</strong> <?= e((string) ($bitacoraSeleccionada['firma_digital'] ?? '-')) ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <h2>Bitácoras consecutivas</h2>
        <?php if ($totalBitacoras === 0): ?>
            <p>No hay bitácoras registradas para este paciente.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($informes as $informe): ?>
                    <article class="bitacora-item">
                        <div class="bitacora-resumen">
                            <h3>Bitácora #<?= e((string) $informe['id']) ?></h3>
                            <p><?= e((string) ($informe['fecha_bitacora'] ?: $informe['fecha_registro'])) ?></p>
                            <a class="btn btn-link" href="/Sanpablo/public/paciente_informe.php?id=<?= e((string) $pacienteId) ?>&amp;bitacora=<?= e((string) $informe['id']) ?>">Ver detalle</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card actions">
        <button class="btn" type="button" onclick="window.location.href='/Sanpablo/public/paciente_detalle.php?id=<?= e((string) $pacienteId) ?>'">Volver al detalle</button>
    </div>

    <?= renderIframeNavButtons() ?>
</body>
</html>
