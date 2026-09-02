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
$esVistaAdmin = esAdministrador();

if ($esVistaProfesional && !$asignacionModel->pacienteAsignadoAProfesional($pacienteId, $usuarioId)) {
    http_response_code(403);
    exit('No tienes permiso para ver este paciente.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!validarTokenCsrf($token)) {
        flash('error', 'Token CSRF invalido.');
        redirect('/Sanpablo/public/paciente_detalle.php?id=' . $pacienteId);
    }

    try {
        $accion = trim((string) ($_POST['accion'] ?? ''));

            if ($accion === 'subir_adjunto') {
                $tipoAdjunto = trim((string) ($_POST['tipo_adjunto'] ?? ''));
                $archivo = $_FILES['documento_adjunto'] ?? null;

                if ($tipoAdjunto === '' || !is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new InvalidArgumentException('Selecciona un tipo y un archivo valido.');
                }

                $tiposPermitidos = $esVistaAdmin
                    ? PacienteModel::tiposDocumentosPaciente()
                    : PacienteModel::tiposDocumentosCargablesProfesional();
                if (!in_array($tipoAdjunto, $tiposPermitidos, true)) {
                    throw new RuntimeException('No tienes permiso para cargar este tipo de documento.');
                }

                $pacienteModel->guardarAdjunto($pacienteId, $archivo, $tipoAdjunto);
                flash('ok', 'Documento adjunto cargado correctamente.');
                redirect('/Sanpablo/public/paciente_detalle.php?id=' . $pacienteId . '&section=documentos');
            }

            if ($accion === 'guardar_objetivo_general') {
                $objetivoGeneral = trim((string) ($_POST['objetivo_general'] ?? ''));
                if ($objetivoGeneral === '') {
                    throw new InvalidArgumentException('El objetivo general no puede estar vacio.');
                }

                $pacienteModel->guardarObjetivoGeneral($pacienteId, $objetivoGeneral);
                flash('ok', 'Objetivo general guardado correctamente.');
                redirect('/Sanpablo/public/paciente_detalle.php?id=' . $pacienteId . '&section=objetivos');
            }

            if ($accion === 'crear_objetivo_especifico') {
                if (!$esVistaAdmin) {
                    throw new RuntimeException('Solo el administrador puede crear objetivos especificos.');
                }

                $descripcion = trim((string) ($_POST['descripcion_objetivo'] ?? ''));
                if ($descripcion === '') {
                    throw new InvalidArgumentException('La descripcion del objetivo es obligatoria.');
                }

                $pacienteModel->crearObjetivoEspecifico($pacienteId, $descripcion, 'Semanal');
                flash('ok', 'Objetivo especifico agregado correctamente.');
                redirect('/Sanpablo/public/paciente_detalle.php?id=' . $pacienteId . '&section=objetivos');
            }

            if ($accion === 'evaluar_objetivo') {
                $objetivoId = (int) ($_POST['objetivo_id'] ?? 0);
                $estadoObjetivo = trim((string) ($_POST['estado_objetivo'] ?? 'pendiente'));
                $observacion = trim((string) ($_POST['observacion_objetivo'] ?? ''));

                if ($objetivoId <= 0 || !in_array($estadoObjetivo, ['pendiente', 'cumplido', 'no_cumplido'], true)) {
                    throw new InvalidArgumentException('La evaluacion del objetivo no es valida.');
                }
                if ($estadoObjetivo === 'no_cumplido' && $observacion === '') {
                    throw new InvalidArgumentException('Escribe una observacion cuando el objetivo no se cumple.');
                }

                $pacienteModel->actualizarObjetivoEspecifico($pacienteId, $objetivoId, $estadoObjetivo, $observacion);
                flash('ok', 'Evaluacion del objetivo guardada correctamente.');
                redirect('/Sanpablo/public/paciente_detalle.php?id=' . $pacienteId . '&section=objetivos');
            }

        if ($accion === 'crear_informe') {
            if (!$esVistaProfesional) {
                throw new RuntimeException('No tienes permisos para registrar bitacoras.');
            }

                $camposBitacora = [
                    'fecha_bitacora' => 'fecha',
                    'grado' => 'grado',
                    'actividad_realizada' => 'actividad realizada',
                    'nivel_participacion' => 'nivel de participacion',
                    'descripcion_participacion' => 'descripcion de la participacion',
                    'apoyos_brindados' => 'apoyos brindados',
                    'avances_logros' => 'avances de logros estipulados',
                    'dificultades_observadas' => 'dificultades observadas',
                ];
                foreach ($camposBitacora as $campo => $nombreCampo) {
                    if (trim((string) ($_POST[$campo] ?? '')) === '') {
                        throw new InvalidArgumentException('Completa el campo: ' . $nombreCampo . '.');
                    }
                }
                if (!in_array((string) $_POST['nivel_participacion'], ['Alta', 'Media', 'Baja'], true)) {
                    throw new InvalidArgumentException('Selecciona un nivel de participacion valido.');
                }

            $asignacion = $asignacionModel->obtenerAsignacionPorPacienteYProfesional($pacienteId, $usuarioId);
            if ($asignacion === null) {
                throw new RuntimeException('No se encontro una asignacion valida para este paciente.');
            }

            $asignacionModel->crearInforme($usuarioId, [
                'asignacion_id' => (int) $asignacion['id'],
                'fecha_bitacora' => trim((string) ($_POST['fecha_bitacora'] ?? date('Y-m-d'))),
                'grado' => trim((string) ($_POST['grado'] ?? '')),
                'resumen_jornada' => trim((string) ($_POST['actividad_realizada'] ?? '')),
                'nivel_participacion' => trim((string) ($_POST['nivel_participacion'] ?? '')),
                'descripcion_participacion' => trim((string) ($_POST['descripcion_participacion'] ?? '')),
                'apoyos_brindados' => trim((string) ($_POST['apoyos_brindados'] ?? '')),
                'avances_logros' => trim((string) ($_POST['avances_logros'] ?? '')),
                'dificultades_observadas' => trim((string) ($_POST['dificultades_observadas'] ?? '')),
                'observaciones' => trim((string) ($_POST['observaciones'] ?? '')),
                'firma_digital' => trim((string) ($_POST['firma_digital'] ?? '')),
                'comportamiento_observado' => trim((string) ($_POST['descripcion_participacion'] ?? '')),
                'novedades_alertas' => trim((string) ($_POST['dificultades_observadas'] ?? '')),
                'manejo_brindado' => trim((string) ($_POST['apoyos_brindados'] ?? '')),
            ]);

            flash('ok', 'Bitacora registrada correctamente.');
            redirect('/Sanpablo/public/paciente_informe.php?id=' . $pacienteId);
        }

        if ($accion === 'actualizar_paciente') {
            if (!$esVistaAdmin) {
                throw new RuntimeException('No tienes permisos para modificar este paciente.');
            }

            $estado = trim((string) ($_POST['estado'] ?? 'activo'));
            $tipoDiscapacidad = trim((string) ($_POST['tipo_discapacidad'] ?? ''));
            $otraDiscapacidad = trim((string) ($_POST['otra_discapacidad'] ?? ''));
            $tiposPermitidos = ['Intelectual', 'Sensorial', 'Física', 'Psicosocial', 'Múltiple', 'Otro'];

            if (!in_array($estado, ['activo', 'inactivo', 'finalizado'], true)) {
                throw new InvalidArgumentException('Estado del paciente no permitido.');
            }

            if ($tipoDiscapacidad === '' || !in_array($tipoDiscapacidad, $tiposPermitidos, true)) {
                throw new InvalidArgumentException('Debe seleccionar un tipo de discapacidad valido.');
            }

            if ($tipoDiscapacidad === 'Otro' && $otraDiscapacidad === '') {
                throw new InvalidArgumentException('Debe describir la otra discapacidad.');
            }

            $pacienteModel->actualizar([
                'id' => $pacienteId,
                'documento_identidad' => trim((string) ($_POST['documento_identidad'] ?? '')),
                'nombre' => trim((string) ($_POST['nombre'] ?? '')),
                'apellido' => trim((string) ($_POST['apellido'] ?? '')),
                'fecha_nacimiento' => trim((string) ($_POST['fecha_nacimiento'] ?? '')),
                'nombre_acudiente' => trim((string) ($_POST['nombre_acudiente'] ?? '')),
                'contacto_acudiente' => trim((string) ($_POST['contacto_acudiente'] ?? '')),
                'observaciones_iniciales' => trim((string) ($_POST['observaciones_iniciales'] ?? '')),
                'estado' => $estado,
                'tipo_discapacidad' => $tipoDiscapacidad,
                'otra_discapacidad' => $otraDiscapacidad,
                'colegio' => trim((string) ($_POST['colegio'] ?? '')),
            ]);

            flash('ok', 'Paciente actualizado correctamente.');
            redirect('/Sanpablo/public/paciente_detalle.php?id=' . $pacienteId . '&section=editar');
        }

        throw new InvalidArgumentException('Accion no soportada.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('/Sanpablo/public/paciente_detalle.php?id=' . $pacienteId);
    }
}

$paciente = $pacienteModel->obtenerPorId($pacienteId);
if (!$paciente) {
    http_response_code(404);
    exit('Paciente no encontrado.');
}

$adjuntos = $pacienteModel->listarAdjuntos($pacienteId);
$tiposVisibles = $esVistaAdmin
    ? PacienteModel::tiposDocumentosPaciente()
    : PacienteModel::tiposDocumentosVisiblesProfesional();
$adjuntosVisibles = array_values(array_filter($adjuntos, static function (array $adjunto) use ($tiposVisibles): bool {
    return in_array((string) ($adjunto['tipo'] ?? ''), $tiposVisibles, true);
}));
$objetivoGeneral = $pacienteModel->obtenerObjetivoGeneral($pacienteId);
$objetivosEspecificos = $pacienteModel->listarObjetivosEspecificos($pacienteId);
$informes = $asignacionModel->listarPorPaciente($pacienteId);
$flash = obtenerFlash();
$csrfToken = generarTokenCsrf();

$fotoPaciente = null;
foreach ($adjuntos as $adjunto) {
    $tipo = strtolower((string) ($adjunto['tipo'] ?? ''));
    if (str_contains($tipo, 'foto')) {
        $fotoPaciente = (string) $adjunto['ruta_archivo'];
        break;
    }
}

$seccionActiva = trim((string) ($_GET['section'] ?? ''));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle paciente | San Pablo</title>
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
            padding: 12px;
        }

        .flash {
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .flash.ok { background: #e4f8d5; color: #4a7b14; border: 1px solid #cce9b3; }
        .flash.error { background: #ffe6eb; color: #9f2444; border: 1px solid #ffc9d5; }

        .card {
            background: var(--panel-bg);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
            box-shadow: var(--shadow);
            margin-bottom: 10px;
        }

        .patient-summary h1 { margin-bottom: 12px; }
        body.section-open .patient-summary .info-layout { display: none; }
        body.section-open .patient-summary { margin-bottom: 10px; }

        h1, h2 { margin-top: 0; color: #24486f; }
        h1 { margin-bottom: 12px; font-size: clamp(1.45rem, 2.4vw, 2rem); }
        h2 { margin-bottom: 10px; font-size: 1.25rem; }

        .info-layout {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 14px;
            align-items: start;
        }

        .foto-wrap {
            border: 1px solid #e2ebf8;
            border-radius: 12px;
            min-height: 200px;
            background: #f7faff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .foto-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .foto-placeholder {
            color: var(--text-soft);
            font-size: 0.86rem;
            text-align: center;
            padding: 12px;
        }

        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
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
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        input:focus, select:focus, textarea:focus { border-color: var(--blue-sp); outline: 0; box-shadow: 0 0 0 3px rgba(57, 132, 198, .16); }
        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { outline: 3px solid #d91b72; outline-offset: 3px; }

        textarea { min-height: 80px; resize: vertical; }

        input[disabled],
        select[disabled],
        textarea[disabled] {
            background: #f7f9fd;
            color: #51627d;
            cursor: not-allowed;
        }

        .pill {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e4f8d5;
            color: #4a7b14;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 10px;
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

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 6px;
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
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(57, 132, 198, .2); filter: saturate(1.08); }

        .btn-secondary {
            background: linear-gradient(135deg, #3984c6, #54b4ce);
        }

        .section-panel { display: none; }
        .section-panel.active { display: block; }
        .observacion-objetivo { display: none; margin-top: 6px; }
        .observacion-objetivo.visible { display: block; }
        .objetivos-especificos-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 18px 0 10px; }
        .objetivos-especificos-heading h3 { margin: 0; }
        .btn-add-objective { width: 38px; height: 38px; margin: 0; padding: 0; border-radius: 50%; font-size: 1.45rem; line-height: 1; }
        .objective-create-form { display: none; margin-bottom: 14px; }
        .objective-create-form.active { display: block; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #edf2fb; text-align: left; vertical-align: top; }
        th { background: #f3f7fd; color: #50627c; }

        a { color: var(--blue-sp); }

        @media (max-width: 920px) {
            body { padding: 8px; }
            .info-layout { grid-template-columns: 1fr; }
            .summary-grid, .field-grid { grid-template-columns: 1fr; }
            .actions { justify-content: stretch; }
            .actions .btn { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; } }

        .objetivo-estado {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: help;
            position: relative;
        }

        .objetivo-estado.no-cumplido {
            background: #ffe6eb;
            color: #9f2444;
            border: 1px solid #ffc9d8;
        }

        .objetivo-estado:not(.no-cumplido) {
            background: #e4f8d5;
            color: #4a7b14;
            border: 1px solid #cbeab0;
        }

        .tooltip-observacion {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #2c3e50;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            max-width: 300px;
            white-space: normal;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 8px;
        }

        .tooltip-observacion::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #2c3e50;
        }

        .objetivo-estado.no-cumplido:hover .tooltip-observacion {
            display: block;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            max-width: 400px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 14px 34px rgba(30, 54, 88, 0.2);
        }

        .modal-content.small-modal {
            max-width: 350px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #24486f;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #61708a;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: #222;
        }


    </style>
</head>
<body>
    <?= renderToastFlash($flash) ?>

    <div class="card patient-summary">
        <h1><?= e($paciente['nombre'] . ' ' . $paciente['apellido']) ?></h1>
        <div class="info-layout">
            <div class="foto-wrap">
                <?php if ($fotoPaciente !== null): ?>
                    <img src="<?= e($fotoPaciente) ?>" alt="Fotografia del paciente">
                <?php else: ?>
                    <div class="foto-placeholder">Sin fotografia cargada</div>
                <?php endif; ?>
            </div>

            <form method="post" action="" id="formEditarPaciente">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="accion" value="actualizar_paciente">
                <div class="field-grid">
                    <div class="field"><label>Documento</label><input type="text" name="documento_identidad" value="<?= e((string) $paciente['documento_identidad']) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?> required></div>
                    <div class="field"><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento" value="<?= e((string) $paciente['fecha_nacimiento']) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?> required></div>
                    <div class="field"><label>Nombre</label><input type="text" name="nombre" value="<?= e((string) $paciente['nombre']) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?> required></div>
                    <div class="field"><label>Apellido</label><input type="text" name="apellido" value="<?= e((string) $paciente['apellido']) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?> required></div>
                    <div class="field"><label>Colegio</label><input type="text" name="colegio" value="<?= e((string) ($paciente['colegio'] ?? '')) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?>></div>
                    <div class="field"><label>Estado</label>
                        <select name="estado" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?>>
                            <option value="activo" <?= ((string) $paciente['estado'] === 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ((string) $paciente['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            <option value="finalizado" <?= ((string) $paciente['estado'] === 'finalizado') ? 'selected' : '' ?>>Finalizado</option>
                        </select>
                    </div>
                    <div class="field"><label>Tipo discapacidad</label>
                        <select name="tipo_discapacidad" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?> required>
                            <?php foreach (['Intelectual', 'Sensorial', 'Física', 'Psicosocial', 'Múltiple', 'Otro'] as $tipo): ?>
                                <option value="<?= e($tipo) ?>" <?= ((string) $paciente['tipo_discapacidad'] === $tipo) ? 'selected' : '' ?>><?= e($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field"><label>Otra discapacidad</label><input type="text" name="otra_discapacidad" value="<?= e((string) ($paciente['otra_discapacidad'] ?? '')) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?>></div>
                    <div class="field"><label>Acudiente</label><input type="text" name="nombre_acudiente" value="<?= e((string) ($paciente['nombre_acudiente'] ?? '')) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?>></div>
                    <div class="field"><label>Contacto</label><input type="text" name="contacto_acudiente" value="<?= e((string) ($paciente['contacto_acudiente'] ?? '')) ?>" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?>></div>
                    <div class="field full"><label>Observaciones</label><textarea name="observaciones_iniciales" <?= $esVistaAdmin ? 'disabled' : 'disabled' ?>><?= e((string) ($paciente['observaciones_iniciales'] ?? '')) ?></textarea></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card section-panel <?= ($seccionActiva === 'documentos') ? 'active' : '' ?>" id="panelDocumentos">
        <h2>Documentos adjuntos</h2>
        <?php if (count($adjuntosVisibles) === 0): ?>
            <p>No hay documentos cargados.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($adjuntosVisibles as $adjunto): ?>
                    <li><strong><?= e((string) $adjunto['tipo']) ?>:</strong> <a href="<?= e((string) $adjunto['ruta_archivo']) ?>" target="_blank"><?= e((string) $adjunto['nombre_original']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($esVistaAdmin || $esVistaProfesional): ?>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="accion" value="subir_adjunto">
            <div class="field-grid">
                <div class="field"><label>Tipo de documento</label><select name="tipo_adjunto" id="tipoDocumentoSelect" required><option value="">Selecciona un tipo...</option><?php foreach (($esVistaAdmin ? PacienteModel::tiposDocumentosPaciente() : PacienteModel::tiposDocumentosCargablesProfesional()) as $tipoDocumento): ?><option value="<?= e($tipoDocumento) ?>"><?= e($tipoDocumento) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>Archivo</label><input type="file" name="documento_adjunto" id="archivoInput" required></div>
            </div>
            <button class="btn" type="submit"><?= $esVistaAdmin ? 'Cargar documento' : 'Cargar anexo de seguimiento' ?></button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card section-panel <?= ($seccionActiva === 'objetivos') ? 'active' : '' ?>" id="panelObjetivos">
        <h2>Objetivos del plan de tratamiento</h2>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="accion" value="guardar_objetivo_general">
            <div class="field full">
                <label>Objetivo general</label>
                <textarea name="objetivo_general" required placeholder="Describe el objetivo general del plan de tratamiento"><?= e($objetivoGeneral) ?></textarea>
            </div>
            <button class="btn" type="submit">Guardar objetivo general</button>
        </form>

        <div class="objetivos-especificos-heading">
            <h3>Objetivos especificos</h3>
            <button class="btn btn-secondary btn-add-objective" type="button" id="btnNuevoObjetivo" aria-label="Añadir objetivo especifico" aria-expanded="false">+</button>
        </div>
        <?php if ($esVistaAdmin): ?>
        <form method="post" action="" class="objective-create-form" id="formNuevoObjetivo">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="accion" value="crear_objetivo_especifico">
            <div class="field-grid">
                <div class="field full"><label>Descripcion del objetivo</label><textarea name="descripcion_objetivo" required placeholder="Describe el resultado observable que se evaluara"></textarea></div>
            </div>
            <button class="btn btn-secondary" type="submit">Guardar objetivo especifico</button>
        </form>
        <?php endif; ?>

        <?php if (count($objetivosEspecificos) === 0): ?>
            <p>Aun no hay objetivos especificos registrados.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Codigo</th><th>Objetivo</th><th>Evaluacion</th></tr></thead>
                <tbody>
                    <?php foreach ($objetivosEspecificos as $objetivo): ?>
                        <tr>
                            <td><strong><?= e((string) $objetivo['codigo']) ?></strong></td>
                            <td><?= e((string) $objetivo['descripcion']) ?></td>
                            <td>
                                <?php if ($esVistaAdmin): ?>
                                    <span class="objetivo-estado <?= $objetivo['estado'] === 'no_cumplido' ? 'no-cumplido' : '' ?>"
                                          <?= $objetivo['estado'] === 'no_cumplido' && !empty($objetivo['observacion']) ? 'data-observacion="' . e((string) $objetivo['observacion']) . '"' : '' ?>>
                                        <?= e((string) $objetivo['estado']) ?>
                                    </span>
                                    <?php if ($objetivo['estado'] === 'no_cumplido' && !empty($objetivo['observacion'])): ?>
                                        <div class="tooltip-observacion"><?= e((string) $objetivo['observacion']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form method="post" action="">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="accion" value="evaluar_objetivo">
                                        <input type="hidden" name="objetivo_id" value="<?= e((string) $objetivo['id']) ?>">
                                        <select name="estado_objetivo" class="estado-objetivo">
                                            <option value="pendiente" <?= $objetivo['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                            <option value="cumplido" <?= $objetivo['estado'] === 'cumplido' ? 'selected' : '' ?>>Cumplido</option>
                                            <option value="no_cumplido" <?= $objetivo['estado'] === 'no_cumplido' ? 'selected' : '' ?>>No cumplido</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($esVistaProfesional): ?>
        <div class="card section-panel <?= ($seccionActiva === 'bitacora') ? 'active' : '' ?>" id="panelBitacora">
            <h2>Añadir bitácora</h2>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="accion" value="crear_informe">
                <div class="field-grid">
                    <div class="field"><label>Nombre del estudiante</label><input type="text" value="<?= e($paciente['nombre'] . ' ' . $paciente['apellido']) ?>" readonly></div>
                    <div class="field"><label>Grado</label><input type="text" name="grado" maxlength="100" required></div>
                    <div class="field"><label>Fecha</label><input type="date" name="fecha_bitacora" value="<?= e(date('Y-m-d')) ?>" required></div>
                    <div class="field"><label>Nivel de participación</label><select name="nivel_participacion" required><option value="">Selecciona un nivel...</option><option value="Alta">Alta</option><option value="Media">Media</option><option value="Baja">Baja</option></select></div>
                    <div class="field full">
                        <label>Actividad realizada</label>
                        <textarea name="actividad_realizada" maxlength="250" required placeholder="Describe la actividad realizada"></textarea>
                    </div>
                    <div class="field full">
                        <label>Descripción de la participación</label>
                        <textarea name="descripcion_participacion" maxlength="250" required placeholder="Describe la participación del estudiante"></textarea>
                    </div>
                    <div class="field full">
                        <label>Apoyos brindados</label>
                        <textarea name="apoyos_brindados" maxlength="250" required placeholder="Describe los apoyos brindados"></textarea>
                    </div>
                    <div class="field full">
                        <label>Avances de logros estipulados</label>
                        <textarea name="avances_logros" maxlength="250" required placeholder="Describe los avances observados"></textarea>
                    </div>
                    <div class="field full">
                        <label>Dificultades observadas</label>
                        <textarea name="dificultades_observadas" maxlength="250" required placeholder="Describe las dificultades observadas"></textarea>
                    </div>
                    <div class="field full">
                        <label>Observaciones</label>
                        <textarea name="observaciones" maxlength="250" placeholder="Registra observaciones adicionales"></textarea>
                    </div>
                    <div class="field full">
                        <label>Firma digital</label>
                        <input type="text" name="firma_digital" maxlength="255" placeholder="Nombre del profesional o referencia de firma">
                    </div>
                </div>
                <button class="btn" type="submit">Guardar bitácora</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="actions">
            <?php if ($esVistaAdmin): ?>
                <button class="btn btn-secondary" type="button" id="btnModificar">Modificar</button>
            <?php endif; ?>
            <button class="btn btn-secondary" type="button" data-section="documentos" aria-expanded="false">Consultar documentos</button>
            <button class="btn btn-secondary" type="button" data-section="objetivos">Objetivos</button>
            <button class="btn btn-secondary" type="button" data-open-url="/Sanpablo/public/paciente_informe.php?id=<?= e((string) $pacienteId) ?>">Informe general</button>
            <?php if ($esVistaProfesional): ?>
                <button class="btn" type="button" data-section="bitacora">Añadir bitácora</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        (function () {
            const botonesSeccion = document.querySelectorAll('[data-section]');
            const botonesRedireccion = document.querySelectorAll('[data-open-url]');
            const paneles = {
                documentos: document.getElementById('panelDocumentos'),
                objetivos: document.getElementById('panelObjetivos'),
                bitacora: document.getElementById('panelBitacora')
            };
            const botonDocumentos = document.querySelector('[data-section="documentos"]');
            const btnNuevoObjetivo = document.getElementById('btnNuevoObjetivo');
            const formNuevoObjetivo = document.getElementById('formNuevoObjetivo');

            if (btnNuevoObjetivo && formNuevoObjetivo) {
                btnNuevoObjetivo.addEventListener('click', function () {
                    const abierto = formNuevoObjetivo.classList.toggle('active');
                    btnNuevoObjetivo.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                    btnNuevoObjetivo.textContent = abierto ? '−' : '+';
                });
            }

            function mostrarSeccion(nombre, actualizarBoton = true) {
                document.body.classList.toggle('section-open', Boolean(nombre));
                Object.keys(paneles).forEach((clave) => {
                    const panel = paneles[clave];
                    if (!panel) {
                        return;
                    }
                    panel.classList.toggle('active', clave === nombre);
                });

                if (botonDocumentos && actualizarBoton) {
                    const documentosActivos = nombre === 'documentos';
                    botonDocumentos.setAttribute('aria-expanded', documentosActivos ? 'true' : 'false');
                    botonDocumentos.textContent = documentosActivos ? 'Cerrar documentos' : 'Consultar documentos';
                }
            }

            botonesSeccion.forEach((boton) => {
                boton.addEventListener('click', function () {
                    const target = boton.getAttribute('data-section');
                    if (!target) {
                        return;
                    }

                    if (target === 'documentos' && paneles.documentos.classList.contains('active')) {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('section');
                        window.history.replaceState({}, '', url);
                        mostrarSeccion('', true);
                        return;
                    }

                    mostrarSeccion(target);

                    if (target === 'documentos') {
                        const url = new URL(window.location.href);
                        url.searchParams.set('section', 'documentos');
                        window.history.pushState({ section: 'documentos' }, '', url);
                    } else {
                        const url = new URL(window.location.href);
                        url.searchParams.set('section', target);
                        window.history.replaceState({ section: target }, '', url);
                    }
                });
            });

            window.addEventListener('popstate', function () {
                const url = new URL(window.location.href);
                mostrarSeccion(url.searchParams.get('section') || '', true);
            });

            botonesRedireccion.forEach((boton) => {
                boton.addEventListener('click', function () {
                    const targetUrl = boton.getAttribute('data-open-url');
                    if (!targetUrl) {
                        return;
                    }
                    window.location.href = targetUrl;
                });
            });

            const btnModificar = document.getElementById('btnModificar');
            const formEditarPaciente = document.getElementById('formEditarPaciente');
            let modoEdicion = false;

            // Modal de observaciones
            const modalObservacion = document.getElementById('modalObservacion');
            const formObservacion = document.getElementById('formObservacion');
            const objetivoIdModal = document.getElementById('objetivoIdModal');
            const cerrarModalObservacion = document.getElementById('cerrarModalObservacion');
            const cancelarObservacion = document.getElementById('cancelarObservacion');

            document.querySelectorAll('.estado-objetivo').forEach((select) => {
                select.addEventListener('change', function () {
                    if (this.value === 'no_cumplido') {
                        objetivoIdModal.value = this.form.querySelector('[name="objetivo_id"]').value;
                        modalObservacion.classList.add('active');
                    }
                });
            });

            if (cerrarModalObservacion) {
                cerrarModalObservacion.addEventListener('click', function () {
                    modalObservacion.classList.remove('active');
                });
            }

            if (cancelarObservacion) {
                cancelarObservacion.addEventListener('click', function () {
                    modalObservacion.classList.remove('active');
                });
            }

            modalObservacion.addEventListener('click', function (e) {
                if (e.target === modalObservacion) {
                    modalObservacion.classList.remove('active');
                }
            });

            if (btnModificar && formEditarPaciente) {
                btnModificar.addEventListener('click', function () {
                    const campos = formEditarPaciente.querySelectorAll('input, select, textarea');

                    if (!modoEdicion) {
                        campos.forEach((campo) => {
                            if (campo.name === 'csrf_token' || campo.name === 'accion') {
                                return;
                            }
                            campo.disabled = false;
                        });
                        modoEdicion = true;
                        btnModificar.textContent = 'Guardar cambios';
                        return;
                    }

                    formEditarPaciente.submit();
                });
            }

            mostrarSeccion('<?= e($seccionActiva) ?>');

            // Validación de tipo de archivo para foto
            const tipoSelect = document.getElementById('tipoDocumentoSelect');
            const archivoInput = document.getElementById('archivoInput');
            if (tipoSelect && archivoInput) {
                tipoSelect.addEventListener('change', function () {
                    if (this.value === 'Foto') {
                        archivoInput.setAttribute('accept', 'image/*');
                    } else {
                        archivoInput.removeAttribute('accept');
                    }
                });
            }
        })();
    </script>

    <?= renderIframeNavButtons() ?>

    <!-- Modal para observaciones de objetivos -->
    <div class="modal-overlay" id="modalObservacion">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h3 class="modal-title">Observación</h3>
                <button class="modal-close" type="button" id="cerrarModalObservacion">&times;</button>
            </div>
            <form method="post" action="" id="formObservacion">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="accion" value="evaluar_objetivo">
                <input type="hidden" name="objetivo_id" id="objetivoIdModal">
                <input type="hidden" name="estado_objetivo" value="no_cumplido">
                <div class="field">
                    <label>¿Por qué no se cumplió el objetivo?</label>
                    <textarea name="observacion_objetivo" required placeholder="Describe las razones por las que no se cumplió el objetivo"></textarea>
                </div>
                <div class="actions">
                    <button class="btn btn-secondary" type="button" id="cancelarObservacion">Cancelar</button>
                    <button class="btn" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
