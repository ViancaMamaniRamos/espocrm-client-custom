<?php

namespace Espo\Modules\Goit\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\ORM\EntityManager;
use RuntimeException;
use stdClass;
use Throwable;

class PostHistorial implements Action
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function process(Request $request): Response
    {
        $payload = $this->normalizePayload($request->getParsedBody());

        $type = $this->getStringValue($payload, 'type');
        $entityType = $this->getStringValue($payload, 'entityType');
        $idRecord = $this->getStringValue($payload, 'idRecord');
        $field = $this->getStringValue($payload, 'field');
        $modProvided = property_exists($payload, 'mod');
        $mod = $modProvided ? $payload->mod : null;

        $validationError = $this->validatePayload($type, $entityType, $idRecord, $field, $modProvided);

        if ($validationError !== null) {
            $this->createHistorialRecord($payload, $type, $entityType, $idRecord, $field, $mod, false, $validationError);

            return ResponseComposer::json([
                'success' => false,
                'message' => $validationError,
            ]);
        }

        try {
            $entityDefs = $this->entityManager->getDefs()->tryGetEntity($entityType);

            if ($entityDefs === null) {
                throw new RuntimeException('El entityType no existe.');
            }

            if (!$entityDefs->hasField($field)) {
                throw new RuntimeException('El field indicado no existe en la entidad.');
            }

            $entity = $this->entityManager->getEntityById($entityType, $idRecord);

            if ($entity === null) {
                throw new RuntimeException('No se encontro el registro indicado.');
            }

            $value = $type === 'clear' ? '' : $mod;

            $entity->set($field, $value);
            $this->entityManager->saveEntity($entity);

            $this->createHistorialRecord($payload, $type, $entityType, $idRecord, $field, $value, true, 'acción realizada.');

            return ResponseComposer::json([
                'success' => true,
                'message' => 'acción realizada.',
            ]);
        } catch (Throwable $e) {
            $this->createHistorialRecord($payload, $type, $entityType, $idRecord, $field, $mod, false, $e->getMessage());

            return ResponseComposer::json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePayload(mixed $body): stdClass
    {
        if ($body instanceof stdClass) {
            return $body;
        }

        if (is_array($body)) {
            return (object) $body;
        }

        return new stdClass();
    }

    private function getStringValue(stdClass $payload, string $property): string
    {
        if (!property_exists($payload, $property)) {
            return '';
        }

        $value = $payload->{$property};

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function validatePayload(
        string $type,
        string $entityType,
        string $idRecord,
        string $field,
        bool $modProvided
    ): ?string {
        if (!in_array($type, ['update', 'clear'], true)) {
            return 'type solo puede ser update o clear.';
        }

        if ($entityType === '' || $idRecord === '' || $field === '') {
            return 'entityType, idRecord y field son obligatorios.';
        }

        if ($type === 'update' && !$modProvided) {
            return 'mod es obligatorio cuando type es update.';
        }

        return null;
    }

    private function createHistorialRecord(
        stdClass $payload,
        string $type,
        string $entityType,
        string $idRecord,
        string $field,
        mixed $mod,
        bool $success,
        string $message
    ): void {
        $historial = $this->entityManager->getNewEntity('Historial');

        $historial->set('name', $type !== '' ? 'API Historial ' . $type : 'API Historial');
        $historial->set('requestType', $type);
        $historial->set('targetEntityType', $entityType);
        $historial->set('targetRecordId', $idRecord);
        $historial->set('targetField', $field);
        $historial->set('modValue', $this->stringifyValue($mod));
        $historial->set('payload', json_decode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}', true));
        $historial->set('success', $success);
        $historial->set('message', $message);

        $this->entityManager->saveEntity($historial);
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encodedValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encodedValue === false ? '' : $encodedValue;
    }
}
