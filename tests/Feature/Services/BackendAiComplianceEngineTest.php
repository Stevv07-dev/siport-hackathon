<?php

namespace Tests\Feature\Services;

use App\Exceptions\ScreeningNotAvailableException;
use App\Services\Compliance\BackendAiComplianceEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BackendAiComplianceEngineTest extends TestCase
{
    private BackendAiComplianceEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = $this->app->make(BackendAiComplianceEngine::class);
    }

    public function test_only_usb_c_cable_under_wires_cables_is_available(): void
    {
        $this->assertTrue($this->engine->isAvailable('wires-cables', 'USB-C Cable'));
        $this->assertFalse($this->engine->isAvailable('wires-cables', 'PVC Insulated Cable'));
        $this->assertFalse($this->engine->isAvailable('telecommunications', 'Router'));
    }

    public function test_generating_a_questionnaire_for_an_unsupported_combination_throws(): void
    {
        $this->expectException(ScreeningNotAvailableException::class);

        $this->engine->generateQuestionnaire('telecommunications', 'Telecommunications', 'Router');
    }

    public function test_the_questionnaire_is_sourced_from_the_real_dataset_and_ordered_by_priority(): void
    {
        $result = $this->engine->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable');

        $keys = array_column($result['questions'], 'key');

        $this->assertSame([
            'connector_present', 'connector_type', 'voltage_rating_v', 'telecommunication_use',
            'insulation_material', 'core_diameter_mm', 'flat_cable', 'intended_use',
            'quantity_pcs', 'gross_weight_kg',
        ], $keys);

        $telecomQuestion = collect($result['questions'])->firstWhere('key', 'telecommunication_use');
        $this->assertSame('boolean_plus_text', $telecomQuestion['type']);

        $insulationQuestion = collect($result['questions'])->firstWhere('key', 'insulation_material');
        $this->assertSame('enum', $insulationQuestion['type']);
        $this->assertContains('plastic', array_column($insulationQuestion['options'], 'value'));
    }

    public function test_evaluate_for_an_unsupported_combination_throws(): void
    {
        $this->expectException(ScreeningNotAvailableException::class);

        $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'PVC Insulated Cable', [], []);
    }

    public function test_evaluate_needs_review_when_there_is_no_connector(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'no',
        ]);

        $this->assertSame('fail', $result['verdict']);
    }

    public function test_evaluate_needs_review_when_voltage_exceeds_1000v(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'yes',
            'voltage_rating_v' => '1500',
        ]);

        $this->assertSame('fail', $result['verdict']);
    }

    #[DataProvider('plasticDiameterThresholds')]
    public function test_plastic_insulation_narrows_to_an_8_digit_candidate_by_core_diameter(string $diameter, string $expectedHs): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'yes',
            'voltage_rating_v' => '20',
            'insulation_material' => 'plastic',
            'core_diameter_mm' => $diameter,
        ]);

        $this->assertSame('pass', $result['verdict']);
        $this->assertStringContainsString($expectedHs, $result['summary']);
    }

    public static function plasticDiameterThresholds(): array
    {
        return [
            'at the 5mm boundary' => ['5', '8544.42.94'],
            'above 5mm, at the 19.5mm boundary' => ['19.5', '8544.42.95'],
            'above 19.5mm' => ['25', '8544.42.96'],
        ];
    }

    public function test_rubber_or_paper_insulation_candidates_directly_to_97(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'yes',
            'voltage_rating_v' => '20',
            'insulation_material' => 'rubber',
        ]);

        $this->assertSame('pass', $result['verdict']);
        $this->assertStringContainsString('8544.42.97', $result['summary']);
    }

    public function test_without_insulation_material_the_candidate_stops_honestly_at_the_6_digit_subheading(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'yes',
            'voltage_rating_v' => '20',
        ]);

        $this->assertSame('pass', $result['verdict']);
        $this->assertStringContainsString('8544.42', $result['summary']);
        $this->assertStringNotContainsString('8544.42.9', $result['summary']);
    }

    public function test_atm_intended_use_flags_the_indonesia_lartas_restricted_goods_step(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'yes',
            'voltage_rating_v' => '20',
            'insulation_material' => 'rubber',
            'intended_use' => 'Power cable for an ATM machine',
        ]);

        $this->assertTrue(collect($result['issues'])->contains('title', 'Possible restricted-goods match (LRT001)'));
    }

    public function test_non_atm_intended_use_gets_the_generic_lartas_lookup_reminder(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'yes',
            'voltage_rating_v' => '20',
            'insulation_material' => 'rubber',
            'intended_use' => 'Charging a laptop',
        ]);

        $this->assertTrue(collect($result['issues'])->contains('title', 'Confirm export restriction status (LRT002)'));
    }

    public function test_telecommunication_use_adds_the_subheading_check_step(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'yes',
            'voltage_rating_v' => '20',
            'insulation_material' => 'rubber',
            'telecommunication_use' => 'yes',
        ]);

        $this->assertTrue(collect($result['issues'])->contains('title', 'Check telecommunications-specific subheadings (CLS003)'));
    }

    public function test_singapore_hs_ahtn_reminder_is_always_present(): void
    {
        $result = $this->engine->evaluate('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable', [], [
            'connector_present' => 'no',
        ]);

        $this->assertTrue(collect($result['issues'])->contains('title', 'Confirm Singapore\'s HS/AHTN code'));
    }
}
