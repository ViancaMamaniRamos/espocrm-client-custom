<?php

namespace Espo\Custom\TemplateHelpers;

use Espo\Core\Htmlizer\Helper;
use Espo\Core\Htmlizer\Helper\Data;
use Espo\Core\Htmlizer\Helper\Result;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class ContactsRelationsReport implements Helper
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function render(Data $data): Result
    {
        $contacts = $this->entityManager
            ->getRDBRepository('Contact')
            ->order([
                ['lastName', 'ASC'],
                ['firstName', 'ASC'],
                ['id', 'ASC'],
            ])
            ->find();

        $blocks = [];
        $count = 0;

        foreach ($contacts as $contact) {
            $count++;
            $blocks[] = $this->buildContactBlock($contact);
        }

        $html = '<style>' . $this->getStyle() . '</style>';
        $html .= '<div class="contacts-relations-report">';
        $html .= '<h1>Reporte de Contactos</h1>';
        $html .= '<div class="report-subtitle">Total de contactos: ' . $count . '</div>';

        if ($blocks === []) {
            $html .= '<div class="empty-main">No se encontraron contactos.</div>';
        } else {
            $html .= implode('', $blocks);
        }

        $html .= '</div>';

        return Result::createSafeString($html);
    }

    private function buildContactBlock(Entity $contact): string
    {
        $email = (string) ($contact->get('emailAddress') ?? '');
        $phone = (string) ($contact->get('phoneNumber') ?? '');
        $account = (string) ($contact->get('accountName') ?? '');

        $html = '<div class="contact-block">';
        $html .= '<div class="contact-header">' . $this->escape($this->getEntityName($contact)) . '</div>';
        $html .= '<div class="contact-summary">';
        $html .= '<span><strong>Email:</strong> ' . $this->escape($email !== '' ? $email : '-') . '</span>';
        $html .= '<span><strong>Telefono:</strong> ' . $this->escape($phone !== '' ? $phone : '-') . '</span>';
        $html .= '<span><strong>Account principal:</strong> ' . $this->escape($account !== '' ? $account : '-') . '</span>';
        $html .= '</div>';
        $html .= $this->buildAccountsSection($contact);
        $html .= $this->buildOpportunitiesSection($contact);
        $html .= $this->buildCasesSection($contact);
        $html .= '</div>';

        return $html;
    }

    private function buildAccountsSection(Entity $contact): string
    {
        $accounts = $this->entityManager
            ->getRDBRepository('Contact')
            ->getRelation($contact, 'accounts')
            ->order('name', 'ASC')
            ->find();

        $rows = [];

        foreach ($accounts as $account) {
            $rows[] = [
                $this->getEntityName($account),
                (string) ($account->get('type') ?? ''),
                (string) ($account->get('website') ?? ''),
            ];
        }

        return $this->buildTable('Accounts', ['Nombre', 'Tipo', 'Sitio web'], $rows);
    }

    private function buildOpportunitiesSection(Entity $contact): string
    {
        $opportunities = $this->entityManager
            ->getRDBRepository('Contact')
            ->getRelation($contact, 'opportunities')
            ->order('name', 'ASC')
            ->find();

        $rows = [];

        foreach ($opportunities as $opportunity) {
            $rows[] = [
                $this->getEntityName($opportunity),
                (string) ($opportunity->get('stage') ?? ''),
                (string) ($opportunity->get('accountName') ?? ''),
                $this->formatAmount($opportunity),
            ];
        }

        return $this->buildTable('Opportunities', ['Nombre', 'Etapa', 'Account', 'Monto'], $rows);
    }

    private function buildCasesSection(Entity $contact): string
    {
        $cases = $this->entityManager
            ->getRDBRepository('Contact')
            ->getRelation($contact, 'cases')
            ->order('name', 'ASC')
            ->find();

        $rows = [];

        foreach ($cases as $case) {
            $rows[] = [
                $this->getEntityName($case),
                (string) ($case->get('status') ?? ''),
                (string) ($case->get('priority') ?? ''),
                (string) ($case->get('accountName') ?? ''),
            ];
        }

        return $this->buildTable('Cases', ['Nombre', 'Estado', 'Prioridad', 'Account'], $rows);
    }

    /**
     * @param string[] $headers
     * @param array<int, string[]> $rows
     */
    private function buildTable(string $title, array $headers, array $rows): string
    {
        $html = '<div class="section-title">' . $this->escape($title) . ' (' . count($rows) . ')</div>';

        if ($rows === []) {
            return $html . '<div class="empty-section">Sin registros relacionados.</div>';
        }

        $html .= '<div class="relation-list">';

        foreach ($rows as $row) {
            $html .= '<div class="relation-row">';

            foreach ($row as $index => $value) {
                $label = $headers[$index] ?? '';
                $html .= '<div class="relation-cell"><strong>' . $this->escape($label) . ':</strong> ' .
                    $this->escape($value !== '' ? $value : '-') . '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function getEntityName(Entity $entity): string
    {
        $name = trim((string) ($entity->get('name') ?? ''));

        return $name !== '' ? $name : $entity->getId();
    }

    private function formatAmount(Entity $opportunity): string
    {
        $amount = $opportunity->get('amount');

        if ($amount === null || $amount === '') {
            return '';
        }

        $amountString = is_numeric($amount) ? number_format((float) $amount, 2, '.', ',') : (string) $amount;
        $currency = (string) ($opportunity->get('amountCurrency') ?? '');

        return trim($amountString . ' ' . $currency);
    }

    private function getStyle(): string
    {
        return implode('', [
            '.contacts-relations-report{font-family:DejaVu Sans,sans-serif;font-size:10.5px;color:#1f2937;}',
            '.contacts-relations-report h1{margin:0 0 4px;font-size:22px;text-align:center;color:#0f766e;}',
            '.report-subtitle{margin:0 0 18px;text-align:center;color:#475569;font-size:11px;}',
            '.empty-main{padding:14px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;color:#64748b;}',
            '.contact-block{margin:0 0 16px;border:1px solid #cbd5e1;background:#ffffff;}',
            '.contact-header{padding:8px 10px;background:#0f766e;color:#ffffff;font-size:14px;font-weight:bold;}',
            '.contact-summary{padding:7px 9px;background:#f8fafc;border-bottom:1px solid #cbd5e1;color:#334155;}',
            '.contact-summary span{display:inline-block;width:32%;vertical-align:top;}',
            '.section-title{padding:8px 9px 4px;font-weight:bold;color:#0f766e;}',
            '.empty-section{padding:0 9px 9px;color:#64748b;font-style:italic;}',
            '.relation-list{margin:0 9px 10px;border-top:1px solid #d1d5db;border-left:1px solid #d1d5db;}',
            '.relation-row{border-bottom:1px solid #d1d5db;background:#ffffff;}',
            '.relation-row:nth-child(even){background:#f8fafc;}',
            '.relation-cell{display:inline-block;width:24%;padding:5px 0 5px 6px;vertical-align:top;border-right:1px solid #d1d5db;}',
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
