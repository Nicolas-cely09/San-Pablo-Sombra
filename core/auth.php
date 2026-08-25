<?php

declare(strict_types=1);

function usuarioAutenticado(): bool
{
    return isset($_SESSION['usuario']) && is_array($_SESSION['usuario']);
}

function esAdministrador(): bool
{
    return usuarioAutenticado() && (int) ($_SESSION['usuario']['rol_id'] ?? 0) === 1;
}

function esProfesional(): bool
{
    return usuarioAutenticado() && (int) ($_SESSION['usuario']['rol_id'] ?? 0) === 2;
}

function requerirLogin(): void
{
    if (!usuarioAutenticado()) {
        redirect('/Sanpablo/public/login.php');
    }
}

function requerirAdministrador(): void
{
    requerirLogin();

    if (!esAdministrador()) {
        http_response_code(403);
        exit('No tienes permisos para acceder a esta seccion.');
    }
}

function requerirProfesional(): void
{
    requerirLogin();

    if (!esProfesional()) {
        http_response_code(403);
        exit('No tienes permisos para acceder a esta seccion.');
    }
}
