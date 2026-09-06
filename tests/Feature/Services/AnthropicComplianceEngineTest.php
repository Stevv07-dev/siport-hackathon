<?php

namespace Tests\Feature\Services;

use App\Exceptions\ComplianceEngineException;
use App\Services\Compliance\AnthropicComplianceEngine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnthropicComplianceEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'claude-sonnet-5']);
    }

    private function toolUseResponse(string $toolName, array $input): array
    {
        return [
            'id' => 'msg_test',
            'type' => 'message',
            'content' => [
                ['type' => 'tool_use', 'id' => 'toolu_test', 'name' => $toolName, 'input' => $input],
            ],
        ];
    }

    public function test_it_parses_a_generated_questionnaire(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->toolUseResponse('submit_questionnaire', [
                'questions' => [
                    ['key' => 'shielding', 'label' => 'Is the cable shielded?', 'type' => 'boolean'],
                ],
            ])),
        ]);

        $result = (new AnthropicComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');

        $this->assertSame('shielding', $result['questions'][0]['key']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'test-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request['model'] === 'claude-sonnet-5'
                && $request['tool_choice']['name'] === 'submit_questionnaire';
        });
    }

    public function test_it_parses_an_evaluation_verdict(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->toolUseResponse('submit_verdict', [
                'verdict' => 'fail',
                'summary' => 'Missing certification.',
                'issues' => [
                    ['title' => 'No test report', 'detail' => 'Provide a lab test report.'],
                ],
            ])),
        ]);

        $result = (new AnthropicComplianceEngine)->evaluate(
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
        config(['services.anthropic.key' => null]);

        $this->expectException(ComplianceEngineException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY');

        (new AnthropicComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }

    public function test_it_throws_on_a_non_2xx_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'overloaded'], 529),
        ]);

        $this->expectException(ComplianceEngineException::class);

        (new AnthropicComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }

    public function test_it_throws_when_the_response_has_no_tool_use_block(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'id' => 'msg_test',
                'type' => 'message',
                'content' => [['type' => 'text', 'text' => 'Sorry, I cannot help with that.']],
            ]),
        ]);

        $this->expectException(ComplianceEngineException::class);

        (new AnthropicComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }

    public function test_it_throws_when_a_connection_error_occurs(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->expectException(ComplianceEngineException::class);

        (new AnthropicComplianceEngine)->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'Fiber Optic Cable');
    }
}
