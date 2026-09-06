<?php

namespace Tests\Feature\Services;

use App\Exceptions\ComplianceEngineException;
use App\Services\Compliance\GeminiComplianceEngine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiComplianceEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-3.6-flash']);
    }

    private function interactionResponse(array $jsonPayload): array
    {
        return [
            'id' => 'int_test',
            'status' => 'completed',
            'steps' => [
                ['type' => 'user_input', 'status' => 'done', 'content' => [['type' => 'text', 'text' => 'prompt']]],
                ['type' => 'model_output', 'status' => 'done', 'content' => [['type' => 'text', 'text' => json_encode($jsonPayload)]]],
            ],
        ];
    }

    public function test_it_parses_a_generated_questionnaire(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->interactionResponse([
                'questions' => [
                    ['key' => 'shielding', 'label' => 'Is the cable shielded?', 'type' => 'boolean'],
                ],
            ])),
        ]);

        $result = (new GeminiComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');

        $this->assertSame('shielding', $result['questions'][0]['key']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta2/interactions'
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && $request['model'] === 'gemini-3.6-flash'
                && $request['response_format'][0]['mime_type'] === 'application/json';
        });
    }

    public function test_it_parses_an_evaluation_verdict(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->interactionResponse([
                'verdict' => 'fail',
                'summary' => 'Missing certification.',
                'issues' => [
                    ['title' => 'No test report', 'detail' => 'Provide a lab test report.'],
                ],
            ])),
        ]);

        $result = (new GeminiComplianceEngine)->evaluate(
            'wires-cables',
            'Insulated Wires / Cables',
            'Fiber Optic Cable',
            [['key' => 'shielding', 'label' => 'Is the cable shielded?', 'type' => 'boolean']],
            ['shielding' => 'no'],
        );

        $this->assertSame('fail', $result['verdict']);
        $this->assertSame('No test report', $result['issues'][0]['title']);
    }

    public function test_it_throws_when_the_api_key_is_missing(): void
    {
        config(['services.gemini.key' => null]);

        $this->expectException(ComplianceEngineException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY');

        (new GeminiComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }

    public function test_it_throws_on_a_non_2xx_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('', 404),
        ]);

        $this->expectException(ComplianceEngineException::class);

        (new GeminiComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }

    public function test_it_throws_when_the_response_has_no_model_output_step(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'id' => 'int_test',
                'status' => 'completed',
                'steps' => [
                    ['type' => 'user_input', 'status' => 'done', 'content' => [['type' => 'text', 'text' => 'prompt']]],
                ],
            ]),
        ]);

        $this->expectException(ComplianceEngineException::class);

        (new GeminiComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }

    public function test_it_throws_when_the_model_output_text_is_not_valid_json(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'id' => 'int_test',
                'status' => 'completed',
                'steps' => [
                    ['type' => 'model_output', 'status' => 'done', 'content' => [['type' => 'text', 'text' => 'not json']]],
                ],
            ]),
        ]);

        $this->expectException(ComplianceEngineException::class);

        (new GeminiComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }

    public function test_it_throws_when_a_connection_error_occurs(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->expectException(ComplianceEngineException::class);

        (new GeminiComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }
}
