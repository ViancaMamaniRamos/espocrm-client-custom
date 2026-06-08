<?php

namespace Espo\Modules\Goit\Hooks\Case;

use Espo\ORM\Entity;
use Espo\Core\ORM\EntityManager;

class UpdateCaseContacts
{
    public function __construct(private EntityManager $entityManager)
    {}

    public function beforeSave(Entity $entity, array $options): void
    {
        if (!$entity->isAttributeChanged('contactsIds')) {
            return;
        }

        $contactsIds = $entity->get('contactsIds');

        if (!$contactsIds || count($contactsIds) == 0) {
            return;
        }

        $ocupados = 0;
        $desocupados = 0;

        foreach ($contactsIds as $contactId) {
            $contact = $this->entityManager->getEntityById('Contact', $contactId);

            if (!$contact) {
                continue;
            }

            $status = $contact->get('cContactStatus');

            if ($status == 'ocupado') {
                $ocupados++;
            } elseif ($status == 'desocupado') {
                $desocupados++;
            }
        }

        if ($desocupados > $ocupados) {
            foreach ($contactsIds as $contactId) {
                $contact = $this->entityManager->getEntityById('Contact', $contactId);

                if (!$contact) {
                    continue;
                }

                if ($contact->get('cContactStatus') == 'desocupado') {
                    $contact->set('cContactStatus', 'ocupado');
                    $contact->set(
                        'description',
                        'Se le asignó el caso ' . $entity->getId() . ' y se está trabajando.'
                    );
                    $this->entityManager->saveEntity($contact);
                }
            }

            $entity->set(
                'description',
                'El caso puede continuar porque hay suficiente personal del cliente.'
            );

            return;
        }

        if ($desocupados < $ocupados) {
            foreach ($contactsIds as $contactId) {
                $contact = $this->entityManager->getEntityById('Contact', $contactId);

                if (!$contact) {
                    continue;
                }

                if ($contact->get('cContactStatus') == 'ocupado') {
                    $contact->set(
                        'description',
                        'No se puede continuar con el caso ' . $entity->getId() . ' por falta de personal desocupado.'
                    );
                    $this->entityManager->saveEntity($contact);
                }
            }

            $entity->set(
                'description',
                'Problemas con los contactos para continuar el caso.'
            );
        }
    }
}