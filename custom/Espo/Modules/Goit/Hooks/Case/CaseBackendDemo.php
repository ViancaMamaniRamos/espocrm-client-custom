<?php

namespace Espo\Modules\Goit\Hooks\Case;

use Espo\ORM\Entity;

class CaseBackendDemo
{
    public function beforeSave(Entity $entity, array $options): void
    {
        if ($entity->isAttributeChanged('status')) {

            $entity->set(
                'description',
                'El estado fue modificado.'
            );
        }
    }
}


