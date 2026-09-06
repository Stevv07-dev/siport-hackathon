<?php

namespace App\Services;

use App\Contracts\ComplianceEngine;
use App\Exceptions\ComplianceEngineException;
use App\Models\ExportSession;
use App\Models\User;
use App\Services\Compliance\TriggerEvaluator;
use Illuminate\Support\Facades\Session;

/**
 * Shared logic for turning a fully-answered ExportSession into a completed
 * one with a verdict — used both by the normal "Submit for Compliance
 * Review" action and by the guest-claim flow below.
 *
 * Guests can go through the whole Category → Questionnaire flow without an
 * account (see routes/web.php — products.* isn't behind 'auth'). The
 * session they build up is tied to their browser via `guest_token`
 * (App\Policies\ExportSessionPolicy). Only when they try to see the
 * *result* do we ask them to log in or register — at which point we "claim"
 * that anonymous session onto their new/existing account and finish it
 * immediately, so login is the only extra step between finishing the quiz
 * and seeing the verdict.
 */
class ExportSessionFinalizer
{
    private const SESSION_KEY = 'pending_export_session_id';

    public function __construct(
        private readonly ComplianceEngine $engine,
        private readonly TriggerEvaluator $trigger,
    ) {}

    /**
     * Whether $question should be shown/required given the answers gathered
     * so far. A question with no trigger, or a narrative one backend-ai
     * didn't write in the `attribute == value` grammar ("shipment setup",
     * "classification remains ambiguous"), always applies — there's no basis
     * to invent a gate for it. Only a trigger TriggerEvaluator can actually
     * parse is used to conditionally skip a question.
     */
    public function questionApplies(array $question, array $answers): bool
    {
        $trigger = $question['trigger'] ?? null;

        if (! $trigger || ! $this->trigger->isParseable($trigger)) {
            return true;
        }

        return $this->trigger->evaluate($trigger, $answers);
    }

    /**
     * Keys of every applicable question in $questions that $answers doesn't
     * yet answer. A `boolean_plus_text` question additionally requires its
     * "{key}_detail" companion field, matching products/show.blade.php's
     * client-side isAnswered().
     *
     * @return array<int, string>
     */
    public function missingAnswers(array $questions, array $answers): array
    {
        return collect($questions)
            ->filter(fn (array $q) => $this->questionApplies($q, $answers))
            ->reject(function (array $q) use ($answers) {
                if (! filled($answers[$q['key']] ?? null)) {
                    return false;
                }

                if (($q['type'] ?? null) === 'boolean_plus_text') {
                    return filled($answers[$q['key'].'_detail'] ?? null);
                }

                return true;
            })
            ->pluck('key')
            ->all();
    }

    public static function remember(ExportSession $session): void
    {
        Session::put(self::SESSION_KEY, $session->id);
    }

    /**
     * Called right after a successful login/registration. If the browser
     * has an unclaimed guest session pending, attach it to $user and score
     * it, returning the now-completed session so the caller can redirect
     * straight to the result. Returns null when there's nothing to claim
     * (the common case — most logins aren't coming from the guest quiz).
     */
    public function claimPending(User $user): ?ExportSession
    {
        $sessionId = Session::pull(self::SESSION_KEY);

        if (! $sessionId) {
            return null;
        }

        $exportSession = ExportSession::find($sessionId);

        if (! $exportSession || $exportSession->user_id !== null) {
            return null;
        }

        $exportSession->forceFill(['user_id' => $user->id, 'guest_token' => null])->save();

        if ($exportSession->isCompleted()) {
            return $exportSession;
        }

        try {
            $this->finalize($exportSession, $exportSession->answers ?? []);
        } catch (ComplianceEngineException $e) {
            report($e);

            // The session is claimed but not yet scored; the user lands on
            // it via products.show and can just press Submit again there.
        }

        return $exportSession->fresh();
    }

    /**
     * @throws ComplianceEngineException
     */
    public function finalize(ExportSession $session, array $answers): void
    {
        $result = $this->engine->evaluate(
            $session->category_key,
            $session->category_name,
            $session->material,
            $session->questions,
            $answers,
        );

        $session->update([
            'answers' => $answers,
            'result' => $result,
            'status' => 'completed',
            'submitted_at' => now(),
        ]);
    }
}
