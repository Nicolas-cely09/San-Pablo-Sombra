<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (usuarioAutenticado()) {
    redirect('/Sanpablo/public/dashboard.php');
}

$db = Conexion::getConexion();
$authController = new AuthController(new UserModel($db));
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!validarTokenCsrf($token)) {
        $error = 'Token CSRF invalido. Recarga la pagina e intenta de nuevo.';
    } else {
        $identificador = trim($_POST['identificador'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        try {
            if ($authController->login($identificador, $password)) {
                redirect('/Sanpablo/public/dashboard.php');
            }

            $error = 'Credenciales invalidas.';
        } catch (Throwable $e) {
            if ($e->getMessage() === 'USUARIO_INACTIVO') {
                $error = 'Usuario inactivo. Solicita al administrador activar la cuenta para poder ingresar.';
            } else {
                $error = 'No fue posible completar el inicio de sesion. Verifica la base de datos y ejecuta el seed inicial.';
            }
        }
    }
}

$csrfToken = generarTokenCsrf();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | San Pablo</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap');

        :root {
            --blue-sp: #3984c6;
            --violet-sp: #8b3a8b;
            --magenta-sp: #e21b79;
            --lime-sp: #a3d133;
        }

        body { font-family: "Manrope", "Segoe UI", sans-serif; background: linear-gradient(145deg, #f4f7fb, #e9eff7); margin: 0; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { width: 100%; max-width: 460px; background: rgba(255,255,255,.92); border: 1px solid #e2e9f2; border-radius: 16px; padding: 24px; box-shadow: 0 14px 34px rgba(30, 54, 88, .12); }
        .brand-pill { border-radius: 999px; border: 1px solid #ecf1f7; padding: 10px 14px; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 8px 18px rgba(54, 72, 97, 0.08); margin-bottom: 8px; background: #fff; }
        .brand-logo { width: 100%; max-width: 260px; height: auto; display: block; }
        .subtitle { text-align: center; margin: 6px 0 18px; font-size: 11px; color: #222222; letter-spacing: .09em; text-transform: uppercase; font-weight: 700; }
        .card-title { margin: 0 0 4px; color: #24486f; text-align: center; }
        .card-subtitle { margin: 0 0 14px; color: #61708a; font-size: 13px; text-align: center; }
        label { display: block; margin: 10px 0 6px; color: #243b53; font-size: 14px; }
        input { width: 100%; padding: 10px; border: 1px solid #bcccdc; border-radius: 8px; box-sizing: border-box; }
        button { margin-top: 16px; width: 100%; border: 0; border-radius: 8px; padding: 12px; background: var(--blue-sp); color: #fff; font-weight: 700; cursor: pointer; }
        .error { background: #ffe3e3; color: #9b2226; border: 1px solid #ffc9c9; padding: 10px; border-radius: 8px; margin-bottom: 12px; }
        .hint { font-size: 13px; color: #486581; margin-top: 12px; }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <div class="brand-pill" aria-hidden="true">
                <img class="brand-logo" src="/Sanpablo/public/assets/logo-sanpablo-horizontal.svg" alt="San Pablo">
            </div>
            <p class="subtitle">Centro Terapeutico Integral</p>
            <h2 class="card-title">Ingreso al sistema</h2>
            <p class="card-subtitle">Acceso administrativo y profesionales sombra</p>
            <?php if ($error !== null): ?>
                <div class="error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <label for="identificador">Numero de documento</label>
                <input type="text" id="identificador" name="identificador" placeholder="Ej: 1069769579" required>

                <label for="password">Contrasena</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Ingresar</button>
            </form>

            <p class="hint">Admin: 1069769579 | Profesional: 1069732158 | Contrasena: 123456789</p>
        </section>
    </main>
</body>
</html>
