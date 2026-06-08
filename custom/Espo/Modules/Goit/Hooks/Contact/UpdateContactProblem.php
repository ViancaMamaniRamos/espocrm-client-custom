<?php

namespace Espo\Modules\Goit\Hooks\Contact;

use Espo\ORM\Entity;
use Espo\Core\ORM\EntityManager;

class UpdateContactProblem
{
    public function __construct(private EntityManager $entityManager)
    {}

    public function afterSave(Entity $entity, array $options): void
    {
        if (!$entity->get('cContactsProblem')) {
            return;
        }

        $contactId = $entity->getId();
        $email = $entity->get('emailAddress');

        if (!$email) {
            return;
        }

        $parts = explode('@', $email);

        if (count($parts) < 2) {
            return;
        }

        $domain = $parts[1];

        // Buscar team soporte
        $team = $this->entityManager
            ->getRDBRepository('Team')
            ->where([
                'name' => 'soporte'
            ])
            ->findOne();

        if (!$team) {
            return;
        }

        // Buscar usuarios del team soporte
        $users = $this->entityManager
            ->getRDBRepository('Team')
            ->getRelation($team, 'users')
            ->find();

        $assignedUserId = null;

        foreach ($users as $user) {
            if ($user->get('isActive') == false) {
                continue;
            }

            $cases = $this->entityManager
                ->getRDBRepository('Case')
                ->where([
                    'assignedUserId' => $user->getId()
                ])
                ->find();

            if (count($cases) < 2) {
                $assignedUserId = $user->getId();
                break;
            }
        }

        if (!$assignedUserId) {
            return;
        }

        // Crear case
        $case = $this->entityManager->createEntity('Case', [
            'name' => 'Problema con ' . $domain,
            'status' => 'New',
            'priority' => 'Normal',
            'assignedUserId' => $assignedUserId,
            'teamId' => $team->getId(),
            'accountId' => $entity->get('accountId'),
            'description' => 'Caso creado automáticamente desde contacto con problemas.'
        ]);

        // Relacionar contacto al case
        $this->entityManager
            ->getRDBRepository('Case')
            ->getRelation($case, 'contacts')
            ->relateById($contactId);

        // Guardar descripción del contacto
            $entity->set('description', 'Caso creado exitoso.');
            $this->entityManager->saveEntity($entity);
        
    }
}