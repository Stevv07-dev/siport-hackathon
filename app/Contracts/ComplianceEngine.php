<?php

namespace App\Contracts;

use App\Exceptions\ComplianceEngineException;
use App\Exceptions\ScreeningNotAvailableException;

/**
 * Source of the questionnaire content and the compliance verdict for a
 * screening session — the "AI backend" the product spec refers to.
 *
 * Two questions/answers are generated per session:
 *   1. generateQuestionnaire() — what to ask, given a product category and
 *      material. The result is snapshotted into export_sessions.questions
 *      so a session stays reproducible even if the engine's output changes
 *      later (different prompt, different model, switched provider, ...).
 *   2. evaluate() — given the same questions plus the user's answers,
 *      whether the product passes screening and why.
 *
 * Swap the bound implementation in AppServiceProvider (config('compliance.driver'))
 * without touching any controller or view code.
 */
interface ComplianceEngine
{
    /**
     * `type` is deliberately not a fixed enum here — different engines
     * source different shapes. BackendAiComplianceEngine passes through
     * backend-ai's own answer_type values verbatim (boolean, text, number,
     * integer, enum, boolean_plus_text); the LLM-based engines currently
     * only ever produce boolean/enum. The questionnaire view
     * (products/show.blade.php) renders whatever it's given and must be
     * extended if a new type shows up — it does not invent a fallback.
     *
     * `trigger` (optional) is a raw condition string ("attribute == value",
     * possibly narrative/non-parseable) — see TriggerEvaluator for how it's
     * used to skip inapplicable questions without a round trip per answer.
     *
     * @return array{questions: array<int, array{key: string, label: string, type: string, options?: array<int, array{value: string, label: string}>, unit?: string, trigger?: string, reason?: string, source?: string}>}
     *
     * @throws ComplianceEngineException
     * @throws ScreeningNotAvailableException
     */
    public function generateQuestionnaire(string $categoryKey, string $categoryName, string $material): array;

    /**
     * @param  array<int, array{key: string, label: string, type: string, options?: array<int, string>}>  $questions
     * @param  array<string, string>  $answers
     * @return array{verdict: 'pass'|'fail', summary: string, issues: array<int, array{title: string, detail: string}>}
     *
     * @throws ComplianceEngineException
     */
    public function evaluate(string $categoryKey, string $categoryName, string $material, array $questions, array $answers): array;
}
