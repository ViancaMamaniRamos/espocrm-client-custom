<?php

namespace Espo\Modules\Goit\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\ORM\EntityManager;

class TestNimConnection implements Job
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function run(Data $data): void
    {
        $apiKey = getenv('NVIDIA_API_KEY');
        $baseUrl = getenv('NVIDIA_BASE_URL');
        $model = getenv('NVIDIA_MODEL');
        $GLOBALS['log']->error($apiKey. $baseUrl. $model);

        if (!$apiKey || !$model) {
            $this->createTask(
                'Error Configuración NVIDIA NIM',
                'Faltan variables de entorno NVIDIA_API_KEY o NVIDIA_MODEL.'
            );
            return;
        }

        $caseText = 'Cliente reporta retraso en su pedido y solicita una solución urgente.';
        $prompt = 'Analiza el siguiente caso de soporte y responde en espanol con tres secciones claramente identificadas: 1. Resumen del caso. 2. Nivel de urgencia. 3. Accion sugerida.'
            . PHP_EOL . PHP_EOL
            . 'Caso:' . PHP_EOL
            . $caseText;
        $url = rtrim($baseUrl, '/') . '/chat/completions';

        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->createTask(
                'Error respuesta NVIDIA NIM',
                'Código HTTP: ' . $httpCode . PHP_EOL . 'Respuesta: ' . $response
            );
            return;
        }

        $data = json_decode($response, true);
        $aiResponse = $data['choices'][0]['message']['content'] ?? 'No se pudo obtener el contenido de la respuesta.';

        $description = 'CASO ANALIZADO:' . PHP_EOL
            . $caseText . PHP_EOL . PHP_EOL
            . 'RESPUESTA DE IA:' . PHP_EOL
            . $aiResponse . PHP_EOL . PHP_EOL
            . 'GENERADO POR:' . PHP_EOL
            . 'EspoCRM + OpenCode + NVIDIA NIM';

        $this->createTask(
            'Resumen real generado con NVIDIA NIM',
            $description
        );
    }

    private function createTask($name, $description)
    {
        $task = $this->entityManager->getNewEntity('Task');
        $task->set([
            'name' => $name,
            'description' => $description,
            'status' => 'Not Started',
        ]);
        $this->entityManager->saveEntity($task);
    }
}
