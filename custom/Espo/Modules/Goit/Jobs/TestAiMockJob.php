<?php

namespace Espo\Modules\Goit\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\ORM\EntityManager;

class TestAiMockJob implements Job
{
    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function run(Data $data): void
    {
        $textoOriginal = 'Cliente reporta retraso en su pedido.';
        $respuestaSimulada = 'El cliente tiene un problema relacionado con retraso de pedido.';

        $task = $this->entityManager->getNewEntity('Task');

        $task->setMultiple([
            'name' => 'Resumen IA simulado desde OpenCode',
            'status' => 'Not Started',
            'description' => "Texto original: {$textoOriginal}\n\nRespuesta simulada IA: {$respuestaSimulada}\n\nModificación realizada por OpenCode:\nEste archivo fue editado por OpenCode en modo Build como evidencia de modificación funcional sobre un Job existente.",
        ]);

        $this->entityManager->saveEntity($task);
    }
}
