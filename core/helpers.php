<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function generarTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarTokenCsrf(?string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || $token === null) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'mensaje' => $mensaje,
    ];
}

function obtenerFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function renderToastFlash(?array $flash): string
{
    if ($flash === null) {
        return '';
    }

    $tipo = (string) ($flash['tipo'] ?? 'ok');
    $mensaje = e((string) ($flash['mensaje'] ?? ''));
    $toastClass = $tipo === 'ok' ? 'toast-success' : 'toast-error';

    return <<<HTML
<div class="sp-toast-wrap" id="spToastWrap" role="status" aria-live="polite">
    <div class="sp-toast {$toastClass}" id="spToastMessage">{$mensaje}</div>
</div>
<style>
    .sp-toast-wrap {
        position: fixed;
        top: 18px;
        right: 18px;
        z-index: 9999;
        pointer-events: none;
    }

    .sp-toast {
        min-width: 250px;
        max-width: min(92vw, 420px);
        padding: 11px 14px;
        border-radius: 10px;
        border: 1px solid transparent;
        box-shadow: 0 12px 28px rgba(23, 40, 65, 0.2);
        font-family: "Manrope", "Segoe UI", sans-serif;
        font-weight: 700;
        font-size: 0.92rem;
        line-height: 1.35;
        transform: translateX(115%);
        opacity: 0;
    }

    .sp-toast.toast-success {
        background: #e6f9d7;
        color: #335d0f;
        border-color: #cbeab0;
    }

    .sp-toast.toast-error {
        background: #ffe8ee;
        color: #91203f;
        border-color: #ffc9d8;
    }

    .sp-toast.sp-toast-in {
        animation: spToastIn 260ms ease-out forwards;
    }

    .sp-toast.sp-toast-out {
        animation: spToastOut 260ms ease-in forwards;
    }

    @keyframes spToastIn {
        from { transform: translateX(115%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes spToastOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(115%); opacity: 0; }
    }

    @media (max-width: 700px) {
        .sp-toast-wrap {
            top: 12px;
            right: 12px;
            left: 12px;
        }
        .sp-toast {
            max-width: none;
            min-width: 0;
        }
    }
</style>
<script>
    (function () {
        var toast = document.getElementById('spToastMessage');
        var wrap = document.getElementById('spToastWrap');

        if (!toast || !wrap) {
            return;
        }

        requestAnimationFrame(function () {
            toast.classList.add('sp-toast-in');
        });

        setTimeout(function () {
            toast.classList.remove('sp-toast-in');
            toast.classList.add('sp-toast-out');
        }, 3400);

        setTimeout(function () {
            if (wrap.parentNode) {
                wrap.parentNode.removeChild(wrap);
            }
        }, 3800);
    })();
</script>
HTML;
}

function renderIframeNavButtons(): string
{
    return <<<HTML
<div class="sp-iframe-nav" aria-label="Navegacion rapida">
    <button type="button" class="sp-iframe-nav-btn sp-iframe-nav-back" id="spIframeGoBack" title="Volver a la vista anterior" aria-label="Volver">
        &#8592;
    </button>
    <button type="button" class="sp-iframe-nav-btn sp-iframe-nav-home" id="spIframeGoDashboard" title="Ir al dashboard" aria-label="Dashboard">
        &#8962;
    </button>
</div>
<style>
    .sp-iframe-nav {
        position: fixed;
        right: 16px;
        bottom: 16px;
        z-index: 9998;
        display: grid;
        gap: 10px;
    }

    .sp-iframe-nav-btn {
        width: 46px;
        height: 46px;
        border: 0;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
        box-shadow: 0 12px 24px rgba(27, 49, 83, 0.28);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .sp-iframe-nav-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 26px rgba(27, 49, 83, 0.36);
    }

    .sp-iframe-nav-back {
        background: linear-gradient(135deg, #3b86c8, #57b7cf);
    }

    .sp-iframe-nav-home {
        background: linear-gradient(135deg, #3984c6, #8b3a8b);
    }

    @media (max-width: 700px) {
        .sp-iframe-nav {
            right: 12px;
            bottom: 12px;
            gap: 8px;
        }

        .sp-iframe-nav-btn {
            width: 42px;
            height: 42px;
        }
    }
</style>
<script>
    (function () {
        var btnBack = document.getElementById('spIframeGoBack');
        var btnDashboard = document.getElementById('spIframeGoDashboard');

        if (btnBack) {
            btnBack.addEventListener('click', function () {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }
                window.location.href = '/Sanpablo/public/dashboard.php';
            });
        }

        if (btnDashboard) {
            btnDashboard.addEventListener('click', function () {
                if (window.top && window.top !== window) {
                    window.top.location.href = '/Sanpablo/public/dashboard.php';
                    return;
                }
                window.location.href = '/Sanpablo/public/dashboard.php';
            });
        }
    })();
</script>
HTML;
}
