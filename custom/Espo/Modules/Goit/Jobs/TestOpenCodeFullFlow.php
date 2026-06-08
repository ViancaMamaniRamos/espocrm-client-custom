<?php

namespace Espo\Modules\Goit\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\ORM\EntityManager;

class TestOpenCodeFullFlow implements Job
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function run(Data $data): void
    {
        $task = $this->entityManager->getNewEntity('Task');
        $task->set([
            'name' => 'Prueba completa OpenCode create modify execute',
            'status' => 'Not Started',
            'description' => 'Esta tarea fue creada por OpenCode dentro del contenedor, ejecutando el flujo completo de creación, modificación y prueba.',
        ]);

        $this->entityManager->saveEntity($task);
    }
}
