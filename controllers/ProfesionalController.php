<?php

declare(strict_types=1);

final class ProfesionalController
{
    public function __construct(private AsignacionModel $asignacionModel)
    {
    }

    public function listarPacientesAsignados(int $profesionalId): array
    {
        return $this->asignacionModel->listarPorProfesional($profesionalId);
    }

    public function listarInformes(int $profesionalId): array
    {
        return $this->asignacionModel->listarInformesPorProfesional($profesionalId);
    }

    public function crearInforme(int $profesionalId, array $input): void
    {
        $resumen = trim((string) ($input['resumen_jornada'] ?? ''));

        if ($resumen === '') {
            throw new InvalidArgumentException('El resumen de jornada es obligatorio para crear el informe.');
        }

        $this->asignacionModel->crearInforme($profesionalId, [
            'asignacion_id' => (int) ($input['asignacion_id'] ?? 0),
            'resumen_jornada' => $resumen,
            'comportamiento_observado' => trim((string) ($input['comportamiento_observado'] ?? '')),
            'novedades_alertas' => trim((string) ($input['novedades_alertas'] ?? '')),
        ]);
    }
}
