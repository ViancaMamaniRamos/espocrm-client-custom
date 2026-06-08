<?php

namespace Espo\Modules\Goit\Hooks\Case;

use Espo\ORM\Entity;
use Espo\Core\ORM\EntityManager;

class BackendDemo
{
    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, array $options): void
    {
        // $task = $this->entityManager->getNewEntity('Task');

        // $task->setMultiple([
        //     'name' => 'Revisar caso desde backend',
        //     'status' => 'Not Started',
        //     'description' => 'Esta tarea fue creada usando setMultiple.',

        //     // RELACIÓN
        //     'parentType' => 'Case',
        //     'parentId' => $entity->getId(),
        // ]);

        // $this->entityManager->saveEntity($task);
        $this->entityManager
            ->getRelation($entity, 'tasks')
            ->unrelateById('6a0372c6763010147');
    }
}