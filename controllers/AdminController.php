<?php

declare(strict_types=1);

final class AdminController
{
    public function __construct(
        private UserModel $userModel,
        private PacienteModel $pacienteModel,
        private AsignacionModel $asignacionModel
    ) {
    }

    public function crearProfesional(array $input, array $files = []): void
    {
        $documentoIdentidad = trim($input['documento_identidad'] ?? '');
        $nombre = trim($input['nombre'] ?? '');
        $apellido = trim($input['apellido'] ?? '');
        $email = trim($input['email'] ?? '');
        $telefono = trim($input['telefono'] ?? '');
        $password = (string) ($input['password'] ?? '');

        if ($documentoIdentidad === '' || $nombre === '' || $apellido === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Todos los campos obligatorios del profesional deben ser completados.');
        }

        if (!preg_match('/^[0-9A-Za-z-]{5,30}$/', $documentoIdentidad)) {
            throw new InvalidArgumentException('El numero de documento no tiene un formato valido.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo del profesional no es valido.');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('La contrasena debe tener al menos 8 caracteres.');
        }

        $this->userModel->crearProfesional([
            'documento_identidad' => $documentoIdentidad,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'telefono' => $telefono,
            'password' => $password,
        ]);

        $profesional = $this->userModel->buscarPorIdentificador($documentoIdentidad);
        if ($profesional !== null && isset($files['fotografia_profesional'])) {
            $this->userModel->guardarAdjuntoProfesional((int) $profesional['id'], $files['fotografia_profesional'], 'Foto');
        }
    }

    public function actualizarProfesional(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $documentoIdentidad = trim($input['documento_identidad'] ?? '');
        $nombre = trim($input['nombre'] ?? '');
        $apellido = trim($input['apellido'] ?? '');
        $email = trim($input['email'] ?? '');
        $telefono = trim($input['telefono'] ?? '');
        $estado = trim($input['estado'] ?? 'activo');

        if ($id <= 0 || $documentoIdentidad === '' || $nombre === '' || $apellido === '' || $email === '') {
            throw new InvalidArgumentException('Datos incompletos para actualizar profesional.');
        }

        if (!preg_match('/^[0-9A-Za-z-]{5,30}$/', $documentoIdentidad)) {
            throw new InvalidArgumentException('El numero de documento del profesional no es valido.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo del profesional no es valido.');
        }

        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('Estado de profesional no permitido.');
        }

