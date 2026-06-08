<?php

namespace Espo\Modules\Goit\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\ORM\EntityManager;

class TestOpenCodeHealth implements Job
{
    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function run(Data $data): void
    {
        $task = $this->entityManager->getNewEntity('Task');

        $task->setMultiple([
            'name' => 'Prueba OpenCode Health',
            'status' => 'Not Started',
            'description' => 'Esta tarea fue creada para comprobar que OpenCode puede modificar EspoCRM correctamente.',
        ]);

        $this->entityManager->saveEntity($task);
    }
}