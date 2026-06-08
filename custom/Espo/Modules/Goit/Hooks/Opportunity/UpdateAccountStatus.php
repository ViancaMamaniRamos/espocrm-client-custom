<?php

namespace Espo\Modules\Goit\Hooks\Opportunity;

use Espo\ORM\Entity;
use Espo\Core\ORM\EntityManager;

class UpdateAccountStatus
{
    public function __construct(private EntityManager $entityManager)
    {}

    public function beforeSave(Entity $entity, array $options): void
    {
        if (!$entity->isAttributeChanged('stage') &&
            !$entity->isAttributeChanged('accountId')
        ) {
            return;
        }

        $accountId = $entity->get('accountId');

        if (!$accountId) {
            return;
        }

        $opportunityList = $this->entityManager
            ->getRDBRepository('Opportunity')
            ->where([
                'accountId' => $accountId
            ])
            ->find();

        $won = 0;
        $lost = 0;
        $proposal = 0;

        foreach ($opportunityList as $opportunity) {

            if ($opportunity->getId() == $entity->getId()) {
                continue;
            }

            $stage = $opportunity->get('stage');

            if ($stage == 'Closed Won') {
                $won++;
            }

            if ($stage == 'Closed Lost') {
                $lost++;
            }

            if ($stage == 'Proposal') {
                $proposal++;
            }
        }

        $currentStage = $entity->get('stage');

        if ($currentStage == 'Closed Won') {
            $won++;
        }

        if ($currentStage == 'Closed Lost') {
            $lost++;
        }

        if ($currentStage == 'Proposal') {
            $proposal++;
        }

        $status = null;
        $accountDescription = null;

        if ($lost > $won) {
            $status = 'muerto';
        } elseif ($lost == $won && $lost > 0) {
            $status = 'riesgo';
        } elseif ($proposal > $won && $proposal > $lost) {
            $status = 'profundizar';
            $accountDescription = 'Hay que profundizar en este cliente.';
        } elseif ($won > $lost) {
            $status = 'sano';
        }

        if (!$status) {
            return;
        }

        $account = $this->entityManager->getEntityById('Account', $accountId);

        if (!$account) {
            return;
        }

        $account->set('cClientStatus', $status);

        if ($accountDescription) {
            $account->set('description', $accountDescription);
        }

        $this->entityManager->saveEntity($account);
    }
}