<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$db = Conexion::getConexion();
$authController = new AuthController(new UserModel($db));
$authController->logout();

redirect('/Sanpablo/public/login.php');
