<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requerirLogin();

$db = Conexion::getConexion();
$userModel = new UserModel($db);
$usuarioSesionId = (int) ($_SESSION['usuario']['id'] ?? 0);
$esAdmin = esAdministrador();
$profesionalId = $esAdmin ? (int) ($_GET['id'] ?? $_POST['id'] ?? 0) : $usuarioSesionId;

if ($profesionalId <= 0 || (!$esAdmin && $profesionalId !== $usuarioSesionId)) {
    http_response_code(403);
    exit('No tienes permiso para ver este perfil.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token CSRF invalido.');
        redirect('/Sanpablo/public/profesional_detalle.php' . ($esAdmin ? '?id=' . $profesionalId : ''));
    }

    try {
        $accion = trim((string) ($_POST['accion'] ?? ''));

        if ($accion === 'actualizar_profesional') {
            $estado = trim((string) ($_POST['estado'] ?? 'activo'));
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                throw new InvalidArgumentException('Estado no permitido.');
            }

            $userModel->actualizarProfesional([
                'id' => $profesionalId,
                'documento_identidad' => trim((string) ($_POST['documento_identidad'] ?? '')),
                'nombre' => trim((string) ($_POST['nombre'] ?? '')),
                'apellido' => trim((string) ($_POST['apellido'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'telefono' => trim((string) ($_POST['telefono'] ?? '')),
                'estado' => $esAdmin ? $estado : (string) ($_SESSION['usuario']['estado'] ?? 'activo'),
            ]);
            flash('ok', 'Informacion del profesional actualizada correctamente.');
        } elseif ($accion === 'subir_adjunto') {
            $tipo = trim((string) ($_POST['tipo_adjunto'] ?? ''));
            $archivo = $_FILES['documento_adjunto'] ?? null;
            if ($tipo === '' || !is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Selecciona un tipo y un archivo valido.');
            }
            $userModel->guardarAdjuntoProfesional($profesionalId, $archivo, $tipo);
            flash('ok', 'Documento cargado correctamente.');
        } else {
            throw new InvalidArgumentException('Accion no soportada.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    redirect('/Sanpablo/public/profesional_detalle.php' . ($esAdmin ? '?id=' . $profesionalId : ''));
}

$profesional = $userModel->obtenerProfesionalPorId($profesionalId);
if (!$profesional) {
    http_response_code(404);
    exit('Profesional no encontrado.');
}

$adjuntos = $userModel->listarAdjuntosProfesional($profesionalId);
$fotoProfesional = null;
foreach ($adjuntos as $adjunto) {
    if (str_contains(strtolower((string) ($adjunto['tipo'] ?? '')), 'foto')) {
        $fotoProfesional = (string) $adjunto['ruta_archivo'];
        break;
    }
}

$flash = obtenerFlash();
$csrfToken = generarTokenCsrf();
$baseQuery = $esAdmin ? '?id=' . $profesionalId : '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esAdmin ? 'Detalle profesional' : 'Mi perfil' ?> | San Pablo</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');
        :root { --blue: #3984c6; --violet: #8b3a8b; --ink: #222; --soft: #61708a; --line: #dfe8f3; --shadow: 0 14px 34px rgba(30,54,88,.12); }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 12px; color: var(--ink); font-family: "Manrope", "Segoe UI", sans-serif; background: linear-gradient(145deg,#f4f7fb,#e9eff7); }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 14px; margin-bottom: 10px; box-shadow: var(--shadow); }
        h1, h2 { margin-top: 0; color: #24486f; } h1 { margin-bottom: 12px; font-size: clamp(1.45rem,2.4vw,2rem); } h2 { font-size: 1.2rem; }
        .flash { padding: 10px 12px; margin-bottom: 10px; border-radius: 8px; font-weight: 700; } .flash.ok { background:#e4f8d5; color:#4a7b14; } .flash.error { background:#ffe6eb; color:#9f2444; }
        .info-layout { display: grid; grid-template-columns: 200px 1fr; gap: 14px; align-items: start; }
        .foto-wrap { min-height: 200px; border: 1px solid #e2ebf8; border-radius: 12px; background:#f7faff; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .foto-wrap img { width:100%; height:100%; object-fit:cover; display:block; } .foto-placeholder { padding:12px; color:var(--soft); text-align:center; font-size:.86rem; }
        .field-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; } .field { display:flex; flex-direction:column; gap:4px; } .field.full { grid-column:1/-1; }
        label { color:#4b6383; font-size:.8rem; font-weight:700; } input, select, textarea { width:100%; border:1px solid #cddbeb; border-radius:8px; padding:8px 10px; font:inherit; color:var(--ink); background:#fff; } input[disabled], select[disabled] { background:#f7f9fd; color:#51627d; }
        .btn { border:0; border-radius:8px; padding:9px 12px; color:#fff; background:linear-gradient(135deg,var(--blue),var(--violet)); font:inherit; font-weight:700; cursor:pointer; transition:transform .18s ease,box-shadow .18s ease,filter .18s ease; } .btn:hover { transform:translateY(-1px); box-shadow:0 8px 18px rgba(57,132,198,.2); filter:saturate(1.08); } .btn:focus-visible { outline:3px solid #d91b72; outline-offset:3px; } .btn-secondary { background:linear-gradient(135deg,var(--blue),#54b4ce); }
        .actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; } .section-panel { display:none; } .section-panel.active { display:block; }
        table { width:100%; border-collapse:collapse; } th,td { padding:8px; border-bottom:1px solid #edf2fb; text-align:left; vertical-align:top; } th { background:#f3f7fd; color:#50627c; }
        a { color:var(--blue); }
        input:focus, select:focus, textarea:focus { border-color:var(--blue); outline:0; box-shadow:0 0 0 3px rgba(57,132,198,.16); }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition-duration:.01ms !important; animation-duration:.01ms !important; } }
        @media (max-width:920px) { body { padding:8px; } .info-layout,.field-grid { grid-template-columns:1fr; } .actions .btn { width:100%; } }
    </style>
</head>
<body>
    <?= renderToastFlash($flash) ?>
    <div class="card">
        <h1><?= e($esAdmin ? 'Detalle de ' . $profesional['nombre'] . ' ' . $profesional['apellido'] : 'Mi perfil') ?></h1>
        <div class="info-layout">
            <div class="foto-wrap">
                <?php if ($fotoProfesional !== null): ?><img src="<?= e($fotoProfesional) ?>" alt="Fotografia del profesional">
                <?php else: ?><div class="foto-placeholder">Sin fotografia cargada</div><?php endif; ?>
            </div>
            <form method="post" action="<?= e($baseQuery) ?>" id="formPerfil">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="accion" value="actualizar_profesional"><input type="hidden" name="id" value="<?= e((string) $profesionalId) ?>">
                <div class="field-grid">
                    <div class="field"><label>Documento</label><input name="documento_identidad" value="<?= e((string) $profesional['documento_identidad']) ?>" disabled required></div>
                    <div class="field"><label>Correo</label><input type="email" name="email" value="<?= e((string) $profesional['email']) ?>" disabled required></div>
                    <div class="field"><label>Nombre</label><input name="nombre" value="<?= e((string) $profesional['nombre']) ?>" disabled required></div>
                    <div class="field"><label>Apellido</label><input name="apellido" value="<?= e((string) $profesional['apellido']) ?>" disabled required></div>
                    <div class="field"><label>Telefono</label><input name="telefono" value="<?= e((string) $profesional['telefono']) ?>" disabled></div>
                    <div class="field"><label>Estado</label><select name="estado" <?= $esAdmin ? 'disabled' : 'disabled' ?>><option value="activo" <?= $profesional['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= $profesional['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select></div>
                </div>
            </form>
        </div>
    </div>
    <?php if ($esAdmin): ?>
        <div class="card section-panel" id="panelDocumentos">
            <h2>Documentos adjuntos</h2>
            <?php if (count($adjuntos) === 0): ?><p>No hay documentos cargados.</p><?php else: ?><ul><?php foreach ($adjuntos as $adjunto): ?><li><strong><?= e((string) $adjunto['tipo']) ?>:</strong> <a href="<?= e((string) $adjunto['ruta_archivo']) ?>" target="_blank"><?= e((string) $adjunto['nombre_original']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
            <form method="post" action="<?= e($baseQuery) ?>" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="accion" value="subir_adjunto"><div class="field-grid"><div class="field"><label>Tipo de documento</label><select name="tipo_adjunto" id="tipoDocumentoSelect" required><option value="">Selecciona un tipo...</option><?php foreach (UserModel::tiposDocumentosProfesional() as $tipoDocumento): ?><option value="<?= e($tipoDocumento) ?>"><?= e($tipoDocumento) ?></option><?php endforeach; ?></select></div><div class="field"><label>Archivo</label><input type="file" name="documento_adjunto" id="archivoInput" required></div></div><button class="btn" type="submit">Cargar documento</button></form>
        </div>
    <?php endif; ?>
    <div class="card"><div class="actions"><?php if ($esAdmin): ?><button class="btn btn-secondary" type="button" id="btnModificar">Modificar</button><?php endif; ?><?php if ($esAdmin): ?><button class="btn btn-secondary" type="button" id="btnDocumentos" aria-expanded="false">Consultar documentos</button><?php endif; ?></div></div>
    <script>
        (function () {
            const form = document.getElementById('formPerfil');
            const modificar = document.getElementById('btnModificar');
            const documentos = document.getElementById('panelDocumentos');
            const btnDocumentos = document.getElementById('btnDocumentos');
            const tipoSelect = document.getElementById('tipoDocumentoSelect');
            const archivoInput = document.getElementById('archivoInput');
            let editando = false;
            if (modificar) {
                modificar.addEventListener('click', function () {
                    const campos = form.querySelectorAll('input:not([type="hidden"]), select');
                    if (!editando) { campos.forEach((campo) => { campo.disabled = false; }); editando = true; modificar.textContent = 'Guardar cambios'; return; }
                    form.submit();
                });
            }
            if (btnDocumentos && documentos) {
                btnDocumentos.addEventListener('click', function () {
                    const abierto = documentos.classList.toggle('active');
                    btnDocumentos.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                    btnDocumentos.textContent = abierto ? 'Cerrar documentos' : 'Consultar documentos';
                });
        }   
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
</body>
</html>
