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

        :root { --brand: #2877b2; --brand-dark: #1d4f78; --accent: #d91b72; --ink: #172b3a; --muted: #526579; --line: #d6e0e8; --page: #f4f7fa; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; color: var(--ink); font-family: "Manrope", "Segoe UI", sans-serif; background: radial-gradient(circle at 12% 10%, rgba(217,27,114,.12), transparent 30%), linear-gradient(140deg, #eef5f8, var(--page)); }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { width: 100%; max-width: 440px; padding: 34px; background: rgba(255,255,255,.94); border: 1px solid rgba(214,224,232,.9); border-radius: 14px; box-shadow: 0 24px 60px rgba(29,79,120,.14); }
        .brand-mark { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 26px; color: var(--brand-dark); font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .brand-mark::before { width: 9px; height: 9px; border-radius: 50%; background: var(--accent); content: ""; }
        .brand-logo { display: block; width: min(100%, 250px); height: auto; margin-bottom: 12px; }
        .subtitle { margin: 0 0 28px; color: var(--muted); font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .card-title { margin: 0 0 7px; color: var(--brand-dark); font-size: 1.7rem; letter-spacing: 0; }
        .card-subtitle { margin: 0 0 22px; color: var(--muted); font-size: .9rem; }
        label { display: block; margin: 16px 0 7px; color: var(--ink); font-size: .84rem; font-weight: 800; }
        input { width: 100%; padding: 12px 13px; border: 1px solid var(--line); border-radius: 8px; color: var(--ink); background: #fff; font: inherit; transition: border-color .18s, box-shadow .18s; }
        input:focus { border-color: var(--brand); outline: 0; box-shadow: 0 0 0 3px rgba(40,119,178,.18); }
        button { width: 100%; margin-top: 24px; padding: 13px 16px; border: 0; border-radius: 8px; background: var(--brand); color: #fff; font: inherit; font-weight: 800; cursor: pointer; transition: background .18s, transform .18s; }
        button:hover { background: var(--brand-dark); transform: translateY(-1px); }
        button:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
        .error { display: flex; gap: 9px; align-items: flex-start; margin-bottom: 14px; padding: 12px 13px; border: 1px solid #f2b8b5; border-radius: 8px; background: #fff1f0; color: #8e211b; font-size: .88rem; line-height: 1.4; }
        .error::before { content: "!"; display: grid; place-items: center; flex: 0 0 20px; width: 20px; height: 20px; border-radius: 50%; background: #b42318; color: #fff; font-size: .75rem; font-weight: 800; }
        .hint { margin: 18px 0 0; color: var(--muted); font-size: .78rem; line-height: 1.5; }
        @media (max-width: 520px) { .wrap { padding: 14px; } .card { padding: 24px 20px; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; } }
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
