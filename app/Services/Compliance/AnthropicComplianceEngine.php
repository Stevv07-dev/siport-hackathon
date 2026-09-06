<?php

namespace App\Services\Compliance;

use App\Contracts\ComplianceEngine;
use App\Exceptions\ComplianceEngineException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Calls the Anthropic Messages API to generate the questionnaire and the
 * compliance verdict. Enabled by setting COMPLIANCE_ENGINE=anthropic and
 * ANTHROPIC_API_KEY in .env — see DEPLOYMENT.md.
 *
 * Uses forced tool-use so the model's reply is parsed JSON (matching a
 * schema) rather than free text we'd have to regex out of a chat response.
 */
class AnthropicComplianceEngine implements ComplianceEngine
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    public function generateQuestionnaire(string $categoryKey, string $categoryName, string $material): array
    {
        $tool = [
            'name' => 'submit_questionnaire',
            'description' => 'Return the technical compliance questions to ask for this product.',
            'input_schema' => [
                'type' => 'object',
                'required' => ['questions'],
                'properties' => [
                    'questions' => [
                        'type' => 'array',
                        'minItems' => 2,
                        'maxItems' => 6,
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
                                    'description' => 'Required when type is "select"; the list of selectable answers.',
                                ],
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
            'options (e.g. a rating scale). Keep it to 3-5 questions.';

        $input = $this->call($prompt, $tool);

        if (! isset($input['questions']) || ! is_array($input['questions'])) {
            throw new ComplianceEngineException('Anthropic response was missing a "questions" array.');
        }

        return ['questions' => $input['questions']];
    }

    public function evaluate(string $categoryKey, string $categoryName, string $material, array $questions, array $answers): array
    {
        $tool = [
            'name' => 'submit_verdict',
            'description' => 'Return the export-readiness verdict for this product.',
            'input_schema' => [
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

        $input = $this->call($prompt, $tool);

        if (! isset($input['verdict'], $input['summary'], $input['issues'])) {
            throw new ComplianceEngineException('Anthropic response was missing required verdict fields.');
        }

        return [
            'verdict' => $input['verdict'],
            'summary' => $input['summary'],
            'issues' => $input['issues'],
        ];
    }

    /**
     * @param  array{name: string, description: string, input_schema: array}  $tool
     * @return array<string, mixed> the parsed tool_use input block
     */
    private function call(string $prompt, array $tool): array
    {
        $apiKey = config('services.anthropic.key');

        if (empty($apiKey)) {
            throw new ComplianceEngineException('ANTHROPIC_API_KEY is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => self::API_VERSION,
            ])
                ->timeout(30)
                ->post(self::ENDPOINT, [
                    'model' => config('services.anthropic.model'),
                    'max_tokens' => 1024,
                    'temperature' => 0,
                    'tools' => [$tool],
                    'tool_choice' => ['type' => 'tool', 'name' => $tool['name']],
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new ComplianceEngineException('Could not reach the Anthropic API.', previous: $e);
        }

        if ($response->failed()) {
            throw new ComplianceEngineException(
                "Anthropic API returned HTTP {$response->status()}: ".$response->body(),
            );
        }

        try {
            $block = collect($response->json('content', []))
                ->firstWhere('type', 'tool_use');
        } catch (Throwable $e) {
            throw new ComplianceEngineException('Could not parse the Anthropic API response.', previous: $e);
        }

        if (! is_array($block) || ! isset($block['input']) || ! is_array($block['input'])) {
            throw new ComplianceEngineException('Anthropic response did not include the expected tool_use block.');
        }

        return $block['input'];
    }
}
