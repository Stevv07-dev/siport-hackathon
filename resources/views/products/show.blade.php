@php
    $completed = $session->isCompleted();
    $step = $completed ? 3 : 2;
@endphp

<x-app-layout>
    <x-slot:title>{{ $completed ? 'Screening Result' : 'Questionnaire' }}</x-slot:title>

    <x-slot:header>
        <div class="mx-auto flex max-w-5xl items-center gap-3">
            <a href="{{ auth()->check() ? route('history.index') : route('home') }}" class="btn-ghost !px-2" aria-label="Back">
                <x-icon name="arrow-left" />
            </a>
            <h1 class="text-lg font-bold tracking-tight text-ink">{{ $session->category_name }} — {{ $session->material }}</h1>
        </div>
    </x-slot:header>

    {{-- Stepper --}}
    <ol class="flex items-center px-1">
        @foreach (['Category', 'Questionnaire', 'Results'] as $index => $label)
            @php $n = $index + 1; @endphp
            <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                <div class="flex flex-col items-center gap-2">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold
                                 {{ $step > $n ? 'bg-brand-600 text-white' : ($step === $n ? 'bg-brand-600 text-white ring-4 ring-brand-100' : 'bg-surface-field text-ink-muted') }}">
                        @if ($step > $n)
                            <x-icon name="check" class="h-5 w-5" />
                        @else
                            {{ $n }}
                        @endif
                    </span>
                    <span class="text-xs font-semibold {{ $step >= $n ? 'text-ink' : 'text-ink-muted' }}">{{ $label }}</span>
                </div>

                @unless ($loop->last)
                    <div class="mx-3 h-0.5 flex-1 rounded {{ $step > $n ? 'bg-brand-600' : 'bg-line' }}"></div>
                @endunless
            </li>
        @endforeach
    </ol>

    <x-card padding="p-6 sm:p-8" class="mt-8">
        @if ($completed)
            {{-- Step 3 — Results --}}
            @php $passed = $session->passed(); @endphp

            <div class="rounded-lg border border-line bg-surface-sunken p-4 text-xs leading-relaxed text-ink-muted">
                This is an automated <span class="font-semibold text-ink">candidate</span> classification from the AI
                Engineer's rule engine, not an official customs ruling. Verify the candidate HS code and every next
                step below through the appropriate authority before relying on it.
            </div>

            <div class="mt-4 {{ $passed ? 'alert-success' : 'alert-danger' }}">
                <x-icon :name="$passed ? 'shield-check' : 'alert-triangle'" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="text-sm font-bold">{{ $passed ? 'Candidate Classification Found' : 'Needs Manual Review' }}</p>
                    <p class="mt-0.5 text-sm">{{ $session->result['summary'] ?? '' }}</p>
                </div>
            </div>

            @if (! empty($session->result['issues']))
                <div class="mt-6 space-y-3">
                    <h3 class="text-sm font-bold uppercase tracking-label text-ink-muted">
                        Next Steps ({{ count($session->result['issues']) }})
                    </h3>

                    @foreach ($session->result['issues'] as $issue)
                        <div class="rounded-lg border border-line bg-surface-sunken p-4">
                            <p class="text-sm font-bold text-ink">{{ $issue['title'] }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ $issue['detail'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <dl class="mt-6 divide-y divide-line rounded-xl border border-line">
                @foreach ($session->questions as $question)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="text-sm text-ink-muted">{{ $question['label'] }}</dt>
                        <dd class="max-w-[60%] text-right text-sm font-semibold text-ink">
                            {{ $session->answers[$question['key']] ?? '—' }}{{ isset($question['unit']) && isset($session->answers[$question['key']]) ? ' '.$question['unit'] : '' }}
                            @if (! empty($session->answers[$question['key'].'_detail']))
                                <span class="block text-xs font-normal text-ink-muted">{{ $session->answers[$question['key'].'_detail'] }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>

            <p class="mt-6 text-xs text-ink-subtle">
                Submitted {{ $session->submitted_at->diffForHumans() }} · Engine: {{ $session->engine }}
            </p>

            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('products.create') }}" class="btn-primary">Start New Screening</a>
                <a href="{{ route('history.index') }}" class="btn-secondary">Back to History</a>
            </div>
        @else
            {{-- Step 2 — Questionnaire --}}
            {{--
                Js::from() (not @json) is required here: @json's raw output contains
                literal `"` characters that terminate this double-quoted HTML
                attribute early once a session has saved answers, corrupting the
                whole x-data expression. Js::from() escapes it safely for embedding.
            --}}
            <div x-data="questionnaire(
                    {{ \Illuminate\Support\Js::from($session->questions) }},
                    {{ \Illuminate\Support\Js::from($session->answers ?: (object) []) }},
                    '{{ route('products.progress', $session) }}'
                 )">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="text-2xl font-extrabold tracking-tight text-ink">Material Verification</h2>
                    <p class="shrink-0 text-sm font-semibold text-ink-muted"
                       x-text="'Question ' + (currentIndex + 1) + ' of ' + totalQuestions"></p>
                </div>
                <p class="mt-2 text-sm leading-relaxed text-ink-muted">
                    Answer one question at a time for {{ $session->material }}. Some questions only appear
                    depending on how you answer earlier ones.
                </p>

                {{-- Progress bar --}}
                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-surface-field">
                    <div class="h-full rounded-full bg-brand-600 transition-all duration-300"
                         :style="`width: ${((currentIndex + 1) / totalQuestions) * 100}%`"></div>
                </div>

                <x-input-error :messages="$errors->get('submit')" class="mt-4" />
                <x-input-error :messages="$errors->get('material')" class="mt-4" />

                <form method="POST" action="{{ route('products.submit', $session) }}"
                      x-on:change="saveProgress()">
                    @csrf

                    <div class="mt-6">
                        @foreach ($session->questions as $question)
                            <div x-show="currentKey === '{{ $question['key'] }}'" x-cloak
                                 class="rounded-lg bg-surface-sunken p-5">
                                <p class="text-base font-semibold text-ink">{{ $question['label'] }}</p>

                                @if (! empty($question['reason']))
                                    <p class="mt-1 text-xs text-ink-subtle">{{ $question['reason'] }}</p>
                                @endif

                                @if ($question['type'] === 'enum')
                                    <div class="relative mt-4">
                                        <select name="answers[{{ $question['key'] }}]"
                                                x-model="answers['{{ $question['key'] }}']"
                                                class="field-outline appearance-none bg-white pr-10">
                                            {{--
                                                "selected" is required, not just "disabled": without it,
                                                browsers auto-select the first enabled <option> (skipping
                                                this placeholder) as the select's real DOM value even
                                                though Alpine's own answers[] state stays empty — so a
                                                native form submit (JS disabled, or the disabled-button
                                                guard bypassed) could silently send an answer the user
                                                never actually chose.
                                            --}}
                                            <option value="" disabled selected>Select an option...</option>
                                            @foreach ($question['options'] ?? [] as $option)
                                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-ink-subtle">
                                            <x-icon name="chevron-down" class="h-4 w-4" />
                                        </span>
                                    </div>
                                @elseif ($question['type'] === 'text')
                                    <input type="text" name="answers[{{ $question['key'] }}]"
                                           x-model="answers['{{ $question['key'] }}']"
                                           class="field-outline mt-4 bg-white" placeholder="Type your answer...">
                                @elseif ($question['type'] === 'number' || $question['type'] === 'integer')
                                    <div class="relative mt-4">
                                        <input type="number" @if ($question['type'] === 'integer') step="1" @else step="any" @endif
                                               name="answers[{{ $question['key'] }}]"
                                               x-model="answers['{{ $question['key'] }}']"
                                               class="field-outline bg-white {{ isset($question['unit']) ? 'pr-14' : '' }}"
                                               placeholder="0">
                                        @if (isset($question['unit']))
                                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-ink-subtle">{{ $question['unit'] }}</span>
                                        @endif
                                    </div>
                                @elseif ($question['type'] === 'boolean' || $question['type'] === 'boolean_plus_text')
                                    <div class="mt-4 flex gap-3">
                                        <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border py-3 text-sm font-semibold transition"
                                               :class="answers['{{ $question['key'] }}'] === 'yes' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line-strong bg-white text-ink-muted hover:border-brand-300'">
                                            <input type="radio" name="answers[{{ $question['key'] }}]" value="yes"
                                                   x-model="answers['{{ $question['key'] }}']" class="sr-only">
                                            Yes
                                        </label>
                                        <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border py-3 text-sm font-semibold transition"
                                               :class="answers['{{ $question['key'] }}'] === 'no' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line-strong bg-white text-ink-muted hover:border-brand-300'">
                                            <input type="radio" name="answers[{{ $question['key'] }}]" value="no"
                                                   x-model="answers['{{ $question['key'] }}']" class="sr-only">
                                            No
                                        </label>
                                    </div>

                                    @if ($question['type'] === 'boolean_plus_text')
                                        <textarea name="answers[{{ $question['key'] }}_detail]"
                                                  x-model="answers['{{ $question['key'] }}_detail']"
                                                  rows="2" class="field-outline mt-3 bg-white"
                                                  placeholder="Explain..."></textarea>
                                    @endif
                                @else
                                    {{--
                                        backend-ai defines an answer_type this view doesn't know how to render
                                        yet — say so honestly instead of silently dropping the question or
                                        guessing at an input.
                                    --}}
                                    <p class="mt-4 text-sm text-danger-600">
                                        This question's answer format ("{{ $question['type'] }}") isn't supported by
                                        this screen yet.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8 flex items-center justify-between border-t border-line pt-6">
                        <button type="button" x-on:click="back()" x-show="!isFirst" x-cloak class="btn-secondary">
                            Back
                        </button>
                        <span x-show="isFirst"></span>

                        <div class="flex items-center gap-3">
                            <p class="text-xs text-ink-muted" x-show="savedAt" x-cloak x-text="'Saved ' + savedAt"></p>

                            <button type="button" x-show="!isLast" x-cloak
                                    x-on:click="next()"
                                    class="btn-primary"
                                    :class="{ 'cursor-not-allowed opacity-60': !currentAnswered }"
                                    :disabled="!currentAnswered">
                                Next Question
                            </button>

                            <button type="submit" x-show="isLast" x-cloak
                                    class="btn-primary"
                                    :class="{ 'cursor-not-allowed opacity-60': !allAnswered }"
                                    :disabled="!allAnswered">
                                Submit for Compliance Review
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </x-card>

    @unless ($completed)
        @push('scripts')
            <script>
                // Mirrors app/Services/Compliance/TriggerEvaluator.php's grammar exactly
                // (attribute op value, joined by " AND ") so a question that's hidden
                // client-side stays hidden server-side too, and vice versa. A trigger
                // this can't parse ("shipment setup", "classification remains
                // ambiguous") is narrative, not a gate — such a question is always
                // shown, never silently skipped.
                function isTriggerParseable(trigger) {
                    return trigger.split(/\s+AND\s+/).every((clause) => parseClause(clause) !== null);
                }

                function evaluateTrigger(trigger, answers) {
                    return trigger.split(/\s+AND\s+/).every((clause) => {
                        const parsed = parseClause(clause);
                        if (! parsed) return false;

                        const [attribute, operator, expected] = parsed;
                        if (! (attribute in answers) || answers[attribute] === '') return false;

                        return compare(coerce(answers[attribute]), operator, expected);
                    });
                }

                function parseClause(clause) {
                    clause = clause.trim();

                    for (const operator of ['==', '!=', '<=', '>=', '<', '>']) {
                        const pos = clause.indexOf(operator);
                        if (pos === -1) continue;

                        const attribute = clause.slice(0, pos).trim();
                        const rawValue = clause.slice(pos + operator.length).trim();
                        if (! attribute || ! rawValue) return null;

                        return [attribute, operator, parseValue(rawValue)];
                    }

                    return null;
                }

                function parseValue(raw) {
                    raw = raw.trim().replace(/^["']|["']$/g, '');
                    if (/^true$/i.test(raw)) return true;
                    if (/^false$/i.test(raw)) return false;
                    if (raw !== '' && ! isNaN(raw)) return Number(raw);
                    return raw;
                }

                function coerce(value) {
                    if (value === 'yes') return true;
                    if (value === 'no') return false;
                    if (typeof value === 'string' && value !== '' && ! isNaN(value)) return Number(value);
                    return value;
                }

                function compare(actual, operator, expected) {
                    switch (operator) {
                        case '==': return actual == expected;
                        case '!=': return actual != expected;
                        case '<=': return actual <= expected;
                        case '>=': return actual >= expected;
                        case '<': return actual < expected;
                        case '>': return actual > expected;
                        default: return false;
                    }
                }

                function questionnaire(questionsData, initialAnswers, progressUrl) {
                    // Every question key must start as an explicit '' (not just
                    // absent) — otherwise x-model tries to sync an <select> to
                    // JS `undefined`, which stringifies to "undefined" and
                    // matches no <option>, so the browser silently keeps
                    // whatever it auto-selected before Alpine ran (its first
                    // enabled option) instead of the empty placeholder.
                    const answers = {};
                    questionsData.forEach((q) => {
                        answers[q.key] = initialAnswers[q.key] ?? '';
                        if (q.type === 'boolean_plus_text') {
                            answers[q.key + '_detail'] = initialAnswers[q.key + '_detail'] ?? '';
                        }
                    });

                    return {
                        answers,
                        savedAt: '',
                        questions: questionsData,
                        currentIndex: 0,

                        init() {
                            // Resuming a session with some answers already saved should
                            // land on the first unanswered visible question, not force
                            // re-clicking through ones already done.
                            const visible = this.visibleQuestions;
                            const firstUnanswered = visible.findIndex((q) => ! this.isAnswered(q));
                            this.currentIndex = firstUnanswered === -1 ? Math.max(visible.length - 1, 0) : firstUnanswered;
                        },

                        questionApplies(question) {
                            if (! question.trigger || ! isTriggerParseable(question.trigger)) {
                                return true;
                            }

                            return evaluateTrigger(question.trigger, this.answers);
                        },

                        get visibleQuestions() {
                            return this.questions.filter((q) => this.questionApplies(q));
                        },

                        get totalQuestions() {
                            return this.visibleQuestions.length;
                        },

                        get currentKey() {
                            return this.visibleQuestions[this.currentIndex]?.key;
                        },

                        get isFirst() {
                            return this.currentIndex === 0;
                        },

                        get isLast() {
                            return this.currentIndex === this.totalQuestions - 1;
                        },

                        isAnswered(question) {
                            const value = this.answers[question.key];
                            if (value === '' || value === null || value === undefined) return false;

                            if (question.type === 'boolean_plus_text') {
                                return !! this.answers[question.key + '_detail'];
                            }

                            return true;
                        },

                        get currentAnswered() {
                            const question = this.visibleQuestions[this.currentIndex];
                            return question ? this.isAnswered(question) : false;
                        },

                        get allAnswered() {
                            return this.visibleQuestions.every((q) => this.isAnswered(q));
                        },

                        next() {
                            if (this.currentAnswered && ! this.isLast) {
                                this.currentIndex++;
                            }
                        },

                        back() {
                            if (! this.isFirst) {
                                this.currentIndex--;
                            }
                        },

                        saveProgress() {
                            fetch(progressUrl, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ answers: this.answers }),
                            }).then((response) => {
                                if (response.ok) {
                                    this.savedAt = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                }
                            }).catch(() => {});
                        },
                    };
                }
            </script>
        @endpush
    @endunless
</x-app-layout>
