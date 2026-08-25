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
        }

        @media (max-width: 900px) {
            body { padding: 10px; }
            .summary-grid { grid-template-columns: 1fr; }
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

    <div class="card">
        <h2>Bitácoras consecutivas</h2>
        <?php if ($totalBitacoras === 0): ?>
            <p>No hay bitácoras registradas para este paciente.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($informes as $index => $informe): ?>
                    <article class="bitacora-item">
                        <div class="bitacora-meta">
                            <h3>Registro <?= e((string) ($index + 1)) ?></h3>
                            <span class="pill"><?= e((string) $informe['fecha_registro']) ?></span>
                        </div>
                        <div class="bitacora-grid">
                            <p><strong>Profesional:</strong> <?= e((string) $informe['profesional_nombre']) ?></p>
                            <p><strong>Actividad diaria:</strong> <?= e((string) $informe['resumen_jornada']) ?></p>
                            <p><strong>Comportamiento:</strong> <?= e((string) $informe['comportamiento_observado']) ?></p>
                            <p><strong>Manejo brindado:</strong> <?= e((string) $informe['manejo_brindado']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card actions">
        <button class="btn" type="button" onclick="window.location.href='/Sanpablo/public/paciente_detalle.php?id=<?= e((string) $pacienteId) ?>'">Volver al detalle</button>
    </div>

    <?= renderIframeNavButtons() ?>
</body>
</html>
