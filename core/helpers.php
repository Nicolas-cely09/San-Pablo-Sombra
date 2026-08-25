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
    $toastIcon = $tipo === 'ok' ? '&#10003;' : '!';

    return <<<HTML
<div class="sp-toast-wrap" id="spToastWrap" role="status" aria-live="polite">
    <div class="sp-toast {$toastClass}" id="spToastMessage">
        <span class="sp-toast-icon" aria-hidden="true">{$toastIcon}</span>
        <span class="sp-toast-copy">{$mensaje}</span>
        <button class="sp-toast-close" id="spToastClose" type="button" aria-label="Cerrar notificacion">&times;</button>
    </div>
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
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 280px;
        max-width: min(92vw, 420px);
        padding: 13px 12px 13px 14px;
        border-radius: 12px;
        border: 1px solid transparent;
        box-shadow: 0 12px 28px rgba(23, 40, 65, 0.2);
        font-family: "Manrope", "Segoe UI", sans-serif;
        font-weight: 700;
        font-size: 0.9rem;
        line-height: 1.35;
        transform: translateX(115%);
        opacity: 0;
    }

    .sp-toast-icon {
        display: grid;
        place-items: center;
        flex: 0 0 22px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        color: #fff;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .toast-success .sp-toast-icon { background: #287a45; }
    .toast-error .sp-toast-icon { background: #b42318; }
    .sp-toast-copy { flex: 1; }
    .sp-toast-close {
        flex: 0 0 24px;
        width: 24px;
        height: 24px;
        margin: -3px -2px 0 0;
        padding: 0;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: currentColor;
        cursor: pointer;
        font-size: 1.2rem;
        line-height: 1;
    }
    .sp-toast-close:hover { background: rgba(23, 43, 58, 0.1); }
    .sp-toast-close:focus-visible { outline: 3px solid #172b3a; outline-offset: 2px; }

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
        var close = document.getElementById('spToastClose');

        if (!toast || !wrap) {
            return;
        }

        function dismiss() {
            toast.classList.remove('sp-toast-in');
            toast.classList.add('sp-toast-out');
            setTimeout(function () {
                if (wrap.parentNode) {
                    wrap.parentNode.removeChild(wrap);
                }
            }, 280);
        }

        if (close) {
            close.addEventListener('click', dismiss);
        }

        requestAnimationFrame(function () {
            toast.classList.add('sp-toast-in');
        });

        setTimeout(function () {
            dismiss();
        }, 5000);
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

    .sp-iframe-nav-btn:focus-visible {
        outline: 3px solid #d91b72;
        outline-offset: 3px;
    }

    @media (prefers-reduced-motion: reduce) {
        .sp-iframe-nav-btn { transition: none; }
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
