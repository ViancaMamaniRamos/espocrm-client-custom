<?php

namespace Espo\Custom\TemplateHelpers;

use Espo\Core\Htmlizer\Helper;
use Espo\Core\Htmlizer\Helper\Data;
use Espo\Core\Htmlizer\Helper\Result;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\ORM\EntityManager;

class CaseAccountStatusReport implements Helper
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function render(Data $data): Result
    {
        $cases = $this->entityManager
            ->getRDBRepository(CaseObj::ENTITY_TYPE)
            ->find();

        $accountStats = [];
        $globalStats = ['Inicio' => 0, 'Proceso' => 0, 'Finalizado' => 0];

        foreach ($cases as $case) {
            $status = $case->get('status') ?? '';
            $group = match ($status) {
                'New', 'Assigned' => 'Inicio',
                'Pending' => 'Proceso',
                'Closed', 'Rejected', 'Duplicate' => 'Finalizado',
                default => 'Inicio',
            };
            $globalStats[$group]++;

            $accountId = $case->get('accountId') ?: '__no_account__';
            $accountName = $case->get('accountName') ?: 'Sin Cuenta';

            if (!isset($accountStats[$accountId])) {
                $accountStats[$accountId] = [
                    'name' => $accountName,
                    'Inicio' => 0,
                    'Proceso' => 0,
                    'Finalizado' => 0,
                    'total' => 0,
                ];
            }
            $accountStats[$accountId][$group]++;
            $accountStats[$accountId]['total']++;
        }

        uasort($accountStats, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $parts = [];

        $parts[] = '<table style="width:100%;margin-bottom:20px;border-collapse:collapse;"><tr>';
        $parts[] = '<td style="text-align:center;padding:15px;background:#3498db;color:#fff;font-size:18px;font-weight:bold;border-radius:5px;width:33%;">Inicio: ' . $globalStats['Inicio'] . '</td>';
        $parts[] = '<td style="text-align:center;padding:15px;background:#f39c12;color:#fff;font-size:18px;font-weight:bold;border-radius:5px;width:33%;">Proceso: ' . $globalStats['Proceso'] . '</td>';
        $parts[] = '<td style="text-align:center;padding:15px;background:#27ae60;color:#fff;font-size:18px;font-weight:bold;border-radius:5px;width:33%;">Finalizado: ' . $globalStats['Finalizado'] . '</td>';
        $parts[] = '</tr></table>';

        $parts[] = '<table style="width:100%;border-collapse:collapse;border:1px solid #ccc;font-size:11px;">';
        $parts[] = '<thead><tr style="background:#e8e8e8;">';
        $parts[] = '<th style="border:1px solid #ccc;padding:6px;text-align:left;">Cuenta</th>';
        $parts[] = '<th style="border:1px solid #ccc;padding:6px;text-align:center;">Inicio</th>';
        $parts[] = '<th style="border:1px solid #ccc;padding:6px;text-align:center;">Proceso</th>';
        $parts[] = '<th style="border:1px solid #ccc;padding:6px;text-align:center;">Finalizado</th>';
        $parts[] = '<th style="border:1px solid #ccc;padding:6px;text-align:center;">Total</th>';
        $parts[] = '</tr></thead><tbody>';

        foreach ($accountStats as $a) {
            $name = htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8');
            $parts[] = '<tr>';
            $parts[] = '<td style="border:1px solid #ccc;padding:6px;">' . $name . '</td>';
            $parts[] = '<td style="border:1px solid #ccc;padding:6px;text-align:center;">' . $a['Inicio'] . '</td>';
            $parts[] = '<td style="border:1px solid #ccc;padding:6px;text-align:center;">' . $a['Proceso'] . '</td>';
            $parts[] = '<td style="border:1px solid #ccc;padding:6px;text-align:center;">' . $a['Finalizado'] . '</td>';
            $parts[] = '<td style="border:1px solid #ccc;padding:6px;text-align:center;font-weight:bold;">' . $a['total'] . '</td>';
            $parts[] = '</tr>';
        }

        $parts[] = '</tbody></table>';

        return Result::createSafeString(implode('', $parts));
    }
}
