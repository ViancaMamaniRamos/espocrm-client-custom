<?php

namespace Espo\Modules\Goit\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\ORM\EntityManager;

class TestOpenCodeBuild implements Job
{
    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function run(Data $data): void
    {
        $task = $this->entityManager->getNewEntity('Task');

        $task->setMultiple([
            'name' => 'Prueba creada por OpenCode Build',
            'status' => 'Not Started',
            'description' => 'Esta tarea fue creada por un archivo generado desde OpenCode en modo Build.',
        ]);

        $this->entityManager->saveEntity($task);
    }
}
