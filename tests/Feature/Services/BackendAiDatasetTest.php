<?php

namespace Tests\Feature\Services;

use App\Services\Compliance\BackendAiDataset;
use Tests\TestCase;

/**
 * Reads the real files under backend-ai/usb-c-cable-dataset/ — not fixtures
 * — so this fails loudly if the AI Engineer's dataset shape ever changes out
 * from under MAXPORT.
 */
class BackendAiDatasetTest extends TestCase
{
    private BackendAiDataset $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataset = new BackendAiDataset;
    }

    public function test_clarification_questions_are_read_from_the_real_dataset(): void
    {
        $questions = $this->dataset->clarificationQuestions();

        $this->assertNotEmpty($questions);
        $this->assertContains('connector_present', array_column($questions, 'attribute'));
        $this->assertContains('Q001', array_column($questions, 'id'));
    }

    public function test_sources_can_be_looked_up_by_id(): void
    {
        $source = $this->dataset->source('SRC-ID-BTKI');

        $this->assertNotNull($source);
        $this->assertSame('Indonesia', $source['country']);
    }

    public function test_unknown_source_id_returns_null_rather_than_a_guess(): void
    {
        $this->assertNull($this->dataset->source('SRC-DOES-NOT-EXIST'));
    }

    public function test_classification_lartas_and_singapore_rule_files_all_load(): void
    {
        $this->assertNotEmpty($this->dataset->classificationRules());
        $this->assertNotEmpty($this->dataset->lartasRules());
        $this->assertNotEmpty($this->dataset->singaporeImportRules());
    }
}
