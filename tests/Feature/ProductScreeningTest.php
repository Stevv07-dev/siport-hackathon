<?php

namespace Tests\Feature;

use App\Models\ExportSession;
use App\Models\User;
use App\Services\Compliance\BackendAiComplianceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductScreeningTest extends TestCase
{
    use RefreshDatabase;

    // --- Guest access: the core screening flow is open before login -------

    public function test_guests_can_view_the_category_picker(): void
    {
        $this->get('/products/new')
            ->assertOk()
            ->assertSee('Select Product Category');
    }

    public function test_guests_can_create_a_session_and_view_the_questionnaire(): void
    {
        $response = $this->post('/products', [
            'category_key' => 'wires-cables',
            'material' => 'USB-C Cable',
        ]);
        $this->persistGuestSession();

        $session = ExportSession::first();
        $response->assertRedirect(route('products.show', $session));

        $this->assertNull($session->user_id);
        $this->assertNotEmpty($session->guest_token);

        $this->get(route('products.show', $session))
            ->assertOk()
            ->assertSee('Material Verification');
    }

    public function test_a_guest_cannot_view_another_guests_session(): void
    {
        $session = $this->createSession(null, ['guest_token' => 'someone-elses-browser-session-id']);

        $this->get(route('products.show', $session))->assertForbidden();
    }

    public function test_guest_submit_redirects_to_register_without_scoring(): void
    {
        $this->post('/products', ['category_key' => 'wires-cables', 'material' => 'USB-C Cable'])
            ->assertRedirect();
        $this->persistGuestSession();

        $session = ExportSession::first();

        $response = $this->post(route('products.submit', $session), [
            'answers' => $this->completeUsbCCableAnswers(),
        ]);

        $response->assertRedirect(route('register'));

        $session->refresh();
        $this->assertSame('in_progress', $session->status);
        $this->assertNull($session->result);
        $this->assertSame('yes', $session->answers['connector_present']);
    }

    public function test_registering_after_the_guest_quiz_claims_and_scores_the_session(): void
    {
        $this->post('/products', ['category_key' => 'wires-cables', 'material' => 'USB-C Cable']);
        $this->persistGuestSession();
        $session = ExportSession::first();

        $this->post(route('products.submit', $session), [
            'answers' => $this->completeUsbCCableAnswers(),
        ])->assertRedirect(route('register'));

        $response = $this->post('/register', [
            'name' => 'Rina Kusuma',
            'email' => 'rina@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $session->refresh();
        $response->assertRedirect(route('products.show', $session));

        $this->assertSame(User::where('email', 'rina@example.com')->value('id'), $session->user_id);
        $this->assertSame('completed', $session->status);
        $this->assertSame('pass', $session->result['verdict']);
    }

    public function test_logging_in_after_the_guest_quiz_claims_and_scores_the_session(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->post('/products', ['category_key' => 'wires-cables', 'material' => 'USB-C Cable']);
        $this->persistGuestSession();
        $session = ExportSession::first();

        $this->post(route('products.submit', $session), [
            'answers' => $this->completeUsbCCableAnswers(['connector_present' => 'no']),
        ])->assertRedirect(route('register'));

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $session->refresh();
        $response->assertRedirect(route('products.show', $session));

        $this->assertSame($user->id, $session->user_id);
        $this->assertSame('completed', $session->status);
        $this->assertSame('fail', $session->result['verdict']);
    }

    public function test_history_still_requires_login(): void
    {
        $this->get(route('history.index'))->assertRedirect('/login');
    }

    // --- Honest availability: only USB-C Cable is real ----------------------

    public function test_an_invalid_category_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/products', ['category_key' => 'not-a-real-category', 'material' => 'Something'])
            ->assertSessionHasErrors('category_key');

        $this->assertSame(0, ExportSession::count());
    }

    public function test_a_category_with_no_real_questionnaire_is_rejected_as_material_not_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/products', ['category_key' => 'telecommunications', 'material' => 'Router'])
            ->assertSessionHasErrors('material');

        $this->assertSame(0, ExportSession::count());
    }

    public function test_a_material_other_than_usb_c_cable_is_rejected_within_the_one_available_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/products', ['category_key' => 'wires-cables', 'material' => 'PVC Insulated Cable'])
            ->assertSessionHasErrors('material');

        $this->assertSame(0, ExportSession::count());
    }

    public function test_the_category_picker_marks_every_category_except_wires_cables_as_coming_soon(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/products/new')
            ->assertOk()
            ->assertSee('Select Product Category')
            ->assertSee('Insulated Wires / Cables')
            ->assertSee('Soon');
    }

    // --- Authenticated behavior ----------------------------------------------

    public function test_submitting_a_category_creates_a_session_with_the_real_backend_ai_questionnaire(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/products', [
            'category_key' => 'wires-cables',
            'material' => 'USB-C Cable',
        ]);

        $session = ExportSession::first();

        $response->assertRedirect(route('products.show', $session));

        $this->assertSame($user->id, $session->user_id);
        $this->assertSame('in_progress', $session->status);
        $this->assertSame('backend_ai', $session->engine);
        $this->assertNotEmpty($session->questions);
        $this->assertSame([], $session->answers);
        $this->assertContains('connector_present', array_column($session->questions, 'key'));
    }

    public function test_the_questionnaire_step_is_shown_for_an_in_progress_session(): void
    {
        $user = User::factory()->create();
        $session = $this->createSession($user);

        $this->actingAs($user)
            ->get(route('products.show', $session))
            ->assertOk()
            ->assertSee('Material Verification')
            ->assertSee('Submit for Compliance Review');
    }

    public function test_progress_is_saved_so_a_session_can_be_resumed(): void
    {
        $user = User::factory()->create();
        $session = $this->createSession($user);

        $this->actingAs($user)
            ->patch(route('products.progress', $session), [
                'answers' => ['connector_present' => 'yes'],
            ])
            ->assertNoContent();

        $this->assertSame(['connector_present' => 'yes'], $session->fresh()->answers);

        $this->actingAs($user)
            ->get(route('products.show', $session))
            ->assertOk();
    }

    public function test_submitting_incomplete_answers_is_rejected(): void
    {
        $user = User::factory()->create();
        $session = $this->createSession($user);

        $this->actingAs($user)
            ->post(route('products.submit', $session), [
                'answers' => ['connector_present' => 'yes'],
            ])
            ->assertSessionHasErrors('submit');

        $this->assertSame('in_progress', $session->fresh()->status);
    }

    public function test_a_boolean_plus_text_question_requires_both_its_choice_and_its_explanation(): void
    {
        $user = User::factory()->create();
        $session = $this->createSession($user);

        $answers = $this->completeUsbCCableAnswers();
        unset($answers['telecommunication_use_detail']);

        $this->actingAs($user)
            ->post(route('products.submit', $session), ['answers' => $answers])
            ->assertSessionHasErrors('submit');

        $this->assertSame('in_progress', $session->fresh()->status);
    }

    public function test_questions_gated_by_an_unmet_trigger_are_not_required(): void
    {
        $user = User::factory()->create();
        $session = $this->createSession($user);

        // connector_present == no means connector_type/voltage_rating_v (gated on it)
        // and telecommunication_use (gated on voltage_rating_v) never become
        // applicable — so answering everything else is already "complete".
        $answers = [
            'connector_present' => 'no',
            'insulation_material' => 'plastic',
            'core_diameter_mm' => '3',
            'flat_cable' => 'no',
            'intended_use' => 'Personal charging cable',
            'quantity_pcs' => '100',
            'gross_weight_kg' => '12.5',
        ];

        $response = $this->actingAs($user)->post(route('products.submit', $session), ['answers' => $answers]);

        $response->assertRedirect(route('products.show', $session));
        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_submitting_complete_answers_stores_the_candidate_classification_and_completes_the_session(): void
    {
        $user = User::factory()->create();
        $session = $this->createSession($user);

        $response = $this->actingAs($user)->post(route('products.submit', $session), [
            'answers' => $this->completeUsbCCableAnswers(),
        ]);

        $response->assertRedirect(route('products.show', $session));

        $session->refresh();

        $this->assertSame('completed', $session->status);
        $this->assertSame('pass', $session->result['verdict']);
        $this->assertNotNull($session->submitted_at);
    }

    public function test_the_result_is_still_visible_after_completion_and_is_framed_as_a_candidate_not_a_pass_fail_verdict(): void
    {
        $user = User::factory()->create();
        $session = $this->createSession($user);

        $this->actingAs($user)->post(route('products.submit', $session), [
            'answers' => $this->completeUsbCCableAnswers(['connector_present' => 'no']),
        ]);

        $this->actingAs($user)
            ->get(route('products.show', $session))
            ->assertOk()
            ->assertSee('Needs Manual Review')
            ->assertSee('candidate')
            ->assertSee("Confirm Singapore's HS/AHTN code");
    }

    public function test_a_user_cannot_view_another_users_session(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $session = $this->createSession($owner);

        $this->actingAs($intruder)
            ->get(route('products.show', $session))
            ->assertForbidden();
    }

    public function test_a_user_cannot_update_another_users_session_progress(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $session = $this->createSession($owner);

        $this->actingAs($intruder)
            ->patch(route('products.progress', $session), ['answers' => ['connector_present' => 'yes']])
            ->assertForbidden();
    }

    public function test_history_lists_only_the_current_users_sessions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createSession($user, ['material' => 'Mine']);
        $this->createSession($otherUser, ['material' => 'Not Mine']);

        $this->actingAs($user)
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Not Mine');
    }

    /**
     * A full, valid answer set for the real USB-C cable questionnaire —
     * built from app/Services/Compliance/BackendAiComplianceEngine's own
     * classify()/nextSteps() branches, not invented independently, so this
     * test suite breaks (rather than silently drifts) if that logic changes.
     */
    private function completeUsbCCableAnswers(array $overrides = []): array
    {
        return array_merge([
            'connector_present' => 'yes',
            'connector_type' => 'USB-C to USB-C',
            'voltage_rating_v' => '20',
            'telecommunication_use' => 'no',
            'telecommunication_use_detail' => 'Used only for charging and data transfer between consumer devices.',
            'insulation_material' => 'plastic',
            'core_diameter_mm' => '3',
            'flat_cable' => 'no',
            'intended_use' => 'Personal charging cable',
            'quantity_pcs' => '100',
            'gross_weight_kg' => '12.5',
        ], $overrides);
    }

    /**
     * Uses the real BackendAiComplianceEngine to build the question set
     * (rather than a hand-maintained fixture) so it can never silently drift
     * from what backend-ai/usb-c-cable-dataset actually defines.
     */
    private function createSession(?User $user, array $overrides = []): ExportSession
    {
        $questions = app(BackendAiComplianceEngine::class)
            ->generateQuestionnaire('wires-cables', 'Insulated Wires / Cables', 'USB-C Cable')['questions'];

        return ExportSession::create(array_merge([
            'user_id' => $user?->id,
            'category_key' => 'wires-cables',
            'category_name' => 'Insulated Wires / Cables',
            'material' => 'USB-C Cable',
            'status' => 'in_progress',
            'questions' => $questions,
            'answers' => [],
            'engine' => 'backend_ai',
        ], $overrides));
    }
}
