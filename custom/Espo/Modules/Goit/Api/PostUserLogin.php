<?php

namespace Espo\Modules\Goit\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\ORM\EntityManager;

class PostUserLogin implements Action
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

        if ($body->password) {
            $password = $body->password;
        } else {
            $password = '';
        }

        if ($userValue == '' || $password == '') {
            return ResponseComposer::json([
                'success' => false,
                'message' => 'Debe enviar User y password.'
            ]);
        }

        if (str_contains($userValue, '@')) {
            $field = 'emailAddress';
        } elseif(str_contains($userValue), '+'){
           $field = 'phoneNumber';
        }else{
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
        $token = 'token_prueba';

        $task = $this->entityManager->getNewEntity('Task');

        $task->set('name', 'Inicio de sesión por API');
        $task->set('status', 'Not Started');
        $task->set('description', 'El usuario inició sesión correctamente desde la API.');
        $task->set('assignedUserId', $user->getId());

        $this->entityManager->saveEntity($task);

        return ResponseComposer::json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'taskId' => $task->getId()
        ]);
    }
}