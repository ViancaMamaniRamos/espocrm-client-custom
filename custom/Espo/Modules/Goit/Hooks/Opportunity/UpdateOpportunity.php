<?php

namespace Espo\Modules\Goit\Hooks\Opportunity;

use Espo\ORM\Entity;
use Espo\Core\ORM\EntityManager;

class UpdateOpportunity
{
    public function __construct(private EntityManager $entityManager)
    {}

    public function beforeSave(Entity $entity, array $options): void
    {
        if (!$entity->isAttributeChanged('probability')) {
            return;
        }

        $probability = (int) $entity->get('probability');

        if ($probability >= 10 && $probability <= 30) {
            $entity->set('description', 'Oportunidad en seguimiento inicial.');
            return;
        }

        if ($probability > 30 && $probability <= 60) {
            $task = $this->entityManager->createEntity('Task', [
                'name' => 'Seguimiento de oportunidad',
                'status' => 'Not Started',
                'assignedUserId' => '69ab9baeb85f265d4',
                'teamsIds' => ['69ab9acea9fe95a9e'],
            ]);

            $this->entityManager
                ->getRDBRepository('Opportunity')
                ->getRelation($entity, 'tasks')
                ->relateById($task->getId());

            $entity->set('description', 'Tarea enviada a ventas');
            return;
        }

        if ($probability > 60 && $probability <= 90) {
            $task = $this->entityManager->createEntity('Task', [
                'name' => 'Revisión administrativa',
                'status' => 'Not Started',
                'assignedUserId' => '69a5b51b59363244e',
                'teamsIds' => ['69ab9af0b7c36b567'],
            ]);

            $this->entityManager
                ->getRDBRepository('Opportunity')
                ->getRelation($entity, 'tasks')
                ->relateById($task->getId());
            $currentDescription = $entity->get('description'); 
            $entity->set('description', $currentDescription . "\nSe notificó al administrador de oportunidades"); 
           
 
        }
    }
}