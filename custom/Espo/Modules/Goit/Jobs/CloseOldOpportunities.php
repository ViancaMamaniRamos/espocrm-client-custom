<?php

namespace Espo\Modules\Goit\Jobs;

use Espo\Core\ORM\EntityManager;

class CloseOldOpportunities
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function run(): void
    {
        $limitDate = (new \DateTime('-30 days'))->format('Y-m-d H:i:s');
        $limitDate2 = (new \DateTime('-45 days'))->format('Y-m-d H:i:s');
        $GLOBALS['log']->error( $limitDate);
        $opportunities = $this->entityManager
            ->getRDBRepository('Opportunity')
            ->where([
                'createdAt<' => $limitDate,
                'createdAt>'  => $limitDate2
            ])
            ->find();

        foreach ($opportunities as $opportunity) {

            if ($opportunity->get('stage') == 'Prospecting') {

                $opportunity->set('stage', 'Closed Lost');

                $call = $this->entityManager->getNewEntity('Call');

                $call->setMultiple([
                    'name' => 'Comunicar cierre del negocio',
                    'status' => 'Planned',
                    'assignedUserId' => $opportunity->get('assignedUserId'),
                    'parentType' => 'Opportunity',
                    'parentId' => $opportunity->getId(),
                    'description' => 'Debe comunicar al cliente que el negocio se cerró.'
                ]);

                $this->entityManager->saveEntity($opportunity);

                $this->entityManager->saveEntity($call);
            }
        }
    }
}