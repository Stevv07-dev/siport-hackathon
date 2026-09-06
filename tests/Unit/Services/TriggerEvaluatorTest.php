<?php

namespace Tests\Unit\Services;

use App\Services\Compliance\TriggerEvaluator;
use Tests\TestCase;

class TriggerEvaluatorTest extends TestCase
{
    private TriggerEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new TriggerEvaluator;
    }

    public function test_equals_clause_is_parseable_and_evaluates_true_when_matched(): void
    {
        $this->assertTrue($this->evaluator->isParseable('connector_present == true'));
        $this->assertTrue($this->evaluator->evaluate('connector_present == true', ['connector_present' => 'yes']));
    }

    public function test_equals_clause_evaluates_false_when_not_matched(): void
    {
        $this->assertFalse($this->evaluator->evaluate('connector_present == true', ['connector_present' => 'no']));
    }

    public function test_numeric_comparison_clause(): void
    {
        $this->assertTrue($this->evaluator->evaluate('voltage_rating_v <= 1000', ['voltage_rating_v' => '1000']));
        $this->assertTrue($this->evaluator->evaluate('voltage_rating_v <= 1000', ['voltage_rating_v' => '500']));
        $this->assertFalse($this->evaluator->evaluate('voltage_rating_v <= 1000', ['voltage_rating_v' => '1200']));
    }

    public function test_and_chain_requires_every_clause_to_match(): void
    {
        $trigger = 'connector_present == true AND voltage_rating_v <= 1000';

        $this->assertTrue($this->evaluator->evaluate($trigger, [
            'connector_present' => 'yes',
            'voltage_rating_v' => '230',
        ]));

        $this->assertFalse($this->evaluator->evaluate($trigger, [
            'connector_present' => 'yes',
            'voltage_rating_v' => '2000',
        ]));
    }

    public function test_missing_answer_for_the_referenced_attribute_is_not_satisfied(): void
    {
        $this->assertFalse($this->evaluator->evaluate('connector_present == true', []));
    }

    public function test_narrative_triggers_from_the_real_dataset_are_not_parseable(): void
    {
        // These exact strings appear in backend-ai/usb-c-cable-dataset/02_clarification_questions.json.
        // TriggerEvaluator must not invent a way to parse them — a narrative trigger has no
        // basis for this class to decide true/false on its own.
        foreach ([
            'product_type identified as insulated cable',
            'classification branch requires insulation information',
            'plastic-insulated branch requiring core diameter',
            'classification remains ambiguous',
            'before Indonesian export restriction lookup',
            'shipment setup',
        ] as $narrativeTrigger) {
            $this->assertFalse($this->evaluator->isParseable($narrativeTrigger), "Expected \"{$narrativeTrigger}\" to be unparseable.");
        }
    }

    public function test_evaluate_returns_false_rather_than_guess_for_a_narrative_trigger(): void
    {
        $this->assertFalse($this->evaluator->evaluate('shipment setup', ['anything' => 'yes']));
    }
}
