<?php

declare(strict_types=1);

final class AuthController
{
    public function __construct(private UserModel $userModel)
    {
    }

    public function login(string $identificador, string $password): bool
    {
        $usuario = $this->userModel->buscarPorIdentificador($identificador);

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            return false;
        }

        if ((string) ($usuario['estado'] ?? '') !== 'activo') {
            throw new RuntimeException('USUARIO_INACTIVO');
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nombre_completo' => trim($usuario['nombre'] . ' ' . $usuario['apellido']),
            'email' => $usuario['email'],
            'rol_id' => (int) $usuario['rol_id'],
            'rol' => $usuario['rol_nombre'],
        ];

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}
