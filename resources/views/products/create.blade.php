<x-app-layout>
    <x-slot:title>New Export</x-slot:title>

    <x-slot:header>
        <div class="mx-auto flex max-w-5xl items-center gap-3">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="btn-ghost !px-2" aria-label="Back">
                <x-icon name="arrow-left" />
            </a>
            <h1 class="text-lg font-bold tracking-tight text-ink">New Export</h1>
        </div>
    </x-slot:header>

    <div x-data="categoryPicker()">
        {{-- Stepper --}}
        <ol class="flex items-center px-1">
            @foreach (['Category', 'Questionnaire', 'Results'] as $index => $label)
                @php $n = $index + 1; @endphp
                <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                    <div class="flex flex-col items-center gap-2">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                              :class="{{ $n }} === 1 ? 'bg-brand-600 text-white ring-4 ring-brand-100' : 'bg-surface-field text-ink-muted'">
                            {{ $n }}
                        </span>
                        <span class="text-xs font-semibold" :class="{{ $n }} === 1 ? 'text-ink' : 'text-ink-muted'">{{ $label }}</span>
                    </div>

                    @unless ($loop->last)
                        <div class="mx-3 h-0.5 flex-1 rounded bg-line"></div>
                    @endunless
                </li>
            @endforeach
        </ol>

        <x-card padding="p-6 sm:p-8" class="mt-8">
            <h2 class="text-2xl font-extrabold tracking-tight text-ink">Select Product Category</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-muted">
                Choose the group and material that best match the product you want to export.
                Screening today only covers <span class="font-semibold text-ink">Insulated Wires/Cables → USB-C Cable</span> —
                everything else is marked Coming Soon rather than faked.
            </p>

            <x-input-error :messages="$errors->get('material')" class="mt-4" />

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <button type="button"
                            @if ($category['available'])
                                x-on:click="selectCategory('{{ $category['key'] }}', '{{ $category['name'] }}')"
                            @else
                                disabled title="Coming soon"
                            @endif
                            class="relative flex flex-col items-start gap-3 rounded-xl border p-5 text-left transition"
                            :class="category?.key === '{{ $category['key'] }}'
                                ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600'
                                : '{{ $category['available'] ? 'border-line bg-white hover:border-brand-300' : 'cursor-default border-line bg-surface-sunken opacity-60' }}'">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-brand-600">
                            <x-icon name="box" class="h-5 w-5" />
                        </span>

                        <span>
                            <span class="flex items-center gap-2">
                                <span class="block text-sm font-bold text-ink">{{ $category['name'] }}</span>
                                @unless ($category['available'])
                                    <span class="rounded-full bg-surface-sunken px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-label text-ink-subtle">Soon</span>
                                @endunless
                            </span>
                            <span class="mt-0.5 block text-xs text-ink-muted">{{ $category['description'] }}</span>
                        </span>

                        <span x-show="category?.key === '{{ $category['key'] }}'" x-cloak
                              class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-white">
                            <x-icon name="check" class="h-3 w-3" />
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="mt-8 space-y-2" x-show="category" x-cloak>
                <label class="field-label-plain" for="material">Select Material</label>
                <div class="relative">
                    <select id="material" x-model="material" class="field-outline appearance-none pr-10">
                        <option value="" disabled>Choose a material...</option>
                        <template x-for="option in materials" :key="option.name">
                            <option :value="option.name" x-text="option.name + (option.available ? '' : ' (Coming Soon)')" :disabled="!option.available"></option>
                        </template>
                    </select>
                    <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-ink-subtle">
                        <x-icon name="chevron-down" class="h-4 w-4" />
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('products.store') }}" class="mt-8 flex justify-end border-t border-line pt-6">
                @csrf
                <input type="hidden" name="category_key" x-bind:value="category?.key">
                <input type="hidden" name="material" x-bind:value="material">

                <button type="submit"
                        class="btn-primary"
                        :class="{ 'cursor-not-allowed opacity-60': !canProceed }"
                        :disabled="!canProceed">
                    Generate Questionnaire
                </button>
            </form>
        </x-card>
    </div>

    @push('scripts')
        <script>
            function categoryPicker() {
                return {
                    category: null,
                    material: '',
                    materialsByCategory: @json($materialsByCategory),

                    get materials() {
                        return this.category ? (this.materialsByCategory[this.category.key] ?? []) : [];
                    },

                    get canProceed() {
                        const chosen = this.materials.find(option => option.name === this.material);
                        return this.category !== null && !!chosen && chosen.available;
                    },

                    selectCategory(key, name) {
                        if (this.category?.key !== key) {
                            this.material = '';
                        }

                        this.category = { key, name };
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
