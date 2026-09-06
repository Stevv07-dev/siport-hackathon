<?php

namespace App\Services\Compliance;

use App\Contracts\ComplianceEngine;
use App\Exceptions\ScreeningNotAvailableException;

/**
 * Sources questions and the candidate-classification verdict from the AI
 * Engineer's actual dataset (backend-ai/usb-c-cable-dataset/), not
 * fabricated content. That dataset — and app/ai.py's own SYSTEM_PROMPT —
 * only covers one product today: USB-C cable. Every other category/material
 * throws ScreeningNotAvailableException rather than pretending to have
 * content for it (see ProductScreeningController for how that surfaces as
 * "Coming Soon" in the UI).
 *
 * The verdict this produces is a *candidate* HS classification plus export
 * checks to run next — never a pass/fail compliance decision. That mirrors
 * app/ai.py's own constraint on itself: "Kamu BUKAN pejabat bea cukai dan
 * tidak boleh menyatakan Candidate HS Code sebagai keputusan resmi."
 *
 * classify() below is a direct port of backend-ai/app/classifier.py's
 * connector/voltage → 8544.42 rule, extended with
 * usb-c-cable-dataset/03_classification_rules.json's insulation/diameter
 * branches (CLS004–CLS007) — classifier.py doesn't implement those yet, but
 * the dataset defines them precisely enough to port mechanically. Nothing
 * here is a rule this class invented.
 */
class BackendAiComplianceEngine implements ComplianceEngine
{
    private const SUPPORTED_CATEGORY = 'wires-cables';

    private const SUPPORTED_MATERIAL = 'USB-C Cable';

    /** Bahasa Indonesia labels for the enum options in the dataset — the
     *  dataset itself only stores the raw English values. */
    private const ENUM_LABELS = [
        'plastic' => 'Plastik',
        'rubber' => 'Karet',
        'paper' => 'Kertas',
        'other' => 'Lainnya',
        'unknown' => 'Tidak tahu',
    ];

    public function __construct(private readonly BackendAiDataset $dataset, private readonly TriggerEvaluator $trigger) {}

    public function isAvailable(string $categoryKey, string $material): bool
    {
        return $categoryKey === self::SUPPORTED_CATEGORY && $material === self::SUPPORTED_MATERIAL;
    }

    public function generateQuestionnaire(string $categoryKey, string $categoryName, string $material): array
    {
        $this->ensureAvailable($categoryKey, $material);

        $questions = collect($this->dataset->clarificationQuestions())
            ->sortBy('priority')
            ->map(fn (array $q) => $this->mapQuestion($q))
            ->values()
            ->all();

        return ['questions' => $questions];
    }

    public function evaluate(string $categoryKey, string $categoryName, string $material, array $questions, array $answers): array
    {
        $this->ensureAvailable($categoryKey, $material);

        $classification = $this->classify($answers);
        $nextSteps = $this->nextSteps($answers, $classification);

        return [
            // Kept as 'pass'/'fail' so the rest of the app (dashboard stats,
            // history badges) doesn't need its own vocabulary — but see
            // ExportSession::passed()'s doc comment: it means "a candidate
            // HS code was found", not "your product is compliant".
            'verdict' => $classification['status'] === 'candidate' ? 'pass' : 'fail',
            'summary' => $classification['summary'],
            'issues' => $nextSteps,
        ];
    }

    private function ensureAvailable(string $categoryKey, string $material): void
    {
        if (! $this->isAvailable($categoryKey, $material)) {
            throw new ScreeningNotAvailableException(
                "Screening isn't available yet for {$material} ({$categoryKey}). ".
                'The AI Engineer\'s rule engine currently only covers USB-C cable.',
            );
        }
    }

    private function mapQuestion(array $q): array
    {
        $mapped = [
            'key' => $q['attribute'],
            'label' => $q['question'],
            'type' => $q['answer_type'],
            'trigger' => $q['trigger'] ?? null,
            'reason' => $q['reason'] ?? null,
        ];

        if (isset($q['unit'])) {
            $mapped['unit'] = $q['unit'];
        }

        if (isset($q['options'])) {
            $mapped['options'] = collect($q['options'])
                ->map(fn (string $value) => ['value' => $value, 'label' => self::ENUM_LABELS[$value] ?? ucfirst($value)])
                ->all();
        }

        if (! empty($q['source_ids'])) {
            $mapped['source'] = collect($q['source_ids'])
                ->map(fn (string $id) => $this->dataset->source($id)['authority'] ?? $id)
                ->unique()
                ->implode(', ');
        }

        return $mapped;
    }

