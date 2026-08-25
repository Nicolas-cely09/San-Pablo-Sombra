<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/config/conexion.php';

require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/PacienteModel.php';
require_once __DIR__ . '/models/AsignacionModel.php';

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/ProfesionalController.php';