        $this->userModel->actualizarProfesional([
            'id' => $id,
            'documento_identidad' => $documentoIdentidad,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'telefono' => $telefono,
            'estado' => $estado,
        ]);
    }

    public function crearPaciente(array $input, array $files = []): void
    {
        $documentoIdentidad = trim($input['documento_identidad'] ?? '');
        $nombre = trim($input['nombre'] ?? '');
        $apellido = trim($input['apellido'] ?? '');
        $fechaNacimiento = trim($input['fecha_nacimiento'] ?? '');
        $tipoDiscapacidad = trim((string) ($input['tipo_discapacidad'] ?? ''));
        $otraDiscapacidad = trim((string) ($input['otra_discapacidad'] ?? ''));
        $profesionalId = (int) ($input['profesional_id'] ?? 0);
        $fechaInicio = trim((string) ($input['fecha_inicio'] ?? ''));

        if ($documentoIdentidad === '' || $nombre === '' || $apellido === '' || $fechaNacimiento === '') {
            throw new InvalidArgumentException('Documento, nombre, apellido y fecha de nacimiento son obligatorios para paciente.');
        }

        if (!preg_match('/^[0-9A-Za-z-]{5,30}$/', $documentoIdentidad)) {
            throw new InvalidArgumentException('El numero de documento del paciente no es valido.');
        }

        $tiposPermitidos = ['Intelectual', 'Sensorial', 'Física', 'Psicosocial', 'Múltiple', 'Otro'];
        if ($tipoDiscapacidad === '' || !in_array($tipoDiscapacidad, $tiposPermitidos, true)) {
            throw new InvalidArgumentException('Debe seleccionar un tipo de discapacidad valido.');
        }

        if ($tipoDiscapacidad === 'Otro' && $otraDiscapacidad === '') {
            throw new InvalidArgumentException('Debe describir la otra discapacidad.');
        }

        if ($profesionalId <= 0) {
            throw new InvalidArgumentException('Debe asignar un profesional al crear el paciente.');
        }

        $pacienteId = $this->pacienteModel->crear([
            'documento_identidad' => $documentoIdentidad,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'fecha_nacimiento' => $fechaNacimiento,
            'nombre_acudiente' => trim($input['nombre_acudiente'] ?? ''),
            'contacto_acudiente' => trim($input['contacto_acudiente'] ?? ''),
            'observaciones_iniciales' => trim($input['observaciones_iniciales'] ?? ''),
            'objetivo_general' => trim((string) ($input['objetivos_plan'] ?? '')),
            'estado' => trim($input['estado'] ?? 'activo'),
            'tipo_discapacidad' => $tipoDiscapacidad,
            'otra_discapacidad' => $otraDiscapacidad,
            'colegio' => trim((string) ($input['colegio'] ?? '')),
        ]);

        $this->asignacionModel->crear([
            'paciente_id' => $pacienteId,
            'profesional_id' => $profesionalId,
            'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : date('Y-m-d'),
            'fecha_fin' => trim((string) ($input['fecha_fin'] ?? '')),
            'estado' => trim($input['estado_asignacion'] ?? 'activo'),
            'objetivos_plan' => trim((string) ($input['objetivos_plan'] ?? '')),
        ]);

        $tiposDocumentos = $input['documentos_tipo'] ?? [];
        $archivos = $files['documentos_archivo'] ?? [];
        if (!is_array($tiposDocumentos) || !is_array($archivos)) {
            throw new InvalidArgumentException('La información de documentos no es válida.');
        }

        foreach ($tiposDocumentos as $indice => $tipo) {
            $tipo = trim((string) $tipo);
            $archivo = $archivos['tmp_name'][$indice] ?? null;
            $error = (int) ($archivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE);

            if ($tipo === '' && $error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (!in_array($tipo, PacienteModel::tiposDocumentosPaciente(), true)) {
                throw new InvalidArgumentException('El tipo de documento seleccionado no es válido.');
            }
            if ($error !== UPLOAD_ERR_OK || !isset($archivo)) {
                throw new InvalidArgumentException('Cada documento debe tener un archivo válido.');
            }

            $archivoIndividual = [
                'name' => $archivos['name'][$indice] ?? '',
                'type' => $archivos['type'][$indice] ?? '',
                'tmp_name' => $archivo,
                'error' => $error,
                'size' => $archivos['size'][$indice] ?? 0,
            ];
            $this->pacienteModel->guardarAdjunto($pacienteId, $archivoIndividual, $tipo);
        }
    }

    public function actualizarPaciente(array $input): void
    {
        $id = (int) ($input['id'] ?? 0);
        $documentoIdentidad = trim($input['documento_identidad'] ?? '');
        $nombre = trim($input['nombre'] ?? '');
        $apellido = trim($input['apellido'] ?? '');
        $fechaNacimiento = trim($input['fecha_nacimiento'] ?? '');
        $estado = trim($input['estado'] ?? 'activo');
        $tipoDiscapacidad = trim((string) ($input['tipo_discapacidad'] ?? ''));
        $otraDiscapacidad = trim((string) ($input['otra_discapacidad'] ?? ''));

        if ($id <= 0 || $documentoIdentidad === '' || $nombre === '' || $apellido === '' || $fechaNacimiento === '') {
            throw new InvalidArgumentException('Datos incompletos para actualizar paciente.');
        }

        $tiposPermitidos = ['Intelectual', 'Sensorial', 'Física', 'Psicosocial', 'Múltiple', 'Otro'];
        if ($tipoDiscapacidad === '' || !in_array($tipoDiscapacidad, $tiposPermitidos, true)) {
            throw new InvalidArgumentException('Debe seleccionar un tipo de discapacidad valido.');
        }

        if ($tipoDiscapacidad === 'Otro' && $otraDiscapacidad === '') {
            throw new InvalidArgumentException('Debe describir la otra discapacidad.');
        }

        if (!in_array($estado, ['activo', 'inactivo', 'finalizado'], true)) {
            throw new InvalidArgumentException('Estado del paciente no permitido.');
        }

        $this->pacienteModel->actualizar([
            'id' => $id,
            'documento_identidad' => $documentoIdentidad,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'fecha_nacimiento' => $fechaNacimiento,
            'nombre_acudiente' => trim($input['nombre_acudiente'] ?? ''),
            'contacto_acudiente' => trim($input['contacto_acudiente'] ?? ''),
            'observaciones_iniciales' => trim($input['observaciones_iniciales'] ?? ''),
            'estado' => $estado,
            'tipo_discapacidad' => $tipoDiscapacidad,
            'otra_discapacidad' => $otraDiscapacidad,
            'colegio' => trim((string) ($input['colegio'] ?? '')),
        ]);
    }

    public function crearAsignacion(array $input): void
    {
        $pacienteId = (int) ($input['paciente_id'] ?? 0);
        $profesionalId = (int) ($input['profesional_id'] ?? 0);
        $fechaInicio = trim($input['fecha_inicio'] ?? '');

        if ($pacienteId <= 0 || $profesionalId <= 0 || $fechaInicio === '') {
            throw new InvalidArgumentException('Paciente, profesional y fecha de inicio son obligatorios para asignar.');
        }

        $this->asignacionModel->crear([
            'paciente_id' => $pacienteId,
            'profesional_id' => $profesionalId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => trim($input['fecha_fin'] ?? ''),
            'estado' => trim($input['estado'] ?? 'activo'),
            'objetivos_plan' => trim($input['objetivos_plan'] ?? ''),
        ]);
    }

    public function crearPacientePrueba(): void
    {
        $this->pacienteModel->crearPacientePrueba();
    }

    public function listarProfesionales(): array
    {
        return $this->userModel->listarProfesionales();
    }

    public function listarPacientes(): array
    {
        return $this->pacienteModel->listar();
    }

    public function listarPacientesActivos(): array
    {
        return $this->pacienteModel->listarActivos();
    }

    public function listarProfesionalesActivos(): array
    {
        return $this->userModel->listarProfesionalesActivos();
    }

    public function listarAsignaciones(): array
    {
        return $this->asignacionModel->listar();
    }
}
