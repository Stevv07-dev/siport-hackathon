<?php

namespace App\Services\Compliance;

use App\Contracts\ComplianceEngine;
use App\Exceptions\ComplianceEngineException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Calls the Google Gemini Interactions API (generativelanguage.googleapis.com/v1beta2/interactions)
 * to generate the questionnaire and the compliance verdict. Enabled by
 * setting COMPLIANCE_ENGINE=gemini and GEMINI_API_KEY in .env — see
 * DEPLOYMENT.md.
 *
 * Note: Google retired the older `models/{model}:generateContent` endpoint
 * in favor of this Interactions API (see
 * https://ai.google.dev/gemini-api/docs/migrate-to-interactions) — new API
 * keys are no longer granted access to the old endpoint.
 *
 * Uses `response_format` (JSON-Schema structured output) rather than
 * free-text generation, so the model's reply is always parseable JSON
 * matching a fixed shape — this is also what keeps answer OPTIONS
 * constrained to whatever the model puts in the schema-validated "options"
 * array, instead of the user being able to type anything: the questionnaire
 * UI only ever renders fixed choices sourced from this response, never a
 * free-text field.
 */
class GeminiComplianceEngine implements ComplianceEngine
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta2/interactions';

    public function generateQuestionnaire(string $categoryKey, string $categoryName, string $material): array
    {
        $schema = [
            'type' => 'object',
            'required' => ['questions'],
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 8,
                    'items' => [
                        'type' => 'object',
                        'required' => ['key', 'label', 'type'],
                        'properties' => [
                            'key' => ['type' => 'string', 'description' => 'snake_case identifier, unique within the list'],
                            'label' => ['type' => 'string', 'description' => 'The question shown to the exporter'],
                            'type' => ['type' => 'string', 'enum' => ['boolean', 'select']],
                            'options' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Required when type is "select": the fixed list of selectable answers. The exporter can only pick one of these — never free text.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $prompt = "Product category: {$categoryName} ({$categoryKey})\nMaterial: {$material}\n\n".
            'List the technical compliance questions an Indonesian exporter should answer before '.
            'shipping this electronics product to Singapore, so it can be checked for export readiness. '.
            'Prefer yes/no ("boolean") questions; use "select" only when there are 3-5 concrete standard '.
            'options (e.g. a rating or range). Every question must be answerable from a fixed set of '.
            'choices — never a question that expects free-form text or an open number. Keep it to 3-6 questions.';

        $data = $this->call($prompt, $schema);

        if (! isset($data['questions']) || ! is_array($data['questions'])) {
            throw new ComplianceEngineException('Gemini response was missing a "questions" array.');
        }

        return ['questions' => $data['questions']];
    }

    public function evaluate(string $categoryKey, string $categoryName, string $material, array $questions, array $answers): array
    {
        $schema = [
            'type' => 'object',
            'required' => ['verdict', 'summary', 'issues'],
            'properties' => [
                'verdict' => ['type' => 'string', 'enum' => ['pass', 'fail']],
                'summary' => ['type' => 'string', 'description' => 'One or two sentences explaining the verdict.'],
                'issues' => [
                    'type' => 'array',
                    'description' => 'Empty when verdict is "pass".',
                    'items' => [
                        'type' => 'object',
                        'required' => ['title', 'detail'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'detail' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $qa = collect($questions)->map(function (array $question) use ($answers) {
            $answer = $answers[$question['key']] ?? '(not answered)';

            return "- {$question['label']} → {$answer}";
        })->implode("\n");

        $prompt = "Product category: {$categoryName} ({$categoryKey})\nMaterial: {$material}\n\n".
            "Questionnaire and answers:\n{$qa}\n\n".
            'Decide whether this product is ready for export to Singapore based on these answers. '.
            'List concrete issues (with what to fix) for any answer that raises a compliance concern; '.
            'return an empty issues list only when there is nothing to flag.';

        $data = $this->call($prompt, $schema);

        if (! isset($data['verdict'], $data['summary'], $data['issues'])) {
            throw new ComplianceEngineException('Gemini response was missing required verdict fields.');
        }

        return [
            'verdict' => $data['verdict'],
            'summary' => $data['summary'],
            'issues' => $data['issues'],
        ];
    }

    /**
     * @return array<string, mixed> the parsed JSON body of the model's reply
     */
    private function call(string $prompt, array $schema): array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            throw new ComplianceEngineException('GEMINI_API_KEY is not configured.');
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout(30)
                ->post(self::ENDPOINT, [
                    'model' => config('services.gemini.model'),
                    'input' => $prompt,
                    'response_format' => [
                        [
                            'type' => 'text',
                            'mime_type' => 'application/json',
                            'schema' => $schema,
                        ],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new ComplianceEngineException('Could not reach the Gemini API.', previous: $e);
        }

        if ($response->failed()) {
            throw new ComplianceEngineException(
                "Gemini API returned HTTP {$response->status()}: ".$response->body(),
            );
        }

        $text = collect($response->json('steps', []))
            ->firstWhere('type', 'model_output')['content'][0]['text'] ?? null;

        if (! is_string($text) || $text === '') {
            throw new ComplianceEngineException('Gemini response did not include a model_output text part.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new ComplianceEngineException('Gemini response text was not valid JSON.');
        }

        return $decoded;
    }
}
