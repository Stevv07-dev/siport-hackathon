<section class="space-y-5">
    <header>
        <h2 class="text-lg font-bold tracking-tight text-danger-700">Delete Account</h2>
        <p class="mt-1 max-w-xl text-sm leading-relaxed text-ink-muted">
            Once your account is deleted, all of its data and resources will be permanently removed.
            Download anything you want to keep before continuing.
        </p>
    </header>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        Delete Account
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold tracking-tight text-ink">Are you sure you want to delete your account?</h2>

            <p class="mt-2 text-sm leading-relaxed text-ink-muted">
                All data will be permanently deleted. Enter your password to confirm.
            </p>

            <div class="mt-6 space-y-2">
                <x-input-label for="password" value="Password" class="sr-only" />
                <x-form.password name="password" variant="outline"
                                 :invalid="$errors->userDeletion->has('password')"
                                 placeholder="Password" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-7 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-danger-button>Delete Account</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
