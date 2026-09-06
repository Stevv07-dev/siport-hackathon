<?php

namespace App\Http\Controllers;

use App\Contracts\ComplianceEngine;
use App\Exceptions\ComplianceEngineException;
use App\Exceptions\ScreeningNotAvailableException;
use App\Models\ExportSession;
use App\Services\ExportSessionFinalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * The whole Category → Questionnaire flow is open to guests (see
 * routes/web.php) — someone can try MAXPORT's core feature before ever
 * creating an account. Their session is tracked via guest_token, tied to
 * their browser's Laravel session ID (ExportSessionPolicy). Only submit()
 * requires an account, since that's the point of the flow where there's
 * something worth saving to a profile: the compliance verdict.
 *
 * Only Insulated Wires/Cables → USB-C Cable has a real questionnaire behind
 * it (backend-ai/usb-c-cable-dataset/, via BackendAiComplianceEngine) —
 * every other category/material is marked unavailable here and shown as
 * "Coming Soon" rather than silently falling back to placeholder content.
 * Keep this list's `available` flags in sync with
 * BackendAiComplianceEngine::isAvailable().
 */
class ProductScreeningController extends Controller
{
    private const CATEGORIES = [
        ['key' => 'electrical-machines', 'name' => 'Electrical Machines', 'description' => 'Motors, inverters & apparatus', 'available' => false],
        ['key' => 'telecommunications', 'name' => 'Telecommunications', 'description' => 'Routers, switches & antennas', 'available' => false],
        ['key' => 'semiconductors', 'name' => 'Semiconductor Devices', 'description' => 'ICs, diodes & sensor chips', 'available' => false],
        ['key' => 'capacitors', 'name' => 'Electrical Capacitors', 'description' => 'Ceramic, film & electrolytic', 'available' => false],
        ['key' => 'wires-cables', 'name' => 'Insulated Wires / Cables', 'description' => 'Power, data & control cables', 'available' => true],
    ];

    private const MATERIALS = [
        'electrical-machines' => [],
        'telecommunications' => [],
        'semiconductors' => [],
        'capacitors' => [],
        'wires-cables' => [
            ['name' => 'USB-C Cable', 'available' => true],
            ['name' => 'PVC Insulated Cable', 'available' => false],
            ['name' => 'XLPE Insulated Cable', 'available' => false],
            ['name' => 'Rubber Insulated Cable', 'available' => false],
            ['name' => 'Fiber Optic Cable', 'available' => false],
        ],
    ];

    public function __construct(
        private readonly ComplianceEngine $engine,
        private readonly ExportSessionFinalizer $finalizer,
    ) {}

    /**
     * Step 1 — pick a category and material. Nothing is persisted yet; the
     * session is only created once the AI engine has produced a questionnaire
     * (see store()), so an abandoned category pick never litters the history.
     */
    public function create(): View
    {
        return view('products.create', [
            'categories' => self::CATEGORIES,
            'materialsByCategory' => self::MATERIALS,
        ]);
    }

    /**
     * Creates the session and asks the compliance engine for its
     * questionnaire, then redirects into the resumable session view. Works
     * for guests (user_id null, ownership via guest_token) and logged-in
     * users alike.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_key' => ['required', 'string', 'in:'.implode(',', array_column(self::CATEGORIES, 'key'))],
            'material' => ['required', 'string', 'max:255'],
        ]);

        $category = collect(self::CATEGORIES)->firstWhere('key', $data['category_key']);
        $material = collect(self::MATERIALS[$data['category_key']] ?? [])->firstWhere('name', $data['material']);

        if (! $category['available'] || ! $material || ! $material['available']) {
            return back()->withInput()->withErrors([
                'material' => "{$data['material']} isn't available for screening yet — the rule engine currently only covers USB-C cables.",
            ]);
        }

        try {
            $questionnaire = $this->engine->generateQuestionnaire($category['key'], $category['name'], $data['material']);
        } catch (ScreeningNotAvailableException $e) {
            report($e);

            return back()->withInput()->withErrors([
                'material' => "{$data['material']} isn't available for screening yet.",
            ]);
        } catch (ComplianceEngineException $e) {
            report($e);

            return back()->withInput()->withErrors([
                'material' => "Couldn't prepare the questionnaire right now. Please try again in a moment.",
            ]);
        }

        $user = $request->user();

        $session = ExportSession::create([
            'user_id' => $user?->id,
            'guest_token' => $user ? null : Session::getId(),
            'category_key' => $category['key'],
            'category_name' => $category['name'],
            'material' => $data['material'],
            'status' => 'in_progress',
            'questions' => $questionnaire['questions'],
            'answers' => [],
            'engine' => config('compliance.driver'),
        ]);

        return redirect()->route('products.show', $session);
    }

    /**
     * Resumable session view: renders the questionnaire (pre-filled with any
     * previously saved answers) while in_progress, or the verdict once
     * completed. This is the URL a user returns to from History, or that a
     * guest gets redirected back to right after claiming their session.
     */
    public function show(Request $request, ExportSession $session): View
    {
        $this->authorize('view', $session);

        return view('products.show', ['session' => $session]);
    }

    /**
     * Lightweight autosave for partial answers, called as the user answers
     * each question — so leaving mid-questionnaire and coming back later
     * resumes exactly where they left off, not just at the last full step.
     */
    public function saveProgress(Request $request, ExportSession $session): Response
    {
        $this->authorize('update', $session);

        $data = $request->validate(['answers' => ['array']]);

        $session->update(['answers' => $data['answers'] ?? []]);

        return response()->noContent();
    }

    /**
     * Final submission. Logged-in users get scored immediately. A guest gets
     * sent to create an account first — their answers are already saved (via
     * saveProgress autosave), so ExportSessionFinalizer::claimPending() picks
     * this exact session back up right after they authenticate and scores it
     * then, landing them straight on the result.
     */
    public function submit(Request $request, ExportSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        $data = $request->validate(['answers' => ['required', 'array']]);

        $missing = $this->finalizer->missingAnswers($session->questions, $data['answers']);

        if (! empty($missing)) {
            return back()->withInput()->withErrors([
                'submit' => 'Please answer every question before submitting.',
            ]);
        }

        $session->update(['answers' => $data['answers']]);

        if (! $request->user()) {
            ExportSessionFinalizer::remember($session);

            return redirect()->route('register')->with(
                'status',
                "You're all set! Create a free account to see your compliance result.",
            );
        }

        try {
            $this->finalizer->finalize($session, $data['answers']);
        } catch (ComplianceEngineException $e) {
            report($e);

            return back()->withErrors([
                'submit' => "Couldn't score your submission right now. Please try again in a moment.",
            ]);
        }

        return redirect()->route('products.show', $session);
    }
}
