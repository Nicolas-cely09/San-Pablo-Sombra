<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (usuarioAutenticado() && (esAdministrador() || esProfesional())) {
    redirect('/Sanpablo/public/dashboard.php');
}

redirect('/Sanpablo/public/login.php');
