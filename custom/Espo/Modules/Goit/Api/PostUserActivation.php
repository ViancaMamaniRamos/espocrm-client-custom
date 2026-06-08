<?php

namespace Espo\Modules\Goit\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\ORM\EntityManager;

class PostUserActivation implements Action
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function process(Request $request): Response
    {
        $body = $request->getParsedBody();

        if ($body->User) {
            $userValue = $body->User;
        } else {
            $userValue = '';
        }

        if ($body->active == true || $body->active == false) {
            $active = $body->active;
        } else {
            $active = null;
        }

        if ($userValue == '') {
            return ResponseComposer::json([
                'success' => false,
                'message' => 'Debe enviar User.'
            ]);
        }

        if (is_bool($active) == false) {
            return ResponseComposer::json([
                'success' => false,
                'message' => 'Debe enviar active como true o false.'
            ]);
        }
        if (str_contains($userValue, '@')) {
            $field = 'emailAddress';
        } else {
            $field = 'userName';
        }

        $users = $this->entityManager
            ->getRDBRepository('User')
            ->where([
                $field => $userValue
            ])
            ->find();

        if (count($users) == 0) {
            return ResponseComposer::json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ]);
        }

        $user = $users[0];

        if ($user->get('userName') == 'admin' && $active == false) {
            return ResponseComposer::json([
                'success' => false,
                'message' => 'No se permite desactivar al usuario administrador.'
            ]);
        }

        $user->set('isActive', $active);

        $this->entityManager->saveEntity($user);

        if ($active == true) {
            $taskName = 'Activación de usuario por API';
            $taskDescription = 'El usuario fue activado correctamente desde la API.';
            $message = 'Usuario activado exitosamente.';
        } else {
            $taskName = 'Desactivación de usuario por API';
            $taskDescription = 'El usuario fue desactivado correctamente desde la API.';
            $message = 'Usuario desactivado exitosamente.';
        }


        $task = $this->entityManager->getNewEntity('Task');

        $task->set('name', $taskName);
        $task->set('status', 'Not Started');
        $task->set('description', $taskDescription);
        $task->set('assignedUserId', $user->getId());

        $this->entityManager->saveEntity($task);

        return ResponseComposer::json([
            'success' => true,
            'message' => $message,
            'taskId' => $task->getId()
        ]);
    }
}