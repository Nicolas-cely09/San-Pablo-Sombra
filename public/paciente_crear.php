<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requerirAdministrador();

$db = Conexion::getConexion();
$adminController = new AdminController(new UserModel($db), new PacienteModel($db), new AsignacionModel($db));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!validarTokenCsrf($token)) {
        flash('error', 'Token CSRF invalido.');
        redirect('/Sanpablo/public/paciente_crear.php');
    }

    try {
        $adminController->crearPaciente($_POST, $_FILES);
        flash('ok', 'Paciente creado correctamente.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    redirect('/Sanpablo/public/paciente_crear.php');
}

$csrfToken = generarTokenCsrf();
$flash = obtenerFlash();
$profesionalesActivos = $adminController->listarProfesionalesActivos();
$tiposDocumentos = PacienteModel::tiposDocumentosPaciente();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear paciente | San Pablo</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

        :root {
            --blue-sp: #3984c6;
            --violet-sp: #8b3a8b;
            --text-main: #222222;
            --text-soft: #61708a;
            --line: #e0e0e0;
            --shadow: 0 14px 34px rgba(30, 54, 88, 0.12);
            --ok-bg: #e4f8d5;
            --ok-ink: #4a7b14;
            --danger-bg: #ffe6eb;
            --danger-ink: #9f2444;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--text-main);
            background: linear-gradient(145deg, #f4f7fb 0%, #e9eff7 100%);
            padding: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 14px;
        }

        h1 {
            margin: 0 0 12px;
            color: #24486f;
            font-size: 1.25rem;
        }

        .flash {
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .flash.ok { background: var(--ok-bg); color: var(--ok-ink); border: 1px solid #cce9b3; }
        .flash.error { background: var(--danger-bg); color: var(--danger-ink); border: 1px solid #ffc9d5; }

        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field.full { grid-column: 1 / -1; }
        .document-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; gap: 8px; align-items: end; margin-bottom: 8px; }
        .document-row .btn-remove { margin: 0; padding: 8px 10px; background: #ffe6eb; color: #9f2444; }
        .document-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }

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
            padding: 10px 14px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            background: linear-gradient(135deg, #3984c6, #8b3a8b);
            color: #fff;
        }

        @media (max-width: 900px) {
            body { padding: 10px; }
            .field-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?= renderToastFlash($flash) ?>

    <div class="card">
        <h1>Crear paciente</h1>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="field-grid">
                <div class="field"><label>Documento</label><input type="text" name="documento_identidad" required></div>
                <div class="field"><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento" required></div>
                <div class="field"><label>Nombre</label><input type="text" name="nombre" required></div>
                <div class="field"><label>Apellido</label><input type="text" name="apellido" required></div>
                <div class="field"><label>Nombre acudiente</label><input type="text" name="nombre_acudiente"></div>
                <div class="field"><label>Contacto acudiente</label><input type="text" name="contacto_acudiente"></div>
                <div class="field"><label>Colegio</label><input type="text" name="colegio"></div>
                <div class="field"><label>Tipo de discapacidad</label>
                    <select name="tipo_discapacidad" id="tipoDiscapacidad" required>
                        <option value="">Selecciona</option>
                        <option value="Intelectual">Intelectual</option>
                        <option value="Sensorial">Sensorial</option>
                        <option value="Física">Física</option>
                        <option value="Psicosocial">Psicosocial</option>
                        <option value="Múltiple">Múltiple</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="field" id="otraDiscapacidadField"><label>Otra discapacidad</label><input type="text" name="otra_discapacidad" id="otraDiscapacidad"></div>
                <div class="field"><label>Profesional asignado</label>
                    <select name="profesional_id" required>
                        <option value="">Selecciona profesional</option>
                        <?php foreach ($profesionalesActivos as $profesionalActivo): ?>
                            <option value="<?= e((string) $profesionalActivo['id']) ?>"><?= e($profesionalActivo['documento_identidad'] . ' - ' . $profesionalActivo['nombre'] . ' ' . $profesionalActivo['apellido']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Fecha inicio</label><input type="date" name="fecha_inicio"></div>
                <div class="field"><label>Fecha fin</label><input type="date" name="fecha_fin"></div>
                <div class="field"><label>Estado asignación</label>
                    <select name="estado_asignacion">
                        <option value="activo">Activo</option>
                        <option value="pausado">Pausado</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>
                <div class="field full"><label>Objetivos del plan</label><textarea name="objetivos_plan" placeholder="Objetivos terapéuticos y acompañamiento esperado"></textarea></div>
                <div class="field full"><label>Observaciones iniciales</label><textarea name="observaciones_iniciales"></textarea></div>
                <div class="field full">
                    <label>Documentos del paciente</label>
                    <div id="documentosContainer">
                        <div class="document-row">
                            <select name="documentos_tipo[]">
                                <option value="">Selecciona un tipo...</option>
                                <?php foreach ($tiposDocumentos as $tipoDocumento): ?>
                                    <option value="<?= e($tipoDocumento) ?>"><?= e($tipoDocumento) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="file" name="documentos_archivo[]" data-document-file>
                            <button class="btn btn-remove" type="button" data-remove-document style="display: none;">Quitar</button>
                        </div>
                    </div>
                    <div class="document-actions">
                        <button class="btn" type="button" id="addDocument">Añadir otro documento</button>
                    </div>
                </div>
            </div>
            <button class="btn" type="submit">Guardar paciente</button>
        </form>
    </div>

    <script>
        (function () {
            const tipoDiscapacidad = document.getElementById('tipoDiscapacidad');
            const otraDiscapacidad = document.getElementById('otraDiscapacidad');

            if (!tipoDiscapacidad || !otraDiscapacidad) {
                return;
            }

            function syncOtraDiscapacidad() {
                const esOtro = tipoDiscapacidad.value === 'Otro';
                otraDiscapacidad.required = esOtro;
                if (!esOtro) {
                    otraDiscapacidad.value = '';
                }
            }

            tipoDiscapacidad.addEventListener('change', syncOtraDiscapacidad);
            syncOtraDiscapacidad();

            const documentosContainer = document.getElementById('documentosContainer');
            const addDocument = document.getElementById('addDocument');
            if (documentosContainer && addDocument) {
                addDocument.addEventListener('click', function () {
                    const fila = documentosContainer.querySelector('.document-row').cloneNode(true);
                    fila.querySelector('select').value = '';
                    fila.querySelector('[data-document-file]').value = '';
                    fila.querySelector('[data-remove-document]').style.display = 'block';
                    documentosContainer.appendChild(fila);
                });

                documentosContainer.addEventListener('click', function (event) {
                    const boton = event.target.closest('[data-remove-document]');
                    if (boton) {
                        boton.closest('.document-row').remove();
                    }
                });
            }
        })();
    </script>

    <?= renderIframeNavButtons() ?>
</body>
</html>
