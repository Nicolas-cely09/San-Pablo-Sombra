# San Pablo - Plan Sombra (PHP Puro)

## Estructura recomendada

- BD/
  - init.sql
  - seeds/seed_inicial.sql
- config/
  - config.php
  - conexion.php
- core/
  - auth.php
  - helpers.php
- controllers/
  - AuthController.php
  - AdminController.php
  - ProfesionalController.php
- models/
  - UserModel.php
  - PacienteModel.php
- modules/
  - admin/
    - dashboard.php
  - profesional/
    - dashboard.php
- public/
  - index.php
  - login.php
  - dashboard.php
  - logout.php
- bootstrap.php

## Puesta en marcha

1. Crea la base de datos ejecutando BD/init.sql.
2. Si tu base ya existia desde antes, ejecuta BD/migrations/001_add_documento_identidad_usuarios.sql.
3. Ejecuta las migraciones restantes de BD/migrations en orden, incluida 004_add_campos_bitacora_diaria.sql.
4. Ejecuta BD/seeds/seed_inicial.sql para cargar roles, administrador, profesional y paciente de prueba.
5. Ajusta credenciales de base de datos en config/config.php si aplica.
6. Abre en navegador: /Sanpablo/public/login.php

## Credenciales iniciales

- Documento admin: 1069769579
- Documento profesional: 1069732158
- Contrasena: 123456789

Puedes ingresar usando el numero de documento del usuario.