    /**
     * Port of classifier.py's classify_product(), extended with
     * 03_classification_rules.json CLS004–CLS007 for the finer 8-digit
     * candidate (classifier.py itself stops at the 6-digit subheading).
     *
     * @return array{status: 'candidate'|'needs_review', heading: ?string, subheading: ?string, hs: ?string, summary: string}
     */
    private function classify(array $answers): array
    {
        $connectorPresent = $this->asBool($answers['connector_present'] ?? null);
        $voltage = $this->asFloat($answers['voltage_rating_v'] ?? null);
        $insulation = is_string($answers['insulation_material'] ?? null) ? strtolower($answers['insulation_material']) : null;
        $coreDiameter = $this->asFloat($answers['core_diameter_mm'] ?? null);

        // CLS008 — mirrors classifier.py: connector_present/voltage are the
        // load-bearing facts for even the 6-digit candidate.
        if ($connectorPresent !== true || $voltage === null || $voltage > 1000) {
            return [
                'status' => 'needs_review',
                'heading' => null,
                'subheading' => null,
                'hs' => null,
                'summary' => 'Based on your answers, this doesn\'t yet match the prototype\'s classification branch (connector + ≤1000V). '.
                    'This needs a manual review rather than an automated candidate.',
            ];
        }

        // CLS001 + CLS002
        $heading = '8544';
        $subheading = '8544.42';
        $hs = null;

        // CLS004–CLS007 — only reachable once insulation/diameter are known;
        // otherwise the candidate stops at the 6-digit subheading honestly,
        // rather than guessing the last two digits.
        if ($insulation === 'plastic' && $coreDiameter !== null) {
            $hs = match (true) {
                $coreDiameter <= 5 => '8544.42.94',
                $coreDiameter <= 19.5 => '8544.42.95',
                default => '8544.42.96',
            };
        } elseif (in_array($insulation, ['rubber', 'paper'], true)) {
            $hs = '8544.42.97';
        }

        $summary = $hs
            ? "Candidate classification: {$hs} — this is a candidate only, not an official HS ruling."
            : "Candidate classification: {$subheading} — the 8-digit code needs the insulation material and core diameter to narrow further.";

        return [
            'status' => 'candidate',
            'heading' => $heading,
            'subheading' => $subheading,
            'hs' => $hs,
            'summary' => $summary,
        ];
    }

    /**
     * Advisory checklist from 04_indonesia_lartas_rules.json and
     * 05_singapore_import_rules.json — informational next steps, not
     * pass/fail issues. LRT001's ATM-cable scope is matched with a plain
     * keyword check against intended_use, exactly as the dataset's own
     * CASE003 example implies; everything else is a fixed reminder since
     * this MVP's destination is always Singapore.
     *
     * @return array<int, array{title: string, detail: string}>
     */
    private function nextSteps(array $answers, array $classification): array
    {
        $steps = [];

        $intendedUse = strtolower((string) ($answers['intended_use'] ?? ''));
        $mentionsAtm = str_contains($intendedUse, 'atm') || str_contains($intendedUse, 'automated teller');

        if ($mentionsAtm) {
            $steps[] = [
                'title' => 'Possible restricted-goods match (LRT001)',
                'detail' => 'Your stated use mentions ATM machines. Indonesia\'s export-restriction list has a specific '.
                    'entry (ex 8544.42.99) for power cables with connectors for ATM machines — compare your product '.
                    'against that exact scope before exporting.',
            ];
        } else {
            $steps[] = [
                'title' => 'Confirm export restriction status (LRT002)',
                'detail' => 'Run a current official Indonesian export-restriction (Lartas) lookup using the candidate '.
                    'HS code above — restriction lists change and this prototype doesn\'t check them live.',
            ];
        }

        if (($answers['telecommunication_use'] ?? null) !== null && $this->asBool($answers['telecommunication_use']) === true) {
            $steps[] = [
                'title' => 'Check telecommunications-specific subheadings (CLS003)',
                'detail' => 'You indicated this cable is used for telecommunications. Some 8544.42 subheadings '.
                    'distinguish telecommunications use specifically — verify the candidate code against those.',
            ];
        }

        $steps[] = [
            'title' => 'Confirm Singapore\'s HS/AHTN code',
            'detail' => 'Determine Singapore\'s 8-digit HS/AHTN code for this product using Singapore Customs\' HS '.
                'classification guidance, then run it through the HS/CA Product Code Checker.',
        ];

        if ($classification['status'] === 'candidate') {
            $steps[] = [
                'title' => 'Identify a Competent Authority if controlled',
                'detail' => 'If the HS/CA checker flags this as controlled, identify the relevant Competent Authority '.
                    'and the authorisation it requires before shipping.',
            ];
        } else {
            $steps[] = [
                'title' => 'Consider an official classification ruling',
                'detail' => 'Since classification remains uncertain from the answers given, Singapore Customs\' '.
                    'classification ruling service can provide a definitive answer.',
            ];
        }

        return $steps;
    }

    private function asBool(mixed $value): ?bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function asFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
