<?php

namespace Espo\Modules\Goit\TemplateHelpers;

use Espo\Core\Acl;
use Espo\Core\Htmlizer\Helper;
use Espo\Core\Htmlizer\Helper\Data;
use Espo\Core\Htmlizer\Helper\Result;
use Espo\Core\Utils\Language;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\EntityManager;

class OpportunityStageTable implements Helper
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private Language $language
    ) {}

    public function render(Data $data): Result
    {
        $idList = $this->normalizeIdList($data->getArgumentList()[0] ?? []);

        if ($idList === []) {
            return Result::createEmpty();
        }

        $groupedData = $this->getGroupedData($idList);

        if ($groupedData === []) {
            return Result::createEmpty();
        }

        return Result::createSafeString($this->buildHtml($groupedData));
    }

    /**
     * @return string[]
     */
    private function normalizeIdList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $idList = array_values(array_filter(array_map(
            static fn (mixed $id): string => is_scalar($id) ? trim((string) $id) : '',
            $value
        )));

        return array_values(array_unique($idList));
    }

    /**
     * @param string[] $idList
     * @return array<string, array{label: string, rows: array<int, array<string, string>>}>
     */
    private function getGroupedData(array $idList): array
    {
        $collection = $this->entityManager
            ->getRDBRepository(Opportunity::ENTITY_TYPE)
            ->where(['id' => $idList])
            ->find();

        $groupedData = [];

        foreach ($collection as $opportunity) {
            if (!$this->acl->checkEntityRead($opportunity)) {
                continue;
            }

            $stageValue = (string) ($opportunity->get('stage') ?? '');
            $stageKey = $stageValue !== '' ? $stageValue : '__empty__';
            $stageLabel = $stageValue !== '' ? $this->language->translateOption($stageValue, 'stage', Opportunity::ENTITY_TYPE) : 'Sin etapa';

            if (!isset($groupedData[$stageKey])) {
                $groupedData[$stageKey] = [
                    'label' => $stageLabel,
                    'rows' => [],
                ];
            }

            $groupedData[$stageKey]['rows'][] = [
                'name' => (string) ($opportunity->get('name') ?? ''),
                'account' => (string) ($opportunity->get('accountName') ?? ''),
                'amount' => $this->formatAmount($opportunity),
                'assignedUser' => (string) ($opportunity->get('assignedUserName') ?? ''),
                'stage' => $stageLabel,
            ];
        }

        uasort($groupedData, static function (array $a, array $b): int {
            return strcasecmp($a['label'], $b['label']);
        });

        return $groupedData;
    }

    private function formatAmount(Opportunity $opportunity): string
    {
        $amount = $opportunity->get('amount');

        if ($amount === null || $amount === '') {
            return '';
        }

        $amountString = is_numeric($amount) ? number_format((float) $amount, 2, '.', ',') : (string) $amount;
        $currency = (string) ($opportunity->get('amountCurrency') ?? '');

        return trim($amountString . ' ' . $currency);
    }

    /**
     * @param array<string, array{label: string, rows: array<int, array<string, string>>}> $groupedData
     */
    private function buildHtml(array $groupedData): string
    {
        $html = '';

        foreach ($groupedData as $stageData) {
            $html .= '<div class="stage-title">Etapa: ' . $this->escape($stageData['label']) . '</div>';
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th>Nombre de oportunidad</th>';
            $html .= '<th>Cuenta</th>';
            $html .= '<th>Monto</th>';
            $html .= '<th>Usuario asignado</th>';
            $html .= '<th>Etapa</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($stageData['rows'] as $row) {
                $html .= '<tr>';
                $html .= '<td>' . $this->escape($row['name']) . '</td>';
                $html .= '<td>' . $this->escape($row['account']) . '</td>';
                $html .= '<td>' . $this->escape($row['amount']) . '</td>';
                $html .= '<td>' . $this->escape($row['assignedUser']) . '</td>';
                $html .= '<td>' . $this->escape($row['stage']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        return $html;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
