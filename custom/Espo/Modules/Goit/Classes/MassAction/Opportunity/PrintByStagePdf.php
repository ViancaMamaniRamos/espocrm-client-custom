<?php

namespace Espo\Modules\Goit\Classes\MassAction\Opportunity;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Htmlizer\TemplateRendererFactory;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\EntityManager;
use Espo\Tools\Pdf\Dompdf\DompdfInitializer;
use Espo\Tools\Pdf\Template as PdfTemplate;

class PrintByStagePdf implements MassAction
{
    private const ATTACHMENT_ROLE = 'Mass Pdf';

    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private FileStorageManager $fileStorageManager,
        private TemplateRendererFactory $templateRendererFactory,
        private DompdfInitializer $dompdfInitializer,
        private User $user
    ) {}

    public function process(Params $params, Data $data): Result
    {
        if (!$this->acl->checkScope(Opportunity::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        if (!$params->hasIds()) {
            throw new BadRequest('Debe seleccionar al menos una oportunidad.');
        }

        $idList = array_values(array_unique(array_filter($params->getIds())));

        if ($idList === []) {
            throw new BadRequest('Debe seleccionar al menos una oportunidad.');
        }

        $existingCount = $this->entityManager
            ->getRDBRepository(Opportunity::ENTITY_TYPE)
            ->where(['id' => $idList])
            ->count();

        if ($existingCount === 0) {
            throw new BadRequest('No se encontraron oportunidades seleccionadas.');
        }

        $pdfContents = $this->buildPdf($idList);
        $attachment = $this->storePdf($pdfContents);

        return new Result(1, [$attachment->getId()]);
    }

    /**
     * @param string[] $idList
     */
    private function buildPdf(array $idList): string
    {
        $printedAt = date('Y-m-d H:i:s');
        $printedBy = trim((string) ($this->user->get('name') ?? ''));

        if ($printedBy === '') {
            $printedBy = 'Sistema';
        }

        $template = <<<'HTML'
<div class="report-header">
    <h1>REPORTE DE OPORTUNIDADES</h1>
    <h2>Oportunidades separadas por etapa</h2>
    <div class="meta-wrap">
        <div class="meta-line"><strong>Fecha de impresion:</strong> {{printedAt}}</div>
        <div class="meta-line"><strong>Usuario:</strong> {{printedBy}}</div>
    </div>
</div>
{{{goitOpportunityStageTable selectedIds}}}
HTML;

        $body = $this->templateRendererFactory
            ->create()
            ->setData([
                'selectedIds' => $idList,
                'printedAt' => $printedAt,
                'printedBy' => $printedBy,
            ])
            ->renderTemplate($template);

        $pdfTemplate = new OpportunityStagePdfTemplate();
        $pdf = $this->dompdfInitializer->initialize($pdfTemplate);
        $pdf->loadHtml($this->wrapHtml($body));
        $pdf->render();

        return $pdf->output();
    }

    private function wrapHtml(string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' .
            'body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#1f2937;}' .
            'h1{font-size:22px;margin:0 0 4px 0;text-align:center;color:#7c3aed;}' .
            'h2{font-size:13px;margin:0 0 12px 0;text-align:center;font-weight:normal;color:#4b5563;}' .
            '.report-header{margin-bottom:20px;}' .
            '.meta-wrap{margin-top:0;padding-top:0;border-top:none;}' .
            '.meta-line{margin-bottom:4px;font-size:11px;color:#374151;}' .
            '.stage-title{margin:18px 0 8px 0;padding:8px 10px;background:#ede9fe;color:#6d28d9;font-size:14px;font-weight:bold;}' .
            'table{width:100%;border-collapse:collapse;margin-bottom:14px;}' .
            'th,td{border:1px solid #d1d5db;padding:6px 8px;text-align:left;vertical-align:top;}' .
            'th{background:#7c3aed;color:#ffffff;font-size:11px;}' .
            'tr:nth-child(even) td{background:#faf5ff;}' .
            '</style></head><body>' . $body . '</body></html>';
    }

    private function storePdf(string $contents): Attachment
    {
        /** @var Attachment $attachment */
        $attachment = $this->entityManager->getNewEntity(Attachment::ENTITY_TYPE);

        $attachment
            ->setName(Util::sanitizeFileName('Reporte de Oportunidades por Etapa') . '.pdf')
            ->setType('application/pdf')
            ->setRole(self::ATTACHMENT_ROLE)
            ->setSize(strlen($contents));

        $this->entityManager->saveEntity($attachment);
        $this->fileStorageManager->putContents($attachment, $contents);

        return $attachment;
    }
}

class OpportunityStagePdfTemplate implements PdfTemplate
{
    public function getFontFace(): ?string
    {
        return null;
    }

    public function getBottomMargin(): float
    {
        return 12.0;
    }

    public function getTopMargin(): float
    {
        return 12.0;
    }

    public function getLeftMargin(): float
    {
        return 10.0;
    }

    public function getRightMargin(): float
    {
        return 10.0;
    }

    public function hasFooter(): bool
    {
        return false;
    }

    public function getFooter(): string
    {
        return '';
    }

    public function getFooterPosition(): float
    {
        return 0.0;
    }

    public function hasHeader(): bool
    {
        return false;
    }

    public function getHeader(): string
    {
        return '';
    }

    public function getHeaderPosition(): float
    {
        return 0.0;
    }

    public function getBody(): string
    {
        return '';
    }

    public function getPageOrientation(): string
    {
        return self::PAGE_ORIENTATION_PORTRAIT;
    }

    public function getPageFormat(): string
    {
        return 'A4';
    }

    public function getPageWidth(): float
    {
        return 0.0;
    }

    public function getPageHeight(): float
    {
        return 0.0;
    }

    public function hasTitle(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return 'REPORTE DE OPORTUNIDADES';
    }

    public function getStyle(): ?string
    {
        return null;
    }
}
