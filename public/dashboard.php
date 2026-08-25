<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requerirLogin();

if (esAdministrador()) {
    require_once dirname(__DIR__) . '/modules/admin/dashboard.php';
    exit;
}

if (esProfesional()) {
    require_once dirname(__DIR__) . '/modules/profesional/dashboard.php';
    exit;
}

http_response_code(403);
exit('No tienes permisos para acceder al panel.');
